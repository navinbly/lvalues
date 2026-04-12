<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_session_model extends CI_Model
{
    public function get_upcoming_published($limit = 10)
    {
        $now = date('Y-m-d H:i:s');

        $this->db->select("
            ts.id,
            ts.title,
            ts.mode,
            ts.location_text,
            ts.start_at,
            ts.end_at,
            ts.capacity,
            u.id AS tutor_user_id,
            u.first_name AS tutor_first_name,
            u.last_name AS tutor_last_name
        ");
        $this->db->from('tutor_sessions ts');
        $this->db->join('users u', 'u.id = ts.tutor_user_id', 'left');
        $this->db->where('ts.status', 'published');
        $this->db->where('ts.start_at >=', $now);
        $this->db->order_by('ts.start_at', 'ASC');
        $this->db->limit((int)$limit);

        return $this->db->get()->result_array();
    }
}
