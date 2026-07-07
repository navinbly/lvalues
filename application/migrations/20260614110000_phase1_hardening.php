<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Phase1_hardening extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS immutable_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_uuid CHAR(36) NOT NULL,
            event_type VARCHAR(60) NOT NULL,
            action VARCHAR(120) NOT NULL,
            actor_user_id INT UNSIGNED NULL,
            actor_role VARCHAR(60) NULL,
            entity_type VARCHAR(80) NOT NULL,
            entity_id VARCHAR(100) NULL,
            before_json MEDIUMTEXT NULL,
            after_json MEDIUMTEXT NULL,
            metadata_json MEDIUMTEXT NULL,
            request_id VARCHAR(100) NULL,
            ip_hash CHAR(64) NULL,
            user_agent_hash CHAR(64) NULL,
            previous_hash CHAR(64) NULL,
            event_hash CHAR(64) NOT NULL,
            created_at DATETIME(6) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_immutable_audit_uuid (event_uuid),
            UNIQUE KEY uq_immutable_audit_hash (event_hash),
            KEY idx_immutable_audit_event_date (event_type, created_at),
            KEY idx_immutable_audit_entity (entity_type, entity_id, created_at),
            KEY idx_immutable_audit_actor (actor_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS operation_idempotency_keys (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            operation_scope VARCHAR(80) NOT NULL,
            idempotency_key VARCHAR(128) NOT NULL,
            actor_user_id INT UNSIGNED NULL,
            request_hash CHAR(64) NOT NULL,
            status ENUM('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
            resource_type VARCHAR(80) NULL,
            resource_id VARCHAR(100) NULL,
            response_json MEDIUMTEXT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            expires_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_operation_idempotency (operation_scope, idempotency_key),
            KEY idx_operation_idempotency_expiry (expires_at),
            KEY idx_operation_idempotency_actor (actor_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS secure_upload_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id INT UNSIGNED NULL,
            owner_user_id INT UNSIGNED NULL,
            entity_type VARCHAR(80) NULL,
            entity_id VARCHAR(100) NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_path VARCHAR(500) NULL,
            extension VARCHAR(20) NOT NULL,
            detected_mime VARCHAR(120) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            malware_status ENUM('clean','rejected','scanner_unavailable') NOT NULL,
            status ENUM('accepted','rejected') NOT NULL,
            rejection_reason VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_secure_upload_actor (actor_user_id, created_at),
            KEY idx_secure_upload_entity (entity_type, entity_id),
            KEY idx_secure_upload_sha (sha256)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS backup_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_type ENUM('backup','restore_drill') NOT NULL,
            status ENUM('running','passed','failed') NOT NULL DEFAULT 'running',
            file_path VARCHAR(500) NULL,
            checksum_sha256 CHAR(64) NULL,
            size_bytes BIGINT UNSIGNED NULL,
            details_json MEDIUMTEXT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_backup_runs_type_date (run_type, started_at),
            KEY idx_backup_runs_status (status, started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS provider_health_checks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider_key VARCHAR(80) NOT NULL,
            status ENUM('healthy','degraded','down','not_configured') NOT NULL,
            response_ms INT UNSIGNED NULL,
            http_status SMALLINT UNSIGNED NULL,
            message VARCHAR(500) NULL,
            checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_provider_health_key_date (provider_key, checked_at),
            KEY idx_provider_health_status_date (status, checked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS operations_alerts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            alert_key VARCHAR(120) NOT NULL,
            severity ENUM('info','warning','critical') NOT NULL,
            status ENUM('open','acknowledged','resolved') NOT NULL DEFAULT 'open',
            message VARCHAR(1000) NOT NULL,
            context_json MEDIUMTEXT NULL,
            first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_operations_alert_status (status, severity, last_seen_at),
            KEY idx_operations_alert_key (alert_key, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->safe_query("DROP TRIGGER IF EXISTS immutable_audit_logs_no_update");
        $this->safe_query("DROP TRIGGER IF EXISTS immutable_audit_logs_no_delete");
        $this->db->query("CREATE TRIGGER immutable_audit_logs_no_update BEFORE UPDATE ON immutable_audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable audit logs cannot be updated'");
        $this->db->query("CREATE TRIGGER immutable_audit_logs_no_delete BEFORE DELETE ON immutable_audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Immutable audit logs cannot be deleted'");

        $this->add_column('enrol', 'idempotency_key', "VARCHAR(128) NULL");
        $this->add_column('tutor_batch_invites', 'idempotency_key', "VARCHAR(128) NULL");
        $this->add_column('tutor_batch_attendance', 'idempotency_key', "VARCHAR(128) NULL");
        $this->add_column('tutor_batch_assignment_submissions', 'grading_idempotency_key', "VARCHAR(128) NULL");
        $this->add_column('payout', 'idempotency_key', "VARCHAR(128) NULL");
        $this->add_unique_index('enrol', 'uq_enrol_user_course', array('user_id', 'course_id'));
        $this->add_unique_index('payout', 'uq_payout_idempotency', array('idempotency_key'));
    }

    public function down()
    {
        $this->safe_query("DROP TRIGGER IF EXISTS immutable_audit_logs_no_update");
        $this->safe_query("DROP TRIGGER IF EXISTS immutable_audit_logs_no_delete");
        $this->db->query("DROP TABLE IF EXISTS operations_alerts");
        $this->db->query("DROP TABLE IF EXISTS provider_health_checks");
        $this->db->query("DROP TABLE IF EXISTS backup_runs");
        $this->db->query("DROP TABLE IF EXISTS secure_upload_events");
        $this->db->query("DROP TABLE IF EXISTS operation_idempotency_keys");
        $this->db->query("DROP TABLE IF EXISTS immutable_audit_logs");
    }

    private function add_column($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function add_unique_index($table, $name, array $columns)
    {
        if (!$this->db->table_exists($table)) return;
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=? LIMIT 1',
            array($table, $name)
        )->num_rows() > 0;
        if ($exists) return;
        foreach ($columns as $column) {
            if (!$this->db->field_exists($column, $table)) return;
        }
        $quoted = '`' . implode('`,`', $columns) . '`';
        $this->safe_query("CREATE UNIQUE INDEX `{$name}` ON `{$table}` ({$quoted})");
    }

    private function safe_query($sql)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $this->db->query($sql);
        } catch (Throwable $e) {
            log_message('error', 'Phase 1 optional schema operation failed: ' . $e->getMessage());
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
