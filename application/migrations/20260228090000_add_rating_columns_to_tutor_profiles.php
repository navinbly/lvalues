<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_rating_columns_to_tutor_profiles extends CI_Migration
{
    public function up()
    {
        // Keep this migration safe if columns already exist.
        if (!$this->db->field_exists('avg_rating', 'tutor_profiles')) {
            $this->db->query("ALTER TABLE tutor_profiles ADD COLUMN avg_rating DECIMAL(3,1) NOT NULL DEFAULT 0 AFTER hourly_fee");
        }
        if (!$this->db->field_exists('rating_count', 'tutor_profiles')) {
            $this->db->query("ALTER TABLE tutor_profiles ADD COLUMN rating_count INT NOT NULL DEFAULT 0 AFTER avg_rating");
        }

        // Helpful indexes for search
        // (Ignore errors if index already exists)
        @$this->db->query("CREATE INDEX idx_tutor_profiles_city ON tutor_profiles(city)");
        @$this->db->query("CREATE INDEX idx_tutor_profiles_pincode ON tutor_profiles(pincode)");
        @$this->db->query("CREATE INDEX idx_tutor_profiles_mode ON tutor_profiles(teaching_mode)");
        @$this->db->query("CREATE INDEX idx_tutor_profiles_rating ON tutor_profiles(avg_rating)");
        @$this->db->query("CREATE INDEX idx_tutor_profiles_fee ON tutor_profiles(hourly_fee)");
    }

    public function down()
    {
        // Down migrations are optional here (keeping existing data safer).
    }
}
