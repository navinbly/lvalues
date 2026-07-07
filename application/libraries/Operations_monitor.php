<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Operations_monitor
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('operations');
    }

    public function readiness(): array
    {
        $checks = array();
        $checks['database'] = $this->database_check();
        $checks['uploads_writable'] = is_dir(FCPATH . 'uploads') && is_writable(FCPATH . 'uploads');
        $checks['logs_writable'] = is_dir(APPPATH . 'logs') && is_writable(APPPATH . 'logs');
        $checks['audit_table'] = $this->CI->db->table_exists('immutable_audit_logs');
        $checks['idempotency_table'] = $this->CI->db->table_exists('operation_idempotency_keys');
        return array('ok' => !in_array(false, $checks, true), 'checks' => $checks, 'environment' => ENVIRONMENT, 'checked_at' => date(DATE_ATOM));
    }

    public function check_providers(): array
    {
        $providers = (array)$this->CI->config->item('operations_providers');
        $results = array();
        foreach (array('smtp', 'whatsapp', 'payment') as $key) {
            $url = (string)($providers[$key] ?? '');
            if ($url === '') {
                $result = array('provider_key' => $key, 'status' => 'not_configured', 'response_ms' => null, 'http_status' => null, 'message' => 'Health endpoint is not configured.');
            } else {
                $started = microtime(true);
                $ch = curl_init($url);
                curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false));
                curl_exec($ch);
                $error = curl_error($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $response_ms = (int)round((microtime(true) - $started) * 1000);
                $healthy = $error === '' && $status >= 200 && $status < 400;
                $result = array('provider_key' => $key, 'status' => $healthy ? 'healthy' : 'down', 'response_ms' => $response_ms, 'http_status' => $status ?: null, 'message' => $healthy ? 'Provider responded successfully.' : ($error ?: 'Provider returned HTTP ' . $status));
            }
            $results[$key] = $result;
            if ($this->CI->db->table_exists('provider_health_checks')) $this->CI->db->insert('provider_health_checks', $result);
            if ($result['status'] === 'down') $this->alert('provider.' . $key, 'critical', $result['message'], $result);
        }
        return $results;
    }

    public function alert(string $key, string $severity, string $message, array $context = array()): void
    {
        if (!$this->CI->db->table_exists('operations_alerts')) return;
        $open = $this->CI->db->get_where('operations_alerts', array('alert_key' => $key, 'status' => 'open'), 1)->row_array();
        if ($open) {
            $this->CI->db->where('id', (int)$open['id'])->update('operations_alerts', array('severity' => $severity, 'message' => $message, 'context_json' => json_encode($context), 'last_seen_at' => date('Y-m-d H:i:s')));
        } else {
            $this->CI->db->insert('operations_alerts', array('alert_key' => $key, 'severity' => $severity, 'message' => $message, 'context_json' => json_encode($context)));
        }
        $email = trim((string)$this->CI->config->item('operations_alert_email'));
        if ($email !== '') {
            $this->CI->load->model('Email_model', 'email_model');
            $this->CI->email_model->send_smtp_mail(nl2br(html_escape($message)), '[Lvalues ' . strtoupper($severity) . '] ' . $key, $email);
        }
    }

    private function database_check(): bool
    {
        try {
            $query = $this->CI->db->query('SELECT 1 AS healthy');
            return $query && (int)$query->row('healthy') === 1;
        } catch (Throwable $e) {
            return false;
        }
    }
}
