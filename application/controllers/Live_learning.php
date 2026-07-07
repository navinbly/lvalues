<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Live_learning extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if($this->session->userdata('user_login')!=true)redirect(site_url('login'),'refresh');
        $this->load->library('live_learning_service');
    }

    public function providers(){return $this->json(array('status'=>true,'providers'=>$this->live_learning_service->providers((int)$this->session->userdata('user_id'))));}

    public function connection_test()
    {
        $this->post();
        $session_id=(int)$this->input->post('session_id',true);
        $user_id=(int)$this->session->userdata('user_id');
        $session=$this->db->get_where('tutor_batch_sessions',array('id'=>$session_id),1)->row_array();
        if(!$session||!$this->live_learning_service->teacher_owns_session($session_id,$user_id))return $this->json(array('status'=>false,'message'=>'Session not found or access denied.'));
        $url=$session['host_join_url']?:($session['student_join_url']?:$session['meeting_url']);
        $result=$this->live_learning_service->connection_test((string)$session['provider_type'],(string)$url);
        $result['status']=!empty($result['reachable']);
        return $this->json($result);
    }

    public function join($session_id=0)
    {
        $role=(int)$this->session->userdata('is_instructor')===1?'tutor':'student';
        $result=$this->live_learning_service->join_decision((int)$session_id,(int)$this->session->userdata('user_id'),$role);
        if(!empty($result['status'])&&!empty($result['url']))redirect($result['url'],'location',302);
        $this->session->set_flashdata('error_message',$result['message']??'Unable to join session.');
        redirect($role==='tutor'?site_url('teacher-workspace'):site_url('student_batch/my_batches'),'refresh');
    }

    public function consent($session_id=0)
    {
        $this->post();$ok=$this->live_learning_service->record_consent((int)$session_id,(int)$this->session->userdata('user_id'),(string)$this->input->post('consent_status',true));
        return $this->json(array('status'=>$ok));
    }

    public function incident($session_id=0)
    {
        $this->post();return $this->json($this->live_learning_service->report_incident((int)$session_id,(int)$this->session->userdata('user_id'),$this->input->post(null,true)));
    }

    public function playback($session_id=0)
    {
        $this->post();return $this->json(array('status'=>$this->live_learning_service->playback_event((int)$session_id,(int)$this->session->userdata('user_id'),$this->input->post(null,true))));
    }

    public function recording($session_id=0)
    {
        $result=$this->live_learning_service->recording_decision((int)$session_id,(int)$this->session->userdata('user_id'));
        if(!empty($result['status'])&&!empty($result['url']))redirect($result['url'],'location',302);
        $this->session->set_flashdata('error_message',$result['message']??'Recording is not available.');
        redirect(!empty($result['batch_id'])?site_url('student_batch/view/'.(int)$result['batch_id']):site_url('student_batch/my_batches'),'refresh');
    }

    public function sync_attendance($session_id=0)
    {
        $this->post();
        if((int)$this->session->userdata('is_instructor')!==1||!$this->live_learning_service->teacher_owns_session((int)$session_id,(int)$this->session->userdata('user_id')))show_error('Forbidden',403);
        return $this->json(array('status'=>true,'synced'=>$this->live_learning_service->sync_attendance_from_joins((int)$session_id)));
    }

    public function analytics($session_id=0)
    {
        if((int)$this->session->userdata('is_instructor')!==1||!$this->live_learning_service->teacher_owns_session((int)$session_id,(int)$this->session->userdata('user_id')))show_error('Forbidden',403);
        return $this->json(array('status'=>true,'analytics'=>$this->live_learning_service->playback_analytics((int)$session_id)));
    }

    private function post(): void{if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);}
    private function json(array $body){return $this->output->set_content_type('application/json')->set_output(json_encode($body));}
}
