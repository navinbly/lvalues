<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Taxonomy scale foundation: link question_bank_questions to the exam/section
 * master tables by ID instead of only by name string, so renaming an exam
 * master can no longer orphan its questions. Existing name columns are kept
 * and all read paths still use them — this migration is behavior-neutral.
 */
class Migration_Add_question_bank_master_ids extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('question_bank_questions')) {
            return;
        }

        if (!$this->db->field_exists('exam_master_id', 'question_bank_questions')) {
            $this->dbforge->add_column('question_bank_questions', array(
                'exam_master_id' => array('type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'exam_type'),
            ));
            $this->db->query('ALTER TABLE `question_bank_questions` ADD KEY `idx_qbq_exam_master` (`exam_master_id`)');
        }
        if (!$this->db->field_exists('section_master_id', 'question_bank_questions')) {
            $this->dbforge->add_column('question_bank_questions', array(
                'section_master_id' => array('type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'exam_master_id'),
            ));
            $this->db->query('ALTER TABLE `question_bank_questions` ADD KEY `idx_qbq_section_master` (`section_master_id`)');
        }

        if (!$this->db->table_exists('question_bank_exam_masters')) {
            return;
        }

        // Backfill exam_master_id by matching the stored exam name (owner-aware).
        $this->db->query("
            UPDATE question_bank_questions q
            JOIN question_bank_exam_masters e
              ON e.exam_name = q.exam_type
             AND (e.created_by IS NULL OR q.created_by IS NULL OR e.created_by = q.created_by)
            SET q.exam_master_id = e.id
            WHERE q.exam_master_id IS NULL
              AND q.exam_type IS NOT NULL AND q.exam_type != ''
        ");

        if ($this->db->table_exists('question_bank_section_masters')) {
            // Backfill section_master_id using question_section, falling back to topic
            // (the same resolution rule the application uses everywhere).
            $this->db->query("
                UPDATE question_bank_questions q
                JOIN question_bank_section_masters s
                  ON s.exam_master_id = q.exam_master_id
                 AND s.section_name = COALESCE(NULLIF(q.question_section, ''), q.topic)
                SET q.section_master_id = s.id
                WHERE q.section_master_id IS NULL
                  AND q.exam_master_id IS NOT NULL
            ");
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('question_bank_questions')) {
            return;
        }
        if ($this->db->field_exists('section_master_id', 'question_bank_questions')) {
            $this->dbforge->drop_column('question_bank_questions', 'section_master_id');
        }
        if ($this->db->field_exists('exam_master_id', 'question_bank_questions')) {
            $this->dbforge->drop_column('question_bank_questions', 'exam_master_id');
        }
    }
}
