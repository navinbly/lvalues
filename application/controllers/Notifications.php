<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->database();
        $this->load->helper('url');
        $this->load->model('notification_model');

        if ($this->session->userdata('user_login') != true && !$this->session->userdata('user_id')) {
            redirect(site_url('login'), 'refresh');
        }
    }

    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $page_data['notifications'] = $this->notification_model->get_user_notifications($user_id, 50);
        $page_data['page_name'] = 'notifications_page';
        $page_data['page_title'] = 'Notifications';

        $this->load->view('frontend/default-new/index', $page_data);
    }

    public function read($notification_id = 0)
    {
        $notification_id = (int)$notification_id;
        $user_id = (int)$this->session->userdata('user_id');

        $notification = $this->notification_model->mark_as_read($notification_id, $user_id);

        if (empty($notification)) {
            show_404();
            return;
        }

        $target_url = trim((string)($notification['target_url'] ?? ''));

        if ($this->is_safe_local_url($target_url)) {
            redirect($target_url, 'refresh');
            return;
        }

        redirect(site_url('notifications'), 'refresh');
    }

    public function mark_all_read()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $this->notification_model->mark_all_read($user_id);

        $this->session->set_flashdata('flash_message', 'All notifications marked as read.');
        redirect(site_url('notifications'), 'refresh');
    }

    private function is_safe_local_url($url)
    {
        if ($url === '') {
            return false;
        }

        return strpos($url, site_url()) === 0
            || strpos($url, base_url()) === 0
            || preg_match('#^/[a-zA-Z0-9_./-]+$#', $url);
    }
}
