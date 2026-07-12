<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mock_test_lead_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
        $this->ensure_tables();
    }

    public function normalize_phone($countryCode, $phone)
    {
        $countryCode = preg_replace('/\D+/', '', (string)$countryCode);
        $phone = preg_replace('/\D+/', '', (string)$phone);
        if ($countryCode === '') $countryCode = '91';
        if (strpos($phone, $countryCode) === 0 && strlen($phone) > strlen($countryCode) + 6) {
            $phone = substr($phone, strlen($countryCode));
        }
        if ($countryCode === '91' && strlen($phone) !== 10) {
            return ['ok' => false, 'message' => 'Enter a valid 10 digit Indian mobile number without country code.'];
        }
        if (strlen($phone) < 7 || strlen($phone) > 15) {
            return ['ok' => false, 'message' => 'Enter a valid mobile number for the selected country.'];
        }
        return [
            'ok' => true,
            'country_code' => '+' . $countryCode,
            'national_number' => $phone,
            'phone_e164' => '+' . $countryCode . $phone,
        ];
    }

    public function find_or_create($phone, $payload = [])
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->get_where('mock_test_leads', ['phone_e164' => $phone], 1)->row_array();
        $row = [
            'phone_country_code' => (string)($payload['country_code'] ?? ''),
            'phone_national' => (string)($payload['national_number'] ?? ''),
            'name' => (string)($payload['name'] ?? ''),
            'email' => (string)($payload['email'] ?? ''),
            'consent' => !empty($payload['consent']) ? 1 : 0,
            'source' => (string)($payload['source'] ?? 'mock_test'),
            'ip_hash' => $this->ip_hash(),
            'user_agent' => substr((string)($this->input->user_agent() ?: ''), 0, 255),
            'updated_at' => $now,
        ];
        if ($existing) {
            $this->db->where('id', (int)$existing['id'])->update('mock_test_leads', $row);
            return (int)$existing['id'];
        }
        $row['phone_e164'] = $phone;
        $row['created_at'] = $now;
        $this->db->insert('mock_test_leads', $row);
        return (int)$this->db->insert_id();
    }

    public function can_start_exam($phone, $examId)
    {
        $since = date('Y-m-d H:i:s', time() - 86400);
        $count = (int)$this->db
            ->where('phone_e164', $phone)
            ->where('exam_id', (int)$examId)
            ->where('created_at >=', $since)
            ->count_all_results('mock_test_lead_attempts');
        if ($count >= 1) {
            return ['ok' => false, 'message' => 'This phone number has already started this free mock test today. Please try again after 24 hours.'];
        }
        return ['ok' => true];
    }

    public function link_attempt($leadId, $examId, $attemptId, $phone)
    {
        $this->db->insert('mock_test_lead_attempts', [
            'lead_id' => (int)$leadId,
            'exam_id' => (int)$examId,
            'attempt_id' => (int)$attemptId,
            'phone_e164' => $phone,
            'ip_hash' => $this->ip_hash(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function ip_hash()
    {
        return hash('sha256', (string)$this->input->ip_address());
    }

    private function ensure_tables()
    {
        if (!$this->db->table_exists('mock_test_leads')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'phone_e164' => ['type' => 'VARCHAR', 'constraint' => 32],
                'phone_country_code' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
                'phone_national' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
                'consent' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'source' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'mock_test'],
                'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('phone_e164');
            $this->dbforge->create_table('mock_test_leads', true);
        }

        if (!$this->db->table_exists('mock_test_lead_attempts')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'lead_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'exam_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'attempt_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'phone_e164' => ['type' => 'VARCHAR', 'constraint' => 32],
                'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('lead_id');
            $this->dbforge->add_key('exam_id');
            $this->dbforge->add_key('attempt_id');
            $this->dbforge->create_table('mock_test_lead_attempts', true);
        }
    }
}
