<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_request_model extends CI_Model
{
    private function has_pending_duplicate(int $student_user_id, int $tutor_user_id, int $subject_id = 0, string $query_text = ''): bool
    {
        $query_text = strtolower(trim($query_text));

        $this->db->from('tutor_requests');
        $this->db->where('student_user_id', $student_user_id);
        $this->db->where('tutor_user_id', $tutor_user_id);
        $this->db->where('status', 'pending');

        if ($subject_id > 0) {
            $this->db->where('subject_id', $subject_id);
        } else {
            $this->db->where('LOWER(TRIM(query_text)) =', $query_text);
        }

        return ((int) $this->db->count_all_results()) > 0;
    }

    public function create_request(int $student_user_id, array $payload): array
    {
        $tutor_user_id = (int) ($payload['tutor_user_id'] ?? 0);
        if ($student_user_id <= 0 || $tutor_user_id <= 0) {
            return ['status' => false, 'message' => 'Invalid tutor request.'];
        }
        if ($student_user_id === $tutor_user_id) {
            return ['status' => false, 'message' => 'You cannot send a request to yourself.'];
        }

        $student = $this->db->get_where('users', ['id' => $student_user_id])->row_array();
        $tutor   = $this->db->get_where('users', ['id' => $tutor_user_id])->row_array();
        if (empty($student) || empty($tutor)) {
            return ['status' => false, 'message' => 'Student or tutor account not found.'];
        }

        $tutor_profile = $this->db->get_where('tutor_profiles', [
            'id' => (int) ($payload['tutor_profile_id'] ?? 0),
            'user_id' => $tutor_user_id,
            'status' => 'active'
        ])->row_array();
        if (empty($tutor_profile)) {
            return ['status' => false, 'message' => 'Tutor profile is not active.'];
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return ['status' => false, 'message' => 'Please enter your requirement before submitting the request.'];
        }

        $preferred_mode = strtolower(trim((string) ($payload['preferred_mode'] ?? 'both')));
        if (!in_array($preferred_mode, ['online', 'offline', 'both'], true)) {
            $preferred_mode = 'both';
        }

        $category_id = (int) ($payload['category_id'] ?? 0);
        $class_id = (int) ($payload['class_id'] ?? 0);
        $subject_id = (int) ($payload['subject_id'] ?? 0);
        $query_text = trim((string) ($payload['query_text'] ?? ''));

        $subject_map = [];
        if ($subject_id > 0) {
            $this->load->model('Tutor_master_model', 'tutor_master_model');
            $subject_map = $this->tutor_master_model->get_subject_detail_map([$subject_id]);
        }
        $subject_name_snapshot = !empty($subject_map[$subject_id]['subject_name']) ? $subject_map[$subject_id]['subject_name'] : $query_text;

        if ($this->has_pending_duplicate($student_user_id, $tutor_user_id, $subject_id, $query_text)) {
            return ['status' => false, 'message' => 'You already have a pending request with this tutor for the same requirement.'];
        }

        $data = [
            'student_user_id'          => $student_user_id,
            'tutor_user_id'            => $tutor_user_id,
            'tutor_profile_id'         => (int) $tutor_profile['id'],
            'category_id'              => $category_id ?: null,
            'class_id'                 => $class_id ?: null,
            'subject_id'               => $subject_id ?: null,
            'subject_name_snapshot'    => $subject_name_snapshot,
            'query_text'               => $query_text,
            'student_name_snapshot'    => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
            'student_email_snapshot'   => (string) ($student['email'] ?? ''),
            'student_phone_snapshot'   => (string) ($student['phone'] ?? ''),
            'preferred_mode'           => $preferred_mode,
            'student_location_text'    => trim((string) ($payload['student_location'] ?? '')),
            'message'                  => $message,
            'status'                   => 'pending',
            'created_at'               => date('Y-m-d H:i:s'),
            'updated_at'               => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('tutor_requests', $data);
        $request_id = (int) $this->db->insert_id();
        if ($request_id <= 0) {
            return ['status' => false, 'message' => 'Unable to submit the request right now.'];
        }

        $this->log_status($request_id, '', 'pending', 'Student submitted the request.', $student_user_id);
        $this->create_system_notification(
            'tutor_request_received',
            $tutor_user_id,
            'New student request received',
            trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) . ' submitted a tutoring request' . ($subject_name_snapshot ? ' for ' . $subject_name_snapshot : '') . '.',
            $student_user_id
        );
        $this->create_system_notification(
            'tutor_request_submitted',
            $student_user_id,
            'Tutor request submitted',
            'Your tutoring request has been sent to ' . trim(($tutor['first_name'] ?? '') . ' ' . ($tutor['last_name'] ?? '')) . '.',
            $tutor_user_id
        );

        $request_row = $this->get_request_details($request_id);
        $this->send_new_request_email_to_tutor($request_row);

        return ['status' => true, 'message' => 'Your tutoring request has been submitted successfully.'];
    }

    public function get_incoming_requests_for_tutor(int $tutor_user_id, string $status = 'all', int $limit = 0): array
    {
        $this->db->select('tr.*, u.first_name AS student_first_name, u.last_name AS student_last_name, u.email AS student_email, u.phone AS student_phone, tp.headline AS tutor_headline, tp.profile_photo')
            ->from('tutor_requests tr')
            ->join('users u', 'u.id = tr.student_user_id', 'left')
            ->join('tutor_profiles tp', 'tp.user_id = tr.tutor_user_id', 'left')
            ->where('tr.tutor_user_id', $tutor_user_id);

        if ($status !== 'all') {
            $this->db->where('tr.status', $status);
        }

        $this->db->order_by('FIELD(tr.status, "pending", "approved", "rejected", "cancelled")', '', false);
        $this->db->order_by('tr.id', 'DESC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }
        return $this->db->get()->result_array();
    }

    public function get_student_requests(int $student_user_id): array
    {
        return $this->db->select('tr.*, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name, u.email AS tutor_email, u.phone AS tutor_phone, tp.headline AS tutor_headline, tp.profile_photo, tp.hourly_fee, tp.teaching_mode')
            ->from('tutor_requests tr')
            ->join('users u', 'u.id = tr.tutor_user_id', 'left')
            ->join('tutor_profiles tp', 'tp.user_id = tr.tutor_user_id', 'left')
            ->where('tr.student_user_id', $student_user_id)
            ->order_by('tr.id', 'DESC')
            ->get()->result_array();
    }

    public function count_pending_for_tutor(int $tutor_user_id): int
    {
        return (int) $this->db->where('tutor_user_id', $tutor_user_id)->where('status', 'pending')->count_all_results('tutor_requests');
    }

    public function respond_to_request(int $request_id, int $tutor_user_id, string $action, string $response_message = ''): array
    {
        if (!in_array($action, ['approved', 'rejected'], true)) {
            return ['status' => false, 'message' => 'Invalid request action.'];
        }

        $request = $this->db->get_where('tutor_requests', [
            'id' => $request_id,
            'tutor_user_id' => $tutor_user_id,
        ])->row_array();

        if (empty($request)) {
            return ['status' => false, 'message' => 'Request not found.'];
        }
        if ($request['status'] !== 'pending') {
            return ['status' => false, 'message' => 'Only pending requests can be updated.'];
        }

        $update = [
            'status' => $action,
            'tutor_response' => $response_message,
            'responded_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', $request_id)->update('tutor_requests', $update);
        $this->log_status($request_id, 'pending', $action, $response_message, $tutor_user_id);

        $request_row = $this->get_request_details($request_id);
        $status_text = $action === 'approved' ? 'accepted' : 'rejected';

        $this->create_system_notification(
            'tutor_request_' . $action,
            (int) $request_row['student_user_id'],
            'Tutor request ' . ucfirst($status_text),
            trim(($request_row['tutor_first_name'] ?? '') . ' ' . ($request_row['tutor_last_name'] ?? '')) . ' has ' . $status_text . ' your tutoring request' . (!empty($request_row['subject_name_snapshot']) ? ' for ' . $request_row['subject_name_snapshot'] : '') . '.',
            $tutor_user_id
        );

        $this->send_status_email_to_student($request_row, $action);
        $this->send_whatsapp_status_message($request_row, $action);

        return ['status' => true, 'message' => 'Request ' . $status_text . ' successfully.'];
    }

    public function get_request_details(int $request_id): array
    {
        return (array) $this->db->select('tr.*, s.first_name AS student_first_name, s.last_name AS student_last_name, s.email AS student_email, s.phone AS student_phone, t.first_name AS tutor_first_name, t.last_name AS tutor_last_name, t.email AS tutor_email, t.phone AS tutor_phone, tp.headline AS tutor_headline, tp.profile_photo, tp.hourly_fee, tp.teaching_mode')
            ->from('tutor_requests tr')
            ->join('users s', 's.id = tr.student_user_id', 'left')
            ->join('users t', 't.id = tr.tutor_user_id', 'left')
            ->join('tutor_profiles tp', 'tp.user_id = tr.tutor_user_id', 'left')
            ->where('tr.id', $request_id)
            ->get()->row_array();
    }

    private function create_system_notification(string $type, int $to_user, string $title, string $description, int $from_user = 0): void
    {
        $this->load->model('Email_model', 'email_model');
        $this->email_model->notify($type, $to_user, $title, $description, $from_user);
    }

    private function log_status(int $request_id, string $from_status, string $to_status, string $note, int $acted_by): void
    {
        if (!$this->db->table_exists('tutor_request_status_logs')) {
            return;
        }
        $this->db->insert('tutor_request_status_logs', [
            'request_id' => $request_id,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'note' => $note,
            'acted_by' => $acted_by,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function send_new_request_email_to_tutor(array $request): void
    {
        if (empty($request['tutor_email'])) {
            return;
        }
        $this->load->model('Email_model', 'email_model');
        $student_name = trim(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? ''));
        $subject = 'New tutoring request from ' . $student_name;
        $body = $this->wrap_email_template(
            'New tutoring request received',
            '<p>Hello ' . html_escape(trim(($request['tutor_first_name'] ?? '') . ' ' . ($request['tutor_last_name'] ?? ''))) . ',</p>' .
            '<p>You have received a new tutoring request from <strong>' . html_escape($student_name) . '</strong>.</p>' .
            '<p><strong>Requirement:</strong> ' . html_escape($request['subject_name_snapshot'] ?: $request['query_text']) . '<br>' .
            '<strong>Preferred mode:</strong> ' . html_escape(ucfirst($request['preferred_mode'])) . '<br>' .
            '<strong>Location:</strong> ' . html_escape($request['student_location_text']) . '</p>' .
            '<p><strong>Student message:</strong><br>' . nl2br(html_escape((string) $request['message'])) . '</p>' .
            '<p>Please log in to your tutor dashboard and review the request.</p>',
            site_url('user/student_requests')
        );
        $this->email_model->send_smtp_mail($body, $subject, $request['tutor_email']);
    }

    private function send_status_email_to_student(array $request, string $action): void
    {
        if (empty($request['student_email'])) {
            return;
        }
        $this->load->model('Email_model', 'email_model');
        $status_word = $action === 'approved' ? 'accepted' : 'rejected';
        $subject = 'Update on your tutoring request';
        $body = $this->wrap_email_template(
            'Your tutoring request has been ' . $status_word,
            '<p>Hello ' . html_escape(trim(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? ''))) . ',</p>' .
            '<p>Your request for <strong>' . html_escape($request['subject_name_snapshot'] ?: $request['query_text']) . '</strong> has been <strong>' . html_escape($status_word) . '</strong> by tutor <strong>' . html_escape(trim(($request['tutor_first_name'] ?? '') . ' ' . ($request['tutor_last_name'] ?? ''))) . '</strong>.</p>' .
            (!empty($request['tutor_response']) ? '<p><strong>Tutor response:</strong><br>' . nl2br(html_escape((string) $request['tutor_response'])) . '</p>' : '') .
            '<p>You can log in to your account to review the latest status of your request.</p>',
            site_url('home/my_tutor_requests')
        );
        $this->email_model->send_smtp_mail($body, $subject, $request['student_email']);
    }

    private function send_whatsapp_status_message(array $request, string $action): bool
    {
        // Honest default: no automatic WhatsApp is sent unless a valid API/provider is configured.
        // This method is intentionally a safe stub for future integration.
        return false;
    }

    private function wrap_email_template(string $heading, string $content, string $cta_url = ''): string
    {
        $button = '';
        if ($cta_url !== '') {
            $button = '<p style="margin-top:24px;"><a href="' . html_escape($cta_url) . '" style="background:#6c4df6;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;display:inline-block;">Open Dashboard</a></p>';
        }

        return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f7f8fc;padding:30px;">'
            . '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e9ecf4;border-radius:10px;overflow:hidden;">'
            . '<div style="background:#6c4df6;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">Lvalues Tutor Request Update</div>'
            . '<div style="padding:24px;color:#2b2f3a;line-height:1.7;">'
            . '<h2 style="margin-top:0;font-size:22px;color:#1d2746;">' . html_escape($heading) . '</h2>'
            . $content
            . $button
            . '<p style="margin-top:24px;color:#7b8190;font-size:13px;">This is an automated message from Lvalues. Please do not reply to this email.</p>'
            . '</div></div></div>';
    }
}
