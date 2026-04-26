<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_tutor_batch_module_tables extends CI_Migration
{
    public function up()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batches (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id INT UNSIGNED NOT NULL,
            tutor_profile_id INT UNSIGNED NULL,
            category_id INT UNSIGNED NULL,
            class_id INT UNSIGNED NULL,
            subject_id INT UNSIGNED NULL,
            title VARCHAR(191) NOT NULL,
            slug VARCHAR(220) DEFAULT NULL,
            description TEXT NULL,
            batch_code VARCHAR(40) DEFAULT NULL,
            delivery_mode ENUM('online','offline','hybrid') NOT NULL DEFAULT 'online',
            capacity INT UNSIGNED NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            status ENUM('draft','published','completed','cancelled','archived') NOT NULL DEFAULT 'draft',
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tutor_batches_tutor (tutor_user_id),
            KEY idx_tutor_batches_status (status),
            UNIQUE KEY uq_tutor_batches_code (batch_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_students (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            invite_id INT UNSIGNED NULL,
            joined_at DATETIME NULL,
            left_at DATETIME NULL,
            membership_status ENUM('invited','active','removed','completed','rejected') NOT NULL DEFAULT 'active',
            progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            attendance_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tutor_batch_students (batch_id, student_user_id),
            KEY idx_tutor_batch_students_student (student_user_id),
            KEY idx_tutor_batch_students_status (membership_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_invites (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NULL,
            invite_email VARCHAR(191) NULL,
            invite_phone VARCHAR(40) NULL,
            invite_token VARCHAR(64) NOT NULL,
            invite_message TEXT NULL,
            invite_status ENUM('pending','accepted','rejected','expired','cancelled') NOT NULL DEFAULT 'pending',
            email_sent_at DATETIME NULL,
            whatsapp_sent_at DATETIME NULL,
            responded_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tutor_batch_invites_token (invite_token),
            KEY idx_tutor_batch_invites_batch (batch_id),
            KEY idx_tutor_batch_invites_student (student_user_id),
            KEY idx_tutor_batch_invites_status (invite_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_sessions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            agenda TEXT NULL,
            session_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Kolkata',
            session_type ENUM('regular','demo','doubt','test','other') NOT NULL DEFAULT 'regular',
            provider_type ENUM('manual','zoom','jitsi','bbb','100ms','custom') NOT NULL DEFAULT 'manual',
            provider_meeting_id VARCHAR(191) NULL,
            provider_room_code VARCHAR(191) NULL,
            host_join_url TEXT NULL,
            student_join_url TEXT NULL,
            meeting_passcode VARCHAR(80) NULL,
            location_text VARCHAR(255) NULL,
            is_live_class TINYINT(1) NOT NULL DEFAULT 1,
            is_recording_enabled TINYINT(1) NOT NULL DEFAULT 0,
            recording_status ENUM('not_requested','scheduled','recording','processing','ready','failed') NOT NULL DEFAULT 'not_requested',
            session_status ENUM('draft','published','live','completed','cancelled') NOT NULL DEFAULT 'draft',
            attendance_opened_at DATETIME NULL,
            started_at DATETIME NULL,
            ended_at DATETIME NULL,
            provider_payload LONGTEXT NULL,
            published_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tutor_batch_sessions_batch (batch_id),
            KEY idx_tutor_batch_sessions_date (session_date),
            KEY idx_tutor_batch_sessions_status (session_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_recordings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NOT NULL,
            provider_type VARCHAR(40) NULL,
            provider_recording_id VARCHAR(191) NULL,
            title VARCHAR(191) NULL,
            playback_url TEXT NULL,
            embed_url TEXT NULL,
            storage_path VARCHAR(255) NULL,
            duration_seconds INT UNSIGNED NULL,
            file_size_bytes BIGINT UNSIGNED NULL,
            mime_type VARCHAR(120) NULL,
            availability_status ENUM('processing','ready','failed','hidden') NOT NULL DEFAULT 'processing',
            allow_download TINYINT(1) NOT NULL DEFAULT 0,
            available_from DATETIME NULL,
            expires_at DATETIME NULL,
            provider_payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tutor_batch_recordings_session (session_id),
            KEY idx_tutor_batch_recordings_batch (batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_tasks (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            description LONGTEXT NULL,
            task_type ENUM('assignment','test') NOT NULL DEFAULT 'assignment',
            instructions LONGTEXT NULL,
            max_marks DECIMAL(8,2) NULL,
            passing_marks DECIMAL(8,2) NULL,
            attachment_path VARCHAR(255) NULL,
            external_link TEXT NULL,
            available_from DATETIME NULL,
            due_at DATETIME NULL,
            evaluation_status ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tutor_batch_tasks_batch (batch_id),
            KEY idx_tutor_batch_tasks_type (task_type),
            KEY idx_tutor_batch_tasks_status (evaluation_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_task_submissions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            submission_text LONGTEXT NULL,
            submission_file VARCHAR(255) NULL,
            submitted_at DATETIME NULL,
            checked_at DATETIME NULL,
            checked_by INT UNSIGNED NULL,
            score DECIMAL(8,2) NULL,
            feedback LONGTEXT NULL,
            submission_status ENUM('pending','submitted','late','checked','rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tutor_batch_task_submission (task_id, student_user_id),
            KEY idx_tutor_batch_task_submissions_batch (batch_id),
            KEY idx_tutor_batch_task_submissions_student (student_user_id),
            KEY idx_tutor_batch_task_submissions_status (submission_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @$this->db->query("ALTER TABLE tutor_batches ADD CONSTRAINT fk_tutor_batches_user FOREIGN KEY (tutor_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_students ADD CONSTRAINT fk_tutor_batch_students_batch FOREIGN KEY (batch_id) REFERENCES tutor_batches(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_invites ADD CONSTRAINT fk_tutor_batch_invites_batch FOREIGN KEY (batch_id) REFERENCES tutor_batches(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_sessions ADD CONSTRAINT fk_tutor_batch_sessions_batch FOREIGN KEY (batch_id) REFERENCES tutor_batches(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_recordings ADD CONSTRAINT fk_tutor_batch_recordings_session FOREIGN KEY (session_id) REFERENCES tutor_batch_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_tasks ADD CONSTRAINT fk_tutor_batch_tasks_batch FOREIGN KEY (batch_id) REFERENCES tutor_batches(id) ON DELETE CASCADE ON UPDATE CASCADE");
        @$this->db->query("ALTER TABLE tutor_batch_task_submissions ADD CONSTRAINT fk_tutor_batch_task_submissions_task FOREIGN KEY (task_id) REFERENCES tutor_batch_tasks(id) ON DELETE CASCADE ON UPDATE CASCADE");

        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_task_submissions');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_tasks');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_recordings');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_sessions');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_invites');
        $this->db->query('DROP TABLE IF EXISTS tutor_batch_students');
        $this->db->query('DROP TABLE IF EXISTS tutor_batches');
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
