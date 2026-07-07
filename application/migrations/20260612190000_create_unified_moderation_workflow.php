<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_unified_moderation_workflow extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_exams', 'review_status', "VARCHAR(30) NOT NULL DEFAULT 'draft' AFTER status");
        $this->add_column('content_exams', 'admin_remark', "TEXT NULL AFTER review_status");
        $this->add_column('content_exams', 'submitted_for_review_at', "DATETIME NULL AFTER admin_remark");
        $this->add_column('content_exams', 'reviewed_by', "INT UNSIGNED NULL AFTER submitted_for_review_at");
        $this->add_column('content_exams', 'reviewed_at', "DATETIME NULL AFTER reviewed_by");

        $this->db->query("ALTER TABLE question_bank_questions MODIFY status
            ENUM('draft','in_review','active','rejected','on_hold','update_required','archived')
            NOT NULL DEFAULT 'draft'");
        $this->add_column('question_bank_questions', 'review_status', "VARCHAR(30) NOT NULL DEFAULT 'draft' AFTER status");
        $this->add_column('question_bank_questions', 'submitted_for_review_at', "DATETIME NULL AFTER admin_remark");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_moderation_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(40) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(40) NOT NULL,
            from_status VARCHAR(40) NULL,
            to_status VARCHAR(40) NOT NULL,
            reason TEXT NULL,
            actor_id INT UNSIGNED NOT NULL,
            creator_id INT UNSIGNED NULL,
            metadata_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_moderation_entity (entity_type, entity_id, created_at),
            KEY idx_moderation_actor (actor_id, created_at),
            KEY idx_moderation_status (to_status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("UPDATE content_exams SET review_status=CASE
            WHEN status='published' OR is_published=1 THEN 'published'
            WHEN status='archived' THEN 'archived'
            ELSE 'draft' END
            WHERE review_status='draft'");
        $this->db->query("UPDATE question_bank_questions SET review_status=CASE
            WHEN status='active' THEN 'published'
            WHEN status='archived' THEN 'archived'
            ELSE status END");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS content_moderation_events");
    }

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}
