<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_batch extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);

        $this->load->model('Tutor_batch_model', 'tutor_batch_model');
        $this->load->model('Tutor_master_model', 'tutor_master_model');
        $this->load->model('Teacher_workflow_model', 'teacher_workflow');

        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ((int)$this->session->userdata('is_instructor') !== 1) {
            redirect(site_url('user/become_an_instructor'), 'refresh');
        }
    }

    private function require_post(): void
    {
        if (strtoupper((string)$this->input->method(true)) !== 'POST') {
            show_error('Invalid request method.', 405);
        }
    }

    private function redirect_to_batch_or_index(array $result): void
    {
        $batch_id = (int)($result['batch_id'] ?? 0);
        redirect($batch_id > 0 ? site_url('tutor_batch/manage/' . $batch_id) : site_url('tutor_batch'), 'refresh');
    }

    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $page_data['batches']    = $this->tutor_batch_model->get_tutor_batches($user_id);
        foreach ($page_data['batches'] as &$batch_row) {
            $batch_row['health'] = $this->teacher_workflow->calculate_batch_health((int)$batch_row['id'], $user_id);
        }
        unset($batch_row);
        $page_data['templates'] = $this->teacher_workflow->get_templates($user_id);
        $page_data['page_name']  = 'tutor_batches';
        $page_data['page_title'] = 'my_batches';

        $this->load->view('backend/index', $page_data);
    }

    public function create($batch_id = 0)
    {
        $user_id  = (int)$this->session->userdata('user_id');
        $batch_id = (int)$batch_id;

        $page_data['batch']      = $batch_id ? $this->tutor_batch_model->get_batch($batch_id, $user_id) : [];
        $page_data['categories'] = $this->tutor_master_model->get_categories();
        $page_data['page_name']  = 'tutor_batch_form';
        $page_data['page_title'] = $batch_id ? 'edit_batch' : 'create_batch';

        $this->load->view('backend/index', $page_data);
    }

    public function save($batch_id = 0)
    {
        $this->require_post();
$user_id  = (int)$this->session->userdata('user_id');
        $batch_id = (int)$batch_id;

        $result = $this->tutor_batch_model->save_batch(
            $user_id,
            $this->input->post(NULL, true),
            $batch_id
        );

        $this->session->set_flashdata(
            !empty($result['flash_type']) ? $result['flash_type'] : ($result['status'] ? 'flash_message' : 'error_message'),
            $result['message']
        );

        if ($result['status']) {
            redirect(site_url('tutor_batch/manage/' . (int)$result['batch_id']), 'refresh');
        }

        redirect(site_url('tutor_batch/create/' . $batch_id), 'refresh');
    }

    public function manage($batch_id, $section = 'overview')
    {
        $user_id  = (int)$this->session->userdata('user_id');
        $batch_id = (int)$batch_id;

        $batch = $this->tutor_batch_model->get_batch($batch_id, $user_id);

        if (empty($batch)) {
            show_404();
            return;
        }

        $page_data['batch']     = $batch;
        $page_data['students']  = $this->tutor_batch_model->get_batch_students($batch_id);
        $page_data['sessions']  = $this->tutor_batch_model->get_batch_sessions($batch_id);
        $page_data['tasks']     = $this->tutor_batch_model->get_batch_tasks($batch_id);
		$page_data['assignment_submissions'] = $this->tutor_batch_model->get_assignment_submissions_for_batch($batch_id, $user_id);
        $page_data['summary']   = $this->tutor_batch_model->get_batch_summary($batch_id);
        $page_data['batch_360'] = $this->tutor_batch_model->get_batch_360_foundation($batch_id, $user_id);
        $page_data['progress_reports'] = $this->tutor_batch_model->get_batch_student_progress_reports($batch_id, $user_id);
        $page_data['batch_health'] = $this->teacher_workflow->calculate_batch_health($batch_id, $user_id);
        $page_data['batch_templates'] = $this->teacher_workflow->get_templates($user_id);
        $page_data['enrollment_requests'] = $this->db->select('r.*,u.first_name,u.last_name,u.email')->from('tutor_batch_enrollment_requests r')->join('users u','u.id=r.student_user_id','left')->where('r.batch_id',$batch_id)->order_by('r.created_at','DESC')->get()->result_array();
        $page_data['waitlist'] = $this->db->select('w.*,u.first_name,u.last_name,u.email')->from('tutor_batch_waitlist w')->join('users u','u.id=w.student_user_id','left')->where('w.batch_id',$batch_id)->order_by('w.position')->get()->result_array();
        $page_data['student_history'] = $this->db->where('batch_id',$batch_id)->order_by('created_at','DESC')->limit(100)->get('tutor_batch_student_history')->result_array();
        $page_data['assessment_rubrics'] = $this->db->group_start()->where('owner_user_id',$user_id)->or_where('is_shared',1)->group_end()->order_by('title')->get('assessment_rubrics')->result_array();

        // Correct model alias is tutor_batch_model, not Tutor_batch_model
        $page_data['invites']   = $this->tutor_batch_model->get_batch_invites($batch_id);

        $allowed_sections = ['overview','invite','schedule','tasks','students','sessions','assignments','progress'];
        $section = in_array($section, $allowed_sections, true) ? $section : 'overview';
        $page_data['manage_section'] = $section;

        $page_data['page_name']  = 'tutor_batch_manage';
        $page_data['page_title'] = 'manage_batch';

		$page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
		$page_data['invite_students'] = $this->tutor_batch_model->get_all_classified_students_for_invite($batch_id);
		$page_data['enrolled_students'] = $this->tutor_batch_model->get_enrolled_students_for_batch($batch_id, $user_id);
		$page_data['eligible_students'] = $page_data['invite_students']; // Backward-compatible alias for older view references.
		
		$page_data['tests'] = $this->tutor_batch_model->get_batch_tests($batch_id, $user_id);

        $this->load->view('backend/index', $page_data);
    }

    public function invite($batch_id)
    {
        $this->require_post();
$batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->invite_student(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            !empty($result['flash_type']) ? $result['flash_type'] : ($result['status'] ? 'flash_message' : 'error_message'),
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id . '/invite'), 'refresh');
    }

    public function add_session($batch_id)
    {
        $this->require_post();
$batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->save_session(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            !empty($result['flash_type']) ? $result['flash_type'] : ($result['status'] ? 'flash_message' : 'error_message'),
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id . '/schedule'), 'refresh');
    }

    public function add_task($batch_id)
    {
        $this->require_post();
$batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->save_task(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            !empty($result['flash_type']) ? $result['flash_type'] : ($result['status'] ? 'flash_message' : 'error_message'),
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id . '/tasks'), 'refresh');
    }

    public function bulk_invite($batch_id)
    {
        $this->require_post();
$batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $student_ids = $this->input->post('student_ids');
        $message     = trim($this->input->post('invite_message', true));

        $result = $this->tutor_batch_model->bulk_invite_students(
            $batch_id,
            $user_id,
            $student_ids,
            $message,
            !empty($this->input->post('send_email')),
            !empty($this->input->post('prepare_whatsapp'))
        );

        $this->session->set_flashdata(
            $result['status'] ? 'flash_message' : 'error_message',
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/'.$batch_id . '/invite'), 'refresh');
    }

	public function evaluate_assignment($submission_id = 0)
	{
        $this->require_post();
$submission_id = (int)$submission_id;
		$user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->evaluate_assignment_submission(
			$submission_id,
			$user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		$this->redirect_to_batch_or_index($result);
	}

	public function create_test($batch_id = 0)
	{
        $this->require_post();
$batch_id = (int)$batch_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->create_batch_test(
			$batch_id,
			$tutor_user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		redirect(site_url('tutor_batch/manage/' . $batch_id . '/assignments'), 'refresh');
	}

	public function add_test_question($test_id = 0)
	{
        $this->require_post();
$test_id = (int)$test_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->add_test_question(
			$test_id,
			$tutor_user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		$this->redirect_to_batch_or_index($result);
	}

	public function publish_test($test_id = 0)
	{
        $this->require_post();
$test_id = (int)$test_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->publish_test($test_id, $tutor_user_id);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		$this->redirect_to_batch_or_index($result);
	}

	public function attendance($session_id = 0)
	{
		$session_id = (int)$session_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$page_data['attendance_data'] = $this->tutor_batch_model->get_session_attendance_page_data($session_id, $tutor_user_id);

		if (empty($page_data['attendance_data'])) {
			$this->session->set_flashdata('error_message', 'Session not found or access denied.');
			redirect(site_url('tutor_batch'), 'refresh');
		}

		$page_data['page_name'] = 'tutor_batch_attendance';
		$page_data['page_title'] = 'Mark Attendance';

		$this->load->view('backend/index', $page_data);
	}

	public function save_attendance($session_id = 0)
	{
        $this->require_post();
$session_id = (int)$session_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->save_session_attendance(
			$session_id,
			$tutor_user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		$this->redirect_to_batch_or_index($result);
	}
	public function update_session_links($session_id = 0)
	{
        $this->require_post();
$session_id = (int)$session_id;
		$tutor_user_id = (int)$this->session->userdata('user_id');

		$result = $this->tutor_batch_model->update_session_links(
			$session_id,
			$tutor_user_id,
			$this->input->post(NULL, true)
		);

		$this->session->set_flashdata(
			$result['status'] ? 'flash_message' : 'error_message',
			$result['message']
		);

		$this->redirect_to_batch_or_index($result);
	}



}
