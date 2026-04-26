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

        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ((int)$this->session->userdata('is_instructor') !== 1) {
            redirect(site_url('user/become_an_instructor'), 'refresh');
        }
    }

    public function index()
    {
        $user_id = (int)$this->session->userdata('user_id');

        $page_data['batches']    = $this->tutor_batch_model->get_tutor_batches($user_id);
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
        $user_id  = (int)$this->session->userdata('user_id');
        $batch_id = (int)$batch_id;

        $result = $this->tutor_batch_model->save_batch(
            $user_id,
            $this->input->post(NULL, true),
            $batch_id
        );

        $this->session->set_flashdata(
            $result['status'] ? 'flash_message' : 'error_message',
            $result['message']
        );

        if ($result['status']) {
            redirect(site_url('tutor_batch/manage/' . (int)$result['batch_id']), 'refresh');
        }

        redirect(site_url('tutor_batch/create/' . $batch_id), 'refresh');
    }

    public function manage($batch_id)
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
        $page_data['summary']   = $this->tutor_batch_model->get_batch_summary($batch_id);

        // Correct model alias is tutor_batch_model, not Tutor_batch_model
        $page_data['invites']   = $this->tutor_batch_model->get_batch_invites($batch_id);
        $page_data['eligible_students'] = $this->tutor_batch_model->get_eligible_students_for_batch($batch_id, $user_id);

        $page_data['page_name']  = 'tutor_batch_manage';
        $page_data['page_title'] = 'manage_batch';

		$page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
		$page_data['eligible_students'] = $this->tutor_batch_model->get_all_classified_students_for_invite($batch_id);

        $this->load->view('backend/index', $page_data);
    }

    public function invite($batch_id)
    {
        $batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->invite_student(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            $result['status'] ? 'flash_message' : 'error_message',
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id), 'refresh');
    }

    public function add_session($batch_id)
    {
        $batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->save_session(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            $result['status'] ? 'flash_message' : 'error_message',
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id), 'refresh');
    }

    public function add_task($batch_id)
    {
        $batch_id = (int)$batch_id;
        $user_id  = (int)$this->session->userdata('user_id');

        $result = $this->tutor_batch_model->save_task(
            $batch_id,
            $user_id,
            $this->input->post(NULL, true)
        );

        $this->session->set_flashdata(
            $result['status'] ? 'flash_message' : 'error_message',
            $result['message']
        );

        redirect(site_url('tutor_batch/manage/' . $batch_id), 'refresh');
    }

    public function bulk_invite($batch_id)
    {
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

        redirect(site_url('tutor_batch/manage/'.$batch_id), 'refresh');
    }

}