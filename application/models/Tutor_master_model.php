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

    public function normalize_ids($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
