<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    public function index()
    {
        // Safety: allow only in local dev
        if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
            show_error('Migration runner is allowed only on localhost.', 403);
        }

        $this->load->library('migration');

        if ($this->migration->latest() === FALSE) {
            show_error($this->migration->error_string());
        }

        echo "✅ Migrations executed successfully.";
    }
}
