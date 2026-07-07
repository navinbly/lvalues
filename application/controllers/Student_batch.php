<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_batch extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Tutor_batch_model', 'tutor_batch_model');

        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
    }

    private function require_post(): void
    {
        if (strtoupper((string)$this->input->method(true)) !== 'POST') {
            show_error('Invalid request method.', 405);
        }
    }

    public function invites()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $page_data['invites'] = $this->tutor_batch_model->get_student_invites($user_id);
        $page_data['is_dashboard_embed'] = $this->input->get('dashboard') === '1'
            || strtolower((string)$this->input->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
        $page_data['page_name'] = 'student_batch_invites';
        $page_data['page_title'] = 'my_batch_invites';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function profile()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $page_data['student_profile'] = $this->tutor_batch_model->get_student_profile($user_id);
        $page_data['categories'] = $this->tutor_batch_model->get_tutor_master_categories();
        $page_data['classes'] = $this->tutor_batch_model->get_tutor_master_classes();
        $page_data['subjects'] = $this->tutor_batch_model->get_tutor_master_subjects();
        $page_data['page_name'] = 'student_update_profile';
        $page_data['page_title'] = 'update_profile';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function update_learning_profile()
    {
        $this->require_post();
$result = $this->tutor_batch_model->save_student_classification(
            (int)$this->session->userdata('user_id'),
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata($result['status'] ? 'flash_message' : 'error_message', $result['message']);
        redirect(site_url('student_batch/profile'), 'refresh');
    }

    public function invite($token = '')
    {
        if ($token === '') {
            redirect(site_url('student_batch/invites'), 'refresh');
        }
        $page_data['invite'] = $this->tutor_batch_model->get_invite_by_token($token);
        $page_data['page_name'] = 'student_batch_invites';
        $page_data['page_title'] = 'batch_invitation';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function respond($token, $action)
    {
        $this->require_post();
        $result = $this->tutor_batch_model->respond_invite((string)$token, (int)$this->session->userdata('user_id'), strtolower((string)$action));
        $this->session->set_flashdata($result['status'] ? 'flash_message' : 'error_message', $result['message']);
        if ($this->input->get('dashboard') === '1') {
            redirect(site_url('home/student_dashboard#batch-invites'), 'refresh');
        }
        redirect(site_url('student_batch/invites'), 'refresh');
    }

    public function my_batches()
    {
        $page_data['batches'] = $this->tutor_batch_model->get_student_batches((int)$this->session->userdata('user_id'));
        $page_data['page_name'] = 'student_batches';
        $page_data['page_title'] = 'my_batches';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    /*public function view($batch_id)
    {
        $page_data['batch'] = $this->tutor_batch_model->get_student_batch_detail((int)$batch_id, (int)$this->session->userdata('user_id'));
        if (empty($page_data['batch'])) {
            redirect(site_url('student_batch/my_batches'), 'refresh');
        }
        $page_data['batches'] = [$page_data['batch']];
        $page_data['page_name'] = 'student_batches';
        $page_data['page_title'] = 'batch_details';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }*/
	
	
	public function view($batch_id = 0)
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$student_user_id = (int)$this->session->userdata('user_id');
		$batch_id = (int)$batch_id;

		$batch = $this->tutor_batch_model->get_student_batch_detail($batch_id, $student_user_id);

		if (empty($batch)) {
			show_404();
		}

		$page_data['batch'] = $batch;
		$page_data['page_name'] = 'student_batch_detail';
		$page_data['page_title'] = 'Batch Detail';

		$this->load->view('frontend/default-new/index', $page_data);
	}
	
	public function submit_assignment($task_id = 0)
	{
        $this->require_post();
if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$task_id = (int)$task_id;
		$student_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->submit_assignment(
			$task_id,
			$student_user_id,
			$this->input->post(NULL, true),
			$_FILES
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		redirect(((int)($result['batch_id'] ?? 0) > 0) ? site_url('student_batch/view/' . (int)$result['batch_id']) : site_url('student_batch/my_batches'), 'refresh');
	}
	
	public function start_test($test_id = 0)
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$test_id = (int)$test_id;
		$student_user_id = (int)$this->session->userdata('user_id');

		$page_data['test_data'] = $this->tutor_batch_model->start_student_test($test_id, $student_user_id);

		if (empty($page_data['test_data'])) {
			$this->session->set_flashdata('error_message', 'Test not found or access denied.');
			redirect(site_url('student_batch/my_batches'), 'refresh');
		}

		$page_data['page_name'] = 'student_take_test';
		$page_data['page_title'] = 'Take Test';

		$this->load->view('frontend/default-new/index', $page_data);
	}

	public function submit_test($attempt_id = 0)
	{
        $this->require_post();
if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$attempt_id = (int)$attempt_id;
		$student_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->submit_student_test(
			$attempt_id,
			$student_user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		redirect(((int)($result['batch_id'] ?? 0) > 0) ? site_url('student_batch/view/' . (int)$result['batch_id']) : site_url('student_batch/my_batches'), 'refresh');
	}
	
	
	public function join_session($session_id = 0)
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$session_id = (int)$session_id;
		$student_user_id = (int)$this->session->userdata('user_id');
        $this->load->library('live_learning_service');
		$result = $this->live_learning_service->join_decision($session_id, $student_user_id, 'student');

		if (!$result['status']) {
			$this->session->set_flashdata('error_message', $result['message']);
			redirect(((int)($result['batch_id'] ?? 0) > 0) ? site_url('student_batch/view/' . (int)$result['batch_id']) : site_url('student_batch/my_batches'), 'refresh');
		}

		redirect($result['url'], 'location', 302);
	}

    public function export_progress_pdf($batch_id = 0)
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $student_user_id = (int)$this->session->userdata('user_id');
        $batch_id = (int)$batch_id;
        $report = $this->tutor_batch_model->get_parent_ready_student_progress($student_user_id);

        if (empty($report)) {
            show_404();
            return;
        }

        if ($batch_id > 0) {
            $allowed_batch_ids = array_map(static function ($batch) {
                return (int)($batch['batch_id'] ?? 0);
            }, $report['active_batches'] ?? []);

            if (!in_array($batch_id, $allowed_batch_ids, true)) {
                show_404();
                return;
            }
        }

        $this->load->library('simple_pdf');
        $student = $report['student_profile'];
        $summary = $report['summary'];
        $batch_filter = $batch_id > 0;
        $batch_title = 'All Batches';

        $this->simple_pdf->add_title('Lvalues Student Progress Report');
        $this->simple_pdf->add_line('Student: ' . ($student['name'] ?? 'Student'));
        $this->simple_pdf->add_line('Email: ' . ($student['email'] ?? ''));
        $this->simple_pdf->add_line('Generated: ' . ($report['generated_at'] ?? date('Y-m-d H:i:s')));

        $this->simple_pdf->add_heading('Overall Summary');
        $this->simple_pdf->add_line('Active batches: ' . (int)($summary['active_batch_count'] ?? 0));
        $this->simple_pdf->add_line('Attendance: ' . (float)($summary['attendance_percent'] ?? 0) . '%');
        $this->simple_pdf->add_line('Assignment completion: ' . (float)($summary['assignment_completion_percent'] ?? 0) . '%');
        $this->simple_pdf->add_line('Average test score: ' . (float)($summary['average_test_score_percent'] ?? 0) . '%');
        $this->simple_pdf->add_line('Overall progress: ' . (float)($summary['overall_progress_percent'] ?? 0) . '%');

        $insights = $report['learning_insights'] ?? [];
        $this->simple_pdf->add_heading('Learning Insights');
        $this->simple_pdf->add_line('Learning consistency: ' . (float)($insights['learning_consistency'] ?? 0) . '%');
        $this->simple_pdf->add_line('Weak areas: ' . implode(', ', $insights['weak_areas'] ?? []));
        $this->simple_pdf->add_line('Strong areas: ' . implode(', ', $insights['strong_areas'] ?? []));
        $this->simple_pdf->add_line('Missed classes: ' . (int)($insights['missed_class_count'] ?? 0));

        $this->simple_pdf->add_heading('Batch Progress');
        foreach (($report['active_batches'] ?? []) as $batch) {
            if ($batch_filter && (int)$batch['batch_id'] !== $batch_id) {
                continue;
            }
            if ($batch_filter) {
                $batch_title = $batch['batch_title'] ?: 'Batch';
            }
            $this->simple_pdf->add_line(($batch['batch_title'] ?: 'Batch') . ' | Tutor: ' . ($batch['tutor_name'] ?: 'Tutor'));
            $this->simple_pdf->add_line('Attendance: ' . $batch['attendance_percent'] . '% | Assignments: ' . $batch['assignment_completion_percent'] . '% | Tests: ' . $batch['average_test_score_percent'] . '% | Overall: ' . $batch['overall_progress_percent'] . '%');
            $this->simple_pdf->add_line('Test score trend: ' . ($batch['test_score_trend']['label'] ?? 'No submitted tests yet'));
        }

        $this->simple_pdf->add_heading('Teacher Remarks');
        $remark_count = 0;
        foreach (($report['teacher_remarks'] ?? []) as $remark) {
            if ($batch_filter && (int)($remark['batch_id'] ?? 0) !== $batch_id) {
                continue;
            }
            $remark_count++;
            $this->simple_pdf->add_line(($remark['task_title'] ?? 'Assignment') . ' | ' . ($remark['batch_title'] ?? 'Batch') . ' | Marks: ' . ($remark['marks_obtained'] ?? 0) . ' / ' . ($remark['max_marks'] ?? 0));
            $this->simple_pdf->add_line('Remark: ' . ($remark['tutor_remarks'] ?? ''));
            $this->simple_pdf->add_line('Evaluated: ' . ($remark['evaluated_at'] ?? ''));
        }
        if ($remark_count === 0) {
            $this->simple_pdf->add_line('No teacher remarks yet.');
        }

        $this->simple_pdf->add_heading('Recent Assignments and Tests');
        $work_count = 0;
        foreach (($report['recent_assignments_tests'] ?? []) as $work) {
            if ($batch_filter && (int)($work['batch_id'] ?? 0) !== $batch_id) {
                continue;
            }
            $work_count++;
            $this->simple_pdf->add_line(ucfirst((string)($work['type'] ?? 'work')) . ': ' . ($work['title'] ?? '') . ' | Batch: ' . ($work['batch_title'] ?? '') . ' | Status: ' . ($work['status'] ?? ''));
        }
        if ($work_count === 0) {
            $this->simple_pdf->add_line('No recent assignments or tests yet.');
        }

        $filename = 'lvalues_progress_' . preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($student['name'] ?? 'student')) . '_' . preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($batch_title)) . '.pdf';
        $this->simple_pdf->output($filename);
    }
    public function request_enrollment($batch_id = 0)
    {
        $this->require_post();
        $batch_id = (int)$batch_id;
        $student_id = (int)$this->session->userdata('user_id');
        if ($student_id <= 0 || (int)$this->session->userdata('is_instructor') === 1) {
            show_error('Only student accounts can request batch enrollment.', 403);
        }
        $batch = $this->db->get_where('tutor_batches', ['id'=>$batch_id,'status'=>'published'], 1)->row_array();
        if (!$batch) {
            $this->session->set_flashdata('error_message', 'Batch not found.');
            redirect(site_url('home/student_batches'), 'refresh');
        }
        $member = $this->db->get_where('tutor_batch_students', ['batch_id'=>$batch_id,'student_user_id'=>$student_id], 1)->row_array();
        if ($member && in_array($member['membership_status'], ['active','completed'], true)) {
            $this->session->set_flashdata('flash_message', 'You are already enrolled in this batch.');
            redirect(site_url('student_batch/view/'.$batch_id), 'refresh');
        }
        $active = (int)$this->db->where('batch_id',$batch_id)->where('membership_status','active')->count_all_results('tutor_batch_students');
        $is_full = $active >= max(1, (int)$batch['capacity']);
        if ($is_full && empty($batch['waitlist_enabled'])) {
            $this->session->set_flashdata('error_message', 'This batch is full and its waitlist is closed.');
            redirect(site_url('home/student_batches'), 'refresh');
        }
        $status = $is_full ? 'waitlisted' : (($batch['enrollment_mode'] ?? 'approval') === 'open' ? 'approved' : 'pending');
        $request = [
            'batch_id'=>$batch_id,'student_user_id'=>$student_id,'status'=>$status,
            'parent_name'=>trim((string)$this->input->post('parent_name',true)),
            'parent_email'=>trim((string)$this->input->post('parent_email',true)),
            'parent_phone'=>trim((string)$this->input->post('parent_phone',true)),
            'note'=>trim((string)$this->input->post('note',true)),
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        $this->db->trans_begin();
        $existing=$this->db->get_where('tutor_batch_enrollment_requests',['batch_id'=>$batch_id,'student_user_id'=>$student_id],1)->row_array();
        if($existing)$this->db->where('id',$existing['id'])->update('tutor_batch_enrollment_requests',$request);else{$request['created_at']=date('Y-m-d H:i:s');$this->db->insert('tutor_batch_enrollment_requests',$request);}
        if($status==='waitlisted'){
            $position=(int)$this->db->where('batch_id',$batch_id)->count_all_results('tutor_batch_waitlist')+1;
            $this->db->replace('tutor_batch_waitlist',['batch_id'=>$batch_id,'student_user_id'=>$student_id,'position'=>$position,'status'=>'waiting','parent_name'=>$request['parent_name'],'parent_email'=>$request['parent_email'],'parent_phone'=>$request['parent_phone']]);
        } elseif ($status === 'approved') {
            $membership = [
                'batch_id'=>$batch_id,
                'student_user_id'=>$student_id,
                'membership_status'=>'active',
                'joined_at'=>date('Y-m-d H:i:s'),
                'parent_name'=>$request['parent_name'],
                'parent_email'=>$request['parent_email'],
                'parent_phone'=>$request['parent_phone'],
                'enrollment_approved_by'=>(int)$batch['tutor_user_id'],
                'enrollment_approved_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s')
            ];
            if ($member) {
                $this->db->where('id', $member['id'])->update('tutor_batch_students', $membership);
            } else {
                $membership['created_at']=date('Y-m-d H:i:s');
                $this->db->insert('tutor_batch_students', $membership);
            }
        }
        $this->db->insert('tutor_batch_student_history', [
            'batch_id'=>$batch_id,
            'student_user_id'=>$student_id,
            'event_type'=>'enrollment_'.$status,
            'from_status'=>$member['membership_status'] ?? null,
            'to_status'=>$status,
            'details_json'=>json_encode(['enrollment_mode'=>$batch['enrollment_mode'] ?? 'approval']),
            'actor_user_id'=>$student_id
        ]);
        if ($this->db->trans_status()) {
            $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error_message', 'Enrollment could not be completed. Please try again.');
            redirect(site_url('home/student_batches'), 'refresh');
        }
        $this->session->set_flashdata('flash_message',$status==='approved'?'Enrollment approved.':($status==='waitlisted'?'Batch is full; you joined the waitlist.':'Enrollment request sent for teacher approval.'));
        redirect(site_url('home/student_batches'), 'refresh');
    }

}
