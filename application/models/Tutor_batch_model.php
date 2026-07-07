<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_batch_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('email_model');
		$this->load->model('notification_model');
    }

    /**
     * Step 8 security helper: batch access must be checked in the model too,
     * not only in controllers/views. This prevents direct URL access.
     */
    private function is_student_active_in_batch(int $batch_id, int $student_user_id): bool
    {
        if ($batch_id <= 0 || $student_user_id <= 0) {
            return false;
        }

        return (int)$this->db
            ->where('batch_id', $batch_id)
            ->where('student_user_id', $student_user_id)
            ->where_in('membership_status', ['active', 'completed'])
            ->count_all_results('tutor_batch_students') > 0;
    }

    private function marketplace_paid_access_allowed(int $tutor_user_id, int $student_user_id): bool
    {
        if ($tutor_user_id <= 0 || $student_user_id <= 0) {
            return true;
        }
        if (!$this->db->table_exists('tutor_requests') || !$this->db->table_exists('tutor_request_payments')) {
            return true;
        }

        $requests = $this->db
            ->select('id')
            ->from('tutor_requests')
            ->where('tutor_user_id', $tutor_user_id)
            ->where('student_user_id', $student_user_id)
            ->where('status', 'approved')
            ->get()
            ->result_array();

        if (empty($requests)) {
            return true;
        }

        $request_ids = array_map(static function ($request) {
            return (int)$request['id'];
        }, $requests);

        return (int)$this->db
            ->where_in('request_id', $request_ids)
            ->where('payment_status', 'paid')
            ->where_in('settlement_status', ['held_by_admin', 'ready_for_payout', 'payout_pending', 'paid_to_tutor'])
            ->count_all_results('tutor_request_payments') > 0;
    }

    private function sanitize_external_url(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private function date_parse_has_errors($errors): bool
    {
        return is_array($errors) && (((int)($errors['warning_count'] ?? 0) > 0) || ((int)($errors['error_count'] ?? 0) > 0));
    }

    /**
     * Phase A reliability helper.
     * Strictly accepts HTML date input (Y-m-d), rejects empty/invalid/0000 dates,
     * and returns a safe MySQL DATE value.
     */
    private function normalize_mysql_date($value, string $label, bool $required, ?string &$error): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                $error = $label . ' is required.';
            }
            return null;
        }

        if ($value === '0000-00-00') {
            $error = $label . ' is invalid.';
            return null;
        }

        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value || $this->date_parse_has_errors(DateTime::getLastErrors())) {
            $error = 'Please enter a valid ' . strtolower($label) . '.';
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * Phase A reliability helper.
     * Accepts HTML time input (H:i) and MySQL time (H:i:s), rejects invalid
     * placeholders, and returns a safe MySQL TIME value.
     */
    private function normalize_mysql_time($value, string $label, bool $required, ?string &$error): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                $error = $label . ' is required.';
            }
            return null;
        }

        if ($value === '0000-00-00') {
            $error = $label . ' is invalid.';
            return null;
        }

        foreach (['!H:i', '!H:i:s'] as $format) {
            $time = DateTime::createFromFormat($format, $value);
            if ($time && !$this->date_parse_has_errors(DateTime::getLastErrors())) {
                $expected = $format === '!H:i' ? $time->format('H:i') : $time->format('H:i:s');
                if ($expected === $value) {
                    return $time->format('H:i:s');
                }
            }
        }

        $error = 'Please enter a valid ' . strtolower($label) . '.';
        return null;
    }

    /**
     * Phase A reliability helper.
     * Accepts HTML datetime-local (Y-m-dTH:i) or MySQL datetime and returns a
     * safe MySQL DATETIME value.
     */
    private function normalize_mysql_datetime($value, string $label, bool $required, ?string &$error): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            if ($required) {
                $error = $label . ' is required.';
            }
            return null;
        }

        if ($value === '0000-00-00 00:00:00' || $value === '0000-00-00T00:00') {
            $error = $label . ' is invalid.';
            return null;
        }

        $formats = [
            '!Y-m-d\TH:i' => 'Y-m-d\TH:i',
            '!Y-m-d H:i' => 'Y-m-d H:i',
            '!Y-m-d H:i:s' => 'Y-m-d H:i:s',
        ];

        foreach ($formats as $format => $output_format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date && !$this->date_parse_has_errors(DateTime::getLastErrors()) && $date->format($output_format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $error = 'Please enter a valid ' . strtolower($label) . '.';
        return null;
    }

    private function get_session_start_timestamp(array $session): int
    {
        $session_date = trim((string)($session['session_date'] ?? ''));
        $start_time = trim((string)($session['start_time'] ?? ''));

        if ($session_date !== '' && $start_time !== '') {
            return (int)strtotime($session_date . ' ' . $start_time);
        }

        return (int)strtotime($start_time);
    }

    private function get_session_end_timestamp(array $session): int
    {
        $session_date = trim((string)($session['session_date'] ?? ''));
        $end_time = trim((string)($session['end_time'] ?? ''));

        if ($session_date !== '' && $end_time !== '') {
            return (int)strtotime($session_date . ' ' . $end_time);
        }

        return (int)strtotime($end_time);
    }

    public function get_tutor_batches(int $tutor_user_id): array
    {
        return $this->db->select('b.*, COUNT(DISTINCT s.student_user_id) AS total_students')
            ->from('tutor_batches b')
            ->join('tutor_batch_students s', 's.batch_id = b.id AND s.membership_status IN ("active","completed")', 'left')
            ->where('b.tutor_user_id', $tutor_user_id)
            ->group_by('b.id')
            ->order_by('b.id', 'DESC')
            ->get()->result_array();
    }

    public function get_batch(int $batch_id, int $tutor_user_id = 0): array
    {
        if ($batch_id <= 0) {
            return [];
        }
        $this->db->from('tutor_batches')->where('id', $batch_id);
        if ($tutor_user_id > 0) {
            $this->db->where('tutor_user_id', $tutor_user_id);
        }
        return (array) $this->db->get()->row_array();
    }

    public function save_batch(int $tutor_user_id, array $data, int $batch_id = 0): array
    {
        $old_batch = $batch_id > 0 ? $this->get_batch($batch_id, $tutor_user_id) : [];
        $date_error = null;
        $start_date = $this->normalize_mysql_date($data['start_date'] ?? '', 'Start date', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        $end_date = $this->normalize_mysql_date($data['end_date'] ?? '', 'End date', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        if ($start_date !== null && $end_date !== null && strtotime($end_date) < strtotime($start_date)) {
            return ['status' => false, 'message' => 'End date must be on or after start date.'];
        }
        if ($batch_id <= 0 && $start_date !== null && $start_date < date('Y-m-d')) {
            return ['status' => false, 'message' => 'Start date cannot be in the past.'];
        }

        $submitted_batch_code = trim((string)($data['batch_code'] ?? ''));
        $batch_code = $submitted_batch_code;
        if ($batch_code === '') {
            $batch_code = !empty($old_batch['batch_code'])
                ? trim((string)$old_batch['batch_code'])
                : $this->generate_unique_batch_code();
        }

        $payload = [
            'tutor_user_id'  => $tutor_user_id,
            'tutor_profile_id' => !empty($data['tutor_profile_id']) ? (int)$data['tutor_profile_id'] : null,
            'category_id'    => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            'class_id'       => !empty($data['class_id']) ? (int)$data['class_id'] : null,
            'subject_id'     => !empty($data['subject_id']) ? (int)$data['subject_id'] : null,
            'title'          => trim((string)($data['title'] ?? '')),
            'slug'           => url_title(trim((string)($data['title'] ?? '')), 'dash', true),
            'description'    => trim((string)($data['description'] ?? '')),
            'batch_code'     => $batch_code,
            'delivery_mode'  => in_array(($data['delivery_mode'] ?? 'online'), ['online','offline','hybrid'], true) ? $data['delivery_mode'] : 'online',
            'capacity'       => max(1, (int)($data['capacity'] ?? 1)),
            'enrollment_mode'=> in_array(($data['enrollment_mode'] ?? 'approval'), ['open','approval'], true) ? $data['enrollment_mode'] : 'approval',
            'waitlist_enabled'=> !empty($data['waitlist_enabled']) ? 1 : 0,
            'price'          => is_numeric($data['price'] ?? null) ? (float)$data['price'] : null,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'status'         => in_array(($data['status'] ?? 'draft'), ['draft','published','completed','cancelled','archived'], true) ? $data['status'] : 'draft',
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($payload['title'] === '') {
            return ['status' => false, 'message' => 'Batch title is required.'];
        }

        if ($batch_id > 0) {
            $this->db->where('id', $batch_id)->where('tutor_user_id', $tutor_user_id)->update('tutor_batches', $payload);
            $event = (($old_batch['status'] ?? '') !== 'published' && $payload['status'] === 'published') ? 'batch_published' : 'batch_updated';
            $this->trigger_batch_communication($batch_id, $event, 0, $old_batch);
            return ['status' => true, 'message' => 'Batch updated successfully.', 'batch_id' => $batch_id];
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        if ($payload['status'] === 'published') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert('tutor_batches', $payload);
        $new_id = (int)$this->db->insert_id();
        if ($new_id > 0 && $payload['status'] === 'published') {
            $this->trigger_batch_communication($new_id, 'batch_published');
        }
        return ['status' => $new_id > 0, 'message' => $new_id > 0 ? 'Batch created successfully.' : 'Unable to create batch.', 'batch_id' => $new_id];
    }

    public function get_batch_students(int $batch_id): array
    {
        return $this->db->select('bs.*, u.first_name, u.last_name, u.email, u.phone')
            ->from('tutor_batch_students bs')
            ->join('users u', 'u.id = bs.student_user_id', 'left')
            ->where('bs.batch_id', $batch_id)
            ->order_by('bs.id', 'DESC')
            ->get()->result_array();
    }

    /**
     * Phase B recipient scoping helper.
     * Assignment/test recipients must come from accepted/enrolled batch members,
     * not from the wider invite/discovery student list.
     */
    public function get_enrolled_students_for_batch(int $batch_id, int $tutor_user_id = 0): array
    {
        if ($batch_id <= 0) {
            return [];
        }

        $this->db
            ->distinct()
            ->select(''
                . 'u.id, u.first_name, u.last_name, u.email, u.phone, '
                . 'COALESCE(i.target_category_id, b.category_id, sls.category_id, slp.category_id) AS category_id, '
                . 'COALESCE(i.target_class_id, b.class_id, sls.class_id, slp.class_id) AS class_id, '
                . 'COALESCE(i.target_subject_id, b.subject_id, sls.subject_id, slp.subject_interest_id) AS subject_id, '
                . 'c.name AS category_name, cl.name AS class_name, sm.name AS subject_name'
            , false)
            ->from('tutor_batch_students bs')
            ->join('tutor_batches b', 'b.id = bs.batch_id', 'inner')
            ->join('users u', 'u.id = bs.student_user_id', 'inner')
            ->join(
                'tutor_batch_invites i',
                '(i.id = bs.invite_id OR (i.batch_id = bs.batch_id AND i.student_user_id = bs.student_user_id AND i.invite_status = "accepted"))',
                'left'
            )
            ->join('student_learning_subjects sls', 'sls.student_user_id = u.id AND (i.target_subject_id IS NULL OR sls.subject_id = i.target_subject_id)', 'left')
            ->join('student_learning_profiles slp', 'slp.student_user_id = u.id', 'left')
            ->join('tutor_categories c', 'c.id = COALESCE(i.target_category_id, b.category_id, sls.category_id, slp.category_id)', 'left')
            ->join('tutor_classes cl', 'cl.id = COALESCE(i.target_class_id, b.class_id, sls.class_id, slp.class_id)', 'left')
            ->join('tutor_subject_master sm', 'sm.id = COALESCE(i.target_subject_id, b.subject_id, sls.subject_id, slp.subject_interest_id)', 'left')
            ->where('bs.batch_id', $batch_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->where('u.is_instructor', 0)
            ->where('u.status', 1);

        if ($tutor_user_id > 0) {
            $this->db->where('b.tutor_user_id', $tutor_user_id);
        }

        $rows = $this->db
            ->order_by('u.first_name', 'ASC')
            ->order_by('u.last_name', 'ASC')
            ->get()
            ->result_array();

        $students = [];
        $seen = [];
        foreach ($rows as $row) {
            $student_id = (int)($row['id'] ?? 0);
            $category_id = (int)($row['category_id'] ?? 0);
            $class_id = (int)($row['class_id'] ?? 0);
            $subject_id = (int)($row['subject_id'] ?? 0);

            if ($student_id <= 0 || $category_id <= 0 || $class_id <= 0) {
                continue;
            }

            $key = $this->build_student_context_key($student_id, $category_id, $class_id, $subject_id);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $row['id'] = $student_id;
            $row['category_id'] = $category_id;
            $row['class_id'] = $class_id;
            $row['subject_id'] = $subject_id > 0 ? $subject_id : null;
            $students[] = $row;
        }

        return $students;
    }

    public function invite_student($batch_id, $tutor_user_id, $data)
    {
        $student_ids = [];
        if (!empty($data['student_ids']) && is_array($data['student_ids'])) {
            $student_ids = $data['student_ids'];
        } elseif (!empty($data['student_user_id'])) {
            $student_ids = [(int)$data['student_user_id']];
        }

        return $this->bulk_invite_students(
            (int)$batch_id,
            (int)$tutor_user_id,
            $student_ids,
            trim((string)($data['invite_message'] ?? $data['bulk_invite_message'] ?? '')),
            !empty($data['send_email']),
            !empty($data['prepare_whatsapp'])
        );
    }

	public function get_student_invites(int $student_user_id): array
	{
		$student = $this->db
			->select('email')
			->from('users')
			->where('id', $student_user_id)
			->get()
			->row_array();

		$student_email = '';
		if (!empty($student) && !empty($student['email'])) {
			$student_email = trim($student['email']);
		}

		$this->db
			->select('i.*, b.title AS batch_title, b.description AS batch_description, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
			->from('tutor_batch_invites i')
			->join('tutor_batches b', 'b.id = i.batch_id', 'inner')
			->join('users u', 'u.id = i.tutor_user_id', 'left')
			->group_start()
				->where('i.student_user_id', $student_user_id);

		if ($student_email !== '') {
			$this->db->or_where('i.invite_email', $student_email);
		}

		$this->db
			->group_end()
			->order_by('i.id', 'DESC');

		$rows = $this->db->get()->result_array();
        $deduped = [];
        foreach ($rows as $row) {
            $identity = !empty($row['student_user_id'])
                ? 'id:' . (int)$row['student_user_id']
                : 'email:' . strtolower(trim((string)$row['invite_email']));
            $key = implode(':', [
                (int)$row['batch_id'],
                $identity,
                (int)($row['target_category_id'] ?? 0),
                (int)($row['target_class_id'] ?? 0),
                (int)($row['target_subject_id'] ?? 0),
            ]);
            if (!isset($deduped[$key])) $deduped[$key] = $row;
        }
		return array_values($deduped);
	}

    public function get_invite_by_token(string $token): array
    {
        return (array)$this->db->select('i.*, b.title AS batch_title, b.description AS batch_description, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
            ->from('tutor_batch_invites i')
            ->join('tutor_batches b', 'b.id = i.batch_id', 'inner')
            ->join('users u', 'u.id = i.tutor_user_id', 'left')
            ->where('i.invite_token', $token)
            ->get()->row_array();
    }

    public function respond_invite(string $token, int $student_user_id, string $action): array
    {
        $invite = $this->get_invite_by_token($token);
        if (empty($invite)) {
            return ['status' => false, 'message' => 'Invitation not found.'];
        }
        if (!in_array($action, ['accepted', 'rejected'], true)) {
            return ['status' => false, 'message' => 'Invalid invite action.'];
        }
        $this->load->model('Idempotency_model', 'idempotency');
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $operation = ['token' => $token, 'student_user_id' => $student_user_id, 'action' => $action];
        $key = $this->idempotency->key('invitation.respond', $operation);
        $this->db->trans_begin();
        $claim = $this->idempotency->claim('invitation.respond', $key, $student_user_id, $operation);
        if (empty($claim['proceed'])) {
            $this->db->trans_rollback();
            return $claim['response'];
        }
        if ($invite['invite_status'] !== 'pending') {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'This invitation has already been updated.'];
        }

        if ($action === 'accepted' && !$this->marketplace_paid_access_allowed((int)$invite['tutor_user_id'], $student_user_id)) {
            $this->db->trans_rollback();
            return [
                'status' => false,
                'message' => 'Paid batch access is blocked until admin confirms a verified marketplace payment. Payments are intentionally disabled right now.'
            ];
        }

        $update = [
            'student_user_id' => $student_user_id,
            'invite_status' => $action,
            'responded_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->field_exists('idempotency_key', 'tutor_batch_invites')) $update['idempotency_key'] = $key;
        $this->db->where('id', (int)$invite['id'])->update('tutor_batch_invites', $update);

        if ($action === 'accepted') {
            $batch_policy = $this->db->get_where('tutor_batches', ['id' => (int)$invite['batch_id']], 1)->row_array();
            $active_count = (int)$this->db->where('batch_id', (int)$invite['batch_id'])->where('membership_status', 'active')->count_all_results('tutor_batch_students');
            if ($batch_policy && $active_count >= (int)$batch_policy['capacity']) {
                if (empty($batch_policy['waitlist_enabled'])) {
                    $this->db->trans_rollback();
                    return ['status' => false, 'message' => 'This batch is full and the waitlist is disabled.'];
                }
                $position = (int)$this->db->where('batch_id', (int)$invite['batch_id'])->count_all_results('tutor_batch_waitlist') + 1;
                $this->db->replace('tutor_batch_waitlist', [
                    'batch_id' => (int)$invite['batch_id'],
                    'student_user_id' => $student_user_id,
                    'position' => $position,
                    'status' => 'waiting',
                ]);
                $response = ['status' => true, 'message' => 'The batch is full. You have been added to the waitlist at position ' . $position . '.'];
                $this->immutable_audit->record('invitation', 'accepted_to_waitlist', 'tutor_batch_invite', (int)$invite['id'], $invite, array_merge($invite, $update), [
                    'actor_user_id' => $student_user_id, 'actor_role' => 'student', 'idempotency_key' => $key, 'waitlist_position' => $position
                ]);
                $this->idempotency->complete((int)$claim['id'], $response, 'tutor_batch_waitlist', $position);
                $this->db->trans_commit();
                return $response;
            }
            $member = $this->db->get_where('tutor_batch_students', ['batch_id' => (int)$invite['batch_id'], 'student_user_id' => $student_user_id], 1)->row_array();
            $data = [
                'batch_id' => (int)$invite['batch_id'],
                'student_user_id' => $student_user_id,
                'invite_id' => (int)$invite['id'],
                'joined_at' => date('Y-m-d H:i:s'),
                'membership_status' => 'active',
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($member) {
                $this->db->where('id', (int)$member['id'])->update('tutor_batch_students', $data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('tutor_batch_students', $data);
            }
        }

        $response = ['status' => true, 'message' => $action === 'accepted' ? 'Batch invitation accepted.' : 'Batch invitation rejected.'];
        $this->immutable_audit->record('invitation', 'respond_' . $action, 'tutor_batch_invite', (int)$invite['id'], $invite, array_merge($invite, $update), [
            'actor_user_id' => $student_user_id,
            'actor_role' => 'student',
            'idempotency_key' => $key,
        ]);
        $this->idempotency->complete((int)$claim['id'], $response, 'tutor_batch_invite', (int)$invite['id']);
        if ($this->db->trans_status()) $this->db->trans_commit(); else $this->db->trans_rollback();
        return $response;
    }

    public function save_session(int $batch_id, int $tutor_user_id, array $input): array
    {
        $batch = $this->get_batch($batch_id, $tutor_user_id);
        if (empty($batch)) {
            return ['status' => false, 'message' => 'Batch not found.'];
        }
        $allowed_session_types = ['live_class','demo_class','revision','doubt_session','assignment_discussion','test_discussion'];
        $allowed_session_statuses = ['draft','scheduled','live','completed','cancelled','archived'];
        $session_type = (string)($input['session_type'] ?? '');
        $session_status = (string)($input['session_status'] ?? '');

        if ($session_type === '' || !in_array($session_type, $allowed_session_types, true)) {
            $session_type = 'live_class';
        }
        if ($session_status === '' || !in_array($session_status, $allowed_session_statuses, true)) {
            $session_status = 'scheduled';
        }

        $date_error = null;
        $session_date = $this->normalize_mysql_date($input['session_date'] ?? '', 'Session date', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        $start_time = $this->normalize_mysql_time($input['start_time'] ?? '', 'Start time', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        $end_time = $this->normalize_mysql_time($input['end_time'] ?? '', 'End time', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        $this->load->model('Teacher_workflow_model', 'teacher_workflow');
        $conflict = $this->teacher_workflow->check_session_conflict($tutor_user_id, $session_date, $start_time, $end_time);
        if (!empty($conflict['has_conflict'])) {
            $names = array_map(function ($row) { return ($row['title'] ?? 'Session') . ' (' . substr($row['start_time'], 0, 5) . '-' . substr($row['end_time'], 0, 5) . ')'; }, $conflict['conflicts']);
            return ['status' => false, 'message' => 'Schedule conflict detected: ' . implode(', ', $names) . '.'];
        }

        $data = [
            'batch_id' => $batch_id,
            'tutor_user_id' => $tutor_user_id,
            'title' => trim((string)($input['title'] ?? '')),
            'agenda' => trim((string)($input['agenda'] ?? '')),
            'session_date' => $session_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'timezone' => trim((string)($input['timezone'] ?? 'Asia/Kolkata')),
            'session_type' => $session_type,
            'provider_type' => in_array(($input['provider_type'] ?? 'manual'), ['manual','zoom','jitsi','bbb','100ms','custom'], true) ? $input['provider_type'] : 'manual',
            'provider_meeting_id' => trim((string)($input['provider_meeting_id'] ?? '')) ?: null,
            'provider_room_code' => trim((string)($input['provider_room_code'] ?? '')) ?: null,
            'host_join_url' => trim((string)($input['host_join_url'] ?? '')) ?: null,
            'student_join_url' => trim((string)($input['student_join_url'] ?? '')) ?: null,
            'meeting_passcode' => trim((string)($input['meeting_passcode'] ?? '')) ?: null,
            'location_text' => trim((string)($input['location_text'] ?? '')) ?: null,
            'is_live_class' => !empty($input['is_live_class']) ? 1 : 0,
            'is_recording_enabled' => !empty($input['is_recording_enabled']) ? 1 : 0,
            'recording_status' => !empty($input['is_recording_enabled']) ? 'scheduled' : 'not_requested',
            'recurrence_rule' => !empty($input['recurrence_frequency']) ? strtoupper((string)$input['recurrence_frequency']) . ';COUNT=' . max(1, (int)($input['recurrence_occurrences'] ?? 1)) : null,
            'reminder_minutes' => max(5, min(10080, (int)($input['reminder_minutes'] ?? 30))),
            'join_opens_minutes' => max(0, min(1440, (int)($input['join_opens_minutes'] ?? 15))),
            'join_closes_minutes' => max(0, min(1440, (int)($input['join_closes_minutes'] ?? 30))),
            'fallback_join_url' => $this->sanitize_external_url((string)($input['fallback_join_url'] ?? '')) ?: null,
            'recording_consent_required' => !empty($input['recording_consent_required']) ? 1 : 0,
            'session_status' => $session_status,
            'provider_payload' => !empty($input['provider_payload']) ? json_encode($input['provider_payload']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($data['title'] === '') {
            return ['status' => false, 'message' => 'Session title is required.'];
        }

        $start_timestamp = $this->get_session_start_timestamp($data);
        $end_timestamp = $this->get_session_end_timestamp($data);
        if ($start_timestamp <= 0 || $end_timestamp <= 0) {
            return ['status' => false, 'message' => 'Please enter a valid session date and time.'];
        }
        if ($end_timestamp <= $start_timestamp) {
            return ['status' => false, 'message' => 'End time must be after start time.'];
        }

        $this->db->insert('tutor_batch_sessions', $data);
        $session_id = (int)$this->db->insert_id();

        if ($session_id > 0) {
            $frequency = strtolower(trim((string)($input['recurrence_frequency'] ?? '')));
            $occurrences = max(1, (int)($input['recurrence_occurrences'] ?? 1));
            $recurrence = in_array($frequency, ['daily','weekly','biweekly'], true) && $occurrences > 1
                ? $this->teacher_workflow->create_recurring_sessions($session_id, $tutor_user_id, $frequency, $occurrences)
                : ['created' => 0, 'conflicts' => []];
            $this->teacher_workflow->schedule_default_reminders($batch_id, $tutor_user_id);
            $this->notify_accepted_students_for_batch(
                $batch_id,
                'session',
                $session_id,
                'New live session scheduled',
                'A new live session "' . $data['title'] . '" has been scheduled for your batch.',
                site_url('student_batch/view/' . $batch_id)
            );
            $this->trigger_batch_communication($batch_id, 'session_scheduled', $session_id);
        }

        return [
            'status' => $session_id > 0,
            'message' => $session_id > 0
                ? 'Session scheduled successfully: "' . html_escape($data['title']) . '" on ' . $session_date . ' from ' . substr($start_time, 0, 5) . ' to ' . substr($end_time, 0, 5) . '. ' . (int)($recurrence['created'] ?? 0) . ' recurring session(s) added. Enrolled students have been notified.'
                : 'Unable to schedule session. No session record was created.',
            'session_id' => $session_id,
            'batch_id' => $batch_id
        ];
    }

    public function get_batch_sessions(int $batch_id): array
    {
        return $this->db->from('tutor_batch_sessions')->where('batch_id', $batch_id)->order_by('session_date', 'ASC')->order_by('start_time', 'ASC')->get()->result_array();
    }

    /**
     * Phase F Teacher 360 Foundation.
     * Uses existing batch/session/task/payment data only; no analytics tables.
     */
    public function get_teacher_360_foundation(int $tutor_user_id): array
    {
        $tutor_user_id = (int)$tutor_user_id;
        if ($tutor_user_id <= 0) {
            return [];
        }

        $today = date('Y-m-d');

        $todays_classes = $this->db
            ->select('s.id, s.batch_id, s.title, s.session_date, s.start_time, s.end_time, s.session_type, s.session_status, b.title AS batch_title')
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'left')
            ->where('s.tutor_user_id', $tutor_user_id)
            ->where('s.session_date', $today)
            ->where_not_in('s.session_status', ['cancelled', 'archived'])
            ->order_by('s.start_time', 'ASC')
            ->limit(6)
            ->get()
            ->result_array();

        $pending_grading_count = (int)$this->db
            ->from('tutor_batch_assignment_submissions sub')
            ->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
            ->where('t.tutor_user_id', $tutor_user_id)
            ->where('t.task_type', 'assignment')
            ->where('sub.evaluation_status !=', 'evaluated')
            ->count_all_results();

        $pending_grading = $this->db
            ->select('sub.id, sub.submitted_at, t.title AS task_title, t.batch_id, b.title AS batch_title, u.first_name, u.last_name')
            ->from('tutor_batch_assignment_submissions sub')
            ->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
            ->join('tutor_batches b', 'b.id = t.batch_id', 'left')
            ->join('users u', 'u.id = sub.student_user_id', 'left')
            ->where('t.tutor_user_id', $tutor_user_id)
            ->where('t.task_type', 'assignment')
            ->where('sub.evaluation_status !=', 'evaluated')
            ->order_by('sub.submitted_at', 'ASC')
            ->limit(5)
            ->get()
            ->result_array();

        $demo_followups_count = (int)$this->db
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'left')
            ->where('s.tutor_user_id', $tutor_user_id)
            ->where('s.session_type', 'demo_class')
            ->where_in('s.session_status', ['scheduled', 'live', 'completed'])
            ->count_all_results();

        $demo_followups = $this->db
            ->select('s.id, s.batch_id, s.title, s.session_date, s.start_time, s.session_status, b.title AS batch_title, COUNT(DISTINCT bs.student_user_id) AS enrolled_students')
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'left')
            ->join('tutor_batch_students bs', 'bs.batch_id = s.batch_id AND bs.membership_status IN ("active","completed")', 'left')
            ->where('s.tutor_user_id', $tutor_user_id)
            ->where('s.session_type', 'demo_class')
            ->where_in('s.session_status', ['scheduled', 'live', 'completed'])
            ->group_by('s.id')
            ->order_by('s.session_date', 'DESC')
            ->order_by('s.start_time', 'DESC')
            ->limit(5)
            ->get()
            ->result_array();

        $batch_health = $this->db
            ->select('b.id, b.title, b.status, COUNT(DISTINCT bs.student_user_id) AS students, COUNT(DISTINCT s.id) AS sessions, COUNT(DISTINCT t.id) AS tasks')
            ->from('tutor_batches b')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id AND bs.membership_status IN ("active","completed")', 'left')
            ->join('tutor_batch_sessions s', 's.batch_id = b.id AND s.session_status NOT IN ("cancelled","archived")', 'left')
            ->join('tutor_batch_tasks t', 't.batch_id = b.id', 'left')
            ->where('b.tutor_user_id', $tutor_user_id)
            ->group_by('b.id')
            ->order_by('b.updated_at', 'DESC')
            ->limit(5)
            ->get()
            ->result_array();

        $active_batch_count = 0;
        $total_students = 0;
        foreach ($batch_health as $batch) {
            if (in_array(strtolower((string)($batch['status'] ?? '')), ['published', 'active'], true)) {
                $active_batch_count++;
            }
            $total_students += (int)($batch['students'] ?? 0);
        }

        return [
            'today_date' => $today,
            'todays_classes' => $todays_classes,
            'todays_class_count' => count($todays_classes),
            'pending_grading' => $pending_grading,
            'pending_grading_count' => $pending_grading_count,
            'demo_followups' => $demo_followups,
            'demo_followups_count' => $demo_followups_count,
            'batch_health' => $batch_health,
            'active_batch_count' => $active_batch_count,
            'total_student_count' => $total_students,
        ];
    }

    private function parse_student_context_selection($selection): array
    {
        $parts = explode('|', (string)$selection);
        return [
            'student_user_id' => isset($parts[0]) ? (int)$parts[0] : 0,
            'category_id' => isset($parts[1]) && $parts[1] !== '' ? (int)$parts[1] : null,
            'class_id' => isset($parts[2]) && $parts[2] !== '' ? (int)$parts[2] : null,
            'subject_id' => isset($parts[3]) && $parts[3] !== '' ? (int)$parts[3] : null,
        ];
    }

    private function build_student_context_key(int $student_user_id, ?int $category_id, ?int $class_id, ?int $subject_id): string
    {
        return $student_user_id . '|' . (int)$category_id . '|' . (int)$class_id . '|' . ((int)$subject_id > 0 ? (int)$subject_id : '');
    }

    private function where_nullable_int($column, $value): void
    {
        if ($value === null || $value === '' || (int)$value <= 0) {
            $this->db->where($column.' IS NULL', null, false);
        } else {
            $this->db->where($column, (int)$value);
        }
    }

    public function save_task(int $batch_id, int $tutor_user_id, array $input): array
    {
        $batch = $this->get_batch($batch_id, $tutor_user_id);
        if (empty($batch)) {
            return ['status' => false, 'message' => 'Batch not found.'];
        }

        $selected_students = isset($input['task_student_ids']) && is_array($input['task_student_ids']) ? $input['task_student_ids'] : [];
        if (empty($selected_students)) {
            return ['status' => false, 'message' => 'Please select at least one student for assignment/test.'];
        }

        $allowed_recipients = $this->get_enrolled_students_for_batch($batch_id, $tutor_user_id);
        if (empty($allowed_recipients)) {
            return ['status' => false, 'message' => 'No enrolled students are available for assignment/test in this batch.'];
        }

        $allowed_keys = [];
        foreach ($allowed_recipients as $recipient) {
            $allowed_keys[$this->build_student_context_key(
                (int)($recipient['id'] ?? 0),
                (int)($recipient['category_id'] ?? 0),
                (int)($recipient['class_id'] ?? 0),
                !empty($recipient['subject_id']) ? (int)$recipient['subject_id'] : null
            )] = true;
        }

        $valid_selected_students = [];
        $seen_selected = [];
        foreach ($selected_students as $selection) {
            $ctx = $this->parse_student_context_selection($selection);
            if ($ctx['student_user_id'] <= 0 || empty($ctx['category_id']) || empty($ctx['class_id'])) {
                continue;
            }

            $key = $this->build_student_context_key(
                (int)$ctx['student_user_id'],
                (int)$ctx['category_id'],
                (int)$ctx['class_id'],
                !empty($ctx['subject_id']) ? (int)$ctx['subject_id'] : null
            );

            if (!isset($allowed_keys[$key]) || isset($seen_selected[$key])) {
                continue;
            }

            $seen_selected[$key] = true;
            $ctx['student_user_id'] = (int)$ctx['student_user_id'];
            $ctx['category_id'] = (int)$ctx['category_id'];
            $ctx['class_id'] = (int)$ctx['class_id'];
            $ctx['subject_id'] = !empty($ctx['subject_id']) ? (int)$ctx['subject_id'] : null;
            $valid_selected_students[] = $ctx;
        }

        if (empty($valid_selected_students)) {
            return ['status' => false, 'message' => 'Assignments/tests can only be sent to students already enrolled in this batch.'];
        }

		$evaluation_status = 'published';

		if (isset($input['evaluation_status']) && in_array($input['evaluation_status'], ['draft','published','closed'], true)) {
			$evaluation_status = $input['evaluation_status'];
		}


        $date_error = null;
        $available_from = $this->normalize_mysql_datetime($input['available_from'] ?? '', 'Available from date/time', false, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        $due_at = $this->normalize_mysql_datetime($input['due_at'] ?? '', 'Due date/time', true, $date_error);
        if ($date_error !== null) {
            return ['status' => false, 'message' => $date_error];
        }
        if ($available_from !== null && $due_at !== null && strtotime($due_at) <= strtotime($available_from)) {
            return ['status' => false, 'message' => 'Due date/time must be after available from date/time.'];
        }

        $data = [
            'batch_id' => $batch_id,
            'tutor_user_id' => $tutor_user_id,
            'title' => trim((string)($input['title'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),
            'task_type' => ($input['task_type'] ?? 'assignment') === 'test' ? 'test' : 'assignment',
            'instructions' => trim((string)($input['instructions'] ?? '')),
            'max_marks' => is_numeric($input['max_marks'] ?? null) ? (float)$input['max_marks'] : null,
            'passing_marks' => is_numeric($input['passing_marks'] ?? null) ? (float)$input['passing_marks'] : null,
            'external_link' => $this->sanitize_external_url((string)($input['external_link'] ?? '')) ?: null,
            'available_from' => $available_from,
            'due_at' => $due_at,
            'rubric_id' => !empty($input['rubric_id']) ? (int)$input['rubric_id'] : null,
            'late_policy' => in_array($input['late_policy'] ?? 'allow', ['allow','deduct','block'], true) ? $input['late_policy'] : 'allow',
            'late_penalty_percent' => max(0, min(100, (float)($input['late_penalty_percent'] ?? 0))),
            'max_retakes' => max(0, min(20, (int)($input['max_retakes'] ?? 0))),
            'plagiarism_check_enabled' => !empty($input['plagiarism_check_enabled']) ? 1 : 0,
            #'evaluation_status' => in_array(($input['evaluation_status'] ?? 'published'), ['draft','published','closed'], true) ? $input['evaluation_status'] : 'published',
            'evaluation_status' => $evaluation_status,
			'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($data['title'] === '') {
            return ['status' => false, 'message' => 'Task title is required.'];
        }

        $this->db->trans_start();
        $this->db->insert('tutor_batch_tasks', $data);
        $task_id = (int)$this->db->insert_id();

        $assigned = 0;
        $assigned_student_ids = [];
        $now = date('Y-m-d H:i:s');
        $seen = [];

        foreach ($valid_selected_students as $ctx) {
            $key = $this->build_student_context_key($ctx['student_user_id'], $ctx['category_id'], $ctx['class_id'], $ctx['subject_id']);
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;

            $this->db->insert('tutor_batch_task_students', [
                'task_id' => $task_id,
                'batch_id' => $batch_id,
                'student_user_id' => $ctx['student_user_id'],
                'target_category_id' => $ctx['category_id'],
                'target_class_id' => $ctx['class_id'],
                'target_subject_id' => !empty($ctx['subject_id']) ? (int)$ctx['subject_id'] : null,
                'assignment_status' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $assigned_student_ids[] = (int)$ctx['student_user_id'];
            $assigned++;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false || $task_id <= 0 || $assigned <= 0) {
            return ['status' => false, 'message' => 'Unable to create task or assign students.'];
        }

        if ($data['evaluation_status'] === 'published') {
            $reference_type = ($data['task_type'] === 'test') ? 'test' : 'assignment';
            $title = ($data['task_type'] === 'test') ? 'New test assigned' : 'New assignment assigned';
            $message = 'A new ' . $data['task_type'] . ' "' . $data['title'] . '" has been assigned in your batch.';

            $this->notify_students_by_ids(
                $assigned_student_ids,
                $batch_id,
                $reference_type,
                $task_id,
                $title,
                $message,
                site_url('student_batch/view/' . $batch_id)
            );
        }

        $task_label = $data['task_type'] === 'test' ? 'Test' : 'Assignment';
        $notification_text = $data['evaluation_status'] === 'published'
            ? 'Notifications were sent to assigned students.'
            : 'It is saved as ' . $data['evaluation_status'] . '; student notifications were not sent yet.';

        return [
            'status' => true,
            'message' => $task_label . ' created successfully: "' . html_escape($data['title']) . '". Assigned to ' . $assigned . ' enrolled student selection(s). Due: ' . $due_at . '. ' . $notification_text
        ];
    }

    public function get_batch_tasks(int $batch_id): array
    {
        return $this->db
            ->select('t.*, COUNT(DISTINCT ts.id) AS assigned_students')
            ->from('tutor_batch_tasks t')
            ->join('tutor_batch_task_students ts', 'ts.task_id = t.id', 'left')
            ->where('t.batch_id', $batch_id)
            ->group_by('t.id')
            ->order_by('t.id', 'DESC')
            ->get()
            ->result_array();
    }

  /*  public function get_student_tasks_for_batch(int $batch_id, int $student_user_id): array
    {
        if (!$this->is_student_active_in_batch($batch_id, $student_user_id)) {
            return [];
        }

		return $this->db
            ->select('t.*, ts.assignment_status, ts.target_category_id, ts.target_class_id, ts.target_subject_id')
            ->from('tutor_batch_tasks t')
            ->join('tutor_batch_task_students ts', 'ts.task_id = t.id', 'inner')
            ->where('t.batch_id', $batch_id)
            ->where('ts.student_user_id', $student_user_id)
            ->order_by('t.id', 'DESC')
            ->get()
            ->result_array();
    }*/
	
	public function get_student_tasks_for_batch(int $batch_id, int $student_user_id): array
	{
        if (!$this->is_student_active_in_batch($batch_id, $student_user_id)) {
            return [];
        }

		return $this->db
			->select('
				t.*,
				ts.assignment_status,
				ts.target_category_id,
				ts.target_class_id,
				ts.target_subject_id,
				sub.id AS submission_id,
				sub.submission_file,
				sub.external_link AS submitted_external_link,
				sub.student_comment,
				sub.submitted_at,
				sub.evaluation_status,
				sub.marks_obtained,
				sub.tutor_remarks
			')
			->from('tutor_batch_tasks t')
			->join('tutor_batch_task_students ts', 'ts.task_id = t.id', 'inner')
			->join(
				'tutor_batch_assignment_submissions sub',
				'sub.task_id = t.id AND sub.student_user_id = ts.student_user_id',
				'left'
			)
			->where('t.batch_id', $batch_id)
			->where('ts.student_user_id', $student_user_id)
			->order_by('t.id', 'DESC')
			->get()
			->result_array();
	}

    public function get_student_batches(int $student_user_id): array
    {
        return $this->db->select('bs.*, b.title AS batch_title, b.description AS batch_description, b.delivery_mode, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
            ->from('tutor_batch_students bs')
            ->join('tutor_batches b', 'b.id = bs.batch_id', 'inner')
            ->join('users u', 'u.id = b.tutor_user_id', 'left')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active','completed'])
            ->order_by('bs.id', 'DESC')
            ->get()->result_array();
    }

    public function get_student_batch_detail(int $batch_id, int $student_user_id): array
    {
        $batch = $this->db->select('b.*, bs.progress_percent, bs.attendance_percent, bs.membership_status, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
            ->from('tutor_batch_students bs')
            ->join('tutor_batches b', 'b.id = bs.batch_id', 'inner')
            ->join('users u', 'u.id = b.tutor_user_id', 'left')
            ->where('bs.batch_id', $batch_id)
            ->where('bs.student_user_id', $student_user_id)
            ->get()->row_array();
        if (!$batch) {
            return [];
        }
        $batch['sessions'] = $this->db
            ->from('tutor_batch_sessions')
            ->where('batch_id', $batch_id)
            ->where_in('session_status', ['scheduled', 'live', 'completed'])
            ->order_by('session_date', 'ASC')
            ->order_by('start_time', 'ASC')
            ->get()
            ->result_array();
        $batch['tasks'] = $this->get_student_tasks_for_batch($batch_id, $student_user_id);
		$batch['progress_summary'] = $this->get_student_progress_summary($batch_id, $student_user_id);
		$batch['tests'] = $this->get_student_tests_for_batch($batch_id, $student_user_id);
        $batch['recordings'] = $this->db->from('tutor_batch_recordings')->where('batch_id', $batch_id)->where('availability_status', 'ready')->order_by('id', 'DESC')->get()->result_array();
        return $batch;
    }

    /**
     * Student 360 dashboard foundation.
     * Uses existing student batch, task, test, notification and profile data only.
     */
    public function get_student_360_foundation(int $student_user_id): array
    {
        if ($student_user_id <= 0) {
            return [];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $batches = $this->get_student_batches($student_user_id);
        $invites = $this->get_student_invites($student_user_id);
        $pending_invites = array_values(array_filter($invites, static function ($invite) {
            return strtolower((string)($invite['invite_status'] ?? '')) === 'pending';
        }));

        $today_sessions = $this->db
            ->select('s.*, b.title AS batch_title, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id', 'inner')
            ->join('users u', 'u.id = b.tutor_user_id', 'left')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->where('s.session_date', $today)
            ->where_in('s.session_status', ['scheduled', 'live'])
            ->order_by('s.start_time', 'ASC')
            ->limit(6)
            ->get()
            ->result_array();

        $upcoming_sessions = $this->db
            ->select('s.*, b.title AS batch_title, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name')
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id', 'inner')
            ->join('users u', 'u.id = b.tutor_user_id', 'left')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->where('s.session_date >', $today)
            ->where_in('s.session_status', ['scheduled', 'live'])
            ->order_by('s.session_date', 'ASC')
            ->order_by('s.start_time', 'ASC')
            ->limit(6)
            ->get()
            ->result_array();

        $pending_assignments = $this->db
            ->select('t.*, ts.assignment_status, b.title AS batch_title')
            ->from('tutor_batch_task_students ts')
            ->join('tutor_batch_tasks t', 't.id = ts.task_id', 'inner')
            ->join('tutor_batches b', 'b.id = t.batch_id', 'left')
            ->where('ts.student_user_id', $student_user_id)
            ->where('t.task_type', 'assignment')
            ->where_not_in('ts.assignment_status', ['submitted', 'evaluated'])
            ->order_by('t.due_at', 'ASC')
            ->limit(6)
            ->get()
            ->result_array();

        $pending_tests = $this->db
            ->select('t.*, b.title AS batch_title, a.id AS attempt_id, a.status AS attempt_status')
            ->from('tutor_batch_tests t')
            ->join('tutor_batches b', 'b.id = t.batch_id', 'inner')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id', 'inner')
            ->join('tutor_batch_test_attempts a', 'a.test_id = t.id AND a.student_user_id = ' . (int)$student_user_id, 'left')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->where('t.status', 'published')
            ->group_start()
                ->where('a.id IS NULL', null, false)
                ->or_where('a.status !=', 'submitted')
            ->group_end()
            ->order_by('t.id', 'DESC')
            ->limit(6)
            ->get()
            ->result_array();

        $batch_progress = [];
        $attendance_total = 0;
        $assignment_total = 0;
        $test_total = 0;
        $overall_total = 0;
        $latest_teacher_remarks = [];
        foreach ($batches as $batch) {
            $batch_id = (int)$batch['batch_id'];
            $summary = $this->get_student_progress_summary($batch_id, $student_user_id);

            $test_attempts = $this->db
                ->select('t.title, a.score, a.total_marks, a.percentage, a.submitted_at')
                ->from('tutor_batch_test_attempts a')
                ->join('tutor_batch_tests t', 't.id = a.test_id', 'left')
                ->where('a.batch_id', $batch_id)
                ->where('a.student_user_id', $student_user_id)
                ->where('a.status', 'submitted')
                ->order_by('a.submitted_at', 'ASC')
                ->get()
                ->result_array();

            $trend_label = 'No submitted tests yet';
            $trend_direction = 'neutral';
            $trend_delta = 0;
            $attempt_count = count($test_attempts);
            if ($attempt_count >= 2) {
                $previous = (float)($test_attempts[$attempt_count - 2]['percentage'] ?? 0);
                $latest = (float)($test_attempts[$attempt_count - 1]['percentage'] ?? 0);
                $trend_delta = round($latest - $previous, 2);
                if ($trend_delta > 0) {
                    $trend_label = 'Improving +' . $trend_delta . '%';
                    $trend_direction = 'up';
                } elseif ($trend_delta < 0) {
                    $trend_label = 'Declining ' . $trend_delta . '%';
                    $trend_direction = 'down';
                } else {
                    $trend_label = 'Stable';
                }
            } elseif ($attempt_count === 1) {
                $trend_label = 'First score ' . round((float)($test_attempts[0]['percentage'] ?? 0), 2) . '%';
            }

            $teacher_remarks = $this->db
                ->select('t.title AS task_title, t.batch_id, b.title AS batch_title, sub.tutor_remarks, sub.marks_obtained, t.max_marks, sub.evaluated_at')
                ->from('tutor_batch_assignment_submissions sub')
                ->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
                ->join('tutor_batches b', 'b.id = t.batch_id', 'left')
                ->where('sub.batch_id', $batch_id)
                ->where('sub.student_user_id', $student_user_id)
                ->where('sub.evaluation_status', 'evaluated')
                ->where('sub.tutor_remarks IS NOT NULL', null, false)
                ->where('sub.tutor_remarks !=', '')
                ->order_by('sub.evaluated_at', 'DESC')
                ->limit(5)
                ->get()
                ->result_array();

            $batch_progress[] = [
                'batch' => $batch,
                'summary' => $summary,
                'test_attempts' => $test_attempts,
                'test_trend' => [
                    'label' => $trend_label,
                    'direction' => $trend_direction,
                    'delta' => $trend_delta,
                ],
                'teacher_remarks' => $teacher_remarks,
            ];
            $latest_teacher_remarks = array_merge($latest_teacher_remarks, $teacher_remarks);
            $attendance_total += (float)($summary['attendance_percent'] ?? 0);
            $assignment_total += (float)($summary['assignment_percent'] ?? 0);
            $test_total += (float)($summary['test_percent'] ?? 0);
            $overall_total += (float)($summary['overall_percent'] ?? 0);
        }

        $batch_count = count($batch_progress);
        usort($latest_teacher_remarks, static function ($left, $right) {
            return strtotime((string)($right['evaluated_at'] ?? '')) <=> strtotime((string)($left['evaluated_at'] ?? ''));
        });
        $latest_teacher_remarks = array_slice($latest_teacher_remarks, 0, 6);

        $summary_snapshot = [
            'attendance_percent' => $batch_count > 0 ? round($attendance_total / $batch_count, 2) : 0,
            'assignment_percent' => $batch_count > 0 ? round($assignment_total / $batch_count, 2) : 0,
            'test_percent' => $batch_count > 0 ? round($test_total / $batch_count, 2) : 0,
            'overall_percent' => $batch_count > 0 ? round($overall_total / $batch_count, 2) : 0,
        ];

        $weak_areas = [];
        $strong_areas = [];
        if ($summary_snapshot['attendance_percent'] > 0 && $summary_snapshot['attendance_percent'] < 75) {
            $weak_areas[] = 'Attendance consistency';
        } elseif ($summary_snapshot['attendance_percent'] >= 90) {
            $strong_areas[] = 'Attendance consistency';
        }
        if ($summary_snapshot['assignment_percent'] > 0 && $summary_snapshot['assignment_percent'] < 70) {
            $weak_areas[] = 'Assignment completion';
        } elseif ($summary_snapshot['assignment_percent'] >= 90) {
            $strong_areas[] = 'Assignment completion';
        }
        if ($summary_snapshot['test_percent'] > 0 && $summary_snapshot['test_percent'] < 60) {
            $weak_areas[] = 'Test performance';
        } elseif ($summary_snapshot['test_percent'] >= 85) {
            $strong_areas[] = 'Test performance';
        }
        if (empty($weak_areas)) {
            $weak_areas[] = 'No major weak area detected from current data';
        }
        if (empty($strong_areas)) {
            $strong_areas[] = 'Keep attending classes and submitting work to build stronger signals';
        }

        $missed_classes = $this->db
            ->select('a.status, a.marked_at, s.title, s.session_date, s.start_time, b.title AS batch_title')
            ->from('tutor_batch_attendance a')
            ->join('tutor_batch_sessions s', 's.id = a.session_id', 'inner')
            ->join('tutor_batches b', 'b.id = a.batch_id', 'left')
            ->where('a.student_user_id', $student_user_id)
            ->where('a.status', 'absent')
            ->order_by('s.session_date', 'DESC')
            ->order_by('s.start_time', 'DESC')
            ->limit(5)
            ->get()
            ->result_array();

        $learning_consistency = round(
            ($summary_snapshot['attendance_percent'] + $summary_snapshot['assignment_percent']) / 2,
            2
        );

        $latest_notifications = [];
        $unread_notifications = 0;
        if ($this->db->table_exists('lms_notifications')) {
            $unread_notifications = (int)$this->db
                ->where('user_id', $student_user_id)
                ->where('is_read', 0)
                ->count_all_results('lms_notifications');

            $latest_notifications = $this->db
                ->where('user_id', $student_user_id)
                ->order_by('id', 'DESC')
                ->limit(5)
                ->get('lms_notifications')
                ->result_array();
        }

        return [
            'active_batch_count' => count($batches),
            'pending_invite_count' => count($pending_invites),
            'today_class_count' => count($today_sessions),
            'pending_work_count' => count($pending_assignments) + count($pending_tests),
            'batches' => $batches,
            'pending_invites' => $pending_invites,
            'today_sessions' => $today_sessions,
            'upcoming_sessions' => $upcoming_sessions,
            'pending_assignments' => $pending_assignments,
            'pending_tests' => $pending_tests,
            'batch_progress' => $batch_progress,
            'latest_teacher_remarks' => $latest_teacher_remarks,
            'latest_notifications' => $latest_notifications,
            'unread_notifications' => $unread_notifications,
            'learning_profile' => $this->get_student_profile($student_user_id),
            'summary' => $summary_snapshot,
            'learning_insights' => [
                'weak_areas' => $weak_areas,
                'strong_areas' => $strong_areas,
                'missed_classes' => $missed_classes,
                'missed_class_count' => count($missed_classes),
                'learning_consistency' => $learning_consistency,
            ],
            'generated_at' => $now,
        ];
    }

    /**
     * Parent dashboard foundation.
     * Returns a stable, read-only progress shape that can later be reused by a parent portal.
     * No parent account/linking logic is introduced here.
     */
    public function get_parent_ready_student_progress(int $student_user_id): array
    {
        if ($student_user_id <= 0) {
            return [];
        }

        $student = $this->db
            ->select('id, first_name, last_name, email, phone, image')
            ->where('id', $student_user_id)
            ->get('users', 1)
            ->row_array();

        if (empty($student)) {
            return [];
        }

        $dashboard = $this->get_student_360_foundation($student_user_id);
        $batch_rows = [];

        foreach (($dashboard['batch_progress'] ?? []) as $item) {
            $batch = $item['batch'] ?? [];
            $summary = $item['summary'] ?? [];
            $trend = $item['test_trend'] ?? [];

            $batch_rows[] = [
                'batch_id' => (int)($batch['batch_id'] ?? 0),
                'batch_title' => (string)($batch['batch_title'] ?? ''),
                'tutor_name' => trim((string)($batch['tutor_first_name'] ?? '') . ' ' . (string)($batch['tutor_last_name'] ?? '')),
                'membership_status' => (string)($batch['membership_status'] ?? ''),
                'attendance_percent' => (float)($summary['attendance_percent'] ?? 0),
                'assignment_completion_percent' => (float)($summary['assignment_percent'] ?? 0),
                'average_test_score_percent' => (float)($summary['test_percent'] ?? 0),
                'overall_progress_percent' => (float)($summary['overall_percent'] ?? 0),
                'test_score_trend' => [
                    'label' => (string)($trend['label'] ?? 'No submitted tests yet'),
                    'direction' => (string)($trend['direction'] ?? 'neutral'),
                    'delta' => (float)($trend['delta'] ?? 0),
                ],
            ];
        }

        $pending_work = [];
        foreach (($dashboard['pending_assignments'] ?? []) as $assignment) {
            $pending_work[] = [
                'type' => 'assignment',
                'title' => (string)($assignment['title'] ?? ''),
                'batch_id' => (int)($assignment['batch_id'] ?? 0),
                'batch_title' => (string)($assignment['batch_title'] ?? ''),
                'due_at' => (string)($assignment['due_at'] ?? ''),
                'status' => (string)($assignment['assignment_status'] ?? 'pending'),
            ];
        }

        foreach (($dashboard['pending_tests'] ?? []) as $test) {
            $pending_work[] = [
                'type' => 'test',
                'title' => (string)($test['title'] ?? ''),
                'batch_id' => (int)($test['batch_id'] ?? 0),
                'batch_title' => (string)($test['batch_title'] ?? ''),
                'due_at' => '',
                'status' => (string)($test['attempt_status'] ?? 'pending'),
            ];
        }

        $recent_sessions = $this->db
            ->select('s.id, s.batch_id, s.title, s.session_date, s.start_time, s.end_time, s.session_status, b.title AS batch_title')
            ->from('tutor_batch_sessions s')
            ->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id', 'inner')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->order_by('s.session_date', 'DESC')
            ->order_by('s.start_time', 'DESC')
            ->limit(8)
            ->get()
            ->result_array();

        $recent_assignments_tests = $this->db
            ->select('t.id, t.batch_id, t.task_type AS type, t.title, t.due_at, b.title AS batch_title, ts.assignment_status AS status')
            ->from('tutor_batch_task_students ts')
            ->join('tutor_batch_tasks t', 't.id = ts.task_id', 'inner')
            ->join('tutor_batches b', 'b.id = t.batch_id', 'left')
            ->where('ts.student_user_id', $student_user_id)
            ->order_by('t.due_at', 'DESC')
            ->limit(8)
            ->get()
            ->result_array();

        $recent_tests = $this->db
            ->select('t.id, t.batch_id, "test" AS type, t.title, "" AS due_at, b.title AS batch_title, COALESCE(a.status, "pending") AS status', false)
            ->from('tutor_batch_tests t')
            ->join('tutor_batches b', 'b.id = t.batch_id', 'inner')
            ->join('tutor_batch_students bs', 'bs.batch_id = b.id', 'inner')
            ->join('tutor_batch_test_attempts a', 'a.test_id = t.id AND a.student_user_id = ' . (int)$student_user_id, 'left')
            ->where('bs.student_user_id', $student_user_id)
            ->where_in('bs.membership_status', ['active', 'completed'])
            ->where('t.status', 'published')
            ->order_by('t.id', 'DESC')
            ->limit(8)
            ->get()
            ->result_array();

        $recent_assignments_tests = array_slice(array_merge($recent_assignments_tests, $recent_tests), 0, 10);

        return [
            'student_profile' => [
                'student_user_id' => (int)$student['id'],
                'name' => trim((string)($student['first_name'] ?? '') . ' ' . (string)($student['last_name'] ?? '')),
                'email' => (string)($student['email'] ?? ''),
                'phone' => (string)($student['phone'] ?? ''),
                'learning_profile' => $dashboard['learning_profile'] ?? [],
            ],
            'summary' => [
                'active_batch_count' => (int)($dashboard['active_batch_count'] ?? 0),
                'attendance_percent' => (float)($dashboard['summary']['attendance_percent'] ?? 0),
                'assignment_completion_percent' => (float)($dashboard['summary']['assignment_percent'] ?? 0),
                'average_test_score_percent' => (float)($dashboard['summary']['test_percent'] ?? 0),
                'overall_progress_percent' => (float)($dashboard['summary']['overall_percent'] ?? 0),
                'pending_work_count' => count($pending_work),
            ],
            'active_batches' => $batch_rows,
            'teacher_remarks' => $dashboard['latest_teacher_remarks'] ?? [],
            'learning_insights' => $dashboard['learning_insights'] ?? [],
            'recent_sessions' => $recent_sessions,
            'recent_assignments_tests' => $recent_assignments_tests,
            'pending_work' => $pending_work,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function get_batch_summary(int $batch_id): array
    {
        $students = (int)$this->db->where('batch_id', $batch_id)->where_in('membership_status', ['active','completed'])->count_all_results('tutor_batch_students');
        $sessions = (int)$this->db->where('batch_id', $batch_id)->count_all_results('tutor_batch_sessions');
        $tasks = (int)$this->db->where('batch_id', $batch_id)->count_all_results('tutor_batch_tasks');
        $pending_invites = (int)$this->db->where('batch_id', $batch_id)->where('invite_status', 'pending')->count_all_results('tutor_batch_invites');
        return compact('students', 'sessions', 'tasks', 'pending_invites');
    }

    private function generate_unique_batch_code(): string
    {
        do {
            $code = 'BTH-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->db->where('batch_code', $code)->count_all_results('tutor_batches') > 0);

        return $code;
    }
	
	
    public function get_batch_invites($batch_id)
    {
        return $this->db
            ->select('i.*, u.first_name, u.last_name, tc.name AS target_category_name, tcl.name AS target_class_name, tsm.name AS target_subject_name')
            ->from('tutor_batch_invites i')
            ->join('users u', 'u.id = i.student_user_id', 'left')
            ->join('tutor_categories tc', 'tc.id = i.target_category_id', 'left')
            ->join('tutor_classes tcl', 'tcl.id = i.target_class_id', 'left')
            ->join('tutor_subject_master tsm', 'tsm.id = i.target_subject_id', 'left')
            ->where('i.batch_id', (int)$batch_id)
            ->order_by('i.id', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_student_profile($student_user_id)
    {
        $profile = $this->db
            ->select('slp.*, c.name AS category_name, cl.name AS class_name, s.name AS subject_name')
            ->from('student_learning_profiles slp')
            ->join('tutor_categories c', 'c.id = slp.category_id', 'left')
            ->join('tutor_classes cl', 'cl.id = slp.class_id', 'left')
            ->join('tutor_subject_master s', 's.id = slp.subject_interest_id', 'left')
            ->where('slp.student_user_id', (int)$student_user_id)
            ->get()
            ->row_array();
        return $profile ? $profile : [];
    }

    public function save_student_classification($student_user_id, $input)
    {
        $student_user_id = (int)$student_user_id;
        $payload = [
            'student_user_id' => $student_user_id,
            'category_id' => !empty($input['category_id']) ? (int)$input['category_id'] : null,
            'class_id' => !empty($input['class_id']) ? (int)$input['class_id'] : null,
            'subject_interest_id' => !empty($input['subject_interest_id']) ? (int)$input['subject_interest_id'] : null,
            'current_level_label' => trim((string)($input['current_level_label'] ?? '')),
            'academic_year' => trim((string)($input['academic_year'] ?? date('Y'))),
            'status' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $existing = $this->db->get_where('student_learning_profiles', ['student_user_id' => $student_user_id], 1)->row_array();
        if ($existing) {
            $this->db->where('student_user_id', $student_user_id)->update('student_learning_profiles', $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('student_learning_profiles', $payload);
        }

        return ['status' => true, 'message' => 'Learning profile updated successfully.'];
    }

    public function get_eligible_students_for_batch($batch_id, $tutor_user_id)
    {
        $batch = $this->get_batch((int)$batch_id, (int)$tutor_user_id);
        if (empty($batch)) {
            return [];
        }

        $this->db
            ->select('u.id, u.first_name, u.last_name, u.email, u.phone, slp.category_id, slp.class_id, slp.subject_interest_id, c.name AS category_name, cl.name AS class_name, sm.name AS subject_name')
            ->from('users u')
            ->join('student_learning_profiles slp', 'slp.student_user_id = u.id', 'inner')
            ->join('tutor_categories c', 'c.id = slp.category_id', 'left')
            ->join('tutor_classes cl', 'cl.id = slp.class_id', 'left')
            ->join('tutor_subject_master sm', 'sm.id = slp.subject_interest_id', 'left')
            ->where('u.is_instructor', 0)
            ->where('u.status', 1)
            ->where('slp.status', 1);

        if (!empty($batch['category_id'])) {
            $this->db->where('slp.category_id', (int)$batch['category_id']);
        }
        if (!empty($batch['class_id'])) {
            $this->db->where('slp.class_id', (int)$batch['class_id']);
        }

        return $this->db->order_by('u.first_name', 'ASC')->get()->result_array();
    }

    public function get_tutor_master_categories()
    {
        return $this->db->where('status', 1)->order_by('sort_order', 'ASC')->get('tutor_categories')->result_array();
    }

    public function get_tutor_master_classes()
    {
        return $this->db->where('status', 1)->order_by('category_id', 'ASC')->order_by('sort_order', 'ASC')->get('tutor_classes')->result_array();
    }

    public function get_tutor_master_subjects()
    {
        return $this->db->where('status', 1)->order_by('class_id', 'ASC')->order_by('sort_order', 'ASC')->get('tutor_subject_master')->result_array();
    }

	private function build_invite_email_html($batch, $message, $invite_url)
	{
		return '
		<div style="font-family:Arial,sans-serif;background:#f5f7fb;padding:30px;">
			<div style="max-width:650px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
				<div style="background:#0f172a;color:#ffffff;padding:22px;">
					<h2 style="margin:0;">Batch Invitation</h2>
					<p style="margin:5px 0 0;">Lvalues Learning Platform</p>
				</div>

				<div style="padding:25px;color:#111827;">
					<p>Hello,</p>

					<p>You have been invited to join the batch:</p>

					<h3 style="color:#2563eb;margin-top:10px;">'.html_escape($batch['title']).'</h3>

					<div style="background:#f9fafb;border-left:4px solid #2563eb;padding:15px;margin:20px 0;">
						'.nl2br(html_escape($message)).'
					</div>

					<p>Please review the invitation and confirm your participation.</p>

					<p style="margin:25px 0;">
						<a href="'.$invite_url.'" 
						   style="background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;display:inline-block;">
						   Open Invitation
						</a>
					</p>

					<p style="font-size:13px;color:#6b7280;">
						If the button does not work, copy this link:<br>
						'.$invite_url.'
					</p>

					<p>Regards,<br>
					<strong>Lvalues Team</strong></p>
				</div>
			</div>
		</div>';
	}
	
    public function bulk_invite_students($batch_id, $tutor_user_id, $student_ids, $message, $send_email = true, $prepare_whatsapp = true)
    {
        $batch_id = (int)$batch_id;
        $tutor_user_id = (int)$tutor_user_id;
        $batch = $this->get_batch($batch_id, $tutor_user_id);

        if (empty($batch)) {
            return ['status' => false, 'message' => 'Invalid batch.'];
        }
        if (empty($student_ids) || !is_array($student_ids)) {
            return ['status' => false, 'message' => 'Please select at least one eligible student.'];
        }

        $sent = 0;
        $skipped = 0;
        $whatsapp_ready = 0;
        $email_sent_count = 0;
        $email_failed_count = 0;
        $email_not_requested_count = 0;
        $whatsapp_missing_phone_count = 0;
        $message = trim((string)$message);
        if ($message === '') {
            $message = 'You are invited to join the batch "'.$batch['title'].'". Please accept the invitation from your student dashboard.';
        }
        $this->load->model('Idempotency_model', 'idempotency');
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $operation = ['batch_id' => $batch_id, 'students' => array_values($student_ids), 'message' => $message, 'send_email' => (bool)$send_email, 'prepare_whatsapp' => (bool)$prepare_whatsapp];
        $key = $this->idempotency->key('invitation.bulk_create', $operation);
        $this->db->trans_begin();
        $claim = $this->idempotency->claim('invitation.bulk_create', $key, $tutor_user_id, $operation);
        if (empty($claim['proceed'])) {
            $this->db->trans_rollback();
            return $claim['response'];
        }

        foreach ($student_ids as $selection) {
            $ctx = $this->parse_student_context_selection($selection);
            $student_id = (int)$ctx['student_user_id'];
            if ($student_id <= 0 || empty($ctx['category_id']) || empty($ctx['class_id'])) { $skipped++; continue; }

            $student = $this->db
                ->select('u.id, u.first_name, u.last_name, u.email, u.phone')
                ->from('users u')
                ->where('u.id', $student_id)
                ->where('u.is_instructor', 0)
                ->where('u.status', 1)
                ->get()
                ->row_array();

            if (empty($student) || empty($student['email'])) { $skipped++; continue; }
            if (!empty($batch['category_id']) && (int)$ctx['category_id'] !== (int)$batch['category_id']) { $skipped++; continue; }
            if (!empty($batch['class_id']) && (int)$ctx['class_id'] !== (int)$batch['class_id']) { $skipped++; continue; }
            if (!empty($batch['subject_id']) && (int)($ctx['subject_id'] ?? 0) !== (int)$batch['subject_id']) { $skipped++; continue; }

            $this->db->where('batch_id', $batch_id)
                ->where('student_user_id', $student_id)
                ->where('target_category_id', (int)$ctx['category_id'])
                ->where('target_class_id', (int)$ctx['class_id'])
                ->where_in('invite_status', ['pending', 'accepted']);
            $this->where_nullable_int('target_subject_id', $ctx['subject_id']);
            $existing = $this->db->get('tutor_batch_invites')->row_array();

            if (!empty($existing)) { $skipped++; continue; }

            $this->db->where('batch_id', $batch_id)
                ->where('student_user_id', $student_id)
                ->where('target_category_id', (int)$ctx['category_id'])
                ->where('target_class_id', (int)$ctx['class_id'])
                ->where('invite_status', 'rejected')
                ->order_by('id', 'DESC');
            $this->where_nullable_int('target_subject_id', $ctx['subject_id']);
            $rejected = $this->db->get('tutor_batch_invites')->row_array();

            $token = !empty($rejected['invite_token']) ? $rejected['invite_token'] : md5(uniqid($student['email'], true));
            $invite_url = site_url('student_batch/invites');
            $now = date('Y-m-d H:i:s');
            $payload = [
                'batch_id'        => $batch_id,
                'tutor_user_id'   => $tutor_user_id,
                'student_user_id' => $student_id,
                'target_category_id' => (int)$ctx['category_id'],
                'target_class_id' => (int)$ctx['class_id'],
                'target_subject_id' => $ctx['subject_id'] ? (int)$ctx['subject_id'] : null,
                'invite_email'    => $student['email'],
                'invite_phone'    => $student['phone'] ?? '',
                'invite_token'    => $token,
                'invite_message'  => $message,
                'invite_status'   => 'pending',
                'email_sent_at'   => null,
                'whatsapp_sent_at'=> null,
                'responded_at'    => null,
                'expires_at'      => date('Y-m-d H:i:s', strtotime('+7 days')),
                'updated_at'      => $now
            ];
            if ($this->db->field_exists('idempotency_key', 'tutor_batch_invites')) {
                $payload['idempotency_key'] = substr($key . ':' . $student_id, 0, 128);
            }

            $invite_id = 0;
            if (!empty($rejected)) {
                $invite_id = (int)$rejected['id'];
                $this->db->where('id', $invite_id)->update('tutor_batch_invites', $payload);
            } else {
                $payload['created_at'] = $now;
                $this->db->insert('tutor_batch_invites', $payload);
                $invite_id = (int)$this->db->insert_id();
            }
            $this->immutable_audit->record('invitation', empty($rejected) ? 'created' : 'resent', 'tutor_batch_invite', $invite_id, $rejected ?: [], $payload, [
                'actor_user_id' => $tutor_user_id,
                'actor_role' => 'tutor',
                'idempotency_key' => $key,
            ]);
            $reminder_key = 'invitation:' . $invite_id . ':pending';
            $this->db->query("INSERT IGNORE INTO teacher_automated_reminders(tutor_user_id,batch_id,reminder_type,reference_type,reference_id,recipient_user_id,scheduled_at,channels_json,dedupe_key) VALUES(?,?,'invitation','invite',?,?,?,'[\"email\",\"in_app\"]',?)", [
                $tutor_user_id, $batch_id, $invite_id, $student_id, date('Y-m-d H:i:s', strtotime('+24 hours')), $reminder_key
            ]);

            if ($send_email) {
                $subject = 'Batch invitation for '.$batch['title'];
                $html_message = $this->build_invite_email_html($batch, $message, $invite_url);
                $email_sent = $this->send_batch_invite_email($student['email'], $subject, $html_message);

                if ($email_sent && $invite_id > 0) {
                    $this->db->where('id', $invite_id)->update('tutor_batch_invites', [
                        'email_sent_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $email_sent_count++;
                } else {
                    $email_failed_count++;
                }
            } else {
                $email_not_requested_count++;
            }

            if ($prepare_whatsapp && !empty($student['phone'])) {
                $whatsapp_ready++;
            } elseif ($prepare_whatsapp && empty($student['phone'])) {
                $whatsapp_missing_phone_count++;
            }
            $sent++;
        }

        if ($sent <= 0) {
            $response = [
                'status' => false,
                'message' => 'No invitations were created. Skipped: ' . $skipped . '. Please select students who are not already pending/accepted and match this batch.'
            ];
            $this->idempotency->complete((int)$claim['id'], $response, 'tutor_batch', $batch_id);
            $this->db->trans_commit();
            return $response;
        }

        $details = [
            'Created/Re-sent: ' . $sent,
            'Skipped: ' . $skipped,
        ];

        if ($send_email) {
            $details[] = 'Email sent: ' . $email_sent_count;
            $details[] = 'Email failed: ' . $email_failed_count;
        } else {
            $details[] = 'Email not requested: ' . $email_not_requested_count;
        }

        if ($prepare_whatsapp) {
            $details[] = 'WhatsApp prepared: ' . $whatsapp_ready;
            if ($whatsapp_missing_phone_count > 0) {
                $details[] = 'WhatsApp not prepared due to missing phone: ' . $whatsapp_missing_phone_count;
            }
        } else {
            $details[] = 'WhatsApp not requested';
        }

        $response = [
            'status' => true,
            'flash_type' => ($email_failed_count > 0 || $skipped > 0 || $whatsapp_missing_phone_count > 0) ? 'warning_message' : 'flash_message',
            'message' => 'Student invite completed for batch "' . html_escape($batch['title']) . '". ' . implode(' | ', $details) . '.'
        ];
        $this->idempotency->complete((int)$claim['id'], $response, 'tutor_batch', $batch_id);
        if ($this->db->trans_status()) $this->db->trans_commit(); else $this->db->trans_rollback();
        return $response;
    }

	
	
    public function get_all_classified_students_for_invite($batch_id)
	{
		$batch_id = (int)$batch_id;

		return $this->db
			->select('
				u.id,
				u.first_name,
				u.last_name,
				u.email,
				u.phone,
				sls.category_id,
				sls.class_id,
				sls.subject_id,
				c.name AS category_name,
				cl.name AS class_name,
				s.name AS subject_name,
				i.invite_status AS existing_invite_status
			')
			->from('users u')

			// 🔥 NEW TABLE (multiple subjects)
			->join('student_learning_subjects sls', 'sls.student_user_id = u.id', 'inner')

			->join('tutor_categories c', 'c.id = sls.category_id', 'left')
			->join('tutor_classes cl', 'cl.id = sls.class_id', 'left')
			->join('tutor_subject_master s', 's.id = sls.subject_id', 'left')

			// 🔥 SUBJECT LEVEL JOIN
			->join(
				'tutor_batch_invites i',
				'i.student_user_id = u.id 
				 AND i.batch_id = '.$batch_id.'
				 AND i.target_subject_id = sls.subject_id',
				'left'
			)

			->where('u.is_instructor', 0)
			->where('u.status', 1)

			->order_by('c.name', 'ASC')
			->order_by('cl.name', 'ASC')
			->order_by('s.name', 'ASC')
			->order_by('u.first_name', 'ASC')

			->get()
			->result_array();
	}
	
	/**
	 * Send batch invite using the existing LMS Email_model SMTP pipeline.
	 */
	private function send_batch_invite_email($to, $subject, $html_message)
	{
		if (empty($to) || empty($subject) || empty($html_message)) {
			return false;
		}

		if (!isset($this->email_model) || !is_object($this->email_model)) {
			$this->load->model('email_model');
		}

		$sent = $this->email_model->send_smtp_mail($html_message, $subject, $to);

		if (!$sent) {
			log_message('error', 'Batch invite email failed for: ' . $to . ' | Subject: ' . $subject);
		}

		return (bool)$sent;
	}
	
	
	public function submit_assignment(int $task_id, int $student_user_id, array $input, array $files): array
	{
		$task = $this->db
			->select('t.*, ts.student_user_id')
			->from('tutor_batch_tasks t')
			->join('tutor_batch_task_students ts', 'ts.task_id = t.id', 'inner')
			->where('t.id', $task_id)
			->where('ts.student_user_id', $student_user_id)
			->where('t.task_type', 'assignment')
			->get()
			->row_array();

		if (empty($task)) {
			return [
				'status' => false,
				'message' => 'Assignment not found or access denied.',
				'batch_id' => 0
			];
		}

		$batch_id = (int)$task['batch_id'];
        $is_late = !empty($task['due_at']) && strtotime($task['due_at']) < time();
        if ($is_late && ($task['late_policy'] ?? 'allow') === 'block') {
            return ['status'=>false,'message'=>'The submission deadline has passed and late submissions are closed.','batch_id'=>$batch_id];
        }

        if (!$this->is_student_active_in_batch($batch_id, $student_user_id)) {
            return [
                'status' => false,
                'message' => 'You are not authorized to submit this assignment.',
                'batch_id' => $batch_id
            ];
        }
		$external_link = $this->sanitize_external_url((string)($input['external_link'] ?? ''));
		$student_comment = trim((string)($input['student_comment'] ?? ''));

		$uploaded_file = null;

		if (!empty($files['submission_file']['name'])) {
			$upload_path = FCPATH . 'uploads/assignment_submissions/';
			$CI =& get_instance();
			$CI->load->library('secure_upload');
			$upload_result = $CI->secure_upload->store($files['submission_file'], $upload_path, [
				'extensions' => ['pdf','doc','docx','jpg','jpeg','png'],
				'max_bytes' => 5 * 1024 * 1024,
				'actor_user_id' => $student_user_id,
				'owner_user_id' => $student_user_id,
				'entity_type' => 'assignment_submission',
				'entity_id' => $task_id,
			]);
			if (empty($upload_result['ok'])) {
				return [
					'status' => false,
					'message' => $upload_result['message'],
					'batch_id' => $batch_id
				];
			}
			$uploaded_file = 'uploads/assignment_submissions/' . $upload_result['file_name'];
		}

		if ($uploaded_file === null && $external_link === '' && $student_comment === '') {
			return [
				'status' => false,
				'message' => 'Please upload a file, add a link, or write a comment.',
				'batch_id' => $batch_id
			];
		}

		$existing = $this->db
			->get_where('tutor_batch_assignment_submissions', [
				'task_id' => $task_id,
				'student_user_id' => $student_user_id
			])
			->row_array();
        if ($existing && (int)($existing['submission_attempt'] ?? 1) >= 1 + (int)($task['max_retakes'] ?? 0)) {
            return ['status'=>false,'message'=>'This assignment does not allow another submission.','batch_id'=>$batch_id];
        }

		$now = date('Y-m-d H:i:s');

		$data = [
			'task_id' => $task_id,
			'batch_id' => $batch_id,
			'student_user_id' => $student_user_id,
			'external_link' => $external_link ?: null,
			'student_comment' => $student_comment ?: null,
			'submission_status' => empty($existing) ? 'submitted' : 'resubmitted',
            'submission_attempt' => empty($existing) ? 1 : ((int)($existing['submission_attempt'] ?? 1) + 1),
			'submitted_at' => $now,
			'evaluation_status' => 'pending',
            'plagiarism_status' => !empty($task['plagiarism_check_enabled']) ? 'queued' : 'not_checked',
			'updated_at' => $now
		];

		if ($uploaded_file !== null) {
			$data['submission_file'] = $uploaded_file;
		}

		if (empty($existing)) {
			$data['created_at'] = $now;
			$this->db->insert('tutor_batch_assignment_submissions', $data);
		} else {
			$this->db
				->where('id', (int)$existing['id'])
				->update('tutor_batch_assignment_submissions', $data);
		}

		$this->db
			->where('task_id', $task_id)
			->where('student_user_id', $student_user_id)
			->update('tutor_batch_task_students', [
				'assignment_status' => 'submitted',
				'updated_at' => $now
			]);

		return [
			'status' => true,
			'message' => 'Assignment submitted successfully.',
			'batch_id' => $batch_id
		];
	}

	public function get_assignment_submissions_for_batch(int $batch_id, int $tutor_user_id): array
	{
		$batch = $this->get_batch($batch_id, $tutor_user_id);

		if (empty($batch)) {
			return [];
		}

		return $this->db
			->select('
				sub.*,
				t.title AS task_title,
				t.max_marks,
				t.due_at,
				u.first_name,
				u.last_name,
				u.email,
				u.phone
			')
			->from('tutor_batch_assignment_submissions sub')
			->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
			->join('users u', 'u.id = sub.student_user_id', 'left')
			->where('sub.batch_id', $batch_id)
			->where('t.tutor_user_id', $tutor_user_id)
			->where('t.task_type', 'assignment')
			->order_by('sub.submitted_at', 'DESC')
			->get()
			->result_array();
	}

    /**
     * Phase G Batch 360 Foundation.
     * Builds the tutor's main batch working snapshot using existing tables only.
     */
    public function get_batch_360_foundation(int $batch_id, int $tutor_user_id): array
    {
        $batch = $this->get_batch($batch_id, $tutor_user_id);
        if (empty($batch)) {
            return [];
        }

        $attendance_summary = $this->db
            ->select('
                COUNT(*) AS total_records,
                SUM(CASE WHEN status IN ("present","late") THEN 1 ELSE 0 END) AS present_records,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) AS absent_records,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) AS late_records
            ', false)
            ->where('batch_id', $batch_id)
            ->get('tutor_batch_attendance')
            ->row_array();

        $total_attendance = (int)($attendance_summary['total_records'] ?? 0);
        $present_attendance = (int)($attendance_summary['present_records'] ?? 0);
        $attendance_percent = $total_attendance > 0 ? round(($present_attendance / $total_attendance) * 100, 1) : 0;

        $marks_summary = $this->db
            ->select('
                COUNT(sub.id) AS submissions,
                SUM(CASE WHEN sub.evaluation_status = "evaluated" THEN 1 ELSE 0 END) AS evaluated,
                AVG(CASE WHEN sub.evaluation_status = "evaluated" THEN sub.marks_obtained ELSE NULL END) AS average_marks,
                AVG(CASE WHEN sub.evaluation_status = "evaluated" AND t.max_marks > 0 THEN (sub.marks_obtained / t.max_marks) * 100 ELSE NULL END) AS average_percent
            ', false)
            ->from('tutor_batch_assignment_submissions sub')
            ->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
            ->where('sub.batch_id', $batch_id)
            ->where('t.tutor_user_id', $tutor_user_id)
            ->get()
            ->row_array();

        $tests_summary = $this->db
            ->select('COUNT(*) AS tests, SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) AS published_tests', false)
            ->where('batch_id', $batch_id)
            ->where('tutor_user_id', $tutor_user_id)
            ->get('tutor_batch_tests')
            ->row_array();

        $communication_log = [];
        $recent_invites = $this->db
            ->select('id, invite_status AS status, invite_email, email_sent_at, whatsapp_sent_at, created_at, updated_at')
            ->where('batch_id', $batch_id)
            ->order_by('id', 'DESC')
            ->limit(5)
            ->get('tutor_batch_invites')
            ->result_array();
        foreach ($recent_invites as $invite) {
            $communication_log[] = [
                'type' => 'Invite',
                'title' => $invite['invite_email'] ?? 'Student invite',
                'status' => $invite['status'] ?? '',
                'channel' => !empty($invite['email_sent_at']) ? 'Email sent' : (!empty($invite['whatsapp_sent_at']) ? 'WhatsApp sent' : 'Created'),
                'created_at' => $invite['updated_at'] ?: ($invite['created_at'] ?? ''),
            ];
        }

        $recent_sessions = $this->db
            ->select('id, title, session_status, session_date, start_time, created_at, updated_at')
            ->where('batch_id', $batch_id)
            ->where('tutor_user_id', $tutor_user_id)
            ->order_by('session_date', 'DESC')
            ->order_by('start_time', 'DESC')
            ->limit(5)
            ->get('tutor_batch_sessions')
            ->result_array();
        foreach ($recent_sessions as $session) {
            $communication_log[] = [
                'type' => 'Session',
                'title' => $session['title'] ?? 'Session',
                'status' => $session['session_status'] ?? '',
                'channel' => 'Batch notification',
                'created_at' => trim(($session['session_date'] ?? '') . ' ' . ($session['start_time'] ?? '')),
            ];
        }

        $recent_tasks = $this->db
            ->select('id, title, task_type, evaluation_status, created_at, updated_at')
            ->where('batch_id', $batch_id)
            ->where('tutor_user_id', $tutor_user_id)
            ->order_by('id', 'DESC')
            ->limit(5)
            ->get('tutor_batch_tasks')
            ->result_array();
        foreach ($recent_tasks as $task) {
            $communication_log[] = [
                'type' => ucfirst($task['task_type'] ?? 'Task'),
                'title' => $task['title'] ?? 'Task',
                'status' => $task['evaluation_status'] ?? '',
                'channel' => 'Student notification',
                'created_at' => $task['updated_at'] ?: ($task['created_at'] ?? ''),
            ];
        }

        usort($communication_log, function ($a, $b) {
            return strtotime((string)($b['created_at'] ?? '')) <=> strtotime((string)($a['created_at'] ?? ''));
        });

        return [
            'attendance' => [
                'total_records' => $total_attendance,
                'present_records' => $present_attendance,
                'absent_records' => (int)($attendance_summary['absent_records'] ?? 0),
                'late_records' => (int)($attendance_summary['late_records'] ?? 0),
                'attendance_percent' => $attendance_percent,
            ],
            'marks' => [
                'submissions' => (int)($marks_summary['submissions'] ?? 0),
                'evaluated' => (int)($marks_summary['evaluated'] ?? 0),
                'average_marks' => round((float)($marks_summary['average_marks'] ?? 0), 1),
                'average_percent' => round((float)($marks_summary['average_percent'] ?? 0), 1),
            ],
            'tests' => [
                'tests' => (int)($tests_summary['tests'] ?? 0),
                'published_tests' => (int)($tests_summary['published_tests'] ?? 0),
            ],
            'communication_log' => array_slice($communication_log, 0, 10),
        ];
    }

	public function evaluate_assignment_submission(int $submission_id, int $tutor_user_id, array $input): array
	{
		$submission = $this->db
			->select('sub.*, t.max_marks, t.tutor_user_id, t.due_at, t.late_policy, t.late_penalty_percent')
			->from('tutor_batch_assignment_submissions sub')
			->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
			->where('sub.id', $submission_id)
			->where('t.tutor_user_id', $tutor_user_id)
			->get()
			->row_array();

		if (empty($submission)) {
			return [
				'status' => false,
				'message' => 'Submission not found or access denied.',
				'batch_id' => 0
			];
		}

		$marks = isset($input['marks_obtained']) ? (float)$input['marks_obtained'] : 0;
		$max_marks = isset($submission['max_marks']) ? (float)$submission['max_marks'] : 0;

		if ($marks < 0) {
			return [
				'status' => false,
				'message' => 'Marks cannot be negative.',
				'batch_id' => (int)$submission['batch_id']
			];
		}

		if ($max_marks > 0 && $marks > $max_marks) {
			return [
				'status' => false,
				'message' => 'Marks cannot be greater than maximum marks.',
				'batch_id' => (int)$submission['batch_id']
			];
		}
        $late_penalty = 0;
        if (!empty($submission['due_at']) && strtotime($submission['submitted_at']) > strtotime($submission['due_at']) && ($submission['late_policy'] ?? '') === 'deduct') {
            $late_penalty = round($marks * ((float)$submission['late_penalty_percent'] / 100), 2);
            $marks = max(0, $marks - $late_penalty);
        }

		$now = date('Y-m-d H:i:s');
		$this->load->model('Idempotency_model', 'idempotency');
		$this->load->model('Immutable_audit_model', 'immutable_audit');
		$operation = [
			'submission_id' => $submission_id,
			'marks_obtained' => $marks,
			'tutor_remarks' => trim((string)($input['tutor_remarks'] ?? $input['tutor_feedback'] ?? '')),
		];
		$key = $this->idempotency->key('grading.assignment', $operation, (string)($input['idempotency_key'] ?? ''));
		$this->db->trans_begin();
		$claim = $this->idempotency->claim('grading.assignment', $key, $tutor_user_id, $operation);
		if (empty($claim['proceed'])) {
			$this->db->trans_rollback();
			return $claim['response'];
		}

		$grade_update = [
			'marks_obtained' => $marks,
			'tutor_remarks' => trim((string)($input['tutor_remarks'] ?? $input['tutor_feedback'] ?? '')),
			'evaluation_status' => 'evaluated',
			'evaluated_at' => $now,
			'evaluated_by' => $tutor_user_id,
            'moderation_status' => in_array($submission['plagiarism_status'] ?? '', ['review','blocked'], true) ? 'pending' : 'not_required',
			'updated_at' => $now
		];
		if ($this->db->field_exists('grading_idempotency_key', 'tutor_batch_assignment_submissions')) $grade_update['grading_idempotency_key'] = $key;
		$this->db->where('id', $submission_id)->update('tutor_batch_assignment_submissions', $grade_update);

		$this->db
			->where('task_id', (int)$submission['task_id'])
			->where('student_user_id', (int)$submission['student_user_id'])
			->update('tutor_batch_task_students', [
				'assignment_status' => 'evaluated',
				'updated_at' => $now
			]);

		$response = [
			'status' => true,
			'message' => 'Assignment evaluated successfully.' . ($late_penalty > 0 ? ' Late penalty applied: ' . $late_penalty . ' marks.' : ''),
			'batch_id' => (int)$submission['batch_id']
		];
		$this->immutable_audit->record('grading', 'assignment_evaluated', 'assignment_submission', $submission_id, $submission, array_merge($submission, $grade_update), [
			'actor_user_id' => $tutor_user_id,
			'actor_role' => 'tutor',
			'idempotency_key' => $key,
		]);
		$this->idempotency->complete((int)$claim['id'], $response, 'assignment_submission', $submission_id);
		if ($this->db->trans_status()) $this->db->trans_commit(); else $this->db->trans_rollback();
		return $response;
	}

	public function get_student_progress_summary(int $batch_id, int $student_user_id): array
	{
		$total_assignments = (int)$this->db
			->from('tutor_batch_task_students ts')
			->join('tutor_batch_tasks t', 't.id = ts.task_id', 'inner')
			->where('t.batch_id', $batch_id)
			->where('ts.student_user_id', $student_user_id)
			->where('t.task_type', 'assignment')
			->count_all_results();

		$completed_assignments = (int)$this->db
			->from('tutor_batch_task_students ts')
			->join('tutor_batch_tasks t', 't.id = ts.task_id', 'inner')
			->where('t.batch_id', $batch_id)
			->where('ts.student_user_id', $student_user_id)
			->where('t.task_type', 'assignment')
			->where_in('ts.assignment_status', ['submitted', 'evaluated'])
			->count_all_results();

		$assignment_percent = $total_assignments > 0
			? round(($completed_assignments / $total_assignments) * 100, 2)
			: 0;

		$test_row = $this->db
			->select_avg('percentage', 'avg_percentage')
			->where('batch_id', $batch_id)
			->where('student_user_id', $student_user_id)
			->where('status', 'submitted')
			->get('tutor_batch_test_attempts')
			->row_array();

		$test_percent = !empty($test_row['avg_percentage'])
			? round((float)$test_row['avg_percentage'], 2)
			: 0;

		$attendance_percent = $this->get_student_attendance_percent($batch_id, $student_user_id);

		$overall_percent = round(($assignment_percent + $test_percent + $attendance_percent) / 3, 2);

		return [
			'total_assignments' => $total_assignments,
			'completed_assignments' => $completed_assignments,
			'assignment_percent' => $assignment_percent,
			'test_percent' => $test_percent,
			'attendance_percent' => $attendance_percent,
			'overall_percent' => $overall_percent
		];
	}

    /**
     * Phase H progress report foundation.
     * Per-student report data for tutors; PDF export and parent dashboard come later.
     */
    public function get_batch_student_progress_reports(int $batch_id, int $tutor_user_id): array
    {
        $batch = $this->get_batch($batch_id, $tutor_user_id);
        if (empty($batch)) {
            return [];
        }

        $students = $this->get_batch_students($batch_id);
        $reports = [];

        foreach ($students as $student) {
            $student_user_id = (int)($student['student_user_id'] ?? 0);
            if ($student_user_id <= 0) {
                continue;
            }

            $summary = $this->get_student_progress_summary($batch_id, $student_user_id);

            $test_attempts = $this->db
                ->select('t.title, a.score, a.total_marks, a.percentage, a.submitted_at')
                ->from('tutor_batch_test_attempts a')
                ->join('tutor_batch_tests t', 't.id = a.test_id', 'left')
                ->where('a.batch_id', $batch_id)
                ->where('a.student_user_id', $student_user_id)
                ->where('a.status', 'submitted')
                ->order_by('a.submitted_at', 'ASC')
                ->get()
                ->result_array();

            $trend_label = 'No submitted tests yet';
            $trend_direction = 'neutral';
            $trend_delta = 0;
            $attempt_count = count($test_attempts);
            if ($attempt_count >= 2) {
                $previous = (float)($test_attempts[$attempt_count - 2]['percentage'] ?? 0);
                $latest = (float)($test_attempts[$attempt_count - 1]['percentage'] ?? 0);
                $trend_delta = round($latest - $previous, 2);
                if ($trend_delta > 0) {
                    $trend_label = 'Improving +' . $trend_delta . '%';
                    $trend_direction = 'up';
                } elseif ($trend_delta < 0) {
                    $trend_label = 'Declining ' . $trend_delta . '%';
                    $trend_direction = 'down';
                } else {
                    $trend_label = 'Stable';
                }
            } elseif ($attempt_count === 1) {
                $trend_label = 'First score ' . round((float)($test_attempts[0]['percentage'] ?? 0), 2) . '%';
            }

            $teacher_remarks = $this->db
                ->select('t.title AS task_title, sub.tutor_remarks, sub.marks_obtained, t.max_marks, sub.evaluated_at')
                ->from('tutor_batch_assignment_submissions sub')
                ->join('tutor_batch_tasks t', 't.id = sub.task_id', 'inner')
                ->where('sub.batch_id', $batch_id)
                ->where('sub.student_user_id', $student_user_id)
                ->where('t.tutor_user_id', $tutor_user_id)
                ->where('sub.evaluation_status', 'evaluated')
                ->where('sub.tutor_remarks IS NOT NULL', null, false)
                ->where('sub.tutor_remarks !=', '')
                ->order_by('sub.evaluated_at', 'DESC')
                ->limit(5)
                ->get()
                ->result_array();

            $reports[] = [
                'student' => $student,
                'summary' => $summary,
                'test_attempts' => $test_attempts,
                'test_trend' => [
                    'label' => $trend_label,
                    'direction' => $trend_direction,
                    'delta' => $trend_delta,
                ],
                'teacher_remarks' => $teacher_remarks,
            ];
        }

        return $reports;
    }
	
	public function create_batch_test(int $batch_id, int $tutor_user_id, array $input): array
	{
		$batch = $this->get_batch($batch_id, $tutor_user_id);

		if (empty($batch)) {
			return [
				'status' => false,
				'message' => 'Batch not found or access denied.'
			];
		}

		$title = trim((string)($input['title'] ?? ''));

		if ($title === '') {
			return [
				'status' => false,
				'message' => 'Test title is required.'
			];
		}

		$now = date('Y-m-d H:i:s');

		$this->db->insert('tutor_batch_tests', [
			'batch_id' => $batch_id,
			'tutor_user_id' => $tutor_user_id,
			'title' => $title,
			'description' => trim((string)($input['description'] ?? '')),
			'duration_minutes' => max(1, (int)($input['duration_minutes'] ?? 30)),
			'status' => 'draft',
			'created_at' => $now,
			'updated_at' => $now
		]);

		return [
			'status' => true,
			'message' => 'Test created successfully.'
		];
	}

	public function get_batch_tests(int $batch_id, int $tutor_user_id = 0): array
	{
		if ($tutor_user_id > 0) {
			$this->db->where('tutor_user_id', $tutor_user_id);
		}

		$tests = $this->db
			->where('batch_id', $batch_id)
			->order_by('id', 'DESC')
			->get('tutor_batch_tests')
			->result_array();

		foreach ($tests as &$test) {
			$test['questions'] = $this->db
				->where('test_id', (int)$test['id'])
				->order_by('id', 'ASC')
				->get('tutor_batch_test_questions')
				->result_array();
		}

		return $tests;
	}

	public function add_test_question(int $test_id, int $tutor_user_id, array $input): array
	{
		$test = $this->db
			->where('id', $test_id)
			->where('tutor_user_id', $tutor_user_id)
			->get('tutor_batch_tests')
			->row_array();

		if (empty($test)) {
			return [
				'status' => false,
				'message' => 'Test not found or access denied.',
				'batch_id' => 0
			];
		}

		$question_text = trim((string)($input['question_text'] ?? ''));
		$correct_option = strtoupper(trim((string)($input['correct_option'] ?? '')));

		if ($question_text === '' || !in_array($correct_option, ['A', 'B', 'C', 'D'])) {
			return [
				'status' => false,
				'message' => 'Question and correct option are required.',
				'batch_id' => (int)$test['batch_id']
			];
		}

		$now = date('Y-m-d H:i:s');

		$this->db->insert('tutor_batch_test_questions', [
			'test_id' => $test_id,
			'question_text' => $question_text,
			'option_a' => trim((string)($input['option_a'] ?? '')),
			'option_b' => trim((string)($input['option_b'] ?? '')),
			'option_c' => trim((string)($input['option_c'] ?? '')),
			'option_d' => trim((string)($input['option_d'] ?? '')),
			'correct_option' => $correct_option,
			'marks' => max(1, (float)($input['marks'] ?? 1)),
			'created_at' => $now,
			'updated_at' => $now
		]);

		$total_marks = $this->db
			->select_sum('marks')
			->where('test_id', $test_id)
			->get('tutor_batch_test_questions')
			->row()
			->marks;

		$this->db->where('id', $test_id)->update('tutor_batch_tests', [
			'total_marks' => (float)$total_marks,
			'updated_at' => $now
		]);

		return [
			'status' => true,
			'message' => 'Question added successfully.',
			'batch_id' => (int)$test['batch_id']
		];
	}

	public function publish_test(int $test_id, int $tutor_user_id): array
	{
		$test = $this->db
			->where('id', $test_id)
			->where('tutor_user_id', $tutor_user_id)
			->get('tutor_batch_tests')
			->row_array();

		if (empty($test)) {
			return [
				'status' => false,
				'message' => 'Test not found or access denied.',
				'batch_id' => 0
			];
		}

		$question_count = $this->db
			->where('test_id', $test_id)
			->count_all_results('tutor_batch_test_questions');

		if ($question_count <= 0) {
			return [
				'status' => false,
				'message' => 'Add at least one question before publishing.',
				'batch_id' => (int)$test['batch_id']
			];
		}

		$this->db->where('id', $test_id)->update('tutor_batch_tests', [
			'status' => 'published',
			'updated_at' => date('Y-m-d H:i:s')
		]);

        $this->notify_accepted_students_for_batch(
            (int)$test['batch_id'],
            'test',
            $test_id,
            'New online test published',
            'A new online test "' . ($test['title'] ?? 'Test') . '" is available in your batch.',
            site_url('student_batch/view/' . (int)$test['batch_id'])
        );

		return [
			'status' => true,
			'message' => 'Test published successfully.',
			'batch_id' => (int)$test['batch_id']
		];
	}

	public function get_student_tests_for_batch(int $batch_id, int $student_user_id): array
	{
        if (!$this->is_student_active_in_batch($batch_id, $student_user_id)) {
            return [];
        }

		$tests = $this->db
			->select('t.*, a.id AS attempt_id, a.score, a.total_marks AS attempt_total_marks, a.percentage, a.status AS attempt_status, a.submitted_at')
			->from('tutor_batch_tests t')
			->join('tutor_batch_test_attempts a', 'a.test_id = t.id AND a.student_user_id = ' . (int)$student_user_id, 'left')
			->where('t.batch_id', $batch_id)
			->where('t.status', 'published')
			->order_by('t.id', 'DESC')
			->get()
			->result_array();

		return $tests;
	}

	public function start_student_test(int $test_id, int $student_user_id): array
	{
		$test = $this->db
			->where('id', $test_id)
			->where('status', 'published')
			->get('tutor_batch_tests')
			->row_array();

		if (empty($test)) {
			return [];
		}

		if (!$this->is_student_active_in_batch((int)$test['batch_id'], $student_user_id)) {
			return [];
		}

        $question_count = (int)$this->db
            ->where('test_id', $test_id)
            ->count_all_results('tutor_batch_test_questions');

        if ($question_count <= 0) {
            return [];
        }

		$attempt = $this->db
			->where('test_id', $test_id)
			->where('student_user_id', $student_user_id)
			->get('tutor_batch_test_attempts')
			->row_array();

		if (!empty($attempt) && $attempt['status'] === 'submitted') {
			return [];
		}

		$now = date('Y-m-d H:i:s');

		if (empty($attempt)) {
			$this->db->insert('tutor_batch_test_attempts', [
				'test_id' => $test_id,
				'batch_id' => (int)$test['batch_id'],
				'student_user_id' => $student_user_id,
				'started_at' => $now,
				'total_marks' => (float)$test['total_marks'],
				'status' => 'in_progress',
				'created_at' => $now,
				'updated_at' => $now
			]);

			$attempt_id = $this->db->insert_id();

			$attempt = $this->db
				->where('id', $attempt_id)
				->get('tutor_batch_test_attempts')
				->row_array();
		}

		$questions = $this->db
			->select('id, question_text, option_a, option_b, option_c, option_d, marks')
			->where('test_id', $test_id)
			->order_by('id', 'ASC')
			->get('tutor_batch_test_questions')
			->result_array();

		return [
			'test' => $test,
			'attempt' => $attempt,
			'questions' => $questions
		];
	}

	public function submit_student_test(int $attempt_id, int $student_user_id, array $input): array
	{
		$attempt = $this->db
			->where('id', $attempt_id)
			->where('student_user_id', $student_user_id)
			->where('status', 'in_progress')
			->get('tutor_batch_test_attempts')
			->row_array();

		if (empty($attempt)) {
			return [
				'status' => false,
				'message' => 'Attempt not found or already submitted.',
				'batch_id' => 0
			];
		}

		$test = $this->db
			->where('id', (int)$attempt['test_id'])
			->get('tutor_batch_tests')
			->row_array();

		if (empty($test)) {
			return [
				'status' => false,
				'message' => 'Test not found.',
				'batch_id' => (int)$attempt['batch_id']
			];
		}

        if (!$this->is_student_active_in_batch((int)$attempt['batch_id'], $student_user_id)) {
            return [
                'status' => false,
                'message' => 'You are not authorized to submit this test.',
                'batch_id' => (int)$attempt['batch_id']
            ];
        }

        $started_at = strtotime((string)$attempt['started_at']);
        $allowed_seconds = max(1, (int)$test['duration_minutes']) * 60 + 120;
        if ($started_at && time() > ($started_at + $allowed_seconds)) {
            $this->db->where('id', $attempt_id)->update('tutor_batch_test_attempts', [
                'submitted_at' => date('Y-m-d H:i:s'),
                'status' => 'submitted',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'status' => false,
                'message' => 'Test time has expired. Your attempt was closed.',
                'batch_id' => (int)$attempt['batch_id']
            ];
        }

		$questions = $this->db
			->where('test_id', (int)$test['id'])
			->get('tutor_batch_test_questions')
			->result_array();

		$answers = $input['answers'] ?? [];
		$score = 0;
		$total_marks = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($questions as $question) {
			$question_id = (int)$question['id'];
			$selected = strtoupper(trim((string)($answers[$question_id] ?? '')));
			$correct = strtoupper($question['correct_option']);
			$marks = (float)$question['marks'];

			$is_correct = ($selected === $correct) ? 1 : 0;
			$marks_obtained = $is_correct ? $marks : 0;

			$score += $marks_obtained;
			$total_marks += $marks;

			$this->db->insert('tutor_batch_test_answers', [
				'attempt_id' => $attempt_id,
				'test_id' => (int)$test['id'],
				'question_id' => $question_id,
				'student_user_id' => $student_user_id,
				'selected_option' => in_array($selected, ['A', 'B', 'C', 'D']) ? $selected : null,
				'correct_option' => $correct,
				'is_correct' => $is_correct,
				'marks_obtained' => $marks_obtained,
				'created_at' => $now
			]);
		}

		$percentage = $total_marks > 0 ? round(($score / $total_marks) * 100, 2) : 0;

		$this->db->where('id', $attempt_id)->update('tutor_batch_test_attempts', [
			'submitted_at' => $now,
			'score' => $score,
			'total_marks' => $total_marks,
			'percentage' => $percentage,
			'status' => 'submitted',
			'updated_at' => $now
		]);

		return [
			'status' => true,
			'message' => 'Test submitted successfully. Your score is ' . $score . ' / ' . $total_marks . ' (' . $percentage . '%).',
			'batch_id' => (int)$attempt['batch_id']
		];
	}
	
	public function get_session_attendance_page_data(int $session_id, int $tutor_user_id): array
	{
		$session = $this->db
			->select('s.*, b.title AS batch_title')
			->from('tutor_batch_sessions s')
			->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
			->where('s.id', $session_id)
			->where('b.tutor_user_id', $tutor_user_id)
			->get()
			->row_array();

		if (empty($session)) {
			return [];
		}

		$students = $this->db
			->select('
				bs.student_user_id,
				bs.membership_status AS batch_student_status,
				u.first_name,
				u.last_name,
				u.email,
				a.status AS attendance_status
			')
			->from('tutor_batch_students bs')
			->join('users u', 'u.id = bs.student_user_id', 'left')
			->join(
				'tutor_batch_attendance a',
				'a.session_id = ' . (int)$session_id . ' AND a.student_user_id = bs.student_user_id',
				'left'
			)
			->where('bs.batch_id', (int)$session['batch_id'])
			->where_in('bs.membership_status', ['active','completed'])
			->order_by('u.first_name', 'ASC')
			->get()
			->result_array();

		return [
			'session' => $session,
			'students' => $students
		];
	}

	public function save_session_attendance(int $session_id, int $tutor_user_id, array $input): array
	{
		$session = $this->db
			->select('s.*, b.tutor_user_id')
			->from('tutor_batch_sessions s')
			->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
			->where('s.id', $session_id)
			->where('b.tutor_user_id', $tutor_user_id)
			->get()
			->row_array();

		if (empty($session)) {
			return [
				'status' => false,
				'message' => 'Session not found or access denied.',
				'batch_id' => 0
			];
		}

		$attendance = $input['attendance'] ?? [];
		$now = date('Y-m-d H:i:s');
		$this->load->model('Idempotency_model', 'idempotency');
		$this->load->model('Immutable_audit_model', 'immutable_audit');
		$operation = ['session_id' => $session_id, 'attendance' => $attendance];
		$key = $this->idempotency->key('attendance.save', $operation, (string)($input['idempotency_key'] ?? ''));
		$this->db->trans_begin();
		$claim = $this->idempotency->claim('attendance.save', $key, $tutor_user_id, $operation);
		if (empty($claim['proceed'])) {
			$this->db->trans_rollback();
			return $claim['response'];
		}

		$students = $this->db
			->where('batch_id', (int)$session['batch_id'])
			->where_in('membership_status', ['active','completed'])
			->get('tutor_batch_students')
			->result_array();

		foreach ($students as $student) {
			$student_user_id = (int)$student['student_user_id'];
			$status = $attendance[$student_user_id] ?? 'absent';

			if (!in_array($status, ['present', 'absent', 'late'])) {
				$status = 'absent';
			}

			$existing = $this->db
				->where('session_id', $session_id)
				->where('student_user_id', $student_user_id)
				->get('tutor_batch_attendance')
				->row_array();

			$data = [
				'batch_id' => (int)$session['batch_id'],
				'session_id' => $session_id,
				'student_user_id' => $student_user_id,
				'status' => $status,
				'marked_by' => $tutor_user_id,
				'marked_at' => $now,
				'updated_at' => $now
			];
			if ($this->db->field_exists('idempotency_key', 'tutor_batch_attendance')) $data['idempotency_key'] = substr($key . ':' . $student_user_id, 0, 128);

			if (empty($existing)) {
				$data['created_at'] = $now;
				$this->db->insert('tutor_batch_attendance', $data);
			} else {
				$this->db
					->where('id', (int)$existing['id'])
					->update('tutor_batch_attendance', $data);
			}
			$this->immutable_audit->record('attendance', empty($existing) ? 'marked' : 'changed', 'tutor_batch_attendance', empty($existing) ? $this->db->insert_id() : (int)$existing['id'], $existing ?: [], $data, [
				'actor_user_id' => $tutor_user_id,
				'actor_role' => 'tutor',
				'idempotency_key' => $key,
			]);
			if ($status === 'absent') {
				$reminder_key = 'absence:' . $session_id . ':' . $student_user_id;
				$this->db->query("INSERT IGNORE INTO teacher_automated_reminders(tutor_user_id,batch_id,reminder_type,reference_type,reference_id,recipient_user_id,scheduled_at,channels_json,dedupe_key) VALUES(?,?,'absence','session',?,?,?,'[\"email\",\"in_app\"]',?)", [
					$tutor_user_id, (int)$session['batch_id'], $session_id, $student_user_id, date('Y-m-d H:i:s', strtotime('+1 hour')), $reminder_key
				]);
			}

			$this->update_student_progress_cache((int)$session['batch_id'], $student_user_id);
		}

		$response = [
			'status' => true,
			'message' => 'Attendance saved successfully.',
			'batch_id' => (int)$session['batch_id']
		];
		$this->idempotency->complete((int)$claim['id'], $response, 'tutor_batch_session', $session_id);
		if ($this->db->trans_status()) $this->db->trans_commit(); else $this->db->trans_rollback();
		return $response;
	}

	public function get_student_attendance_percent(int $batch_id, int $student_user_id): float
	{
		$total_sessions = (int)$this->db
			->where('batch_id', $batch_id)
			->count_all_results('tutor_batch_sessions');

		if ($total_sessions <= 0) {
			return 0;
		}

		$present_sessions = (int)$this->db
			->where('batch_id', $batch_id)
			->where('student_user_id', $student_user_id)
			->where_in('status', ['present', 'late'])
			->count_all_results('tutor_batch_attendance');

		return round(($present_sessions / $total_sessions) * 100, 2);
	}

	public function update_student_progress_cache(int $batch_id, int $student_user_id): void
	{
		$summary = $this->get_student_progress_summary($batch_id, $student_user_id);

		$this->db
			->where('batch_id', $batch_id)
			->where('student_user_id', $student_user_id)
			->update('tutor_batch_students', [
				'progress_percent' => $summary['overall_percent'],
				'attendance_percent' => $summary['attendance_percent']
			]);
	}
	
	public function update_session_links(int $session_id, int $tutor_user_id, array $input): array
	{
		$session = $this->db
			->select('s.*, b.tutor_user_id')
			->from('tutor_batch_sessions s')
			->join('tutor_batches b', 'b.id = s.batch_id', 'inner')
			->where('s.id', $session_id)
			->where('b.tutor_user_id', $tutor_user_id)
			->get()
			->row_array();

		if (empty($session)) {
			return [
				'status' => false,
				'message' => 'Session not found or access denied.',
				'batch_id' => 0
			];
		}

		$meeting_url = $this->sanitize_external_url((string)($input['meeting_url'] ?? ($input['student_join_url'] ?? '')));
		$recording_url = $this->sanitize_external_url((string)($input['recording_url'] ?? ''));
		$captions_url = $this->sanitize_external_url((string)($input['captions_url'] ?? ''));
		$session_status = trim((string)($input['session_status'] ?? 'scheduled'));

		if (!in_array($session_status, ['draft', 'published', 'scheduled', 'live', 'completed', 'cancelled'], true)) {
			$session_status = 'scheduled';
		}

        $was_recording_available = (($session['recording_status'] ?? '') === 'available' && !empty($session['recording_url']));
        $recording_changed = $recording_url !== '' && $recording_url !== (string)($session['recording_url'] ?? '');

		$data = [
			'meeting_url' => $meeting_url ?: null,
            'student_join_url' => $meeting_url ?: null,
			'recording_url' => $recording_url ?: null,
			'captions_url' => $captions_url ?: null,
			'recording_expires_at' => !empty($input['recording_expires_at']) ? date('Y-m-d H:i:s', strtotime((string)$input['recording_expires_at'])) : null,
			'fallback_join_url' => $this->sanitize_external_url((string)($input['fallback_join_url'] ?? ($session['fallback_join_url'] ?? ''))) ?: null,
			'recording_status' => $recording_url !== '' ? 'available' : 'not_available',
			'session_status' => $session_status,
			'updated_at' => date('Y-m-d H:i:s')
		];

		$this->db->where('id', $session_id)->update('tutor_batch_sessions', $data);

        if ($recording_url !== '' && (!$was_recording_available || $recording_changed)) {
            $this->notify_accepted_students_for_batch(
                (int)$session['batch_id'],
                'recording',
                $session_id,
                'Class recording available',
                'The recording for session "' . ($session['title'] ?? 'Session') . '" is now available.',
                site_url('student_batch/view/' . (int)$session['batch_id'])
            );
        }

		return [
			'status' => true,
			'message' => 'Session links updated successfully.',
			'batch_id' => (int)$session['batch_id']
		];
	}

	public function get_live_session_join_url(int $session_id, int $student_user_id): array
	{
		$session = $this->db
			->select('s.*')
			->from('tutor_batch_sessions s')
			->join('tutor_batch_students bs', 'bs.batch_id = s.batch_id', 'inner')
			->where('s.id', $session_id)
			->where('bs.student_user_id', $student_user_id)
			->where_in('bs.membership_status', ['active','completed'])
			->get()
			->row_array();

		if (empty($session)) {
			return [
				'status' => false,
				'message' => 'Session not found or access denied.',
				'batch_id' => 0
			];
		}

        $meeting_url = $this->sanitize_external_url((string)($session['meeting_url'] ?? ($session['student_join_url'] ?? '')));

		if ($meeting_url === '') {
			return [
				'status' => false,
				'message' => 'Meeting link is not available yet.',
				'batch_id' => (int)$session['batch_id']
			];
		}

		$now = time();
		$start = $this->get_session_start_timestamp($session);
		$end = $this->get_session_end_timestamp($session);
		if (!$start || !$end) {
			return [
				'status' => false,
				'message' => 'Session time is not configured correctly.',
				'batch_id' => (int)$session['batch_id']
			];
		}

		$join_window_start = $start - (10 * 60);
		$join_window_end = $end + (15 * 60);

		if ($now < $join_window_start) {
			return [
				'status' => false,
				'message' => 'Session is not live yet.',
				'batch_id' => (int)$session['batch_id']
			];
		}

		if ($now > $join_window_end) {
			return [
				'status' => false,
				'message' => 'Session has ended.',
				'batch_id' => (int)$session['batch_id']
			];
		}

		return [
			'status' => true,
			'message' => 'Joining session.',
			'batch_id' => (int)$session['batch_id'],
			'meeting_url' => $meeting_url
		];
	}



    /**
     * Central notification helper for LMS batch features.
     * Security/performance:
     * - Only accepted/active batch students are selected.
     * - Uses bulk insert when Notification_model supports it.
     * - Falls back to the existing notifications table used by the current theme.
     * - No expensive joins are used.
     */
    private function notify_accepted_students_for_batch(int $batch_id, string $reference_type, int $reference_id, string $title, string $message, string $target_url = ''): int
    {
        $rows = $this->db
            ->select('student_user_id')
            ->where('batch_id', $batch_id)
            ->where_in('membership_status', ['active', 'completed'])
            ->get('tutor_batch_students')
            ->result_array();

        $student_ids = array_map('intval', array_column($rows, 'student_user_id'));

        return $this->notify_students_by_ids($student_ids, $batch_id, $reference_type, $reference_id, $title, $message, $target_url);
    }

    private function notify_students_by_ids(array $student_ids, int $batch_id, string $reference_type, int $reference_id, string $title, string $message, string $target_url = ''): int
    {
        $student_ids = array_values(array_unique(array_filter(array_map('intval', $student_ids))));

        if (empty($student_ids) || trim($title) === '') {
            return 0;
        }

        $created = 0;
        $payload = [
            'batch_id' => $batch_id,
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'title' => trim($title),
            'message' => trim($message),
            'target_url' => trim($target_url)
        ];

        if (isset($this->notification_model) && method_exists($this->notification_model, 'create_bulk_notifications')) {
            $created += (int)$this->notification_model->create_bulk_notifications($student_ids, $payload);
        }

        // Backward-compatible fallback for the existing theme notification dropdown.
        if ($this->db->table_exists('notifications')) {
            $now = time();
            $legacy_rows = [];
            $notification_fields = $this->db->list_fields('notifications');

            foreach ($student_ids as $student_id) {
                $row = [
                    'to_user' => $student_id,
                    'title' => $payload['title'],
                    'description' => $payload['message'],
                    'type' => $reference_type,
                    'status' => 0,
                    'created_at' => $now
                ];

                if (in_array('from_user', $notification_fields, true)) {
                    $row['from_user'] = (int)$this->session->userdata('user_id');
                }

                if (in_array('updated_at', $notification_fields, true)) {
                    $row['updated_at'] = $now;
                }

                $legacy_rows[] = array_intersect_key($row, array_flip($notification_fields));
            }

            if (!empty($legacy_rows)) {
                $this->db->insert_batch('notifications', $legacy_rows);
                $created += max(0, (int)$this->db->affected_rows());
            }
        }

        return $created;
    }

    private function trigger_batch_communication(int $batch_id, string $event, int $reference_id = 0, array $old_batch = []): void
    {
        if ($batch_id <= 0 || !$this->db->table_exists('message_campaigns')) {
            return;
        }
        try {
            $this->load->library('Communication_service');
            $this->communication_service->notify_batch_event($batch_id, $event, $reference_id, $old_batch);
        } catch (Throwable $exception) {
            log_message('error', 'Batch communication trigger failed without blocking batch/session save: ' . $exception->getMessage());
        }
    }

}
