<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Communication_model extends CI_Model
{
    public function filter_options(int $actor_id, string $role): array
    {
        $options = [
            'categories' => $this->safe_rows('tutor_categories', ['status' => 1], 'name'),
            'classes' => $this->safe_rows('tutor_classes', ['status' => 1], 'name'),
            'subjects' => $this->safe_rows('tutor_subject_master', ['status' => 1], 'name'),
            'tutors' => [],
            'batches' => [],
            'courses' => [],
        ];

        if ($role === 'admin') {
            $options['tutors'] = $this->db->select('id, first_name, last_name')->where('is_instructor', 1)->where('status', 1)->order_by('first_name')->get('users')->result_array();
            $options['batches'] = $this->db->select('id, title, tutor_user_id')->order_by('title')->get('tutor_batches')->result_array();
            $options['courses'] = $this->db->select('id, title, creator')->order_by('title')->get('course')->result_array();
        } else {
            $options['batches'] = $this->db->select('id, title, tutor_user_id')->where('tutor_user_id', $actor_id)->order_by('title')->get('tutor_batches')->result_array();
            $this->db->select('id, title, creator')->from('course');
            $this->db->group_start()->where('creator', $actor_id)->or_where("FIND_IN_SET(" . (int)$actor_id . ", user_id) >", 0, false)->group_end();
            $options['courses'] = $this->db->order_by('title')->get()->result_array();
        }

        return $options;
    }

    public function preview_audience(array $filters, int $actor_id, string $role, int $limit = 500): array
    {
        $students = $role === 'admin'
            ? $this->admin_audience($filters)
            : $this->tutor_audience($filters, $actor_id);

        $deduped = [];
        foreach ($students as $student) {
            $id = (int)($student['id'] ?? 0);
            if ($id > 0 && !isset($deduped[$id])) {
                $deduped[$id] = $student;
            }
        }

        return array_slice(array_values($deduped), 0, max(1, $limit));
    }

    private function base_student_query(): void
    {
        $this->db->distinct()
            ->select('u.id, u.first_name, u.last_name, u.email, u.phone, u.address, u.status, slp.category_id, slp.class_id, slp.subject_interest_id AS subject_id, tc.name AS category_name, tcl.name AS class_name, tsm.name AS subject_name')
            ->from('users u')
            ->join('student_learning_profiles slp', 'slp.student_user_id = u.id', 'left')
            ->join('tutor_categories tc', 'tc.id = slp.category_id', 'left')
            ->join('tutor_classes tcl', 'tcl.id = slp.class_id', 'left')
            ->join('tutor_subject_master tsm', 'tsm.id = slp.subject_interest_id', 'left')
            ->where('u.is_instructor', 0);
    }

    private function admin_audience(array $filters): array
    {
        $this->base_student_query();
        $this->apply_common_filters($filters);

        if (!empty($filters['tutor_id'])) {
            $tutor_id = (int)$filters['tutor_id'];
            $this->db->where("(EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$tutor_id}) OR EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND b.tutor_user_id={$tutor_id}))", null, false);
        }
        $this->apply_batch_course_payment_filters($filters);
        return $this->db->order_by('u.first_name')->get()->result_array();
    }

    private function tutor_audience(array $filters, int $actor_id): array
    {
        $this->base_student_query();
        $this->db->where(
            "(EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id})"
            . " OR EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND b.tutor_user_id={$actor_id} AND bs.membership_status IN ('active','completed'))"
            . " OR EXISTS (SELECT 1 FROM enrol e JOIN course c ON c.id=e.course_id WHERE e.user_id=u.id AND (c.creator={$actor_id} OR FIND_IN_SET({$actor_id},c.user_id)>0)))",
            null,
            false
        );
        $this->apply_common_filters($filters);
        if (!empty($filters['relationship'])) {
            if ($filters['relationship'] === 'accepted') {
                $this->db->where("EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id} AND tr.status='approved')", null, false);
            } elseif ($filters['relationship'] === 'requested') {
                $this->db->where("EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id})", null, false);
            }
        }
        $this->apply_batch_course_payment_filters($filters, $actor_id);
        return $this->db->order_by('u.first_name')->get()->result_array();
    }

    private function apply_common_filters(array $filters): void
    {
        foreach (['category_id' => 'slp.category_id', 'class_id' => 'slp.class_id', 'subject_id' => 'slp.subject_interest_id'] as $key => $column) {
            if (!empty($filters[$key])) {
                $this->db->where($column, (int)$filters[$key]);
            }
        }
        if (!empty($filters['city'])) {
            $this->db->like('u.address', trim((string)$filters['city']));
        }
        if (isset($filters['student_status']) && $filters['student_status'] !== '') {
            $this->db->where('u.status', (int)$filters['student_status']);
        }
    }

    private function apply_batch_course_payment_filters(array $filters, int $tutor_id = 0): void
    {
        if (!empty($filters['batch_id'])) {
            $batch_id = (int)$filters['batch_id'];
            $scope = $tutor_id > 0 ? " AND b.tutor_user_id={$tutor_id}" : '';
            $this->db->where("EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND bs.batch_id={$batch_id}{$scope})", null, false);
        }
        if (!empty($filters['course_id'])) {
            $course_id = (int)$filters['course_id'];
            $scope = $tutor_id > 0 ? " AND (c.creator={$tutor_id} OR FIND_IN_SET({$tutor_id},c.user_id)>0)" : '';
            $this->db->where("EXISTS (SELECT 1 FROM enrol e JOIN course c ON c.id=e.course_id WHERE e.user_id=u.id AND e.course_id={$course_id}{$scope})", null, false);
        }
        if (!empty($filters['payment_status'])) {
            $paid = $filters['payment_status'] === 'paid';
            $this->db->where(($paid ? '' : 'NOT ') . "EXISTS (SELECT 1 FROM payment p WHERE p.user_id=u.id)", null, false);
        }
    }

    public function create_campaign(array $data): int
    {
        if (!empty($data['event_key'])) {
            $existing = $this->db->select('id')->get_where('message_campaigns', ['event_key' => $data['event_key']], 1)->row_array();
            if ($existing) {
                return 0;
            }
        }
        $this->db->insert('message_campaigns', $data);
        return (int)$this->db->insert_id();
    }

    public function add_recipient(int $campaign_id, int $student_id, string $channel, string $address, string $subject, string $message): int
    {
        $this->db->insert('message_campaign_recipients', [
            'campaign_id' => $campaign_id,
            'student_user_id' => $student_id,
            'channel' => $channel,
            'recipient_address' => $address,
            'rendered_subject' => $subject,
            'rendered_message' => $message,
            'status' => 'pending',
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_recipient(int $id, string $status, string $error = ''): void
    {
        $data = ['status' => $status, 'error_message' => $error];
        if ($status === 'sent') {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $id)->update('message_campaign_recipients', $data);
    }

    public function complete_campaign(int $campaign_id): void
    {
        $counts = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($this->db->select('status, COUNT(*) total')->where('campaign_id', $campaign_id)->group_by('status')->get('message_campaign_recipients')->result_array() as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }
        $status = $counts['failed'] > 0 ? (($counts['sent'] > 0 || $counts['skipped'] > 0) ? 'partial' : 'failed') : 'completed';
        $this->db->where('id', $campaign_id)->update('message_campaigns', [
            'sent_count' => $counts['sent'],
            'failed_count' => $counts['failed'],
            'skipped_count' => $counts['skipped'],
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function history(int $actor_id, string $role, int $limit = 50): array
    {
        if ($role !== 'admin') {
            $this->db->where('created_by', $actor_id)->where('created_role', 'tutor');
        }
        return $this->db->order_by('id', 'DESC')->limit($limit)->get('message_campaigns')->result_array();
    }

    public function recent_delivery_logs(int $actor_id, string $role, int $limit = 100): array
    {
        $this->db
            ->select('r.channel, r.status, r.error_message, r.sent_at, r.created_at, c.title, u.first_name, u.last_name')
            ->from('message_campaign_recipients r')
            ->join('message_campaigns c', 'c.id = r.campaign_id', 'inner')
            ->join('users u', 'u.id = r.student_user_id', 'left');
        if ($role !== 'admin') {
            $this->db->where('c.created_by', $actor_id)->where('c.created_role', 'tutor');
        }
        return $this->db->order_by('r.id', 'DESC')->limit($limit)->get()->result_array();
    }

    private function safe_rows(string $table, array $where, string $order): array
    {
        if (!$this->db->table_exists($table)) {
            return [];
        }
        return $this->db->where($where)->order_by($order)->get($table)->result_array();
    }
}
