<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{

    /**
     * Upsert tutor profile + subjects for a given user.
     * This keeps the tutoring module decoupled from existing LMS logic:
     * - Instructor application still goes to `applications`
     * - Tutor searchable fields go to `tutor_profiles` + `tutor_subjects`
     */
    
private function upsert_tutor_profile_from_post($user_id)
    {
        if (!$this->db->table_exists('tutor_profiles')) {
            return;
        }

        $fee_type = $this->input->post('fee_type');
        if (!in_array($fee_type, ['per_hour', 'per_subject'], true)) {
            $fee_type = 'per_hour';
        }
        $fee_currency = strtoupper(trim((string)$this->input->post('fee_currency')));
        if (!in_array($fee_currency, ['INR', 'USD', 'GBP', 'AED', 'CAD', 'AUD'], true)) {
            $fee_currency = 'INR';
        }
        if (!$this->db->field_exists('fee_currency', 'tutor_profiles')) {
            $after_fee_type = $this->db->field_exists('fee_type', 'tutor_profiles') ? " AFTER `fee_type`" : "";
            $this->db->query("ALTER TABLE `tutor_profiles` ADD COLUMN `fee_currency` VARCHAR(10) NULL" . $after_fee_type);
        }

        $subject_fees = [];
        $fee_names = $this->input->post('subject_fee_name');
        $fee_amounts = $this->input->post('subject_fee_amount');
        if (is_array($fee_names) && is_array($fee_amounts)) {
            $count = max(count($fee_names), count($fee_amounts));
            for ($i = 0; $i < $count; $i++) {
                $name = html_escape(trim((string)($fee_names[$i] ?? '')));
                $amount = trim((string)($fee_amounts[$i] ?? ''));
                if ($name !== '' && $amount !== '' && is_numeric($amount) && (float)$amount >= 0) {
                    $subject_fees[] = ['subject_name' => $name, 'fee' => (float)$amount, 'currency' => $fee_currency];
                }
            }
        }

        $tutor_bio = trim((string)$this->input->post('tutor_bio'));
        $current_role = trim((string)$this->input->post('tutor_current_role'));
        if ($current_role !== '') {
            $tutor_bio = 'Current role: ' . $current_role . "\n\n" . $tutor_bio;
        }

        $profile = [
            'user_id'          => (int)$user_id,
            'headline'         => html_escape((string)$this->input->post('tutor_headline')),
            'bio'              => $tutor_bio,
            'qualification'    => html_escape((string)$this->input->post('tutor_qualification')),
            'experience_years' => is_numeric($this->input->post('tutor_experience_years')) ? (int)$this->input->post('tutor_experience_years') : null,
            'teaching_mode'    => in_array($this->input->post('tutor_teaching_mode'), ['online','offline','both'], true) ? $this->input->post('tutor_teaching_mode') : 'both',
            'city'             => html_escape((string)$this->input->post('tutor_city')),
            'state'            => html_escape((string)$this->input->post('tutor_state')),
            'country'          => html_escape((string)$this->input->post('tutor_country')),
            'pincode'          => html_escape((string)$this->input->post('tutor_pincode')),
            'hourly_fee'       => ($fee_type === 'per_hour' && is_numeric($this->input->post('tutor_hourly_fee'))) ? (float)$this->input->post('tutor_hourly_fee') : null,
            'lat'              => is_numeric($this->input->post('tutor_lat')) ? (float)$this->input->post('tutor_lat') : null,
            'lng'              => is_numeric($this->input->post('tutor_lng')) ? (float)$this->input->post('tutor_lng') : null,
            'status'           => 'pending',
        ];

        if ($this->db->field_exists('fee_type', 'tutor_profiles')) {
            $profile['fee_type'] = $fee_type;
        }
        if ($this->db->field_exists('fee_currency', 'tutor_profiles')) {
            $profile['fee_currency'] = $fee_currency;
        }
        if ($this->db->field_exists('subject_fees_json', 'tutor_profiles')) {
            $profile['subject_fees_json'] = !empty($subject_fees) ? json_encode($subject_fees) : null;
        }
        if ($this->db->field_exists('current_role', 'tutor_profiles')) {
            $profile['current_role'] = html_escape($current_role);
        }

        foreach ($profile as $k => $v) {
            if ($v === '') unset($profile[$k]);
        }

        $existing = $this->db->get_where('tutor_profiles', ['user_id' => (int)$user_id], 1);
        if ($existing->num_rows() > 0) {
            $this->db->where('user_id', (int)$user_id)->update('tutor_profiles', $profile);
            $tutor_profile_id = (int)$existing->row('id');
        } else {
            $this->db->insert('tutor_profiles', $profile);
            $tutor_profile_id = (int)$this->db->insert_id();
        }

        if ($this->db->table_exists('tutor_subjects')) {
            $category_ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->input->post('tutor_category_ids')))));
            $class_ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->input->post('tutor_class_ids')))));
            $subject_ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->input->post('tutor_subject_ids')))));

            $details = [];
            if (!empty($subject_ids) && $this->db->table_exists('tutor_subject_master') && $this->db->table_exists('tutor_classes') && $this->db->table_exists('tutor_categories')) {
                $rows = $this->db->select('s.id AS subject_id, s.class_id, c.category_id')
                    ->from('tutor_subject_master s')
                    ->join('tutor_classes c', 'c.id = s.class_id', 'inner')
                    ->where_in('s.id', $subject_ids)
                    ->get()->result_array();
                foreach ($rows as $row) {
                    $details[(int)$row['subject_id']] = ['category_id' => (int)$row['category_id'], 'class_id' => (int)$row['class_id']];
                }
            }

            $this->db->where('tutor_profile_id', $tutor_profile_id)->delete('tutor_subjects');
            foreach ($subject_ids as $sid) {
                $category_id = $details[$sid]['category_id'] ?? (!empty($category_ids) ? (int)$category_ids[0] : null);
                $class_id = $details[$sid]['class_id'] ?? (!empty($class_ids) ? (int)$class_ids[0] : null);
                $this->db->insert('tutor_subjects', [
                    'tutor_profile_id' => $tutor_profile_id,
                    'category_id'      => $category_id,
                    'class_id'         => $class_id,
                    'subject_id'       => $sid,
                ]);
            }
        }
    }

    function __construct()
    {
        parent::__construct();
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function get_admin_details()
    {
        return $this->db->get_where('users', array('role_id' => 1));
    }

    public function get_user($user_id = 0)
    {
        if ($user_id > 0) {
            $this->db->where('id', $user_id);
        }
        $this->db->where('role_id', 2);
        return $this->db->get('users');
    }

    public function get_all_user($user_id = 0)
    {
        if ($user_id > 0) {
            $this->db->where('id', $user_id);
        }
        return $this->db->get('users');
    }

    public function add_user($is_instructor = false, $is_admin = false)
    {
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        } else {
          //  $data['unique_identifier'] = 0;
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));
            $data['email'] = html_escape($this->input->post('email'));
            $data['password'] = sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['phone'] = $this->input->post('phone');
            $data['address'] = $this->input->post('address');

            if ($is_admin) {
                $data['role_id'] = 1;
                $data['is_instructor'] = 1;
            } else {
                $data['role_id'] = 2;
            }

            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist'] = json_encode(array());
            $data['status'] = 1;
            $data['image'] = md5(rand(10000, 10000000));

            //All payment keys
            if(isset($_POST['gateways'])){
                $data['payment_keys'] = json_encode($_POST['gateways']);
            }

            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }

            $this->db->insert('users', $data);
            $user_id = $this->db->insert_id();
         //   $this->user_model->update_unique_identifier($user_id);

            // IF THIS IS A USER THEN INSERT BLANK VALUE IN PERMISSION TABLE AS WELL
            if ($is_admin) {
                $permission_data['admin_id'] = $user_id;
                $permission_data['permissions'] = json_encode(array());
                $this->db->insert('permissions', $permission_data);
            }

            $this->upload_user_image($data['image']);
            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
        }
    }

    public function add_shortcut_user($is_instructor = false)
    {
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $response['status'] = 0;
            $response['message'] = get_phrase('this_email_already_exits') . '. ' . get_phrase('please_use_another_email');
            return json_encode($response);
        } else {
          //  $data['unique_identifier'] = 0;
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));
            $data['email'] = html_escape($this->input->post('email'));
            $data['password'] = sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = '';
            $social_link['twitter'] = '';
            $social_link['linkedin'] = '';
            $data['social_links'] = json_encode($social_link);
            $data['role_id'] = 2;
            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist'] = json_encode(array());
            $data['status'] = 1;
            $data['image'] = md5(rand(10000, 10000000));

            // Add paypal keys
            $payment_keys = array();

            $paypal['production_client_id']  = '';
            $paypal['production_secret_key'] = '';
            $payment_keys['paypal'] = $paypal;

            // Add Stripe keys
            $stripe['public_live_key'] = '';
            $stripe['secret_live_key'] = '';
            $payment_keys['stripe'] = $stripe;

            // Add razorpay keys
            $razorpay['key_id'] = '';
            $razorpay['secret_key'] = '';
            $payment_keys['razorpay'] = $razorpay;

            //All payment keys
            $data['payment_keys'] = json_encode(array());

            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }
            $this->db->insert('users', $data);

            $user_id = $this->db->insert_id();
           // $this->user_model->update_unique_identifier($user_id);

            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
            $response['status'] = 1;
            return json_encode($response);
        }
    }

    public function check_duplication($action = "", $email = "", $user_id = "")
    {
        $duplicate_email_check = $this->db->get_where('users', array('email' => $email));

        if ($action == 'on_create') {
            if ($duplicate_email_check->num_rows() > 0) {
                if ($duplicate_email_check->row()->status == 1) {
                    return false;
                } else {
                    return 'unverified_user';
                }
            } else {
                return true;
            }
        } elseif ($action == 'on_update') {
            if ($duplicate_email_check->num_rows() > 0) {
                if ($duplicate_email_check->row()->id == $user_id) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    public function edit_user($user_id = "")
    { // Admin does this editing
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));

            if (isset($_POST['email'])) {
                $data['email'] = html_escape($this->input->post('email'));
            }
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['title'] = html_escape($this->input->post('title'));
            $data['skills'] = html_escape($this->input->post('skills'));
            $data['last_modified'] = strtotime(date("Y-m-d H:i:s"));

            if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
                unlink('uploads/user_image/' . $this->db->get_where('users', array('id' => $user_id))->row('image') . '.jpg');
                $data['image'] = md5(rand(10000, 10000000));
                $this->upload_user_image($data['image']);
            }

            //All payment keys
            if(isset($_POST['gateways'])){
                $data['payment_keys'] = json_encode($_POST['gateways']);
            }

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }
    public function delete_user($user_id = "")
    {
        $user_id = (int)$user_id;
        $user = $this->db->get_where('users', array('id' => $user_id), 1)->row_array();
        if (!$user) {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            return;
        }

        $this->db->trans_start();
        if ($this->db->table_exists('user_auth_identities')) {
            $this->db->where('user_id', $user_id)->delete('user_auth_identities');
            $this->db->where('email', strtolower(trim((string)$user['email'])))->delete('user_auth_identities');
        }
        if ($this->db->table_exists('applications')) {
            $this->db->where('user_id', $user_id)->delete('applications');
        }
        $this->db->where('id', $user_id)->delete('users');
        $this->db->trans_complete();

        $ok = $this->db->trans_status();
        $this->session->set_flashdata($ok ? 'flash_message' : 'error_message', $ok ? get_phrase('user_deleted_successfully') : 'User deletion failed.');
    }

    public function password_matches($plain_password, $stored_password)
    {
        $stored_password = (string)$stored_password;
        if ($stored_password === '') {
            return false;
        }
        if (preg_match('/^[a-f0-9]{40}$/i', $stored_password)) {
            return hash_equals(strtolower($stored_password), sha1((string)$plain_password));
        }
        return password_verify((string)$plain_password, $stored_password);
    }

    public function unlock_screen_by_password($password = "")
    {
        $password = sha1($password);
        return $this->db->get_where('users', array('id' => $this->session->userdata('user_id'), 'password' => $password))->num_rows();
    }

    public function register_user($data)
    {
        $inserted = $this->db->insert('users', $data);
        if (!$inserted) {
            $error = $this->db->error();
            log_message('error', 'User registration insert failed: ' . json_encode($error) . ' | email=' . ($data['email'] ?? ''));
            return 0;
        }

        $user_id = (int)$this->db->insert_id();
        if ($user_id <= 0) {
            log_message('error', 'User registration insert returned empty insert_id | email=' . ($data['email'] ?? ''));
            return 0;
        }

       // $this->user_model->update_unique_identifier($user_id);
        return $user_id;
    }

	/*
    public function register_user_update_code($data, $status = "")
	{
		$update_code['status'] = $status;
		$update_code['verification_code'] = $data['verification_code'];
		$update_code['password'] = $data['password'];

		// ✅ Track when code was generated (for 30-min expiry)
		$update_code['last_modified'] = time();

		$this->db->where('email', $data['email']);
		$this->db->update('users', $update_code);
	}*/
	public function register_user_update_code($data, $status = "")
	{
		$update_code = array(
			'first_name'        => $data['first_name'],
			'last_name'         => $data['last_name'],
			'phone'             => isset($data['phone']) ? $data['phone'] : '',
			'status'            => $status,
			'verification_code' => $data['verification_code'],
			'password'          => $data['password'],
			'last_modified'     => time()
		);

		$this->db->where('email', $data['email']);
		$this->db->update('users', $update_code);
	}

    public function my_courses($user_id = "")
    {
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }
        return $this->db->get_where('enrol', array('user_id' => $user_id));
    }

    public function upload_user_image($image_code)
    {
        if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
            move_uploaded_file($_FILES['user_image']['tmp_name'], 'uploads/user_image/' . $image_code . '.jpg');
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        }
    }

    public function update_account_settings($user_id)
    {
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                $user_details = $this->get_user($user_id)->row_array();
                $current_password = $this->input->post('current_password');
                $new_password = $this->input->post('new_password');
                $confirm_password = $this->input->post('confirm_password');
                if ($user_details['password'] == sha1($current_password) && $new_password == $confirm_password) {
                    $data['password'] = sha1($new_password);
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('mismatch_password'));
                    return;
                }
            }
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->session->set_flashdata('flash_message', get_phrase('updated_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }

    public function change_password($user_id)
    {
        $data = array();
        if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $user_details = $this->get_all_user($user_id)->row_array();
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');

            if ($user_details['password'] == sha1($current_password) && $new_password == $confirm_password) {
                $data['password'] = sha1($new_password);
            } else {
                $this->session->set_flashdata('error_message', get_phrase('mismatch_password'));
                return;
            }
        }

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
        $this->session->set_flashdata('flash_message', get_phrase('password_updated'));
    }


    public function get_instructor($id = 0)
    {
        if ($id > 0) {
            return $this->db->get_where('users', array('id' => $id, 'is_instructor' => 1));
        } else {
            return $this->db->get_where('users', array('is_instructor' => 1));
        }
    }

    public function get_instructor_by_email($email = null)
    {
        return $this->db->get_where('users', array('email' => $email, 'is_instructor' => 1));
    }

    public function get_admins($id = 0)
    {
        if ($id > 0) {
            return $this->db->get_where('users', array('id' => $id, 'role_id' => 1));
        } else {
            return $this->db->get_where('users', array('role_id' => 1));
        }
    }

    public function get_number_of_active_courses_of_instructor($instructor_id)
    {
        $result = $this->crud_model->get_courses_by_instructor_id($instructor_id, 'active');
        return $result->num_rows();
    }

    public function get_user_image_url($user_id)
    {
        $user_profile_image = $this->db->get_where('users', array('id' => $user_id))->row('image');
        if (file_exists('uploads/user_image/optimized/' . $user_profile_image . '.jpg')){
            return base_url() . 'uploads/user_image/optimized/' . $user_profile_image . '.jpg';
        }elseif(file_exists('uploads/user_image/' . $user_profile_image . '.jpg')){
            //resizeImage
            resizeImage('uploads/user_image/' . $user_profile_image . '.jpg', 'uploads/user_image/optimized/', 220);
            return base_url() . 'uploads/user_image/' . $user_profile_image . '.jpg';
        }else{
            return base_url() . 'uploads/user_image/placeholder.png';
        }
    }

    public function get_instructor_list()
    {
        return $this->db->get_where('users', array('status' => '1', 'is_instructor' => '1'));
        // $query1 = $this->db->get_where('course', array('status' => 'active'))->result_array();
        // $instructor_ids = array();
        // $query_result = array();
        // foreach ($query1 as $row1) {
        //     if (!in_array($row1['user_id'], $instructor_ids) && $row1['user_id'] != "") {
        //         array_push($instructor_ids, $row1['user_id']);
        //     }
        // }
        // if (count($instructor_ids) > 0) {
        //     $this->db->where_in('id', $instructor_ids);
        //     $query_result = $this->db->get('users');
        // } else {
        //     $query_result = $this->get_admin_details();
        // }

        // return $query_result;
    }

    public function update_instructor_paypal_settings($user_id = '')
    {
        $user_details = $this->get_all_user($user_id)->row_array();
        $payment_keys = json_decode($user_details['payment_keys'], true);
        // Update paypal keys
        $paypal['production_client_id'] = html_escape($this->input->post('paypal_client_id'));
        $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
        $payment_keys['paypal'] = $paypal;

        //All payment keys
        $data['payment_keys'] = json_encode($payment_keys);

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }
    public function update_instructor_stripe_settings($user_id = '')
    {
        $user_details = $this->get_all_user($user_id)->row_array();
        $payment_keys = json_decode($user_details['payment_keys'], true);
        // Update stripe keys
        $stripe['public_live_key'] = html_escape($this->input->post('stripe_public_key'));
        $stripe['secret_live_key'] = html_escape($this->input->post('stripe_secret_key'));
        $payment_keys['stripe'] = $stripe;

        //All payment keys
        $data['payment_keys'] = json_encode($payment_keys);

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }

    public function update_instructor_razorpay_settings($user_id = ''){
        $user_details = $this->get_all_user($user_id)->row_array();
        $payment_keys = json_decode($user_details['payment_keys'], true);
        // Update razorpay keys
        $razorpay['key_id'] = html_escape($this->input->post('key_id'));
        $razorpay['secret_key'] = html_escape($this->input->post('secret_key'));
        $payment_keys['razorpay'] = $razorpay;

        //All payment keys
        $data['payment_keys'] = json_encode($payment_keys);

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }

    // POST INSTRUCTOR APPLICATION FORM AND INSERT INTO DATABASE IF EVERYTHING IS OKAY
    public function post_instructor_application($user_id = "")
    {
        if($user_id == ""){
            $user_id = $this->input->post('id');
        }
        $user_details = $this->get_all_user($user_id)->row_array();

        if($this->input->post('email')){
            $email = $this->input->post('email');
        }else{
            $email = $user_details['email'];
        }

        // CHECK IF THE PROVIDED ID AND EMAIL ARE COMING FROM VALID USER
        if ($user_details['email'] == $email) {

            // GET PREVIOUS DATA FROM APPLICATION TABLE
            $previous_data = $this->get_applications($user_details['id'], 'user')->num_rows();
            // CHECK IF THE USER HAS SUBMITTED FORM BEFORE
            if ($previous_data > 0) {
                $this->session->set_flashdata('error_message', get_phrase('already_submitted'));
                redirect(site_url('user/become_an_instructor'), 'refresh');
            }
            $data['user_id'] = $user_id;
            $data['address'] = $this->input->post('address');
            $data['phone'] = $this->input->post('phone');
            $data['message'] = $this->input->post('message');
            if (isset($_FILES['document']) && $_FILES['document']['name'] != "") {
                if (!file_exists('uploads/document')) {
                    mkdir('uploads/document', 0777, true);
                }
                $accepted_ext = array('doc', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
                $path = $_FILES['document']['name'];
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), $accepted_ext)) {
                    $document_custom_name = random(15) . '.' . $ext;
                    $data['document'] = $document_custom_name;
                    move_uploaded_file($_FILES['document']['tmp_name'], 'uploads/document/' . $document_custom_name);
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('invalide_file'));
                    redirect(site_url('user/become_an_instructor'), 'refresh');
                }
            }
            $this->db->insert('applications', $data);

            // ✅ NEW: store tutor searchable/profile fields for the tutoring marketplace
            // This ensures tutor search filters (subject/location/mode/fee/rating) have data.
            $this->upsert_tutor_profile_from_post($user_id);

            $this->session->set_flashdata('flash_message', site_phrase('You have successfully submitted your application.').' '.get_phrase('We will review it and notify you via email notification'));
            redirect(site_url('user/become_an_instructor'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            redirect(site_url('user/become_an_instructor'), 'refresh');
        }
    }

    /*function instructor_application(){
        $user = $this->db->get_where('users', ['email' => $this->input->post('email')]);
        if($user->num_rows() > 0){
            $user_details = $user->row_array();
            $previous_data = $this->get_applications($user_details['id'], 'user')->num_rows();
            if ($previous_data == 0) {
                if (!file_exists('uploads/document')) {
                    mkdir('uploads/document', 0777, true);
                }
                $address_parts = array_filter([
                    trim((string)$this->input->post('tutor_address_line1')),
                    trim((string)$this->input->post('tutor_city')),
                    trim((string)$this->input->post('tutor_state')),
                    trim((string)$this->input->post('tutor_country')),
                    trim((string)$this->input->post('tutor_pincode')),
                ]);

                $data['user_id'] = $user_details['id'];
                $data['address'] = html_escape(!empty($address_parts) ? implode(', ', $address_parts) : (string)$this->input->post('tutor_location'));
                $data['phone'] = $this->input->post('phone');
                $data['message'] = $this->input->post('message');

                $document_custom_name = random(15).'.'.pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                $data['document'] = $document_custom_name;
                move_uploaded_file($_FILES['document']['tmp_name'], 'uploads/document/' . $document_custom_name);
                $this->db->insert('applications', $data);

                $this->upsert_tutor_profile_from_post($user_details['id']);
            }
        }
    }*/
	
	function instructor_application($user_id = 0, $set_flash = true){
        $user_id = (int)$user_id;
        if ($user_id > 0) {
            $user = $this->db->get_where('users', ['id' => $user_id], 1);
        } else {
            $user = $this->db->get_where('users', ['email' => $this->input->post('email')], 1);
        }

        if ($user->num_rows() <= 0) {
            return ['success' => false, 'message' => get_phrase('user_not_found')];
        }

        $user_details = $user->row_array();
        $previous_data = $this->get_applications($user_details['id'], 'user')->num_rows();
        if ($previous_data > 0) {
            return ['success' => false, 'message' => get_phrase('already_submitted')];
        }

        $address_parts = array_filter([
            trim((string)$this->input->post('tutor_address_line1')),
            trim((string)$this->input->post('tutor_city')),
            trim((string)$this->input->post('tutor_state')),
            trim((string)$this->input->post('tutor_country')),
            trim((string)$this->input->post('tutor_pincode')),
        ]);

        $document_custom_name = '';
        $target_path = '';
        if (isset($_FILES['document']) && !empty($_FILES['document']['name'])) {
            if (!file_exists('uploads/document') && !mkdir('uploads/document', 0777, true)) {
                return ['success' => false, 'message' => 'Could not create uploads/document directory.'];
            }

            $accepted_ext = array('doc', 'docx', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
            $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $accepted_ext, true)) {
                return ['success' => false, 'message' => get_phrase('invalide_file')];
            }

            $document_custom_name = random(15) . '.' . $ext;
            $target_path = 'uploads/document/' . $document_custom_name;
            if (!move_uploaded_file($_FILES['document']['tmp_name'], $target_path)) {
                return ['success' => false, 'message' => 'Document upload failed.'];
            }
        }

        $submitted_headline = trim((string)$this->input->post('tutor_headline'));
        $submitted_current_role = trim((string)$this->input->post('tutor_current_role'));
        $submitted_bio = trim((string)$this->input->post('tutor_bio'));
        $submitted_message = trim((string)$this->input->post('message'));
        $submitted_fee_currency = strtoupper(trim((string)$this->input->post('fee_currency')));
        if (!in_array($submitted_fee_currency, ['INR', 'USD', 'GBP', 'AED', 'CAD', 'AUD'], true)) {
            $submitted_fee_currency = 'INR';
        }
        $submitted_fee_type = $this->input->post('fee_type') === 'per_subject' ? 'per_subject' : 'per_hour';
        $fee_summary = '';
        if ($submitted_fee_type === 'per_hour' && trim((string)$this->input->post('tutor_hourly_fee')) !== '') {
            $fee_summary = trim((string)$this->input->post('tutor_hourly_fee')) . ' ' . $submitted_fee_currency . ' per hour';
        } elseif ($submitted_fee_type === 'per_subject') {
            $fee_rows = [];
            $fee_names = $this->input->post('subject_fee_name');
            $fee_amounts = $this->input->post('subject_fee_amount');
            if (is_array($fee_names) && is_array($fee_amounts)) {
                $count = max(count($fee_names), count($fee_amounts));
                for ($i = 0; $i < $count; $i++) {
                    $name = trim((string)($fee_names[$i] ?? ''));
                    $amount = trim((string)($fee_amounts[$i] ?? ''));
                    if ($name !== '' && $amount !== '') {
                        $fee_rows[] = $name . ': ' . $amount . ' ' . $submitted_fee_currency . ' per subject';
                    }
                }
            }
            $fee_summary = implode(', ', $fee_rows);
        }
        $profile_summary = trim(implode("\n", array_filter([
            $submitted_headline !== '' ? 'Headline: ' . $submitted_headline : '',
            'Education / Qualification: ' . trim((string)$this->input->post('tutor_qualification')),
            'Teaching Experience: ' . trim((string)$this->input->post('tutor_experience_years')) . ' year(s)',
            $submitted_current_role !== '' ? 'Current Role: ' . $submitted_current_role : '',
            $submitted_bio !== '' ? 'Teaching Bio: ' . $submitted_bio : '',
            $fee_summary !== '' ? 'Fees: ' . $fee_summary : '',
            $submitted_message !== '' ? 'Additional Message: ' . $submitted_message : '',
        ])));

        $data = [
            'user_id' => (int)$user_details['id'],
            'address' => html_escape(!empty($address_parts) ? implode(', ', $address_parts) : (string)$this->input->post('tutor_location')),
            'phone' => $this->input->post('phone'),
            'message' => $profile_summary,
            'document' => $document_custom_name,
        ];
        if ($this->db->field_exists('status', 'applications')) {
            $data['status'] = 0;
        }

        if (!$this->db->insert('applications', $data)) {
            $error = $this->db->error();
            if (file_exists($target_path)) {
                @unlink($target_path);
            }
            return [
                'success' => false,
                'message' => !empty($error['message']) ? $error['message'] : 'Application insert failed.'
            ];
        }

        // Create admin notification for tutor approval request
        $this->load->model('email_model');

        $admin = $this->db->get_where('users', ['role_id' => 1])->row_array();
        if (!empty($admin)) {
            $full_name = trim(($user_details['first_name'] ?? '') . ' ' . ($user_details['last_name'] ?? ''));
            $full_name = $full_name !== '' ? $full_name : ($user_details['email'] ?? 'New user');

            $subject = 'Tutor approval request';
            $description = 'New tutor registration submitted by ' . $full_name .
                           '<br>User email: ' . html_escape($user_details['email']) .
                           '<br>Please review and approve/reject the application.';

            $this->email_model->notify(
                'tutor_approval_request',
                (int)$admin['id'],
                $subject,
                $description,
                (int)$user_details['id']
            );
        }

        $this->upsert_tutor_profile_from_post($user_details['id']);

        if ($set_flash) {
            $this->session->set_flashdata('flash_message', site_phrase('You have successfully submitted your application.').' '.get_phrase('We will review it and notify you via email notification'));
        }

        return ['success' => true, 'message' => 'Tutor application submitted successfully.'];
    }


    // GET INSTRUCTOR APPLICATIONS
    public function get_applications($id = "", $type = "")
    {
        if ($id > 0 && !empty($type)) {
            if ($type == 'user') {
                $applications = $this->db->get_where('applications', array('user_id' => $id));
                return $applications;
            } else {
                $applications = $this->db->get_where('applications', array('id' => $id));
                return $applications;
            }
        } else {
            $this->db->order_by("id", "DESC");
            $applications = $this->db->get_where('applications');
            return $applications;
        }
    }

    public function pending_tutor_login_block_message($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return '';
        }

        $user = $this->db->get_where('users', ['id' => $user_id], 1)->row_array();
        if (empty($user)) {
            return '';
        }

        $this->ensure_application_review_columns();

        $application = $this->db
            ->order_by('id', 'DESC')
            ->get_where('applications', ['user_id' => $user_id], 1)
            ->row_array();
        if (empty($application)) {
            if ($this->db->table_exists('tutor_profiles')) {
                $profile = $this->db->get_where('tutor_profiles', ['user_id' => $user_id], 1)->row_array();
                $profile_status = strtolower((string)($profile['status'] ?? ''));
                if ($profile_status === 'pending') {
                    return 'Your tutor registration is pending admin approval. Please log in after approval.';
                }
                if ($profile_status === 'rejected') {
                    return 'Your tutor application was rejected by admin. Please contact support or submit a new tutor application with updated details.';
                }
                if ($profile_status === 'inactive') {
                    return 'Your tutor application is not approved. Please contact support for the admin decision before logging in as a tutor.';
                }
            }

            return '';
        }

        $application_status = (string)($application['status'] ?? '0');
        if ((int)($user['is_instructor'] ?? 0) === 1 && (int)$application_status === 1) {
            return '';
        }

        if ((int)$application_status === 2) {
            $admin_response = trim((string)($application['admin_response'] ?? ''));
            $message = 'Your tutor application was rejected by admin.';
            if ($admin_response !== '') {
                $message .= ' Admin response: ' . $admin_response;
            } else {
                $message .= ' Please contact support or submit a new tutor application with updated details.';
            }
            return $message;
        }

        return 'Your tutor registration is pending admin approval. Please log in after approval.';
    }

    private function ensure_application_review_columns()
    {
        if (!$this->db->table_exists('applications')) {
            return;
        }

        if (!$this->db->field_exists('admin_response', 'applications')) {
            $this->db->query("ALTER TABLE `applications` ADD COLUMN `admin_response` TEXT NULL");
        }

        if (!$this->db->field_exists('reviewed_by', 'applications')) {
            $this->db->query("ALTER TABLE `applications` ADD COLUMN `reviewed_by` INT NULL");
        }

        if (!$this->db->field_exists('reviewed_at', 'applications')) {
            $this->db->query("ALTER TABLE `applications` ADD COLUMN `reviewed_at` DATETIME NULL");
        }
    }

    // GET APPROVED APPLICATIONS
    public function get_approved_applications()
    {
        $applications = $this->db->get_where('applications', array('status' => 1));
        return $applications;
    }

    // GET PENDING APPLICATIONS
    public function get_pending_applications()
    {
        $applications = $this->db->get_where('applications', array('status' => 0));
        return $applications;
    }

    // GET REJECTED APPLICATIONS
    public function get_rejected_applications()
    {
        $this->ensure_application_review_columns();
        $applications = $this->db->get_where('applications', array('status' => 2));
        return $applications;
    }

    //UPDATE STATUS OF INSTRUCTOR APPLICATION
    /*public function update_status_of_application($status, $application_id)
    {
        $application_details = $this->get_applications($application_id, 'application');
        if ($application_details->num_rows() > 0) {
            $application_details = $application_details->row_array();
            if ($status == 'approve') {
                $application_data['status'] = 1;
                $this->db->where('id', $application_id);
                $this->db->update('applications', $application_data);

                $instructor_data['is_instructor'] = 1;
                $this->db->where('id', $application_details['user_id']);
                $this->db->update('users', $instructor_data);

                // ✅ NEW: activate tutor profile so tutor becomes searchable
                if ($this->db->table_exists('tutor_profiles')) {
                    $exists = $this->db->get_where('tutor_profiles', ['user_id' => (int)$application_details['user_id']], 1);
                    if ($exists->num_rows() > 0) {
                        $this->db->where('user_id', (int)$application_details['user_id'])->update('tutor_profiles', ['status' => 'active']);
                    } else {
                        // If profile wasn't created for some reason, create minimal active profile.
                        $this->db->insert('tutor_profiles', [
                            'user_id' => (int)$application_details['user_id'],
                            'status'  => 'active',
                            'teaching_mode' => 'both'
                        ]);
                    }
                }

                $this->session->set_flashdata('flash_message', get_phrase('application_approved_successfully'));
                redirect(site_url('admin/instructor_application'), 'refresh');
            } else {
                $this->db->where('id', $application_id);
                $this->db->delete('applications');
                $this->session->set_flashdata('flash_message', get_phrase('application_deleted_successfully'));
                redirect(site_url('admin/instructor_application'), 'refresh');
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('invalid_application'));
            redirect(site_url('admin/instructor_application'), 'refresh');
        }
    }*/
	
	//UPDATE STATUS OF INSTRUCTOR APPLICATION
	public function update_status_of_application($status, $application_id)
	{
		$this->ensure_application_review_columns();
		$application_details = $this->get_applications($application_id, 'application');

		if ($application_details->num_rows() <= 0) {
			$this->session->set_flashdata('error_message', get_phrase('invalid_application'));
			redirect(site_url('admin/instructor_application'), 'refresh');
		}

		$application_details = $application_details->row_array();
		$user_id = (int) $application_details['user_id'];
		$user_details = $this->db->get_where('users', ['id' => $user_id])->row_array();
		$admin_user_id = (int) $this->session->userdata('user_id');
	
		if (!is_array($user_details) || empty($user_details)) {
			log_message('error', 'Application approval failed: linked user record missing. application_id = ' . $application_id . ', user_id = ' . $user_id);
			//$this->session->set_flashdata('error_message', 'Cannot approve application because linked user record was not found.');
			$this->session->set_flashdata(
				'error_message',
				'Approval failed: User not found for this application. 
				Application ID: '.$application_id.' | Missing User ID: '.$user_id.'. 
				This usually happens if the user record was deleted or not created properly.'
			);
			
			
			redirect(site_url('admin/instructor_application'), 'refresh');
		}

		$this->load->model('email_model');

		if ($status == 'approve') {
			$has_required_document = !empty($application_details['document']);
			$profile_status = $has_required_document ? 'active' : 'documents_pending';
			$approval_response = $has_required_document
				? 'Approved by admin.'
				: 'Approved by admin. Document verification is still pending. Please upload Government ID proof, qualification certificate, marksheet, professional certification, teaching experience proof, or another supporting document from your tutor dashboard before your profile can be verified and publicly listed.';

			// 1. Approve application
			$application_data = array(
				'status' => 1,
				'admin_response' => $approval_response,
				'reviewed_by' => $admin_user_id,
				'reviewed_at' => date('Y-m-d H:i:s')
			);
			$this->db->where('id', $application_id);
			$this->db->update('applications', $application_data);

			// 2. Mark user as instructor/tutor approved
			$instructor_data['is_instructor'] = 1;
			$this->db->where('id', $user_id);
			$this->db->update('users', $instructor_data);

			// 3. Activate tutor profile
			if ($this->db->table_exists('tutor_profiles')) {
				$exists = $this->db->get_where('tutor_profiles', ['user_id' => $user_id], 1);
				if ($exists->num_rows() > 0) {
					$this->db->where('user_id', $user_id)->update('tutor_profiles', ['status' => $profile_status]);
				} else {
					$this->db->insert('tutor_profiles', [
						'user_id'       => $user_id,
						'status'        => $profile_status,
						'teaching_mode' => 'both'
					]);
				}
			}

			// 4. Notify tutor
			if (!empty($user_details)) {
				$this->email_model->notify(
					'tutor_application_approved',
					$user_id,
					'Tutor application approved',
					$has_required_document
						? 'Your tutor application has been approved by admin. You can now log in and continue.'
						: 'Your tutor application has been approved by admin. You can now log in, but please upload your verification document from the tutor dashboard before your profile can be verified and publicly listed.',
					$admin_user_id
				);
			}

			$this->session->set_flashdata('flash_message', $has_required_document ? get_phrase('application_approved_successfully') : 'Application approved. Tutor can log in, but document upload is still required for verified badge and public listing.');
			redirect(site_url('admin/instructor_application'), 'refresh');
		}

		// Reject application without deleting tutor intent. Login must keep showing the admin decision.
		$admin_response = trim(strip_tags((string)$this->input->post('admin_response', true)));
		if ($admin_response === '') {
			$admin_response = trim(strip_tags((string)$this->input->post('reject_reason', true)));
		}
		if ($admin_response === '') {
			$this->session->set_flashdata('error_message', 'Please enter a rejection response for the tutor.');
			redirect(site_url('admin/instructor_application'), 'refresh');
		}

		$this->db->where('id', $application_id);
		$this->db->update('applications', array(
			'status' => 2,
			'admin_response' => $admin_response,
			'reviewed_by' => $admin_user_id,
			'reviewed_at' => date('Y-m-d H:i:s')
		));

		$this->db->where('id', $user_id);
		$this->db->update('users', array(
			'role_id' => 2,
			'status' => 1,
			'is_instructor' => 0
		));

		if ($this->db->table_exists('tutor_profiles')) {
			$this->db->where('user_id', $user_id)->update('tutor_profiles', ['status' => 'rejected']);
		}

		if (!empty($user_details)) {
			$this->email_model->notify(
				'tutor_application_rejected',
				$user_id,
				'Tutor application rejected',
				'Your tutor application has been rejected by admin. Response: ' . $admin_response,
				$admin_user_id
			);
		}

		$this->session->set_flashdata('flash_message', 'Tutor application rejected and response sent to the applicant.');
		redirect(site_url('admin/instructor_application'), 'refresh');
	}

    // ASSIGN PERMISSION
    public function assign_permission()
    {
        $argument = html_escape($this->input->post('arg'));
        $argument = explode('-', $argument);
        $admin_id = $argument[0];
        $module = $argument[1];

        // CHECK IF IT IS A ROOT ADMIN
        if (is_root_admin($admin_id)) {
            return false;
        }

        $permission_data['admin_id'] = $admin_id;
        $previous_permissions = json_decode($this->get_admins_permission_json($permission_data['admin_id']), TRUE);

        if (in_array($module, $previous_permissions)) {
            $new_permission = array();
            foreach ($previous_permissions as $permission) {
                if ($permission != $module) {
                    array_push($new_permission, $permission);
                }
            }
        } else {
            array_push($previous_permissions, $module);
            $new_permission = $previous_permissions;
        }

        $permission_data['permissions'] = json_encode($new_permission);

        $this->db->where('admin_id', $admin_id);
        $this->db->update('permissions', $permission_data);
        return true;
    }

    // GET ADMIN'S PERMISSION JSON
    public function get_admins_permission_json($admin_id)
    {
        $admins_permissions = $this->db->get_where('permissions', ['admin_id' => $admin_id])->row_array();
        return $admins_permissions['permissions'];
    }

    // GET MULTI INSTRUCTOR DETAILS WITH COURSE ID
    public function get_multi_instructor_details_with_csv($csv)
    {
        $instructor_ids = explode(',', $csv);
        $this->db->where_in('id', $instructor_ids);
        return $this->db->get('users')->result_array();
    }

    function quiz_submission_checker($quiz_id = ""){
        $quiz_details = $this->crud_model->get_lessons('lesson', $quiz_id)->row_array();
        $total_quiz_seconds = time_to_seconds($quiz_details['duration']);

        $this->db->where('quiz_id', $quiz_id);
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $query = $this->db->order_by('quiz_result_id', 'desc')->get('quiz_results');
        if($query->num_rows() > 0){
            $row = $query->row_array();
            if(($total_quiz_seconds + $row['date_added']) < time() && $total_quiz_seconds > 0 || $row['is_submitted'] == 1){

                if($row['is_submitted'] != 1){
                    $this->db->where('quiz_id', $quiz_id);
                    $this->db->where('user_id', $this->session->userdata('user_id'));
                    $this->db->update('quiz_results', array('is_submitted' => 1));
                }

                return 'submitted';
            }else{
                return 'on_progress';
            }
        }else{
            return 'no_data';
        }
    }



/*START LOGIN LOGOUT AND DEVICE ALLOW SECTION*/
    private function testing_verification_disabled(): bool
    {
        // Temporary testing switch: set to false after registration/login testing is complete.
        return true;
    }

    // For device login tracker
    public function new_device_login_tracker($user_id = "", $is_verified = '')
    {
        $pre_sessions = array();
        $updated_session_arr = array();
        $current_session_id = session_id();
        $this->db->where('id', $user_id);
        $sessions = $this->db->get('users');

        if($sessions->row('role_id') == 1){
            return;
        }

        $pre_sessions = json_decode($sessions->row('sessions'), true);

        if ($this->testing_verification_disabled()) {
            $updated_session_arr = is_array($pre_sessions) ? $pre_sessions : array();
            if (!in_array($current_session_id, $updated_session_arr)) {
                array_push($updated_session_arr, $current_session_id);
            }
            $this->db->where('id', $user_id);
            $this->db->update('users', array('sessions' => json_encode($updated_session_arr)));

            // ── NEW: run suspicious-login pipeline even when OTP flow is disabled ──
            $this->_run_suspicious_login_pipeline((int)$user_id);
            return;
        }

        if(is_array($pre_sessions) && count($pre_sessions) > 0){
            if($is_verified == true && !in_array($current_session_id, $pre_sessions)){
                $allowed_device = get_settings('allowed_device_number_of_loging');
                $previous_tatal_device = count($pre_sessions) + 1; //current device

                $removeable_device = $previous_tatal_device - $allowed_device;

                foreach($pre_sessions as $key => $pre_session){
                    if($removeable_device >= 1){
                        $this->db->where('id', $pre_session);
                        $this->db->delete('ci_sessions');
                    }else{

                        if($this->db->get_where('ci_sessions', ['id' => $pre_session])->num_rows() > 0){
                            array_push($updated_session_arr, $pre_session);                        
                        }
                    }
                    $removeable_device = $removeable_device - 1;
                }
                array_push($updated_session_arr, $current_session_id);
            }else{
                if(!in_array($current_session_id, $pre_sessions)){
                    if(count($pre_sessions) >= get_settings('allowed_device_number_of_loging')){
                        $this->email_model->new_device_login_alert($user_id);
                        redirect(site_url('login/new_login_confirmation'), 'refresh');
                    }else{
                        $updated_session_arr = $pre_sessions;
                        array_push($updated_session_arr, $current_session_id);
                    }
                }
            }
        }else{
            $updated_session_arr = [$current_session_id];
        }

        if(count($updated_session_arr) > 0){
            $data['sessions'] = json_encode($updated_session_arr);
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
        }

        // ── NEW: run suspicious-login pipeline ──
        $this->_run_suspicious_login_pipeline((int)$user_id);
    }

    /**
     * Suspicious Login Detection Pipeline
     * ------------------------------------
     * Called on every successful login (including when OTP bypass is active).
     * Captures device context, records history, checks trusted devices,
     * and triggers a security alert email if the login looks suspicious.
     *
     * Wrapped in try/catch: ANY failure here is logged but NEVER breaks login.
     *
     * @param int $user_id
     */
    private function _run_suspicious_login_pipeline(int $user_id)
    {
        try {
            // ── Load dependencies ──
            $this->load->helper('security_login');
            $this->load->model('Security_login_model', 'security_login_model');
            $this->load->library('user_agent');

            // ── Gather user record ──
            $user = $this->db->get_where('users', ['id' => $user_id], 1)->row_array();
            if (empty($user)) {
                return;
            }

            // ── Collect device & network context ──
            $ip           = sl_get_client_ip();
            $ua_string    = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $browser_name = $this->agent->is_browser() ? $this->agent->browser() : (
                            $this->agent->is_mobile()  ? $this->agent->mobile()  : 'Unknown');
            $browser_ver  = $this->agent->is_browser() ? $this->agent->version() : '';
            $os_name      = $this->agent->platform();
            $device_type  = sl_get_device_type();
            $fingerprint  = sl_get_device_fingerprint($browser_name, $browser_ver, $os_name, $device_type);
            $role         = sl_get_role_label($user);

            // ── Geolocation (silent fallback to nulls) ──
            $geo = sl_get_ip_geolocation($ip);

            // ── Determine if suspicious ──
            $is_suspicious = (int)$this->security_login_model->is_suspicious_login($user_id, $fingerprint, [
                'ip_address' => $ip,
                'city'       => $geo['city'],
                'country'    => $geo['country']
            ]);

            // ── Record login history ──
            $history_id = $this->security_login_model->record_login([
                'user_id'         => $user_id,
                'role'            => $role,
                'email'           => $user['email'] ?? '',
                'ip_address'      => $ip,
                'user_agent'      => $ua_string,
                'browser_name'    => $browser_name,
                'browser_version' => $browser_ver,
                'os_name'         => $os_name,
                'device_type'     => $device_type,
                'city'            => $geo['city'],
                'state'           => $geo['state'],
                'country'         => $geo['country'],
                'is_suspicious'   => $is_suspicious,
                'alert_sent'      => 0,
            ]);

            // ── First login or trusted device: trust it and finish ──
            if (!$is_suspicious) {
                $this->security_login_model->trust_device($user_id, $role, $fingerprint, [
                    'ip_address'   => $ip,
                    'browser_name' => $browser_name,
                    'os_name'      => $os_name,
                    'city'         => $geo['city'],
                    'country'      => $geo['country'],
                ]);
                return;
            }

            // ── Suspicious login ── check duplicate alert prevention ──
            $dup = sl_duplicate_alert_exists($this->db, $user_id, $fingerprint, 30);
            if ($dup) {
                return; // Alert already sent recently, skip
            }

            // ── Create secure token ──
            $token = $this->security_login_model->create_alert_token(
                $user_id, $role, $user['email'], $history_id, 'new_device'
            );

            if ($token === '') {
                return; // Token creation failed (tables missing?), skip silently
            }

            // ── Build confirmation URLs ──
            $yes_url = site_url('security/confirm/yes/' . $token);
            $no_url  = site_url('security/confirm/no/' . $token);

            // ── Send alert email (login never fails if this fails) ──
            $login_info = [
                'browser_name' => $browser_name,
                'browser_ver'  => $browser_ver,
                'os_name'      => $os_name,
                'device_type'  => $device_type,
                'ip_address'   => $ip,
                'city'         => $geo['city'],
                'state'        => $geo['state'],
                'country'      => $geo['country'],
                'login_time'   => date('d M Y, h:i A'),
            ];

            $sent = $this->email_model->send_suspicious_login_alert($user, $login_info, $yes_url, $no_url);

            // ── Update alert log with email status ──
            $this->security_login_model->update_alert_email_status(
                $token,
                $sent ? 'sent' : 'failed',
                $sent ? '' : 'SMTP send failed'
            );

            if ($sent) {
                $this->security_login_model->mark_alert_sent($history_id);
            }

        } catch (Exception $e) {
            log_message('error', 'User_model::_run_suspicious_login_pipeline failed for user ' . $user_id . ': ' . $e->getMessage());
            // Never propagate – login must continue
        }
    }

    function set_login_userdata($user_id = ""){
        // Checking login credential for admin
        $query = $this->db->get_where('users', array('id' => $user_id));

        if ($query->num_rows() > 0) {
            $row = $query->row();
            //604800s == 7 days
            $this->session->set_userdata('custom_session_limit', (time()+864000));
            $this->session->set_userdata('user_id', $row->id);
            $this->session->set_userdata('role_id', $row->role_id);
            $this->session->set_userdata('role', get_user_role('user_role', $row->id));
            $this->session->set_userdata('name', $row->first_name . ' ' . $row->last_name);
            $this->session->set_userdata('is_instructor', $row->is_instructor);
            $this->session->set_flashdata('flash_message', get_phrase('welcome') . ' ' . $row->first_name . ' ' . $row->last_name);
            if ($row->role_id == 1) {
                $this->session->set_userdata('admin_login', '1');
                redirect(site_url('admin/dashboard'), 'refresh');
            } else if ($row->role_id == 2) {
                $this->session->set_userdata('user_login', '1');
                if($this->session->userdata('url_history')){
                    redirect($this->session->userdata('url_history'), 'refresh');
                }
                if ((int)$row->is_instructor === 1) {
                    redirect(site_url('user/dashboard'), 'refresh');
                }
                redirect(site_url('home/student_dashboard'), 'refresh');
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('invalid_login_credentials'));
            redirect(site_url('login'), 'refresh');
        }
    }

    function check_session_data($user_type = ""){
        $this->remove_garbage_collection();

        if (!$this->session->userdata('cart_items')) {
            $this->session->set_userdata('cart_items', array());
        }

        if (!$this->session->userdata('language')) {
            $this->session->set_userdata('language', get_settings('language'));
        }

        if($user_type == 'admin'){
            if($this->session->userdata('custom_session_limit') >= time()){
                $this->session->set_userdata('custom_session_limit', (time()+864000));
            }else{
                $this->session_destroy();
                redirect(site_url('login'), 'refresh');
            }

            if ($this->session->userdata('admin_login') != true) {
                redirect(site_url('login'), 'refresh');
            }
        }elseif($user_type == 'user'){
            if($this->session->userdata('custom_session_limit') >= time()){
                $this->session->set_userdata('custom_session_limit', (time()+864000));
            }else{
                $this->session_destroy();
                redirect(site_url('login'), 'refresh');
            }

            if ($this->session->userdata('user_login') != true) {
                redirect(site_url('login'), 'refresh');
            }else{
                if($this->get_all_user($this->session->userdata('user_id'))->num_rows() == 0){
                    $this->session_destroy();
                    redirect(site_url('login'), 'refresh');
                }
                $tutor_login_block_message = $this->pending_tutor_login_block_message((int)$this->session->userdata('user_id'));
                if ($tutor_login_block_message !== '') {
                    $this->session_destroy();
                    $this->session->set_flashdata('error_message', $tutor_login_block_message);
                    redirect(site_url('login'), 'refresh');
                }
            }
        }elseif($user_type == 'login'){
            if ($this->session->userdata('admin_login')) {
                redirect(site_url('admin'), 'refresh');
            } elseif ($this->session->userdata('user_login')) {
                redirect(site_url('home/my_courses'), 'refresh');
            }
        }
    }

    public function session_destroy()
    {
        $cart_items = $this->session->userdata('cart_items');
        $language = $this->session->userdata('language');

        $this->remove_garbage_collection();

        $logged_in_user_id = $this->session->userdata('user_id');
        if($logged_in_user_id > 0 && $this->session->userdata('user_login') == 1){
            $pre_sessions = array();
            $updated_session_arr = array();
            $current_session_id = session_id();

            $this->db->where('id', $logged_in_user_id);
            $sessions = $this->db->get('users')->row('sessions');
            $pre_sessions = json_decode($sessions, true);
            if(is_array($pre_sessions)){
                foreach($pre_sessions as $key => $pre_session){
                    if($pre_session != $current_session_id){
                        if($this->db->get_where('ci_sessions', ['id' => $pre_session])->num_rows() > 0){
                            array_push($updated_session_arr, $pre_session);                        
                        }
                    }else{
                        $this->db->where('id', $pre_session);
                        $this->db->delete('ci_sessions');
                    }
                }
                $data['sessions'] = json_encode($updated_session_arr);
                $this->db->where('id', $logged_in_user_id);
                $this->db->update('users', $data);
            }
        }

        $this->session->unset_userdata('admin_login');
        $this->session->unset_userdata('user_login');
        $this->session->unset_userdata('custom_session_limit');
        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('role_id');
        $this->session->unset_userdata('role');
        $this->session->unset_userdata('name');
        $this->session->unset_userdata('is_instructor');
        $this->session->unset_userdata('url_history');
        $this->session->unset_userdata('app_url');
        $this->session->unset_userdata('total_price_of_checking_out');
        $this->session->unset_userdata('register_email');
        $this->session->unset_userdata('applied_coupon');
        $this->session->unset_userdata('new_device_code_expiration_time');
        $this->session->unset_userdata('new_device_user_email');
        $this->session->unset_userdata('new_device_user_id');
        $this->session->unset_userdata('new_device_verification_code');

        $this->session->unset_userdata('oauth_provider');
        $this->session->unset_userdata('oauth_state');
        $this->session->unset_userdata('oauth_nonce');
        $this->session->unset_userdata('oauth_code_verifier');
        $this->session->unset_userdata('oauth_started_at');
        $this->session->unset_userdata('oauth_registration_type');
        $this->session->unset_userdata('oauth_flow');

        if (method_exists($this->session, 'sess_regenerate')) {
            $this->session->sess_regenerate(true);
        }

        if (!empty($cart_items)) {
            $this->session->set_userdata('cart_items', $cart_items);
        }
        if (!empty($language)) {
            $this->session->set_userdata('language', $language);
        }
}

    function remove_garbage_collection(){
        $this->db->where('timestamp <', time()-864000);
        $this->db->delete('ci_sessions');
    }
    /*END LOGIN LOGOUT AND DEVICE ALLOW SECTION*/


   /* function update_unique_identifier($user_id = ""){
        $data['unique_identifier'] = $user_id.strtolower(random(10));
        $this->db->where('unique_identifier', 0);
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }*/



    //course-gift-ryan

    function get_user_by_email($email = ""){
        if($email){
            $this->db->where('email', $email);
        }
        return $this->db->get('users');
    }

    //course-gift-ryan
}
