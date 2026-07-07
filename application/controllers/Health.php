<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Health extends CI_Controller
{
    public function live()
    {
        return $this->json(array('ok' => true, 'service' => 'lvalues', 'time' => date(DATE_ATOM)), 200);
    }

    public function ready()
    {
        $this->load->library('operations_monitor');
        $result = $this->operations_monitor->readiness();
        return $this->json($result, $result['ok'] ? 200 : 503);
    }

    public function providers()
    {
        $this->config->load('operations');
        $expected = (string)$this->config->item('operations_health_token');
        $provided = (string)$this->input->get_request_header('X-Health-Token', true);
        if ($expected !== '' && !hash_equals($expected, $provided)) return $this->json(array('ok' => false, 'message' => 'Unauthorized.'), 401);
        $this->load->library('operations_monitor');
        return $this->json(array('ok' => true, 'providers' => $this->operations_monitor->check_providers()), 200);
    }

    private function json(array $body, int $status)
    {
        return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode($body));
    }
}
