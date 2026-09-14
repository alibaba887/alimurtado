<?php

class Dashboard extends CIF_Controller {

    public $layout = 'full';
    public $module = 'dashboard';
    public $model = 'users';

    public function index() {
        $this->permission();
        $data['services'] = $this->db->get('services')->num_rows();
        $data['projects'] = $this->db->get('projects')->num_rows();
        $data['blog'] = $this->db->get('blog')->num_rows();
        $data['testimonials'] = $this->db->get('testimonials')->num_rows();
        $data['clients'] = $this->db->get('clients')->num_rows();
        $data['messages'] = $this->db->get('messages')->num_rows();
        $data['skills'] = $this->db->get('skills')->num_rows();
        $data['visitors'] = config('visitors');

        // Auto-Publish Queue & Cron Data
        $data['queue_count'] = $this->db->where('nama_file IS NULL', null, false)->where('terpublish', 0)->count_all_results('konten_publish');

        $cron_setting = $this->db->where('key', 'ai_blog_cron_status')->get('settings')->row();
        $data['cron_status'] = $cron_setting ? $cron_setting->value : '0';

        $last_run = $this->db->where('key', 'ai_blog_last_run')->get('settings')->row();
        $data['cron_last_run'] = $last_run ? $last_run->value : '-';

        $last_title = $this->db->where('key', 'ai_blog_last_title')->get('settings')->row();
        $data['cron_last_title'] = $last_title ? $last_title->value : '-';

        $this->load->view($this->module, $data);
    }

}
