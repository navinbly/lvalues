<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_tutor_request_workflow_fields extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('tutor_requests')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS tutor_requests (
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
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        $this->add_column_if_missing('tutor_requests', 'tutor_profile_id', "INT UNSIGNED NULL AFTER tutor_user_id");
        $this->add_column_if_missing('tutor_requests', 'category_id', "INT UNSIGNED NULL AFTER tutor_profile_id");
        $this->add_column_if_missing('tutor_requests', 'class_id', "INT UNSIGNED NULL AFTER category_id");
        $this->add_column_if_missing('tutor_requests', 'query_text', "VARCHAR(255) NULL AFTER subject_id");
        $this->add_column_if_missing('tutor_requests', 'student_name_snapshot', "VARCHAR(191) NULL AFTER query_text");
        $this->add_column_if_missing('tutor_requests', 'student_email_snapshot', "VARCHAR(191) NULL AFTER student_name_snapshot");
        $this->add_column_if_missing('tutor_requests', 'student_phone_snapshot', "VARCHAR(40) NULL AFTER student_email_snapshot");
        $this->add_column_if_missing('tutor_requests', 'subject_name_snapshot', "VARCHAR(191) NULL AFTER student_phone_snapshot");
        $this->add_column_if_missing('tutor_requests', 'responded_at', "DATETIME NULL AFTER tutor_response");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_request_status_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id INT UNSIGNED NOT NULL,
            from_status VARCHAR(50) NULL,
            to_status VARCHAR(50) NOT NULL,
            note TEXT NULL,
            acted_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tutor_request_status_logs_request (request_id),
            KEY idx_tutor_request_status_logs_acted_by (acted_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        @$this->db->query("CREATE INDEX idx_tutor_requests_pending_lookup ON tutor_requests(tutor_user_id, status, created_at)");
        @$this->db->query("CREATE INDEX idx_tutor_requests_student_lookup ON tutor_requests(student_user_id, created_at)");
    }

    public function down()
    {
        if ($this->db->table_exists('tutor_request_status_logs')) {
            $this->db->query("DROP TABLE tutor_request_status_logs");
        }
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->db->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}
