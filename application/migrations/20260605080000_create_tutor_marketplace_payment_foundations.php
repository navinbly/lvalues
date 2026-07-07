<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_tutor_marketplace_payment_foundations extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_request_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            tutor_profile_id INT UNSIGNED NULL,
            batch_id INT UNSIGNED NULL,
            session_id INT UNSIGNED NULL,
            plan_type ENUM('one_time','monthly') NOT NULL DEFAULT 'one_time',
            billing_cycle_start DATE NULL,
            billing_cycle_end DATE NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'INR',
            platform_commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            platform_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tutor_payable_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_status ENUM('payment_disabled','draft','pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'payment_disabled',
            settlement_status ENUM('not_eligible','held_by_admin','ready_for_payout','payout_pending','paid_to_tutor','cancelled') NOT NULL DEFAULT 'not_eligible',
            gateway VARCHAR(60) NULL,
            gateway_transaction_id VARCHAR(191) NULL,
            disabled_reason TEXT NULL,
            admin_note TEXT NULL,
            paid_at DATETIME NULL,
            payout_ready_at DATETIME NULL,
            payout_paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tutor_request_plan_cycle (request_id, plan_type, billing_cycle_start),
            KEY idx_trp_request (request_id),
            KEY idx_trp_student_status (student_user_id, payment_status),
            KEY idx_trp_tutor_settlement (tutor_user_id, settlement_status),
            KEY idx_trp_batch_session (batch_id, session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_payment_renewals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_payment_id BIGINT UNSIGNED NOT NULL,
            request_id INT UNSIGNED NOT NULL,
            student_user_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            due_date DATE NOT NULL,
            reminder_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'INR',
            renewal_status ENUM('scheduled','reminder_due','payment_disabled','paid','overdue','cancelled','blocked') NOT NULL DEFAULT 'payment_disabled',
            access_status ENUM('allowed','blocked') NOT NULL DEFAULT 'blocked',
            reminder_sent_at DATETIME NULL,
            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tpr_due_status (due_date, renewal_status),
            KEY idx_tpr_request (request_id),
            KEY idx_tpr_parent (parent_payment_id),
            KEY idx_tpr_student_access (student_user_id, access_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS tutor_session_feedback (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id INT UNSIGNED NOT NULL,
            payment_id BIGINT UNSIGNED NULL,
            batch_id INT UNSIGNED NULL,
            session_id INT UNSIGNED NULL,
            student_user_id INT UNSIGNED NOT NULL,
            tutor_user_id INT UNSIGNED NOT NULL,
            rating DECIMAL(3,1) NULL,
            review TEXT NULL,
            feedback_status ENUM('pending','submitted','hidden') NOT NULL DEFAULT 'pending',
            submitted_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tsf_request_session_student (request_id, session_id, student_user_id),
            KEY idx_tsf_tutor_status (tutor_user_id, feedback_status),
            KEY idx_tsf_student_status (student_user_id, feedback_status),
            KEY idx_tsf_payment (payment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS tutor_session_feedback');
        $this->db->query('DROP TABLE IF EXISTS tutor_payment_renewals');
        $this->db->query('DROP TABLE IF EXISTS tutor_request_payments');
    }
}
