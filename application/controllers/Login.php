<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set(get_settings('timezone'));
        
        // Your own constructor code
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Tutor_master_model', 'tutor_master_model');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');


        //Check custom session data
        $this->user_model->check_session_data();
    }

    public function index()
    {
        //Check custom session data
        $this->user_model->check_session_data('login');

        $page_data['page_name'] = 'login';
        $page_data['page_title'] = site_phrase('login');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function sign_up()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name'] = 'sign_up';
        $page_data['page_title'] = site_phrase('sign_up');
        $page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }


    public function validate_login($from = "")
	{
		if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
			$this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
			redirect(site_url('login'), 'refresh');
		}

		$email = $this->input->post('email');
		$password = $this->input->post('password');

		$credential = array(
			'email'    => $email,
			'password' => sha1($password),
			'status'   => 1
		);

		$query = $this->db->get_where('users', $credential);

		if ($query->num_rows() <= 0) {
			$this->session->set_flashdata('error_message', get_phrase('invalid_login_credentials'));
			redirect(site_url('login'), 'refresh');
		}

		$row = $query->row();

		// ✅ Block login if instructor application is pending approval
		// Condition: applied (applications row exists) but not approved (is_instructor != 1)
		if ((int)$row->is_instructor !== 1) {
			$app = $this->db->get_where('applications', ['user_id' => (int)$row->id], 1)->row_array();
			if (!empty($app)) {
				// If application has a status column use it, otherwise treat existence as pending
				$status = isset($app['status']) ? (int)$app['status'] : 0;
				if ($status !== 1) {
					$this->session->set_flashdata('error_message', 'Your instructor application is pending admin approval. Please wait for approval.');
					redirect(site_url('login'), 'refresh');
				}
			}
		}

		$this->user_model->new_device_login_tracker($row->id);
		$this->user_model->set_login_userdata($row->id);
	}

    function new_login_confirmation($param1 = ""){
        $new_device_code_expiration_time = $this->session->userdata('new_device_code_expiration_time');
        if(!$new_device_code_expiration_time || $new_device_code_expiration_time < (time())){
            $this->session->set_flashdata('error_message', get_phrase('time_over').'! '.site_phrase('please_try_again'));
            redirect(site_url('login'), 'refresh');
        }

        if($param1 == 'submit'){
            $new_device_verification_code = $this->input->post('new_device_verification_code');
            if($new_device_verification_code != $this->session->userdata('new_device_verification_code')){
                $this->session->set_flashdata('error_message', get_phrase('verification_code_is_wrong'));
                redirect(site_url('login/new_login_confirmation'), 'refresh');
            }

            // Checking login credential for admin
            $query = $this->db->get_where('users', array('id' => $this->session->userdata('new_device_user_id')));

            if ($query->num_rows() > 0) {
                $row = $query->row();

                // For device login tracker
                $this->user_model->new_device_login_tracker($row->id, true);
                $this->user_model->set_login_userdata($row->id);
            }
            $this->session->set_flashdata('error_message', get_phrase('something_is_wrong').'! '.site_phrase('please_try_again'));
            redirect(site_url('home'), 'refresh');
        }

        if($param1 == 'resend'){
            $this->email_model->new_device_login_alert();
            return;
        }

        $page_data['page_name'] = 'new_login_confirmation';
        $page_data['page_title'] = site_phrase('new_login_confirmation');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }
    
    public function fb_validate_login($access_token = "", $fb_user_id = "") {
        $this->social_login_modal->fb_validate_login($access_token, $fb_user_id);
    }


    /*public function register()
	{
		if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
			$this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
			redirect(site_url('login'), 'refresh');
		}

		$this->load->library('form_validation');

		$registration_type = strtolower((string)$this->input->post('registration_type'));
		$is_instructor = get_settings('allow_instructor') && $registration_type === 'tutor';

		if ($is_instructor) {
			$location_parts = array_filter([
				trim((string)$this->input->post('tutor_address_line1')),
				trim((string)$this->input->post('tutor_city')),
				trim((string)$this->input->post('tutor_state')),
				trim((string)$this->input->post('tutor_country')),
				trim((string)$this->input->post('tutor_pincode')),
			]);
			$_POST['tutor_location'] = implode(', ', $location_parts);
		}

		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim|min_length[2]|max_length[50]|regex_match[/^[a-zA-Z ]+$/]');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|min_length[1]|max_length[50]|regex_match[/^[a-zA-Z ]+$/]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[120]');
		$this->form_validation->set_rules('password', 'Password', 'required|callback__strong_password');

		if ($is_instructor) {
			$this->form_validation->set_rules('phone_country_code', 'Country code', 'required|trim');
			$this->form_validation->set_rules('phone_number', 'Phone number', 'required|trim|callback__valid_phone_by_country');
			$this->form_validation->set_rules('tutor_category_ids[]', 'Category', 'required');
			$this->form_validation->set_rules('tutor_class_ids[]', 'Class / Course Group', 'required');
			$this->form_validation->set_rules('tutor_subject_ids[]', 'Subject', 'required');
			$this->form_validation->set_rules('tutor_teaching_mode', 'Mode', 'required|in_list[online,offline,both]');
			$this->form_validation->set_rules('tutor_address_line1', 'Address', 'required|trim|min_length[3]|max_length[255]');
			$this->form_validation->set_rules('tutor_city', 'City', 'required|trim|min_length[2]|max_length[120]');
			$this->form_validation->set_rules('tutor_state', 'State', 'trim|max_length[120]');
			$this->form_validation->set_rules('tutor_country', 'Country', 'required|trim|min_length[2]|max_length[120]');
			$this->form_validation->set_rules('tutor_pincode', 'Pincode', 'trim|max_length[20]');
			$this->form_validation->set_rules('tutor_location', 'Location', 'required|trim|min_length[3]|max_length[255]');
			$this->form_validation->set_rules('tutor_lat', 'Current latitude', 'required|trim|callback__valid_latitude');
			$this->form_validation->set_rules('tutor_lng', 'Current longitude', 'required|trim|callback__valid_longitude');
			$this->form_validation->set_rules('fee_type', 'Fee type', 'required|in_list[per_hour,per_subject]');

			$fee_type = $this->input->post('fee_type');
			if ($fee_type === 'per_hour') {
				$this->form_validation->set_rules('tutor_hourly_fee', 'Per hour fee', 'required|trim|numeric|greater_than_equal_to[0]');
			} elseif ($fee_type === 'per_subject') {
				$subject_fee_names = $this->input->post('subject_fee_name');
				$subject_fee_amounts = $this->input->post('subject_fee_amount');
				if (!$this->_has_valid_subject_fee_rows($subject_fee_names, $subject_fee_amounts)) {
					$this->form_validation->set_rules('subject_fee_amount[]', 'Per subject fees', 'callback__subject_fee_rows_required');
				}
			}
		}

		if ($this->form_validation->run() == FALSE) {
			$page_data['page_name'] = 'sign_up';
			$page_data['page_title'] = site_phrase('sign_up');
			$page_data['form_errors'] = validation_errors();
			$page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
			$this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
			return;
		}

		if ($is_instructor) {
			$category_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_category_ids'));
			$class_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_class_ids'));
			$subject_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_subject_ids'));
			if (empty($category_ids) || empty($class_ids) || empty($subject_ids)) {
				$this->session->set_flashdata('error_message', 'Please select category, class/course group, and subject.');
				redirect(site_url('sign_up?tutor=1'), 'refresh');
			}
			if (!$this->tutor_master_model->class_ids_belong_to_categories($class_ids, $category_ids)) {
				$this->session->set_flashdata('error_message', 'Selected classes do not belong to the chosen category.');
				redirect(site_url('sign_up?tutor=1'), 'refresh');
			}
			if (!$this->tutor_master_model->subject_ids_belong_to_classes($subject_ids, $class_ids)) {
				$this->session->set_flashdata('error_message', 'Selected subjects do not belong to the chosen class/course group.');
				redirect(site_url('sign_up?tutor=1'), 'refresh');
			}
			if (!isset($_FILES['document']) || empty($_FILES['document']['name'])) {
				$this->session->set_flashdata('error_message', 'Document is required for tutor registration.');
				redirect(site_url('sign_up?tutor=1'), 'refresh');
			}
			$accepted_ext = array('doc', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
			$ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
			if (!in_array($ext, $accepted_ext)) {
				$this->session->set_flashdata('error_message', 'Invalid document file. Allowed: doc, docs, pdf, txt, png, jpg, jpeg');
				redirect(site_url('sign_up?tutor=1'), 'refresh');
			}
		}

		$email = html_escape($this->input->post('email'));
		$validity = $this->user_model->check_duplication('on_create', $email);
		if (!($validity === 'unverified_user' || $validity === true)) {
			$this->session->set_flashdata('error_message', get_phrase('you_have_already_registered'));
			redirect(site_url('login'), 'refresh');
		}

		$verification_code = rand(100000, 999999);
		$now = time();
		$data = [
			'first_name' => html_escape($this->input->post('first_name')),
			'last_name'  => html_escape($this->input->post('last_name')),
			'email'      => $email,
			'password'   => sha1($this->input->post('password')),
			'verification_code' => $verification_code,
			'last_modified' => $now,
			'status' => (get_settings('student_email_verification') == 'enable') ? 0 : 1,
			'wishlist' => json_encode([]),
			'date_added' => $now,
			'role_id' => 2,
			'social_links' => json_encode(['facebook'=>'','twitter'=>'','linkedin'=>'']),
			'payment_keys' => json_encode([])
		];

		if ($validity === true) {
			$user_id = $this->user_model->register_user($data);
		} else {
			$this->user_model->register_user_update_code($data, $data['status']);
			$user_id = $this->db->get_where('users', ['email' => $email])->row('id');
		}

		if ($is_instructor) {
			$cc = trim((string)$this->input->post('phone_country_code'));
			$pn = preg_replace('/\D+/', '', (string)$this->input->post('phone_number'));
			$_POST['phone'] = $cc . $pn;
			$_POST['email'] = $email;
			$_POST['message'] = trim((string)$this->input->post('message'));
			$_POST['instructor'] = 'yes';
			$this->user_model->instructor_application();
		}

		if (get_settings('student_email_verification') == 'enable') {
			$this->email_model->send_email_verification_mail($email, $verification_code);
			$this->session->set_userdata('register_email', $email);
			redirect(site_url('sign_up/verification_code'), 'refresh');
		}

		if (!empty($user_id)) {
			$this->email_model->signup_mail($user_id);
		}

		$success_message = $is_instructor ? 'Tutor registration submitted successfully. Please log in after admin approval.' : get_phrase('your_registration_has_been_successfully_done');
		$this->session->set_flashdata('flash_message', $success_message);
		redirect(site_url('login'), 'refresh');
	}*/
	
	public function register()
	{
		if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
			$this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
			redirect(site_url('login'), 'refresh');
		}

		$this->load->library('form_validation');

		$registration_type = strtolower((string)$this->input->post('registration_type'));
		$is_instructor = get_settings('allow_instructor') && $registration_type === 'tutor';
		$signup_redirect = $is_instructor ? site_url('sign_up?tutor=1') : site_url('sign_up');

		if ($is_instructor) {
			$location_parts = array_filter([
				trim((string)$this->input->post('tutor_address_line1')),
				trim((string)$this->input->post('tutor_city')),
				trim((string)$this->input->post('tutor_state')),
				trim((string)$this->input->post('tutor_country')),
				trim((string)$this->input->post('tutor_pincode')),
			]);
			$_POST['tutor_location'] = implode(', ', $location_parts);
		}

		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim|min_length[2]|max_length[50]|regex_match[/^[a-zA-Z ]+$/]');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|min_length[1]|max_length[50]|regex_match[/^[a-zA-Z ]+$/]');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[120]');
		$this->form_validation->set_rules('password', 'Password', 'required|callback__strong_password');

		// Mobile number for both student and tutor
		$this->form_validation->set_rules('phone_country_code', 'Country code', 'required|trim');
		$this->form_validation->set_rules('phone_number', 'Phone number', 'required|trim|callback__valid_phone_by_country');

		if ($is_instructor) {
			$this->form_validation->set_rules('tutor_category_ids[]', 'Category', 'required');
			$this->form_validation->set_rules('tutor_class_ids[]', 'Class / Course Group', 'required');
			$this->form_validation->set_rules('tutor_subject_ids[]', 'Subject', 'required');
			$this->form_validation->set_rules('tutor_teaching_mode', 'Mode', 'required|in_list[online,offline,both]');
			$this->form_validation->set_rules('tutor_address_line1', 'Address', 'required|trim|min_length[3]|max_length[255]');
			$this->form_validation->set_rules('tutor_city', 'City', 'required|trim|min_length[2]|max_length[120]');
			$this->form_validation->set_rules('tutor_state', 'State', 'trim|max_length[120]');
			$this->form_validation->set_rules('tutor_country', 'Country', 'required|trim|min_length[2]|max_length[120]');
			$this->form_validation->set_rules('tutor_pincode', 'Pincode', 'trim|max_length[20]');
			$this->form_validation->set_rules('tutor_location', 'Location', 'required|trim|min_length[3]|max_length[255]');
			$this->form_validation->set_rules('tutor_lat', 'Current latitude', 'required|trim|callback__valid_latitude');
			$this->form_validation->set_rules('tutor_lng', 'Current longitude', 'required|trim|callback__valid_longitude');
			$this->form_validation->set_rules('fee_type', 'Fee type', 'required|in_list[per_hour,per_subject]');

			$fee_type = $this->input->post('fee_type');
			if ($fee_type === 'per_hour') {
				$this->form_validation->set_rules('tutor_hourly_fee', 'Per hour fee', 'required|trim|numeric|greater_than_equal_to[0]');
			} elseif ($fee_type === 'per_subject') {
				$subject_fee_names = $this->input->post('subject_fee_name');
				$subject_fee_amounts = $this->input->post('subject_fee_amount');
				if (!$this->_has_valid_subject_fee_rows($subject_fee_names, $subject_fee_amounts)) {
					$this->form_validation->set_rules('subject_fee_amount[]', 'Per subject fees', 'callback__subject_fee_rows_required');
				}
			}
		}

		if ($this->form_validation->run() == FALSE) {
			$page_data['page_name'] = 'sign_up';
			$page_data['page_title'] = site_phrase('sign_up');
			$page_data['form_errors'] = validation_errors();
			$page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
			$this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
			return;
		}

		if ($is_instructor) {
			$category_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_category_ids'));
			$class_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_class_ids'));
			$subject_ids = $this->tutor_master_model->normalize_ids((array) $this->input->post('tutor_subject_ids'));

			if (empty($category_ids) || empty($class_ids) || empty($subject_ids)) {
				$this->session->set_flashdata('error_message', 'Please select category, class/course group, and subject.');
				redirect($signup_redirect, 'refresh');
			}

			if (!$this->tutor_master_model->class_ids_belong_to_categories($class_ids, $category_ids)) {
				$this->session->set_flashdata('error_message', 'Selected classes do not belong to the chosen category.');
				redirect($signup_redirect, 'refresh');
			}

			if (!$this->tutor_master_model->subject_ids_belong_to_classes($subject_ids, $class_ids)) {
				$this->session->set_flashdata('error_message', 'Selected subjects do not belong to the chosen class/course group.');
				redirect($signup_redirect, 'refresh');
			}

			if (!isset($_FILES['document']) || empty($_FILES['document']['name'])) {
				$this->session->set_flashdata('error_message', 'Document is required for tutor registration.');
				redirect($signup_redirect, 'refresh');
			}

			$accepted_ext = array('doc', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
			$ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
			if (!in_array($ext, $accepted_ext)) {
				$this->session->set_flashdata('error_message', 'Invalid document file. Allowed: doc, docs, pdf, txt, png, jpg, jpeg');
				redirect($signup_redirect, 'refresh');
			}
		}

		$email = html_escape($this->input->post('email'));
		$validity = $this->user_model->check_duplication('on_create', $email);

		if (!($validity === 'unverified_user' || $validity === true)) {
			$this->session->set_flashdata('error_message', get_phrase('you_have_already_registered'));
			redirect(site_url('login'), 'refresh');
		}

		$cc = trim((string)$this->input->post('phone_country_code'));
		$pn = preg_replace('/\D+/', '', (string)$this->input->post('phone_number'));
		$full_phone = $cc . $pn;

		// Prevent reusing same previous password for same email
		if ($validity === 'unverified_user') {
			$existing_user = $this->db->get_where('users', ['email' => $email])->row_array();
			if (is_array($existing_user) && !empty($existing_user['password']) && $existing_user['password'] === sha1($this->input->post('password'))) {
				$this->session->set_flashdata('error_message', 'Please choose a different password from your previous password.');
				redirect($signup_redirect, 'refresh');
			}
		}

		$verification_code = rand(100000, 999999);
		$now = time();

		$data = [
			'first_name' => html_escape($this->input->post('first_name')),
			'last_name'  => html_escape($this->input->post('last_name')),
			'email'      => $email,
			'phone'      => $full_phone,
			'password'   => sha1($this->input->post('password')),
			'verification_code' => $verification_code,
			'last_modified' => $now,
			'status' => (get_settings('student_email_verification') == 'enable') ? 0 : 1,
			'wishlist' => json_encode([]),
			'date_added' => $now,
			'role_id' => 2,
			'social_links' => json_encode(['facebook'=>'','twitter'=>'','linkedin'=>'']),
			'payment_keys' => json_encode([])
		];

		if ($validity === true) {
			$user_id = $this->user_model->register_user($data);
		} else {
			$this->user_model->register_user_update_code($data, $data['status']);
			$user_id = $this->db->get_where('users', ['email' => $email])->row('id');
		}

		if ($is_instructor) {
			$_POST['phone'] = $full_phone;
			$_POST['email'] = $email;
			$_POST['message'] = trim((string)$this->input->post('message'));
			$_POST['instructor'] = 'yes';
			$this->user_model->instructor_application();
		}

		if (get_settings('student_email_verification') == 'enable') {
			$this->email_model->send_email_verification_mail($email, $verification_code);
			$this->session->set_userdata('register_email', $email);
			redirect(site_url('sign_up/verification_code'), 'refresh');
		}

		if (!empty($user_id)) {
			$this->email_model->signup_mail($user_id);
		}

		$success_message = $is_instructor
			? 'Tutor registration submitted successfully. Please log in after admin approval.'
			: get_phrase('your_registration_has_been_successfully_done');

		$this->session->set_flashdata('flash_message', $success_message);
		redirect(site_url('login'), 'refresh');
	}

	public function _strong_password($password)
	{
		$password = (string)$password;
		if (strlen($password) < 8) {
			$this->form_validation->set_message('_strong_password', 'Password must be at least 8 characters long');
			return false;
		}
		if (!preg_match('/[A-Z]/', $password)) {
			$this->form_validation->set_message('_strong_password', 'Password must contain at least one uppercase letter');
			return false;
		}
		if (!preg_match('/[a-z]/', $password)) {
			$this->form_validation->set_message('_strong_password', 'Password must contain at least one lowercase letter');
			return false;
		}
		if (!preg_match('/[0-9]/', $password)) {
			$this->form_validation->set_message('_strong_password', 'Password must contain at least one number');
			return false;
		}
		if (!preg_match('/[^A-Za-z0-9]/', $password)) {
			$this->form_validation->set_message('_strong_password', 'Password must contain at least one special character');
			return false;
		}
		return true;
	}

	public function _valid_phone_by_country($phone_number)
	{
		$phone_number = preg_replace('/\D+/', '', (string)$phone_number);
		$country_code = trim((string)$this->input->post('phone_country_code'));

		if ($country_code === '+91' && !preg_match('/^[6-9][0-9]{9}$/', $phone_number)) {
			$this->form_validation->set_message('_valid_phone_by_country', 'Enter a valid 10-digit Indian mobile number');
			return false;
		}

		if ($country_code !== '+91' && (strlen($phone_number) < 6 || strlen($phone_number) > 15)) {
			$this->form_validation->set_message('_valid_phone_by_country', 'Enter a valid phone number');
			return false;
		}

		return true;
	}

	public function _valid_latitude($value)
	{
		if (!is_numeric($value) || (float)$value < -90 || (float)$value > 90) {
			$this->form_validation->set_message('_valid_latitude', 'Current device location is required. Please allow location access.');
			return false;
		}
		return true;
	}

	public function _valid_longitude($value)
	{
		if (!is_numeric($value) || (float)$value < -180 || (float)$value > 180) {
			$this->form_validation->set_message('_valid_longitude', 'Current device location is required. Please allow location access.');
			return false;
		}
		return true;
	}

	public function _subject_fee_rows_required($value)
	{
		$this->form_validation->set_message('_subject_fee_rows_required', 'Add at least one valid subject fee row');
		return false;
	}

	private function _has_valid_subject_fee_rows($names, $amounts)
	{
		if (!is_array($names) || !is_array($amounts)) {
			return false;
		}

		$count = max(count($names), count($amounts));
		for ($i = 0; $i < $count; $i++) {
			$name = trim((string)($names[$i] ?? ''));
			$amount = trim((string)($amounts[$i] ?? ''));
			if ($name !== '' && $amount !== '' && is_numeric($amount) && (float)$amount >= 0) {
				return true;
			}
		}
		return false;
	}

    public function logout($from = "")
    {
        //destroy sessions of specific userdata. We've done this for not removing the cart session
        $this->user_model->session_destroy();
        redirect(site_url('login'), 'refresh');
    }

    public function forgot_password_request()
    {
        if ($this->session->userdata('admin_login')) {
            redirect(site_url('admin'), 'refresh');
        } elseif ($this->session->userdata('user_login')) {
            redirect(site_url('user'), 'refresh');
        }
        $page_data['page_name'] = 'forgot_password';
        $page_data['page_title'] = site_phrase('forgot_password');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    function forgot_password($from = "")
    {

        if ($this->crud_model->check_recaptcha() == false && get_frontend_settings('recaptcha_status') == true) {
            $this->session->set_flashdata('error_message', get_phrase('recaptcha_verification_failed'));
            redirect(site_url('login'), 'refresh');
        }
        $email = $this->input->post('email');
        $query = $this->db->get_where('users', array('email' => $email, 'status' => 1));
        if ($query->num_rows() > 0) {
            $this->crud_model->forgot_password();
            redirect(site_url('login'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            redirect(site_url('login'), 'refresh');
        }
    }

    function change_password($verification_code = ""){
        
        if($verification_code == ""){
            $this->session->set_flashdata('error_message', get_phrase('invalid_verification_code').'. '.get_phrase('please_send_a_new_forgot_password_request'));
            redirect(site_url('login'), 'refresh');
        }else{
            $verification_code = str_replace(' ', '', $verification_code);
            $verification_code = str_replace('%20', '', $verification_code);
            $verification_code = str_replace('=', '', $verification_code);

            $decoded_verification_code = explode('--', base64_decode($verification_code));
            $solid_email = $decoded_verification_code[0];
            $email = str_replace('__', '@', $solid_email);

            $current_time = time();
            $expired_time = $current_time-900;
            $this->db->where('email', $email);
            $this->db->where('verification_code', $verification_code);
            $row = $this->db->get('users');

            if($row->row('last_modified') < $expired_time || $row->num_rows() <= 0){
                $this->session->set_flashdata('error_message', get_phrase('This link has been expired.').' '.get_phrase('Please send a new request'));
                redirect(site_url('login/forgot_password_request'), 'refresh');
            }
        }


        if(isset($_POST['new_password']) && isset($_POST['confirm_password']) && !empty($_POST['confirm_password']) && $verification_code){
            $new_password = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');
            if($new_password == $confirm_password):
                $this->crud_model->change_password_from_forgot_passord($verification_code);
                $this->session->set_flashdata('flash_message', get_phrase('password_has_changed_successfully'));
                redirect(site_url('login'), 'refresh');
            else:
                $this->session->set_flashdata('error_message', get_phrase('the_confirmed_password_is_not_matching_with_the_new_password'));
                redirect(site_url('login/change_password/'.$verification_code), 'refresh');
            endif;
        }


        $page_data['verification_code'] = $verification_code;
        $page_data['page_name'] = 'change_password_from_forgot_password';
        $page_data['page_title'] = site_phrase('change_password');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);

    }

	public function resend_verification_code()
	{
		$email = $this->input->post('email');
		if (empty($email)) {
			echo false;
			return;
		}

		$user = $this->db->get_where('users', array('email' => $email), 1)->row_array();
		if (empty($user)) {
			echo false;
			return;
		}

		// ✅ Generate NEW code and reset timer (E)
		$new_code = rand(100000, 999999);
		$this->db->where('id', (int)$user['id']);
		$this->db->update('users', array(
			'verification_code' => $new_code,
			'last_modified' => time()
		));

		$this->email_model->send_email_verification_mail($email, $new_code);
		echo true;
	}

    public function verify_email_address()
	{
		$email = $this->input->post('email');
		$verification_code = $this->input->post('verification_code');

		$user_q = $this->db->get_where('users', array('email' => $email, 'verification_code' => $verification_code));
		if ($user_q->num_rows() <= 0) {
			$this->session->set_flashdata('error_message', get_phrase('the_verification_code_is_wrong') . '.');
			echo false;
			return;
		}

		$user = $user_q->row_array();

		// ✅ Expire after 30 minutes (E)
		$created_at = (int)($user['last_modified'] ?? 0);
		if ($created_at <= 0 || $created_at < (time() - 1800)) {
			$this->session->set_flashdata('error_message', 'Verification code expired. Please resend code.');
			echo false;
			return;
		}

		// Activate user email
		$this->db->where('id', $user['id']);
		$this->db->update('users', array('status' => 1));

		// ✅ If instructor was applied, tell user approval pending (D)
		$app = $this->db->get_where('applications', ['user_id' => (int)$user['id']], 1)->row_array();
		if (!empty($app)) {
			$this->session->set_flashdata('info_message', 'Email verified. Your instructor application is pending admin approval.');
		} else {
			$this->session->set_flashdata('flash_message', get_phrase('congratulations') . '! ' . get_phrase('your_email_address_has_been_successfully_verified') . '.');
		}

		$this->session->set_userdata('register_email', null);
		echo true;
	}

	public function verify_email_link()
	{
		$email = $this->input->get('email');
		$code  = $this->input->get('code');

		if (empty($email) || empty($code)) {
			$this->session->set_flashdata('error_message', 'Invalid verification link.');
			redirect(site_url('login'), 'refresh');
		}

		$query = $this->db->get_where('users', array(
			'email' => $email,
			'verification_code' => $code
		));

		if ($query->num_rows() > 0) {
			$user = $query->row_array();

			$this->db->where('id', $user['id']);
			$this->db->update('users', array('status' => 1));

			$this->session->set_flashdata('flash_message', 'Email verified successfully.');
			$this->session->set_userdata('register_email', null);

			redirect(site_url('login'), 'refresh');
		} else {
			$this->session->set_flashdata('error_message', 'Verification code is wrong.');
			redirect(site_url('sign_up/verification_code'), 'refresh');
		}
	}


    function check_recaptcha_with_ajax()
    {
        if ($this->crud_model->check_recaptcha()) {
            echo true;
        } else {
            echo false;
        }
    }
	
	

}