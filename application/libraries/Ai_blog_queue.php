<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Ai_blog_queue {

    protected $CI;

    /**
     * Pool gambar terkurasi bertema digital advertising, meta ads, marketing, dan analytics.
     * Semua URL telah diverifikasi aktif dan beresolusi tinggi (1200x630).
     */
    protected $_image_pool = [
        "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1533750516457-a7f992034fec?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1432888498266-38ffec3eaf0a?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1531482615713-2afd69097998?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1553484771-371a605b060b?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1483058712412-4245e9b90334?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1534972195531-a756b1126975?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1556740758-90de374c12ad?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1551836022-4a4c7f3b49e6?auto=format&fit=crop&w=1200&h=630&q=80",
        "https://images.unsplash.com/photo-1532619675605-1ede6c2ed2b0?auto=format&fit=crop&w=1200&h=630&q=80"
    ];

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

        // 4. Siapkan prompt untuk Gemini Proxy (Dioptimasi dengan Piramida Keyword & AEO/LLM Answer Engine)
        $prompt_guidelines = "IDENTITAS & SUDUT PANDANG:\n";
        $prompt_guidelines .= "- Anda adalah Moh. Ali Murtado, Pakar Meta Ads & Praktisi Digital Advertising Indonesia, Co-Founder Impacta.\n";
        $prompt_guidelines .= "- Gunakan sudut pandang orang pertama ('saya' / 'Moh. Ali Murtado') yang berpengalaman, praktis, to-the-point, dan berorientasi hasil (ROAS).\n\n";
        
        $prompt_guidelines .= "PIRAMIDA KATA KUNCI (WAJIB DISELIPKAN SECARA NATURAL DALAM KONTEN):\n";
        $prompt_guidelines .= "- Tier 1 (Otoritas Pribadi): 'Pakar Meta Ads Indonesia', 'Praktisi Digital Advertising Indonesia', 'Konsultan Facebook Ads', 'Performance Marketer Indonesia'.\n";
        $prompt_guidelines .= "- Tier 2 (Komersial & Strategis): 'Optimasi ROAS', 'Scale Up Bisnis Terukur', 'Audit Akun Meta Ads', 'Jasa Iklan Digital Profesional'.\n";
        $prompt_guidelines .= "- Tier 3 (Problem-Solving Teknis): Metrik praktis (CPA bengkak, CAC, CTR, Conversion Rate), strategi Advantage+ Shopping Campaigns (ASC), penanganan Creative Fatigue, Hook video 3 detik pertama, testing audiens, dan CAPI (Conversions API).\n";
        $prompt_guidelines .= "- Tier 4 (Modern AI Trend): Otomasi dan AI dalam ekosistem digital advertising modern.\n\n";

        $prompt_guidelines .= "STRUKTUR ARTIKEL (FORMAT WAJIB):\n";
        $prompt_guidelines .= "1. Judul: Menarik, spesifik, memuat keyword topik utama, dan SEO-friendly.\n";
        $prompt_guidelines .= "2. Pembuka (Lead Hook): Mengangkat masalah atau friksi nyata pengiklan di Indonesia.\n";
        $prompt_guidelines .= "3. Direct Answer / Intisari Praktis (AEO Block): Tepat setelah pembuka, sertakan ringkasan takeaway langsung dalam tag HTML: <div class=\"alert alert-info\"><strong>Intisari Praktis:</strong><ul>... (3-4 poin jawaban langsung) ...</ul></div> agar sangat mudah dikutip oleh AI/LLM dan mesin pencari.\n";
        $prompt_guidelines .= "4. Sub-heading (h2 dan h3): Pembahasan taktis mendalam, langkah-langkah praktis, atau studi kasus.\n";
        $prompt_guidelines .= "5. Tabel Perbandingan Data: WAJIB menyertakan minimal 1 TABEL PERBANDINGAN (format HTML <table class=\"table table-bordered\"><tr><th>...</th></tr><tr><td>...</td></tr></table>) yang relevan dengan topik.\n";
        $prompt_guidelines .= "6. Poin-poin Terstruktur: Gunakan <ol> atau <ul> dan <strong> untuk menonjolkan istilah/action item penting.\n";
        $prompt_guidelines .= "7. Penutup & Soft CTA: Kesimpulan tajam yang merefleksikan pengalaman Moh. Ali Murtado disertai ajakan santai berdiskusi atau konsultasi strategi iklan.\n";

        $prompt = "TULIS ARTIKEL BLOG LENGKAP UNTUK TOPIK: \"" . $title . "\"\n";
        $prompt .= "Kategori Target: " . $kategori_name . " (ID: " . $category_id . ")\n\n";
        $prompt .= $prompt_guidelines . "\n";
        $prompt .= "OUTPUT WAJIB MURNI DALAM FORMAT JSON (tanpa kutip markdown ```json) dengan skema:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"" . addslashes($title) . "\",\n";
        $prompt .= "  \"category_id\": " . $category_id . ",\n";
        $prompt .= "  \"short_description\": \"Ringkasan sangat singkat artikel (MAKSIMAL 90 karakter)\",\n";
        $prompt .= "  \"meta_description\": \"Deskripsi SEO memuat keyword utama dan nama Moh. Ali Murtado (140-160 karakter)\",\n";
        $prompt .= "  \"meta_keywords\": \"5-8 kata kunci relevan kombinasi otoritas & topik (misal: Pakar Meta Ads, Praktisi Digital Advertising, Optimasi ROAS, [Topik])\",\n";
        $prompt .= "  \"image_keyword\": \"meta ads facebook marketing advertising\",\n";
        $prompt .= "  \"content\": \"Konten lengkap dalam format HTML valid (gunakan <h2>, <h3>, <p>, <div class=\\\"alert alert-info\\\">, <ul>, <ol>, <li>, <table class=\\\"table table-bordered\\\">, <tr>, <th>, <td>, <strong>). Panjang minimal 600-900 kata.\"\n";
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

        // 6. Download Cover Image (Rotasi unik: tidak boleh sama dalam rentang minimal 10 artikel)
        $custom_candidate_url = null;
        if (!empty($queue_item->konten_gambar) && filter_var($queue_item->konten_gambar, FILTER_VALIDATE_URL)) {
            $custom_candidate_url = $queue_item->konten_gambar;
        }
        $image_filename = $this->download_unique_cover_image($final_title, $custom_candidate_url);

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

    /**
     * Download gambar cover unik dengan aturan rotasi:
     * URL gambar yang sama baru boleh digunakan kembali setelah minimal jeda 10 artikel.
     *
     * @param string $title Judul artikel (untuk penamaan file)
     * @param string|null $custom_candidate_url URL kandidat khusus (misal dari queue)
     * @return string|false Nama file gambar atau false
     */
    public function download_unique_cover_image($title, $custom_candidate_url = null) {
        // 1. Ambil riwayat URL gambar yang baru-baru ini digunakan dari tabel settings
        $recent_urls = [];
        $setting_row = $this->CI->db->where('key', 'ai_blog_recent_images')->get('settings')->row();
        if ($setting_row && !empty($setting_row->value)) {
            $decoded = json_decode($setting_row->value, true);
            if (is_array($decoded)) {
                $recent_urls = $decoded;
            }
        }

        // Ambil 10 URL terakhir yang TIDAK BOLEH dipakai ulang
        $last_10 = array_slice($recent_urls, -10);

        // 2. Jika ada kandidat gambar khusus dari tabel antrean dan belum digunakan di 10 artikel terakhir
        if (!empty($custom_candidate_url) && filter_var($custom_candidate_url, FILTER_VALIDATE_URL) && !in_array($custom_candidate_url, $last_10)) {
            $filename = $this->_download_image($custom_candidate_url, $title);
            if ($filename) {
                $this->_record_used_image_url($custom_candidate_url, $recent_urls);
                return $filename;
            }
        }

        // 3. Filter pool gambar: buang semua URL yang sudah dipakai dalam 10 artikel terakhir
        $available_pool = array_values(array_diff($this->_image_pool, $last_10));
        if (empty($available_pool)) {
            $available_pool = $this->_image_pool;
        }

        // Acak urutan agar pilihan gambar dinamis dan tidak monoton
        shuffle($available_pool);

        // 4. Coba download dari pool yang tersedia
        foreach ($available_pool as $candidate_url) {
            $filename = $this->_download_image($candidate_url, $title);
            if ($filename) {
                $this->_record_used_image_url($candidate_url, $recent_urls);
                return $filename;
            }
        }

        return false;
    }

    /**
     * Catat URL gambar yang baru saja digunakan ke settings
     */
    private function _record_used_image_url($url, $recent_urls) {
        $recent_urls[] = $url;
        // Simpan 20 riwayat terakhir
        if (count($recent_urls) > 20) {
            $recent_urls = array_slice($recent_urls, -20);
        }

        $exists = $this->CI->db->where('key', 'ai_blog_recent_images')->get('settings')->row();
        if ($exists) {
            $this->CI->db->where('key', 'ai_blog_recent_images')->update('settings', ['value' => json_encode(array_values($recent_urls))]);
        } else {
            $this->CI->db->insert('settings', ['key' => 'ai_blog_recent_images', 'value' => json_encode(array_values($recent_urls))]);
        }
    }

    private function _download_image($url, $title) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
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
        $filename = $slug . '-' . time() . '-' . mt_rand(100, 999) . $ext;

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
