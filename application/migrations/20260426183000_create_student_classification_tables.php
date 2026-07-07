<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_student_classification_tables extends CI_Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS student_learning_profiles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_user_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NULL,
            class_id INT UNSIGNED NULL,
            subject_interest_id INT UNSIGNED NULL,
            current_level_label VARCHAR(191) NULL,
            academic_year VARCHAR(20) NULL,
            status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_student_learning_profiles_user (student_user_id),
            KEY idx_student_learning_profiles_category (category_id),
            KEY idx_student_learning_profiles_class (class_id),
            KEY idx_student_learning_profiles_subject (subject_interest_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->safe_query("ALTER TABLE student_learning_profiles ADD CONSTRAINT fk_slp_user FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->safe_query("ALTER TABLE student_learning_profiles ADD CONSTRAINT fk_slp_category FOREIGN KEY (category_id) REFERENCES tutor_categories(id) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->safe_query("ALTER TABLE student_learning_profiles ADD CONSTRAINT fk_slp_class FOREIGN KEY (class_id) REFERENCES tutor_classes(id) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->safe_query("ALTER TABLE student_learning_profiles ADD CONSTRAINT fk_slp_subject FOREIGN KEY (subject_interest_id) REFERENCES tutor_subject_master(id) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS student_learning_profiles");
    }

    private function safe_query($sql)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $this->db->query($sql);
        } catch (Throwable $exception) {
            log_message('error', 'Optional student classification constraint skipped: ' . $exception->getMessage());
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }
}
