-- ============================================================
-- Lvalues – Suspicious Login / New Device Alert
-- Run these 3 statements on Hostinger MySQL (phpMyAdmin or SSH)
-- Safe to run multiple times (IF NOT EXISTS used)
-- ============================================================

-- 1. Login history: every successful login is logged here
CREATE TABLE IF NOT EXISTS `user_login_history` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)      NOT NULL,
  `role`            ENUM('student','tutor','admin') NOT NULL DEFAULT 'student',
  `email`           VARCHAR(191) NOT NULL DEFAULT '',
  `ip_address`      VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`      TEXT,
  `browser_name`    VARCHAR(100) DEFAULT NULL,
  `browser_version` VARCHAR(50)  DEFAULT NULL,
  `os_name`         VARCHAR(100) DEFAULT NULL,
  `device_type`     ENUM('desktop','mobile','tablet','unknown') NOT NULL DEFAULT 'unknown',
  `city`            VARCHAR(120) DEFAULT NULL,
  `state`           VARCHAR(120) DEFAULT NULL,
  `country`         VARCHAR(120) DEFAULT NULL,
  `is_suspicious`   TINYINT(1)   NOT NULL DEFAULT 0,
  `alert_sent`      TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ulh_user`    (`user_id`),
  KEY `idx_ulh_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Trusted devices: fingerprints the user/admin has confirmed as safe
CREATE TABLE IF NOT EXISTS `trusted_user_devices` (
  `id`                 INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`            INT(11)      NOT NULL,
  `role`               ENUM('student','tutor','admin') NOT NULL DEFAULT 'student',
  `device_fingerprint` VARCHAR(64)  NOT NULL DEFAULT '',
  `ip_address`         VARCHAR(45)  DEFAULT NULL,
  `browser_name`       VARCHAR(100) DEFAULT NULL,
  `os_name`            VARCHAR(100) DEFAULT NULL,
  `city`               VARCHAR(120) DEFAULT NULL,
  `country`            VARCHAR(120) DEFAULT NULL,
  `trusted_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status`             ENUM('active','revoked') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_tud_user` (`user_id`),
  UNIQUE KEY `uq_tud_fp_user` (`user_id`, `device_fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Security alert logs: one row per alert email, tracks token & user response
CREATE TABLE IF NOT EXISTS `security_alert_logs` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)      NOT NULL,
  `role`             ENUM('student','tutor','admin') NOT NULL DEFAULT 'student',
  `email`            VARCHAR(191) NOT NULL DEFAULT '',
  `alert_type`       VARCHAR(60)  NOT NULL DEFAULT 'new_device',
  `login_history_id` INT(11)      DEFAULT NULL,
  `token`            VARCHAR(64)  NOT NULL DEFAULT '',
  `token_expires_at` DATETIME     NOT NULL,
  `user_response`    ENUM('pending','confirmed','not_me') NOT NULL DEFAULT 'pending',
  `email_sent_status` ENUM('sent','failed','skipped') NOT NULL DEFAULT 'skipped',
  `email_error`      TEXT,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at`     DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sal_user`    (`user_id`),
  KEY `idx_sal_token`   (`token`),
  KEY `idx_sal_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
