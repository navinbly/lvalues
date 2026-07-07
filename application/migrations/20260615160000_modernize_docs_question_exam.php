<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Modernize_docs_question_exam extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_nodes', 'reader_mode', "ENUM('interactive','pdf') NOT NULL DEFAULT 'interactive' AFTER content_type");
        $this->add_column('content_nodes', 'source_file', "VARCHAR(500) NULL AFTER reader_mode");
        $this->add_column('content_nodes', 'source_mime', "VARCHAR(120) NULL AFTER source_file");
        $this->add_column('content_exam_sections', 'selection_count', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER question_count");
        $this->add_column('content_exam_sections', 'pool_count', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER selection_count");
        $this->add_column('content_exam_sections', 'source_exam_type', "VARCHAR(120) NULL AFTER pool_count");
        $this->add_column('content_exam_sections', 'source_section', "VARCHAR(160) NULL AFTER source_exam_type");
        $this->add_column('content_exam_sections', 'source_difficulty', "VARCHAR(30) NULL AFTER source_section");
        $this->add_column('content_exam_attempt_answers', 'is_marked_for_review', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_correct");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_reading_progress (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            book_node_id INT NOT NULL,
            page_node_id INT NOT NULL,
            progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            last_read_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reading_progress_user_book (user_id, book_node_id),
            KEY idx_reading_progress_user_date (user_id, last_read_at),
            KEY idx_reading_progress_book (book_node_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS content_bookmarks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            book_node_id INT NOT NULL,
            page_node_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_content_bookmark (user_id, page_node_id),
            KEY idx_content_bookmark_user_book (user_id, book_node_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->add_index('content_nodes', 'idx_content_book_reader', array('content_type','status','is_deleted','slug'));
        $this->add_index('content_nodes', 'idx_content_root_order', array('root_key','parent_id','sort_order'));
        $this->add_index('content_node_pages', 'idx_content_page_search', array('status','is_deleted','node_id'));
        $this->add_index('question_bank_questions', 'idx_qb_exam_section_topic_type', array('exam_type','question_section','topic','question_type','status'));

        $this->db->query("UPDATE content_exam_sections SET selection_count=question_count WHERE selection_count=0");
        $this->db->query("UPDATE content_exam_sections SET pool_count=question_count WHERE pool_count=0");
        $this->db->query("UPDATE question_bank_questions SET question_section=topic WHERE (question_section IS NULL OR question_section='') AND topic IS NOT NULL");
    }

    public function down() {}

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function add_index($table, $index, $columns)
    {
        if (!$this->db->table_exists($table)) return;
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $index)
        )->num_rows() > 0;
        if (!$exists) $this->db->query("CREATE INDEX `{$index}` ON `{$table}`(`".implode('`,`', $columns)."`)");
    }
}
