<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_welcome_email_and_sponsored_courses extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS email_delivery_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NULL,
            email VARCHAR(191) NOT NULL,
            email_type VARCHAR(80) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('success','failed','skipped') NOT NULL DEFAULT 'success',
            provider VARCHAR(80) NULL,
            error_message TEXT NULL,
            meta_json MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email_logs_user_type (user_id, email_type),
            KEY idx_email_logs_email_type (email, email_type),
            KEY idx_email_logs_status_date (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS sponsored_courses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_title VARCHAR(191) NOT NULL,
            provider_name VARCHAR(191) NOT NULL,
            course_description TEXT NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            class_timing VARCHAR(191) NULL,
            fees DECIMAL(10,2) NULL,
            mode ENUM('online','offline','hybrid') NOT NULL DEFAULT 'online',
            location VARCHAR(255) NULL,
            contact_email VARCHAR(191) NULL,
            contact_phone VARCHAR(60) NULL,
            registration_link VARCHAR(500) NULL,
            banner_image VARCHAR(255) NULL,
            status ENUM('draft','published','unpublished','archived') NOT NULL DEFAULT 'draft',
            display_order INT NOT NULL DEFAULT 0,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sponsored_status_order (status, display_order, created_at),
            KEY idx_sponsored_dates (start_date, end_date),
            KEY idx_sponsored_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS sponsored_courses');
        $this->db->query('DROP TABLE IF EXISTS email_delivery_logs');
    }
}
