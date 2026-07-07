<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Admin_content_questionbank_enhancement extends CI_Migration
{
    public function up()
    {
        $this->add_column('question_bank_questions', 'question_section', "VARCHAR(160) NULL AFTER exam_type");
        $this->add_column('content_exam_questions', 'question_section', "VARCHAR(160) NULL AFTER topic");
        $this->add_index('question_bank_questions', 'idx_qb_exam_section_topic_status', array('exam_type', 'question_section', 'topic', 'status'));
        $this->add_index('question_bank_questions', 'idx_qb_type_difficulty_status', array('question_type', 'difficulty', 'status'));
        $this->add_index('content_exam_questions', 'idx_exam_questions_section_topic', array('section_id', 'question_section', 'topic'));
    }

    public function down()
    {
        $this->drop_index('content_exam_questions', 'idx_exam_questions_section_topic');
        $this->drop_index('question_bank_questions', 'idx_qb_type_difficulty_status');
        $this->drop_index('question_bank_questions', 'idx_qb_exam_section_topic_status');
    }

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
        if (!$exists) {
            $this->db->query("CREATE INDEX `{$index}` ON `{$table}`(`" . implode('`,`', $columns) . "`)");
        }
    }

    private function drop_index($table, $index)
    {
        if (!$this->db->table_exists($table)) return;
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $index)
        )->num_rows() > 0;
        if ($exists) {
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
}
