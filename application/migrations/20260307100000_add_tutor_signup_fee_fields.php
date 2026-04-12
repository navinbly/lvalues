<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_tutor_signup_fee_fields extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('tutor_profiles')) {
            if (!$this->db->field_exists('fee_type', 'tutor_profiles')) {
                $this->db->query("ALTER TABLE tutor_profiles ADD COLUMN fee_type ENUM('per_hour','per_subject') NOT NULL DEFAULT 'per_hour' AFTER hourly_fee");
            }
            if (!$this->db->field_exists('subject_fees_json', 'tutor_profiles')) {
                $this->db->query("ALTER TABLE tutor_profiles ADD COLUMN subject_fees_json LONGTEXT NULL AFTER fee_type");
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists('tutor_profiles')) {
            if ($this->db->field_exists('subject_fees_json', 'tutor_profiles')) {
                $this->db->query("ALTER TABLE tutor_profiles DROP COLUMN subject_fees_json");
            }
            if ($this->db->field_exists('fee_type', 'tutor_profiles')) {
                $this->db->query("ALTER TABLE tutor_profiles DROP COLUMN fee_type");
            }
        }
    }
}
