<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Blog Indexing Controller
 * 
 * Mengelola status indexing artikel blog ke Search Engine:
 * - IndexNow (Bing, Yandex, Seznam, Naver)
 * - Google Search Console Sitemaps API
 * - Google Web Search Indexing API
 *
 * @package Application\Controllers\Admin
 * @author Moh. Ali Murtado
 */
class Blog_indexing extends CIF_Controller {

    public $layout = 'full';
    public $module = 'blog_indexing';

    public function __construct() {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            $this->permission();
        }
        $this->load->library('auto_indexer');
    }

    public function index() {
        $filter_status      = $this->input->get('status') ?: 'all';
        $filter_indexnow    = $this->input->get('indexnow');
        $filter_gsc         = $this->input->get('gsc');
        $filter_google_api  = $this->input->get('google_api');
        $filter_gsc_verdict = $this->input->get('gsc_verdict');
        $search_query       = trim($this->input->get('q') ?: '');

        // 1. Hitung Statistik Ringkasan Global
        $data['total_posts'] = $this->db->where('display', '1')->count_all_results('blog');
        $data['indexed_count'] = $this->db->where('display', '1')
                                          ->group_start()
                                              ->where('gsc_status', 1)
                                              ->or_where('indexnow_status', 1)
                                              ->or_where('google_indexing_status', 1)
                                          ->group_end()
                                          ->count_all_results('blog');
        $data['pending_count'] = $this->db->where('display', '1')
                                          ->group_start()
                                              ->where('gsc_status', 0)
                                              ->or_where('indexnow_status', 0)
                                              ->or_where('google_indexing_status !=', 1)
                                          ->group_end()
                                          ->count_all_results('blog');

        // Statistik rincian per-engine untuk dropdown options
        $stats_row = $this->db->query("
            SELECT 
              SUM(IF(indexnow_status = 1, 1, 0)) as in_success,
              SUM(IF(indexnow_status = 0, 1, 0)) as in_pending,
              SUM(IF(indexnow_status = 2, 1, 0)) as in_failed,
              SUM(IF(gsc_status = 1, 1, 0)) as gsc_success,
              SUM(IF(gsc_status = 0, 1, 0)) as gsc_pending,
              SUM(IF(gsc_status = 2, 1, 0)) as gsc_failed,
              SUM(IF(google_indexing_status = 1, 1, 0)) as gapi_success,
              SUM(IF(google_indexing_status = 0, 1, 0)) as gapi_pending,
              SUM(IF(google_indexing_status = 2, 1, 0)) as gapi_failed,
              SUM(IF(gsc_verdict = 'PASS', 1, 0)) as gsc_inspect_pass,
              SUM(IF(gsc_verdict = 'NEUTRAL', 1, 0)) as gsc_inspect_neutral,
              SUM(IF(gsc_verdict = 'FAIL', 1, 0)) as gsc_inspect_fail,
              SUM(IF(gsc_verdict IS NOT NULL AND gsc_verdict != '', 1, 0)) as gsc_inspect_done,
              SUM(IF(gsc_verdict IS NULL OR gsc_verdict = '', 1, 0)) as gsc_inspect_uninspected
            FROM blog WHERE display = '1'
        ")->row();
        $data['stats_breakdown'] = $stats_row;

        $last_run_row = $this->db->select('last_indexed_at')->where('last_indexed_at IS NOT NULL', null, false)->order_by('last_indexed_at', 'DESC')->limit(1)->get('blog')->row();
        $data['last_indexed_at'] = $last_run_row ? $last_run_row->last_indexed_at : '-';

        // 2. Query Utama dengan Filter
        $this->_apply_filters($this->db, $filter_status, $filter_indexnow, $filter_gsc, $filter_google_api, $filter_gsc_verdict, $search_query);

        // Hitung total filtered rows untuk pagination
        $total_filtered = $this->db->count_all_results('blog', FALSE);

        // Pagination CodeIgniter
        $this->load->library('pagination');
        $per_page = 20;
        $config['total_rows'] = $total_filtered;
        $config['base_url']   = site_url('admin/blog_indexing/index');
        $config['per_page']   = $per_page;
        $config['suffix']     = '?' . http_build_query($_GET);
        $this->pagination->initialize($config);
        $data['pagination']   = $this->pagination->create_links();

        $offset = (int)$this->uri->segment(4);
        $this->db->order_by('blog_id', 'DESC');
        $this->db->limit($per_page, $offset);
        $data['items'] = $this->db->get()->result();

        $data['filter_status']      = $filter_status;
        $data['filter_indexnow']    = $filter_indexnow;
        $data['filter_gsc']         = $filter_gsc;
        $data['filter_google_api']  = $filter_google_api;
        $data['filter_gsc_verdict'] = $filter_gsc_verdict;
        $data['search_query']       = $search_query;
        $data['total_filtered']     = $total_filtered;

        $data['has_active_filter'] = ($filter_status !== 'all' && !empty($filter_status)) ||
                                     ($filter_indexnow !== null && $filter_indexnow !== '' && $filter_indexnow !== 'all') ||
                                     ($filter_gsc !== null && $filter_gsc !== '' && $filter_gsc !== 'all') ||
                                     ($filter_google_api !== null && $filter_google_api !== '' && $filter_google_api !== 'all') ||
                                     ($filter_gsc_verdict !== null && $filter_gsc_verdict !== '' && $filter_gsc_verdict !== 'all') ||
                                     (!empty($search_query));

        $this->load->view($this->module . '/index', $data);
    }

    /**
     * AJAX: Index satu artikel secara manual
     */
    public function ajax_index_single() {
        $blog_id = (int)$this->input->post('blog_id');
        if (!$blog_id) {
            $this->_json_output(['success' => false, 'message' => 'ID Blog tidak valid']);
        }

        $blog = $this->db->where('blog_id', $blog_id)->get('blog')->row();
        if (!$blog) {
            $this->_json_output(['success' => false, 'message' => 'Artikel blog tidak ditemukan']);
        }

        $slug = function_exists('sanitize') ? sanitize($blog->title) : url_title($blog->title, '-', TRUE);
        $post_url = site_url('post/' . $blog->blog_id . '-' . $slug);

        $res = $this->auto_indexer->index_url($post_url, null, $blog_id);

        $updated_blog = $this->db->where('blog_id', $blog_id)->get('blog')->row();

        $this->_json_output([
            'success' => true,
            'message' => 'Indexing artikel #' . $blog_id . ' berhasil dijalankan!',
            'data'    => [
                'blog_id'                => $blog_id,
                'post_url'               => $post_url,
                'indexnow_status'        => (int)$updated_blog->indexnow_status,
                'gsc_status'             => (int)$updated_blog->gsc_status,
                'google_indexing_status' => (int)$updated_blog->google_indexing_status,
                'last_indexed_at'        => $updated_blog->last_indexed_at,
                'indexing_log'           => $updated_blog->indexing_log,
                'raw_result'             => $res
            ]
        ]);
    }

    /**
     * AJAX: Inspeksi status resmi URL di Google Search Console
     */
    public function ajax_inspect_single() {
        $blog_id = (int)$this->input->post('blog_id');
        if (!$blog_id) {
            $this->_json_output(['success' => false, 'message' => 'ID Blog tidak valid']);
        }

        $blog = $this->db->where('blog_id', $blog_id)->get('blog')->row();
        if (!$blog) {
            $this->_json_output(['success' => false, 'message' => 'Artikel blog tidak ditemukan']);
        }

        $slug = function_exists('sanitize') ? sanitize($blog->title) : url_title($blog->title, '-', TRUE);
        $post_url = site_url('post/' . $blog->blog_id . '-' . $slug);

        $res = $this->auto_indexer->inspect_url($post_url, $blog_id);

        if ($res['success']) {
            $this->_json_output([
                'success' => true,
                'message' => 'Inspeksi Google Search Console berhasil didapatkan!',
                'data'    => $res['data']
            ]);
        } else {
            $this->_json_output([
                'success' => false,
                'message' => 'Inspeksi gagal: ' . (isset($res['message']) ? $res['message'] : 'Gagal memanggil API GSC')
            ]);
        }
    }

    /**
     * AJAX: Dapatkan jumlah sisa artikel yang perlu di-index (mendukung filter spesifik)
     */
    public function ajax_get_pending_count() {
        $status      = $this->input->post('status') ?: 'all';
        $indexnow    = $this->input->post('indexnow');
        $gsc         = $this->input->post('gsc');
        $google_api  = $this->input->post('google_api');
        $gsc_verdict = $this->input->post('gsc_verdict');
        $search      = trim($this->input->post('q') ?: '');

        $has_custom = ($indexnow !== null && $indexnow !== '' && $indexnow !== 'all') ||
                      ($gsc !== null && $gsc !== '' && $gsc !== 'all') ||
                      ($google_api !== null && $google_api !== '' && $google_api !== 'all') ||
                      ($gsc_verdict !== null && $gsc_verdict !== '' && $gsc_verdict !== 'all') ||
                      (!empty($search));

        if ($has_custom) {
            $this->_apply_filters($this->db, $status, $indexnow, $gsc, $google_api, $gsc_verdict, $search);
            $pending_count = $this->db->count_all_results('blog');
        } else {
            $pending_count = $this->db->where('display', '1')
                                      ->group_start()
                                          ->where('gsc_status', 0)
                                          ->or_where('indexnow_status', 0)
                                          ->or_where('google_indexing_status !=', 1)
                                      ->group_end()
                                      ->count_all_results('blog');
        }

        $this->_json_output([
            'success'       => true,
            'pending_count' => $pending_count
        ]);
    }

    /**
     * AJAX: Batch indexing otomatis (proses N artikel per panggilan, mendukung filter)
     */
    public function ajax_index_batch() {
        $limit       = (int)($this->input->post('limit') ?: 10);
        $status      = $this->input->post('status') ?: 'all';
        $indexnow    = $this->input->post('indexnow');
        $gsc         = $this->input->post('gsc');
        $google_api  = $this->input->post('google_api');
        $gsc_verdict = $this->input->post('gsc_verdict');
        $search      = trim($this->input->post('q') ?: '');

        if ($limit > 50) $limit = 50;

        $has_custom = ($indexnow !== null && $indexnow !== '' && $indexnow !== 'all') ||
                      ($gsc !== null && $gsc !== '' && $gsc !== 'all') ||
                      ($google_api !== null && $google_api !== '' && $google_api !== 'all') ||
                      ($gsc_verdict !== null && $gsc_verdict !== '' && $gsc_verdict !== 'all') ||
                      (!empty($search));

        // Ambil artikel target
        if ($has_custom) {
            $this->_apply_filters($this->db, $status, $indexnow, $gsc, $google_api, $gsc_verdict, $search);
        } else {
            $this->db->where('display', '1')
                     ->group_start()
                         ->where('gsc_status', 0)
                         ->or_where('indexnow_status', 0)
                         ->or_where('google_indexing_status !=', 1)
                     ->group_end();
        }

        $target_blogs = $this->db->order_by('blog_id', 'DESC')
                                 ->limit($limit)
                                 ->get('blog')
                                 ->result();

        if (empty($target_blogs)) {
            $this->_json_output([
                'success'         => true,
                'message'         => 'Semua artikel target sudah ter-index! Tidak ada antrean pending.',
                'processed'       => 0,
                'remaining_count' => 0
            ]);
        }

        $items = [];
        foreach ($target_blogs as $blog) {
            $slug = function_exists('sanitize') ? sanitize($blog->title) : url_title($blog->title, '-', TRUE);
            $items[] = [
                'blog_id' => $blog->blog_id,
                'url'     => site_url('post/' . $blog->blog_id . '-' . $slug)
            ];
        }

        $res = $this->auto_indexer->index_batch($items);

        // Hitung sisa
        if ($has_custom) {
            $this->_apply_filters($this->db, $status, $indexnow, $gsc, $google_api, $gsc_verdict, $search);
            $remaining_count = $this->db->count_all_results('blog');
        } else {
            $remaining_count = $this->db->where('display', '1')
                                        ->group_start()
                                            ->where('gsc_status', 0)
                                            ->or_where('indexnow_status', 0)
                                            ->or_where('google_indexing_status !=', 1)
                                        ->group_end()
                                        ->count_all_results('blog');
        }

        $this->_json_output([
            'success'         => true,
            'message'         => 'Berhasil memproses ' . count($items) . ' artikel ke Search Engine!',
            'processed'       => count($items),
            'remaining_count' => $remaining_count,
            'batch_result'    => $res
        ]);
    }

    /**
     * CLI / Cron auto-index untuk memproses background queue jika dipanggil terjadwal
     */
    public function auto_cron() {
        $limit = 10;
        $pending_blogs = $this->db->where('display', '1')
                                  ->group_start()
                                      ->where('gsc_status', 0)
                                      ->or_where('indexnow_status', 0)
                                      ->or_where('google_indexing_status !=', 1)
                                  ->group_end()
                                  ->order_by('blog_id', 'DESC')
                                  ->limit($limit)
                                  ->get('blog')
                                  ->result();

        if (empty($pending_blogs)) {
            echo "Auto-indexer: Semua artikel sudah ter-index.\n";
            exit;
        }

        $items = [];
        foreach ($pending_blogs as $blog) {
            $slug = function_exists('sanitize') ? sanitize($blog->title) : url_title($blog->title, '-', TRUE);
            $items[] = [
                'blog_id' => $blog->blog_id,
                'url'     => site_url('post/' . $blog->blog_id . '-' . $slug)
            ];
        }

        $res = $this->auto_indexer->index_batch($items);
        echo "Auto-indexer cron: Sukses memproses " . count($items) . " artikel.\n";
        exit;
    }

    /**
     * Helper untuk menerapkan filter query
     */
    private function _apply_filters($db, $status, $indexnow, $gsc, $google_api, $gsc_verdict = null, $search = '') {
        $db->where('display', '1');

        if ($status === 'pending') {
            $db->group_start()
                   ->where('gsc_status', 0)
                   ->or_where('indexnow_status', 0)
                   ->or_where('google_indexing_status !=', 1)
               ->group_end();
        } elseif ($status === 'indexed') {
            $db->group_start()
                   ->where('gsc_status', 1)
                   ->or_where('indexnow_status', 1)
                   ->or_where('google_indexing_status', 1)
               ->group_end();
        }

        if ($indexnow !== null && $indexnow !== '' && $indexnow !== 'all') {
            $db->where('indexnow_status', (int)$indexnow);
        }
        if ($gsc !== null && $gsc !== '' && $gsc !== 'all') {
            $db->where('gsc_status', (int)$gsc);
        }
        if ($google_api !== null && $google_api !== '' && $google_api !== 'all') {
            $db->where('google_indexing_status', (int)$google_api);
        }
        if ($gsc_verdict !== null && $gsc_verdict !== '' && $gsc_verdict !== 'all') {
            if ($gsc_verdict === 'uninspected') {
                $db->group_start()
                       ->where('gsc_verdict IS NULL', null, false)
                       ->or_where('gsc_verdict', '')
                   ->group_end();
            } elseif ($gsc_verdict === 'inspected') {
                $db->where('gsc_verdict IS NOT NULL', null, false)
                   ->where('gsc_verdict !=', '');
            } else {
                $db->where('gsc_verdict', $gsc_verdict);
            }
        }

        if (!empty($search)) {
            $db->like('title', $search);
        }
    }

    private function _json_output($data) {
        $this->output
             ->set_content_type('application/json', 'utf-8')
             ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo $this->output->get_output();
        exit;
    }
}
