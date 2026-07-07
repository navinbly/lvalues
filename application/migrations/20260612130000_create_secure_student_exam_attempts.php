<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_secure_student_exam_attempts extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_exam_attempts', 'access_token_hash', "CHAR(64) NULL AFTER attempt_code");
        $this->add_column('content_exam_attempts', 'expires_at', "DATETIME NULL AFTER started_at");
        $this->add_column('content_exam_attempts', 'last_activity_at', "DATETIME NULL AFTER expires_at");
        $this->add_column('content_exam_attempts', 'autosaved_at', "DATETIME NULL AFTER last_activity_at");
        $this->add_column('content_exam_attempts', 'current_question_index', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER attempt_mode");
        $this->add_column('content_exam_attempts', 'pattern_version', "INT UNSIGNED NOT NULL DEFAULT 1 AFTER current_question_index");
        $this->add_column('content_exam_attempts', 'is_timed', "TINYINT(1) NOT NULL DEFAULT 1 AFTER pattern_version");
        $this->add_column('content_exam_attempts', 'allow_feedback_during_attempt', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_timed");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_exam_attempt_questions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id INT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            section_id BIGINT UNSIGNED NULL,
            section_title VARCHAR(180) NOT NULL DEFAULT 'General',
            section_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            question_order INT UNSIGNED NOT NULL,
            question_text TEXT NOT NULL,
            question_type VARCHAR(20) NOT NULL DEFAULT 'single',
            marks DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            negative_marks DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            explanation TEXT NULL,
            topic VARCHAR(255) NULL,
            difficulty VARCHAR(30) NULL,
            options_json LONGTEXT NOT NULL,
            correct_option_ids TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_attempt_snapshot_question (attempt_id, question_id),
            KEY idx_attempt_snapshot_order (attempt_id, question_order),
            KEY idx_attempt_snapshot_section (attempt_id, section_order, question_order),
            CONSTRAINT fk_attempt_snapshot_attempt FOREIGN KEY (attempt_id)
                REFERENCES content_exam_attempts(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->add_index('content_exam_attempts', 'idx_attempt_access_token', array('access_token_hash'));
        $this->add_index('content_exam_attempts', 'idx_attempt_owner_status', array('user_id','submitted_at'));
        $this->db->query("UPDATE content_exam_attempts
            SET last_activity_at=COALESCE(submitted_at, started_at, created_at),
                is_timed=CASE WHEN attempt_mode='practice' THEN 0 ELSE 1 END,
                allow_feedback_during_attempt=CASE WHEN attempt_mode='practice' THEN 1 ELSE 0 END
            WHERE last_activity_at IS NULL");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS content_exam_attempt_questions");
    }

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function add_index($table, $index, $columns)
    {
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $index)
        )->num_rows() > 0;
        if (!$exists) {
            $this->db->query("CREATE INDEX {$index} ON {$table}(`" . implode('`,`', $columns) . "`)");
        }
    }
}
