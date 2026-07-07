<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_quality extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if($this->session->userdata('user_login')!=true||(int)$this->session->userdata('is_instructor')!==1)redirect(site_url('login'),'refresh');
        $this->load->model('Analytics_quality_model','analytics_quality');
    }

    public function teacher()
    {
        $teacher=(int)$this->session->userdata('user_id');
        $data=$this->analytics_quality->teacher_dashboard($teacher,(int)$this->input->get('batch_id'));
        $data['page_name']='teacher_analytics';$data['page_title']='Teaching Analytics & Quality';
        $this->load->view('backend/index',$data);
    }

    public function intervention()
    {
        $this->post();$result=$this->analytics_quality->save_intervention((int)$this->session->userdata('user_id'),$this->input->post(null,true));
        $this->flash($result);redirect(site_url('teacher-analytics?batch_id='.(int)$this->input->post('batch_id')),'refresh');
    }

    public function intervention_status($id=0,$status='')
    {
        $this->post();$result=$this->analytics_quality->update_intervention((int)$this->session->userdata('user_id'),(int)$id,(string)$status);
        $this->flash($result);redirect(site_url('teacher-analytics'),'refresh');
    }

    public function progress_pdf($batch_id=0,$student_id=0)
    {
        $report=$this->analytics_quality->student_report((int)$this->session->userdata('user_id'),(int)$student_id,(int)$batch_id);
        if(!$report)show_error('Student not found or outside your teaching scope.',403);
        $this->render_pdf($report,false);
    }

    public function parent_pdf($batch_id=0,$student_id=0)
    {
        $report=$this->analytics_quality->student_report((int)$this->session->userdata('user_id'),(int)$student_id,(int)$batch_id);
        if(!$report)show_error('Student not found or outside your teaching scope.',403);
        $this->render_pdf($report,true);
    }

    private function render_pdf(array $report,bool $parent): void
    {
        $this->load->library('simple_pdf');$s=$report['student'];$m=$report['metrics'];
        $this->simple_pdf->add_title($parent?'Lvalues Parent Progress Summary':'Lvalues Student Progress Report');
        $this->simple_pdf->add_line('Student: '.trim($s['first_name'].' '.$s['last_name']));$this->simple_pdf->add_line('Batch: '.$s['batch_title']);$this->simple_pdf->add_line('Generated: '.date('Y-m-d H:i:s'));
        $this->simple_pdf->add_heading('Progress');$this->simple_pdf->add_line('Attendance: '.$m['attendance'].'%');$this->simple_pdf->add_line('Assignment completion: '.$m['assignment_completion'].'%');$this->simple_pdf->add_line('Average score: '.$m['score'].'%');$this->simple_pdf->add_line('Score trend: '.($m['score_delta']>=0?'+':'').$m['score_delta'].' percentage points');$this->simple_pdf->add_line('Engagement: '.$m['engagement'].'%');
        $this->simple_pdf->add_heading($parent?'What needs attention':'Transparent risk indicators');if(!$report['risk'])$this->simple_pdf->add_line('No current watch indicators.');foreach($report['risk'] as $risk){$this->simple_pdf->add_line($risk['reason']);$this->simple_pdf->add_line('Next action: '.$risk['action']);}
        $this->simple_pdf->add_heading('Topic mastery');if(!$report['mastery'])$this->simple_pdf->add_line('No topic mastery evidence yet.');foreach($report['mastery'] as $row)$this->simple_pdf->add_line($row['dimension_label'].': '.$row['score_percentage'].'% ('.ucfirst($row['mastery_level']).')');
        $this->simple_pdf->add_heading('Teacher interventions');if(!$report['interventions'])$this->simple_pdf->add_line('No intervention plan recorded.');foreach($report['interventions'] as $row)$this->simple_pdf->add_line(ucwords(str_replace('_',' ',$row['intervention_type'])).': '.$row['reason_text'].' - '.ucfirst(str_replace('_',' ',$row['status'])));
        $this->simple_pdf->output(($parent?'parent_summary_':'student_progress_').(int)$s['student_user_id'].'.pdf');
    }

    private function post(): void{if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);}
    private function flash(array $r): void{$this->session->set_flashdata(!empty($r['status'])?'flash_message':'error_message',$r['message']??'Action failed.');}
}
