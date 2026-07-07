<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_search_model extends CI_Model
{
    public function count_filtered(array $f): int
    {
        $this->_build_base_query($f, false);
        return (int) $this->db->count_all_results();
    }

    public function get_filtered(array $f, int $limit = 9, int $offset = 0): array
    {
        $this->_build_base_query($f, true);

        $sort = $f['sort_by'] ?? 'best_match';
        if ($sort === 'fee_low') {
            $this->db->order_by('tp.hourly_fee', 'ASC');
        } elseif ($sort === 'fee_high') {
            $this->db->order_by('tp.hourly_fee', 'DESC');
        } elseif ($sort === 'rating') {
            $this->db->order_by('avg_rating', 'DESC');
        } else {
            $this->db->order_by('avg_rating', 'DESC');
            if (!empty($f['lat']) && !empty($f['lng'])) {
                $this->db->order_by('distance_km', 'ASC');
            }
        }

        $this->db->limit($limit, $offset);
        return $this->db->get()->result_array();
    }

    private function _build_base_query(array $f, bool $selectDistance): void
    {
        $lat = isset($f['lat']) && is_numeric($f['lat']) ? (float)$f['lat'] : null;
        $lng = isset($f['lng']) && is_numeric($f['lng']) ? (float)$f['lng'] : null;

        $select = [
            'tp.id AS tutor_profile_id',
            'tp.user_id AS tutor_user_id',
            'tp.headline',
            'tp.qualification',
            'tp.experience_years',
            'tp.teaching_mode',
            'tp.city',
            'tp.state',
            'tp.country',
            'tp.pincode',
            'tp.lat',
            'tp.lng',
            'tp.hourly_fee',
            'tp.profile_photo',
            'tp.status AS tutor_profile_status',
            'IFNULL(tp.avg_rating, 0) AS avg_rating',
            'IFNULL(tp.rating_count, 0) AS rating_count',
            'u.first_name',
            'u.last_name',
            'u.email',
            'GROUP_CONCAT(DISTINCT tsm.name ORDER BY tsm.name SEPARATOR ", ") AS subject_names'
        ];

        if ($selectDistance && $lat !== null && $lng !== null) {
            $select[] = "(6371 * ACOS( COS(RADIANS({$lat})) * COS(RADIANS(tp.lat)) * COS(RADIANS(tp.lng) - RADIANS({$lng})) + SIN(RADIANS({$lat})) * SIN(RADIANS(tp.lat)) )) AS distance_km";
        } else {
            $select[] = 'NULL AS distance_km';
        }

        $this->db->select(implode(',', $select), false)
            ->from('tutor_profiles tp')
            ->join('users u', 'u.id = tp.user_id', 'left')
            ->join('tutor_subjects ts', 'ts.tutor_profile_id = tp.id', 'left')
            ->join('tutor_categories tc', 'tc.id = ts.category_id', 'left')
            ->join('tutor_classes tcl', 'tcl.id = ts.class_id', 'left')
            ->join('tutor_subject_master tsm', 'tsm.id = ts.subject_id', 'left')
            ->where('tp.status', 'active');

        $q = trim((string)($f['query'] ?? ''));
        if ($q !== '') {
            $this->db->group_start()
                ->like('u.first_name', $q)
                ->or_like('u.last_name', $q)
                ->or_like('tp.headline', $q)
                ->or_like('tp.qualification', $q)
                ->or_like('tp.city', $q)
                ->or_like('tp.pincode', $q)
                ->or_like('tsm.name', $q)
                ->group_end();
        }

        if (!empty($f['category_id'])) {
            $this->db->where('ts.category_id', (int)$f['category_id']);
        }
        if (!empty($f['class_id'])) {
            $this->db->where('ts.class_id', (int)$f['class_id']);
        }
        if (!empty($f['subject_id'])) {
            $this->db->where('ts.subject_id', (int)$f['subject_id']);
        }

        $mode = $f['mode'] ?? 'all';
        if ($mode === 'online') {
            $this->db->group_start()->where('tp.teaching_mode', 'online')->or_where('tp.teaching_mode', 'both')->group_end();
        } elseif ($mode === 'offline') {
            $this->db->group_start()->where('tp.teaching_mode', 'offline')->or_where('tp.teaching_mode', 'both')->group_end();
        } elseif ($mode === 'both') {
            $this->db->where('tp.teaching_mode', 'both');
        }

        $loc = trim((string)($f['location'] ?? ''));
        if ($loc !== '') {
            $this->db->group_start()
                ->like('tp.city', $loc)
                ->or_like('tp.state', $loc)
                ->or_like('tp.pincode', $loc)
                ->or_like('tp.country', $loc)
                ->group_end();
        }

        $min_rating = isset($f['min_rating']) ? (float)$f['min_rating'] : 0;
        if ($min_rating > 0) {
            $this->db->where('IFNULL(tp.avg_rating, 0) >=', $min_rating, false);
        }
        if (isset($f['fee_min']) && is_numeric($f['fee_min'])) {
            $this->db->where('tp.hourly_fee >=', (float)$f['fee_min']);
        }
        if (isset($f['fee_max']) && is_numeric($f['fee_max'])) {
            $this->db->where('tp.hourly_fee <=', (float)$f['fee_max']);
        }

        $distance_km = isset($f['distance_km']) ? (int)$f['distance_km'] : 0;
        if ($lat !== null && $lng !== null && $distance_km > 0) {
            $this->db->where('tp.lat IS NOT NULL', null, false)
                ->where('tp.lng IS NOT NULL', null, false)
                ->having('distance_km <=', $distance_km);
        }

        $this->db->group_by('tp.id');
    }
}
