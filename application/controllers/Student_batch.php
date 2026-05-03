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
        $result = $this->tutor_batch_model->respond_invite((string)$token, (int)$this->session->userdata('user_id'), strtolower((string)$action));
        $this->session->set_flashdata($result['status'] ? 'flash_message' : 'error_message', $result['message']);
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

		$result = $this->tutor_batch_model->get_live_session_join_url($session_id, $student_user_id);

		if (!$result['status']) {
			$this->session->set_flashdata('error_message', $result['message']);
			redirect(((int)($result['batch_id'] ?? 0) > 0) ? site_url('student_batch/view/' . (int)$result['batch_id']) : site_url('student_batch/my_batches'), 'refresh');
		}

		redirect($result['meeting_url'], 'refresh');
	}
	
}
