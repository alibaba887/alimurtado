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
        $is_cron_method = in_array($this->router->fetch_method(), ['run_scheduled_cron', 'auto_cron']);
        if (!$this->input->is_cli_request() && !$is_cron_method) {
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

        // 3. Konfigurasi Jadwal Otomatis (Cron Settings)
        $settings_rows = $this->db->where_in('key', [
            'schedule_indexing_enabled',
            'schedule_indexing_action',
            'schedule_indexing_batch_size',
            'schedule_indexing_interval',
            'schedule_indexing_date_range',
            'schedule_indexing_secret_token',
            'schedule_indexing_last_run',
            'schedule_indexing_last_log'
        ])->get('settings')->result();

        $schedule_settings = [
            'schedule_indexing_enabled'      => '0',
            'schedule_indexing_action'       => 'both',
            'schedule_indexing_batch_size'   => '5',
            'schedule_indexing_interval'     => '6',
            'schedule_indexing_date_range'   => 'all',
            'schedule_indexing_secret_token' => '',
            'schedule_indexing_last_run'     => '',
            'schedule_indexing_last_log'     => 'Belum pernah dijalankan'
        ];
        foreach ($settings_rows as $row) {
            $schedule_settings[$row->key] = $row->value;
        }
        $data['schedule_settings'] = $schedule_settings;

        // Hitung jumlah artikel non-PASS yang mengantre untuk penjadwalan (sesuai rentang tanggal artikel aktif)
        $queue_query = $this->db->where('display', '1')
                                ->group_start()
                                    ->where('gsc_verdict IS NULL', null, false)
                                    ->or_where('gsc_verdict !=', 'PASS')
                                ->group_end();
        if ($schedule_settings['schedule_indexing_date_range'] !== 'all' && is_numeric($schedule_settings['schedule_indexing_date_range'])) {
            $days = (int)$schedule_settings['schedule_indexing_date_range'];
            $queue_query->where('datetime >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }
        $data['schedule_queue_count'] = $queue_query->count_all_results('blog');

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
     * AJAX: Nyalakan / Matikan saklar penjadwalan otomatis
     */
    public function ajax_toggle_schedule() {
        $enabled = $this->input->post('enabled') === '1' ? '1' : '0';
        $this->db->where('key', 'schedule_indexing_enabled')->update('settings', ['value' => $enabled]);

        $this->_json_output([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled === '1' ? 'Penjadwalan otomatis berhasil DIAKTIFKAN!' : 'Penjadwalan otomatis berhasil DIMATIKAN.'
        ]);
    }

    /**
     * AJAX: Simpan konfigurasi penjadwalan (aksi, batch size, rentang interval & rentang tanggal)
     */
    public function ajax_save_schedule_settings() {
        $action = $this->input->post('action');
        if (!in_array($action, ['both', 'inspect_only', 'index_only'])) {
            $action = 'both';
        }
        $batch_size = (int)$this->input->post('batch_size');
        if ($batch_size < 1) $batch_size = 1;
        if ($batch_size > 20) $batch_size = 20;

        $interval = (int)$this->input->post('interval');
        if ($interval < 0) $interval = 0;
        if ($interval > 168) $interval = 168;

        $date_range = $this->input->post('date_range');
        if (!in_array($date_range, ['all', '7', '30', '90', '365'])) {
            $date_range = 'all';
        }

        $this->_save_setting('schedule_indexing_action', $action);
        $this->_save_setting('schedule_indexing_batch_size', (string)$batch_size);
        $this->_save_setting('schedule_indexing_interval', (string)$interval);
        $this->_save_setting('schedule_indexing_date_range', $date_range);

        // Hitung ulang antrean non-PASS berdasarkan rentang tanggal yang baru disimpan
        $queue_query = $this->db->where('display', '1')
                                ->group_start()
                                    ->where('gsc_verdict IS NULL', null, false)
                                    ->or_where('gsc_verdict !=', 'PASS')
                                ->group_end();
        if ($date_range !== 'all' && is_numeric($date_range)) {
            $days = (int)$date_range;
            $queue_query->where('datetime >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }
        $new_queue_count = $queue_query->count_all_results('blog');

        $interval_label = ($interval === 0) ? 'Setiap Cron Terpanggil' : 'Setiap ' . $interval . ' Jam';
        $daterange_label = ($date_range === 'all') ? 'Semua Artikel' : $date_range . ' Hari Terakhir';

        $this->_json_output([
            'success'         => true,
            'message'         => 'Pengaturan penjadwalan & rentang berhasil disimpan!',
            'queue_count'     => $new_queue_count,
            'interval'        => $interval,
            'interval_label'  => $interval_label,
            'date_range'      => $date_range,
            'daterange_label' => $daterange_label
        ]);
    }

    /**
     * Runner Penjadwalan Otomatis:
     * Dijalankan via CLI (cron Linux) atau HTTP Web Cron (dengan validasi secret token) atau tombol admin.
     * Syarat: Hanya artikel dengan status inspeksi GSC selain 'PASS' (termasuk belum diinspeksi).
     */
    public function run_scheduled_cron($cli_force = null) {
        $is_cli = $this->input->is_cli_request();
        $is_ajax = $this->input->is_ajax_request();
        $is_admin = $this->session->userdata('user_id') ? true : false;
        $token = trim($this->input->get_post('token') ?: '');

        // 1. Ambil seluruh konfigurasi jadwal dari settings
        $settings_rows = $this->db->where_in('key', [
            'schedule_indexing_enabled',
            'schedule_indexing_action',
            'schedule_indexing_batch_size',
            'schedule_indexing_interval',
            'schedule_indexing_date_range',
            'schedule_indexing_secret_token',
            'schedule_indexing_last_run',
            'schedule_indexing_last_log'
        ])->get('settings')->result();

        $cfg = [];
        foreach ($settings_rows as $row) {
            $cfg[$row->key] = $row->value;
        }

        $secret_token = !empty($cfg['schedule_indexing_secret_token']) ? $cfg['schedule_indexing_secret_token'] : '';
        $is_authorized = false;

        if ($is_cli) {
            $is_authorized = true;
        } elseif ($is_admin) {
            $is_authorized = true;
        } elseif (!empty($token) && hash_equals($secret_token, $token)) {
            $is_authorized = true;
        }

        if (!$is_authorized) {
            if ($is_ajax) {
                $this->_json_output(['success' => false, 'message' => 'Akses ditolak: Token tidak valid']);
            } else {
                show_error('Akses ditolak: Token keamanan cron tidak valid.', 403);
            }
        }

        $force_run = ($this->input->get_post('force') == '1' || $cli_force === '1' || $cli_force === 'force');
        $is_enabled = (isset($cfg['schedule_indexing_enabled']) && $cfg['schedule_indexing_enabled'] === '1');

        // Jika dipanggil via cron luar/CLI dan saklar mati (kecuali dipaksa via admin Run Now)
        if (!$is_enabled && !$force_run) {
            $msg = 'Penjadwalan otomatis sedang NONAKTIF (OFF) di pengaturan dashboard.';
            if ($is_ajax) {
                $this->_json_output(['success' => false, 'message' => $msg]);
            } else {
                echo $msg . "\n";
                exit;
            }
        }

        // Cek Rentang Waktu (Interval Minimal Eksekusi Cron)
        $interval_hours = isset($cfg['schedule_indexing_interval']) ? (int)$cfg['schedule_indexing_interval'] : 6;
        if ($interval_hours > 0 && !$force_run && !empty($cfg['schedule_indexing_last_run'])) {
            $last_run_timestamp = strtotime($cfg['schedule_indexing_last_run']);
            $next_allowed_time = $last_run_timestamp + ($interval_hours * 3600);

            if (time() < $next_allowed_time) {
                $remaining_seconds = $next_allowed_time - time();
                $rem_hours = floor($remaining_seconds / 3600);
                $rem_minutes = ceil(($remaining_seconds % 3600) / 60);
                $rem_text = ($rem_hours > 0 ? $rem_hours . ' jam ' : '') . $rem_minutes . ' menit';

                $msg = "Rentang waktu belum tercapai (diatur setiap {$interval_hours} jam). Terakhir dijalankan " . date('d M Y H:i:s', $last_run_timestamp) . ". Putaran berikutnya siap dalam {$rem_text}.";

                if ($is_ajax) {
                    $this->_json_output([
                        'success' => false,
                        'message' => $msg,
                        'skipped' => true
                    ]);
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] [CRON SKIP] " . $msg . "\n";
                    exit;
                }
            }
        }

        $action = !empty($cfg['schedule_indexing_action']) ? $cfg['schedule_indexing_action'] : 'both';
        $batch_size = !empty($cfg['schedule_indexing_batch_size']) ? (int)$cfg['schedule_indexing_batch_size'] : 5;
        if ($batch_size < 1) $batch_size = 1;
        if ($batch_size > 20) $batch_size = 20;

        $date_range = !empty($cfg['schedule_indexing_date_range']) ? $cfg['schedule_indexing_date_range'] : 'all';

        // 2. Query artikel yang berstatus selain PASS (gsc_verdict IS NULL OR gsc_verdict != 'PASS') sesuai rentang tanggal artikel
        $query = $this->db->where('display', '1')
                          ->group_start()
                              ->where('gsc_verdict IS NULL', null, false)
                              ->or_where('gsc_verdict !=', 'PASS')
                          ->group_end();

        if ($date_range !== 'all' && is_numeric($date_range)) {
            $days = (int)$date_range;
            $query->where('datetime >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }

        $target_blogs = $query->order_by('(gsc_verdict IS NULL) DESC, gsc_last_crawl_time ASC, blog_id DESC', '', false)
                              ->limit($batch_size)
                              ->get('blog')
                              ->result();

        if (empty($target_blogs)) {
            $range_label = ($date_range === 'all') ? 'semua artikel' : 'artikel ' . $date_range . ' hari terakhir';
            $msg = 'Semua artikel terbit (' . $range_label . ') sudah berstatus PASS di Google Search Console!';
            $this->_save_setting('schedule_indexing_last_run', date('Y-m-d H:i:s'));
            $this->_save_setting('schedule_indexing_last_log', $msg);

            if ($is_ajax) {
                $this->_json_output([
                    'success'         => true,
                    'message'         => $msg,
                    'processed_count' => 0,
                    'remaining_count' => 0
                ]);
            } else {
                echo $msg . "\n";
                exit;
            }
        }

        // 3. Proses artikel target
        $processed_details = [];
        $pass_count = 0;
        $neutral_count = 0;
        $fail_count = 0;

        foreach ($target_blogs as $blog) {
            $slug = function_exists('sanitize') ? sanitize($blog->title) : url_title($blog->title, '-', TRUE);
            $post_url = site_url('post/' . $blog->blog_id . '-' . $slug);

            $index_res = null;
            $inspect_res = null;

            // Aksi A: Submit Indexing jika mode 'both' atau 'index_only'
            if ($action === 'both' || $action === 'index_only') {
                $index_res = $this->auto_indexer->index_url($post_url, null, $blog->blog_id);
            }

            // Aksi B: Inspeksi GSC jika mode 'both' atau 'inspect_only'
            if ($action === 'both' || $action === 'inspect_only') {
                // Jeda 300ms agar API Google Search Console tidak terkena limit rate
                usleep(300000);
                $inspect_res = $this->auto_indexer->inspect_url($post_url, $blog->blog_id);
                if (!empty($inspect_res['success']) && !empty($inspect_res['data']['verdict'])) {
                    $v = $inspect_res['data']['verdict'];
                    if ($v === 'PASS') $pass_count++;
                    elseif ($v === 'NEUTRAL') $neutral_count++;
                    else $fail_count++;
                }
            }

            $processed_details[] = [
                'blog_id'     => $blog->blog_id,
                'title'       => $blog->title,
                'url'         => $post_url,
                'index_res'   => $index_res,
                'inspect_res' => $inspect_res
            ];
        }

        // Hitung sisa artikel non-PASS yang tersisa sesuai rentang tanggal artikel
        $rem_query = $this->db->where('display', '1')
                              ->group_start()
                                  ->where('gsc_verdict IS NULL', null, false)
                                  ->or_where('gsc_verdict !=', 'PASS')
                              ->group_end();
        if ($date_range !== 'all' && is_numeric($date_range)) {
            $days = (int)$date_range;
            $rem_query->where('datetime >=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }
        $remaining_count = $rem_query->count_all_results('blog');

        $now_str = date('Y-m-d H:i:s');
        $range_label = ($date_range === 'all') ? 'Semua Tanggal' : $date_range . ' Hari Terakhir';
        $log_summary = 'Berhasil memproses ' . count($processed_details) . ' artikel pada ' . date('d M Y H:i:s') . ' (Rentang: ' . $range_label . ').';
        if ($action === 'both' || $action === 'inspect_only') {
            $log_summary .= ' Hasil GSC: ' . $pass_count . ' PASS, ' . $neutral_count . ' NEUTRAL, ' . $fail_count . ' FAIL.';
        }
        $log_summary .= ' Sisa non-PASS: ' . $remaining_count . ' artikel.';

        // Simpan log ke settings
        $this->_save_setting('schedule_indexing_last_run', $now_str);
        $this->_save_setting('schedule_indexing_last_log', $log_summary);

        if ($is_ajax) {
            $this->_json_output([
                'success'         => true,
                'message'         => $log_summary,
                'processed_count' => count($processed_details),
                'remaining_count' => $remaining_count,
                'last_run'        => $now_str,
                'last_log'        => $log_summary,
                'details'         => $processed_details
            ]);
        } else {
            echo "[" . $now_str . "] " . $log_summary . "\n";
            exit;
        }
    }

    /**
     * Helper simpan / perbarui setting
     */
    private function _save_setting($key, $value) {
        $exists = $this->db->where('key', $key)->count_all_results('settings');
        if ($exists > 0) {
            $this->db->where('key', $key)->update('settings', ['value' => $value]);
        } else {
            $this->db->insert('settings', ['key' => $key, 'value' => $value]);
        }
    }

    /**
     * CLI / Cron auto-index untuk memproses background queue jika dipanggil terjadwal
     */
    public function auto_cron() {
        return $this->run_scheduled_cron();
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
