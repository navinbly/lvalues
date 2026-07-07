<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_exam_pattern_builder extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_exams', 'managed_by_admin', "TINYINT(1) NOT NULL DEFAULT 0 AFTER tutor_id");
        $this->add_column('content_exams', 'exam_type', "VARCHAR(120) NULL AFTER difficulty");
        $this->add_column('content_exams', 'pattern_version', "INT UNSIGNED NOT NULL DEFAULT 1 AFTER exam_type");
        $this->add_column('content_exams', 'updated_by', "INT UNSIGNED NULL AFTER created_by");

        $this->add_column('content_exam_questions', 'question_bank_id', "BIGINT UNSIGNED NULL AFTER exam_id");
        $this->add_column('content_exam_questions', 'section_id', "BIGINT UNSIGNED NULL AFTER question_bank_id");
        $this->add_column('content_exam_questions', 'negative_marks', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER marks");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_exam_sections (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            instructions TEXT NULL,
            question_count INT UNSIGNED NOT NULL DEFAULT 0,
            total_marks DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            default_marks DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            default_negative_marks DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_exam_sections_exam_order (exam_id, sort_order),
            CONSTRAINT fk_exam_sections_exam FOREIGN KEY (exam_id)
                REFERENCES content_exams(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_exam_question_bank_map (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            exam_id INT UNSIGNED NOT NULL,
            section_id BIGINT UNSIGNED NOT NULL,
            question_bank_id BIGINT UNSIGNED NOT NULL,
            marks_override DECIMAL(10,2) NULL,
            negative_marks_override DECIMAL(10,2) NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 1,
            materialized_question_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_exam_bank_question (exam_id, question_bank_id),
            KEY idx_exam_map_section_order (section_id, sort_order),
            KEY idx_exam_map_bank (question_bank_id),
            CONSTRAINT fk_exam_map_exam FOREIGN KEY (exam_id)
                REFERENCES content_exams(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_exam_map_section FOREIGN KEY (section_id)
                REFERENCES content_exam_sections(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_exam_map_bank FOREIGN KEY (question_bank_id)
                REFERENCES question_bank_questions(id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->add_index('content_exams', 'idx_content_exams_admin_status', array('managed_by_admin','status'));
        $this->add_index('content_exam_questions', 'idx_exam_questions_bank_status', array('question_bank_id','status'));
        $this->db->query("UPDATE content_exams SET managed_by_admin=1 WHERE created_by IN (SELECT id FROM users WHERE role_id=1)");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS content_exam_question_bank_map");
        $this->db->query("DROP TABLE IF EXISTS content_exam_sections");
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
