<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Ai_blog_queue {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper(array('url', 'form'));
    }

    /**
     * Memproses 1 artikel dari antrean `konten_publish`
     * 
     * @param bool $force Jika true, abaikan status ai_blog_cron_status (untuk eksekusi manual)
     * @return array
     */
    public function process($force = false) {
        // 1. Cek status switch cron jika tidak di-force
        if (!$force) {
            $cron_setting = $this->CI->db->where('key', 'ai_blog_cron_status')->get('settings')->row();
            $cron_active = ($cron_setting && $cron_setting->value === '1');

            if (!$cron_active) {
                return [
                    'success' => false,
                    'code'    => 'cron_disabled',
                    'message' => 'Auto-publish sedang NONAKTIF (OFF). Aktifkan terlebih dahulu untuk memproses antrean secara otomatis.'
                ];
            }
        }

        // 2. Ambil 1 antrean konten yang belum dipublish
        // Query: SELECT * FROM konten_publish WHERE nama_file IS NULL AND terpublish = 0 LIMIT 1
        $queue_item = $this->CI->db
            ->where('nama_file IS NULL', null, false)
            ->where('terpublish', 0)
            ->order_by('id', 'ASC')
            ->limit(1)
            ->get('konten_publish')
            ->row();

        if (!$queue_item) {
            return [
                'success' => false,
                'code'    => 'queue_empty',
                'message' => 'Antrean kosong! Tidak ada artikel pending di tabel konten_publish.'
            ];
        }

        $queue_id = $queue_item->id;
        $title = trim($queue_item->title);
        $kategori_name = trim($queue_item->blok_kategori);

        // 3. Cari ID Kategori yang sesuai
        $category_id = 19; // Default: Automation & AI in Advertising
        if (!empty($kategori_name)) {
            $cat_row = $this->CI->db->like('title', $kategori_name)->get('blog_categories')->row();
            if ($cat_row) {
                $category_id = $cat_row->blog_category_id;
            }
        }

        // 4. Siapkan prompt untuk Gemini Proxy
        $sample_format_guide = "Gaya penulisan wajib mengikuti gaya artikel acuan milik Moh. Ali Murtado (pakar Digital Advertiser):\n";
        $sample_format_guide .= "- Judul menarik, spesifik, dan SEO-friendly.\n";
        $sample_format_guide .= "- Intro pembuka yang menyoroti dilema/masalah nyata pengiklan.\n";
        $sample_format_guide .= "- Sub-heading (h2 dan h3) yang mendalam dan mudah dipahami.\n";
        $sample_format_guide .= "- WAJIB menyertakan minimal 1 TABEL PERBANDINGAN (format HTML <table><tr><th>...</th></tr><tr><td>...</td></tr></table>).\n";
        $sample_format_guide .= "- Menggunakan poin-poin terstruktur (<ol>, <ul>, <li>).\n";
        $sample_format_guide .= "- Menggunakan terminologi praktis (ROAS, CPA, CTR, Creative Fatigue, Hook, Conversion Rate, Testing, Scaling).\n";
        $sample_format_guide .= "- Paragraf penutup dengan kesimpulan yang tajam dan ajakan santai berdiskusi.\n";

        $prompt = "Anda adalah Moh. Ali Murtado, praktisi Digital Advertising profesional di Indonesia, Co-Founder Impacta.\n";
        $prompt .= $sample_format_guide . "\n";
        $prompt .= "TULIS ARTIKEL BLOG LENGKAP UNTUK TOPIK: \"" . $title . "\"\n";
        $prompt .= "Kategori Target: " . $kategori_name . " (ID: " . $category_id . ")\n\n";
        $prompt .= "OUTPUT WAJIB MURNI DALAM FORMAT JSON (tanpa kutip markdown ```json) dengan skema:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"" . addslashes($title) . "\",\n";
        $prompt .= "  \"category_id\": " . $category_id . ",\n";
        $prompt .= "  \"short_description\": \"Ringkasan sangat singkat artikel (MAKSIMAL 90 karakter)\",\n";
        $prompt .= "  \"meta_description\": \"Deskripsi SEO untuk pencarian Google (140-160 karakter)\",\n";
        $prompt .= "  \"meta_keywords\": \"5-8 kata kunci relevan dipisahkan koma\",\n";
        $prompt .= "  \"image_keyword\": \"digital marketing advertising\",\n";
        $prompt .= "  \"content\": \"Konten lengkap dalam format HTML (gunakan <h2>, <h3>, <p>, <ul>, <li>, <table>, <tr>, <th>, <td>, <strong>). Panjang minimal 600-900 kata.\"\n";
        $prompt .= "}";

        // 5. Panggil Gemini Proxy Lokal
        $ch = curl_init("http://127.0.0.1:56675/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer sk-gemini-proxy-alibaba-887",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gemini-flash",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "stream" => false
        ]));
        $raw_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!$raw_response) {
            return [
                'success' => false,
                'code'    => 'proxy_connection_failed',
                'message' => 'Gagal menghubungi Gemini Proxy: ' . $curl_error
            ];
        }

        $api_json = json_decode($raw_response, true);
        $content_str = $api_json['choices'][0]['message']['content'] ?? '';
        if (empty($content_str)) {
            return [
                'success' => false,
                'code'    => 'ai_empty_response',
                'message' => 'AI mengembalikan respon kosong.'
            ];
        }

        $clean_json = preg_replace('/^```(?:json)?\s*/i', '', trim($content_str));
        $clean_json = preg_replace('/\s*```$/i', '', $clean_json);
        $article_data = json_decode($clean_json, true);

        if (!$article_data || empty($article_data['content'])) {
            return [
                'success' => false,
                'code'    => 'ai_parse_failed',
                'message' => 'Gagal memparsing JSON dari AI.',
                'raw'     => substr($content_str, 0, 300)
            ];
        }

        $final_title = !empty($article_data['title']) ? trim($article_data['title']) : $title;
        $final_content = trim($article_data['content']);
        $final_short = !empty($article_data['short_description']) ? mb_substr(trim(strip_tags($article_data['short_description'])), 0, 95) : mb_substr(strip_tags($final_content), 0, 90) . '...';
        $final_meta_desc = !empty($article_data['meta_description']) ? trim(strip_tags($article_data['meta_description'])) : $final_short;
        $final_keywords = !empty($article_data['meta_keywords']) ? trim(strip_tags($article_data['meta_keywords'])) : 'digital marketing, ads';

        // 6. Download Cover Image
        $img_keyword = !empty($article_data['image_keyword']) ? urlencode($article_data['image_keyword']) : 'digital-marketing';
        $cover_url = "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&h=630&q=80";
        $custom_img_url = "https://source.unsplash.com/1200x630/?" . $img_keyword;

        $image_filename = $this->_download_image($custom_img_url, $final_title);
        if (!$image_filename) {
            $image_filename = $this->_download_image($cover_url, $final_title);
        }

        // 7. Simpan artikel ke tabel `alimurtado.blog`
        $insert_data = [
            'title'             => $final_title,
            'description'       => $final_content,
            'short_description' => $final_short,
            'image'             => $image_filename ?: '',
            'blog_category_id'  => $category_id,
            'author'            => 'Moh. Ali Murtado',
            'datetime'          => date('Y-m-d H:i:s'),
            'visits'            => 0,
            'display'           => '1',
            'meta_keywords'     => $final_keywords,
            'meta_description'  => $final_meta_desc
        ];

        $this->CI->db->insert('blog', $insert_data);
        $new_blog_id = $this->CI->db->insert_id();

        $slug = function_exists('sanitize') ? sanitize($final_title) : url_title($final_title, '-', TRUE);
        $post_url = site_url('post/' . $new_blog_id . '-' . $slug);

        // 8. Update baris di `konten_publish`: terpublish = 1, nama_file, tgl_publish, link
        $this->CI->db->where('id', $queue_id)->update('konten_publish', [
            'terpublish'        => 1,
            'nama_file'         => $image_filename ?: 'default.jpg',
            'tgl_publish'       => date('Y-m-d'),
            'link'              => $post_url,
            'short_description' => $final_short,
            'meta_description'  => $final_meta_desc,
            'tag'               => $final_keywords
        ]);

        // 9. Update riwayat cron di `settings`
        $now_str = date('Y-m-d H:i:s');
        $this->CI->db->where('key', 'ai_blog_last_run')->update('settings', ['value' => $now_str]);
        $this->CI->db->where('key', 'ai_blog_last_title')->update('settings', ['value' => $final_title]);

        // Sisa antrean
        $remaining_count = $this->CI->db->where('nama_file IS NULL', null, false)->where('terpublish', 0)->count_all_results('konten_publish');

        return [
            'success' => true,
            'message' => 'Antrean #' . $queue_id . ' berhasil dipublish!',
            'data'    => [
                'queue_id'        => $queue_id,
                'blog_id'         => $new_blog_id,
                'title'           => $final_title,
                'post_url'        => $post_url,
                'image'           => $image_filename,
                'last_run'        => $now_str,
                'remaining_queue' => $remaining_count
            ]
        ];
    }

    private function _download_image($url, $title) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; AlimurtadoBot/1.0)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $img_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($http_code != 200 || empty($img_data) || strlen($img_data) < 1000) {
            return false;
        }

        $ext = '.jpg';
        if (strpos($content_type, 'png') !== false) $ext = '.png';
        elseif (strpos($content_type, 'webp') !== false) $ext = '.webp';

        $slug = function_exists('sanitize') ? sanitize($title) : 'blog-cover';
        $slug = substr($slug, 0, 35);
        $filename = $slug . '-' . time() . $ext;

        $dir = FCPATH . 'cdn/blog/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (@file_put_contents($dir . $filename, $img_data)) {
            return $filename;
        }
        return false;
    }
}
