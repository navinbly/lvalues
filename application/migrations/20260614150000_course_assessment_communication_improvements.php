<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Course_assessment_communication_improvements extends CI_Migration
{
    public function up()
    {
        $this->add_column('course','workflow_status',"ENUM('draft','pending','changes_requested','approved','published','archived') NOT NULL DEFAULT 'draft'");
        $this->add_column('course','moderation_reason',"TEXT NULL");
        $this->add_column('course','resubmission_count',"INT UNSIGNED NOT NULL DEFAULT 0");
        $this->add_column('course','scheduled_publish_at',"DATETIME NULL");
        $this->add_column('course','workflow_updated_at',"DATETIME NULL");
        $this->add_column('course','accessibility_score',"DECIMAL(5,2) NOT NULL DEFAULT 0");
        $this->add_column('course','accessibility_checked_at',"DATETIME NULL");
        $this->add_column('course','published_at',"DATETIME NULL");
        $this->safe_query("CREATE INDEX idx_course_workflow_schedule ON course(workflow_status,scheduled_publish_at)");

        $this->add_column('lesson','alt_text',"VARCHAR(500) NULL");
        $this->add_column('lesson','transcript_url',"TEXT NULL");
        $this->add_column('lesson','reading_order',"INT UNSIGNED NULL");
        $this->add_column('lesson','keyboard_notes',"TEXT NULL");
        $this->add_column('lesson','library_source_id',"BIGINT UNSIGNED NULL");

        $this->add_column('question_bank_questions','chapter',"VARCHAR(191) NULL");
        $this->add_column('question_bank_questions','learning_outcome',"VARCHAR(500) NULL");
        $this->add_column('question_bank_questions','cognitive_level',"ENUM('remember','understand','apply','analyze','evaluate','create') NOT NULL DEFAULT 'understand'");
        $this->safe_query("CREATE INDEX idx_qb_learning_taxonomy ON question_bank_questions(class_id,subject_id,chapter,difficulty)");

        $this->add_column('tutor_batch_tasks','rubric_id',"BIGINT UNSIGNED NULL");
        $this->add_column('tutor_batch_tasks','late_policy',"ENUM('allow','deduct','block') NOT NULL DEFAULT 'allow'");
        $this->add_column('tutor_batch_tasks','late_penalty_percent',"DECIMAL(5,2) NOT NULL DEFAULT 0");
        $this->add_column('tutor_batch_tasks','max_retakes',"INT UNSIGNED NOT NULL DEFAULT 0");
        $this->add_column('tutor_batch_tasks','plagiarism_check_enabled',"TINYINT(1) NOT NULL DEFAULT 0");
        $this->add_column('tutor_batch_assignment_submissions','plagiarism_score',"DECIMAL(5,2) NULL");
        $this->add_column('tutor_batch_assignment_submissions','submission_attempt',"INT UNSIGNED NOT NULL DEFAULT 1");
        $this->add_column('tutor_batch_assignment_submissions','plagiarism_status',"ENUM('not_checked','queued','clear','review','blocked','failed') NOT NULL DEFAULT 'not_checked'");
        $this->add_column('tutor_batch_assignment_submissions','moderation_status',"ENUM('not_required','pending','approved','changes_requested') NOT NULL DEFAULT 'not_required'");
        $this->add_column('content_exams','max_attempts',"INT UNSIGNED NOT NULL DEFAULT 0");
        $this->add_column('content_exams','retake_wait_minutes',"INT UNSIGNED NOT NULL DEFAULT 0");
        $this->add_column('content_exams','manual_moderation_required',"TINYINT(1) NOT NULL DEFAULT 0");
        $this->add_column('content_exam_attempts','moderation_status',"ENUM('auto_graded','pending','approved','adjusted') NOT NULL DEFAULT 'auto_graded'");
        $this->add_column('content_exam_attempts','moderated_by',"INT UNSIGNED NULL");
        $this->add_column('content_exam_attempts','moderated_at',"DATETIME NULL");
        $this->add_column('content_exam_attempts','moderation_note',"TEXT NULL");

        $this->add_column('message_campaigns','message_category',"ENUM('transactional','promotional') NOT NULL DEFAULT 'transactional'");
        $this->add_column('message_campaigns','template_key',"VARCHAR(80) NULL");
        $this->add_column('message_campaign_recipients','attempt_count',"INT UNSIGNED NOT NULL DEFAULT 0");
        $this->add_column('message_campaign_recipients','next_retry_at',"DATETIME NULL");
        $this->add_column('message_campaign_recipients','last_attempt_at',"DATETIME NULL");
        $this->add_column('message_campaign_recipients','delivery_metadata_json',"MEDIUMTEXT NULL");
        $this->safe_query("CREATE INDEX idx_campaign_retry ON message_campaign_recipients(status,next_retry_at)");

        $this->db->query("CREATE TABLE IF NOT EXISTS course_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id INT UNSIGNED NOT NULL,
            version_no INT UNSIGNED NOT NULL,
            snapshot_json LONGTEXT NOT NULL,
            change_summary VARCHAR(500) NULL,
            created_by INT UNSIGNED NOT NULL,
            created_role VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            restored_at DATETIME NULL,
            PRIMARY KEY(id),
            UNIQUE KEY uq_course_version(course_id,version_no),
            KEY idx_course_versions(course_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS course_accessibility_checks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id INT UNSIGNED NOT NULL,
            version_id BIGINT UNSIGNED NULL,
            check_type ENUM('captions','alt_text','reading_order','contrast','keyboard') NOT NULL,
            status ENUM('pass','warning','fail','manual_review') NOT NULL,
            score DECIMAL(5,2) NOT NULL DEFAULT 0,
            details TEXT NULL,
            checked_by INT UNSIGNED NULL,
            checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_course_accessibility(course_id,checked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS lesson_library (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id INT UNSIGNED NOT NULL,
            source_lesson_id INT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            lesson_type VARCHAR(80) NULL,
            content_json LONGTEXT NOT NULL,
            tags VARCHAR(500) NULL,
            is_shared TINYINT(1) NOT NULL DEFAULT 0,
            usage_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_lesson_library_owner(owner_user_id,is_shared,updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS assessment_rubrics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            total_points DECIMAL(10,2) NOT NULL DEFAULT 100,
            is_shared TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_rubric_owner(owner_user_id,is_shared)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS assessment_rubric_criteria (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            rubric_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            max_points DECIMAL(10,2) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY(id),
            KEY idx_rubric_criteria(rubric_id,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS reusable_feedback (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            feedback_text TEXT NOT NULL,
            category VARCHAR(80) NULL,
            is_shared TINYINT(1) NOT NULL DEFAULT 0,
            usage_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_feedback_owner(owner_user_id,category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS assessment_mastery (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_user_id INT UNSIGNED NOT NULL,
            cohort_type VARCHAR(40) NOT NULL DEFAULT 'exam',
            cohort_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            dimension_type ENUM('subject','chapter','topic','learning_outcome') NOT NULL,
            dimension_key VARCHAR(191) NOT NULL,
            dimension_label VARCHAR(191) NOT NULL,
            questions_seen INT UNSIGNED NOT NULL DEFAULT 0,
            correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
            score_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
            mastery_level ENUM('not_started','emerging','developing','proficient','mastered') NOT NULL DEFAULT 'not_started',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uq_student_mastery(student_user_id,cohort_type,cohort_id,dimension_type,dimension_key),
            KEY idx_mastery_cohort(cohort_type,cohort_id,dimension_type,score_percentage)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS communication_templates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id INT UNSIGNED NULL,
            template_key VARCHAR(80) NOT NULL,
            name VARCHAR(191) NOT NULL,
            message_category ENUM('transactional','promotional') NOT NULL DEFAULT 'transactional',
            subject_template VARCHAR(255) NULL,
            email_template MEDIUMTEXT NULL,
            whatsapp_template MEDIUMTEXT NULL,
            is_system TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uq_communication_template(owner_user_id,template_key),
            KEY idx_communication_template_active(is_active,message_category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS communication_preferences (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            email_transactional TINYINT(1) NOT NULL DEFAULT 1,
            email_promotional TINYINT(1) NOT NULL DEFAULT 0,
            whatsapp_transactional TINYINT(1) NOT NULL DEFAULT 1,
            whatsapp_promotional TINYINT(1) NOT NULL DEFAULT 0,
            in_app_transactional TINYINT(1) NOT NULL DEFAULT 1,
            in_app_promotional TINYINT(1) NOT NULL DEFAULT 0,
            quiet_hours_enabled TINYINT(1) NOT NULL DEFAULT 0,
            quiet_start TIME NULL,
            quiet_end TIME NULL,
            timezone VARCHAR(60) NOT NULL DEFAULT 'Asia/Kolkata',
            consent_version VARCHAR(40) NULL,
            consented_at DATETIME NULL,
            opted_out_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE KEY uq_communication_preferences_user(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        foreach (array(
            array('invitation','Invitation','transactional','You are invited to {{batch_name}}','Dear {{student_name}}, you have been invited to {{batch_name}}. Open {{login_link}}.'),
            array('reminder','Reminder','transactional','Reminder from Lvalues','Dear {{student_name}}, this is a reminder about your learning activity. Open {{login_link}}.'),
            array('absence','Absence follow-up','transactional','Class absence follow-up','Dear {{student_name}}, you were marked absent. Please contact your teacher and review the lesson.'),
            array('assignment','Assignment update','transactional','Assignment update','Dear {{student_name}}, an assignment update is available in {{batch_name}}. Open {{login_link}}.'),
            array('result','Result available','transactional','Your result is available','Dear {{student_name}}, your latest result is ready. Open {{login_link}}.'),
            array('progress','Progress summary','transactional','Learning progress summary','Dear {{student_name}}, your latest learning progress summary is available. Open {{login_link}}.')
        ) as $row) {
            $this->db->query("INSERT IGNORE INTO communication_templates(owner_user_id,template_key,name,message_category,subject_template,email_template,whatsapp_template,is_system) VALUES(NULL,?,?,?,?,?,?,1)",
                array($row[0],$row[1],$row[2],$row[3],$row[4],$row[4]));
        }

        $this->db->query("UPDATE course SET workflow_status=CASE
            WHEN status='active' THEN 'published'
            WHEN status='pending' THEN 'pending'
            WHEN status='draft' THEN 'draft'
            ELSE 'archived' END
            WHERE workflow_status='draft'");
    }

    public function down()
    {
        foreach(array('communication_preferences','communication_templates','assessment_mastery','reusable_feedback','assessment_rubric_criteria','assessment_rubrics','lesson_library','course_accessibility_checks','course_versions') as $table) {
            $this->db->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    private function add_column($table,$column,$definition)
    {
        if($this->db->table_exists($table)&&!$this->db->field_exists($column,$table))$this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private function safe_query($sql)
    {
        $debug=$this->db->db_debug;$this->db->db_debug=false;
        try{$this->db->query($sql);}catch(Throwable $e){log_message('error','Course assessment migration adjustment failed: '.$e->getMessage());}
        finally{$this->db->db_debug=$debug;}
    }
}
