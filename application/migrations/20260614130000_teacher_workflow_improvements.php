<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Teacher_workflow_improvements extends CI_Migration
{
    public function up()
    {
        $this->add_column('tutor_batches', 'enrollment_mode', "ENUM('open','approval') NOT NULL DEFAULT 'approval'");
        $this->add_column('tutor_batches', 'waitlist_enabled', "TINYINT(1) NOT NULL DEFAULT 1");
        $this->add_column('tutor_batches', 'health_score', "DECIMAL(5,2) NOT NULL DEFAULT 0");
        $this->add_column('tutor_batches', 'health_updated_at', "DATETIME NULL");

        $this->add_column('tutor_batch_students', 'parent_name', "VARCHAR(191) NULL");
        $this->add_column('tutor_batch_students', 'parent_email', "VARCHAR(191) NULL");
        $this->add_column('tutor_batch_students', 'parent_phone', "VARCHAR(40) NULL");
        $this->add_column('tutor_batch_students', 'enrollment_approved_by', "INT UNSIGNED NULL");
        $this->add_column('tutor_batch_students', 'enrollment_approved_at', "DATETIME NULL");

        $this->add_column('tutor_batch_sessions', 'recurrence_rule', "VARCHAR(255) NULL");
        $this->add_column('tutor_batch_sessions', 'recurrence_parent_id', "INT UNSIGNED NULL");
        $this->add_column('tutor_batch_sessions', 'reminder_minutes', "INT UNSIGNED NOT NULL DEFAULT 30");
        $this->add_column('tutor_batch_sessions', 'join_opens_minutes', "INT UNSIGNED NOT NULL DEFAULT 15");
        $this->add_column('tutor_batch_sessions', 'join_closes_minutes', "INT UNSIGNED NOT NULL DEFAULT 30");
        $this->add_column('tutor_batch_sessions', 'fallback_join_url', "TEXT NULL");
        $this->add_column('tutor_batch_sessions', 'recording_consent_required', "TINYINT(1) NOT NULL DEFAULT 0");
        $this->add_column('tutor_batch_sessions', 'recording_expires_at', "DATETIME NULL");
        $this->add_column('tutor_batch_sessions', 'captions_url', "TEXT NULL");
        $this->safe_query("ALTER TABLE tutor_batch_sessions MODIFY session_type ENUM('regular','demo','doubt','test','other','live_class','demo_class','revision','doubt_session','assignment_discussion','test_discussion') NOT NULL DEFAULT 'regular'");
        $this->safe_query("ALTER TABLE tutor_batch_sessions MODIFY session_status ENUM('draft','published','scheduled','live','completed','cancelled','archived') NOT NULL DEFAULT 'draft'");
        $this->safe_query("ALTER TABLE tutor_batch_sessions MODIFY recording_status ENUM('not_requested','scheduled','recording','processing','ready','available','not_available','failed') NOT NULL DEFAULT 'not_requested'");

        $this->db->query("CREATE TABLE IF NOT EXISTS teacher_workspace_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            teacher_user_id INT UNSIGNED NOT NULL,
            item_type ENUM('class','grading','request','follow_up','overdue','reminder','custom') NOT NULL,
            reference_type VARCHAR(60) NULL,
            reference_id BIGINT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            priority TINYINT UNSIGNED NOT NULL DEFAULT 50,
            status ENUM('open','in_progress','done','snoozed','cancelled') NOT NULL DEFAULT 'open',
            due_at DATETIME NULL,
            snoozed_until DATETIME NULL,
            action_url VARCHAR(500) NULL,
            source_key VARCHAR(191) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_teacher_workspace_source (teacher_user_id, source_key),
            KEY idx_teacher_workspace_agenda (teacher_user_id, status, priority, due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS teacher_draft_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            teacher_user_id INT UNSIGNED NOT NULL,
            entity_type VARCHAR(60) NOT NULL,
            entity_id VARCHAR(100) NOT NULL,
            editor_key VARCHAR(100) NOT NULL DEFAULT 'default',
            version_no INT UNSIGNED NOT NULL,
            content_json LONGTEXT NOT NULL,
            content_hash CHAR(64) NOT NULL,
            save_type ENUM('autosave','manual','recovery') NOT NULL DEFAULT 'autosave',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_teacher_draft_version (teacher_user_id, entity_type, entity_id, editor_key, version_no),
            KEY idx_teacher_draft_latest (teacher_user_id, entity_type, entity_id, editor_key, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_templates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id INT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            description TEXT NULL,
            template_json LONGTEXT NOT NULL,
            source_batch_id INT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_batch_template_tutor (tutor_user_id, is_active, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_waitlist (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL,
            status ENUM('waiting','offered','enrolled','declined','expired') NOT NULL DEFAULT 'waiting',
            parent_name VARCHAR(191) NULL,
            parent_email VARCHAR(191) NULL,
            parent_phone VARCHAR(40) NULL,
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            offered_at DATETIME NULL,
            expires_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_batch_waitlist_student (batch_id, student_user_id),
            KEY idx_batch_waitlist_position (batch_id, status, position)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_enrollment_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            status ENUM('pending','approved','rejected','waitlisted','cancelled') NOT NULL DEFAULT 'pending',
            parent_name VARCHAR(191) NULL,
            parent_email VARCHAR(191) NULL,
            parent_phone VARCHAR(40) NULL,
            note TEXT NULL,
            reviewed_by INT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_batch_enrollment_request (batch_id, student_user_id),
            KEY idx_batch_enrollment_review (batch_id, status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_batch_student_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            event_type VARCHAR(80) NOT NULL,
            from_status VARCHAR(40) NULL,
            to_status VARCHAR(40) NULL,
            details_json MEDIUMTEXT NULL,
            actor_user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_batch_student_history (batch_id, student_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS teacher_automated_reminders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_user_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NULL,
            reminder_type ENUM('session','assignment','test','absence','invitation','custom') NOT NULL,
            reference_type VARCHAR(60) NULL,
            reference_id BIGINT UNSIGNED NULL,
            recipient_user_id INT UNSIGNED NULL,
            scheduled_at DATETIME NOT NULL,
            channels_json VARCHAR(191) NOT NULL DEFAULT '[\"in_app\"]',
            status ENUM('scheduled','processing','sent','failed','cancelled') NOT NULL DEFAULT 'scheduled',
            dedupe_key VARCHAR(191) NOT NULL,
            sent_at DATETIME NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_teacher_reminder_dedupe (dedupe_key),
            KEY idx_teacher_reminder_due (status, scheduled_at),
            KEY idx_teacher_reminder_tutor (tutor_user_id, batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS live_provider_configs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id INT UNSIGNED NULL,
            provider_key ENUM('manual','zoom','jitsi','bbb','100ms','custom') NOT NULL,
            display_name VARCHAR(120) NOT NULL,
            config_json MEDIUMTEXT NULL,
            capabilities_json MEDIUMTEXT NULL,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            health_status ENUM('unknown','healthy','degraded','down') NOT NULL DEFAULT 'unknown',
            last_health_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_live_provider_owner (owner_user_id, provider_key),
            KEY idx_live_provider_enabled (is_enabled, is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS live_session_join_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            user_role VARCHAR(40) NOT NULL,
            decision ENUM('allowed','too_early','too_late','denied','fallback') NOT NULL,
            provider_key VARCHAR(40) NULL,
            joined_at DATETIME NULL,
            left_at DATETIME NULL,
            duration_seconds INT UNSIGNED NULL,
            connection_test_json MEDIUMTEXT NULL,
            ip_hash CHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_live_join_session_user (session_id, user_id, created_at),
            KEY idx_live_join_decision (decision, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS live_session_incidents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id INT UNSIGNED NOT NULL,
            reported_by INT UNSIGNED NULL,
            incident_type ENUM('connection','provider','audio','video','recording','attendance','other') NOT NULL,
            severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            description TEXT NOT NULL,
            provider_key VARCHAR(40) NULL,
            status ENUM('open','investigating','resolved') NOT NULL DEFAULT 'open',
            resolution_note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_live_incident_session (session_id, status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS live_recording_consents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            consent_status ENUM('granted','declined','revoked') NOT NULL,
            policy_version VARCHAR(40) NOT NULL DEFAULT '1',
            captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            revoked_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_recording_consent (session_id, user_id),
            KEY idx_recording_consent_status (session_id, consent_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS live_recording_playback_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            event_type ENUM('start','pause','resume','seek','complete') NOT NULL,
            position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            duration_seconds INT UNSIGNED NULL,
            playback_rate DECIMAL(4,2) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_playback_session_user (session_id, user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        foreach (array(
            array('manual', 'Manual link'), array('zoom', 'Zoom'), array('jitsi', 'Jitsi'),
            array('bbb', 'BigBlueButton'), array('100ms', '100ms'), array('custom', 'Custom provider')
        ) as $provider) {
            $this->db->query(
                "INSERT IGNORE INTO live_provider_configs (owner_user_id,provider_key,display_name,capabilities_json,is_enabled)
                 VALUES (NULL,?,?,?,1)",
                array($provider[0], $provider[1], json_encode(array('external_join' => true, 'recording' => in_array($provider[0], array('zoom','bbb','100ms'), true), 'captions' => in_array($provider[0], array('zoom','bbb','100ms'), true))))
            );
        }
    }

    public function down()
    {
        foreach (array('live_recording_playback_events','live_recording_consents','live_session_incidents','live_session_join_logs','live_provider_configs','teacher_automated_reminders','tutor_batch_student_history','tutor_batch_enrollment_requests','tutor_batch_waitlist','tutor_batch_templates','teacher_draft_versions','teacher_workspace_items') as $table) {
            $this->db->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function safe_query($sql)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try { $this->db->query($sql); } catch (Throwable $e) { log_message('error', 'Teacher workflow schema adjustment failed: ' . $e->getMessage()); }
        finally { $this->db->db_debug = $debug; }
    }
}
