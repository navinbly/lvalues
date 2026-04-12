<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sign_up extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set(get_settings('timezone'));
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Tutor_master_model', 'tutor_master_model');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        $this->user_model->check_session_data();
    }

    public function index()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name']  = 'sign_up';
        $page_data['page_title'] = site_phrase('sign_up');
        $page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function verification_code()
    {
        if (!$this->session->userdata('register_email')) {
            redirect(site_url('sign_up'), 'refresh');
        }
        $page_data['page_name'] = 'verification_code';
        $page_data['page_title'] = site_phrase('verification_code');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }
}
