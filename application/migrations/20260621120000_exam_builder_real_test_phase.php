<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Exam_builder_real_test_phase extends CI_Migration
{
    public function up()
    {
        $this->add_column('content_exams', 'exam_mode', "ENUM('mock','real','practice') NOT NULL DEFAULT 'mock' AFTER exam_type");
        $this->add_column('content_exam_sections', 'section_time_minutes', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER pool_count");
        $this->add_column('content_exam_sections', 'selection_mode', "ENUM('random_pool','manual','mixed') NOT NULL DEFAULT 'random_pool' AFTER section_time_minutes");
        $this->add_column('content_exam_attempt_questions', 'section_time_minutes', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER section_order");
        $this->add_index('content_exam_sections', 'idx_exam_sections_source_draw', array('exam_id','source_exam_type','source_section','selection_mode'));
        $this->db->query("UPDATE content_exam_sections SET section_time_minutes=0 WHERE section_time_minutes IS NULL");
        $this->db->query("UPDATE content_exam_sections SET selection_mode='random_pool' WHERE selection_mode IS NULL OR selection_mode='' ");
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
