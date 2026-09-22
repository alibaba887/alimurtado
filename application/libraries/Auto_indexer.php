<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auto_indexer Library
 * 
 * Otomatisasi indexing artikel baru ke search engine:
 * 1. IndexNow API (Bing, Yandex, Seznam, Naver, dll.)
 * 2. Google Search Console Sitemaps Refresh API (OAuth 2.0)
 * 3. Google Web Search Indexing API (Service Account JWT)
 *
 * @package Application\Libraries
 * @author Moh. Ali Murtado
 */
class Auto_indexer {

    protected $CI;

    // IndexNow configuration
    private $indexnow_host = 'alimurtado.com';
    private $indexnow_key = 'c895ace0e48ed3da971c2c9af525798a';
    private $indexnow_endpoint = 'https://api.indexnow.org/indexnow';

    // Google configuration paths
    private $oauth_config_path;
    private $sa_config_path;

    // In-memory token cache for batch efficiency
    private $cached_oauth_token = null;
    private $cached_sa_token = null;

    public function __construct() {
        $this->CI =& get_instance();
        $this->oauth_config_path = APPPATH . 'config/google_oauth.json';
        $this->sa_config_path    = APPPATH . 'config/google_service_account.json';
    }

    /**
     * Submit single URL to all indexing providers
     *
     * @param string $url URL artikel yang dipublish
     * @param int|null $queue_id ID konten_publish jika ada
     * @param int|null $blog_id ID blog jika ada
     * @return array Hasil pengiriman ke masing-masing engine
     */
    public function index_url($url, $queue_id = null, $blog_id = null) {
        // Resolve blog_id dari URL jika belum ada
        if (!$blog_id && preg_match('#/post/(\d+)(?:-|$)#', $url, $matches)) {
            $blog_id = (int)$matches[1];
        }

        $results = [
            'url'              => $url,
            'queue_id'         => $queue_id,
            'blog_id'          => $blog_id,
            'indexnow'         => ['success' => false, 'code' => 0, 'message' => ''],
            'google_sitemap'   => ['success' => false, 'code' => 0, 'message' => ''],
            'google_indexing'  => ['success' => false, 'code' => 0, 'message' => ''],
            'timestamp'        => date('Y-m-d H:i:s')
        ];

        // 1. Submit ke IndexNow (Bing, Yandex, dll)
        try {
            $results['indexnow'] = $this->submit_indexnow([$url]);
        } catch (Exception $e) {
            $results['indexnow']['message'] = $e->getMessage();
        }

        // 2. Submit / Refresh Sitemap ke Google Search Console via OAuth API
        try {
            $results['google_sitemap'] = $this->ping_google_sitemap();
        } catch (Exception $e) {
            $results['google_sitemap']['message'] = $e->getMessage();
        }

        // 3. Submit langsung ke Google Indexing API via Service Account
        try {
            $results['google_indexing'] = $this->submit_google_indexing($url, 'URL_UPDATED');
        } catch (Exception $e) {
            $results['google_indexing']['message'] = $e->getMessage();
        }

        // 4. Update status di tabel konten_publish jika queue_id atau url diberikan
        if ($queue_id) {
            $this->CI->db->where('id', $queue_id)->update('konten_publish', [
                'gsc' => '1'
            ]);
        } elseif ($url) {
            $this->CI->db->where('link', $url)->update('konten_publish', [
                'gsc' => '1'
            ]);
        }

        // 5. Update status di tabel blog jika blog_id diketahui
        if ($blog_id) {
            $in_code  = isset($results['indexnow']['code']) ? (int)$results['indexnow']['code'] : 0;
            $gsc_code = isset($results['google_sitemap']['code']) ? (int)$results['google_sitemap']['code'] : 0;
            $api_code = isset($results['google_indexing']['code']) ? (int)$results['google_indexing']['code'] : 0;

            $now_dt = date('Y-m-d H:i:s');
            $summary_log = "IndexNow: {$in_code} | GSC Sitemap: {$gsc_code} | Google API: {$api_code}";

            $this->CI->db->where('blog_id', $blog_id)->update('blog', [
                'indexnow_status'        => $results['indexnow']['success'] ? 1 : ($in_code > 0 ? 2 : 0),
                'gsc_status'             => $results['google_sitemap']['success'] ? 1 : ($gsc_code > 0 ? 2 : 0),
                'google_indexing_status' => $results['google_indexing']['success'] ? 1 : ($api_code > 0 ? 2 : 0),
                'last_indexed_at'        => $now_dt,
                'indexing_log'           => $summary_log
            ]);
        }

        // Log hasil indexing
        log_message('info', 'Auto_indexer: ' . json_encode($results));

        return $results;
    }

    /**
     * Submit kumpulan URL sekaligus (batch)
     * IndexNow dipanggil sekali untuk seluruh URL, GSC Sitemap di-refresh sekali,
     * lalu Google Indexing API diproses per-URL.
     *
     * @param array $items Array berisi list ['blog_id' => ..., 'url' => ...]
     * @return array
     */
    public function index_batch($items = []) {
        if (empty($items)) {
            return ['success' => false, 'message' => 'Daftar artikel kosong'];
        }

        $url_list = [];
        foreach ($items as $item) {
            if (!empty($item['url'])) {
                $url_list[] = $item['url'];
            }
        }

        // 1. Bulk submit ke IndexNow (hingga 10.000 URL per call)
        $indexnow_res = $this->submit_indexnow($url_list);

        // 2. Ping refresh GSC Sitemap 1x
        $gsc_res = $this->ping_google_sitemap();

        $processed = 0;
        $details   = [];

        $now_dt = date('Y-m-d H:i:s');
        $in_code  = isset($indexnow_res['code']) ? (int)$indexnow_res['code'] : 0;
        $gsc_code = isset($gsc_res['code']) ? (int)$gsc_res['code'] : 0;

        foreach ($items as $item) {
            $url     = $item['url'];
            $blog_id = isset($item['blog_id']) ? (int)$item['blog_id'] : 0;

            // Google Indexing API per URL
            $api_res = ['success' => false, 'code' => 0];
            try {
                $api_res = $this->submit_google_indexing($url, 'URL_UPDATED');
            } catch (Exception $e) {
                $api_res['message'] = $e->getMessage();
            }

            $api_code = isset($api_res['code']) ? (int)$api_res['code'] : 0;
            $summary_log = "IndexNow: {$in_code} | GSC Sitemap: {$gsc_code} | Google API: {$api_code}";

            if ($blog_id) {
                $this->CI->db->where('blog_id', $blog_id)->update('blog', [
                    'indexnow_status'        => $indexnow_res['success'] ? 1 : ($in_code > 0 ? 2 : 0),
                    'gsc_status'             => $gsc_res['success'] ? 1 : ($gsc_code > 0 ? 2 : 0),
                    'google_indexing_status' => $api_res['success'] ? 1 : ($api_code > 0 ? 2 : 0),
                    'last_indexed_at'        => $now_dt,
                    'indexing_log'           => $summary_log
                ]);
            }

            // Sync ke konten_publish
            $this->CI->db->where('link', $url)->update('konten_publish', ['gsc' => '1']);

            $processed++;
            $details[] = [
                'blog_id' => $blog_id,
                'url'     => $url,
                'api_code'=> $api_code
            ];
        }

        return [
            'success'   => true,
            'processed' => $processed,
            'indexnow'  => $indexnow_res,
            'gsc_sitemap' => $gsc_res,
            'details'   => $details
        ];
    }

    /**
     * Kirim URL ke IndexNow API (Bing, Yandex, dll.)
     *
     * @param array $url_list Daftar URL artikel
     * @return array
     */
    public function submit_indexnow($url_list = []) {
        if (empty($url_list)) {
            return ['success' => false, 'code' => 0, 'message' => 'Daftar URL kosong'];
        }

        $payload = [
            'host'        => $this->indexnow_host,
            'key'         => $this->indexnow_key,
            'keyLocation' => 'https://' . $this->indexnow_host . '/' . $this->indexnow_key . '.txt',
            'urlList'     => array_values($url_list)
        ];

        $ch = curl_init($this->indexnow_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // HTTP 200 atau 202 = Berhasil diterima IndexNow
        $success = in_array($http_code, [200, 202]);

        return [
            'success'  => $success,
            'code'     => $http_code,
            'response' => $response ?: $error,
            'message'  => $success ? 'Berhasil dikirim ke IndexNow' : 'IndexNow gagal: ' . ($response ?: $error)
        ];
    }

    /**
     * Ping Google Search Console Sitemaps API untuk me-refresh sitemap.xml
     *
     * @return array
     */
    public function ping_google_sitemap() {
        if (!file_exists($this->oauth_config_path)) {
            return ['success' => false, 'code' => 0, 'message' => 'File google_oauth.json tidak ditemukan'];
        }

        $oauth = json_decode(file_get_contents($this->oauth_config_path), true);
        if (empty($oauth['refresh_token']) || empty($oauth['client_id']) || empty($oauth['client_secret'])) {
            return ['success' => false, 'code' => 0, 'message' => 'OAuth config tidak lengkap'];
        }

        // 1. Dapatkan access token dari refresh token (atau cache)
        if (!$this->cached_oauth_token) {
            $token_ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($token_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($token_ch, CURLOPT_POST, true);
            curl_setopt($token_ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($token_ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id'     => $oauth['client_id'],
                'client_secret' => $oauth['client_secret'],
                'refresh_token' => $oauth['refresh_token'],
                'grant_type'    => 'refresh_token'
            ]));
            $token_raw = curl_exec($token_ch);
            curl_close($token_ch);

            $token_data = json_decode($token_raw, true);
            if (empty($token_data['access_token'])) {
                return ['success' => false, 'code' => 0, 'message' => 'Gagal refresh Google OAuth token: ' . $token_raw];
            }

            $this->cached_oauth_token = $token_data['access_token'];
        }

        $access_token = $this->cached_oauth_token;

        // 2. Submit sitemap via Google Search Console API (PUT method)
        $site_url = urlencode('sc-domain:' . $this->indexnow_host);
        $feedpath = urlencode('https://' . $this->indexnow_host . '/sitemap.xml');
        $api_url  = "https://www.googleapis.com/webmasters/v3/sites/{$site_url}/sitemaps/{$feedpath}";

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Length: 0'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = in_array($http_code, [200, 204]);

        return [
            'success'  => $success,
            'code'     => $http_code,
            'response' => $response,
            'message'  => $success ? 'Google Search Console Sitemap berhasil di-refresh' : 'Gagal submit sitemap ke GSC'
        ];
    }

    /**
     * Submit URL ke Google Web Search Indexing API via Service Account JWT
     *
     * @param string $url URL yang ingin di-index
     * @param string $action 'URL_UPDATED' atau 'URL_DELETED'
     * @return array
     */
    public function submit_google_indexing($url, $action = 'URL_UPDATED') {
        if (!file_exists($this->sa_config_path)) {
            return ['success' => false, 'code' => 0, 'message' => 'File google_service_account.json tidak ditemukan'];
        }

        $sa = json_decode(file_get_contents($this->sa_config_path), true);
        if (empty($sa['client_email']) || empty($sa['private_key'])) {
            return ['success' => false, 'code' => 0, 'message' => 'Service account config tidak lengkap'];
        }

        // 1 & 2. Dapatkan access token dari Service Account JWT (atau cache)
        if (!$this->cached_sa_token) {
            $now = time();
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];
            $claim = [
                'iss'   => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/indexing',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now
            ];

            $encoded_header = $this->_base64_url_encode(json_encode($header));
            $encoded_claim  = $this->_base64_url_encode(json_encode($claim));
            $data_to_sign   = $encoded_header . '.' . $encoded_claim;

            $signature = '';
            $sign_success = openssl_sign($data_to_sign, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
            if (!$sign_success) {
                return ['success' => false, 'code' => 0, 'message' => 'Gagal membuat signature JWT dengan private key'];
            }

            $jwt = $data_to_sign . '.' . $this->_base64_url_encode($signature);

            // Tukar JWT dengan Google Access Token
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]));
            $token_raw = curl_exec($ch);
            curl_close($ch);

            $token_data = json_decode($token_raw, true);
            if (empty($token_data['access_token'])) {
                return ['success' => false, 'code' => 0, 'message' => 'Gagal autentikasi Service Account: ' . $token_raw];
            }

            $this->cached_sa_token = $token_data['access_token'];
        }

        $access_token = $this->cached_sa_token;

        // 3. Kirim publish notification ke Google Indexing API
        $indexing_ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
        curl_setopt($indexing_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($indexing_ch, CURLOPT_POST, true);
        curl_setopt($indexing_ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($indexing_ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($indexing_ch, CURLOPT_POSTFIELDS, json_encode([
            'url'  => $url,
            'type' => $action
        ]));

        $response = curl_exec($indexing_ch);
        $http_code = curl_getinfo($indexing_ch, CURLINFO_HTTP_CODE);
        curl_close($indexing_ch);

        $success = ($http_code === 200);

        return [
            'success'  => $success,
            'code'     => $http_code,
            'response' => json_decode($response, true) ?: $response,
            'message'  => $success ? 'Berhasil dikirim ke Google Indexing API' : 'Google Indexing API status ' . $http_code
        ];
    }

    /**
     * Inspeksi URL resmi langsung ke Google Search Console URL Inspection API
     *
     * @param string $url URL artikel yang ingin diinspeksi
     * @param int|null $blog_id ID artikel jika ada
     * @return array Hasil inspeksi resmi GSC
     */
    public function inspect_url($url, $blog_id = null) {
        if (!$blog_id && preg_match('#/post/(\d+)(?:-|$)#', $url, $matches)) {
            $blog_id = (int)$matches[1];
        }

        if (!file_exists($this->oauth_config_path)) {
            return ['success' => false, 'message' => 'File google_oauth.json tidak ditemukan'];
        }

        $oauth = json_decode(file_get_contents($this->oauth_config_path), true);
        if (empty($oauth['refresh_token']) || empty($oauth['client_id']) || empty($oauth['client_secret'])) {
            return ['success' => false, 'message' => 'OAuth config tidak lengkap'];
        }

        // 1. Dapatkan access token (atau gunakan cache)
        if (!$this->cached_oauth_token) {
            $token_ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($token_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($token_ch, CURLOPT_POST, true);
            curl_setopt($token_ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($token_ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id'     => $oauth['client_id'],
                'client_secret' => $oauth['client_secret'],
                'refresh_token' => $oauth['refresh_token'],
                'grant_type'    => 'refresh_token'
            ]));
            $token_raw = curl_exec($token_ch);
            curl_close($token_ch);

            $token_data = json_decode($token_raw, true);
            if (empty($token_data['access_token'])) {
                return ['success' => false, 'message' => 'Gagal refresh Google OAuth token: ' . $token_raw];
            }

            $this->cached_oauth_token = $token_data['access_token'];
        }

        $access_token = $this->cached_oauth_token;

        // 2. Panggil GSC URL Inspection API
        $inspect_ch = curl_init('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect');
        curl_setopt($inspect_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($inspect_ch, CURLOPT_POST, true);
        curl_setopt($inspect_ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($inspect_ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($inspect_ch, CURLOPT_POSTFIELDS, json_encode([
            'inspectionUrl' => $url,
            'siteUrl'       => 'sc-domain:' . $this->indexnow_host
        ]));

        $response_raw = curl_exec($inspect_ch);
        $http_code    = curl_getinfo($inspect_ch, CURLINFO_HTTP_CODE);
        curl_close($inspect_ch);

        $res_json = json_decode($response_raw, true);

        if ($http_code !== 200 || empty($res_json['inspectionResult'])) {
            return [
                'success'   => false,
                'code'      => $http_code,
                'message'   => isset($res_json['error']['message']) ? $res_json['error']['message'] : 'Gagal melakukan inspeksi URL di GSC',
                'raw'       => $res_json
            ];
        }

        $inspect_res = $res_json['inspectionResult'];
        $status_res  = isset($inspect_res['indexStatusResult']) ? $inspect_res['indexStatusResult'] : [];

        $verdict          = isset($status_res['verdict']) ? $status_res['verdict'] : 'VERDICT_UNSPECIFIED';
        $coverage_state   = isset($status_res['coverageState']) ? $status_res['coverageState'] : 'Unknown';
        $robots_txt       = isset($status_res['robotsTxtState']) ? $status_res['robotsTxtState'] : '-';
        $indexing_state   = isset($status_res['indexingState']) ? $status_res['indexingState'] : '-';
        $last_crawl_raw   = isset($status_res['lastCrawlTime']) ? $status_res['lastCrawlTime'] : null;
        $last_crawl_time  = $last_crawl_raw ? date('Y-m-d H:i:s', strtotime($last_crawl_raw)) : null;
        $page_fetch_state = isset($status_res['pageFetchState']) ? $status_res['pageFetchState'] : '-';
        $google_canonical = isset($status_res['googleCanonical']) ? $status_res['googleCanonical'] : '-';
        $referring_urls   = isset($status_res['referringUrls']) ? $status_res['referringUrls'] : [];
        $inspect_link     = isset($inspect_res['inspectionResultLink']) ? $inspect_res['inspectionResultLink'] : '';

        $clean_data = [
            'url'              => $url,
            'blog_id'          => $blog_id,
            'verdict'          => $verdict,
            'coverage_state'   => $coverage_state,
            'robots_txt_state' => $robots_txt,
            'indexing_state'   => $indexing_state,
            'last_crawl_time'  => $last_crawl_time,
            'last_crawl_raw'   => $last_crawl_raw,
            'page_fetch_state' => $page_fetch_state,
            'google_canonical' => $google_canonical,
            'referring_urls'   => $referring_urls,
            'inspection_link'  => $inspect_link,
            'inspected_at'     => date('Y-m-d H:i:s')
        ];

        // 3. Simpan hasil inspeksi resmi ke database tabel blog
        if ($blog_id) {
            $this->CI->db->where('blog_id', $blog_id)->update('blog', [
                'gsc_verdict'        => $verdict,
                'gsc_coverage_state' => $coverage_state,
                'gsc_last_crawl_time'=> $last_crawl_time,
                'gsc_inspection_json'=> json_encode($clean_data)
            ]);
        }

        return [
            'success' => true,
            'code'    => 200,
            'data'    => $clean_data
        ];
    }

    /**
     * Base64 URL safe encode
     */
    private function _base64_url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
