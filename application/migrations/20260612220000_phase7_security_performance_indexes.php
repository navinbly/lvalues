<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Phase7_security_performance_indexes extends CI_Migration
{
    public function up()
    {
        $this->add_index('content_exams', 'idx_content_exams_review_updated', 'review_status, updated_at');
        $this->add_index('question_bank_questions', 'idx_qb_review_updated', 'review_status, updated_at');
        $this->add_index('content_nodes', 'idx_content_nodes_review_updated', 'review_status, updated_at');
        $this->add_index('content_exam_options', 'idx_exam_options_question_order', 'question_id, sort_order');
    }

    public function down()
    {
        $this->drop_index('content_exams', 'idx_content_exams_review_updated');
        $this->drop_index('question_bank_questions', 'idx_qb_review_updated');
        $this->drop_index('content_nodes', 'idx_content_nodes_review_updated');
        $this->drop_index('content_exam_options', 'idx_exam_options_question_order');
    }

    private function add_index($table, $name, $columns)
    {
        if (!$this->db->table_exists($table) || $this->index_exists($table, $name)) return;
        $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$columns})");
    }

    private function drop_index($table, $name)
    {
        if ($this->db->table_exists($table) && $this->index_exists($table, $name)) {
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }

    private function index_exists($table, $name)
    {
        $result = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", array($name));
        return $result && $result->num_rows() > 0;
    }
}
