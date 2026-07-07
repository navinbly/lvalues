<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Analytics_quality_improvements extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS teacher_student_interventions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NOT NULL,
            intervention_type ENUM('contact','attendance_plan','assignment_plan','mastery_plan','parent_update','support_referral','custom') NOT NULL,
            reason_code VARCHAR(80) NOT NULL,
            reason_text VARCHAR(500) NOT NULL,
            action_note TEXT NULL,
            status ENUM('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
            due_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_intervention_teacher_status(tutor_user_id,status,due_at),
            KEY idx_intervention_student(batch_id,student_user_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS teacher_quality_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id INT UNSIGNED NOT NULL,
            quality_score DECIMAL(5,2) NOT NULL,
            content_quality_score DECIMAL(5,2) NOT NULL,
            moderation_score DECIMAL(5,2) NOT NULL,
            student_feedback_score DECIMAL(5,2) NOT NULL,
            class_reliability_score DECIMAL(5,2) NOT NULL,
            engagement_score DECIMAL(5,2) NOT NULL,
            inputs_json MEDIUMTEXT NOT NULL,
            actions_json MEDIUMTEXT NULL,
            calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_teacher_quality_snapshot(tutor_user_id,calculated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS operational_job_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_key VARCHAR(100) NOT NULL,
            status ENUM('running','passed','partial','failed') NOT NULL DEFAULT 'running',
            processed_count INT UNSIGNED NOT NULL DEFAULT 0,
            success_count INT UNSIGNED NOT NULL DEFAULT 0,
            failure_count INT UNSIGNED NOT NULL DEFAULT 0,
            details_json MEDIUMTEXT NULL,
            error_message TEXT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY(id),
            KEY idx_operational_job_status(job_key,status,started_at),
            KEY idx_operational_job_recent(started_at,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        foreach(array('operational_job_runs','teacher_quality_snapshots','teacher_student_interventions') as $table) {
            $this->db->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}
