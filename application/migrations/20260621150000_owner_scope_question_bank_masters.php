<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Owner_scope_question_bank_masters extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('question_bank_exam_masters')) {
            $this->drop_index('question_bank_exam_masters', 'uq_qb_exam_master_name');
            $this->add_unique_index('question_bank_exam_masters', 'uq_qb_exam_master_owner_name', array('created_by', 'exam_name'));
            $this->add_index('question_bank_exam_masters', 'idx_qb_exam_master_owner_status', array('created_by', 'status'));
        }

        if ($this->db->table_exists('question_bank_section_masters')) {
            $this->add_index('question_bank_section_masters', 'idx_qb_section_owner_status', array('created_by', 'status'));
        }

        if ($this->db->table_exists('question_bank_questions')) {
            $this->add_index('question_bank_questions', 'idx_qb_owner_exam_section_status', array('created_by', 'exam_type', 'question_section', 'status'));
        }
    }

    public function down()
    {
        if ($this->db->table_exists('question_bank_questions')) {
            $this->drop_index('question_bank_questions', 'idx_qb_owner_exam_section_status');
        }
        if ($this->db->table_exists('question_bank_section_masters')) {
            $this->drop_index('question_bank_section_masters', 'idx_qb_section_owner_status');
        }
        if ($this->db->table_exists('question_bank_exam_masters')) {
            $this->drop_index('question_bank_exam_masters', 'uq_qb_exam_master_owner_name');
            $this->drop_index('question_bank_exam_masters', 'idx_qb_exam_master_owner_status');
            $this->add_unique_index('question_bank_exam_masters', 'uq_qb_exam_master_name', array('exam_name'));
        }
    }

    private function add_index($table, $index, $columns)
    {
        if (!$this->index_exists($table, $index)) {
            $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $index . '` (`' . implode('`,`', $columns) . '`)');
        }
    }

    private function add_unique_index($table, $index, $columns)
    {
        if (!$this->index_exists($table, $index)) {
            $this->db->query('ALTER TABLE `' . $table . '` ADD UNIQUE KEY `' . $index . '` (`' . implode('`,`', $columns) . '`)');
        }
    }

    private function drop_index($table, $index)
    {
        if ($this->index_exists($table, $index)) {
            $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
        }
    }

    private function index_exists($table, $index)
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $index)
        )->num_rows() > 0;
    }
}
