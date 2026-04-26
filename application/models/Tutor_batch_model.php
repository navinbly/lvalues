<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_batch_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('email_model');
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
        $payload = [
            'tutor_user_id'  => $tutor_user_id,
            'tutor_profile_id' => !empty($data['tutor_profile_id']) ? (int)$data['tutor_profile_id'] : null,
            'category_id'    => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            'class_id'       => !empty($data['class_id']) ? (int)$data['class_id'] : null,
            'subject_id'     => !empty($data['subject_id']) ? (int)$data['subject_id'] : null,
            'title'          => trim((string)($data['title'] ?? '')),
            'slug'           => url_title(trim((string)($data['title'] ?? '')), 'dash', true),
            'description'    => trim((string)($data['description'] ?? '')),
            'batch_code'     => trim((string)($data['batch_code'] ?? $this->generate_batch_code())),
            'delivery_mode'  => in_array(($data['delivery_mode'] ?? 'online'), ['online','offline','hybrid'], true) ? $data['delivery_mode'] : 'online',
            'capacity'       => max(1, (int)($data['capacity'] ?? 1)),
            'price'          => is_numeric($data['price'] ?? null) ? (float)$data['price'] : null,
            'start_date'     => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'       => !empty($data['end_date']) ? $data['end_date'] : null,
            'status'         => in_array(($data['status'] ?? 'draft'), ['draft','published','completed','cancelled','archived'], true) ? $data['status'] : 'draft',
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        if ($payload['title'] === '') {
            return ['status' => false, 'message' => 'Batch title is required.'];
        }

        if ($batch_id > 0) {
            $this->db->where('id', $batch_id)->where('tutor_user_id', $tutor_user_id)->update('tutor_batches', $payload);
            return ['status' => true, 'message' => 'Batch updated successfully.', 'batch_id' => $batch_id];
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        if ($payload['status'] === 'published') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }
        $this->db->insert('tutor_batches', $payload);
        $new_id = (int)$this->db->insert_id();
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

		return $this->db->get()->result_array();
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
        if ($invite['invite_status'] !== 'pending') {
            return ['status' => false, 'message' => 'This invitation has already been updated.'];
        }

        $update = [
            'student_user_id' => $student_user_id,
            'invite_status' => $action,
            'responded_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', (int)$invite['id'])->update('tutor_batch_invites', $update);

        if ($action === 'accepted') {
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

        return ['status' => true, 'message' => $action === 'accepted' ? 'Batch invitation accepted.' : 'Batch invitation rejected.'];
    }

    public function save_session(int $batch_id, int $tutor_user_id, array $input): array
    {
        $batch = $this->get_batch($batch_id, $tutor_user_id);
        if (empty($batch)) {
            return ['status' => false, 'message' => 'Batch not found.'];
        }
        $data = [
            'batch_id' => $batch_id,
            'tutor_user_id' => $tutor_user_id,
            'title' => trim((string)($input['title'] ?? '')),
            'agenda' => trim((string)($input['agenda'] ?? '')),
            'session_date' => trim((string)($input['session_date'] ?? '')),
            'start_time' => trim((string)($input['start_time'] ?? '')),
            'end_time' => trim((string)($input['end_time'] ?? '')),
            'timezone' => trim((string)($input['timezone'] ?? 'Asia/Kolkata')),
            'session_type' => in_array(($input['session_type'] ?? 'regular'), ['regular','demo','doubt','test','other'], true) ? $input['session_type'] : 'regular',
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
            'session_status' => in_array(($input['session_status'] ?? 'draft'), ['draft','published','live','completed','cancelled'], true) ? $input['session_status'] : 'draft',
            'provider_payload' => !empty($input['provider_payload']) ? json_encode($input['provider_payload']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($data['title'] === '' || $data['session_date'] === '' || $data['start_time'] === '' || $data['end_time'] === '') {
            return ['status' => false, 'message' => 'Title, date, start time and end time are required.'];
        }

        $this->db->insert('tutor_batch_sessions', $data);
        return ['status' => ((int)$this->db->insert_id()) > 0, 'message' => 'Session scheduled successfully.'];
    }

    public function get_batch_sessions(int $batch_id): array
    {
        return $this->db->from('tutor_batch_sessions')->where('batch_id', $batch_id)->order_by('session_date', 'ASC')->order_by('start_time', 'ASC')->get()->result_array();
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

		$evaluation_status = 'published';

		if (isset($input['evaluation_status']) && in_array($input['evaluation_status'], ['draft','published','closed'], true)) {
			$evaluation_status = $input['evaluation_status'];
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
            'external_link' => trim((string)($input['external_link'] ?? '')) ?: null,
            'available_from' => !empty($input['available_from']) ? $input['available_from'] : null,
            'due_at' => !empty($input['due_at']) ? $input['due_at'] : null,
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
        $now = date('Y-m-d H:i:s');
        $seen = [];

        foreach ($selected_students as $selection) {
            $ctx = $this->parse_student_context_selection($selection);
            if ($ctx['student_user_id'] <= 0 || empty($ctx['category_id']) || empty($ctx['class_id'])) {
                continue;
            }

            $key = $ctx['student_user_id'].'|'.$ctx['category_id'].'|'.$ctx['class_id'].'|'.($ctx['subject_id'] ?: '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $this->db->insert('tutor_batch_task_students', [
                'task_id' => $task_id,
                'batch_id' => $batch_id,
                'student_user_id' => $ctx['student_user_id'],
                'target_category_id' => $ctx['category_id'],
                'target_class_id' => $ctx['class_id'],
                'target_subject_id' => $ctx['subject_id'],
                'assignment_status' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $assigned++;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false || $task_id <= 0 || $assigned <= 0) {
            return ['status' => false, 'message' => 'Unable to create task or assign students.'];
        }

        return [
            'status' => true,
            'message' => ucfirst($data['task_type']) . ' created and assigned to '.$assigned.' student selection(s).'
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

    public function get_student_tasks_for_batch(int $batch_id, int $student_user_id): array
    {
        return $this->db
            ->select('t.*, ts.assignment_status, ts.target_category_id, ts.target_class_id, ts.target_subject_id')
            ->from('tutor_batch_tasks t')
            ->join('tutor_batch_task_students ts', 'ts.task_id = t.id', 'inner')
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
        $batch['sessions'] = $this->get_batch_sessions($batch_id);
        $batch['tasks'] = $this->get_student_tasks_for_batch($batch_id, $student_user_id);
        $batch['recordings'] = $this->db->from('tutor_batch_recordings')->where('batch_id', $batch_id)->where('availability_status', 'ready')->order_by('id', 'DESC')->get()->result_array();
        return $batch;
    }

    public function get_batch_summary(int $batch_id): array
    {
        $students = (int)$this->db->where('batch_id', $batch_id)->where_in('membership_status', ['active','completed'])->count_all_results('tutor_batch_students');
        $sessions = (int)$this->db->where('batch_id', $batch_id)->count_all_results('tutor_batch_sessions');
        $tasks = (int)$this->db->where('batch_id', $batch_id)->count_all_results('tutor_batch_tasks');
        $pending_invites = (int)$this->db->where('batch_id', $batch_id)->where('invite_status', 'pending')->count_all_results('tutor_batch_invites');
        return compact('students', 'sessions', 'tasks', 'pending_invites');
    }

    private function generate_batch_code(): string
    {
        return 'BTH-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
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
        $message = trim((string)$message);
        if ($message === '') {
            $message = 'You are invited to join the batch "'.$batch['title'].'". Please accept the invitation from your student dashboard.';
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

            $invite_id = 0;
            if (!empty($rejected)) {
                $invite_id = (int)$rejected['id'];
                $this->db->where('id', $invite_id)->update('tutor_batch_invites', $payload);
            } else {
                $payload['created_at'] = $now;
                $this->db->insert('tutor_batch_invites', $payload);
                $invite_id = (int)$this->db->insert_id();
            }

            if ($send_email) {
                $subject = 'Batch invitation for '.$batch['title'];
                $html_message = $this->build_invite_email_html($batch, $message, $invite_url);
                $email_sent = $this->send_batch_invite_email($student['email'], $subject, $html_message);

                if ($email_sent && $invite_id > 0) {
                    $this->db->where('id', $invite_id)->update('tutor_batch_invites', [
                        'email_sent_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            if ($prepare_whatsapp && !empty($student['phone'])) { $whatsapp_ready++; }
            $sent++;
        }

        return ['status' => true, 'message' => 'Invite completed. Created/Re-sent: '.$sent.', Skipped: '.$skipped.', WhatsApp ready: '.$whatsapp_ready];
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

}
