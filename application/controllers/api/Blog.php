<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Blog extends CI_Controller {

    public $layout = 'none';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'form'));
    }

    private function _authenticate() {
        $configured_key = config_item('blog_api_key');
        if (empty($configured_key)) {
            $this->_json_response(['success' => false, 'message' => 'API Key not configured on server'], 500);
        }

        $incoming_key = $this->input->get_request_header('X-API-KEY', TRUE);
        if (!$incoming_key && isset($_SERVER['HTTP_X_API_KEY'])) {
            $incoming_key = $_SERVER['HTTP_X_API_KEY'];
        }
        if (!$incoming_key) {
            $auth_header = $this->input->get_request_header('Authorization', TRUE);
            if (!$auth_header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
            }
            if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $incoming_key = trim($matches[1]);
            }
        }
        if (!$incoming_key) {
            $incoming_key = $this->input->get_post('api_key', TRUE);
        }

        if (!$incoming_key || !hash_equals($configured_key, $incoming_key)) {
            $this->_json_response(['success' => false, 'message' => 'Unauthorized: Invalid or missing API Key'], 401);
        }
    }

    private function _json_response($data, $status = 200) {
        $this->output
             ->set_status_header($status)
             ->set_content_type('application/json', 'utf-8')
             ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        // Output and exit directly
        echo $this->output->get_output();
        exit;
    }

    public function ping() {
        $this->_json_response([
            'success' => true,
            'status' => 'healthy',
            'message' => 'Blog Automation API is active',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function categories() {
        $this->_authenticate();
        $categories = $this->db->select('blog_category_id, title')->order_by('blog_category_id', 'ASC')->get('blog_categories')->result_array();
        $this->_json_response([
            'success' => true,
            'total' => count($categories),
            'categories' => $categories
        ]);
    }

    public function publish() {
        $this->_authenticate();

        // Support both JSON body and standard Form POST
        $raw_input = file_get_contents('php://input');
        $json_data = json_decode($raw_input, true);
        $payload = is_array($json_data) ? $json_data : $_POST;

        $title = isset($payload['title']) ? trim($payload['title']) : '';
        $description = isset($payload['content']) ? trim($payload['content']) : (isset($payload['description']) ? trim($payload['description']) : '');

        if (empty($title) || empty($description)) {
            $this->_json_response([
                'success' => false,
                'message' => 'Validation error: "title" and "content" (or "description") are required.'
            ], 422);
        }

        $category_id = isset($payload['category_id']) ? (int)$payload['category_id'] : (isset($payload['blog_category_id']) ? (int)$payload['blog_category_id'] : 19);
        $author = !empty($payload['author']) ? trim($payload['author']) : 'Moh. Ali Murtado';
        $display = isset($payload['display']) ? (string)$payload['display'] : '1';

        // Short description
        if (!empty($payload['short_description'])) {
            $short_description = mb_substr(trim(strip_tags($payload['short_description'])), 0, 95);
        } else {
            $short_description = mb_substr(trim(strip_tags($description)), 0, 90) . '...';
        }

        // Meta SEO
        $meta_description = !empty($payload['meta_description']) ? trim(strip_tags($payload['meta_description'])) : $short_description;
        $meta_keywords = !empty($payload['meta_keywords']) ? trim(strip_tags($payload['meta_keywords'])) : str_replace(' ', ', ', $title);

        // Handle Image
        $image_filename = '';
        if (!empty($payload['image_url'])) {
            $downloaded = $this->_download_image($payload['image_url'], $title);
            if ($downloaded) {
                $image_filename = $downloaded;
            }
        } elseif (!empty($payload['image_filename'])) {
            $image_filename = basename(trim($payload['image_filename']));
        }

        $datetime = !empty($payload['datetime']) ? date('Y-m-d H:i:s', strtotime($payload['datetime'])) : date('Y-m-d H:i:s');

        $data = [
            'title'             => $title,
            'description'       => $description,
            'short_description' => $short_description,
            'image'             => $image_filename,
            'blog_category_id'  => $category_id,
            'author'            => $author,
            'datetime'          => $datetime,
            'visits'            => 0,
            'display'           => in_array($display, ['0', '1']) ? $display : '1',
            'meta_keywords'     => $meta_keywords,
            'meta_description'  => $meta_description
        ];

        $this->db->insert('blog', $data);
        $blog_id = $this->db->insert_id();

        if (!$blog_id) {
            $this->_json_response([
                'success' => false,
                'message' => 'Failed to insert blog post into database.'
            ], 500);
        }

        $slug = function_exists('sanitize') ? sanitize($title) : url_title($title, '-', TRUE);
        $post_url = site_url('post/' . $blog_id . '-' . $slug);

        $this->_json_response([
            'success'   => true,
            'message'   => 'Article successfully published!',
            'data'      => [
                'blog_id'           => $blog_id,
                'title'             => $title,
                'post_url'          => $post_url,
                'category_id'       => $category_id,
                'image'             => $image_filename,
                'image_url'         => $image_filename ? base_url('cdn/blog/' . $image_filename) : null,
                'display'           => $display,
                'datetime'          => $datetime
            ]
        ], 201);
    }

    private function _download_image($url, $title_for_slug = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BlogAutomationBot/1.0)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($http_code != 200 || empty($data)) {
            return false;
        }

        $ext = '.jpg';
        if (strpos($content_type, 'png') !== false) $ext = '.png';
        elseif (strpos($content_type, 'webp') !== false) $ext = '.webp';
        elseif (strpos($content_type, 'gif') !== false) $ext = '.gif';
        elseif (strpos($content_type, 'jpeg') !== false) $ext = '.jpeg';

        $base_slug = function_exists('sanitize') ? sanitize($title_for_slug) : 'blog-cover';
        $base_slug = substr($base_slug, 0, 40);
        $filename = $base_slug . '-' . time() . $ext;

        $upload_dir = FCPATH . 'cdn/blog/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $filepath = $upload_dir . $filename;
        if (file_put_contents($filepath, $data)) {
            return $filename;
        }
        return false;
    }
}
