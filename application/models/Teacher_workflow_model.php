<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teacher_workflow_model extends CI_Model
{
    public function get_daily_workspace(int $teacher_id, string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');
        $this->sync_generated_agenda($teacher_id, $date);
        return $this->db->where('teacher_user_id', $teacher_id)
            ->where_not_in('status', array('done','cancelled'))
            ->group_start()->where('due_at IS NULL', null, false)->or_where('DATE(due_at) <=', $date)->group_end()
            ->group_start()->where('snoozed_until IS NULL', null, false)->or_where('snoozed_until <=', date('Y-m-d H:i:s'))->group_end()
            ->order_by('priority', 'DESC')->order_by('due_at', 'ASC')->limit(40)
            ->get('teacher_workspace_items')->result_array();
    }

    public function get_calendar(int $teacher_id, string $from, string $to): array
    {
        return $this->db->select('s.id,s.batch_id,s.title,s.agenda,s.session_date,s.start_time,s.end_time,s.timezone,s.session_type,s.session_status,s.provider_type,b.title batch_title')
            ->from('tutor_batch_sessions s')->join('tutor_batches b', 'b.id=s.batch_id', 'left')
            ->where('s.tutor_user_id', $teacher_id)->where('s.session_date >=', $from)->where('s.session_date <=', $to)
            ->where_not_in('s.session_status', array('cancelled','archived'))->order_by('s.session_date')->order_by('s.start_time')->get()->result_array();
    }

    public function complete_workspace_item(int $id, int $teacher_id): bool
    {
        return (bool)$this->db->where('id', $id)->where('teacher_user_id', $teacher_id)->update('teacher_workspace_items', array('status'=>'done','completed_at'=>date('Y-m-d H:i:s')));
    }

    public function autosave(int $teacher_id, string $entity_type, string $entity_id, string $editor_key, array $content, string $save_type = 'autosave'): array
    {
        $hash = hash('sha256', json_encode($content));
        $latest = $this->db->where(array('teacher_user_id'=>$teacher_id,'entity_type'=>$entity_type,'entity_id'=>$entity_id,'editor_key'=>$editor_key))
            ->order_by('version_no','DESC')->limit(1)->get('teacher_draft_versions')->row_array();
        if ($latest && hash_equals((string)$latest['content_hash'], $hash)) {
            return array('status'=>true,'unchanged'=>true,'version'=>(int)$latest['version_no'],'saved_at'=>$latest['created_at']);
        }
        $version = (int)($latest['version_no'] ?? 0) + 1;
        $this->db->insert('teacher_draft_versions', array(
            'teacher_user_id'=>$teacher_id,'entity_type'=>substr($entity_type,0,60),'entity_id'=>substr($entity_id,0,100),
            'editor_key'=>substr($editor_key ?: 'default',0,100),'version_no'=>$version,'content_json'=>json_encode($content),
            'content_hash'=>$hash,'save_type'=>in_array($save_type,array('autosave','manual','recovery'),true)?$save_type:'autosave'
        ));
        $this->prune_versions($teacher_id, $entity_type, $entity_id, $editor_key);
        return array('status'=>true,'version'=>$version,'saved_at'=>date('Y-m-d H:i:s'));
    }

    public function get_versions(int $teacher_id, string $entity_type, string $entity_id, string $editor_key = 'default'): array
    {
        return $this->db->where(array('teacher_user_id'=>$teacher_id,'entity_type'=>$entity_type,'entity_id'=>$entity_id,'editor_key'=>$editor_key))
            ->order_by('version_no','DESC')->limit(20)->get('teacher_draft_versions')->result_array();
    }

    public function create_template_from_batch(int $batch_id, int $teacher_id, string $name): array
    {
        $batch = $this->db->get_where('tutor_batches', array('id'=>$batch_id,'tutor_user_id'=>$teacher_id), 1)->row_array();
        if (!$batch) return array('status'=>false,'message'=>'Batch not found.');
        $payload = array(
            'batch'=>array_intersect_key($batch, array_flip(array('category_id','class_id','subject_id','title','description','delivery_mode','capacity','price','enrollment_mode','waitlist_enabled','start_date','end_date'))),
            'sessions'=>$this->db->where('batch_id',$batch_id)->order_by('session_date')->order_by('start_time')->get('tutor_batch_sessions')->result_array(),
            'tasks'=>$this->db->where('batch_id',$batch_id)->get('tutor_batch_tasks')->result_array(),
            'tests'=>$this->db->where('batch_id',$batch_id)->get('tutor_batch_tests')->result_array(),
            'test_questions'=>$this->db->select('q.*')->from('tutor_batch_test_questions q')->join('tutor_batch_tests t','t.id=q.test_id')->where('t.batch_id',$batch_id)->order_by('q.test_id')->order_by('q.id')->get()->result_array(),
        );
        $this->db->insert('tutor_batch_templates', array('tutor_user_id'=>$teacher_id,'name'=>$name ?: $batch['title'].' template','description'=>'Reusable structure from '.$batch['title'],'template_json'=>json_encode($payload),'source_batch_id'=>$batch_id));
        return array('status'=>true,'message'=>'Batch template created.','template_id'=>(int)$this->db->insert_id());
    }

    public function get_templates(int $teacher_id): array
    {
        return $this->db->where('tutor_user_id',$teacher_id)->where('is_active',1)->order_by('updated_at','DESC')->order_by('id','DESC')->get('tutor_batch_templates')->result_array();
    }

    public function create_batch_from_template(int $template_id, int $teacher_id, string $title, string $start_date): array
    {
        $template=$this->db->get_where('tutor_batch_templates',array('id'=>$template_id,'tutor_user_id'=>$teacher_id,'is_active'=>1),1)->row_array();
        if(!$template)return array('status'=>false,'message'=>'Batch template not found.');
        if(!empty($template['source_batch_id'])){
            $source=$this->db->get_where('tutor_batches',array('id'=>$template['source_batch_id'],'tutor_user_id'=>$teacher_id),1)->row_array();
            if($source)return $this->duplicate_batch((int)$source['id'],$teacher_id,$title,$start_date,false);
        }
        $payload=json_decode((string)$template['template_json'],true);
        if(!is_array($payload)||empty($payload['batch']))return array('status'=>false,'message'=>'Template data is invalid.');
        $target=DateTimeImmutable::createFromFormat('Y-m-d',$start_date);
        if(!$target)return array('status'=>false,'message'=>'A valid start date is required.');
        $batch=$payload['batch'];
        $source_start=!empty($batch['start_date'])?new DateTimeImmutable($batch['start_date']):$target;
        $shift=(int)$source_start->diff($target)->format('%r%a');
        $allowed=array('category_id','class_id','subject_id','description','delivery_mode','capacity','price','enrollment_mode','waitlist_enabled');
        $batch=array_intersect_key($batch,array_flip($allowed));
        $batch['tutor_user_id']=$teacher_id;
        $batch['title']=trim($title)?:$template['name'];
        $batch['slug']=url_title($batch['title'],'dash',true).'-'.substr(bin2hex(random_bytes(4)),0,8);
        do{$batch['batch_code']='B'.strtoupper(substr(bin2hex(random_bytes(5)),0,8));}while($this->db->where('batch_code',$batch['batch_code'])->count_all_results('tutor_batches')>0);
        $batch['status']='draft';
        $batch['start_date']=$target->format('Y-m-d');
        $batch['created_at']=date('Y-m-d H:i:s');
        $batch['updated_at']=date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $this->db->insert('tutor_batches',$batch);
        $new_id=(int)$this->db->insert_id();
        foreach(($payload['sessions']??array()) as $row){
            foreach(array('id','created_at','updated_at','published_at','started_at','ended_at','attendance_opened_at') as $key)unset($row[$key]);
            $row['batch_id']=$new_id;$row['tutor_user_id']=$teacher_id;$row['session_status']='draft';$row['recurrence_parent_id']=null;
            if(!empty($row['session_date']))$row['session_date']=(new DateTimeImmutable($row['session_date']))->modify(($shift>=0?'+':'').$shift.' days')->format('Y-m-d');
            $row['created_at']=date('Y-m-d H:i:s');$row['updated_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_sessions',$row);
        }
        foreach(($payload['tasks']??array()) as $row){
            foreach(array('id','created_at','updated_at') as $key)unset($row[$key]);
            $row['batch_id']=$new_id;$row['tutor_user_id']=$teacher_id;$row['evaluation_status']='draft';
            foreach(array('available_from','due_at') as $field)if(!empty($row[$field]))$row[$field]=(new DateTimeImmutable($row[$field]))->modify(($shift>=0?'+':'').$shift.' days')->format('Y-m-d H:i:s');
            $row['created_at']=date('Y-m-d H:i:s');$row['updated_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_tasks',$row);
        }
        $test_ids=array();
        foreach(($payload['tests']??array()) as $row){
            $old_id=(int)($row['id']??0);
            foreach(array('id','created_at','updated_at','published_at') as $key)unset($row[$key]);
            $row['batch_id']=$new_id;$row['tutor_user_id']=$teacher_id;$row['status']='draft';
            $row['created_at']=date('Y-m-d H:i:s');$row['updated_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_tests',$row);
            if($old_id>0)$test_ids[$old_id]=(int)$this->db->insert_id();
        }
        foreach(($payload['test_questions']??array()) as $row){
            $old_test=(int)($row['test_id']??0);
            if(empty($test_ids[$old_test]))continue;
            foreach(array('id','created_at','updated_at') as $key)unset($row[$key]);
            $row['test_id']=$test_ids[$old_test];$row['created_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_test_questions',$row);
        }
        if($this->db->trans_status()){$this->db->trans_commit();return array('status'=>true,'message'=>'Batch created from template.','batch_id'=>$new_id);}
        $this->db->trans_rollback();
        return array('status'=>false,'message'=>'Batch could not be created from the template.');
    }

    public function duplicate_batch(int $source_id, int $teacher_id, string $title, string $start_date, bool $copy_students = false): array
    {
        $source = $this->db->get_where('tutor_batches', array('id'=>$source_id,'tutor_user_id'=>$teacher_id), 1)->row_array();
        if (!$source) return array('status'=>false,'message'=>'Source batch not found.');
        $target_start = DateTimeImmutable::createFromFormat('Y-m-d', $start_date);
        if (!$target_start) return array('status'=>false,'message'=>'A valid target start date is required.');
        $source_start = new DateTimeImmutable($source['start_date'] ?: date('Y-m-d'));
        $shift_days = (int)$source_start->diff($target_start)->format('%r%a');
        $this->db->trans_start();
        $batch = $source;
        unset($batch['id'],$batch['created_at'],$batch['updated_at'],$batch['published_at'],$batch['health_score'],$batch['health_updated_at']);
        $batch['title'] = trim($title) ?: $source['title'].' Copy';
        $batch['slug'] = url_title($batch['title'],'dash',true);
        $batch['batch_code'] = null;
        $batch['status'] = 'draft';
        $batch['start_date'] = $target_start->format('Y-m-d');
        $batch['end_date'] = !empty($source['end_date']) ? (new DateTimeImmutable($source['end_date']))->modify(($shift_days>=0?'+':'').$shift_days.' days')->format('Y-m-d') : null;
        $this->db->insert('tutor_batches',$batch);
        $new_id=(int)$this->db->insert_id();
        foreach ($this->db->where('batch_id',$source_id)->get('tutor_batch_sessions')->result_array() as $row) {
            unset($row['id'],$row['created_at'],$row['updated_at'],$row['published_at'],$row['started_at'],$row['ended_at'],$row['attendance_opened_at']);
            $row['batch_id']=$new_id; $row['session_status']='draft'; $row['recurrence_parent_id']=null;
            $row['session_date']=(new DateTimeImmutable($row['session_date']))->modify(($shift_days>=0?'+':'').$shift_days.' days')->format('Y-m-d');
            $this->db->insert('tutor_batch_sessions',$row);
        }
        foreach ($this->db->where('batch_id',$source_id)->get('tutor_batch_tasks')->result_array() as $row) {
            unset($row['id'],$row['created_at'],$row['updated_at']); $row['batch_id']=$new_id; $row['evaluation_status']='draft';
            if (!empty($row['available_from'])) $row['available_from']=(new DateTimeImmutable($row['available_from']))->modify(($shift_days>=0?'+':'').$shift_days.' days')->format('Y-m-d H:i:s');
            if (!empty($row['due_at'])) $row['due_at']=(new DateTimeImmutable($row['due_at']))->modify(($shift_days>=0?'+':'').$shift_days.' days')->format('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_tasks',$row);
        }
        $test_ids=array();
        foreach ($this->db->where('batch_id',$source_id)->get('tutor_batch_tests')->result_array() as $row) {
            $old_id=(int)$row['id'];
            unset($row['id'],$row['created_at'],$row['updated_at'],$row['published_at']);
            $row['batch_id']=$new_id;$row['tutor_user_id']=$teacher_id;$row['status']='draft';
            $row['created_at']=date('Y-m-d H:i:s');$row['updated_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_tests',$row);
            $test_ids[$old_id]=(int)$this->db->insert_id();
        }
        if($test_ids){
            foreach($this->db->where_in('test_id',array_keys($test_ids))->get('tutor_batch_test_questions')->result_array() as $row){
                $old_test=(int)$row['test_id'];
                unset($row['id'],$row['created_at'],$row['updated_at']);
                $row['test_id']=$test_ids[$old_test];$row['created_at']=date('Y-m-d H:i:s');
                $this->db->insert('tutor_batch_test_questions',$row);
            }
        }
        if ($copy_students) foreach ($this->db->where('batch_id',$source_id)->where_in('membership_status',array('active','completed'))->get('tutor_batch_students')->result_array() as $row) {
            unset($row['id'],$row['created_at'],$row['updated_at'],$row['joined_at'],$row['left_at']); $row['batch_id']=$new_id; $row['membership_status']='invited'; $row['progress_percent']=0; $row['attendance_percent']=0;
            $this->db->insert('tutor_batch_students',$row);
        }
        $this->db->trans_complete();
        return array('status'=>$this->db->trans_status(),'message'=>$this->db->trans_status()?'Batch duplicated with dates shifted by '.$shift_days.' day(s).':'Batch duplication failed.','batch_id'=>$new_id);
    }

    public function check_session_conflict(int $teacher_id, string $date, string $start, string $end, int $exclude_id = 0): array
    {
        $this->db->select('s.id,s.title,s.start_time,s.end_time,b.title batch_title')->from('tutor_batch_sessions s')->join('tutor_batches b','b.id=s.batch_id','left')
            ->where('s.tutor_user_id',$teacher_id)->where('s.session_date',$date)->where_not_in('s.session_status',array('cancelled','archived'))
            ->where('s.start_time <',$end)->where('s.end_time >',$start);
        if ($exclude_id > 0) $this->db->where('s.id !=',$exclude_id);
        $rows=$this->db->get()->result_array();
        return array('has_conflict'=>!empty($rows),'conflicts'=>$rows);
    }

    public function reschedule_session(int $session_id, int $teacher_id, string $date, string $start, string $end): array
    {
        $session=$this->db->get_where('tutor_batch_sessions',array('id'=>$session_id,'tutor_user_id'=>$teacher_id),1)->row_array();
        if(!$session)return array('status'=>false,'message'=>'Session not found.');
        $start_at=DateTimeImmutable::createFromFormat('Y-m-d H:i',$date.' '.substr($start,0,5));
        $end_at=DateTimeImmutable::createFromFormat('Y-m-d H:i',$date.' '.substr($end,0,5));
        if(!$start_at||!$end_at||$end_at<=$start_at)return array('status'=>false,'message'=>'Enter a valid date and time range.','batch_id'=>(int)$session['batch_id']);
        $conflict=$this->check_session_conflict($teacher_id,$date,$start,$end,$session_id);
        if($conflict['has_conflict'])return array('status'=>false,'message'=>'The new time conflicts with another session.','batch_id'=>(int)$session['batch_id']);
        $before=array_intersect_key($session,array_flip(array('session_date','start_time','end_time','timezone')));
        $this->db->where('id',$session_id)->update('tutor_batch_sessions',array('session_date'=>$date,'start_time'=>$start_at->format('H:i:s'),'end_time'=>$end_at->format('H:i:s'),'updated_at'=>date('Y-m-d H:i:s')));
        $this->load->model('Immutable_audit_model','immutable_audit');
        $this->immutable_audit->record('attendance','session_rescheduled','tutor_batch_session',$session_id,$before,array('session_date'=>$date,'start_time'=>$start,'end_time'=>$end),array('actor_user_id'=>$teacher_id,'actor_role'=>'tutor'));
        $this->schedule_default_reminders((int)$session['batch_id'],$teacher_id);
        return array('status'=>true,'message'=>'Session rescheduled.','batch_id'=>(int)$session['batch_id']);
    }

    public function create_recurring_sessions(int $parent_id, int $teacher_id, string $frequency, int $occurrences): array
    {
        $parent=$this->db->get_where('tutor_batch_sessions',array('id'=>$parent_id,'tutor_user_id'=>$teacher_id),1)->row_array();
        if (!$parent) return array('status'=>false,'message'=>'Session not found.');
        $days=$frequency==='biweekly'?14:($frequency==='daily'?1:7);
        $occurrences=max(1,min(52,$occurrences)); $created=0; $conflicts=array();
        for($i=1;$i<$occurrences;$i++){
            $date=(new DateTimeImmutable($parent['session_date']))->modify('+'.($days*$i).' days')->format('Y-m-d');
            $check=$this->check_session_conflict($teacher_id,$date,$parent['start_time'],$parent['end_time']);
            if($check['has_conflict']){$conflicts[]=$date;continue;}
            $row=$parent; unset($row['id'],$row['created_at'],$row['updated_at'],$row['started_at'],$row['ended_at'],$row['attendance_opened_at']);
            $row['session_date']=$date;$row['recurrence_parent_id']=$parent_id;$row['recurrence_rule']=strtoupper($frequency).';COUNT='.$occurrences;$row['created_at']=date('Y-m-d H:i:s');
            $this->db->insert('tutor_batch_sessions',$row);$created++;
        }
        return array('status'=>true,'created'=>$created,'conflicts'=>$conflicts,'message'=>$created.' recurring session(s) created'.($conflicts?' with '.count($conflicts).' conflict(s) skipped.':'.'));
    }

    public function review_enrollment(int $request_id, int $teacher_id, string $action): array
    {
        $request=$this->db->select('r.*,b.tutor_user_id,b.capacity,b.waitlist_enabled')->from('tutor_batch_enrollment_requests r')->join('tutor_batches b','b.id=r.batch_id')->where('r.id',$request_id)->where('b.tutor_user_id',$teacher_id)->get()->row_array();
        if(!$request||!in_array($action,array('approved','rejected'),true)) return array('status'=>false,'message'=>'Enrollment request not found.');
        $active=(int)$this->db->where('batch_id',(int)$request['batch_id'])->where('membership_status','active')->count_all_results('tutor_batch_students');
        if($action==='approved'&&$active>=(int)$request['capacity']){
            if(empty($request['waitlist_enabled'])) return array('status'=>false,'message'=>'Batch capacity is full.');
            $position=(int)$this->db->where('batch_id',(int)$request['batch_id'])->count_all_results('tutor_batch_waitlist')+1;
            $this->db->replace('tutor_batch_waitlist',array('batch_id'=>$request['batch_id'],'student_user_id'=>$request['student_user_id'],'position'=>$position,'parent_name'=>$request['parent_name'],'parent_email'=>$request['parent_email'],'parent_phone'=>$request['parent_phone']));
            $action='waitlisted';
        } elseif($action==='approved'){
            $member=array('batch_id'=>$request['batch_id'],'student_user_id'=>$request['student_user_id'],'membership_status'=>'active','joined_at'=>date('Y-m-d H:i:s'),'parent_name'=>$request['parent_name'],'parent_email'=>$request['parent_email'],'parent_phone'=>$request['parent_phone'],'enrollment_approved_by'=>$teacher_id,'enrollment_approved_at'=>date('Y-m-d H:i:s'));
            $existing=$this->db->get_where('tutor_batch_students',array('batch_id'=>$request['batch_id'],'student_user_id'=>$request['student_user_id']),1)->row_array();
            if($existing)$this->db->where('id',$existing['id'])->update('tutor_batch_students',$member);else{$member['created_at']=date('Y-m-d H:i:s');$this->db->insert('tutor_batch_students',$member);}
        }
        $this->db->where('id',$request_id)->update('tutor_batch_enrollment_requests',array('status'=>$action,'reviewed_by'=>$teacher_id,'reviewed_at'=>date('Y-m-d H:i:s')));
        $this->add_student_history((int)$request['batch_id'],(int)$request['student_user_id'],'enrollment_'.$action,'pending',$action,array(),$teacher_id);
        return array('status'=>true,'message'=>'Enrollment request '.$action.'.','batch_id'=>(int)$request['batch_id']);
    }

    public function calculate_batch_health(int $batch_id, int $teacher_id): array
    {
        $batch=$this->db->get_where('tutor_batches',array('id'=>$batch_id,'tutor_user_id'=>$teacher_id),1)->row_array();
        if(!$batch)return array('score'=>0,'label'=>'Unavailable');
        $students=$this->db->where('batch_id',$batch_id)->where('membership_status','active')->get('tutor_batch_students')->result_array();
        if(!$students)return array('score'=>0,'label'=>'No students','components'=>array());
        $attendance=array_sum(array_map(fn($s)=>(float)$s['attendance_percent'],$students))/count($students);
        $completion=array_sum(array_map(fn($s)=>(float)$s['progress_percent'],$students))/count($students);
        $scores=$this->db->select_avg('percentage','avg_score')->where('batch_id',$batch_id)->where('status','submitted')->get('tutor_batch_test_attempts')->row('avg_score');
        $scores=$scores===null?50:(float)$scores;
        $recent=(int)$this->db->from('message_campaign_recipients r')->join('message_campaigns c','c.id=r.campaign_id')->where('c.reference_type','batch')->where('c.reference_id',$batch_id)->where('r.status','sent')->where('r.created_at >=',date('Y-m-d H:i:s',strtotime('-30 days')))->count_all_results();
        $engagement=min(100,20+($recent/max(1,count($students)))*20);
        $score=round($attendance*.35+$completion*.30+$scores*.25+$engagement*.10,1);
        $this->db->where('id',$batch_id)->update('tutor_batches',array('health_score'=>$score,'health_updated_at'=>date('Y-m-d H:i:s')));
        return array('score'=>$score,'label'=>$score>=80?'Healthy':($score>=60?'Watch':'At risk'),'components'=>array('attendance'=>round($attendance,1),'completion'=>round($completion,1),'scores'=>round($scores,1),'engagement'=>round($engagement,1)));
    }

    public function schedule_default_reminders(int $batch_id, int $teacher_id): int
    {
        $created=0;
        foreach($this->db->where('batch_id',$batch_id)->where('tutor_user_id',$teacher_id)->where('session_date >=',date('Y-m-d'))->get('tutor_batch_sessions')->result_array() as $session){
            $minutes=max(5,(int)$session['reminder_minutes']); $when=date('Y-m-d H:i:s',strtotime($session['session_date'].' '.$session['start_time'].' -'.$minutes.' minutes'));
            $key='session:'.$session['id'].':'.$when;
            $this->db->query("INSERT IGNORE INTO teacher_automated_reminders(tutor_user_id,batch_id,reminder_type,reference_type,reference_id,scheduled_at,channels_json,dedupe_key) VALUES(?,?,'session','session',?,?,'[\"email\",\"in_app\"]',?)",array($teacher_id,$batch_id,$session['id'],$when,$key));
            $created += $this->db->affected_rows();
        }
        foreach($this->db->where('batch_id',$batch_id)->where('tutor_user_id',$teacher_id)->where('evaluation_status','published')->where('due_at >=',date('Y-m-d H:i:s'))->get('tutor_batch_tasks')->result_array() as $task){
            $when=date('Y-m-d H:i:s',strtotime($task['due_at'].' -24 hours'));$key='task:'.$task['id'].':'.$when;
            $this->db->query("INSERT IGNORE INTO teacher_automated_reminders(tutor_user_id,batch_id,reminder_type,reference_type,reference_id,scheduled_at,channels_json,dedupe_key) VALUES(?,?,'assignment','task',?,?,'[\"email\",\"in_app\"]',?)",array($teacher_id,$batch_id,$task['id'],$when,$key));
            $created += $this->db->affected_rows();
        }
        return $created;
    }

    public function process_due_reminders(int $limit = 100): array
    {
        $rows=$this->db->where('status','scheduled')->where('scheduled_at <=',date('Y-m-d H:i:s'))->order_by('scheduled_at')->limit(max(1,min(500,$limit)))->get('teacher_automated_reminders')->result_array();
        $sent=0;$failed=0;
        $this->load->library('Communication_service');
        foreach($rows as $row){
            $this->db->where('id',$row['id'])->where('status','scheduled')->update('teacher_automated_reminders',array('status'=>'processing'));
            if($this->db->affected_rows()!==1)continue;
            $students=array();
            if(!empty($row['recipient_user_id']))$students=$this->db->where('id',$row['recipient_user_id'])->get('users')->result_array();
            elseif(!empty($row['batch_id']))$students=$this->db->select('u.*')->from('tutor_batch_students bs')->join('users u','u.id=bs.student_user_id')->where('bs.batch_id',$row['batch_id'])->where_in('bs.membership_status',array('active','completed'))->get()->result_array();
            $title=ucfirst($row['reminder_type']).' reminder';
            $message=$this->reminder_message($row);
            try{
                $result=$this->communication_service->send_campaign(array('event_key'=>'teacher-reminder:'.$row['id'],'campaign_type'=>'automatic','trigger_event'=>$row['reminder_type'].'_reminder','reference_type'=>$row['reference_type'],'reference_id'=>$row['reference_id'],'title'=>$title,'email_body'=>$message,'whatsapp_body'=>$message,'created_by'=>$row['tutor_user_id'],'created_role'=>'tutor'),$students,json_decode($row['channels_json'],true)?:array('in_app'));
                $ok=!empty($result['status']);$this->db->where('id',$row['id'])->update('teacher_automated_reminders',array('status'=>$ok?'sent':'failed','sent_at'=>$ok?date('Y-m-d H:i:s'):null,'error_message'=>$ok?null:($result['message']??'Delivery failed.')));$ok?$sent++:$failed++;
            }catch(Throwable $e){$this->db->where('id',$row['id'])->update('teacher_automated_reminders',array('status'=>'failed','error_message'=>$e->getMessage()));$failed++;}
        }
        return array('processed'=>count($rows),'sent'=>$sent,'failed'=>$failed);
    }

    private function sync_generated_agenda(int $teacher_id, string $date): void
    {
        foreach($this->get_calendar($teacher_id,$date,$date) as $s)$this->upsert_agenda($teacher_id,'class','session',$s['id'],$s['title'].' - '.$s['batch_title'],90,$date.' '.$s['start_time'],site_url('tutor_batch/manage/'.$s['batch_id'].'/schedule'),'session:'.$s['id']);
        $grading=$this->db->select('sub.id,t.title,sub.batch_id,sub.submitted_at')->from('tutor_batch_assignment_submissions sub')->join('tutor_batch_tasks t','t.id=sub.task_id')->where('t.tutor_user_id',$teacher_id)->where('sub.evaluation_status !=','evaluated')->order_by('sub.submitted_at')->limit(20)->get()->result_array();
        foreach($grading as $g)$this->upsert_agenda($teacher_id,'grading','submission',$g['id'],'Grade: '.$g['title'],80,$g['submitted_at'],site_url('tutor_batch/manage/'.$g['batch_id'].'/assignments'),'grading:'.$g['id']);
        $requests=$this->db->where('tutor_user_id',$teacher_id)->where('status','pending')->order_by('created_at')->limit(20)->get('tutor_requests')->result_array();
        foreach($requests as $r)$this->upsert_agenda($teacher_id,'request','tutor_request',$r['id'],'Review student request',75,$r['created_at'],site_url('user/student_requests'),'request:'.$r['id']);
        foreach($this->db->select('id,batch_id,title,due_at')->where('tutor_user_id',$teacher_id)->where('evaluation_status','published')->where('due_at <',date('Y-m-d H:i:s'))->get('tutor_batch_tasks')->result_array() as $t)$this->upsert_agenda($teacher_id,'overdue','task',$t['id'],'Overdue follow-up: '.$t['title'],95,$t['due_at'],site_url('tutor_batch/manage/'.$t['batch_id'].'/tasks'),'overdue:'.$t['id']);
    }

    private function upsert_agenda(int $teacher,string $type,string $refType,$refId,string $title,int $priority,$due,string $url,string $source): void
    {
        $sql="INSERT INTO teacher_workspace_items(teacher_user_id,item_type,reference_type,reference_id,title,priority,due_at,action_url,source_key) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),priority=VALUES(priority),due_at=VALUES(due_at),action_url=VALUES(action_url)";
        $this->db->query($sql,array($teacher,$type,$refType,$refId,$title,$priority,$due,$url,$source));
    }

    private function add_student_history(int $batch,int $student,string $event,?string $from,?string $to,array $details,int $actor): void
    {
        $this->db->insert('tutor_batch_student_history',array('batch_id'=>$batch,'student_user_id'=>$student,'event_type'=>$event,'from_status'=>$from,'to_status'=>$to,'details_json'=>json_encode($details),'actor_user_id'=>$actor));
    }

    private function prune_versions(int $teacher,string $type,string $id,string $editor): void
    {
        $rows=$this->db->select('id')->where(array('teacher_user_id'=>$teacher,'entity_type'=>$type,'entity_id'=>$id,'editor_key'=>$editor))->order_by('version_no','DESC')->limit(100,20)->get('teacher_draft_versions')->result_array();
        if($rows)$this->db->where_in('id',array_column($rows,'id'))->delete('teacher_draft_versions');
    }

    private function reminder_message(array $row): string
    {
        $type=$row['reminder_type'];
        if($type==='session')return 'Reminder: your batch session starts soon. Open your Lvalues dashboard for timing and join details.';
        if($type==='assignment'||$type==='test')return 'Reminder: assigned work is due soon. Open your Lvalues dashboard to review and submit it.';
        if($type==='absence')return 'You were marked absent for a recent class. Please contact your teacher and review the class material.';
        if($type==='invitation')return 'Your batch invitation is still pending. Please review it before it expires.';
        return 'You have a new teaching reminder in Lvalues.';
    }
}
