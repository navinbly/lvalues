<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teacher_workflow extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('user_login') != true || (int)$this->session->userdata('is_instructor') !== 1) {
            redirect(site_url('login'), 'refresh');
        }
        $this->load->model('Teacher_workflow_model', 'workflow');
    }

    public function workspace()
    {
        $teacher=(int)$this->session->userdata('user_id');
        $page_data['agenda']=$this->workflow->get_daily_workspace($teacher);
        $page_data['calendar']=$this->workflow->get_calendar($teacher,date('Y-m-d',strtotime('-7 days')),date('Y-m-d',strtotime('+45 days')));
        $page_data['templates']=$this->workflow->get_templates($teacher);
        $page_data['page_name']='teacher_workspace';
        $page_data['page_title']='Daily Workspace';
        $this->load->view('backend/index',$page_data);
    }

    public function calendar()
    {
        $from=$this->input->get('from',true)?:date('Y-m-01');
        $to=$this->input->get('to',true)?:date('Y-m-t',strtotime('+1 month'));
        return $this->json(array('status'=>true,'events'=>$this->workflow->get_calendar((int)$this->session->userdata('user_id'),$from,$to)));
    }

    public function complete_item($id=0)
    {
        $this->post();
        return $this->json(array('status'=>$this->workflow->complete_workspace_item((int)$id,(int)$this->session->userdata('user_id'))));
    }

    public function autosave()
    {
        $this->post();
        $content=$this->input->post('content',false);
        if(is_string($content))$content=json_decode($content,true);
        $result=$this->workflow->autosave((int)$this->session->userdata('user_id'),(string)$this->input->post('entity_type',true),(string)$this->input->post('entity_id',true),(string)($this->input->post('editor_key',true)?:'default'),is_array($content)?$content:array(),(string)($this->input->post('save_type',true)?:'autosave'));
        return $this->json($result);
    }

    public function versions()
    {
        return $this->json(array('status'=>true,'versions'=>$this->workflow->get_versions((int)$this->session->userdata('user_id'),(string)$this->input->get('entity_type',true),(string)$this->input->get('entity_id',true),(string)($this->input->get('editor_key',true)?:'default'))));
    }

    public function create_template($batch_id=0)
    {
        $this->post();$result=$this->workflow->create_template_from_batch((int)$batch_id,(int)$this->session->userdata('user_id'),trim((string)$this->input->post('name',true)));
        $this->flash($result);redirect(site_url('tutor_batch/manage/'.(int)$batch_id),'refresh');
    }

    public function duplicate_batch($batch_id=0)
    {
        $this->post();$result=$this->workflow->duplicate_batch((int)$batch_id,(int)$this->session->userdata('user_id'),trim((string)$this->input->post('title',true)),(string)$this->input->post('start_date',true),!empty($this->input->post('copy_students')));
        $this->flash($result);redirect(!empty($result['batch_id'])?site_url('tutor_batch/manage/'.$result['batch_id']):site_url('tutor_batch'),'refresh');
    }

    public function use_template($template_id=0)
    {
        $this->post();$result=$this->workflow->create_batch_from_template((int)$template_id,(int)$this->session->userdata('user_id'),trim((string)$this->input->post('title',true)),(string)$this->input->post('start_date',true));
        $this->flash($result);redirect(!empty($result['batch_id'])?site_url('tutor_batch/manage/'.$result['batch_id']):site_url('teacher-workspace'),'refresh');
    }

    public function reschedule_session($session_id=0)
    {
        $this->post();$result=$this->workflow->reschedule_session((int)$session_id,(int)$this->session->userdata('user_id'),(string)$this->input->post('session_date',true),(string)$this->input->post('start_time',true),(string)$this->input->post('end_time',true));
        $this->flash($result);redirect(site_url('tutor_batch/manage/'.(int)($result['batch_id']??0).'/sessions'),'refresh');
    }

    public function review_enrollment($request_id=0,$action='')
    {
        $this->post();$result=$this->workflow->review_enrollment((int)$request_id,(int)$this->session->userdata('user_id'),(string)$action);
        $this->flash($result);redirect(site_url('tutor_batch/manage/'.(int)($result['batch_id']??0).'/students'),'refresh');
    }

    public function refresh_health($batch_id=0)
    {
        $this->post();$health=$this->workflow->calculate_batch_health((int)$batch_id,(int)$this->session->userdata('user_id'));
        return $this->json(array('status'=>true,'health'=>$health));
    }

    private function post(): void
    {
        if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);
    }
    private function json(array $body){return $this->output->set_content_type('application/json')->set_output(json_encode($body));}
    private function flash(array $result): void{$this->session->set_flashdata(!empty($result['status'])?'flash_message':'error_message',$result['message']??'Unable to complete action.');}
}
