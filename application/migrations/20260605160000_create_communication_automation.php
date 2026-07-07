<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_communication_automation extends CI_Migration
{
    public function up()
    {
        $this->add_course_column('delivery_mode', "ENUM('online','offline','both') NOT NULL DEFAULT 'online'");
        $this->add_course_column('schedule_text', "VARCHAR(191) NULL");
        $this->add_course_column('start_date', "DATE NULL");
        $this->add_course_column('location_text', "VARCHAR(255) NULL");

        $this->db->query("CREATE TABLE IF NOT EXISTS message_campaigns (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_key VARCHAR(191) NULL,
            campaign_type ENUM('manual','automatic') NOT NULL DEFAULT 'manual',
            trigger_event VARCHAR(80) NULL,
            reference_type VARCHAR(60) NULL,
            reference_id BIGINT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            email_body MEDIUMTEXT NULL,
            whatsapp_body MEDIUMTEXT NULL,
            channels_json TEXT NULL,
            audience_filters_json MEDIUMTEXT NULL,
            created_by INT UNSIGNED NULL,
            created_role ENUM('admin','tutor','system') NOT NULL DEFAULT 'system',
            total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
            sent_count INT UNSIGNED NOT NULL DEFAULT 0,
            failed_count INT UNSIGNED NOT NULL DEFAULT 0,
            skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('draft','processing','completed','partial','failed') NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_message_campaign_event (event_key),
            KEY idx_message_campaign_creator (created_by, created_role, created_at),
            KEY idx_message_campaign_reference (reference_type, reference_id),
            KEY idx_message_campaign_status (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS message_campaign_recipients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            channel ENUM('email','whatsapp','in_app') NOT NULL,
            recipient_address VARCHAR(255) NULL,
            rendered_subject VARCHAR(255) NULL,
            rendered_message MEDIUMTEXT NULL,
            status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
            error_message TEXT NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_campaign_student_channel (campaign_id, student_user_id, channel),
            KEY idx_campaign_recipient_status (campaign_id, status),
            KEY idx_campaign_recipient_student (student_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS notification_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NULL,
            user_id INT UNSIGNED NOT NULL,
            channel VARCHAR(30) NOT NULL DEFAULT 'in_app',
            reference_type VARCHAR(60) NULL,
            reference_id BIGINT UNSIGNED NULL,
            status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_notification_log_campaign (campaign_id, status),
            KEY idx_notification_log_user (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS whatsapp_message_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NULL,
            recipient_id BIGINT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            phone VARCHAR(40) NULL,
            provider VARCHAR(60) NULL,
            provider_message_id VARCHAR(191) NULL,
            status ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
            message MEDIUMTEXT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_whatsapp_campaign_status (campaign_id, status),
            KEY idx_whatsapp_user_date (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS whatsapp_message_logs');
        $this->db->query('DROP TABLE IF EXISTS notification_logs');
        $this->db->query('DROP TABLE IF EXISTS message_campaign_recipients');
        $this->db->query('DROP TABLE IF EXISTS message_campaigns');
    }

    private function add_course_column($column, $definition)
    {
        if ($this->db->table_exists('course') && !$this->db->field_exists($column, 'course')) {
            $this->db->query("ALTER TABLE course ADD COLUMN {$column} {$definition}");
        }
    }
}
