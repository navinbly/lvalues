<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_admin_scale_foundations extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id INT UNSIGNED NULL,
            actor_role VARCHAR(60) NULL,
            action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id VARCHAR(80) NULL,
            before_json MEDIUMTEXT NULL,
            after_json MEDIUMTEXT NULL,
            reason TEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            approval_status ENUM('not_required','pending','approved','rejected') NOT NULL DEFAULT 'not_required',
            reviewer_user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_admin_audit_actor_date (actor_user_id, created_at),
            KEY idx_admin_audit_entity (entity_type, entity_id),
            KEY idx_admin_audit_action_date (action, created_at),
            KEY idx_admin_audit_approval (approval_status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS admin_workflow_tasks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_type VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id VARCHAR(80) NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            status ENUM('open','assigned','waiting','escalated','resolved','cancelled') NOT NULL DEFAULT 'open',
            priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
            assigned_to INT UNSIGNED NULL,
            created_by INT UNSIGNED NULL,
            due_at DATETIME NULL,
            resolved_at DATETIME NULL,
            resolution_note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_admin_tasks_status_due (status, due_at),
            KEY idx_admin_tasks_assignee_status (assigned_to, status),
            KEY idx_admin_tasks_entity (entity_type, entity_id),
            KEY idx_admin_tasks_priority_due (priority, due_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS admin_export_jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            export_type VARCHAR(80) NOT NULL,
            requested_by INT UNSIGNED NULL,
            status ENUM('queued','processing','ready','failed','expired') NOT NULL DEFAULT 'queued',
            filters_json MEDIUMTEXT NULL,
            file_path VARCHAR(255) NULL,
            row_count INT UNSIGNED NULL,
            error_message TEXT NULL,
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            expires_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_admin_exports_status_requested (status, requested_at),
            KEY idx_admin_exports_type_requested (export_type, requested_at),
            KEY idx_admin_exports_requested_by (requested_by, requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS admin_kpi_snapshots (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            metric_key VARCHAR(120) NOT NULL,
            metric_scope VARCHAR(80) NOT NULL DEFAULT 'global',
            metric_value DECIMAL(18,4) NOT NULL DEFAULT 0,
            meta_json MEDIUMTEXT NULL,
            period_start DATETIME NULL,
            period_end DATETIME NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_admin_kpi_metric_period (metric_key, metric_scope, period_start, period_end),
            KEY idx_admin_kpi_generated (generated_at),
            KEY idx_admin_kpi_metric_generated (metric_key, generated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->add_index_if_columns_exist('users', 'idx_users_role_date', array('role_id', 'date_added'));
        $this->add_index_if_columns_exist('users', 'idx_users_instructor_date', array('is_instructor', 'date_added'));
        $this->add_index_if_columns_exist('users', 'idx_users_email', array('email'));

        $this->add_index_if_columns_exist('course', 'idx_course_status_date', array('status', 'date_added'));
        $this->add_index_if_columns_exist('course', 'idx_course_category_status', array('sub_category_id', 'status'));
        $this->add_index_if_columns_exist('course', 'idx_course_creator_status', array('creator', 'status'));
        $this->add_index_if_columns_exist('course', 'idx_course_user_status', array('user_id', 'status'));

        $this->add_index_if_columns_exist('payment', 'idx_payment_date', array('date_added'));
        $this->add_index_if_columns_exist('payment', 'idx_payment_user_date', array('user_id', 'date_added'));
        $this->add_index_if_columns_exist('payment', 'idx_payment_course_date', array('course_id', 'date_added'));
        $this->add_index_if_columns_exist('payment', 'idx_payment_type_date', array('payment_type', 'date_added'));

        $this->add_column_if_missing('contact', 'status', "VARCHAR(40) NOT NULL DEFAULT 'new'");
        $this->add_column_if_missing('contact', 'priority', "VARCHAR(20) NOT NULL DEFAULT 'normal'");
        $this->add_column_if_missing('contact', 'assigned_to', "INT UNSIGNED NULL");
        $this->add_column_if_missing('contact', 'due_at', "DATETIME NULL");
        $this->add_column_if_missing('contact', 'resolved_at', "DATETIME NULL");
        $this->add_column_if_missing('contact', 'resolution_note', "TEXT NULL");
        $this->add_index_if_columns_exist('contact', 'idx_contact_status_due', array('status', 'due_at'));
        $this->add_index_if_columns_exist('contact', 'idx_contact_priority_status', array('priority', 'status'));
        $this->add_index_if_columns_exist('contact', 'idx_contact_assignee_status', array('assigned_to', 'status'));
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS admin_kpi_snapshots");
        $this->db->query("DROP TABLE IF EXISTS admin_export_jobs");
        $this->db->query("DROP TABLE IF EXISTS admin_workflow_tasks");
        $this->db->query("DROP TABLE IF EXISTS admin_audit_logs");
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if ($this->db->table_exists($table) && !$this->db->field_exists($column, $table)) {
            $this->safe_query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function add_index_if_columns_exist($table, $index_name, $columns)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }
        if ($this->index_exists($table, $index_name)) {
            return;
        }
        foreach ($columns as $column) {
            if (!$this->db->field_exists($column, $table)) {
                return;
            }
        }
        $column_sql = implode('`,`', $columns);
        $this->safe_query("CREATE INDEX {$index_name} ON {$table}(`{$column_sql}`)");
    }

    private function index_exists($table, $index_name)
    {
        $query = $this->db->query(
            'SELECT 1 FROM information_schema.statistics '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            array($table, $index_name)
        );
        return $query && $query->num_rows() > 0;
    }

    private function safe_query($sql)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $this->db->query($sql);
        } catch (Throwable $exception) {
            log_message('error', 'Optional admin scale schema change skipped: ' . $exception->getMessage());
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }
}
