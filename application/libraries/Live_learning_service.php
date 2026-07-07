<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Live_learning_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function providers(int $owner_user_id = 0): array
    {
        return $this->CI->db->group_start()->where('owner_user_id IS NULL',null,false)->or_where('owner_user_id',$owner_user_id)->group_end()
            ->where('is_enabled',1)->order_by('is_default','DESC')->order_by('display_name')->get('live_provider_configs')->result_array();
    }

    public function connection_test(string $provider_key, string $join_url): array
    {
        $result=array('provider'=>$provider_key,'url_valid'=>filter_var($join_url,FILTER_VALIDATE_URL)!==false,'reachable'=>false,'latency_ms'=>null,'checked_at'=>date(DATE_ATOM));
        if(!$result['url_valid'])return $result;
        $host=(string)parse_url($join_url,PHP_URL_HOST);
        $ips=$host!==''?gethostbynamel($host):false;
        if(!$ips||array_filter($ips,function($ip){return filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)===false;})){
            $result['error']='The meeting host does not resolve to a public network address.';
            return $result;
        }
        $start=microtime(true);$ch=curl_init($join_url);
        curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_NOBODY=>true,CURLOPT_TIMEOUT=>5,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_FOLLOWLOCATION=>false));
        curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
        $result['latency_ms']=(int)round((microtime(true)-$start)*1000);$result['http_status']=$status;$result['reachable']=$error===''&&$status>=200&&$status<500;$result['error']=$error;
        return $result;
    }

    public function join_decision(int $session_id, int $user_id, string $role): array
    {
        $session=$this->CI->db->get_where('tutor_batch_sessions',array('id'=>$session_id),1)->row_array();
        if(!$session)return array('status'=>false,'decision'=>'denied','message'=>'Session not found.');
        $is_teacher=$role==='tutor'&&(int)$session['tutor_user_id']===$user_id;
        $is_student=$role==='student'&&$this->CI->db->where('batch_id',$session['batch_id'])->where('student_user_id',$user_id)->where_in('membership_status',array('active','completed'))->count_all_results('tutor_batch_students')>0;
        if(!$is_teacher&&!$is_student)return $this->log_decision($session,$user_id,$role,'denied','You are not enrolled in this session.');
        if($is_student&&!in_array((string)($session['session_status']??''),array('scheduled','live'),true))return $this->log_decision($session,$user_id,$role,'denied','This class is not open for joining.');
        $tz=new DateTimeZone($session['timezone'] ?: 'UTC');
        $start=new DateTimeImmutable($session['session_date'].' '.$session['start_time'],$tz);
        $end=new DateTimeImmutable($session['session_date'].' '.$session['end_time'],$tz);
        $now=new DateTimeImmutable('now',$tz);
        if(!$is_teacher&&$now<$start->modify('-'.max(0,(int)$session['join_opens_minutes']).' minutes'))return $this->log_decision($session,$user_id,$role,'too_early','Joining opens shortly before the scheduled start.');
        if(!$is_teacher&&$now>$end->modify('+'.max(0,(int)$session['join_closes_minutes']).' minutes'))return $this->log_decision($session,$user_id,$role,'too_late','The join window has closed.');
        if(!empty($session['recording_consent_required'])&&!$is_teacher){
            $consent=$this->CI->db->get_where('live_recording_consents',array('session_id'=>$session_id,'user_id'=>$user_id,'consent_status'=>'granted'),1)->row_array();
            if(!$consent)return array('status'=>false,'decision'=>'consent_required','message'=>'Recording consent is required before joining.','session_id'=>$session_id);
        }
        $primary=$is_teacher?($session['host_join_url']?:$session['meeting_url']):($session['student_join_url']?:$session['meeting_url']);
        $primary=$this->sanitize_url($primary);$fallback=$this->sanitize_url($session['fallback_join_url']??'');
        if($primary===''){
            if($fallback!=='')return $this->log_decision($session,$user_id,$role,'fallback','Primary meeting link is unavailable. Using fallback.',$fallback);
            return $this->log_decision($session,$user_id,$role,'denied','Meeting link is not available.');
        }
        return $this->log_decision($session,$user_id,$role,'allowed','Join permitted.',$primary);
    }

    public function record_consent(int $session_id,int $user_id,string $status): bool
    {
        if(!in_array($status,array('granted','declined','revoked'),true))return false;
        if(!$this->can_access_session($session_id,$user_id))return false;
        $data=array('session_id'=>$session_id,'user_id'=>$user_id,'consent_status'=>$status,'captured_at'=>date('Y-m-d H:i:s'),'revoked_at'=>$status==='revoked'?date('Y-m-d H:i:s'):null);
        return (bool)$this->CI->db->replace('live_recording_consents',$data);
    }

    public function report_incident(int $session_id,int $user_id,array $input): array
    {
        if(!$this->can_access_session($session_id,$user_id))return array('status'=>false,'message'=>'Session not found or access denied.');
        $type=in_array($input['incident_type']??'',array('connection','provider','audio','video','recording','attendance','other'),true)?$input['incident_type']:'other';
        $severity=in_array($input['severity']??'',array('low','medium','high','critical'),true)?$input['severity']:'medium';
        $this->CI->db->insert('live_session_incidents',array('session_id'=>$session_id,'reported_by'=>$user_id,'incident_type'=>$type,'severity'=>$severity,'description'=>trim((string)($input['description']??'')),'provider_key'=>$input['provider_key']??null));
        return array('status'=>true,'message'=>'Live-session incident recorded.','incident_id'=>(int)$this->CI->db->insert_id());
    }

    public function playback_event(int $session_id,int $user_id,array $input): bool
    {
        if(!$this->can_access_session($session_id,$user_id))return false;
        $type=in_array($input['event_type']??'',array('start','pause','resume','seek','complete'),true)?$input['event_type']:'start';
        return (bool)$this->CI->db->insert('live_recording_playback_events',array('session_id'=>$session_id,'user_id'=>$user_id,'event_type'=>$type,'position_seconds'=>max(0,(int)($input['position_seconds']??0)),'duration_seconds'=>isset($input['duration_seconds'])?(int)$input['duration_seconds']:null,'playback_rate'=>isset($input['playback_rate'])?(float)$input['playback_rate']:null));
    }

    public function recording_decision(int $session_id,int $user_id): array
    {
        $session=$this->CI->db->get_where('tutor_batch_sessions',array('id'=>$session_id),1)->row_array();
        if(!$session)return array('status'=>false,'message'=>'Recording not found.');
        if(!$this->can_access_session($session_id,$user_id))return array('status'=>false,'message'=>'You are not authorised to view this recording.','batch_id'=>(int)$session['batch_id']);
        if(($session['recording_status']??'')!=='available'||empty($session['recording_url']))return array('status'=>false,'message'=>'Recording is not available yet.','batch_id'=>(int)$session['batch_id']);
        if(!empty($session['recording_expires_at'])&&strtotime((string)$session['recording_expires_at'])<time())return array('status'=>false,'message'=>'Recording access has expired.','batch_id'=>(int)$session['batch_id']);
        $url=$this->sanitize_url($session['recording_url']);
        if($url==='')return array('status'=>false,'message'=>'Recording link is invalid.','batch_id'=>(int)$session['batch_id']);
        $this->playback_event($session_id,$user_id,array('event_type'=>'start','position_seconds'=>0));
        return array('status'=>true,'url'=>$url,'batch_id'=>(int)$session['batch_id']);
    }

    public function sync_attendance_from_joins(int $session_id): int
    {
        $session=$this->CI->db->get_where('tutor_batch_sessions',array('id'=>$session_id),1)->row_array();if(!$session)return 0;
        $rows=$this->CI->db->select('user_id,MIN(created_at) first_join,MAX(created_at) last_join')->where('session_id',$session_id)->where('user_role','student')->where_in('decision',array('allowed','fallback'))->group_by('user_id')->get('live_session_join_logs')->result_array();
        $count=0;
        foreach($rows as $row){
            $existing=$this->CI->db->get_where('tutor_batch_attendance',array('session_id'=>$session_id,'student_user_id'=>$row['user_id']),1)->row_array();
            $data=array('batch_id'=>$session['batch_id'],'session_id'=>$session_id,'student_user_id'=>$row['user_id'],'status'=>'present','marked_by'=>$session['tutor_user_id'],'marked_at'=>$row['first_join'],'updated_at'=>date('Y-m-d H:i:s'));
            if($existing)$this->CI->db->where('id',$existing['id'])->update('tutor_batch_attendance',$data);else{$data['created_at']=date('Y-m-d H:i:s');$this->CI->db->insert('tutor_batch_attendance',$data);}$count++;
        }
        return $count;
    }

    public function teacher_owns_session(int $session_id,int $teacher_id): bool
    {
        return $this->CI->db->where('id',$session_id)->where('tutor_user_id',$teacher_id)->count_all_results('tutor_batch_sessions')===1;
    }

    public function playback_analytics(int $session_id): array
    {
        return $this->CI->db->select('COUNT(DISTINCT user_id) unique_viewers,COUNT(*) total_events,MAX(position_seconds) furthest_position,AVG(playback_rate) average_rate',false)->where('session_id',$session_id)->get('live_recording_playback_events')->row_array()?:array();
    }

    private function log_decision(array $session,int $user,string $role,string $decision,string $message,string $url=''): array
    {
        $this->CI->db->insert('live_session_join_logs',array('session_id'=>$session['id'],'user_id'=>$user,'user_role'=>$role,'decision'=>$decision,'provider_key'=>$session['provider_type'],'joined_at'=>in_array($decision,array('allowed','fallback'),true)?date('Y-m-d H:i:s'):null,'ip_hash'=>hash('sha256',(string)$this->CI->input->ip_address())));
        return array('status'=>in_array($decision,array('allowed','fallback'),true),'decision'=>$decision,'message'=>$message,'url'=>$url,'provider'=>$session['provider_type'],'session_id'=>(int)$session['id']);
    }

    private function sanitize_url($url): string
    {
        $url=trim((string)$url);if($url===''||filter_var($url,FILTER_VALIDATE_URL)===false)return '';
        return in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),array('http','https'),true)?$url:'';
    }

    private function can_access_session(int $session_id,int $user_id): bool
    {
        $session=$this->CI->db->get_where('tutor_batch_sessions',array('id'=>$session_id),1)->row_array();
        if(!$session)return false;
        if((int)$session['tutor_user_id']===$user_id)return true;
        return $this->CI->db->where('batch_id',$session['batch_id'])->where('student_user_id',$user_id)->where_in('membership_status',array('active','completed'))->count_all_results('tutor_batch_students')>0;
    }
}
