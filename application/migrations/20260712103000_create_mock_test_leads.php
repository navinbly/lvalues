<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_mock_test_leads extends CI_Migration
{
    public function up()
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

    public function down()
    {
        $this->dbforge->drop_table('mock_test_lead_attempts', true);
        $this->dbforge->drop_table('mock_test_leads', true);
    }
}
