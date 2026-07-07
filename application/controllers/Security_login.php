<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Security_login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Security_login_model', 'security_login_model');
    }

    /**
     * User clicks "Yes, this was me" in the email.
     * We mark the alert as confirmed, trust the device, and show success.
     */
    public function confirm_yes($token = '')
    {
        if (empty($token) || strlen($token) !== 64) {
            $this->_show_expired_page();
            return;
        }

        $alert = $this->security_login_model->resolve_token($token);
        if (!$alert) {
            $this->_show_expired_page();
            return;
        }

        // Fetch the corresponding login history to get device context
        $history = $this->db->get_where('user_login_history', ['id' => $alert['login_history_id']])->row_array();
        
        if ($history) {
            // Re-generate fingerprint from history
            $this->load->helper('security_login');
            $fingerprint = sl_get_device_fingerprint(
                $history['browser_name'],
                $history['browser_version'],
                $history['os_name'],
                $history['device_type']
            );

            // Trust the device
            $this->security_login_model->trust_device(
                $alert['user_id'],
                $alert['role'],
                $fingerprint,
                [
                    'ip_address'   => $history['ip_address'],
                    'browser_name' => $history['browser_name'],
                    'os_name'      => $history['os_name'],
                    'city'         => $history['city'],
                    'country'      => $history['country']
                ]
            );
            
            // Mark the history record as NOT suspicious since user confirmed
            $this->db->where('id', $history['id'])->update('user_login_history', ['is_suspicious' => 0]);
        }

        // Mark response
        $this->security_login_model->update_alert_response($token, 'confirmed');

        $page_data['page_name'] = 'security_confirm_yes';
        $page_data['page_title'] = 'Login Confirmed';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    /**
     * User clicks "No, secure my account" in the email.
     * We mark the alert as 'not_me', revoke all trusted devices, and advise password change.
     */
    public function confirm_no($token = '')
    {
        if (empty($token) || strlen($token) !== 64) {
            $this->_show_expired_page();
            return;
        }

        $alert = $this->security_login_model->resolve_token($token);
        if (!$alert) {
            $this->_show_expired_page();
            return;
        }

        // Mark response
        $this->security_login_model->update_alert_response($token, 'not_me');

        // Revoke all trusted devices for this user so they get challenged again
        $this->security_login_model->revoke_all_user_devices($alert['user_id']);

        // Optional: Notify Admin internally
        $this->load->model('Email_model', 'email_model');
        // If there's an internal notification system, we could trigger it here.
        // For now, we rely on the Admin Dashboard view of these alerts.

        // Also destroy their session if they are logged in right now, to force re-login
        if ($this->session->userdata('user_id') == $alert['user_id']) {
            $this->session->sess_destroy();
        }

        $page_data['page_name'] = 'security_confirm_no';
        $page_data['page_title'] = 'Account Security Alert';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    private function _show_expired_page()
    {
        $page_data['page_name'] = 'security_token_expired';
        $page_data['page_title'] = 'Link Expired';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }
}
