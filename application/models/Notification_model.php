<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model
{
    private $allowed_reference_types = ['assignment', 'test', 'session', 'recording', 'general'];

    public function create_notification(array $data): bool
    {
        $user_id = (int)($data['user_id'] ?? 0);

        if ($user_id <= 0 || trim((string)($data['title'] ?? '')) === '') {
            return false;
        }

        $insert = [
            'user_id' => $user_id,
            'batch_id' => !empty($data['batch_id']) ? (int)$data['batch_id'] : null,
            'reference_id' => !empty($data['reference_id']) ? (int)$data['reference_id'] : null,
            'reference_type' => $this->sanitize_reference_type($data['reference_type'] ?? 'general'),
            'title' => mb_substr(trim((string)$data['title']), 0, 255),
            'message' => trim((string)($data['message'] ?? '')),
            'target_url' => $this->sanitize_target_url((string)($data['target_url'] ?? '')),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('lms_notifications', $insert);
    }

    public function create_bulk_notifications(array $user_ids, array $data): int
    {
        if (empty($user_ids) || trim((string)($data['title'] ?? '')) === '') {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $rows = [];
        $reference_type = $this->sanitize_reference_type($data['reference_type'] ?? 'general');
        $title = mb_substr(trim((string)$data['title']), 0, 255);
        $message = trim((string)($data['message'] ?? ''));
        $target_url = $this->sanitize_target_url((string)($data['target_url'] ?? ''));

        foreach (array_unique(array_map('intval', $user_ids)) as $user_id) {
            if ($user_id <= 0) {
                continue;
            }

            $rows[] = [
                'user_id' => $user_id,
                'batch_id' => !empty($data['batch_id']) ? (int)$data['batch_id'] : null,
                'reference_id' => !empty($data['reference_id']) ? (int)$data['reference_id'] : null,
                'reference_type' => $reference_type,
                'title' => $title,
                'message' => $message,
                'target_url' => $target_url,
                'is_read' => 0,
                'created_at' => $now
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        $this->db->insert_batch('lms_notifications', $rows);
        return max(0, (int)$this->db->affected_rows());
    }

    public function get_user_notifications(int $user_id, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));

        return $this->db
            ->where('user_id', $user_id)
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get('lms_notifications')
            ->result_array();
    }

    public function get_unread_count(int $user_id): int
    {
        return (int)$this->db
            ->where('user_id', $user_id)
            ->where('is_read', 0)
            ->count_all_results('lms_notifications');
    }

    public function mark_as_read(int $notification_id, int $user_id): array
    {
        $notification = $this->db
            ->where('id', $notification_id)
            ->where('user_id', $user_id)
            ->get('lms_notifications')
            ->row_array();

        if (empty($notification)) {
            return [];
        }

        if ((int)$notification['is_read'] === 0) {
            $this->db
                ->where('id', $notification_id)
                ->where('user_id', $user_id)
                ->update('lms_notifications', [
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ]);
        }

        return $notification;
    }

    public function mark_all_read(int $user_id): bool
    {
        return $this->db
            ->where('user_id', $user_id)
            ->where('is_read', 0)
            ->update('lms_notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
    }

    private function sanitize_reference_type(string $type): string
    {
        return in_array($type, $this->allowed_reference_types, true) ? $type : 'general';
    }

    private function sanitize_target_url(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (strpos($url, site_url()) === 0 || strpos($url, base_url()) === 0) {
            return $url;
        }

        if (preg_match('#^/?[a-zA-Z0-9_./-]+$#', $url)) {
            return site_url(ltrim($url, '/'));
        }

        return '';
    }
}
