<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_quality_model extends CI_Model
{
    private $quality_weights = array(
        'content_quality' => 25,
        'moderation' => 15,
        'student_feedback' => 20,
        'class_reliability' => 25,
        'engagement' => 15,
    );

    public function teacher_dashboard(int $teacher_id,int $batch_id=0): array
    {
        $batches=$this->db->select('id,title,status,health_score,start_date,end_date')->where('tutor_user_id',$teacher_id)->order_by('updated_at','DESC')->get('tutor_batches')->result_array();
        $allowed=array_map('intval',array_column($batches,'id'));
        if($batch_id>0&&!in_array($batch_id,$allowed,true))$batch_id=0;
        $scope=$batch_id>0?array($batch_id):$allowed;
        $students=$scope?$this->db->select('bs.batch_id,bs.student_user_id,bs.membership_status,b.title batch_title,u.first_name,u.last_name,u.email')->from('tutor_batch_students bs')->join('tutor_batches b','b.id=bs.batch_id')->join('users u','u.id=bs.student_user_id','left')->where('b.tutor_user_id',$teacher_id)->where_in('bs.batch_id',$scope)->where_in('bs.membership_status',array('active','completed'))->order_by('b.title')->order_by('u.first_name')->get()->result_array():array();
        $rows=array();
        $this->load->model('Tutor_batch_model','tutor_batch_model');
        foreach($students as $student){
            $metrics=$this->student_metrics((int)$student['batch_id'],(int)$student['student_user_id']);
            $risk=$this->risk_reasons($metrics);
            $student['metrics']=$metrics;$student['risk']=$risk;$student['risk_level']=$this->risk_level($risk);
            $rows[]=$student;
        }
        usort($rows,function($a,$b){return $this->risk_rank($b['risk_level'])<=>$this->risk_rank($a['risk_level']);});
        return array(
            'batches'=>$batches,'selected_batch_id'=>$batch_id,'students'=>$rows,
            'batch_comparisons'=>$this->batch_comparisons($teacher_id,$allowed),
            'attendance_trend'=>$this->attendance_trend($teacher_id,$scope),
            'score_trend'=>$this->score_trend($teacher_id,$scope),
            'topic_mastery'=>$this->topic_mastery($teacher_id,$scope),
            'quality'=>$this->teacher_quality($teacher_id,false),
            'interventions'=>$this->interventions($teacher_id,$batch_id)
        );
    }

    public function student_report(int $teacher_id,int $student_id,int $batch_id): array
    {
        $membership=$this->db->select('bs.*,b.title batch_title,u.first_name,u.last_name,u.email')->from('tutor_batch_students bs')->join('tutor_batches b','b.id=bs.batch_id')->join('users u','u.id=bs.student_user_id','left')->where('bs.batch_id',$batch_id)->where('bs.student_user_id',$student_id)->where('b.tutor_user_id',$teacher_id)->get()->row_array();
        if(!$membership)return array();
        return array(
            'student'=>$membership,
            'metrics'=>$this->student_metrics($batch_id,$student_id),
            'risk'=>$this->risk_reasons($this->student_metrics($batch_id,$student_id)),
            'mastery'=>$this->db->where('student_user_id',$student_id)->order_by('score_percentage','ASC')->limit(20)->get('assessment_mastery')->result_array(),
            'interventions'=>$this->db->where(array('tutor_user_id'=>$teacher_id,'student_user_id'=>$student_id,'batch_id'=>$batch_id))->order_by('created_at','DESC')->get('teacher_student_interventions')->result_array()
        );
    }

    public function save_intervention(int $teacher_id,array $input): array
    {
        $batch_id=(int)($input['batch_id']??0);$student_id=(int)($input['student_user_id']??0);
        $allowed=$this->db->from('tutor_batch_students bs')->join('tutor_batches b','b.id=bs.batch_id')->where('bs.batch_id',$batch_id)->where('bs.student_user_id',$student_id)->where('b.tutor_user_id',$teacher_id)->count_all_results()===1;
        if(!$allowed)return array('status'=>false,'message'=>'Student or batch is outside your teaching scope.');
        $type=(string)($input['intervention_type']??'contact');
        if(!in_array($type,array('contact','attendance_plan','assignment_plan','mastery_plan','parent_update','support_referral','custom'),true))$type='custom';
        $this->db->insert('teacher_student_interventions',array(
            'tutor_user_id'=>$teacher_id,'student_user_id'=>$student_id,'batch_id'=>$batch_id,'intervention_type'=>$type,
            'reason_code'=>substr(trim((string)($input['reason_code']??'teacher_review')),0,80),
            'reason_text'=>substr(trim((string)($input['reason_text']??'Teacher-created intervention')),0,500),
            'action_note'=>trim((string)($input['action_note']??'')),
            'due_at'=>!empty($input['due_at'])?date('Y-m-d H:i:s',strtotime((string)$input['due_at'])):null
        ));
        return array('status'=>true,'message'=>'Intervention added to the teaching plan.');
    }

    public function update_intervention(int $teacher_id,int $id,string $status): array
    {
        if(!in_array($status,array('planned','in_progress','completed','cancelled'),true))return array('status'=>false,'message'=>'Invalid intervention status.');
        $data=array('status'=>$status,'completed_at'=>$status==='completed'?date('Y-m-d H:i:s'):null);
        $this->db->where('id',$id)->where('tutor_user_id',$teacher_id)->update('teacher_student_interventions',$data);
        return array('status'=>$this->db->affected_rows()===1,'message'=>'Intervention status updated.');
    }

    public function teacher_quality(int $teacher_id,bool $persist=true): array
    {
        $courses=$this->db->where('creator',$teacher_id)->get('course')->result_array();
        $course_scores=array();
        foreach($courses as $course){
            $lessons=(int)$this->db->where('course_id',$course['id'])->count_all_results('lesson');
            $complete=trim(strip_tags((string)($course['description']??'').' '.(string)($course['short_description']??'')))!=='';
            $course_scores[]=min(100,($complete?25:0)+($lessons>0?35:0)+min(40,(float)($course['accessibility_score']??0)*.4));
        }
        $content=$course_scores?round(array_sum($course_scores)/count($course_scores),1):50;
        $review_rows=array();
        foreach(array('content_exams','question_bank_questions') as $table){
            if(!$this->db->table_exists($table)||!$this->db->field_exists('created_by',$table))continue;
            $review_rows=array_merge($review_rows,$this->db->select('submitted_for_review_at,reviewed_at,review_status')->where('created_by',$teacher_id)->where('submitted_for_review_at IS NOT NULL',null,false)->get($table)->result_array());
        }
        $hours=array();$approved=0;
        foreach($review_rows as $row){if(!empty($row['reviewed_at']))$hours[]=(strtotime($row['reviewed_at'])-strtotime($row['submitted_for_review_at']))/3600;if(($row['review_status']??'')==='published')$approved++;}
        $avg_hours=$hours?round(array_sum($hours)/count($hours),1):null;
        $moderation=$avg_hours===null?60:max(0,min(100,100-($avg_hours/168*60)));
        if($review_rows)$moderation=round($moderation*.7+($approved/count($review_rows)*100)*.3,1);
        $feedback=$this->db->table_exists('tutor_session_feedback')?$this->db->select_avg('rating','rating')->where('tutor_user_id',$teacher_id)->where('feedback_status','submitted')->get('tutor_session_feedback')->row('rating'):null;
        $feedback_count=$this->db->table_exists('tutor_session_feedback')?(int)$this->db->where('tutor_user_id',$teacher_id)->where('feedback_status','submitted')->count_all_results('tutor_session_feedback'):0;
        $feedback_score=$feedback===null?60:round(min(100,(float)$feedback*20),1);
        $sessions=$this->db->where('tutor_user_id',$teacher_id)->where('session_date <=',date('Y-m-d'))->get('tutor_batch_sessions')->result_array();
        $reliable=0;$cancelled=0;$missing_links=0;
        foreach($sessions as $session){if(($session['session_status']??'')==='completed')$reliable++;if(($session['session_status']??'')==='cancelled')$cancelled++;if(!in_array($session['session_status']??'',array('cancelled','draft'),true)&&empty($session['student_join_url'])&&empty($session['meeting_url']))$missing_links++;}
        $reliability=$sessions?round(max(0,($reliable/count($sessions)*100)-($missing_links/count($sessions)*20)),1):60;
        $batches=$this->db->select('health_score')->where('tutor_user_id',$teacher_id)->get('tutor_batches')->result_array();
        $engagement=$batches?round(array_sum(array_map(function($r){return (float)$r['health_score'];},$batches))/count($batches),1):50;
        $components=array('content_quality'=>$content,'moderation'=>round($moderation,1),'student_feedback'=>$feedback_score,'class_reliability'=>$reliability,'engagement'=>$engagement);
        $score=0;foreach($this->quality_weights as $key=>$weight)$score+=$components[$key]*$weight/100;
        $actions=array();
        if($content<75)$actions[]='Complete course descriptions, lesson structure, captions, alt text, and accessibility checks.';
        if($moderation<75)$actions[]='Submit review-ready content and respond to moderation requests within 72 hours.';
        if($feedback_score<75)$actions[]='Review student feedback after sessions and close the loop on repeated concerns.';
        if($reliability<85)$actions[]='Publish meeting links early, reduce cancellations, and close sessions as completed.';
        if($engagement<70)$actions[]='Contact inactive students and use assignment, attendance, and mastery follow-ups.';
        $result=array('score'=>round($score,1),'components'=>$components,'weights'=>$this->quality_weights,'inputs'=>array('courses'=>count($courses),'review_items'=>count($review_rows),'moderation_turnaround_hours'=>$avg_hours,'feedback_count'=>$feedback_count,'average_rating'=>$feedback===null?null:round((float)$feedback,1),'past_sessions'=>count($sessions),'completed_sessions'=>$reliable,'cancelled_sessions'=>$cancelled,'missing_meeting_links'=>$missing_links,'batches'=>count($batches)),'actions'=>$actions);
        if($persist&&$this->db->table_exists('teacher_quality_snapshots'))$this->db->insert('teacher_quality_snapshots',array('tutor_user_id'=>$teacher_id,'quality_score'=>$result['score'],'content_quality_score'=>$content,'moderation_score'=>$moderation,'student_feedback_score'=>$feedback_score,'class_reliability_score'=>$reliability,'engagement_score'=>$engagement,'inputs_json'=>json_encode($result['inputs']),'actions_json'=>json_encode($actions)));
        return $result;
    }

    public function admin_teacher_quality(): array
    {
        $teachers=$this->db->select('id,first_name,last_name,email')->where('is_instructor',1)->where('status',1)->order_by('first_name')->get('users')->result_array();
        foreach($teachers as &$teacher)$teacher['quality']=$this->teacher_quality((int)$teacher['id'],false);
        unset($teacher);usort($teachers,function($a,$b){return $b['quality']['score']<=>$a['quality']['score'];});
        return $teachers;
    }

    public function admin_student_success(): array
    {
        $members=$this->db->select('bs.batch_id,bs.student_user_id,b.title batch_title,b.tutor_user_id,u.first_name,u.last_name,u.email,t.first_name tutor_first_name,t.last_name tutor_last_name')->from('tutor_batch_students bs')->join('tutor_batches b','b.id=bs.batch_id')->join('users u','u.id=bs.student_user_id','left')->join('users t','t.id=b.tutor_user_id','left')->where_in('bs.membership_status',array('active','completed'))->order_by('b.title')->get()->result_array();
        $rows=array();foreach($members as $member){$metrics=$this->student_metrics((int)$member['batch_id'],(int)$member['student_user_id']);$member['metrics']=$metrics;$member['risk']=$this->risk_reasons($metrics);$member['risk_level']=$this->risk_level($member['risk']);$rows[]=$member;}
        usort($rows,function($a,$b){return $this->risk_rank($b['risk_level'])<=>$this->risk_rank($a['risk_level']);});
        return $rows;
    }

    public function content_quality(): array
    {
        $courses=$this->db->select('c.id,c.title,c.workflow_status,c.accessibility_score,c.workflow_updated_at,c.creator,u.first_name,u.last_name,COUNT(DISTINCT l.id) lessons')->from('course c')->join('lesson l','l.course_id=c.id','left')->join('users u','u.id=c.creator','left')->group_by('c.id')->order_by('c.last_modified','DESC')->get()->result_array();
        foreach($courses as &$course){$flags=array();if((int)$course['lessons']===0)$flags[]='No lessons';if((float)$course['accessibility_score']<70)$flags[]='Accessibility below 70%';if(in_array($course['workflow_status'],array('pending','changes_requested'),true))$flags[]='Moderation action pending';$course['flags']=$flags;}
        unset($course);
        $turnaround=$this->moderation_turnaround();
        return array('courses'=>$courses,'moderation'=>$turnaround);
    }

    public function operations_dashboard(): array
    {
        $since=date('Y-m-d H:i:s',strtotime('-7 days'));
        $delivery=array();
        foreach(array('email','whatsapp') as $channel)$delivery[$channel]=$this->db->select("status,COUNT(*) total")->where('channel',$channel)->where('created_at >=',$since)->group_by('status')->get('message_campaign_recipients')->result_array();
        $meeting=array(
            'missing_links'=>(int)$this->db->where('session_date >=',date('Y-m-d'))->where_not_in('session_status',array('cancelled','draft','archived'))->group_start()->where('student_join_url IS NULL',null,false)->or_where('student_join_url','')->group_end()->group_start()->where('meeting_url IS NULL',null,false)->or_where('meeting_url','')->group_end()->count_all_results('tutor_batch_sessions'),
            'open_incidents'=>$this->db->table_exists('live_session_incidents')?(int)$this->db->where_in('status',array('open','investigating'))->count_all_results('live_session_incidents'):0
        );
        $uploads=array('rejected'=>0,'scanner_unavailable'=>0);
        if($this->db->table_exists('secure_upload_events')){$uploads['rejected']=(int)$this->db->where('status','rejected')->where('created_at >=',$since)->count_all_results('secure_upload_events');$uploads['scanner_unavailable']=(int)$this->db->where('malware_status','scanner_unavailable')->where('created_at >=',$since)->count_all_results('secure_upload_events');}
        return array(
            'delivery'=>$delivery,'meeting'=>$meeting,'uploads'=>$uploads,
            'job_runs'=>$this->db->table_exists('operational_job_runs')?$this->db->order_by('id','DESC')->limit(50)->get('operational_job_runs')->result_array():array(),
            'failed_exports'=>$this->db->table_exists('admin_export_jobs')?(int)$this->db->where('status','failed')->where('requested_at >=',$since)->count_all_results('admin_export_jobs'):0,
            'failed_imports'=>$this->db->table_exists('question_bank_import_jobs')?(int)$this->db->where('status','failed')->where('created_at >=',$since)->count_all_results('question_bank_import_jobs'):0,
            'failed_reminders'=>$this->db->table_exists('teacher_automated_reminders')?(int)$this->db->where('status','failed')->where('created_at >=',$since)->count_all_results('teacher_automated_reminders'):0,
            'provider_health'=>$this->db->table_exists('provider_health_checks')?$this->db->order_by('id','DESC')->limit(20)->get('provider_health_checks')->result_array():array(),
            'alerts'=>$this->db->table_exists('operations_alerts')?$this->db->where_in('status',array('open','acknowledged'))->order_by('severity','DESC')->order_by('last_seen_at','DESC')->limit(30)->get('operations_alerts')->result_array():array()
        );
    }

    private function student_metrics(int $batch_id,int $student_id): array
    {
        if(!isset($this->tutor_batch_model))$this->load->model('Tutor_batch_model','tutor_batch_model');
        $summary=$this->tutor_batch_model->get_student_progress_summary($batch_id,$student_id);
        $attempts=$this->db->select('percentage,submitted_at')->where(array('batch_id'=>$batch_id,'student_user_id'=>$student_id,'status'=>'submitted'))->order_by('submitted_at','ASC')->get('tutor_batch_test_attempts')->result_array();
        $latest=count($attempts)?$attempts[count($attempts)-1]:array();
        $delta=count($attempts)>=2?round((float)$latest['percentage']-(float)$attempts[count($attempts)-2]['percentage'],1):0;
        $joins=$this->db->table_exists('live_session_join_logs')?(int)$this->db->from('live_session_join_logs j')->join('tutor_batch_sessions s','s.id=j.session_id')->where('s.batch_id',$batch_id)->where('j.user_id',$student_id)->where('j.decision','allowed')->where('j.created_at >=',date('Y-m-d H:i:s',strtotime('-30 days')))->count_all_results():0;
        $submissions=(int)$this->db->where(array('batch_id'=>$batch_id,'student_user_id'=>$student_id))->where('submitted_at IS NOT NULL',null,false)->where('submitted_at >=',date('Y-m-d H:i:s',strtotime('-30 days')))->count_all_results('tutor_batch_assignment_submissions');
        $tests=(int)$this->db->where(array('batch_id'=>$batch_id,'student_user_id'=>$student_id,'status'=>'submitted'))->where('submitted_at >=',date('Y-m-d H:i:s',strtotime('-30 days')))->count_all_results('tutor_batch_test_attempts');
        $engagement=min(100,round(($joins*12)+($submissions*15)+($tests*15),1));
        return array('attendance'=>round((float)$summary['attendance_percent'],1),'assignment_completion'=>round((float)$summary['assignment_percent'],1),'score'=>round((float)$summary['test_percent'],1),'overall'=>round((float)$summary['overall_percent'],1),'score_delta'=>$delta,'engagement'=>$engagement,'recent_joins'=>$joins,'recent_submissions'=>$submissions+$tests);
    }

    private function risk_reasons(array $m): array
    {
        $reasons=array();
        if($m['attendance']<70)$reasons[]=array('code'=>'low_attendance','severity'=>$m['attendance']<50?'high':'medium','reason'=>'Attendance is '.$m['attendance'].'%, below the 70% watch threshold.','action'=>'Review absences and contact the student.');
        if($m['assignment_completion']<60)$reasons[]=array('code'=>'incomplete_work','severity'=>$m['assignment_completion']<35?'high':'medium','reason'=>'Assignment completion is '.$m['assignment_completion'].'%, below 60%.','action'=>'Agree a short assignment completion plan.');
        if($m['score']>0&&$m['score']<50)$reasons[]=array('code'=>'low_score','severity'=>'high','reason'=>'Average score is '.$m['score'].'%, below 50%.','action'=>'Review weak topics and assign targeted practice.');
        if($m['score_delta']<=-10)$reasons[]=array('code'=>'declining_score','severity'=>'high','reason'=>'Latest score fell by '.abs($m['score_delta']).' percentage points.','action'=>'Check misconceptions before the next assessment.');
        if($m['engagement']<25)$reasons[]=array('code'=>'low_engagement','severity'=>'medium','reason'=>'Few joins or submissions were recorded in the last 30 days.','action'=>'Contact the student and confirm access or scheduling barriers.');
        return $reasons;
    }

    private function risk_level(array $reasons): string
    {
        foreach($reasons as $reason)if($reason['severity']==='high')return 'high';
        return $reasons?'medium':'low';
    }
    private function risk_rank(string $level): int{return $level==='high'?3:($level==='medium'?2:1);}

    private function batch_comparisons(int $teacher_id,array $ids): array
    {
        $rows=array();
        foreach($ids as $id){
            $students=$this->db->select('student_user_id')->where('batch_id',$id)->where_in('membership_status',array('active','completed'))->get('tutor_batch_students')->result_array();
            $metrics=array();foreach($students as $student)$metrics[]=$this->student_metrics($id,(int)$student['student_user_id']);
            $batch=$this->db->select('id,title')->where(array('id'=>$id,'tutor_user_id'=>$teacher_id))->get('tutor_batches')->row_array();if(!$batch)continue;
            foreach(array('attendance','assignment_completion','score','engagement') as $key)$batch[$key]=$metrics?round(array_sum(array_column($metrics,$key))/count($metrics),1):0;
            $batch['students']=count($metrics);$rows[]=$batch;
        }
        return $rows;
    }

    private function attendance_trend(int $teacher_id,array $scope): array
    {
        if(!$scope)return array();
        return $this->db->select("DATE_FORMAT(s.session_date,'%Y-%m') period,ROUND(100*SUM(CASE WHEN a.status IN('present','late') THEN 1 ELSE 0 END)/NULLIF(COUNT(a.id),0),1) value",false)->from('tutor_batch_attendance a')->join('tutor_batch_sessions s','s.id=a.session_id')->where('s.tutor_user_id',$teacher_id)->where_in('s.batch_id',$scope)->where('s.session_date >=',date('Y-m-d',strtotime('-6 months')))->group_by("DATE_FORMAT(s.session_date,'%Y-%m')",false)->order_by('period')->get()->result_array();
    }

    private function score_trend(int $teacher_id,array $scope): array
    {
        if(!$scope)return array();
        return $this->db->select("DATE_FORMAT(a.submitted_at,'%Y-%m') period,ROUND(AVG(a.percentage),1) value",false)->from('tutor_batch_test_attempts a')->join('tutor_batches b','b.id=a.batch_id')->where('b.tutor_user_id',$teacher_id)->where_in('a.batch_id',$scope)->where('a.status','submitted')->where('a.submitted_at >=',date('Y-m-d H:i:s',strtotime('-6 months')))->group_by("DATE_FORMAT(a.submitted_at,'%Y-%m')",false)->order_by('period')->get()->result_array();
    }

    private function topic_mastery(int $teacher_id,array $scope): array
    {
        if(!$this->db->table_exists('assessment_mastery'))return array();
        $this->db->select('m.dimension_label,ROUND(AVG(m.score_percentage),1) score,COUNT(DISTINCT m.student_user_id) students')->from('assessment_mastery m');
        $this->db->where("(EXISTS(SELECT 1 FROM content_exams e WHERE e.id=m.cohort_id AND m.cohort_type='exam' AND e.tutor_id=".(int)$teacher_id.")".($scope?" OR (m.cohort_type='batch' AND m.cohort_id IN(".implode(',',array_map('intval',$scope))."))":'').")",null,false);
        return $this->db->group_by('m.dimension_label')->order_by('score','ASC')->limit(15)->get()->result_array();
    }

    private function interventions(int $teacher_id,int $batch_id): array
    {
        $this->db->select('i.*,u.first_name,u.last_name,b.title batch_title')->from('teacher_student_interventions i')->join('users u','u.id=i.student_user_id','left')->join('tutor_batches b','b.id=i.batch_id','left')->where('i.tutor_user_id',$teacher_id);
        if($batch_id>0)$this->db->where('i.batch_id',$batch_id);
        return $this->db->where_in('i.status',array('planned','in_progress'))->order_by('i.due_at','ASC')->order_by('i.id','DESC')->limit(30)->get()->result_array();
    }

    private function moderation_turnaround(): array
    {
        $rows=array();
        foreach(array('content_exams','question_bank_questions') as $table){
            if(!$this->db->table_exists($table))continue;
            $rows=array_merge($rows,$this->db->select('submitted_for_review_at,reviewed_at,review_status')->where('submitted_for_review_at IS NOT NULL',null,false)->get($table)->result_array());
        }
        $hours=array();$pending=0;foreach($rows as $row){if(!empty($row['reviewed_at']))$hours[]=(strtotime($row['reviewed_at'])-strtotime($row['submitted_for_review_at']))/3600;else$pending++;}
        return array('items'=>count($rows),'pending'=>$pending,'average_hours'=>$hours?round(array_sum($hours)/count($hours),1):null,'within_72_percent'=>$hours?round(count(array_filter($hours,function($h){return $h<=72;}))*100/count($hours),1):0);
    }
}
