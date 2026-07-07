<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assessment_workflow extends CI_Controller
{
    public function __construct(){parent::__construct();$admin=$this->session->userdata('admin_login')==true;$tutor=$this->session->userdata('user_login')==true&&(int)$this->session->userdata('is_instructor')===1;if(!$admin&&!$tutor)redirect(site_url('login'),'refresh');$this->load->model('Assessment_improvement_model','assessment');}
    public function index(){$role=$this->role();$data=$this->assessment->dashboard($this->actor(),$role);$data['page_name']='assessment_workflow';$data['page_title']='Assessment Center';$data['assessment_role']=$role;$this->load->view('backend/index',$data);}
    public function rubric(){$this->post();$this->finish($this->assessment->save_rubric($this->actor(),$this->input->post(null,true)));}
    public function feedback(){$this->post();$this->finish($this->assessment->save_feedback($this->actor(),$this->input->post(null,true)));}
    public function bulk_grade(){$this->post();if($this->role()!=='tutor')show_error('Forbidden',403);$this->finish($this->assessment->bulk_grade($this->actor(),$this->input->post(null,true)));}
    public function plagiarism($submission_id=0){$this->post();if($this->role()!=='tutor')show_error('Forbidden',403);$this->finish($this->assessment->plagiarism_check((int)$submission_id,$this->actor()));}
    public function moderate_attempt($attempt_id=0){$this->post();if($this->role()!=='admin')show_error('Forbidden',403);$this->finish($this->assessment->moderate_attempt((int)$attempt_id,$this->actor(),(string)$this->input->post('decision',true),(string)$this->input->post('note',true)));}
    public function rebuild_mastery(){$this->post();if($this->role()!=='admin')show_error('Forbidden',403);$this->finish($this->assessment->rebuild_mastery());}
    public function parent_summary($student_id=0)
    {
        if($this->role()!=='tutor')show_error('Forbidden',403);
        $allowed=$this->db->from('tutor_batch_students bs')->join('tutor_batches b','b.id=bs.batch_id')->where('bs.student_user_id',(int)$student_id)->where('b.tutor_user_id',$this->actor())->count_all_results()>0;
        if(!$allowed)show_error('Student not found or access denied.',403);
        $summary=$this->assessment->parent_summary((int)$student_id);$student=$summary['student'];
        $this->load->library('simple_pdf');$this->simple_pdf->add_title('Lvalues Parent Progress Summary');$this->simple_pdf->add_line('Student: '.trim(($student['first_name']??'').' '.($student['last_name']??'')));$this->simple_pdf->add_line('Overall mastery: '.$summary['average_mastery'].'%');
        $this->simple_pdf->add_heading('Strengths');foreach($summary['strengths'] as $row)$this->simple_pdf->add_line($row['dimension_label'].': '.$row['score_percentage'].'% ('.ucfirst($row['mastery_level']).')');
        $this->simple_pdf->add_heading('Focus Areas');foreach($summary['focus_areas'] as $row)$this->simple_pdf->add_line($row['dimension_label'].': '.$row['score_percentage'].'% - suggested next step: guided review and practice.');
        $this->simple_pdf->add_heading('Recent Results');foreach($summary['recent_results'] as $row)$this->simple_pdf->add_line($row['title'].': '.$row['percentage'].'% ('.ucfirst($row['result_status']).')');
        $this->simple_pdf->output('lvalues_parent_progress_'.$student_id.'.pdf');
    }
    private function role(): string{return $this->session->userdata('admin_login')==true?'admin':'tutor';}
    private function actor(): int{return (int)$this->session->userdata('user_id');}
    private function post(): void{if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);}
    private function finish(array $r): void{$this->session->set_flashdata(!empty($r['status'])?'flash_message':'error_message',$r['message']??'Action failed.');redirect(site_url('assessment-center'),'refresh');}
}
