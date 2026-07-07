<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_chatbot_logs_table extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS chatbot_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            matched_faq_id INT NULL,
            answer_found TINYINT(1) NOT NULL DEFAULT 0,
            visitor_ip VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_chatbot_logs_created_at (created_at),
            KEY idx_chatbot_logs_visitor_ip (visitor_ip),
            KEY idx_chatbot_logs_matched_faq_id (matched_faq_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS chatbot_logs");
    }
}