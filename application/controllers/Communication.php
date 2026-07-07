<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Communication extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session', 'Communication_service']);
        $this->load->model('Communication_model', 'communication_model');
        $this->config->load('communication', true);
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    public function admin()
    {
        $this->require_admin();
        check_permission('user');
        $this->render('admin');
    }

    public function tutor()
    {
        $this->require_tutor();
        $this->render('tutor');
    }

    public function preview($role)
    {
        $role = $this->authorize_role($role);
        $this->require_post_token();
        $filters = $this->filters_from_post();
        $students = $this->communication_model->preview_audience($filters, $this->actor_id(), $role, $this->max_recipients());
        $this->json([
            'status' => true,
            'count' => count($students),
            'sample' => array_map(function ($student) {
                return [
                    'name' => trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')),
                    'class_name' => $student['class_name'] ?? '',
                    'subject_name' => $student['subject_name'] ?? '',
                ];
            }, array_slice($students, 0, 5)),
        ]);
    }

    public function send($role)
    {
        $role = $this->authorize_role($role);
        $this->require_post_token();
        $channels = [];
        foreach (['email', 'whatsapp', 'in_app'] as $channel) {
            if ($this->input->post('send_' . $channel)) {
                $channels[] = $channel;
            }
        }
        $title = trim((string)$this->input->post('title', true));
        $email_body = trim((string)$this->input->post('email_body', true));
        $whatsapp_body = trim((string)$this->input->post('whatsapp_body', true));
        if ($title === '' || empty($channels) || (in_array('email', $channels, true) && $email_body === '') || (in_array('whatsapp', $channels, true) && $whatsapp_body === '')) {
            $this->session->set_flashdata('error_message', 'Add a subject, select a channel, and complete its message body.');
            return $this->redirect_for_role($role);
        }

        $filters = $this->filters_from_post();
        $students = $this->communication_model->preview_audience($filters, $this->actor_id(), $role, $this->max_recipients());
        if (empty($students)) {
            $this->session->set_flashdata('error_message', 'No authorized students match this audience.');
            return $this->redirect_for_role($role);
        }

        $result = $this->communication_service->send_campaign([
            'campaign_type' => 'manual',
            'title' => $title,
            'email_body' => $email_body,
            'whatsapp_body' => $whatsapp_body,
            'filters' => $filters,
            'created_by' => $this->actor_id(),
            'created_role' => $role,
            'course_name' => $this->selected_name('course', $filters['course_id'] ?? 0),
            'batch_name' => $this->selected_name('tutor_batches', $filters['batch_id'] ?? 0),
            'login_link' => site_url('login'),
            'message_category' => in_array($this->input->post('message_category',true),array('transactional','promotional'),true)?$this->input->post('message_category',true):'transactional',
            'template_key' => trim((string)$this->input->post('template_key',true))?:null,
        ], $students, $channels);

        $this->session->set_flashdata($result['status'] ? 'flash_message' : 'error_message', $result['status'] ? 'Campaign processed. Review delivery status in campaign history.' : 'Campaign could not be processed.');
        $this->redirect_for_role($role);
    }

    private function render(string $role): void
    {
        $token = bin2hex(random_bytes(24));
        $this->session->set_userdata('communication_csrf_token', $token);
        $page_data = [
            'page_name' => 'communication_center',
            'page_title' => $role === 'admin' ? 'Communication Center' : 'Student Communication',
            'communication_role' => $role,
            'communication_csrf_token' => $token,
            'communication_options' => $this->communication_model->filter_options($this->actor_id(), $role),
            'campaign_history' => $this->communication_model->history($this->actor_id(), $role),
            'delivery_logs' => $this->communication_model->recent_delivery_logs($this->actor_id(), $role, 100, (int)$this->input->get('student_id')),
            'communication_timeline_student_id' => (int)$this->input->get('student_id'),
            'communication_templates' => $this->communication_model->templates($this->actor_id()),
            'max_recipients' => $this->max_recipients(),
        ];
        $this->load->view('backend/index', $page_data);
    }

    public function retry($recipient_id=0)
    {
        if(strtoupper((string)$this->input->method(true))!=='POST')show_error('Method not allowed.',405);
        $role=$this->session->userdata('admin_login')==true?'admin':'tutor';$this->authorize_role($role);
        if(!$this->communication_model->can_retry((int)$recipient_id,$this->actor_id(),$role))show_error('Delivery not found or access denied.',403);
        $result=$this->communication_service->retry_recipient((int)$recipient_id);$this->session->set_flashdata(!empty($result['status'])?'flash_message':'error_message',$result['message']);$this->redirect_for_role($role);
    }

    public function preferences()
    {
        if($this->session->userdata('user_login')!=true)redirect(site_url('login'),'refresh');
        $user_id=$this->actor_id();
        if(strtoupper((string)$this->input->method(true))==='POST'){$this->communication_model->save_preference($user_id,$this->input->post(null,true));$this->session->set_flashdata('flash_message','Communication preferences updated.');redirect(site_url('communication/preferences'),'refresh');}
        $page_data=array('page_name'=>'communication_preferences','page_title'=>'Communication Preferences','communication_preference'=>$this->communication_model->preference($user_id));
        $this->load->view('frontend/'.get_frontend_settings('theme').'/index',$page_data);
    }

    private function filters_from_post(): array
    {
        return [
            'category_id' => (int)$this->input->post('category_id'),
            'class_id' => (int)$this->input->post('class_id'),
            'subject_id' => (int)$this->input->post('subject_id'),
            'course_id' => (int)$this->input->post('course_id'),
            'city' => trim((string)$this->input->post('city', true)),
            'tutor_id' => (int)$this->input->post('tutor_id'),
            'batch_id' => (int)$this->input->post('batch_id'),
            'payment_status' => trim((string)$this->input->post('payment_status', true)),
            'student_status' => $this->input->post('student_status') === '' ? '' : (int)$this->input->post('student_status'),
            'relationship' => trim((string)$this->input->post('relationship', true)),
        ];
    }

    private function authorize_role(string $role): string
    {
        if ($role === 'admin') {
            $this->require_admin();
            check_permission('user');
            return 'admin';
        }
        $this->require_tutor();
        return 'tutor';
    }

    private function require_admin(): void
    {
        if ($this->session->userdata('admin_login') != true) {
            redirect(site_url('login'), 'refresh');
            exit;
        }
    }

    private function require_tutor(): void
    {
        if ($this->session->userdata('user_login') != true || (int)$this->session->userdata('is_instructor') !== 1) {
            redirect(site_url('login'), 'refresh');
            exit;
        }
    }

    private function require_post_token(): void
    {
        $posted = (string)$this->input->post('communication_csrf_token', true);
        $stored = (string)$this->session->userdata('communication_csrf_token');
        if ($posted === '' || $stored === '' || !hash_equals($stored, $posted)) {
            show_error('The communication form expired. Reload the page and try again.', 403);
            exit;
        }
    }

    private function actor_id(): int
    {
        return (int)$this->session->userdata('user_id');
    }

    private function max_recipients(): int
    {
        $config = $this->config->item('communication');
        return max(1, (int)($config['communication_max_recipients'] ?? 500));
    }

    private function selected_name(string $table, int $id): string
    {
        if ($id <= 0 || !$this->db->table_exists($table)) {
            return '';
        }
        return (string)$this->db->select('title')->get_where($table, ['id' => $id], 1)->row('title');
    }

    private function redirect_for_role(string $role): void
    {
        redirect(site_url($role === 'admin' ? 'admin/communication-center' : 'user/student-communication'), 'refresh');
    }

    private function json(array $payload): void
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }
}
