<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Learn extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
    }

    // Handles all URLs like:
    // /learn/gcp/data-engineer/bigquery/introduction
    public function index($course = null, $track = null, $topic = null, $page = null) {

        $page_data['page_name']  = 'content/index';
        $page_data['page_title'] = 'Learn';

        // Pass URL parts to view
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
