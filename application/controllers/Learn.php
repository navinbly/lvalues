<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Learn extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // 🔹 REQUIRED for frontend layout
        $this->load->library('session');

        // Optional but recommended (matches Blog behavior)
        $this->load->database();

        // Optional: only if you want login-protected later
        // $this->user_model->check_session_data();
    }

    public function index($course = null, $track = null, $topic = null, $page = null) {

        $page_data['page_name']  = 'content/index';
		$page_data['included_page'] = 'content/index.php';
        $page_data['page_title'] = 'Learn';

        $page_data['course'] = $course;
        $page_data['track']  = $track;
        $page_data['topic']  = $topic;
        $page_data['page']   = $page;

        $this->load->view(
            'frontend/' . get_frontend_settings('theme') . '/index',
            $page_data
        );
    }
}
