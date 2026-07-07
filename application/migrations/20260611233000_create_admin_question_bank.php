<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_admin_question_bank extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS question_bank_questions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            legacy_exam_question_id INT UNSIGNED NULL,
            question_code VARCHAR(40) NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('single','multiple') NOT NULL DEFAULT 'single',
            category_id INT UNSIGNED NOT NULL DEFAULT 0,
            class_id INT UNSIGNED NOT NULL DEFAULT 0,
            subject_id INT UNSIGNED NOT NULL DEFAULT 0,
            topic VARCHAR(255) NULL,
            difficulty ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
            exam_type VARCHAR(120) NULL,
            tags VARCHAR(500) NULL,
            marks DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            negative_marks DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            explanation TEXT NULL,
            status ENUM('draft','active','on_hold','archived') NOT NULL DEFAULT 'draft',
            admin_remark TEXT NULL,
            created_by INT UNSIGNED NULL,
            updated_by INT UNSIGNED NULL,
            reviewed_by INT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_question_bank_code (question_code),
            UNIQUE KEY uq_question_bank_legacy (legacy_exam_question_id),
            KEY idx_qb_status_updated (status, updated_at),
            KEY idx_qb_subject_topic_status (subject_id, topic(100), status),
            KEY idx_qb_class_difficulty_status (class_id, difficulty, status),
            KEY idx_qb_exam_type_status (exam_type, status),
            FULLTEXT KEY ft_qb_question_text (question_text)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS question_bank_options (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT UNSIGNED NOT NULL,
            option_text TEXT NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_qb_options_question_order (question_id, sort_order),
            KEY idx_qb_options_question_correct (question_id, is_correct),
            CONSTRAINT fk_qb_options_question FOREIGN KEY (question_id)
                REFERENCES question_bank_questions(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS question_bank_import_jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            requested_by INT UNSIGNED NULL,
            original_filename VARCHAR(255) NULL,
            status ENUM('processing','completed','completed_with_errors','failed') NOT NULL DEFAULT 'processing',
            total_rows INT UNSIGNED NOT NULL DEFAULT 0,
            imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
            failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
            duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0,
            error_summary MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_qb_import_status_date (status, created_at),
            KEY idx_qb_import_user_date (requested_by, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->migrate_legacy_questions();
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS question_bank_import_jobs");
        $this->db->query("DROP TABLE IF EXISTS question_bank_options");
        $this->db->query("DROP TABLE IF EXISTS question_bank_questions");
    }

    private function migrate_legacy_questions()
    {
        if (!$this->db->table_exists('content_exam_questions')) {
            return;
        }
        $questions = $this->db->get('content_exam_questions')->result_array();
        foreach ($questions as $question) {
            $legacy_id = (int)$question['id'];
            if ($this->db->get_where('question_bank_questions', array('legacy_exam_question_id' => $legacy_id))->row_array()) {
                continue;
            }
            $this->db->insert('question_bank_questions', array(
                'legacy_exam_question_id' => $legacy_id,
                'question_code' => 'LEGACY-' . str_pad((string)$legacy_id, 6, '0', STR_PAD_LEFT),
                'question_text' => $question['question_text'],
                'question_type' => $question['question_type'] === 'multiple' ? 'multiple' : 'single',
                'topic' => $question['topic'],
                'difficulty' => in_array($question['difficulty'], array('Beginner','Intermediate','Advanced'), true) ? $question['difficulty'] : 'Beginner',
                'marks' => $question['marks'],
                'explanation' => $question['explanation'],
                'status' => $question['status'] === 'active' ? 'active' : 'archived',
                'created_by' => $question['tutor_id'],
                'updated_by' => $question['tutor_id'],
                'created_at' => $question['created_at'] ?: date('Y-m-d H:i:s'),
                'updated_at' => $question['updated_at'] ?: date('Y-m-d H:i:s'),
            ));
            $new_id = (int)$this->db->insert_id();
            $options = $this->db->order_by('sort_order', 'ASC')
                ->get_where('content_exam_options', array('question_id' => $legacy_id))->result_array();
            foreach ($options as $option) {
                $this->db->insert('question_bank_options', array(
                    'question_id' => $new_id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct'],
                    'sort_order' => $option['sort_order'],
                    'created_at' => $option['created_at'] ?: date('Y-m-d H:i:s'),
                    'updated_at' => $option['updated_at'] ?: date('Y-m-d H:i:s'),
                ));
            }
        }
    }
}
