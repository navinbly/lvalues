<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Serialize_audit_chain extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS immutable_audit_chain_head (
            id TINYINT UNSIGNED NOT NULL,
            head_hash CHAR(64) NULL,
            event_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME(6) NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("INSERT IGNORE INTO immutable_audit_chain_head (id, head_hash, event_count) VALUES (1, NULL, 0)");
        $head = $this->db->select('event_hash')->order_by('id', 'DESC')->limit(1)->get('immutable_audit_logs')->row('event_hash');
        $count = $this->db->count_all('immutable_audit_logs');
        $this->db->where('id', 1)->update('immutable_audit_chain_head', array('head_hash' => $head ?: null, 'event_count' => $count, 'updated_at' => date('Y-m-d H:i:s.u')));
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS immutable_audit_chain_head");
    }
}
