<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Blog extends CIF_Controller {

    public $layout = 'full';
    public $module = 'blog';
    public $model = 'blog_model';

    public function __construct() {
        parent::__construct();
        $this->load->model($this->model);
        $this->_primary_key = $this->{$this->model}->_primary_keys[0];
        $this->permission();
    }

    public function index() {
        //Pagination
        $this->load->library('pagination');
        config('pagination_limit', 10);
        $config['total_rows'] = $this->{$this->model}->get(TRUE);
        $config['suffix'] = '?' . http_build_query($_GET);
        $config['base_url'] = site_url('admin/blog/index');
        $config['per_page'] = config('pagination_limit');
        $this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();

        if ($this->uri->segment(4))
            $this->{$this->model}->offset = $this->uri->segment(4);

        $data['total'] = $config['total_rows'];
        $this->{$this->model}->limit = config('pagination_limit');
        $this->db->order_by('blog_id', 'DESC');
        $data['items'] = $this->{$this->model}->get();
        $data['categories'] = $this->db->order_by('blog_category_id', 'ASC')->get('blog_categories')->result();

        // Auto-Publish Queue & Cron Data
        $data['queue_count'] = $this->db->where('nama_file IS NULL', null, false)->where('terpublish', 0)->count_all_results('konten_publish');

        $cron_setting = $this->db->where('key', 'ai_blog_cron_status')->get('settings')->row();
        $data['cron_status'] = $cron_setting ? $cron_setting->value : '0';

        $last_run = $this->db->where('key', 'ai_blog_last_run')->get('settings')->row();
        $data['cron_last_run'] = $last_run ? $last_run->value : '-';

        $last_title = $this->db->where('key', 'ai_blog_last_title')->get('settings')->row();
        $data['cron_last_title'] = $last_title ? $last_title->value : '-';

        $this->load->view($this->module . '/index', $data);
    }

    public function manage($id = FALSE) {
        $data = array();

        if ($id) {
            $this->{$this->model}->{$this->_primary_key} = $id;
            $data['item'] = $this->{$this->model}->get();
            if (!$data['item'])
                show_404();
        } else {
            $data['item'] = new Std();
            $data['item']->display = '1';
            $this->{$this->model}->datetime = date('Y-m-d H:i:s');
        }
        $this->load->library("form_validation");
        $this->form_validation->set_rules('blog_category_id', 'lang:global_category', 'trim|required');
        $this->form_validation->set_rules('title', 'lang:global_title', 'trim|required');
        $this->form_validation->set_rules('description', 'lang:global_description', 'trim|required');
        $this->form_validation->set_rules('short_description', 'lang:global_short_description', 'trim|required|max_length[255]');
        $this->form_validation->set_rules("image", 'lang:global_featured_image', "trim|callback_file[$id]");
        $this->form_validation->set_rules('meta_keywords', 'lang:global_tags', 'trim|required');
        $this->form_validation->set_rules('meta_description', 'lang:settings_meta_description', 'trim|required');
        $this->form_validation->set_rules('author', 'lang:global_author', 'trim|required');
        $this->form_validation->set_rules('display', 'lang:display_article', 'trim|required');


        if ($this->form_validation->run() == FALSE)
            $this->load->view($this->module . '/manage', $data);

        else {
            $this->{$this->model}->blog_category_id = $this->input->post('blog_category_id');
            $this->{$this->model}->title = $this->input->post('title');
            $this->{$this->model}->description = $this->input->post('description', FALSE);
            $this->{$this->model}->short_description = $this->input->post('short_description');
            $this->{$this->model}->meta_keywords = $this->input->post('meta_keywords');
            $this->{$this->model}->meta_description = $this->input->post('meta_description');
            $this->{$this->model}->author = $this->input->post('author');
            $this->{$this->model}->display = $this->input->post('display');
            $this->{$this->model}->save();
            redirect('admin/' . $this->module);
        }
    }

    public function delete($id = false) {
        if (!$id)
            show_404();
        $this->{$this->model}->{$this->_primary_key} = $id;
        $data['item'] = $this->{$this->model}->get();
        if (!$data['item'])
            show_404();
        $this->{$this->model}->delete();
        redirect('admin/' . $this->module);
    }

    public function file($var, $id) {
        $config['upload_path'] = './cdn/blog/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $this->load->library('upload', $config);
        if ($this->upload->do_upload('image')) {
            $data = $this->upload->data();
            if ($data['file_name'])
                $this->{$this->model}->image = $data['file_name'];
        }
        return true;
    }

    public function generate_ai() {
        $this->permission();
        $this->layout = 'none';

        $custom_topic = trim($this->input->post('topic'));
        $target_category_id = (int)$this->input->post('category_id');
        $display = '1';

        // 1. Ambil artikel acuan (post #538) untuk gaya penulisan & format
        $sample_format_guide = "Gaya penulisan harus mengikuti gaya artikel acuan (post #538) milik Moh. Ali Murtado:\n";
        $sample_format_guide .= "- Judul menarik, spesifik, dan SEO-friendly.\n";
        $sample_format_guide .= "- Intro pembuka yang menyoroti dilema/masalah nyata pengiklan.\n";
        $sample_format_guide .= "- Sub-heading (h2 dan h3) yang mendalam dan mudah dipahami.\n";
        $sample_format_guide .= "- WAJIB menyertakan minimal 1 TABEL PERBANDINGAN (format HTML <table><tr><th>...</th></tr><tr><td>...</td></tr></table>).\n";
        $sample_format_guide .= "- Menggunakan poin-poin terstruktur (<ol>, <ul>, <li>).\n";
        $sample_format_guide .= "- Menggunakan terminologi praktis (ROAS, CPA, CTR, Creative Fatigue, Hook, Conversion Rate, Testing, Scaling).\n";
        $sample_format_guide .= "- Paragraf penutup dengan kesimpulan yang tajam dan ajakan santai berdiskusi.\n";

        // 2. Ambil 15 judul artikel terakhir untuk menghindari duplikasi
        $recent_posts = $this->db->select('title')->order_by('blog_id', 'DESC')->limit(15)->get('blog')->result();
        $existing_titles = [];
        foreach ($recent_posts as $p) {
            $existing_titles[] = $p->title;
        }

        // 3. Ambil daftar kategori yang ada
        $all_categories = $this->db->select('blog_category_id, title')->get('blog_categories')->result();
        $cat_list_str = "";
        foreach ($all_categories as $c) {
            $cat_list_str .= "- ID " . $c->blog_category_id . ": " . $c->title . "\n";
        }

        // 4. Susun prompt untuk Gemini Proxy
        $prompt = "Anda adalah Moh. Ali Murtado, seorang praktisi Digital Advertising profesional di Indonesia, Co-Founder Impacta.\n";
        $prompt .= $sample_format_guide . "\n";
        $prompt .= "Daftar kategori yang tersedia di website Anda:\n" . $cat_list_str . "\n";
        $prompt .= "Artikel-artikel yang SUDAH PERNAH ditulis sebelumnya (JANGAN MEMBUAT TOPIK YANG SAMA):\n- " . implode("\n- ", $existing_titles) . "\n\n";

        if (!empty($custom_topic)) {
            $prompt .= "TOPIK YANG DIMINTA PENGGUNA: \"" . $custom_topic . "\"\n";
            if ($target_category_id > 0) {
                $prompt .= "PILIH KATEGORI DENGAN ID: " . $target_category_id . "\n";
            } else {
                $prompt .= "PILIH KATEGORI PALING COCOK DARI DAFTAR KATEGORI DI ATAS.\n";
            }
        } else {
            if ($target_category_id > 0) {
                $prompt .= "Buatkan 1 artikel tren terbaru yang belum pernah dibahas untuk KATEGORI DENGAN ID: " . $target_category_id . ".\n";
            } else {
                $prompt .= "Buatkan 1 artikel tren periklanan digital terbaru yang berbobot dan belum pernah dibahas dari salah satu kategori di atas.\n";
            }
        }

        $prompt .= "\nOUTPUT WAJIB MURNI DALAM FORMAT JSON (tanpa kutip markdown ```json) dengan skema:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Judul artikel lengkap (max 90 karakter)\",\n";
        $prompt .= "  \"category_id\": 19,\n";
        $prompt .= "  \"short_description\": \"Ringkasan sangat singkat artikel (MAKSIMAL 90 karakter)\",\n";
        $prompt .= "  \"meta_description\": \"Deskripsi SEO untuk pencarian Google (140-160 karakter)\",\n";
        $prompt .= "  \"meta_keywords\": \"5-8 kata kunci relevan dipisahkan koma\",\n";
        $prompt .= "  \"image_keyword\": \"digital advertising marketing\",\n";
        $prompt .= "  \"content\": \"Konten lengkap dalam format HTML (gunakan <h2>, <h3>, <p>, <ul>, <li>, <table>, <tr>, <th>, <td>, <strong>). Panjang minimal 600-900 kata.\"\n";
        $prompt .= "}";

        // 5. Panggil OmniRoute AI Engine (Model: agy--cli)
        $ch = curl_init("http://127.0.0.1:20130/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer sk-895ace0e48ed3da9-71c2c9-af525798",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "agy--cli",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "stream" => false
        ]));
        $raw_response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!$raw_response) {
            $this->_json_output(['success' => false, 'message' => 'Gagal menghubungi OmniRoute AI: ' . $curl_error]);
        }

        $api_json = json_decode($raw_response, true);
        $content_str = $api_json['choices'][0]['message']['content'] ?? '';
        if (empty($content_str)) {
            $this->_json_output(['success' => false, 'message' => 'AI tidak menghasilkan konten. Silakan coba lagi.']);
        }

        // Bersihkan tag <think>...</think> jika model reasoning digunakan
        $content_str = preg_replace('/<think>.*?<\/think>/is', '', $content_str);

        // Ekstrak format JSON
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/is', $content_str, $m)) {
            $clean_json = $m[1];
        } elseif (preg_match('/\{[\s\S]*\}/', $content_str, $m)) {
            $clean_json = $m[0];
        } else {
            $clean_json = trim($content_str);
        }

        $article_data = json_decode($clean_json, true);

        if (!$article_data || empty($article_data['title']) || empty($article_data['content'])) {
            $this->_json_output(['success' => false, 'message' => 'Gagal membaca format JSON dari AI. Silakan coba lagi.']);
        }

        $final_title = trim($article_data['title']);
        $final_content = trim($article_data['content']);
        $final_category = !empty($article_data['category_id']) ? (int)$article_data['category_id'] : ($target_category_id > 0 ? $target_category_id : 19);
        $final_short = !empty($article_data['short_description']) ? mb_substr(trim(strip_tags($article_data['short_description'])), 0, 95) : mb_substr(strip_tags($final_content), 0, 90) . '...';
        $final_meta_desc = !empty($article_data['meta_description']) ? trim(strip_tags($article_data['meta_description'])) : $final_short;
        $final_keywords = !empty($article_data['meta_keywords']) ? trim(strip_tags($article_data['meta_keywords'])) : 'digital marketing, ads';

        // 6. Download Cover Image (Rotasi unik dari pool 38+ gambar)
        $this->load->library('ai_blog_queue');
        $image_filename = $this->ai_blog_queue->download_unique_cover_image($final_title);

        // 7. Simpan ke database
        $insert_data = [
            'title'             => $final_title,
            'description'       => $final_content,
            'short_description' => $final_short,
            'image'             => $image_filename ?: '',
            'blog_category_id'  => $final_category,
            'author'            => 'Moh. Ali Murtado',
            'datetime'          => date('Y-m-d H:i:s'),
            'visits'            => 0,
            'display'           => $display,
            'meta_keywords'     => $final_keywords,
            'meta_description'  => $final_meta_desc
        ];

        $this->db->insert('blog', $insert_data);
        $blog_id = $this->db->insert_id();

        $slug = function_exists('sanitize') ? sanitize($final_title) : url_title($final_title, '-', TRUE);
        $post_url = site_url('post/' . $blog_id . '-' . $slug);
        $edit_url = site_url('admin/blog/manage/' . $blog_id);

        $this->_json_output([
            'success' => true,
            'message' => 'Artikel berhasil dibuat ' . ($display === '1' ? 'dan langsung diterbitkan!' : 'dan disimpan sebagai Draft.'),
            'data' => [
                'blog_id'   => $blog_id,
                'title'     => $final_title,
                'post_url'  => $post_url,
                'edit_url'  => $edit_url,
                'display'   => $display
            ]
        ]);
    }

    private function _download_ai_image($url, $title) {
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

    public function toggle_cron() {
        $current = $this->db->where('key', 'ai_blog_cron_status')->get('settings')->row();
        $current_val = $current ? $current->value : '0';

        $requested_status = $this->input->post('status');
        if ($requested_status !== null && ($requested_status === '1' || $requested_status === '0')) {
            $new_status = $requested_status;
        } else {
            $new_status = ($current_val === '1') ? '0' : '1';
        }

        $this->db->where('key', 'ai_blog_cron_status')->update('settings', ['value' => $new_status]);

        $queue_count = $this->db->where('nama_file IS NULL', null, false)->where('terpublish', 0)->count_all_results('konten_publish');

        $this->_json_output([
            'success'     => true,
            'status'      => $new_status,
            'queue_count' => $queue_count,
            'message'     => ($new_status === '1') ? 'Auto-Publish Cron berhasil DIAKTIFKAN (ON).' : 'Auto-Publish Cron berhasil DINONAKTIFKAN (OFF).'
        ]);
    }

    public function process_queue_manual() {
        $this->load->library('ai_blog_queue');
        $result = $this->ai_blog_queue->process(true); // force run regardless of cron toggle
        $this->_json_output($result);
    }

    private function _json_output($data) {
        $this->output
             ->set_content_type('application/json', 'utf-8')
             ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo $this->output->get_output();
        exit;
    }
}
