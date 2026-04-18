<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_master_model extends CI_Model
{
    public function get_categories($only_active = true): array
    {
        $this->db->select('id, name, slug, sort_order')
            ->from('tutor_categories');
        if ($only_active && $this->db->field_exists('status', 'tutor_categories')) {
            $this->db->where('status', 1);
        }
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get()->result_array();
    }

    public function get_classes_by_category_ids(array $category_ids): array
    {
        $category_ids = $this->normalize_ids($category_ids);
        if (empty($category_ids)) {
            return [];
        }
        $this->db->select('id, category_id, name, slug, sort_order')
            ->from('tutor_classes')
            ->where_in('category_id', $category_ids);
        if ($this->db->field_exists('status', 'tutor_classes')) {
            $this->db->where('status', 1);
        }
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get()->result_array();
    }

    public function get_subjects_by_class_ids(array $class_ids): array
    {
        $class_ids = $this->normalize_ids($class_ids);
        if (empty($class_ids)) {
            return [];
        }
        $this->db->select('id, class_id, name, slug, sort_order')
            ->from('tutor_subject_master')
            ->where_in('class_id', $class_ids);
        if ($this->db->field_exists('status', 'tutor_subject_master')) {
            $this->db->where('status', 1);
        }
        return $this->db->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get()->result_array();
    }

    public function get_registration_tree(): array
    {
        $tree = [];
        foreach ($this->get_categories() as $category) {
            $classes = [];
            foreach ($this->get_classes_by_category_ids([$category['id']]) as $class) {
                $subjects = $this->get_subjects_by_class_ids([$class['id']]);
                $classes[] = [
                    'id' => (string)$class['id'],
                    'name' => $class['name'],
                    'slug' => $class['slug'],
                    'subjects' => array_map(function ($s) {
                        return [
                            'id' => (string)$s['id'],
                            'name' => $s['name'],
                            'slug' => $s['slug'],
                        ];
                    }, $subjects),
                ];
            }
            $tree[] = [
                'id' => (string)$category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'classes' => $classes,
            ];
        }
        return $tree;
    }

    public function class_ids_belong_to_categories(array $class_ids, array $category_ids): bool
    {
        $class_ids = $this->normalize_ids($class_ids);
        $category_ids = $this->normalize_ids($category_ids);
        if (empty($class_ids) || empty($category_ids)) {
            return false;
        }
        $count = (int) $this->db->from('tutor_classes')
            ->where_in('id', $class_ids)
            ->where_in('category_id', $category_ids)
            ->count_all_results();
        return $count === count($class_ids);
    }

    public function subject_ids_belong_to_classes(array $subject_ids, array $class_ids): bool
    {
        $subject_ids = $this->normalize_ids($subject_ids);
        $class_ids = $this->normalize_ids($class_ids);
        if (empty($subject_ids) || empty($class_ids)) {
            return false;
        }
        $count = (int) $this->db->from('tutor_subject_master')
            ->where_in('id', $subject_ids)
            ->where_in('class_id', $class_ids)
            ->count_all_results();
        return $count === count($subject_ids);
    }

    public function get_subject_detail_map(array $subject_ids): array
    {
        $subject_ids = $this->normalize_ids($subject_ids);
        if (empty($subject_ids)) {
            return [];
        }
        $rows = $this->db->select('s.id AS subject_id, s.name AS subject_name, s.class_id, c.name AS class_name, c.category_id, tc.name AS category_name')
            ->from('tutor_subject_master s')
            ->join('tutor_classes c', 'c.id = s.class_id', 'inner')
            ->join('tutor_categories tc', 'tc.id = c.category_id', 'inner')
            ->where_in('s.id', $subject_ids)
            ->get()->result_array();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['subject_id']] = $row;
        }
        return $map;
    }

    public function get_category_id_by_slug(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $row = $this->db->get_where('tutor_categories', ['slug' => $slug], 1)->row_array();
        return $row ? (int)$row['id'] : null;
    }

    public function get_class_id_by_slug(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $row = $this->db->get_where('tutor_classes', ['slug' => $slug], 1)->row_array();
        return $row ? (int)$row['id'] : null;
    }

    public function get_subject_id_by_slug(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $row = $this->db->get_where('tutor_subject_master', ['slug' => $slug], 1)->row_array();
        return $row ? (int)$row['id'] : null;
    }


    public function get_tutor_profile_by_user_id(int $user_id): array
    {
        $row = $this->db->get_where('tutor_profiles', ['user_id' => $user_id], 1)->row_array();
        return is_array($row) ? $row : [];
    }

    public function get_selected_subject_ids_by_user_id(int $user_id): array
    {
        $profile = $this->get_tutor_profile_by_user_id($user_id);
        if (empty($profile['id'])) {
            return [];
        }
        $rows = $this->db->select('subject_id')->from('tutor_subjects')->where('tutor_profile_id', (int)$profile['id'])->where('subject_id IS NOT NULL', null, false)->get()->result_array();
        return array_values(array_unique(array_map('intval', array_column($rows, 'subject_id'))));
    }

    public function get_selected_class_ids_by_user_id(int $user_id): array
    {
        $profile = $this->get_tutor_profile_by_user_id($user_id);
        if (empty($profile['id'])) {
            return [];
        }
        $rows = $this->db->select('class_id')->from('tutor_subjects')->where('tutor_profile_id', (int)$profile['id'])->where('class_id IS NOT NULL', null, false)->get()->result_array();
        return array_values(array_unique(array_map('intval', array_column($rows, 'class_id'))));
    }

    public function get_selected_category_ids_by_user_id(int $user_id): array
    {
        $profile = $this->get_tutor_profile_by_user_id($user_id);
        if (empty($profile['id'])) {
            return [];
        }
        $rows = $this->db->select('category_id')->from('tutor_subjects')->where('tutor_profile_id', (int)$profile['id'])->where('category_id IS NOT NULL', null, false)->get()->result_array();
        return array_values(array_unique(array_map('intval', array_column($rows, 'category_id'))));
    }

    public function save_tutor_teaching_profile(int $user_id, array $payload): array
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return ['status' => false, 'message' => 'Invalid tutor profile update request.'];
        }

        $category_ids = $this->normalize_ids($payload['tutor_category_ids'] ?? []);
        $class_ids = $this->normalize_ids($payload['tutor_class_ids'] ?? []);
        $subject_ids = $this->normalize_ids($payload['tutor_subject_ids'] ?? []);

        if (empty($subject_ids)) {
            return ['status' => false, 'message' => 'Please select at least one subject you teach.'];
        }

        if (!empty($category_ids) && !empty($class_ids) && !$this->class_ids_belong_to_categories($class_ids, $category_ids)) {
            return ['status' => false, 'message' => 'Selected classes do not belong to the selected categories.'];
        }

        if (!empty($class_ids) && !$this->subject_ids_belong_to_classes($subject_ids, $class_ids)) {
            return ['status' => false, 'message' => 'Selected subjects do not belong to the selected classes.'];
        }

        $subject_rows = $this->db->select('s.id AS subject_id, s.class_id, c.category_id')
            ->from('tutor_subject_master s')
            ->join('tutor_classes c', 'c.id = s.class_id', 'inner')
            ->where_in('s.id', $subject_ids)
            ->get()->result_array();

        if (count($subject_rows) !== count($subject_ids)) {
            return ['status' => false, 'message' => 'One or more selected subjects are invalid.'];
        }

        $derived_category_ids = [];
        $derived_class_ids = [];
        $subject_map = [];
        foreach ($subject_rows as $row) {
            $sid = (int)$row['subject_id'];
            $cid = (int)$row['class_id'];
            $cat = (int)$row['category_id'];
            $subject_map[$sid] = ['class_id' => $cid, 'category_id' => $cat];
            $derived_class_ids[] = $cid;
            $derived_category_ids[] = $cat;
        }
        $derived_class_ids = array_values(array_unique($derived_class_ids));
        $derived_category_ids = array_values(array_unique($derived_category_ids));

        $teaching_mode = strtolower(trim((string)($payload['tutor_teaching_mode'] ?? 'both')));
        if (!in_array($teaching_mode, ['online', 'offline', 'both'], true)) {
            $teaching_mode = 'both';
        }

        $profile_data = [
            'headline' => html_escape(trim((string)($payload['tutor_headline'] ?? ''))),
            'qualification' => html_escape(trim((string)($payload['tutor_qualification'] ?? ''))),
            'experience_years' => is_numeric($payload['tutor_experience_years'] ?? null) ? (int)$payload['tutor_experience_years'] : null,
            'teaching_mode' => $teaching_mode,
            'hourly_fee' => is_numeric($payload['tutor_hourly_fee'] ?? null) ? (float)$payload['tutor_hourly_fee'] : null,
            'city' => html_escape(trim((string)($payload['tutor_city'] ?? ''))),
            'state' => html_escape(trim((string)($payload['tutor_state'] ?? ''))),
            'country' => html_escape(trim((string)($payload['tutor_country'] ?? ''))),
            'pincode' => html_escape(trim((string)($payload['tutor_pincode'] ?? ''))),
            'bio' => trim((string)($payload['tutor_bio'] ?? '')),
        ];

        foreach (['headline','qualification','city','state','country','pincode','bio'] as $k) {
            if ($profile_data[$k] === '') {
                $profile_data[$k] = null;
            }
        }
        $profile_data['lat'] = is_numeric($payload['tutor_lat'] ?? null) ? (float)$payload['tutor_lat'] : null;
        $profile_data['lng'] = is_numeric($payload['tutor_lng'] ?? null) ? (float)$payload['tutor_lng'] : null;

        $existing = $this->get_tutor_profile_by_user_id($user_id);
        if (!empty($existing)) {
            if (!isset($profile_data['status']) && !empty($existing['status'])) {
                $profile_data['status'] = $existing['status'];
            }
            $profile_data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('user_id', $user_id)->update('tutor_profiles', $profile_data);
            $tutor_profile_id = (int)$existing['id'];
        } else {
            $profile_data['user_id'] = $user_id;
            $profile_data['status'] = 'active';
            $profile_data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('tutor_profiles', $profile_data);
            $tutor_profile_id = (int)$this->db->insert_id();
        }

        if ($tutor_profile_id <= 0) {
            return ['status' => false, 'message' => 'Unable to save tutor teaching profile right now.'];
        }

        $this->db->where('tutor_profile_id', $tutor_profile_id)->delete('tutor_subjects');
        foreach ($subject_ids as $subject_id) {
            $this->db->insert('tutor_subjects', [
                'tutor_profile_id' => $tutor_profile_id,
                'category_id' => $subject_map[$subject_id]['category_id'] ?? null,
                'class_id' => $subject_map[$subject_id]['class_id'] ?? null,
                'subject_id' => $subject_id,
            ]);
        }

        return [
            'status' => true,
            'message' => 'Your teaching profile has been updated successfully.',
            'tutor_profile_id' => $tutor_profile_id,
            'category_ids' => $derived_category_ids,
            'class_ids' => $derived_class_ids,
            'subject_ids' => $subject_ids,
        ];
    }

    public function normalize_ids($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
