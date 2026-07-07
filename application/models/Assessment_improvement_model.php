<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assessment_improvement_model extends CI_Model
{
    public function dashboard(int $owner_id,string $role): array
    {
        $this->db->select('r.*,COUNT(c.id) criteria_count')->from('assessment_rubrics r')->join('assessment_rubric_criteria c','c.rubric_id=r.id','left');
        if($role!=='admin')$this->db->group_start()->where('r.owner_user_id',$owner_id)->or_where('r.is_shared',1)->group_end();
        $rubrics=$this->db->group_by('r.id')->order_by('r.updated_at','DESC')->get()->result_array();
        $this->db->from('reusable_feedback');if($role!=='admin')$this->db->group_start()->where('owner_user_id',$owner_id)->or_where('is_shared',1)->group_end();
        $feedback=$this->db->order_by('id','DESC')->limit(100)->get()->result_array();
        $this->db->select('m.dimension_type,m.dimension_label,SUM(m.questions_seen) questions,ROUND(AVG(m.score_percentage),1) score,COUNT(DISTINCT m.student_user_id) students')->from('assessment_mastery m');
        if($role!=='admin')$this->db->where("(EXISTS(SELECT 1 FROM content_exams e WHERE e.id=m.cohort_id AND m.cohort_type='exam' AND e.tutor_id=".(int)$owner_id.") OR EXISTS(SELECT 1 FROM tutor_batches b WHERE b.id=m.cohort_id AND m.cohort_type='batch' AND b.tutor_user_id=".(int)$owner_id."))",null,false);
        $mastery=$this->db->group_by(array('m.dimension_type','m.dimension_label'))->order_by('score','ASC')->limit(100)->get()->result_array();
        $submissions=array();$pending_attempts=array();
        if($role!=='admin')$submissions=$this->db->select('sub.*,t.title task_title,t.max_marks,u.first_name,u.last_name')->from('tutor_batch_assignment_submissions sub')->join('tutor_batch_tasks t','t.id=sub.task_id')->join('users u','u.id=sub.student_user_id','left')->where('t.tutor_user_id',$owner_id)->order_by('sub.submitted_at','DESC')->limit(100)->get()->result_array();
        if($role==='admin')$pending_attempts=$this->db->select('a.id,a.percentage,a.result_status,a.submitted_at,a.moderation_status,e.title,u.first_name,u.last_name')->from('content_exam_attempts a')->join('content_exams e','e.id=a.exam_id')->join('users u','u.id=a.user_id','left')->where('a.moderation_status','pending')->where('a.submitted_at IS NOT NULL',null,false)->order_by('a.submitted_at','ASC')->limit(100)->get()->result_array();
        return array('rubrics'=>$rubrics,'feedback'=>$feedback,'mastery'=>$mastery,'submissions'=>$submissions,'pending_attempts'=>$pending_attempts);
    }

    public function save_rubric(int $owner_id,array $input): array
    {
        $title=trim((string)($input['title']??''));$criteria=preg_split('/\r\n|\r|\n/',trim((string)($input['criteria']??'')));
        if($title===''||empty($criteria))return array('status'=>false,'message'=>'Rubric title and criteria are required.');
        $parsed=array();$total=0;
        foreach($criteria as $line){$parts=array_map('trim',explode('|',$line,3));if(count($parts)<2||!is_numeric($parts[1])||(float)$parts[1]<=0)continue;$parsed[]=array('title'=>$parts[0],'max_points'=>(float)$parts[1],'description'=>$parts[2]??'');$total+=(float)$parts[1];}
        if(!$parsed)return array('status'=>false,'message'=>'Use one criterion per line: Name | Points | Description.');
        $this->db->trans_start();$this->db->insert('assessment_rubrics',array('owner_user_id'=>$owner_id,'title'=>$title,'description'=>trim((string)($input['description']??'')),'total_points'=>$total,'is_shared'=>!empty($input['is_shared'])?1:0));$id=(int)$this->db->insert_id();
        foreach($parsed as $i=>$row){$row['rubric_id']=$id;$row['sort_order']=$i+1;$this->db->insert('assessment_rubric_criteria',$row);}$this->db->trans_complete();
        return array('status'=>$this->db->trans_status(),'message'=>'Rubric saved.');
    }

    public function save_feedback(int $owner_id,array $input): array
    {
        $title=trim((string)($input['title']??''));$text=trim((string)($input['feedback_text']??''));if($title===''||$text==='')return array('status'=>false,'message'=>'Feedback title and text are required.');
        $this->db->insert('reusable_feedback',array('owner_user_id'=>$owner_id,'title'=>$title,'feedback_text'=>$text,'category'=>substr(trim((string)($input['category']??'')),0,80),'is_shared'=>!empty($input['is_shared'])?1:0));
        return array('status'=>true,'message'=>'Reusable feedback saved.');
    }

    public function bulk_grade(int $tutor_id,array $input): array
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$input['submission_ids']??array()))));
        if(!$ids)return array('status'=>false,'message'=>'Select submissions to grade.');
        $rows=$this->db->select('sub.id')->from('tutor_batch_assignment_submissions sub')->join('tutor_batch_tasks t','t.id=sub.task_id')->where_in('sub.id',$ids)->where('t.tutor_user_id',$tutor_id)->get()->result_array();
        $this->load->model('Tutor_batch_model','tutor_batch_model');
        $graded=0;$failed=0;
        foreach($rows as $row){
            $id=(int)$row['id'];
            $result=$this->tutor_batch_model->evaluate_assignment_submission($id,$tutor_id,array(
                'marks_obtained'=>$input['marks'][$id]??0,
                'tutor_remarks'=>$input['feedback'][$id]??'',
                'idempotency_key'=>'bulk-grade-'.$tutor_id.'-'.$id.'-'.hash('sha256',json_encode(array($input['marks'][$id]??0,$input['feedback'][$id]??'')))
            ));
            if(!empty($result['status']))$graded++;else $failed++;
        }
        return array('status'=>$graded>0,'message'=>$graded.' submission(s) graded'.($failed?' and '.$failed.' failed validation.':'.'));
    }

    public function plagiarism_check(int $submission_id,int $tutor_id): array
    {
        $submission=$this->db->select('sub.*,t.tutor_user_id')->from('tutor_batch_assignment_submissions sub')->join('tutor_batch_tasks t','t.id=sub.task_id')->where('sub.id',$submission_id)->where('t.tutor_user_id',$tutor_id)->get()->row_array();
        if(!$submission)return array('status'=>false,'message'=>'Submission not found.');
        $source=$this->submission_text($submission);$max=0;
        if($source!=='')foreach($this->db->where('task_id',$submission['task_id'])->where('id !=',$submission_id)->get('tutor_batch_assignment_submissions')->result_array() as $other){$text=$this->submission_text($other);if($text==='')continue;similar_text($source,$text,$percent);$max=max($max,$percent);}
        $max=round($max,1);$state=$max>=80?'blocked':($max>=45?'review':'clear');
        $this->db->where('id',$submission_id)->update('tutor_batch_assignment_submissions',array('plagiarism_score'=>$max,'plagiarism_status'=>$state,'moderation_status'=>$state==='clear'?'not_required':'pending','updated_at'=>date('Y-m-d H:i:s')));
        return array('status'=>true,'message'=>'Plagiarism similarity check completed: '.$max.'%.','score'=>$max,'plagiarism_status'=>$state);
    }

    public function moderate_attempt(int $attempt_id,int $admin_id,string $decision,string $note): array
    {
        if(!in_array($decision,array('approved','adjusted'),true))return array('status'=>false,'message'=>'Invalid moderation decision.');
        $attempt=$this->db->get_where('content_exam_attempts',array('id'=>$attempt_id,'moderation_status'=>'pending'),1)->row_array();
        if(!$attempt)return array('status'=>false,'message'=>'Pending exam attempt not found.');
        $this->db->where('id',$attempt_id)->update('content_exam_attempts',array('moderation_status'=>$decision,'moderated_by'=>$admin_id,'moderated_at'=>date('Y-m-d H:i:s'),'moderation_note'=>$note));
        $this->load->model('Immutable_audit_model','immutable_audit');
        $this->immutable_audit->record('grading','exam_attempt_moderated','content_exam_attempt',$attempt_id,array('moderation_status'=>'pending'),array('moderation_status'=>$decision,'note'=>$note),array('actor_user_id'=>$admin_id,'actor_role'=>'admin'));
        return array('status'=>true,'message'=>'Exam result moderation saved.');
    }

    public function rebuild_mastery(): array
    {
        $this->db->query("INSERT INTO assessment_mastery(student_user_id,cohort_type,cohort_id,dimension_type,dimension_key,dimension_label,questions_seen,correct_answers,score_percentage,mastery_level)
            SELECT i.user_id,'exam',i.exam_id,
                CASE WHEN i.dimension_type='topic' THEN 'topic' ELSE 'chapter' END,
                i.dimension_key,i.dimension_label,SUM(i.total_questions),SUM(i.correct_answers),ROUND(AVG(i.score_percentage),2),
                CASE WHEN AVG(i.score_percentage)>=90 THEN 'mastered' WHEN AVG(i.score_percentage)>=75 THEN 'proficient' WHEN AVG(i.score_percentage)>=50 THEN 'developing' ELSE 'emerging' END
            FROM content_exam_attempt_insights i WHERE i.user_id>0 AND i.dimension_type IN('topic','section')
            GROUP BY i.user_id,i.exam_id,i.dimension_type,i.dimension_key,i.dimension_label
            ON DUPLICATE KEY UPDATE questions_seen=VALUES(questions_seen),correct_answers=VALUES(correct_answers),score_percentage=VALUES(score_percentage),mastery_level=VALUES(mastery_level),updated_at=NOW()");
        return array('status'=>true,'message'=>'Mastery reports refreshed.','rows'=>$this->db->affected_rows());
    }

    public function parent_summary(int $student_id): array
    {
        $student=$this->db->select('id,first_name,last_name')->get_where('users',array('id'=>$student_id),1)->row_array();
        $mastery=$this->db->where('student_user_id',$student_id)->order_by('score_percentage','ASC')->get('assessment_mastery')->result_array();
        $attempts=$this->db->select('a.percentage,a.result_status,a.submitted_at,e.title')->from('content_exam_attempts a')->join('content_exams e','e.id=a.exam_id')->where('a.user_id',$student_id)->where('a.submitted_at IS NOT NULL',null,false)->order_by('a.submitted_at','DESC')->limit(10)->get()->result_array();
        $average=$mastery?round(array_sum(array_column($mastery,'score_percentage'))/count($mastery),1):0;
        return array('student'=>$student,'average_mastery'=>$average,'strengths'=>array_slice(array_reverse($mastery),0,5),'focus_areas'=>array_slice($mastery,0,5),'recent_results'=>$attempts);
    }

    private function submission_text(array $row): string
    {
        return strtolower(trim(preg_replace('/\s+/',' ',strip_tags((string)($row['student_comment']??'').' '.(string)($row['external_link']??'')))));
    }
}
