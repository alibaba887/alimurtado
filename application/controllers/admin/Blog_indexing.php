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
        $filter_status = $this->input->get('status') ?: 'all';
        $search_query  = trim($this->input->get('q') ?: '');

        // 1. Hitung Statistik Ringkasan
        $data['total_posts'] = $this->db->where('display', '1')->count_all_results('blog');
        $data['indexed_count'] = $this->db->where('display', '1')
                                          ->group_start()
                                              ->where('gsc_status', 1)
                                              ->or_where('indexnow_status', 1)
                                          ->group_end()
                                          ->count_all_results('blog');
        $data['pending_count'] = $this->db->where('display', '1')
                                          ->where('gsc_status', 0)
                                          ->where('indexnow_status', 0)
                                          ->count_all_results('blog');
        $data['indexnow_success'] = $this->db->where('display', '1')->where('indexnow_status', 1)->count_all_results('blog');
        $data['gsc_success']      = $this->db->where('display', '1')->where('gsc_status', 1)->count_all_results('blog');
        $data['google_api_success'] = $this->db->where('display', '1')->where('google_indexing_status', 1)->count_all_results('blog');

        $last_run_row = $this->db->select('last_indexed_at')->where('last_indexed_at IS NOT NULL', null, false)->order_by('last_indexed_at', 'DESC')->limit(1)->get('blog')->row();
        $data['last_indexed_at'] = $last_run_row ? $last_run_row->last_indexed_at : '-';

        // 2. Query Utama dengan Filter
        $this->db->where('display', '1');
        if ($filter_status === 'pending') {
            $this->db->where('gsc_status', 0)->where('indexnow_status', 0);
        } elseif ($filter_status === 'indexed') {
            $this->db->group_start()->where('gsc_status', 1)->or_where('indexnow_status', 1)->group_end();
        }

        if (!empty($search_query)) {
            $this->db->like('title', $search_query);
        }

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

        $data['filter_status'] = $filter_status;
        $data['search_query']  = $search_query;
        $data['total_filtered']= $total_filtered;

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
     * AJAX: Dapatkan jumlah sisa artikel yang belum di-index
     */
    public function ajax_get_pending_count() {
        $pending_count = $this->db->where('display', '1')
                                  ->where('gsc_status', 0)
                                  ->where('indexnow_status', 0)
                                  ->count_all_results('blog');

        $this->_json_output([
            'success'       => true,
            'pending_count' => $pending_count
        ]);
    }

    /**
     * AJAX: Batch indexing otomatis (proses N artikel per panggilan)
     */
    public function ajax_index_batch() {
        $limit = (int)($this->input->post('limit') ?: 10);
        if ($limit > 50) $limit = 50;

        // Ambil artikel yang belum di-index
        $pending_blogs = $this->db->where('display', '1')
                                  ->where('gsc_status', 0)
                                  ->where('indexnow_status', 0)
                                  ->order_by('blog_id', 'DESC')
                                  ->limit($limit)
                                  ->get('blog')
                                  ->result();

        if (empty($pending_blogs)) {
            $this->_json_output([
                'success'         => true,
                'message'         => 'Semua artikel sudah ter-index! Tidak ada antrean pending.',
                'processed'       => 0,
                'remaining_count' => 0
            ]);
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

        $remaining_count = $this->db->where('display', '1')
                                    ->where('gsc_status', 0)
                                    ->where('indexnow_status', 0)
                                    ->count_all_results('blog');

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
                                  ->where('gsc_status', 0)
                                  ->where('indexnow_status', 0)
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

    private function _json_output($data) {
        $this->output
             ->set_content_type('application/json', 'utf-8')
             ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo $this->output->get_output();
        exit;
    }
}
