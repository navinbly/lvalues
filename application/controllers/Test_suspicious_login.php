<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Test_suspicious_login extends CI_Controller
{
    public function __construct()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test_suspicious_login/run';
        parent::__construct();
        $this->load->database();
        $this->load->model('User_model', 'user_model');
        // Spoof a user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36';
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8'; // Google DNS, should resolve to US
    }

    public function run()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        echo "Running Suspicious Login Test...\n";
        
        // Find a test user (Student/Tutor)
        $user = $this->db->where('role_id !=', 1)->get('users')->row_array();
        if (!$user) {
            echo "No test user found.\n";
            return;
        }

        echo "Testing with User ID: " . $user['id'] . " (" . $user['email'] . ")\n";

        // Call the login tracker manually to trigger the pipeline
        $this->user_model->new_device_login_tracker($user['id'], true);

        echo "Login tracker executed.\n";
        
        // Let's check the database logs
        echo "\n--- Login History ---\n";
        $history = $this->db->order_by('id', 'DESC')->limit(1)->get('user_login_history')->row_array();
        print_r($history);

        echo "\n--- Security Alerts ---\n";
        $alerts = $this->db->order_by('id', 'DESC')->limit(1)->get('security_alert_logs')->row_array();
        print_r($alerts);
    }
}
