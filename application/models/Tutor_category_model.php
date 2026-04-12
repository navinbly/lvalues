<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_category_model extends CI_Model
{
    private $table = 'category';

    public function __construct()
    {
        parent::__construct();
    }

    private function normalize_parent_value($value): int
    {
        if ($value === null) {
            return 0;
        }

        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === '0' || $value === 0) {
            return 0;
        }

        return (int)$value;
    }

    public function get_all_categories(): array
    {
        return $this->db
            ->select('id, name, slug, parent')
            ->from($this->table)
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_root_categories(): array
    {
        return $this->db
            ->select('id, name, slug, parent')
            ->from($this->table)
            ->group_start()
                ->where('parent', 0)
                ->or_where('parent', '0')
                ->or_where('parent', '')
                ->or_where('parent IS NULL', null, false)
            ->group_end()
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_children(int $parent_id): array
    {
        return $this->db
            ->select('id, name, slug, parent')
            ->from($this->table)
            ->group_start()
                ->where('parent', $parent_id)
                ->or_where('parent', (string)$parent_id)
            ->group_end()
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_registration_tree(): array
    {
        $rows = $this->get_all_categories();
        if (empty($rows)) {
            return [];
        }

        $by_parent = [];
        foreach ($rows as $row) {
            $parent = $this->normalize_parent_value($row['parent'] ?? 0);
            $row['id'] = (string)$row['id'];
            $row['name'] = (string)$row['name'];
            $row['slug'] = (string)($row['slug'] ?? '');
            $row['parent'] = $parent;
            $by_parent[$parent][] = $row;
        }

        $tree = [];
        $roots = $by_parent[0] ?? [];

        foreach ($roots as $root) {
            $classes = [];
            $level2Rows = $by_parent[(int)$root['id']] ?? [];

            foreach ($level2Rows as $level2) {
                $subjectRows = $by_parent[(int)$level2['id']] ?? [];

                if (empty($subjectRows)) {
                    continue;
                }

                $subjects = [];
                foreach ($subjectRows as $subject) {
                    $subjects[] = [
                        'id'   => (string)$subject['id'],
                        'name' => $subject['name'],
                        'slug' => $subject['slug'],
                    ];
                }

                $classes[] = [
                    'id'       => (string)$level2['id'],
                    'name'     => $level2['name'],
                    'slug'     => $level2['slug'],
                    'subjects' => $subjects,
                ];
            }

            if (!empty($classes)) {
                $tree[] = [
                    'id'      => (string)$root['id'],
                    'name'    => $root['name'],
                    'slug'    => $root['slug'],
                    'classes' => $classes,
                ];
            }
        }

        return $tree;
    }

    public function get_classes_by_category_ids(array $category_ids): array
    {
        $category_ids = $this->normalize_ids($category_ids);
        if (empty($category_ids)) {
            return [];
        }

        $string_ids = array_map('strval', $category_ids);

        return $this->db
            ->select('id, name, slug, parent')
            ->from($this->table)
            ->group_start()
                ->where_in('parent', $category_ids)
                ->or_where_in('parent', $string_ids)
            ->group_end()
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_subjects_by_class_ids(array $class_ids): array
    {
        $class_ids = $this->normalize_ids($class_ids);
        if (empty($class_ids)) {
            return [];
        }

        $string_ids = array_map('strval', $class_ids);

        return $this->db
            ->select('id, name, slug, parent')
            ->from($this->table)
            ->group_start()
                ->where_in('parent', $class_ids)
                ->or_where_in('parent', $string_ids)
            ->group_end()
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    public function subject_ids_belong_to_classes(array $subject_ids, array $class_ids): bool
    {
        $subject_ids = $this->normalize_ids($subject_ids);
        $class_ids   = $this->normalize_ids($class_ids);
        if (empty($subject_ids) || empty($class_ids)) {
            return false;
        }

        $class_string_ids = array_map('strval', $class_ids);

        $count = $this->db
            ->from($this->table)
            ->where_in('id', $subject_ids)
            ->group_start()
                ->where_in('parent', $class_ids)
                ->or_where_in('parent', $class_string_ids)
            ->group_end()
            ->count_all_results();

        return $count === count($subject_ids);
    }

    public function class_ids_belong_to_categories(array $class_ids, array $category_ids): bool
    {
        $class_ids    = $this->normalize_ids($class_ids);
        $category_ids = $this->normalize_ids($category_ids);
        if (empty($class_ids) || empty($category_ids)) {
            return false;
        }

        $category_string_ids = array_map('strval', $category_ids);

        $count = $this->db
            ->from($this->table)
            ->where_in('id', $class_ids)
            ->group_start()
                ->where_in('parent', $category_ids)
                ->or_where_in('parent', $category_string_ids)
            ->group_end()
            ->count_all_results();

        return $count === count($class_ids);
    }

    public function normalize_ids($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
