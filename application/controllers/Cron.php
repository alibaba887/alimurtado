<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cron extends CI_Controller {

    public $layout = 'none';

    public function __construct() {
        parent::__construct();
    }

    public function process_queue() {
        // 1. Validasi akses: Hanya boleh dipanggil via CLI atau via URL dengan token rahasia
        $is_cli = is_cli();
        $token = $this->input->get('token');
        $valid_token = config_item('blog_api_key');

        if (!$is_cli && (empty($token) || !hash_equals($valid_token, $token))) {
            show_error('Akses ditolak. Token tidak valid.', 403);
            return;
        }

        $this->load->library('ai_blog_queue');
        $result = $this->ai_blog_queue->process(false);

        if ($result['success']) {
            echo "[" . date('Y-m-d H:i:s') . "] [CRON SUCCESS] " . $result['message'] . "\n";
            echo "Judul: " . $result['data']['title'] . "\n";
            echo "URL: " . $result['data']['post_url'] . "\n";
            echo "Sisa Antrean: " . $result['data']['remaining_queue'] . "\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] [CRON INFO] " . $result['message'] . "\n";
        }
    }
}
