<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_tutoring_tables extends CI_Migration
{
    public function up()
    {
        // Ensure InnoDB for FK support
        $this->db->query("SET FOREIGN_KEY_CHECKS=0;");

        /**
         * 1) tutor_profiles
         */
        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_profiles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                headline VARCHAR(255) NULL,
                bio TEXT NULL,
                qualification VARCHAR(255) NULL,
                experience_years INT NULL,
                teaching_mode ENUM('online','offline','both') NOT NULL DEFAULT 'both',
                city VARCHAR(120) NULL,
                state VARCHAR(120) NULL,
                country VARCHAR(120) NULL,
                pincode VARCHAR(20) NULL,
                lat DECIMAL(10,7) NULL,
                lng DECIMAL(10,7) NULL,
                hourly_fee DECIMAL(10,2) NULL,
                profile_photo VARCHAR(255) NULL,
                status ENUM('draft','pending','active','blocked') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tutor_profiles_user_id (user_id),
                KEY idx_tutor_profiles_lat_lng (lat, lng),
                KEY idx_tutor_profiles_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        /**
         * 2) tutor_subjects
         * subject_id should map to your existing subject/category table
         */
        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_subjects (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tutor_profile_id INT UNSIGNED NOT NULL,
                subject_id INT UNSIGNED NOT NULL,
                level VARCHAR(60) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_tutor_subject (tutor_profile_id, subject_id),
                KEY idx_tutor_subject_subject_id (subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        /**
         * 3) tutor_sessions
         * This powers the homepage Upcoming Sessions table (Step 2).
         */
        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_sessions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                tutor_user_id INT UNSIGNED NOT NULL,
                subject_id INT UNSIGNED NULL,
                course_id INT UNSIGNED NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                mode ENUM('online','offline') NOT NULL DEFAULT 'online',
                location_text VARCHAR(255) NULL,
                lat DECIMAL(10,7) NULL,
                lng DECIMAL(10,7) NULL,
                start_at DATETIME NOT NULL,
                end_at DATETIME NOT NULL,
                capacity INT UNSIGNED NOT NULL DEFAULT 1,
                status ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_tutor_sessions_tutor_start (tutor_user_id, start_at),
                KEY idx_tutor_sessions_status_start (status, start_at),
                KEY idx_tutor_sessions_subject (subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        /**
         * 4) tutor_requests (Uber/Ola request workflow)
         */
        $this->db->query("
            CREATE TABLE IF NOT EXISTS tutor_requests (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_user_id INT UNSIGNED NOT NULL,
                tutor_user_id INT UNSIGNED NOT NULL,
                subject_id INT UNSIGNED NULL,
                preferred_mode ENUM('online','offline','both') NOT NULL DEFAULT 'both',
                student_location_text VARCHAR(255) NULL,
                student_lat DECIMAL(10,7) NULL,
                student_lng DECIMAL(10,7) NULL,
                message TEXT NULL,
                status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
                tutor_response TEXT NULL,
                approved_session_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_tutor_requests_tutor_status (tutor_user_id, status),
                KEY idx_tutor_requests_student (student_user_id),
                KEY idx_tutor_requests_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        /**
         * 5) notifications (in-app notifications; email later)
         */
        $this->db->query("
			CREATE TABLE IF NOT EXISTS tutor_notifications (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id INT UNSIGNED NOT NULL,
				type VARCHAR(50) NOT NULL,
				ref_table VARCHAR(50) NULL,
				ref_id INT UNSIGNED NULL,
				title VARCHAR(255) NOT NULL,
				body TEXT NULL,
				is_read TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				read_at DATETIME NULL,
				PRIMARY KEY (id),
				KEY idx_tutor_notifications_user_read (user_id, is_read),
				KEY idx_tutor_notifications_created (created_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        /**
         * Add foreign keys safely (some projects use different users PK naming).
         * If your users table is "users" with PK "id", this will work.
         * If not, tell me your exact PK and I’ll adjust.
         */
        $this->db->query("SET FOREIGN_KEY_CHECKS=1;");

        // Try FK additions (ignore if already exists or naming differs)
        $this->safe_fk("ALTER TABLE tutor_profiles
            ADD CONSTRAINT fk_tutor_profiles_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->safe_fk("ALTER TABLE tutor_subjects
            ADD CONSTRAINT fk_tutor_subjects_profile
            FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(id)
            ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->safe_fk("ALTER TABLE tutor_sessions
            ADD CONSTRAINT fk_tutor_sessions_tutor_user
            FOREIGN KEY (tutor_user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->safe_fk("ALTER TABLE tutor_requests
            ADD CONSTRAINT fk_tutor_requests_student
            FOREIGN KEY (student_user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->safe_fk("ALTER TABLE tutor_requests
            ADD CONSTRAINT fk_tutor_requests_tutor
            FOREIGN KEY (tutor_user_id) REFERENCES users(id)
            ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->safe_fk("ALTER TABLE tutor_requests
            ADD CONSTRAINT fk_tutor_requests_session
            FOREIGN KEY (approved_session_id) REFERENCES tutor_sessions(id)
            ON DELETE SET NULL ON UPDATE CASCADE;");

		$this->safe_fk("ALTER TABLE tutor_notifications
			ADD CONSTRAINT fk_tutor_notifications_user
			FOREIGN KEY (user_id) REFERENCES users(id)
			ON DELETE CASCADE ON UPDATE CASCADE;");
    }

    public function down()
    {
        $this->db->query("SET FOREIGN_KEY_CHECKS=0;");
        $this->db->query("DROP TABLE IF EXISTS tutor_notifications;");
        $this->db->query("DROP TABLE IF EXISTS tutor_requests;");
        $this->db->query("DROP TABLE IF EXISTS tutor_sessions;");
        $this->db->query("DROP TABLE IF EXISTS tutor_subjects;");
        $this->db->query("DROP TABLE IF EXISTS tutor_profiles;");
        $this->db->query("SET FOREIGN_KEY_CHECKS=1;");
    }

    /*private function safe_fk($sql)
    {
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            // Ignore FK errors to keep migration runnable in slightly different schemas
        }
    }*/
	
	private function safe_fk($sql)
	{
		// simple_query returns TRUE/FALSE and does NOT trigger CI's fatal DB error screen
		$this->db->simple_query($sql);
	}
}
