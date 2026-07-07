<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sponsored_course_model extends CI_Model
{
    public function get_all(): array
    {
        if (!$this->db->table_exists('sponsored_courses')) {
            return [];
        }

        return $this->db
            ->order_by('display_order', 'ASC')
            ->order_by('id', 'DESC')
            ->get('sponsored_courses')
            ->result_array();
    }

    public function get_published(int $limit = 6): array
    {
        if (!$this->db->table_exists('sponsored_courses')) {
            return [];
        }

        $today = date('Y-m-d');
        $this->db
            ->where('status', 'published')
            ->group_start()
                ->where('start_date IS NULL', null, false)
                ->or_where('start_date <=', $today)
            ->group_end()
            ->group_start()
                ->where('end_date IS NULL', null, false)
                ->or_where('end_date >=', $today)
            ->group_end()
            ->order_by('display_order', 'ASC')
            ->order_by('id', 'DESC');

        if ($limit > 0) {
            $this->db->limit($limit);
        }

        return $this->db->get('sponsored_courses')->result_array();
    }

    public function get(int $id): array
    {
        if ($id <= 0 || !$this->db->table_exists('sponsored_courses')) {
            return [];
        }

        return (array)$this->db->get_where('sponsored_courses', ['id' => $id], 1)->row_array();
    }

    public function save(array $input, int $admin_id, int $id = 0): array
    {
        $title = trim((string)($input['course_title'] ?? ''));
        $provider = trim((string)($input['provider_name'] ?? ''));
        if ($title === '' || $provider === '') {
            return ['status' => false, 'message' => 'Course title and provider name are required.'];
        }

        $status = strtolower(trim((string)($input['status'] ?? 'draft')));
        if (!in_array($status, ['draft', 'published', 'unpublished', 'archived'], true)) {
            $status = 'draft';
        }

        $mode = strtolower(trim((string)($input['mode'] ?? 'online')));
        if (!in_array($mode, ['online', 'offline', 'hybrid'], true)) {
            $mode = 'online';
        }

        $registration_link = trim((string)($input['registration_link'] ?? ''));
        if ($registration_link !== '' && !filter_var($registration_link, FILTER_VALIDATE_URL)) {
            return ['status' => false, 'message' => 'Registration link must be a valid URL.'];
        }

        $contact_email = trim((string)($input['contact_email'] ?? ''));
        if ($contact_email !== '' && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Contact email must be valid.'];
        }

        $data = [
            'course_title' => $title,
            'provider_name' => $provider,
            'course_description' => trim((string)($input['course_description'] ?? '')),
            'start_date' => $this->normalize_date($input['start_date'] ?? null),
            'end_date' => $this->normalize_date($input['end_date'] ?? null),
            'class_timing' => trim((string)($input['class_timing'] ?? '')),
            'fees' => ($input['fees'] ?? '') !== '' ? max(0, (float)$input['fees']) : null,
            'mode' => $mode,
            'location' => trim((string)($input['location'] ?? '')),
            'contact_email' => $contact_email,
            'contact_phone' => trim((string)($input['contact_phone'] ?? '')),
            'registration_link' => $registration_link,
            'status' => $status,
            'display_order' => (int)($input['display_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($input['banner_image'])) {
            $data['banner_image'] = trim((string)$input['banner_image']);
        }

        if ($id > 0) {
            $this->db->where('id', $id)->update('sponsored_courses', $data);
            return ['status' => true, 'message' => 'Sponsored course updated successfully.', 'id' => $id];
        }

        $data['created_by'] = $admin_id > 0 ? $admin_id : null;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('sponsored_courses', $data);
        return ['status' => true, 'message' => 'Sponsored course created successfully.', 'id' => (int)$this->db->insert_id()];
    }

    public function update_status(int $id, string $status): bool
    {
        if (!in_array($status, ['draft', 'published', 'unpublished', 'archived'], true)) {
            return false;
        }

        $this->db->where('id', $id)->update('sponsored_courses', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() >= 0;
    }

    private function normalize_date($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
