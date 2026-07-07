<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Course_workflow extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();$this->load->model('Course_workflow_model','course_workflow');
        if($this->session->userdata('user_login')!=true&&$this->session->userdata('admin_login')!=true)redirect(site_url('login'),'refresh');
    }

    public function snapshot($course_id=0){$this->post();$this->guard($course_id);$this->finish($this->course_workflow->snapshot((int)$course_id,$this->actor_id(),$this->role(),(string)$this->input->post('summary',true)),$course_id);}
    public function restore($version_id=0){$this->post();$version=$this->db->get_where('course_versions',array('id'=>(int)$version_id),1)->row_array();$this->guard((int)($version['course_id']??0));$this->finish($this->course_workflow->restore((int)$version_id,$this->actor_id(),$this->role()),(int)$version['course_id']);}
    public function duplicate($course_id=0){$this->post();$this->guard($course_id);$result=$this->course_workflow->duplicate((int)$course_id,$this->actor_id(),(string)$this->input->post('title',true));$this->flash($result);$prefix=$this->role()==='admin'?'admin':'user';redirect(site_url($prefix.'/course_form/course_edit/'.(int)($result['course_id']??$course_id)),'refresh');}
    public function transition($course_id=0,$target=''){$this->post();$this->guard($course_id);$this->finish($this->course_workflow->transition((int)$course_id,(string)$target,(string)$this->input->post('reason',true),$this->actor_id(),$this->role()),$course_id);}
    public function schedule($course_id=0){$this->post();if($this->role()!=='admin')show_error('Forbidden',403);$this->guard($course_id);$this->finish($this->course_workflow->schedule_publish((int)$course_id,(string)$this->input->post('scheduled_publish_at',true),$this->actor_id()),$course_id);}
    public function accessibility($course_id=0){$this->post();$this->guard($course_id);$this->finish($this->course_workflow->accessibility_check((int)$course_id,$this->actor_id()),$course_id);}
    public function save_lesson($lesson_id=0){$this->post();$result=$this->course_workflow->save_lesson_to_library((int)$lesson_id,$this->actor_id(),$this->role()==='admin');$course_id=(int)$this->db->where('id',(int)$lesson_id)->get('lesson')->row('course_id');$this->finish($result,$course_id);}
    public function use_lesson($course_id=0){$this->post();$this->guard($course_id);$this->finish($this->course_workflow->add_library_lesson((int)$this->input->post('library_id'),(int)$course_id,(int)$this->input->post('section_id'),$this->actor_id(),$this->role()==='admin'),$course_id);}

    private function guard($course_id): void{if(!$this->course_workflow->can_edit((int)$course_id,$this->actor_id(),$this->role()==='admin'))show_error('Course not found or access denied.',403);}
    private function role(): string{return $this->session->userdata('admin_login')==true?'admin':'tutor';}
    private function actor_id(): int{return (int)$this->session->userdata('user_id');}
    private function post(): void{if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);}
    private function flash(array $result): void{$this->session->set_flashdata(!empty($result['status'])?'flash_message':'error_message',$result['message']??'Action failed.');}
    private function finish(array $result,int $course_id): void{$this->flash($result);$prefix=$this->role()==='admin'?'admin':'user';redirect(site_url($prefix.'/course_form/course_edit/'.$course_id),'refresh');}
}
