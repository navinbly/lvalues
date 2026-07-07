<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Communication_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Communication_model', 'communication_model');
        $this->CI->load->model('Email_model', 'email_model');
        $this->CI->config->load('communication', true);
    }

    public function send_campaign(array $campaign, array $students, array $channels): array
    {
        $channels = array_values(array_intersect(['email', 'whatsapp', 'in_app'], $channels));
        $this->CI->load->model('Idempotency_model', 'idempotency');
        $this->CI->load->model('Immutable_audit_model', 'immutable_audit');
        $operation_payload = array(
            'event_key' => $campaign['event_key'] ?? null,
            'title' => $campaign['title'] ?? '',
            'student_ids' => array_map(function ($student) { return (int)($student['id'] ?? 0); }, $students),
            'channels' => $channels,
        );
        $key = $this->CI->idempotency->key('communication.send', $operation_payload, (string)($campaign['idempotency_key'] ?? ''));
        $this->CI->db->trans_begin();
        $claim = $this->CI->idempotency->claim('communication.send', $key, (int)($campaign['created_by'] ?? 0), $operation_payload);
        if (empty($claim['proceed'])) {
            $this->CI->db->trans_rollback();
            return $claim['response'];
        }
        $campaign_id = $this->CI->communication_model->create_campaign([
            'event_key' => $campaign['event_key'] ?? null,
            'campaign_type' => $campaign['campaign_type'] ?? 'manual',
            'trigger_event' => $campaign['trigger_event'] ?? null,
            'reference_type' => $campaign['reference_type'] ?? null,
            'reference_id' => $campaign['reference_id'] ?? null,
            'title' => trim((string)($campaign['title'] ?? 'Lvalues update')),
            'email_body' => (string)($campaign['email_body'] ?? ''),
            'whatsapp_body' => (string)($campaign['whatsapp_body'] ?? ''),
            'channels_json' => json_encode($channels),
            'audience_filters_json' => json_encode($campaign['filters'] ?? []),
            'created_by' => $campaign['created_by'] ?? null,
            'created_role' => $campaign['created_role'] ?? 'system',
            'message_category' => in_array($campaign['message_category']??'transactional',array('transactional','promotional'),true)?$campaign['message_category']:'transactional',
            'template_key' => $campaign['template_key'] ?? null,
            'total_recipients' => count($students),
            'status' => 'processing',
        ]);

        if ($campaign_id <= 0) {
            $response = ['status' => true, 'duplicate' => true, 'message' => 'This event was already delivered.'];
            $this->CI->idempotency->complete((int)$claim['id'], $response, 'communication_campaign');
            $this->CI->db->trans_commit();
            return $response;
        }

        foreach ($students as $student) {
            $tokens = $this->tokens($student, $campaign);
            $subject = $this->render((string)($campaign['title'] ?? ''), $tokens);
            foreach ($channels as $channel) {
                $template = $channel === 'whatsapp' ? ($campaign['whatsapp_body'] ?? '') : ($campaign['email_body'] ?? '');
                $message = $this->render((string)$template, $tokens);
                $address = $channel === 'email' ? (string)($student['email'] ?? '') : ($channel === 'whatsapp' ? (string)($student['phone'] ?? '') : (string)$student['id']);
                $recipient_id = $this->CI->communication_model->add_recipient($campaign_id, (int)$student['id'], $channel, $address, $subject, $message);
                $decision=$this->CI->communication_model->delivery_decision((int)$student['id'],$channel,in_array($campaign['message_category']??'transactional',array('transactional','promotional'),true)?$campaign['message_category']:'transactional');
                if(empty($decision['allowed'])){$this->CI->communication_model->update_recipient($recipient_id,$decision['status'],$decision['reason'],$decision['next_retry_at']??null);continue;}
                $result = $this->deliver($channel, $student, $subject, $message, $campaign_id, $recipient_id, $campaign);
                $this->CI->communication_model->update_recipient($recipient_id, $result['status'], $result['error']);
            }
        }
        $this->CI->communication_model->complete_campaign($campaign_id);
        $response = ['status' => true, 'campaign_id' => $campaign_id, 'duplicate' => false];
        $this->CI->immutable_audit->record('communication', 'campaign_sent', 'communication_campaign', $campaign_id, [], [
            'title' => $campaign['title'] ?? '',
            'channels' => $channels,
            'recipient_count' => count($students),
        ], [
            'actor_user_id' => (int)($campaign['created_by'] ?? 0),
            'actor_role' => (string)($campaign['created_role'] ?? 'system'),
            'idempotency_key' => $key,
        ]);
        $this->CI->idempotency->complete((int)$claim['id'], $response, 'communication_campaign', $campaign_id);
        if ($this->CI->db->trans_status()) $this->CI->db->trans_commit(); else $this->CI->db->trans_rollback();
        return $response;
    }

    public function retry_recipient(int $recipient_id): array
    {
        $row=$this->CI->db->select('r.*,c.created_by,c.created_role,c.reference_type,c.reference_id,c.message_category,u.email,u.phone,u.first_name,u.last_name')->from('message_campaign_recipients r')->join('message_campaigns c','c.id=r.campaign_id')->join('users u','u.id=r.student_user_id')->where('r.id',$recipient_id)->get()->row_array();
        if(!$row)return array('status'=>false,'message'=>'Delivery record not found.');
        if((int)$row['attempt_count']>=5)return array('status'=>false,'message'=>'Retry limit reached.');
        $decision=$this->CI->communication_model->delivery_decision((int)$row['student_user_id'],$row['channel'],$row['message_category']);
        if(empty($decision['allowed'])){$this->CI->communication_model->update_recipient($recipient_id,$decision['status'],$decision['reason'],$decision['next_retry_at']??null);return array('status'=>false,'message'=>$decision['reason']);}
        $row['id']=$row['student_user_id'];
        $result=$this->deliver($row['channel'],$row,$row['rendered_subject'],$row['rendered_message'],(int)$row['campaign_id'],$recipient_id,$row);
        $this->CI->communication_model->update_recipient($recipient_id,$result['status'],$result['error']);
        return array('status'=>$result['status']==='sent','message'=>$result['status']==='sent'?'Delivery retry succeeded.':'Delivery retry failed: '.$result['error']);
    }

    public function process_due_retries(int $limit=100): array
    {
        $rows=$this->CI->db->select('id')->where('status','pending')->where('next_retry_at IS NOT NULL',null,false)->where('next_retry_at <=',date('Y-m-d H:i:s'))->where('attempt_count <',5)->order_by('next_retry_at')->limit(max(1,$limit))->get('message_campaign_recipients')->result_array();
        $sent=0;$deferred=0;$failed=0;
        foreach($rows as $row){
            $result=$this->retry_recipient((int)$row['id']);
            $state=(string)$this->CI->db->where('id',(int)$row['id'])->get('message_campaign_recipients')->row('status');
            if($state==='sent')$sent++;elseif($state==='pending')$deferred++;else $failed++;
        }
        return array('processed'=>count($rows),'sent'=>$sent,'deferred'=>$deferred,'failed'=>$failed);
    }

    public function notify_course_event(int $course_id, string $event, array $old = []): array
    {
        $course = $this->CI->db->get_where('course', ['id' => $course_id], 1)->row_array();
        if (!$course || !in_array((string)$course['status'], ['active', 'upcoming'], true)) {
            return ['status' => true, 'skipped' => true];
        }
        $filters = $this->course_filters($course);
        $students = $this->CI->communication_model->preview_audience($filters, (int)($course['creator'] ?? 0), 'admin', 500);
        $changed = $event === 'course_launched' ? 'A new course has launched.' : $this->changed_course_text($old, $course);
        return $this->send_campaign([
            'event_key' => 'course:' . $course_id . ':' . $event . ':' . (int)($course['last_modified'] ?: $course['date_added']),
            'campaign_type' => 'automatic',
            'trigger_event' => $event,
            'reference_type' => 'course',
            'reference_id' => $course_id,
            'title' => 'Course update: {{course_name}}',
            'email_body' => "Dear {{student_name}},\n\n{{tutor_name}} has an update for {{course_name}}.\n{$changed}\nClass/subject: {{class_name}} / {{subject_name}}\nTiming: " . ($course['schedule_text'] ?? 'See course page') . "\nFee: {{fee}}\nMode: " . ucfirst((string)($course['delivery_mode'] ?? 'online')) . "\nStart date: " . ($course['start_date'] ?? '') . "\nLocation: " . ($course['location_text'] ?? '') . "\nOpen: {{login_link}}\n\nSupport: " . $this->support_email(),
            'whatsapp_body' => "{{course_name}} update from {{tutor_name}}: {$changed} Fee {{fee}}. Open {{login_link}}",
            'filters' => $filters,
            'created_by' => (int)($course['creator'] ?? 0),
            'created_role' => 'tutor',
            'course_name' => $course['title'],
            'fee' => $this->course_fee($course),
            'login_link' => site_url('home/course/' . slugify($course['title']) . '/' . $course_id),
        ], $students, ['email', 'whatsapp', 'in_app']);
    }

    public function notify_batch_event(int $batch_id, string $event, int $reference_id = 0, array $old = []): array
    {
        $batch = $this->CI->db->select('b.*, u.first_name, u.last_name')->from('tutor_batches b')->join('users u', 'u.id=b.tutor_user_id', 'left')->where('b.id', $batch_id)->get()->row_array();
        if (!$batch) {
            return ['status' => true, 'skipped' => true];
        }
        $filters = ['batch_id' => $batch_id];
        if ($event === 'batch_published') {
            $filters = array_filter(['category_id' => $batch['category_id'], 'class_id' => $batch['class_id'], 'subject_id' => $batch['subject_id']]);
        }
        $students = $this->CI->communication_model->preview_audience($filters, (int)$batch['tutor_user_id'], $event === 'batch_published' ? 'admin' : 'tutor', 500);
        $event_text = $event === 'session_scheduled' ? 'A new batch session has been scheduled.' : ($event === 'batch_published' ? 'A new batch is now available.' : 'Batch details or schedule were updated.');
        return $this->send_campaign([
            'event_key' => 'batch:' . $batch_id . ':' . $event . ':' . ($reference_id ?: strtotime((string)$batch['updated_at'])),
            'campaign_type' => 'automatic',
            'trigger_event' => $event,
            'reference_type' => $reference_id ? 'session' : 'batch',
            'reference_id' => $reference_id ?: $batch_id,
            'title' => 'Batch update: {{batch_name}}',
            'email_body' => "Dear {{student_name}},\n\n{$event_text}\nTutor: {{tutor_name}}\nBatch: {{batch_name}}\nClass/subject: {{class_name}} / {{subject_name}}\nStart date: {{start_date}}\nFee: {{fee}}\nMode: " . ucfirst((string)$batch['delivery_mode']) . "\nOpen: {{login_link}}\n\nSupport: " . $this->support_email(),
            'whatsapp_body' => "{$event_text} {{batch_name}} by {{tutor_name}}, starts {{start_date}}, fee {{fee}}. {{login_link}}",
            'filters' => $filters,
            'created_by' => (int)$batch['tutor_user_id'],
            'created_role' => 'tutor',
            'batch_name' => $batch['title'],
            'tutor_name' => trim(($batch['first_name'] ?? '') . ' ' . ($batch['last_name'] ?? '')),
            'start_date' => $batch['start_date'],
            'fee' => $batch['price'],
            'login_link' => site_url('student_batch/view/' . $batch_id),
        ], $students, ['email', 'whatsapp', 'in_app']);
    }

    private function deliver(string $channel, array $student, string $subject, string $message, int $campaign_id, int $recipient_id, array $campaign): array
    {
        if ($channel === 'email') {
            if (empty($student['email'])) {
                return ['status' => 'skipped', 'error' => 'Student email is missing.'];
            }
            $sent = (bool)$this->CI->email_model->send_smtp_mail(nl2br(html_escape($message)), $subject, $student['email']);
            return ['status' => $sent ? 'sent' : 'failed', 'error' => $sent ? '' : 'Email provider returned failure.'];
        }
        if ($channel === 'whatsapp') {
            return $this->deliver_whatsapp($student, $message, $campaign_id, $recipient_id);
        }
        $sent = $this->create_in_app($student, $subject, $message, $campaign_id, $campaign);
        return ['status' => $sent ? 'sent' : 'failed', 'error' => $sent ? '' : 'In-app notification could not be created.'];
    }

    private function deliver_whatsapp(array $student, string $message, int $campaign_id, int $recipient_id): array
    {
        $config = $this->CI->config->item('communication');
        $phone = preg_replace('/\D+/', '', (string)($student['phone'] ?? ''));
        $enabled = !empty($config['whatsapp_enabled']) && !empty($config['whatsapp_api_url']) && !empty($config['whatsapp_api_token']);
        $status = (!$enabled || $phone === '') ? 'skipped' : 'pending';
        $error = $phone === '' ? 'Student phone is missing.' : (!$enabled ? 'WhatsApp provider is not configured.' : 'Queued for configured WhatsApp provider.');
        $this->CI->db->insert('whatsapp_message_logs', [
            'campaign_id' => $campaign_id,
            'recipient_id' => $recipient_id,
            'user_id' => (int)$student['id'],
            'phone' => $phone,
            'provider' => (string)($config['whatsapp_provider'] ?? ''),
            'status' => $status,
            'message' => $message,
            'error_message' => $error,
        ]);
        return ['status' => $status, 'error' => $error];
    }

    private function create_in_app(array $student, string $subject, string $message, int $campaign_id, array $campaign): bool
    {
        $sent = false;
        if ($this->CI->db->table_exists('notifications')) {
            $fields = array_flip($this->CI->db->list_fields('notifications'));
            $row = array_intersect_key([
                'to_user' => (int)$student['id'],
                'from_user' => (int)($campaign['created_by'] ?? 0),
                'title' => $subject,
                'description' => $message,
                'type' => 'communication',
                'status' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ], $fields);
            $sent = $this->CI->db->insert('notifications', $row);
        }
        $this->CI->db->insert('notification_logs', [
            'campaign_id' => $campaign_id,
            'user_id' => (int)$student['id'],
            'channel' => 'in_app',
            'reference_type' => $campaign['reference_type'] ?? null,
            'reference_id' => $campaign['reference_id'] ?? null,
            'status' => $sent ? 'sent' : 'failed',
            'error_message' => $sent ? '' : 'Notifications table unavailable or insert failed.',
            'sent_at' => $sent ? date('Y-m-d H:i:s') : null,
        ]);
        return (bool)$sent;
    }

    private function tokens(array $student, array $campaign): array
    {
        $tutor_name = (string)($campaign['tutor_name'] ?? '');
        if ($tutor_name === '' && !empty($campaign['created_by'])) {
            $u = $this->CI->db->select('first_name,last_name')->get_where('users', ['id' => (int)$campaign['created_by']], 1)->row_array();
            $tutor_name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        }
        return [
            '{{student_name}}' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
            '{{course_name}}' => (string)($campaign['course_name'] ?? ''),
            '{{tutor_name}}' => $tutor_name,
            '{{class_name}}' => (string)($student['class_name'] ?? ''),
            '{{subject_name}}' => (string)($student['subject_name'] ?? ''),
            '{{batch_name}}' => (string)($campaign['batch_name'] ?? ''),
            '{{start_date}}' => (string)($campaign['start_date'] ?? ''),
            '{{fee}}' => (string)($campaign['fee'] ?? ''),
            '{{login_link}}' => (string)($campaign['login_link'] ?? site_url('login')),
        ];
    }

    private function render(string $text, array $tokens): string
    {
        return strtr($text, $tokens);
    }

    private function course_filters(array $course): array
    {
        $legacy_class = $this->CI->db->get_where('category', ['id' => (int)$course['sub_category_id']], 1)->row_array();
        $class = $legacy_class ? $this->CI->db->group_start()->where('slug', $legacy_class['slug'])->or_where('name', $legacy_class['name'])->group_end()->get('tutor_classes', 1)->row_array() : [];
        $subject = $class ? $this->CI->db->where('class_id', (int)$class['id'])->group_start()->where('slug', slugify($course['title']))->or_where('name', $course['title'])->group_end()->get('tutor_subject_master', 1)->row_array() : [];
        return array_filter([
            'category_id' => $class['category_id'] ?? null,
            'class_id' => $class['id'] ?? null,
            'subject_id' => $subject['id'] ?? null,
            'city' => in_array(($course['delivery_mode'] ?? 'online'), ['offline', 'both'], true) ? ($course['location_text'] ?? null) : null,
        ]);
    }

    private function changed_course_text(array $old, array $new): string
    {
        $labels = [];
        foreach (['title' => 'course title/details', 'price' => 'fees', 'discounted_price' => 'discounted fees', 'level' => 'class/level', 'delivery_mode' => 'delivery mode', 'schedule_text' => 'course timing', 'start_date' => 'start date', 'location_text' => 'location', 'status' => 'publication status'] as $key => $label) {
            if (array_key_exists($key, $old) && (string)$old[$key] !== (string)($new[$key] ?? '')) {
                $labels[] = $label;
            }
        }
        return $labels ? 'Changed: ' . implode(', ', $labels) . '.' : 'Course details were updated.';
    }

    private function course_fee(array $course): string
    {
        if (!empty($course['is_free_course'])) {
            return 'Free';
        }
        return (string)(!empty($course['discount_flag']) ? $course['discounted_price'] : $course['price']);
    }

    private function support_email(): string
    {
        $config = $this->CI->config->item('communication');
        return (string)($config['communication_support_email'] ?? '') ?: (get_settings('system_email') ?: 'support@lvalues.in');
    }
}
