<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Phase0_data_integrity extends CI_Migration
{
    public function up()
    {
        $this->clean_zero_dates();
        $this->deduplicate_batch_invites();
        $this->add_invitation_unique_key();
        $this->add_date_checks();
    }

    public function down()
    {
        if ($this->index_exists('tutor_batch_invites', 'uq_tutor_batch_invite_context')) {
            $this->db->query('ALTER TABLE tutor_batch_invites DROP INDEX uq_tutor_batch_invite_context');
        }
        foreach (['invite_student_key','target_category_key','target_class_key','target_subject_key'] as $column) {
            if ($this->db->field_exists($column, 'tutor_batch_invites')) {
                $this->db->query("ALTER TABLE tutor_batch_invites DROP COLUMN `{$column}`");
            }
        }
    }

    private function clean_zero_dates()
    {
        $columns = $this->db->query(
            "SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE,IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND DATA_TYPE IN ('date','datetime','timestamp')"
        )->result_array();

        foreach ($columns as $column) {
            $table = str_replace('`', '``', $column['TABLE_NAME']);
            $name = str_replace('`', '``', $column['COLUMN_NAME']);
            $replacement = $column['IS_NULLABLE'] === 'YES'
                ? 'NULL'
                : ($column['DATA_TYPE'] === 'date' ? 'CURRENT_DATE' : 'CURRENT_TIMESTAMP');
            $this->db->query(
                "UPDATE `{$table}` SET `{$name}`={$replacement}
                 WHERE CAST(`{$name}` AS CHAR) LIKE '0000-00-00%'"
            );
        }
    }

    private function deduplicate_batch_invites()
    {
        if (!$this->db->table_exists('tutor_batch_invites')) return;
        $this->db->query(
            "UPDATE tutor_batch_students s
             JOIN tutor_batch_invites old_i ON old_i.id=s.invite_id
             JOIN tutor_batch_invites keep_i
               ON keep_i.batch_id=old_i.batch_id
              AND COALESCE(keep_i.student_user_id,0)=COALESCE(old_i.student_user_id,0)
              AND LOWER(COALESCE(keep_i.invite_email,''))=LOWER(COALESCE(old_i.invite_email,''))
              AND COALESCE(keep_i.target_category_id,0)=COALESCE(old_i.target_category_id,0)
              AND COALESCE(keep_i.target_class_id,0)=COALESCE(old_i.target_class_id,0)
              AND COALESCE(keep_i.target_subject_id,0)=COALESCE(old_i.target_subject_id,0)
              AND keep_i.id>old_i.id
             SET s.invite_id=keep_i.id"
        );
        $this->db->query(
            "DELETE old_i FROM tutor_batch_invites old_i
             JOIN tutor_batch_invites keep_i
               ON keep_i.batch_id=old_i.batch_id
              AND COALESCE(keep_i.student_user_id,0)=COALESCE(old_i.student_user_id,0)
              AND LOWER(COALESCE(keep_i.invite_email,''))=LOWER(COALESCE(old_i.invite_email,''))
              AND COALESCE(keep_i.target_category_id,0)=COALESCE(old_i.target_category_id,0)
              AND COALESCE(keep_i.target_class_id,0)=COALESCE(old_i.target_class_id,0)
              AND COALESCE(keep_i.target_subject_id,0)=COALESCE(old_i.target_subject_id,0)
              AND keep_i.id>old_i.id"
        );
    }

    private function add_invitation_unique_key()
    {
        if (!$this->db->table_exists('tutor_batch_invites')) return;
        $columns = [
            'invite_student_key' => "VARCHAR(191) AS (COALESCE(CAST(student_user_id AS CHAR),LOWER(invite_email),'')) STORED",
            'target_category_key' => "INT AS (COALESCE(target_category_id,0)) STORED",
            'target_class_key' => "INT AS (COALESCE(target_class_id,0)) STORED",
            'target_subject_key' => "INT AS (COALESCE(target_subject_id,0)) STORED",
        ];
        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'tutor_batch_invites')) {
                $this->db->query("ALTER TABLE tutor_batch_invites ADD COLUMN `{$name}` {$definition}");
            }
        }
        if (!$this->index_exists('tutor_batch_invites', 'uq_tutor_batch_invite_context')) {
            $this->db->query(
                'ALTER TABLE tutor_batch_invites ADD UNIQUE KEY uq_tutor_batch_invite_context '
                . '(batch_id,invite_student_key,target_category_key,target_class_key,target_subject_key)'
            );
        }
    }

    private function add_date_checks()
    {
        $checks = [
            ['tutor_batches','chk_tutor_batches_dates',"(start_date IS NULL OR start_date >= '1000-01-01') AND (end_date IS NULL OR end_date >= start_date)"],
            ['tutor_batch_sessions','chk_tutor_batch_session_date',"session_date >= '1000-01-01'"],
            ['course','chk_course_start_date',"(start_date IS NULL OR start_date >= '1000-01-01')"],
            ['sponsored_courses','chk_sponsored_course_dates',"(start_date IS NULL OR start_date >= '1000-01-01') AND (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)"],
        ];
        foreach ($checks as $check) {
            if ($this->db->table_exists($check[0]) && !$this->constraint_exists($check[0], $check[1])) {
                $this->db->query("ALTER TABLE `{$check[0]}` ADD CONSTRAINT `{$check[1]}` CHECK ({$check[2]})");
            }
        }
    }

    private function index_exists($table, $name)
    {
        return $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name=?", [$name])->num_rows() > 0;
    }

    private function constraint_exists($table, $name)
    {
        return $this->db->query(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?',
            [$table, $name]
        )->num_rows() > 0;
    }
}
