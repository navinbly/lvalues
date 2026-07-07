<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Course_workflow_model extends CI_Model
{
    public function get_course(int $course_id): array
    {
        return $this->db->get_where('course',array('id'=>$course_id),1)->row_array()?:array();
    }

    public function can_edit(int $course_id,int $user_id,bool $admin=false): bool
    {
        if($admin)return $this->db->where('id',$course_id)->count_all_results('course')===1;
        return $this->db->where('id',$course_id)->group_start()->where('creator',$user_id)->or_where("FIND_IN_SET(".(int)$user_id.",user_id) >",0,false)->group_end()->count_all_results('course')===1;
    }

    public function snapshot(int $course_id,int $actor_id,string $role,string $summary='Saved version'): array
    {
        $course=$this->get_course($course_id);
        if(!$course)return array('status'=>false,'message'=>'Course not found.');
        $sections=$this->db->where('course_id',$course_id)->order_by('order')->get('section')->result_array();
        $lessons=$this->db->where('course_id',$course_id)->order_by('section_id')->order_by('order')->get('lesson')->result_array();
        $payload=json_encode(array('course'=>$course,'sections'=>$sections,'lessons'=>$lessons));
        $last=$this->db->where('course_id',$course_id)->order_by('version_no','DESC')->get('course_versions',1)->row_array();
        if($last&&hash_equals(hash('sha256',(string)$last['snapshot_json']),hash('sha256',$payload)))return array('status'=>true,'unchanged'=>true,'version_id'=>(int)$last['id'],'version_no'=>(int)$last['version_no']);
        $version=(int)($last['version_no']??0)+1;
        $this->db->insert('course_versions',array('course_id'=>$course_id,'version_no'=>$version,'snapshot_json'=>$payload,'change_summary'=>substr(trim($summary),0,500),'created_by'=>$actor_id,'created_role'=>$role));
        return array('status'=>true,'message'=>'Course version saved.','version_id'=>(int)$this->db->insert_id(),'version_no'=>$version);
    }

    public function versions(int $course_id,int $limit=20): array
    {
        return $this->db->select('v.*,u.first_name,u.last_name')->from('course_versions v')->join('users u','u.id=v.created_by','left')->where('v.course_id',$course_id)->order_by('v.version_no','DESC')->limit($limit)->get()->result_array();
    }

    public function restore(int $version_id,int $actor_id,string $role): array
    {
        $version=$this->db->get_where('course_versions',array('id'=>$version_id),1)->row_array();
        if(!$version)return array('status'=>false,'message'=>'Course version not found.');
        $payload=json_decode((string)$version['snapshot_json'],true);
        if(empty($payload['course'])||!is_array($payload['sections']??null)||!is_array($payload['lessons']??null))return array('status'=>false,'message'=>'Course version is invalid.');
        $course_id=(int)$version['course_id'];
        $current=$this->get_course($course_id);$from=$current['workflow_status']??'draft';
        $this->snapshot($course_id,$actor_id,$role,'Before restoring version '.$version['version_no']);
        $course=$payload['course'];unset($course['id']);$course['workflow_status']='draft';$course['status']='draft';$course['workflow_updated_at']=date('Y-m-d H:i:s');$course['last_modified']=time();
        $this->db->trans_begin();
        $this->db->where('course_id',$course_id)->delete('lesson');
        $this->db->where('course_id',$course_id)->delete('section');
        $this->db->where('id',$course_id)->update('course',$course);
        $section_map=array();
        foreach($payload['sections'] as $row){$old=(int)$row['id'];unset($row['id']);$row['course_id']=$course_id;$this->db->insert('section',$row);$section_map[$old]=(int)$this->db->insert_id();}
        foreach($payload['lessons'] as $row){unset($row['id']);$row['course_id']=$course_id;if(isset($section_map[(int)$row['section_id']]))$row['section_id']=$section_map[(int)$row['section_id']];$this->db->insert('lesson',$row);}
        $this->db->where('id',$version_id)->update('course_versions',array('restored_at'=>date('Y-m-d H:i:s')));
        $this->record_moderation($course_id,'restore',$from,'draft','Restored version '.$version['version_no'],$actor_id,(int)($payload['course']['creator']??0));
        if($this->db->trans_status()){$this->db->trans_commit();return array('status'=>true,'message'=>'Course version restored as a draft.','course_id'=>$course_id);}
        $this->db->trans_rollback();return array('status'=>false,'message'=>'Course version could not be restored.','course_id'=>$course_id);
    }

    public function duplicate(int $course_id,int $actor_id,string $title=''): array
    {
        $source=$this->get_course($course_id);if(!$source)return array('status'=>false,'message'=>'Course not found.');
        $sections=$this->db->where('course_id',$course_id)->order_by('order')->get('section')->result_array();
        $lessons=$this->db->where('course_id',$course_id)->order_by('section_id')->order_by('order')->get('lesson')->result_array();
        unset($source['id']);$source['title']=trim($title)?:$source['title'].' Copy';$source['creator']=$actor_id;$source['user_id']=(string)$actor_id;$source['status']='draft';$source['workflow_status']='draft';$source['moderation_reason']=null;$source['scheduled_publish_at']=null;$source['published_at']=null;$source['date_added']=time();$source['last_modified']=time();
        $this->db->trans_begin();$this->db->insert('course',$source);$new_id=(int)$this->db->insert_id();$map=array();
        foreach($sections as $row){$old=(int)$row['id'];unset($row['id']);$row['course_id']=$new_id;$this->db->insert('section',$row);$map[$old]=(int)$this->db->insert_id();}
        foreach($lessons as $row){unset($row['id']);$row['course_id']=$new_id;if(isset($map[(int)$row['section_id']]))$row['section_id']=$map[(int)$row['section_id']];$this->db->insert('lesson',$row);}
        if($this->db->trans_status()){$this->db->trans_commit();$this->snapshot($new_id,$actor_id,'tutor','Initial duplicated course');return array('status'=>true,'message'=>'Course duplicated as a draft.','course_id'=>$new_id);}
        $this->db->trans_rollback();return array('status'=>false,'message'=>'Course could not be duplicated.');
    }

    public function transition(int $course_id,string $target,string $reason,int $actor_id,string $role): array
    {
        $course=$this->get_course($course_id);if(!$course)return array('status'=>false,'message'=>'Course not found.');
        $allowed=array('draft','pending','changes_requested','approved','published','archived');
        if(!in_array($target,$allowed,true))return array('status'=>false,'message'=>'Invalid course workflow state.');
        if($role!=='admin'&&!in_array($target,array('draft','pending','archived'),true))return array('status'=>false,'message'=>'Only admins can approve or publish courses.');
        if(in_array($target,array('changes_requested','archived'),true)&&trim($reason)==='')return array('status'=>false,'message'=>'A reason is required.');
        $from=$course['workflow_status']?:$this->legacy_workflow($course['status']);
        $transitions=array(
            'draft'=>array('pending','archived'),
            'pending'=>array('changes_requested','approved','archived'),
            'changes_requested'=>array('pending','archived'),
            'approved'=>array('changes_requested','published','archived'),
            'published'=>array('archived'),
            'archived'=>array('draft')
        );
        if(!in_array($target,$transitions[$from]??array(),true))return array('status'=>false,'message'=>'Course cannot move from '.str_replace('_',' ',$from).' to '.str_replace('_',' ',$target).'.');
        $legacy=array('draft'=>'draft','pending'=>'pending','changes_requested'=>'draft','approved'=>'pending','published'=>'active','archived'=>'draft');
        $data=array('workflow_status'=>$target,'status'=>$legacy[$target],'moderation_reason'=>trim($reason)?:null,'workflow_updated_at'=>date('Y-m-d H:i:s'),'last_modified'=>time());
        if($target==='pending'){$data['resubmission_count']=(int)$course['resubmission_count']+($from==='changes_requested'?1:0);}
        if($target==='published'){$data['published_at']=date('Y-m-d H:i:s');$data['scheduled_publish_at']=null;}
        $this->snapshot($course_id,$actor_id,$role,'Before workflow change to '.$target);
        $this->db->where('id',$course_id)->update('course',$data);
        $this->record_moderation($course_id,$target,$from,$target,$reason,$actor_id,(int)$course['creator']);
        $this->load->model('Immutable_audit_model','immutable_audit');
        $this->immutable_audit->record('publishing','course_'.$target,'course',$course_id,array('workflow_status'=>$from),array('workflow_status'=>$target,'reason'=>trim($reason)),array('actor_user_id'=>$actor_id,'actor_role'=>$role));
        return array('status'=>true,'message'=>'Course moved to '.str_replace('_',' ',$target).'.','course_id'=>$course_id);
    }

    public function schedule_publish(int $course_id,string $when,int $admin_id): array
    {
        $course=$this->get_course($course_id);if(!$course)return array('status'=>false,'message'=>'Course not found.');
        if(($course['workflow_status']??'')!=='approved')return array('status'=>false,'message'=>'Only an approved course can be scheduled for publishing.');
        $timestamp=strtotime($when);if(!$timestamp||$timestamp<=time())return array('status'=>false,'message'=>'Choose a future publishing time.');
        $this->db->where('id',$course_id)->update('course',array('workflow_status'=>'approved','status'=>'pending','scheduled_publish_at'=>date('Y-m-d H:i:s',$timestamp),'workflow_updated_at'=>date('Y-m-d H:i:s')));
        $this->record_moderation($course_id,'schedule_publish',$course['workflow_status'],'approved','Scheduled for '.date(DATE_ATOM,$timestamp),$admin_id,(int)$course['creator']);
        $this->load->model('Immutable_audit_model','immutable_audit');
        $this->immutable_audit->record('publishing','course_scheduled','course',$course_id,array('scheduled_publish_at'=>$course['scheduled_publish_at']??null),array('scheduled_publish_at'=>date('Y-m-d H:i:s',$timestamp)),array('actor_user_id'=>$admin_id,'actor_role'=>'admin'));
        return array('status'=>true,'message'=>'Course publishing scheduled.','course_id'=>$course_id);
    }

    public function publish_due(int $limit=100): array
    {
        $rows=$this->db->where('workflow_status','approved')->where('scheduled_publish_at IS NOT NULL',null,false)->where('scheduled_publish_at <=',date('Y-m-d H:i:s'))->limit($limit)->get('course')->result_array();$published=0;
        foreach($rows as $row){$result=$this->transition((int)$row['id'],'published','Scheduled publishing',0,'admin');if($result['status'])$published++;}
        return array('processed'=>count($rows),'published'=>$published);
    }

    public function accessibility_check(int $course_id,int $actor_id): array
    {
        $course=$this->get_course($course_id);if(!$course)return array('status'=>false,'message'=>'Course not found.');
        $lessons=$this->db->where('course_id',$course_id)->order_by('section_id')->order_by('order')->get('lesson')->result_array();
        $total=max(1,count($lessons));$video=0;$captioned=0;$alt=0;$ordered=0;$keyboard=0;
        foreach($lessons as $lesson){if(strpos((string)$lesson['lesson_type'],'video')!==false||!empty($lesson['video_url'])){$video++;if(!empty($lesson['caption'])||!empty($lesson['transcript_url']))$captioned++;}if(!empty($lesson['alt_text']))$alt++;if(!empty($lesson['reading_order'])||!empty($lesson['order']))$ordered++;if(!empty($lesson['keyboard_notes'])||in_array($lesson['lesson_type'],array('text','quiz'),true))$keyboard++;}
        $checks=array(
            'captions'=>array('score'=>$video?round($captioned*100/$video,1):100,'details'=>$captioned.' of '.$video.' video lessons have captions or transcripts.'),
            'alt_text'=>array('score'=>round($alt*100/$total,1),'details'=>$alt.' of '.count($lessons).' lessons include alternative text.'),
            'reading_order'=>array('score'=>round($ordered*100/$total,1),'details'=>$ordered.' lessons have an explicit curriculum order.'),
            'contrast'=>array('score'=>50,'details'=>'Automated contrast review requires rendered-page inspection; manual review remains required.'),
            'keyboard'=>array('score'=>round($keyboard*100/$total,1),'details'=>$keyboard.' lessons include keyboard-safe content or guidance.')
        );
        $this->db->where('course_id',$course_id)->delete('course_accessibility_checks');$sum=0;
        foreach($checks as $type=>$check){$sum+=$check['score'];$status=$check['score']>=90?'pass':($check['score']>=60?'warning':($type==='contrast'?'manual_review':'fail'));$this->db->insert('course_accessibility_checks',array('course_id'=>$course_id,'check_type'=>$type,'status'=>$status,'score'=>$check['score'],'details'=>$check['details'],'checked_by'=>$actor_id));$checks[$type]['status']=$status;}
        $score=round($sum/count($checks),1);$this->db->where('id',$course_id)->update('course',array('accessibility_score'=>$score,'accessibility_checked_at'=>date('Y-m-d H:i:s')));
        return array('status'=>true,'score'=>$score,'checks'=>$checks,'message'=>'Accessibility review completed.');
    }

    public function accessibility_results(int $course_id): array
    {
        return $this->db->where('course_id',$course_id)->order_by('id')->get('course_accessibility_checks')->result_array();
    }

    public function save_lesson_to_library(int $lesson_id,int $owner_id,bool $is_admin=false): array
    {
        $this->db->select('l.*')->from('lesson l')->join('course c','c.id=l.course_id')->where('l.id',$lesson_id);
        if(!$is_admin)$this->db->group_start()->where('c.creator',$owner_id)->or_where("FIND_IN_SET(".(int)$owner_id.",c.user_id) >",0,false)->group_end();
        $lesson=$this->db->get()->row_array();
        if(!$lesson)return array('status'=>false,'message'=>'Lesson not found.');
        $copy=$lesson;unset($copy['id'],$copy['course_id'],$copy['section_id'],$copy['order']);
        $this->db->insert('lesson_library',array('owner_user_id'=>$owner_id,'source_lesson_id'=>$lesson_id,'title'=>$lesson['title'],'lesson_type'=>$lesson['lesson_type'],'content_json'=>json_encode($copy)));
        return array('status'=>true,'message'=>'Lesson saved to the reusable content library.');
    }

    public function library(int $owner_id): array
    {
        return $this->db->group_start()->where('owner_user_id',$owner_id)->or_where('is_shared',1)->group_end()->order_by('updated_at','DESC')->order_by('id','DESC')->get('lesson_library')->result_array();
    }

    public function add_library_lesson(int $library_id,int $course_id,int $section_id,int $owner_id,bool $is_admin=false): array
    {
        $item=$this->db->group_start()->where('owner_user_id',$owner_id)->or_where('is_shared',1)->group_end()->where('id',$library_id)->get('lesson_library',1)->row_array();
        if(!$item||!$this->can_edit($course_id,$owner_id,$is_admin))return array('status'=>false,'message'=>'Library lesson not found or access denied.');
        if($this->db->where('id',$section_id)->where('course_id',$course_id)->count_all_results('section')!==1)return array('status'=>false,'message'=>'Course section not found.');
        $lesson=json_decode((string)$item['content_json'],true);if(!is_array($lesson))return array('status'=>false,'message'=>'Library lesson data is invalid.');
        unset($lesson['id']);$lesson['course_id']=$course_id;$lesson['section_id']=$section_id;$lesson['library_source_id']=$library_id;$lesson['order']=(int)$this->db->where('section_id',$section_id)->select_max('order','max_order')->get('lesson')->row('max_order')+1;$lesson['date_added']=time();$lesson['last_modified']=time();
        $this->db->insert('lesson',$lesson);$this->db->where('id',$library_id)->set('usage_count','usage_count+1',false)->update('lesson_library');
        return array('status'=>true,'message'=>'Reusable lesson added to the curriculum.','lesson_id'=>(int)$this->db->insert_id());
    }

    private function record_moderation(int $course_id,string $action,string $from,string $to,string $reason,int $actor,int $creator): void
    {
        $this->load->model('Moderation_model','moderation');$this->moderation->record('course',$course_id,$action,$from,$to,$reason,$actor,$creator);
    }

    private function legacy_workflow(string $status): string
    {
        return $status==='active'?'published':($status==='pending'?'pending':'draft');
    }
}
