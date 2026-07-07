<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Communication_model extends CI_Model
{
    public function filter_options(int $actor_id, string $role): array
    {
        $options = [
            'categories' => $this->master_categories(),
            'classes' => $this->master_classes(),
            'subjects' => $this->master_subjects(),
            'tutors' => [],
            'batches' => [],
            'courses' => [],
        ];

        if ($role === 'admin') {
            $options['tutors'] = $this->db->select('id, first_name, last_name')->where('is_instructor', 1)->where('status', 1)->order_by('first_name')->get('users')->result_array();
            $options['batches'] = $this->db->select('id, title, tutor_user_id')->order_by('title')->get('tutor_batches')->result_array();
            $options['courses'] = $this->db->select('id, title, creator')->order_by('title')->get('course')->result_array();
        } else {
            $options['batches'] = $this->db->select('id, title, tutor_user_id')->where('tutor_user_id', $actor_id)->order_by('title')->get('tutor_batches')->result_array();
            $this->db->select('id, title, creator')->from('course');
            $this->db->group_start()->where('creator', $actor_id)->or_where("FIND_IN_SET(" . (int)$actor_id . ", user_id) >", 0, false)->group_end();
            $options['courses'] = $this->db->order_by('title')->get()->result_array();
        }

        return $options;
    }

    public function preview_audience(array $filters, int $actor_id, string $role, int $limit = 500): array
    {
        $students = $role === 'admin'
            ? $this->admin_audience($filters)
            : $this->tutor_audience($filters, $actor_id);

        $deduped = [];
        foreach ($students as $student) {
            $id = (int)($student['id'] ?? 0);
            if ($id > 0 && !isset($deduped[$id])) {
                $deduped[$id] = $student;
            }
        }

        return array_slice(array_values($deduped), 0, max(1, $limit));
    }

    private function base_student_query(): void
    {
        $this->db->distinct()
            ->select('u.id, u.first_name, u.last_name, u.email, u.phone, u.address, u.status, slp.category_id, slp.class_id, slp.subject_interest_id AS subject_id, tc.name AS category_name, tcl.name AS class_name, tsm.name AS subject_name')
            ->from('users u')
            ->join('student_learning_profiles slp', 'slp.student_user_id = u.id', 'left')
            ->join('tutor_categories tc', 'tc.id = slp.category_id', 'left')
            ->join('tutor_classes tcl', 'tcl.id = slp.class_id', 'left')
            ->join('tutor_subject_master tsm', 'tsm.id = slp.subject_interest_id', 'left')
            ->where('u.is_instructor', 0);
    }

    private function admin_audience(array $filters): array
    {
        $this->base_student_query();
        $this->apply_common_filters($filters);

        if (!empty($filters['tutor_id'])) {
            $tutor_id = (int)$filters['tutor_id'];
            $this->db->where("(EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$tutor_id}) OR EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND b.tutor_user_id={$tutor_id}))", null, false);
        }
        $this->apply_batch_course_payment_filters($filters);
        return $this->db->order_by('u.first_name')->get()->result_array();
    }

    private function tutor_audience(array $filters, int $actor_id): array
    {
        $this->base_student_query();
        $this->db->where(
            "(EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id})"
            . " OR EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND b.tutor_user_id={$actor_id} AND bs.membership_status IN ('active','completed'))"
            . " OR EXISTS (SELECT 1 FROM enrol e JOIN course c ON c.id=e.course_id WHERE e.user_id=u.id AND (c.creator={$actor_id} OR FIND_IN_SET({$actor_id},c.user_id)>0)))",
            null,
            false
        );
        $this->apply_common_filters($filters);
        if (!empty($filters['relationship'])) {
            if ($filters['relationship'] === 'accepted') {
                $this->db->where("EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id} AND tr.status='approved')", null, false);
            } elseif ($filters['relationship'] === 'requested') {
                $this->db->where("EXISTS (SELECT 1 FROM tutor_requests tr WHERE tr.student_user_id=u.id AND tr.tutor_user_id={$actor_id})", null, false);
            }
        }
        $this->apply_batch_course_payment_filters($filters, $actor_id);
        return $this->db->order_by('u.first_name')->get()->result_array();
    }

    private function apply_common_filters(array $filters): void
    {
        foreach (['category_id' => 'slp.category_id', 'class_id' => 'slp.class_id', 'subject_id' => 'slp.subject_interest_id'] as $key => $column) {
            if (!empty($filters[$key])) {
                $this->db->where($column, (int)$filters[$key]);
            }
        }
        if (!empty($filters['city'])) {
            $this->db->like('u.address', trim((string)$filters['city']));
        }
        if (isset($filters['student_status']) && $filters['student_status'] !== '') {
            $this->db->where('u.status', (int)$filters['student_status']);
        }
    }

    private function apply_batch_course_payment_filters(array $filters, int $tutor_id = 0): void
    {
        if (!empty($filters['batch_id'])) {
            $batch_id = (int)$filters['batch_id'];
            $scope = $tutor_id > 0 ? " AND b.tutor_user_id={$tutor_id}" : '';
            $this->db->where("EXISTS (SELECT 1 FROM tutor_batch_students bs JOIN tutor_batches b ON b.id=bs.batch_id WHERE bs.student_user_id=u.id AND bs.batch_id={$batch_id}{$scope})", null, false);
        }
        if (!empty($filters['course_id'])) {
            $course_id = (int)$filters['course_id'];
            $scope = $tutor_id > 0 ? " AND (c.creator={$tutor_id} OR FIND_IN_SET({$tutor_id},c.user_id)>0)" : '';
            $this->db->where("EXISTS (SELECT 1 FROM enrol e JOIN course c ON c.id=e.course_id WHERE e.user_id=u.id AND e.course_id={$course_id}{$scope})", null, false);
        }
        if (!empty($filters['payment_status'])) {
            $paid = $filters['payment_status'] === 'paid';
            $this->db->where(($paid ? '' : 'NOT ') . "EXISTS (SELECT 1 FROM payment p WHERE p.user_id=u.id)", null, false);
        }
    }

    public function create_campaign(array $data): int
    {
        if (!empty($data['event_key'])) {
            $existing = $this->db->select('id')->get_where('message_campaigns', ['event_key' => $data['event_key']], 1)->row_array();
            if ($existing) {
                return 0;
            }
        }
        $this->db->insert('message_campaigns', $data);
        return (int)$this->db->insert_id();
    }

    public function add_recipient(int $campaign_id, int $student_id, string $channel, string $address, string $subject, string $message): int
    {
        $this->db->insert('message_campaign_recipients', [
            'campaign_id' => $campaign_id,
            'student_user_id' => $student_id,
            'channel' => $channel,
            'recipient_address' => $address,
            'rendered_subject' => $subject,
            'rendered_message' => $message,
            'status' => 'pending',
        ]);
        return (int)$this->db->insert_id();
    }

    public function update_recipient(int $id, string $status, string $error = '', ?string $next_retry_at = null): void
    {
        $data = ['status' => $status, 'error_message' => $error, 'last_attempt_at'=>date('Y-m-d H:i:s'), 'next_retry_at'=>$next_retry_at];
        if ($status === 'sent') {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $id)->set('attempt_count','attempt_count+1',false)->update('message_campaign_recipients', $data);
    }

    public function complete_campaign(int $campaign_id): void
    {
        $counts = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'pending' => 0];
        foreach ($this->db->select('status, COUNT(*) total')->where('campaign_id', $campaign_id)->group_by('status')->get('message_campaign_recipients')->result_array() as $row) {
            $counts[$row['status']] = (int)$row['total'];
        }
        $status = $counts['pending'] > 0 ? 'partial' : ($counts['failed'] > 0 ? (($counts['sent'] > 0 || $counts['skipped'] > 0) ? 'partial' : 'failed') : 'completed');
        $this->db->where('id', $campaign_id)->update('message_campaigns', [
            'sent_count' => $counts['sent'],
            'failed_count' => $counts['failed'],
            'skipped_count' => $counts['skipped'],
            'status' => $status,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function history(int $actor_id, string $role, int $limit = 50): array
    {
        if ($role !== 'admin') {
            $this->db->where('created_by', $actor_id)->where('created_role', 'tutor');
        }
        return $this->db->order_by('id', 'DESC')->limit($limit)->get('message_campaigns')->result_array();
    }

    public function recent_delivery_logs(int $actor_id, string $role, int $limit = 100, int $student_id = 0): array
    {
        $this->db
            ->select('r.id,r.student_user_id,r.channel,r.status,r.error_message,r.sent_at,r.created_at,r.attempt_count,r.next_retry_at,c.title,c.message_category,u.first_name,u.last_name')
            ->from('message_campaign_recipients r')
            ->join('message_campaigns c', 'c.id = r.campaign_id', 'inner')
            ->join('users u', 'u.id = r.student_user_id', 'left');
        if ($role !== 'admin') {
            $this->db->where('c.created_by', $actor_id)->where('c.created_role', 'tutor');
        }
        if($student_id>0)$this->db->where('r.student_user_id',$student_id);
        return $this->db->order_by('r.id', 'DESC')->limit($limit)->get()->result_array();
    }

    public function templates(int $actor_id): array
    {
        return $this->db->group_start()->where('owner_user_id IS NULL',null,false)->or_where('owner_user_id',$actor_id)->group_end()->where('is_active',1)->order_by('is_system','DESC')->order_by('name')->get('communication_templates')->result_array();
    }

    public function preference(int $user_id): array
    {
        return $this->db->get_where('communication_preferences',array('user_id'=>$user_id),1)->row_array()?:array(
            'user_id'=>$user_id,'email_transactional'=>1,'email_promotional'=>0,'whatsapp_transactional'=>1,'whatsapp_promotional'=>0,
            'in_app_transactional'=>1,'in_app_promotional'=>0,'quiet_hours_enabled'=>0,'quiet_start'=>'21:00:00','quiet_end'=>'07:00:00','timezone'=>'Asia/Kolkata'
        );
    }

    public function save_preference(int $user_id,array $input): bool
    {
        $data=array('user_id'=>$user_id,'email_transactional'=>!empty($input['email_transactional'])?1:0,'email_promotional'=>!empty($input['email_promotional'])?1:0,'whatsapp_transactional'=>!empty($input['whatsapp_transactional'])?1:0,'whatsapp_promotional'=>!empty($input['whatsapp_promotional'])?1:0,'in_app_transactional'=>!empty($input['in_app_transactional'])?1:0,'in_app_promotional'=>!empty($input['in_app_promotional'])?1:0,'quiet_hours_enabled'=>!empty($input['quiet_hours_enabled'])?1:0,'quiet_start'=>$input['quiet_start']?:null,'quiet_end'=>$input['quiet_end']?:null,'timezone'=>in_array($input['timezone']??'',timezone_identifiers_list(),true)?$input['timezone']:'Asia/Kolkata','consent_version'=>'1','consented_at'=>date('Y-m-d H:i:s'),'opted_out_at'=>empty($input['email_promotional'])&&empty($input['whatsapp_promotional'])&&empty($input['in_app_promotional'])?date('Y-m-d H:i:s'):null);
        return (bool)$this->db->replace('communication_preferences',$data);
    }

    public function delivery_decision(int $user_id,string $channel,string $category): array
    {
        $preference=$this->preference($user_id);$key=$channel.'_'.$category;
        if(empty($preference[$key]))return array('allowed'=>false,'status'=>'skipped','reason'=>ucfirst($category).' '.$channel.' messages are opted out.','next_retry_at'=>null);
        if(!empty($preference['quiet_hours_enabled'])&&!empty($preference['quiet_start'])&&!empty($preference['quiet_end'])){
            try{
                $tz=new DateTimeZone($preference['timezone']?:'Asia/Kolkata');
                $now=new DateTimeImmutable('now',$tz);
                $current=$now->format('H:i:s');$start=(string)$preference['quiet_start'];$end=(string)$preference['quiet_end'];
                $overnight=$end<=$start;
                $quiet=$overnight?($current>=$start||$current<$end):($current>=$start&&$current<$end);
                if($quiet){
                    $retry=new DateTimeImmutable($now->format('Y-m-d').' '.$end,$tz);
                    if($overnight&&$current>=$start)$retry=$retry->modify('+1 day');
                    return array('allowed'=>false,'status'=>'pending','reason'=>'Deferred during recipient quiet hours.','next_retry_at'=>$retry->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s'));
                }
            }catch(Throwable $e){}
        }
        return array('allowed'=>true);
    }

    public function can_retry(int $recipient_id,int $actor_id,string $role): bool
    {
        $this->db->select('r.id')->from('message_campaign_recipients r')->join('message_campaigns c','c.id=r.campaign_id')->where('r.id',$recipient_id)->where_in('r.status',array('failed','pending'));
        if($role!=='admin')$this->db->where('c.created_by',$actor_id)->where('c.created_role','tutor');
        return $this->db->count_all_results()===1;
    }


    private function master_categories(): array
    {
        if (!$this->db->table_exists('tutor_categories')) {
            return [];
        }
        return $this->db
            ->select('id, name, sort_order')
            ->where('status', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->get('tutor_categories')
            ->result_array();
    }

    private function master_classes(): array
    {
        if (!$this->db->table_exists('tutor_classes')) {
            return [];
        }
        return $this->db
            ->select('id, category_id, name, sort_order')
            ->where('status', 1)
            ->order_by('category_id', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('name', 'ASC')
            ->get('tutor_classes')
            ->result_array();
    }

    private function master_subjects(): array
    {
        if (!$this->db->table_exists('tutor_subject_master')) {
            return [];
        }
        return $this->db
            ->select('tsm.id, tsm.class_id, tcl.category_id, tsm.name, tsm.sort_order')
            ->from('tutor_subject_master tsm')
            ->join('tutor_classes tcl', 'tcl.id = tsm.class_id', 'left')
            ->where('tsm.status', 1)
            ->order_by('tcl.category_id', 'ASC')
            ->order_by('tsm.class_id', 'ASC')
            ->order_by('tsm.sort_order', 'ASC')
            ->order_by('tsm.name', 'ASC')
            ->get()
            ->result_array();
    }

    private function safe_rows(string $table, array $where, string $order): array
    {
        if (!$this->db->table_exists($table)) {
            return [];
        }
        return $this->db->where($where)->order_by($order)->get($table)->result_array();
    }
}
