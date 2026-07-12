<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{
    private $workflow_action_token = '';

    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set(get_settings('timezone'));

        $this->load->database();
        $this->load->library('session');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        // THIS FUNCTION DECIDES WHTHER THE ROUTE IS REQUIRES PUBLIC INSTRUCTOR.
        //$this->get_protected_routes($this->router->method);

        // THIS MIDDLEWARE FUNCTION CHECKS WHETHER THE USER IS TRYING TO ACCESS INSTRUCTOR STUFFS.
        $this->instructor_authorization($this->router->method);

        $this->instructor_approval();

        // CHECK CUSTOM SESSION DATA
        $this->user_model->check_session_data('user');

        $this->workflow_action_token = (string)$this->session->userdata('workflow_action_token');
        if ($this->workflow_action_token === '') {
            $this->workflow_action_token = bin2hex(random_bytes(32));
            $this->session->set_userdata('workflow_action_token', $this->workflow_action_token);
        }
    }

    function instructor_approval()
    {
        $user_id = $this->session->userdata('user_id');
        $query = $this->db->get_where('users', array('id' => $user_id));

        if ($query->num_rows() > 0) {
            $this->session->set_userdata('is_instructor', $query->row('is_instructor'));
        }
    }


    public function get_protected_routes($method)
    {
        // IF ANY FUNCTION DOES NOT REQUIRE PUBLIC INSTRUCTOR, PUT THE NAME HERE.
        $unprotected_routes = ['save_course_progress', 'start_quiz', 'retake_quiz', 'finish_quize_submission', 'submit_quiz_answer', 'join_bbb_meeting'];

        if (!in_array($method, $unprotected_routes)) {
            if (get_settings('allow_instructor') != 1) {
                redirect(site_url('home'), 'refresh');
            }
        }
    }

    public function instructor_authorization($method)
    {
        // IF THE USER IS NOT AN INSTRUCTOR HE/SHE CAN NEVER ACCESS THE OTHER FUNCTIONS EXCEPT FOR BELOW FUNCTIONS.
        if ($this->session->userdata('is_instructor') != 1) {
            $unprotected_routes = ['become_an_instructor', 'manage_profile', 'save_course_progress', 'start_quiz', 'retake_quiz', 'submit_quiz_answer', 'finish_quize_submission', 'join_bbb_meeting', 'my_practice_tests'];

            if (!in_array($method, $unprotected_routes)) {
                redirect(site_url('user/become_an_instructor'), 'refresh');
            }
        }
    }

    public function index()
    {
        if ($this->session->userdata('user_login') == true) {
            $this->dashboard();
        } else {
            redirect(site_url('login'), 'refresh');
        }
    }

    public function dashboard()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $user_id = (int)$this->session->userdata('user_id');
        if (!isset($this->tutor_batch_model) || !is_object($this->tutor_batch_model)) {
            $this->load->model('tutor_batch_model');
        }

        $page_data['teacher_360'] = $this->tutor_batch_model->get_teacher_360_foundation($user_id);
        $this->load->model('Teacher_workflow_model', 'teacher_workflow');
        $page_data['teacher_agenda'] = $this->teacher_workflow->get_daily_workspace($user_id);
        $page_data['teacher_calendar'] = $this->teacher_workflow->get_calendar($user_id, date('Y-m-d'), date('Y-m-d', strtotime('+14 days')));
        $page_data['teacher_360_earnings'] = [
            'pending' => $this->crud_model->get_total_pending_amount($user_id),
            'requested' => $this->crud_model->get_requested_withdrawal_amount($user_id),
            'paid' => $this->crud_model->get_total_payout_amount($user_id),
        ];
        $page_data['teacher_command_center'] = $this->_get_teacher_command_center($user_id);
        $page_data['page_name'] = 'dashboard';
        $page_data['page_title'] = get_phrase('dashboard');
        $this->load->view('backend/index.php', $page_data);
    }

    private function _get_teacher_command_center($user_id)
    {
        $user_id = (int)$user_id;
        $today = date('Y-m-d');
        $metrics = [
            'books' => $this->_count_teacher_content($user_id, 'book', true),
            'pages' => $this->_count_teacher_content($user_id, 'page', false),
            'articles' => $this->_count_teacher_content($user_id, 'article', false),
            'content_in_review' => $this->_count_teacher_content_status($user_id, ['in_review', 'submitted', 'pending']),
            'content_published' => $this->_count_teacher_content_status($user_id, ['published', 'approved']),
            'questions' => $this->_count_teacher_rows('question_bank_questions', $user_id, ['created_by']),
            'questions_ready' => $this->_count_teacher_rows('question_bank_questions', $user_id, ['created_by'], ['status' => ['active', 'approved', 'published']]),
            'questions_in_review' => $this->_count_teacher_rows('question_bank_questions', $user_id, ['created_by'], ['review_status' => ['in_review', 'submitted', 'pending']]),
            'exams' => $this->_count_teacher_exams($user_id),
            'exams_published' => $this->_count_teacher_exams($user_id, ['status' => ['published', 'active']]),
            'exams_in_review' => $this->_count_teacher_exams($user_id, ['review_status' => ['in_review', 'submitted', 'pending']]),
            'batches' => $this->_count_teacher_rows('tutor_batches', $user_id, ['tutor_user_id']),
            'active_batches' => $this->_count_teacher_rows('tutor_batches', $user_id, ['tutor_user_id'], ['status' => ['active', 'published']]),
            'students' => $this->_count_teacher_students($user_id),
            'exam_attempts' => $this->_count_teacher_exam_attempts($user_id),
            'upcoming_sessions' => $this->_count_teacher_sessions($user_id, $today),
        ];

        $actions = [
            [
                'title' => 'Create book or notes',
                'text' => 'Build chapters and pages, save drafts, then submit for review.',
                'url' => site_url('user/content_nodes?section=book'),
                'icon' => 'mdi-book-open-page-variant',
                'tone' => 'primary',
                'count' => $metrics['books'],
                'count_label' => 'books',
            ],
            [
                'title' => 'Create blog/article',
                'text' => 'Write short learning articles and publish them through review.',
                'url' => site_url('user/content_nodes?section=article'),
                'icon' => 'mdi-file-document-edit-outline',
                'tone' => 'info',
                'count' => $metrics['articles'],
                'count_label' => 'articles',
            ],
            [
                'title' => 'Question bank',
                'text' => 'Create, upload, search, and reuse questions by exam, section, topic, and difficulty.',
                'url' => site_url('user/question_bank?qb_tab=create_exam'),
                'icon' => 'mdi-database-search',
                'tone' => 'success',
                'count' => $metrics['questions'],
                'count_label' => 'questions',
            ],
            [
                'title' => 'Mock or real exam',
                'text' => 'Build section-wise patterns and pull ready questions from your own bank.',
                'url' => site_url('user/content_public_exams'),
                'icon' => 'mdi-clipboard-text-outline',
                'tone' => 'warning',
                'count' => $metrics['exams'],
                'count_label' => 'exams',
            ],
            [
                'title' => 'Create batch',
                'text' => 'Group students, schedule classes, share assignments, and run tests.',
                'url' => site_url('tutor_batch/create'),
                'icon' => 'mdi-account-group-outline',
                'tone' => 'purple',
                'count' => $metrics['batches'],
                'count_label' => 'batches',
            ],
            [
                'title' => 'Student reports',
                'text' => 'Track attendance, submissions, scores, and learning risk signals.',
                'url' => site_url('teacher-analytics'),
                'icon' => 'mdi-chart-line',
                'tone' => 'dark',
                'count' => $metrics['students'],
                'count_label' => 'students',
            ],
        ];

        $attention = [];
        if ($metrics['content_in_review'] > 0) {
            $attention[] = ['label' => 'Content waiting for admin review', 'value' => $metrics['content_in_review'], 'url' => site_url('user/content_nodes?section=book'), 'tone' => 'warning'];
        }
        if ($metrics['questions_in_review'] > 0) {
            $attention[] = ['label' => 'Question bank items in review', 'value' => $metrics['questions_in_review'], 'url' => site_url('user/question_bank?qb_tab=search'), 'tone' => 'warning'];
        }
        if ($metrics['exams_in_review'] > 0) {
            $attention[] = ['label' => 'Exam patterns in review', 'value' => $metrics['exams_in_review'], 'url' => site_url('user/content_public_exams'), 'tone' => 'warning'];
        }
        if ($metrics['questions'] === 0) {
            $attention[] = ['label' => 'Start your first question bank', 'value' => 'New', 'url' => site_url('user/question_bank?qb_tab=create_exam'), 'tone' => 'info'];
        }
        if ($metrics['batches'] === 0) {
            $attention[] = ['label' => 'Create a batch before inviting students', 'value' => 'New', 'url' => site_url('tutor_batch/create'), 'tone' => 'primary'];
        }
        if ($metrics['students'] > 0 && $metrics['exam_attempts'] === 0) {
            $attention[] = ['label' => 'No submitted test reports yet', 'value' => 'Track', 'url' => site_url('teacher-analytics'), 'tone' => 'info'];
        }

        return [
            'metrics' => $metrics,
            'actions' => $actions,
            'attention' => $attention,
        ];
    }

    private function _count_teacher_content($user_id, $content_type, $root_only = false)
    {
        if (!$this->db->table_exists('content_nodes')) {
            return 0;
        }
        $this->db->from('content_nodes');
        if ($this->db->field_exists('created_by', 'content_nodes')) {
            $this->db->where('created_by', (int)$user_id);
        }
        if ($this->db->field_exists('content_type', 'content_nodes')) {
            $this->db->where('content_type', $content_type);
        }
        if ($root_only && $this->db->field_exists('is_root', 'content_nodes')) {
            $this->db->where('is_root', 1);
        }
        if ($this->db->field_exists('is_deleted', 'content_nodes')) {
            $this->db->where('is_deleted', 0);
        }
        return (int)$this->db->count_all_results();
    }

    private function _count_teacher_content_status($user_id, $statuses)
    {
        if (!$this->db->table_exists('content_nodes')) {
            return 0;
        }
        $status_field = $this->db->field_exists('review_status', 'content_nodes') ? 'review_status' : ($this->db->field_exists('status', 'content_nodes') ? 'status' : '');
        if ($status_field === '') {
            return 0;
        }
        $this->db->from('content_nodes');
        if ($this->db->field_exists('created_by', 'content_nodes')) {
            $this->db->where('created_by', (int)$user_id);
        }
        if ($this->db->field_exists('is_deleted', 'content_nodes')) {
            $this->db->where('is_deleted', 0);
        }
        $this->db->where_in($status_field, $statuses);
        return (int)$this->db->count_all_results();
    }

    private function _count_teacher_rows($table, $user_id, $owner_fields, $filters = [])
    {
        if (!$this->db->table_exists($table)) {
            return 0;
        }
        $owner_field = '';
        foreach ($owner_fields as $field) {
            if ($this->db->field_exists($field, $table)) {
                $owner_field = $field;
                break;
            }
        }
        $this->db->from($table);
        if ($owner_field !== '') {
            $this->db->where($owner_field, (int)$user_id);
        }
        foreach ($filters as $field => $values) {
            if (!$this->db->field_exists($field, $table)) {
                continue;
            }
            $this->db->where_in($field, (array)$values);
        }
        return (int)$this->db->count_all_results();
    }

    private function _count_teacher_exams($user_id, $filters = [])
    {
        if (!$this->db->table_exists('content_exams')) {
            return 0;
        }
        $this->db->from('content_exams');
        if ($this->db->field_exists('tutor_id', 'content_exams')) {
            $this->db->where('tutor_id', (int)$user_id);
        } elseif ($this->db->field_exists('created_by', 'content_exams')) {
            $this->db->where('created_by', (int)$user_id);
        }
        if ($this->db->field_exists('managed_by_admin', 'content_exams')) {
            $this->db->where('managed_by_admin', 0);
        }
        foreach ($filters as $field => $values) {
            if ($this->db->field_exists($field, 'content_exams')) {
                $this->db->where_in($field, (array)$values);
            }
        }
        return (int)$this->db->count_all_results();
    }

    private function _count_teacher_students($user_id)
    {
        if (!$this->db->table_exists('tutor_batch_students') || !$this->db->table_exists('tutor_batches')) {
            return 0;
        }
        $this->db->select('COUNT(DISTINCT bs.student_user_id) AS total', false)
            ->from('tutor_batch_students bs')
            ->join('tutor_batches b', 'b.id = bs.batch_id', 'inner')
            ->where('b.tutor_user_id', (int)$user_id);
        if ($this->db->field_exists('membership_status', 'tutor_batch_students')) {
            $this->db->where_in('bs.membership_status', ['active', 'completed']);
        }
        $row = $this->db->get()->row_array();
        return (int)($row['total'] ?? 0);
    }

    private function _count_teacher_exam_attempts($user_id)
    {
        if (!$this->db->table_exists('content_exam_attempts') || !$this->db->table_exists('content_exams')) {
            return 0;
        }
        $this->db->from('content_exam_attempts a')
            ->join('content_exams e', 'e.id = a.exam_id', 'inner')
            ->where('a.submitted_at IS NOT NULL', null, false);
        if ($this->db->field_exists('tutor_id', 'content_exams')) {
            $this->db->where('e.tutor_id', (int)$user_id);
        } elseif ($this->db->field_exists('created_by', 'content_exams')) {
            $this->db->where('e.created_by', (int)$user_id);
        }
        return (int)$this->db->count_all_results();
    }

    private function _count_teacher_sessions($user_id, $from_date)
    {
        if (!$this->db->table_exists('tutor_batch_sessions')) {
            return 0;
        }
        $this->db->from('tutor_batch_sessions');
        if ($this->db->field_exists('tutor_user_id', 'tutor_batch_sessions')) {
            $this->db->where('tutor_user_id', (int)$user_id);
        }
        if ($this->db->field_exists('session_date', 'tutor_batch_sessions')) {
            $this->db->where('session_date >=', $from_date);
        }
        if ($this->db->field_exists('session_status', 'tutor_batch_sessions')) {
            $this->db->where_not_in('session_status', ['cancelled', 'archived']);
        }
        return (int)$this->db->count_all_results();
    }

    public function courses()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        $page_data['selected_category_id']   = isset($_GET['category_id']) ? $_GET['category_id'] : "all";
        $page_data['selected_instructor_id'] = $this->session->userdata('user_id');
        $page_data['selected_price']         = isset($_GET['price']) ? $_GET['price'] : "all";
        $page_data['selected_status']        = isset($_GET['status']) ? $_GET['status'] : "all";
        $page_data['courses']                = $this->crud_model->filter_course_for_backend($page_data['selected_category_id'], $page_data['selected_instructor_id'], $page_data['selected_price'], $page_data['selected_status']);
        $page_data['course_manager_counts']  = $this->crud_model->get_course_manager_counts_for_instructor($page_data['selected_instructor_id'], $page_data['selected_category_id']);
        $page_data['page_name']              = 'courses-server-side';
        $page_data['categories']             = $this->crud_model->get_categories();
        $page_data['page_title']             = get_phrase('active_courses');
        $this->load->view('backend/index', $page_data);
    }

    // This function is responsible for loading the course data from server side for datatable SILENTLY
    public function get_courses()
    {
        $this->output->set_content_type('application/json');
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        $courses = array();
        // Filter portion
        $filter_data['selected_category_id']   = $this->input->post('selected_category_id');
        $filter_data['selected_instructor_id'] = $this->input->post('selected_instructor_id');
        $filter_data['selected_price']         = $this->input->post('selected_price');
        $filter_data['selected_status']        = $this->input->post('selected_status');

        // Server side processing portion
        $columns = array(
            0 => '#',
            1 => 'title',
            2 => 'category',
            3 => 'lesson_and_section',
            4 => 'enrolled_student',
            5 => 'status',
            6 => 'price',
            7 => 'actions',
            8 => 'course_id'
        );

        // Coming from databale itself. Limit is the visible number of data
        $limit = html_escape($this->input->post('length'));
        $start = html_escape($this->input->post('start'));
        $order = "";
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->lazyload->count_all_courses($filter_data);
        $totalFiltered = $totalData;

        // This block of code is handling the search event of datatable
        if (empty($this->input->post('search')['value'])) {
            $courses = $this->lazyload->courses($limit, $start, $order, $dir, $filter_data);
        } else {
            $search = $this->input->post('search')['value'];
            $courses =  $this->lazyload->course_search($limit, $start, $search, $order, $dir, $filter_data);
            $totalFiltered = $this->lazyload->course_search_count($search);
        }

        // Fetch the data and make it as JSON format and return it.
        $data = array();
        if (!empty($courses)) {
            foreach ($courses as $key => $row) {
                $instructor_details = $this->user_model->get_all_user($row->user_id)->row_array();
                $category_details = $this->crud_model->get_category_details_by_id($row->sub_category_id)->row_array();
                $category_name = (!empty($category_details) && isset($category_details['name'])) ? $category_details['name'] : 'Unmapped';
                $sections = $this->crud_model->get_section('course', $row->id);
                $lessons = $this->crud_model->get_lessons('course', $row->id);
                $enroll_history = $this->crud_model->enrol_history($row->id);

                $status_badge = "badge-success-lighten";
                if ($row->status == 'pending') {
                    $status_badge = "badge-danger-lighten";
                } elseif ($row->status == 'draft') {
                    $status_badge = "badge-dark-lighten";
                } elseif ($row->status == 'private') {
                    $status_badge = "badge-dark";
                }

                $price_badge = "badge-dark-lighten";
                $price = 0;
                if ($row->is_free_course == null) {
                    if ($row->discount_flag == 1) {
                        $price = currency($row->discounted_price);
                    } else {
                        $price = currency($row->price);
                    }
                } elseif ($row->is_free_course == 1) {
                    $price_badge = "badge-success-lighten";
                    $price = get_phrase('free');
                }

                $price_field = '<span class="badge ' . $price_badge . '">' . $price . '</span>';
                if ($row->expiry_period > 0) {
                    $price_field .= '<p class="text-12">' . $row->expiry_period . ' ' . get_phrase('Months') . '</p>';
                } else {
                    $price_field .= '<p class="text-12">' . get_phrase('Lifetime') . '</p>';
                }

                $view_course_on_frontend_url = site_url('home/course/' . rawurlencode(slugify($row->title)) . '/' . $row->id);
                $go_to_course_playing_page = site_url('home/lesson/' . rawurlencode(slugify($row->title)) . '/' . $row->id);
                $edit_this_course_url = site_url('user/course_form/course_edit/' . $row->id);
                $section_and_lesson_url = site_url('user/course_form/course_edit/' . $row->id);
                $academic_progress_url = site_url('user/course_form/course_edit/' . $row->id . '?tab=academic_progress');

                if ($row->status == 'active' || $row->status == 'pending') {
                    $course_status_changing_action = "confirm_modal('" . site_url('user/course_actions/draft/' . $row->id) . "')";
                    $course_status_changing_message = get_phrase('mark_as_drafted');
                } else {
                    $course_status_changing_action = "confirm_modal('" . site_url('user/course_actions/publish/' . $row->id) . "')";
                    $course_status_changing_message = get_phrase('publish_this_course');
                }

                $delete_course_url = "confirm_modal('" . site_url('user/course_actions/delete/' . $row->id) . "')";

                if ($row->course_type == 'general') {
                    $section_and_lesson_menu = '<li><a class="dropdown-item" href="' . $section_and_lesson_url . '">' . get_phrase("section_and_lesson") . '</a></li>';
                } else {
                    $section_and_lesson_menu = "";
                }

                $action = '
                <div class="dropright dropright">
                <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="mdi mdi-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="' . $view_course_on_frontend_url . '" target="_blank">' . get_phrase("view_course_on_frontend") . '</a></li>
                <li><a class="dropdown-item" href="' . $go_to_course_playing_page . '" target="_blank">' . get_phrase("go_to_course_playing_page") . '</a></li>
                <li><a class="dropdown-item" href="' . $academic_progress_url . '">' . get_phrase("Academic progress") . '</a></li>
                <li><a class="dropdown-item" href="' . $edit_this_course_url . '">' . get_phrase("edit_this_course") . '</a></li>
                ' . $section_and_lesson_menu . '
                <li><a class="dropdown-item" href="javascript:;" onclick="' . $course_status_changing_action . '">' . $course_status_changing_message . '</a></li>
                <li><a class="dropdown-item" href="javascript:;" onclick="' . $delete_course_url . '">' . get_phrase("delete") . '</a></li>
                </ul>
                </div>
                ';

                $nestedData['#'] = $key + 1;

                $instructor_names = "";
                if ($row->multi_instructor) {
                    $instructors = $this->user_model->get_multi_instructor_details_with_csv($row->user_id);
                    foreach ($instructors as $counterForThis => $instructor) {
                        $instructor_names .= $instructor['first_name'] . ' ' . $instructor['last_name'];
                        $instructor_names .= $counterForThis + 1 == count($instructors) ? '' : ', ';
                    }
                } else {
                    $instructor_names = $instructor_details['first_name'] . ' ' . $instructor_details['last_name'];
                }

                $nestedData['title'] = '<strong><a href="' . site_url('user/course_form/course_edit/' . $row->id) . '">' . $row->title . '</a></strong><br>
                <small class="text-muted">' . get_phrase('instructor') . ': <b>' . $instructor_names . '</b></small>';


                $nestedData['category'] = '<span class="badge badge-dark-lighten">' . html_escape($category_name) . '</span>';

                if ($row->course_type == 'scorm') {
                    $nestedData['lesson_and_section'] = '<span class="badge badge-info-lighten">' . get_phrase('scorm_course') . '</span>';
                } elseif ($row->course_type == 'h5p') {
                    $nestedData['lesson_and_section'] = '<span class="badge badge-info-lighten">' . get_phrase('h5p_course') . '</span>';
                } elseif ($row->course_type == 'general') {
                    $nestedData['lesson_and_section'] = '
                    <small class="text-muted"><b>' . get_phrase('total_section') . '</b>: ' . $sections->num_rows() . '</small><br>
                    <small class="text-muted"><b>' . get_phrase('total_lesson') . '</b>: ' . $lessons->num_rows() . '</small>';
                }

                $nestedData['enrolled_student'] = '<small class="text-muted"><b>' . get_phrase('total_enrolment') . '</b>: ' . $enroll_history->num_rows() . '</small>';


                $nestedData['status'] = '<span class="badge ' . $status_badge . '">' . get_phrase($row->status) . '</span>';

                $nestedData['price'] = $price_field;

                $nestedData['actions'] = $action;

                $nestedData['course_id'] = $row->id;

                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);
    }

    public function course_actions($param1 = "", $param2 = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($param1 == "add") {
            $course_id = $this->crud_model->add_course();
            redirect(site_url('user/course_form/course_edit/' . $course_id), 'refresh');
        } elseif ($param1 == "edit") {
            $this->is_the_course_belongs_to_current_instructor($param2);
            $this->crud_model->update_course($param2);

            // CHECK IF LIVE CLASS ADDON EXISTS, ADD OR UPDATE IT TO ADDON MODEL
            if (addon_status('live-class')) {
                $this->load->model('addons/Liveclass_model', 'liveclass_model');
                $this->liveclass_model->update_live_class($param2);
            }

            // CHECK IF JITSI LIVE CLASS ADDON EXISTS, ADD OR UPDATE IT TO ADDON MODEL
            if (addon_status('jitsi-live-class')) {
                $this->load->model('addons/jitsi_liveclass_model', 'jitsi_liveclass_model');
                $this->jitsi_liveclass_model->update_live_class($param2);
            }

            redirect(site_url('user/course_form/course_edit/' . $param2));
        } elseif ($param1 == 'add_shortcut') {
            echo $this->crud_model->add_shortcut_course();
        } elseif ($param1 == 'delete') {
            $this->is_the_course_belongs_to_current_instructor($param2);
            $this->crud_model->delete_course($param2);
            redirect(site_url('user/courses'), 'refresh');
        } elseif ($param1 == 'draft') {
            $this->is_the_course_belongs_to_current_instructor($param2);
            $this->crud_model->change_course_status('draft', $param2);
            redirect(site_url('user/courses'), 'refresh');
        } elseif ($param1 == 'publish') {
            $this->is_the_course_belongs_to_current_instructor($param2);
            $this->crud_model->change_course_status('pending', $param2);
            redirect(site_url('user/courses'), 'refresh');
        }
    }

    public function course_form($param1 = "", $param2 = "")
    {

        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($param1 == 'add_course') {
            $page_data['languages'] = $this->crud_model->get_all_languages();
            $page_data['categories'] = $this->crud_model->get_categories();
            $page_data['page_name'] = 'course_add';
            $page_data['page_title'] = get_phrase('add_course');
            $this->load->view('backend/index', $page_data);
        } elseif ($param1 == 'add_course_shortcut') {
            $page_data['languages'] = $this->crud_model->get_all_languages();
            $page_data['categories'] = $this->crud_model->get_categories();
            $this->load->view('backend/user/course_add_shortcut', $page_data);
        } elseif ($param1 == 'course_edit') {
            $this->is_the_course_belongs_to_current_instructor($param2);
            $page_data['page_name'] = 'course_edit';
            $page_data['course_id'] =  $param2;
            $page_data['page_title'] = get_phrase('edit_course');
            $page_data['languages'] = $this->crud_model->get_all_languages();
            $page_data['categories'] = $this->crud_model->get_categories();
            $this->load->view('backend/index', $page_data);
        }
    }

    public function payout_settings($param1 = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if (strtoupper((string)$this->input->method(true)) === 'POST') {
            $existing_user = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
            $payment_keys = json_decode($existing_user['payment_keys'] ?? '', true);
            if (!is_array($payment_keys)) {
                $payment_keys = [];
            }

            $payment_keys['payout_details'] = [
                'account_holder_name' => html_escape(trim((string)$this->input->post('account_holder_name', true))),
                'bank_name' => html_escape(trim((string)$this->input->post('bank_name', true))),
                'account_number' => html_escape(trim((string)$this->input->post('account_number', true))),
                'ifsc_code' => html_escape(strtoupper(trim((string)$this->input->post('ifsc_code', true)))),
                'upi_id' => html_escape(trim((string)$this->input->post('upi_id', true))),
                'payout_notes' => html_escape(trim((string)$this->input->post('payout_notes', true))),
            ];

            $data['payment_keys'] = json_encode($payment_keys);
            $data['last_modified'] = time();
            $this->db->where('id', $this->session->userdata('user_id'));
            $this->db->update('users', $data);
            $this->session->set_flashdata('flash_message', get_phrase('payout_settings_has_been_updated'));
            redirect(site_url('user/payout_settings'), 'refresh');
        }

        $page_data['page_name'] = 'payment_settings';
        $page_data['page_title'] = get_phrase('payout_settings');
        $this->load->view('backend/index', $page_data);
    }

    public function sales_report($param1 = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($param1 != "") {
            $date_range                   = $this->input->get('date_range');
            $date_range                   = explode(" - ", $date_range);
            $page_data['timestamp_start'] = strtotime($date_range[0] . ' 00:00:00');
            $page_data['timestamp_end']   = strtotime($date_range[1] . ' 23:59:59');
        } else {
            $page_data['timestamp_start'] = strtotime(date("m/01/Y 00:00:00"));
            $page_data['timestamp_end']   = strtotime(date("m/t/Y 23:59:59"));
        }

        $page_data['payment_history'] = $this->crud_model->get_instructor_revenue($this->session->userdata('user_id'), $page_data['timestamp_start'], $page_data['timestamp_end']);
        $page_data['page_name'] = 'sales_report';
        $page_data['page_title'] = get_phrase('sales_report');
        $this->load->view('backend/index', $page_data);
    }

    public function preview($course_id = '')
    {
        if ($this->session->userdata('user_login') != 1)
            redirect(site_url('login'), 'refresh');

        $this->is_the_course_belongs_to_current_instructor($course_id);
        if ($course_id > 0) {
            $courses = $this->crud_model->get_course_by_id($course_id);
            if ($courses->num_rows() > 0) {
                $course_details = $courses->row_array();
                redirect(site_url('home/lesson/' . rawurlencode(slugify($course_details['title'])) . '/' . $course_details['id']), 'refresh');
            }
        }
        redirect(site_url('user/courses'), 'refresh');
    }

    public function sections($param1 = "", $param2 = "", $param3 = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($param2 == 'add') {
            $this->is_the_course_belongs_to_current_instructor($param1);
            $this->crud_model->add_section($param1);
            $this->session->set_flashdata('flash_message', get_phrase('section_has_been_added_successfully'));
        } elseif ($param2 == 'edit') {
            $this->is_the_course_belongs_to_current_instructor($param1, $param3, 'section');
            $this->crud_model->edit_section($param3);
            $this->session->set_flashdata('flash_message', get_phrase('section_has_been_updated_successfully'));
        } elseif ($param2 == 'delete') {
            $this->is_the_course_belongs_to_current_instructor($param1, $param3, 'section');
            $this->crud_model->delete_section($param1, $param3);
            $this->session->set_flashdata('flash_message', get_phrase('section_has_been_deleted_successfully'));
        }
        redirect(site_url('user/course_form/course_edit/' . $param1));
    }

    public function lessons($course_id = "", $param1 = "", $param2 = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        if ($param1 == 'add') {
            $valid_user = $this->is_the_course_belongs_to_current_instructor($course_id, null, null, true);
            if ($valid_user > 0) {
                $response = $this->crud_model->add_lesson();
            } else {
                $response = json_encode(['error' => get_phrase('you_do_not_have_right_to_access_this_course')]);
            }
            echo $response;
            return;
        } elseif ($param1 == 'edit') {
            $valid_user = +$this->is_the_course_belongs_to_current_instructor($course_id, $param2, 'lesson', true);

            if ($valid_user > 0) {
                $response = $this->crud_model->edit_lesson($param2);
            } else {
                $response = json_encode(['error' => get_phrase('you_do_not_have_right_to_access_this_course')]);
            }
            echo $response;
            return;
        } elseif ($param1 == 'delete') {
            $this->is_the_course_belongs_to_current_instructor($course_id, $param2, 'lesson');
            $this->crud_model->delete_lesson($param2);
            $this->session->set_flashdata('flash_message', get_phrase('lesson_has_been_deleted_successfully'));
            redirect('user/course_form/course_edit/' . $course_id);
        } elseif ($param1 == 'filter') {
            redirect('user/lessons/' . $this->input->post('course_id'));
        }
        $page_data['page_name'] = 'lessons';
        $page_data['lessons'] = $this->crud_model->get_lessons('course', $course_id);
        $page_data['course_id'] = $course_id;
        $page_data['page_title'] = get_phrase('lessons');
        $this->load->view('backend/index', $page_data);
    }

    // Manage Quizes
    public function quizes($course_id = "", $action = "", $quiz_id = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($action == 'add') {
            $this->is_the_course_belongs_to_current_instructor($course_id);
            $this->crud_model->add_quiz($course_id);
            $this->session->set_flashdata('flash_message', get_phrase('quiz_has_been_added_successfully'));
        } elseif ($action == 'edit') {
            $this->is_the_course_belongs_to_current_instructor($course_id, $quiz_id, 'quize');
            $this->crud_model->edit_quiz($quiz_id);
            $this->session->set_flashdata('flash_message', get_phrase('quiz_has_been_updated_successfully'));
        } elseif ($action == 'delete') {
            $this->is_the_course_belongs_to_current_instructor($course_id, $quiz_id, 'quize');
            $this->crud_model->delete_lesson($quiz_id);
            $this->session->set_flashdata('flash_message', get_phrase('quiz_has_been_deleted_successfully'));
        }
        redirect(site_url('user/course_form/course_edit/' . $course_id));
    }

    // Manage Quize Questions
    public function quiz_questions($quiz_id = "", $action = "", $question_id = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        $quiz_details = $this->crud_model->get_lessons('lesson', $quiz_id)->row_array();

        if ($action == 'add' || $action == 'edit') {
            echo $this->crud_model->manage_quiz_questions($quiz_id, $question_id, $action);
        } elseif ($action == 'delete') {
            if ($this->db->get_where('question', array('id' => $question_id, 'quiz_id' => $quiz_id))->num_rows() <= 0) {
                $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_quiz_question'));
                redirect(site_url('user/courses'), 'refresh');
            }

            $response = $this->crud_model->delete_quiz_question($question_id);
            $this->session->set_flashdata('flash_message', get_phrase('question_has_been_deleted'));
            redirect(site_url('user/course_form/course_edit/' . $quiz_details['course_id']), 'refresh');
        }
    }

    /******MANAGE OWN PROFILE AND CHANGE PASSWORD INSIDE USER DASHBOARD***/
    function manage_profile($param1 = '', $param2 = '')
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $user_id = (int) $this->session->userdata('user_id');

        if ($param1 == 'update_profile_info') {
            if ((int) $param2 !== $user_id) {
                redirect(site_url('user/manage_profile'), 'refresh');
            }
            $this->user_model->edit_user($user_id);
            redirect(site_url('user/manage_profile'), 'refresh');
        }

        if ($param1 == 'change_password') {
            if ((int) $param2 !== $user_id) {
                redirect(site_url('user/manage_profile'), 'refresh');
            }
            $this->user_model->change_password($user_id);
            redirect(site_url('user/manage_profile'), 'refresh');
        }

        $page_data['page_name']  = 'manage_profile';
        $page_data['page_title'] = get_phrase('manage_profile');
        $page_data['edit_data']  = $this->db->get_where('users', array('id' => $user_id))->result_array();
        $this->load->view('backend/index', $page_data);
    }

    function message($param1 = 'message_home', $param2 = '')
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($param1 == 'send_new') {
            if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
            $message_thread_code = $this->crud_model->send_new_private_message();
            $this->session->set_flashdata('flash_message', get_phrase('message_sent'));
            redirect(site_url('user/message/message_read/' . $message_thread_code), 'refresh');
        }

        if ($param1 == 'send_reply') {
            if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
            $sent = $this->crud_model->send_reply_message($param2);
            $this->session->set_flashdata($sent ? 'flash_message' : 'error_message', $sent ? get_phrase('message_sent') : 'Message thread not found or access denied.');
            redirect(site_url('user/message/message_read/' . $param2), 'refresh');
        }

        if ($param1 == 'message_read') {
            if (!$this->crud_model->can_access_message_thread($param2, (int)$this->session->userdata('user_id'))) {
                show_error('Message thread not found or access denied.', 403);
                return;
            }
            $page_data['current_message_thread_code'] = $param2;
            $this->crud_model->mark_thread_messages_read($param2);
        }

        $page_data['message_inner_page_name'] = $param1;
        $page_data['page_name'] = 'message';
        $page_data['page_title'] = get_phrase('private_messaging');
        $this->load->view('backend/index', $page_data);
    }

    function invoice($payment_id = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        $page_data['page_name'] = 'invoice';
        $page_data['payment_details'] = $this->crud_model->get_payment_details_by_id($payment_id);
        $page_data['page_title'] = get_phrase('invoice');
        $this->load->view('backend/index', $page_data);
    }


    function become_an_instructor()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $applications = $this->user_model->get_applications($this->session->userdata('user_id'), 'user');
        if ($applications->num_rows() == 0) :
            redirect('home/become_an_instructor', 'refresh');
        endif;

        // CHEKING IF A FORM HAS BEEN SUBMITTED FOR REGISTERING AN INSTRUCTOR
        if (isset($_POST) && !empty($_POST)) {
            $this->user_model->post_instructor_application();
        }

        // CHECK USER AVAILABILITY
        $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'));
        if ($user_details->num_rows() > 0) {
            $page_data['user_details'] = $user_details->row_array();
        } else {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            $this->load->view('backend/index', $page_data);
        }
        $page_data['page_name'] = 'become_an_instructor';
        $page_data['page_title'] = get_phrase('become_an_instructor');
        $this->load->view('backend/index', $page_data);
    }


    // PAYOUT REPORT
    public function payout_report()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $page_data['page_name'] = 'payout_report';
        $page_data['page_title'] = get_phrase('payout_report');

        $page_data['payouts'] = $this->crud_model->get_payouts($this->session->userdata('user_id'), 'user');
        $page_data['total_pending_amount'] = $this->crud_model->get_total_pending_amount($this->session->userdata('user_id'));
        $page_data['total_payout_amount'] = $this->crud_model->get_total_payout_amount($this->session->userdata('user_id'));
        $page_data['requested_withdrawal_amount'] = $this->crud_model->get_requested_withdrawal_amount($this->session->userdata('user_id'));

        if (addon_status('ebook')) {
            $this->db->select_sum('instructor_revenue');
            $this->db->where('ebook.user_id', $this->session->userdata('user_id'));
            $this->db->where('ebook_payment.instructor_payment_status', 0);
            $this->db->from('ebook_payment');
            $this->db->join('ebook', 'ebook_payment.ebook_id = ebook.ebook_id');
            $ebook_total_pending_amount = $this->db->get()->row('instructor_revenue');

            $page_data['total_pending_amount'] = $page_data['total_pending_amount'] + $ebook_total_pending_amount;
        }

        if (addon_status('tutor_booking')) {
            $this->db->select_sum('instructor_revenue');
            $this->db->where('tutor_id', $this->session->userdata('user_id'));
            $this->db->from('tutor_payment');
            $tutor_total_pending_amount = $this->db->get()->row('instructor_revenue');

            $page_data['total_pending_amount'] = $page_data['total_pending_amount'] + $tutor_total_pending_amount;
        }

        $this->load->view('backend/index', $page_data);
    }

    // HANDLED WITHDRAWAL REQUESTS
    public function withdrawal($action = "")
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if ($action == 'request') {
            $this->crud_model->add_withdrawal_request();
        }

        if ($action == 'delete') {
            $this->crud_model->delete_withdrawal_request();
        }

        redirect(site_url('user/payout_report'), 'refresh');
    }
    // Ajax Portion
    public function ajax_get_video_details()
    {
        $video_details = $this->video_model->getVideoDetails($_POST['video_url']);
        if (is_array($video_details)) {
            echo $video_details['duration'];
        }
    }

    // AJAX PORTION
    // this function is responsible for managing multiple choice question
    function quiz_fields_type_wize()
    {
        $page_data['question_type'] = $this->input->post('question_type');
        $this->load->view('backend/user/quiz_fields_type_wize', $page_data);
    }

    // This function checks if this course belongs to current logged in instructor
    function is_the_course_belongs_to_current_instructor($course_id, $id = null, $type = null, $is_ajax_call = null)
    {
        $is_valid = 1;
        $course_details = $this->crud_model->get_course_by_id($course_id);

        if ($course_details->num_rows() > 0) {
            $course_details = $course_details->row_array();
            if ($course_details['multi_instructor']) {
                $instructor_ids = explode(',', $course_details['user_id']);
                if (!in_array($this->session->userdata('user_id'), $instructor_ids)) {
                    $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_course'));
                    $is_valid = 0;

                    if ($is_ajax_call == null) {
                        redirect(site_url('user/courses'), 'refresh');
                    }
                }
            } else {
                if ($course_details['user_id'] != $this->session->userdata('user_id')) {
                    $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_course'));
                    $is_valid = 0;
                    if ($is_ajax_call == null) {
                        redirect(site_url('user/courses'), 'refresh');
                    }
                }
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('course_not_found'));
            $is_valid = 0;
            if ($is_ajax_call == null) {
                redirect(site_url('user/courses'), 'refresh');
            }
        }


        if ($type == 'section' && $this->db->get_where('section', array('id' => $id, 'course_id' => $course_id))->num_rows() <= 0) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_section'));
            $is_valid = 0;
            if ($is_ajax_call == null) {
                redirect(site_url('user/courses'), 'refresh');
            }
        }
        if ($type == 'lesson' && $this->db->get_where('lesson', array('id' => $id, 'course_id' => $course_id))->num_rows() <= 0) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_lesson'));
            $is_valid = 0;
            if ($is_ajax_call == null) {
                redirect(site_url('user/courses'), 'refresh');
            }
        }
        if ($type == 'quize' && $this->db->get_where('lesson', array('id' => $id, 'course_id' => $course_id))->num_rows() <= 0) {
            $this->session->set_flashdata('error_message', get_phrase('you_do_not_have_right_to_access_this_quize'));
            $is_valid = 0;
            if ($is_ajax_call == null) {
                redirect(site_url('user/courses'), 'refresh');
            }
        }

        return $is_valid;
    }

    public function ajax_sort_section()
    {
        $section_json = $this->input->post('itemJSON');
        $this->crud_model->sort_section($section_json);
    }
    public function ajax_sort_lesson()
    {
        $lesson_json = $this->input->post('itemJSON');
        $this->crud_model->sort_lesson($lesson_json);
    }
    public function ajax_sort_question()
    {
        $question_json = $this->input->post('itemJSON');
        $this->crud_model->sort_question($question_json);
    }



    // REMOVING INSTRUCTOR FROM COURSE
    public function remove_an_instructor($course_id, $instructor_id)
    {
        $course_details = $this->crud_model->get_course_by_id($course_id)->row_array();

        if ($course_details['creator'] == $instructor_id) {
            $this->session->set_flashdata('error_message', get_phrase('course_creator_can_be_removed'));
            redirect('admin/course_form/course_edit/' . $course_id);
        }

        if ($course_details['multi_instructor']) {
            $instructor_ids = explode(',', $course_details['user_id']);

            if (in_array($instructor_id, $instructor_ids) && in_array($this->session->userdata('user_id'), $instructor_ids)) {
                if (count($instructor_ids) > 1) {
                    if (($key = array_search($instructor_id, $instructor_ids)) !== false) {
                        unset($instructor_ids[$key]);

                        $data['user_id'] = implode(",", $instructor_ids);
                        $this->db->where('id', $course_id);
                        $this->db->update('course', $data);

                        $this->session->set_flashdata('flash_message', get_phrase('instructor_has_been_removed'));
                        if ($this->session->userdata('user_id') == $instructor_id) {
                            redirect('user/courses/');
                        } else {
                            redirect('user/course_form/course_edit/' . $course_id);
                        }
                    }
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('a_course_should_have_at_least_one_instructor'));
                    redirect('user/course_form/course_edit/' . $course_id);
                }
            } else {
                $this->session->set_flashdata('error_message', get_phrase('invalid_instructor_id'));
                redirect('user/course_form/course_edit/' . $course_id);
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('a_course_should_have_at_least_one_instructor'));
            redirect('user/course_form/course_edit/' . $course_id);
        }
    }


    //Blog start
    function add_blog()
    {
        $page_data['page_title'] = get_phrase('add_blog');
        $page_data['page_name'] = 'blog_add';
        $this->load->view('backend/index', $page_data);
    }

    function edit_blog($blog_id = "")
    {
        $page_data['blog'] = $this->crud_model->get_blogs($blog_id)->row_array();
        $page_data['page_title'] = get_phrase('edit_blog');
        $page_data['page_name'] = 'blog_edit';
        $this->load->view('backend/index', $page_data);
    }

    function blog($param1 = "", $param2 = "")
    {
        if (!get_frontend_settings('instructors_blog_permission')) {
            $this->session->set_flashdata('error_message', get_phrase('access_to_the_blog_section_denied'));
            redirect(site_url('user/dashboard'), 'refresh');
        }


        if ($param1 == 'add') {
            $this->crud_model->add_blog();
            $this->session->set_flashdata('flash_message', get_phrase('blog_added_successfully'));
            redirect(site_url('user/pending_blog'), 'refresh');
        } elseif ($param1 == 'update') {
            if ($this->check_validity($param2)) {
                $this->crud_model->update_blog($param2);
            }
            $this->session->set_flashdata('flash_message', get_phrase('blog_updated_successfully'));
            redirect(site_url('user/blog'), 'refresh');
        } elseif ($param1 == 'status') {
            if ($this->check_validity($param2)) {
                $this->crud_model->update_blog_status($param2);
            }
            $this->session->set_flashdata('flash_message', get_phrase('blog_status_has_been_updated'));
            redirect(site_url('user/blog'), 'refresh');
        } elseif ($param1 == 'delete') {
            if ($this->check_validity($param2)) {
                $this->crud_model->blog_delete($param2);
            }
            $this->session->set_flashdata('flash_message', get_phrase('blog_deleted_successfully'));
            redirect(site_url('user/blog'), 'refresh');
        }
        $page_data['blogs'] = $this->crud_model->get_blogs_by_user_id($this->session->userdata('user_id'));
        $page_data['page_title'] = get_phrase('blog');
        $page_data['page_name'] = 'blog';
        $this->load->view('backend/index', $page_data);
    }

    function pending_blog($param1 = "", $param2 = "")
    {
        if ($param1 == 'delete') {
            if ($this->check_validity($param2)) {
                $this->crud_model->blog_delete($param2);
            }
            $this->session->set_flashdata('flash_message', get_phrase('blog_deleted_successfully'));
            redirect(site_url('user/pending_blog'), 'refresh');
        }
        $page_data['pending_blogs'] = $this->crud_model->get_instructors_pending_blog($this->session->userdata('user_id'));
        $page_data['page_title'] = get_phrase('pending_blog');
        $page_data['page_name'] = 'pending_blog';
        $this->load->view('backend/index', $page_data);
    }

    function check_validity($blog_id = "")
    {
        $this->db->where('user_id', $this->session->userdata('user_id'));
        $this->db->where('blog_id', $blog_id);
        $query = $this->db->get('blogs');
        if ($query->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    //End Blog
	
	private function _require_tutor_docs_access()
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if (!$this->session->userdata('is_instructor')) {
			$this->session->set_flashdata('error_message', 'Only tutors can access Content (Docs).');
			redirect(site_url('user/dashboard'), 'refresh');
		}
	}

	private function _deny_tutor_exam_authoring()
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
			return;
		}
		$this->session->set_flashdata('error_message', 'Exam and question publishing is managed by administrators only.');
		redirect(site_url('user/dashboard'), 'refresh');
	}

	private function _ckeditor_upload_response($funcNum, $url = '', $message = '')
	{
		$funcNum = (int)$funcNum;
		$url = json_encode((string)$url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$message = json_encode((string)$message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		echo "<script>window.parent.CKEDITOR.tools.callFunction($funcNum, $url, $message);</script>";
		exit;
	}

	private function _content_nodes_url_for_node($node_id = 0, $fallback_section = 'book')
	{
		$section = ((string)$fallback_section === 'article') ? 'article' : 'book';
		$node_id = (int)$node_id;

		if ($node_id > 0) {
			$row = $this->db->select('content_type, root_key')
				->get_where('content_nodes', [
					'node_id' => $node_id,
					'created_by' => (int)$this->session->userdata('user_id'),
				], 1)
				->row_array();

			if (!empty($row)) {
				$content_type = strtolower((string)($row['content_type'] ?? ''));
				$root_key = strtolower((string)($row['root_key'] ?? ''));
				$section = ($content_type === 'article' || strpos($root_key, 'article_') === 0) ? 'article' : 'book';
			}
		}

		return site_url('user/content_nodes?section=' . $section);
	}

	public function content_docs_readme()
	{
		$this->_require_tutor_docs_access();
		$page_data['page_name']  = 'content_docs_readme';
		$page_data['page_title'] = 'Content (Docs) Documentation';
		$this->load->view('backend/index', $page_data);
	}

	public function content_nodes($param1 = "", $param2 = "")
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');

		$user_id = (int) $this->session->userdata('user_id');
		$user_role = strtolower((string)$this->session->userdata('role')) ?: 'tutor';

		$this->content_docs_model->ensure_root_nodes_exist($user_id);

		if ($param1 === 'add') {
			if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
			$res = $this->content_docs_model->add_node($user_id, $user_role, $this->input->post('parent_id'), $this->input->post('title'));
			$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
			redirect(site_url('user/content_nodes'), 'refresh');
		}

		if ($param1 === 'update') {
			if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
			$res = $this->content_docs_model->update_node_title($user_id, $user_role, (int)$this->input->post('node_id'), (string)$this->input->post('title'));
			$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
			redirect(site_url('user/content_nodes'), 'refresh');
		}

		if ($param1 === 'delete') {
			if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
			$res = $this->content_docs_model->delete_node($user_id, $user_role, (int)$param2);
			$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
			redirect(site_url('user/content_nodes'), 'refresh');
		}

		$page_data['page_name']  = 'content_nodes';
		$page_data['page_title'] = get_phrase('nodes_(tree)');
		$this->load->view('backend/index', $page_data);
	}

	public function content_pages($param1 = "", $param2 = "")
	{
		$this->_require_tutor_docs_access();
		$page_data['page_name']  = 'content_pages';
		$page_data['page_title'] = get_phrase('pages');
		$this->load->view('backend/index', $page_data);
	}

	public function content_page_save()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');

		$node_id = (int)$this->input->post('node_id');
		$user_id = (int)$this->session->userdata('user_id');
		$html = $this->input->post('html', false);
		$redirect_url = $this->_content_nodes_url_for_node($node_id, 'book');

		$meta = [
			'meta_title'       => $this->input->post('meta_title'),
			'meta_description' => $this->input->post('meta_description'),
			'meta_keywords'    => $this->input->post('meta_keywords'),
			'canonical_url'    => $this->input->post('canonical_url'),
			'content_category' => $this->input->post('content_category'),
			'content_tags'     => $this->input->post('content_tags'),
			'og_image'         => null,
		];

		if (!empty($_FILES['og_image']) && !empty($_FILES['og_image']['name'])) {
			$upload_dir = FCPATH . 'uploads/blog/';
			if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0755, true); }
			$config = [
				'upload_path'   => $upload_dir,
				'allowed_types' => 'jpg|jpeg|png|webp|gif',
				'max_size'      => 2048,
				'encrypt_name'  => true,
			];
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('og_image')) {
				$this->session->set_flashdata('error_message', 'OG Image upload failed: ' . $this->upload->display_errors('', ''));
				redirect($redirect_url, 'refresh');
				return;
			}
			$up = $this->upload->data();
			$meta['og_image'] = 'uploads/blog/' . $up['file_name'];
		}

		// Save and Save Draft both keep the item as Draft. Book/Article Publish buttons submit to admin review.
		$res = $this->content_docs_model->save_page_draft($node_id, $html, $meta, $user_id, 'draft');
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message'] ?? 'Unable to save content');
		redirect($redirect_url, 'refresh');
	}

	public function content_page_get($node_id = 0)
	{
		try {
			$this->_require_tutor_docs_access();
			$this->load->model('Content_docs_model', 'content_docs_model');
			$row = $this->content_docs_model->get_owned_page((int)$node_id, (int)$this->session->userdata('user_id'));
			return $this->output->set_content_type('application/json')
				->set_output(json_encode(['ok' => !empty($row), 'data' => $row ?: null, 'message' => $row ? '' : 'Content not found or access denied.']));
		} catch (Throwable $e) {
			log_message('error', 'Tutor content_page_get failed node_id=' . (int)$node_id . ' user_id=' . (int)$this->session->userdata('user_id') . ' error=' . $e->getMessage());
			return $this->output->set_status_header(500)
				->set_content_type('application/json')
				->set_output(json_encode(['ok' => false, 'data' => null, 'message' => 'Unable to load content editor data.']));
		}
	}

	public function content_page_upload()
	{
		$this->_require_tutor_docs_access();
		$funcNum = $this->input->get('CKEditorFuncNum');
		if (!$funcNum) $funcNum = 1;
		$upload_path = FCPATH . 'uploads/content_pages/';
		if (!isset($_FILES['upload'])) {
			return $this->_ckeditor_upload_response($funcNum, '', 'No file selected.');
		}
		$this->load->library('secure_upload');
		$result = $this->secure_upload->store($_FILES['upload'], $upload_path, [
			'extensions' => ['jpg','jpeg','png','gif','webp'],
			'max_bytes' => 2 * 1024 * 1024,
			'actor_user_id' => (int)$this->session->userdata('user_id'),
			'owner_user_id' => (int)$this->session->userdata('user_id'),
			'entity_type' => 'content_page',
		]);
		if (empty($result['ok'])) return $this->_ckeditor_upload_response($funcNum, '', $result['message']);
		return $this->_ckeditor_upload_response($funcNum, base_url('uploads/content_pages/' . $result['file_name']), 'Upload successful');
	}

	public function content_page_pdf_upload()
	{
		$this->_require_tutor_docs_access();
		$upload_path = FCPATH . 'uploads/content_pages_pdfs/';
		if (!isset($_FILES['pdf'])) {
			return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'message'=>'No PDF uploaded.']));
		}
		$this->load->library('secure_upload');
		$result = $this->secure_upload->store($_FILES['pdf'], $upload_path, [
			'extensions' => ['pdf'],
			'max_bytes' => 10 * 1024 * 1024,
			'actor_user_id' => (int)$this->session->userdata('user_id'),
			'owner_user_id' => (int)$this->session->userdata('user_id'),
			'entity_type' => 'content_page_pdf',
		]);
		if (empty($result['ok'])) return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'message'=>$result['message']]));
		return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'url'=>base_url('uploads/content_pages_pdfs/' . $result['file_name']),'file'=>$result['file_name']]));
	}

	public function content_page_document_upload()
	{
		$this->_require_tutor_docs_access();
		$upload_path = FCPATH . 'uploads/content_pages_docs/';
		if (!isset($_FILES['document'])) {
			return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'message'=>'No document uploaded.']));
		}
		$this->load->library('secure_upload');
		$result = $this->secure_upload->store($_FILES['document'], $upload_path, [
			'extensions' => ['pdf', 'doc', 'docx'],
			'max_bytes' => 10 * 1024 * 1024,
			'actor_user_id' => (int)$this->session->userdata('user_id'),
			'owner_user_id' => (int)$this->session->userdata('user_id'),
			'entity_type' => 'content_page_document',
		]);
		if (empty($result['ok'])) {
			return $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'message'=>$result['message']]));
		}
		return $this->output->set_content_type('application/json')->set_output(json_encode([
			'ok' => true,
			'url' => base_url('uploads/content_pages_docs/' . $result['file_name']),
			'file' => $result['file_name'],
			'mime' => $result['mime'] ?? '',
		]));
	}


	public function update_content_node_order()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$parent_id = (int)$this->input->post('parent_id');
		$ordered_ids = $this->input->post('ordered_ids');
		if (is_string($ordered_ids)) $ordered_ids = json_decode($ordered_ids, true);
		$res = $this->content_docs_model->update_node_order(
			$parent_id,
			is_array($ordered_ids) ? $ordered_ids : [],
			(int)$this->session->userdata('user_id'),
			'tutor'
		);
		return $this->output->set_content_type('application/json')->set_output(json_encode($res));
	}


	public function content_book_create()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->create_book_wizard($user_id, $this->input->post('book_title'), $this->input->post('chapter_title'), $this->input->post('page_title'));
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes'), 'refresh');
	}


	public function content_article_create()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->create_article($user_id, $this->input->post('article_title'));
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes?section=article'), 'refresh');
	}

	public function content_book_add_child()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->add_book_child($user_id, (int)$this->input->post('parent_id'), $this->input->post('title'), $this->input->post('type') ?: 'page');
		if ($this->input->is_ajax_request()) {
			return $this->output->set_content_type('application/json')->set_output(json_encode($res));
		}
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes'), 'refresh');
	}

	public function content_page_autosave()
	{
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$user_id = (int)$this->session->userdata('user_id');
		$meta = [
			'meta_title' => $this->input->post('meta_title'),
			'meta_description' => $this->input->post('meta_description'),
			'meta_keywords' => $this->input->post('meta_keywords'),
			'canonical_url' => $this->input->post('canonical_url'),
			'content_category' => $this->input->post('content_category'),
			'content_tags' => $this->input->post('content_tags'),
		];
		$res = $this->content_docs_model->save_page_draft((int)$this->input->post('node_id'), $this->input->post('html', false), $meta, $user_id, 'draft');
		return $this->output->set_content_type('application/json')->set_output(json_encode($res));
	}

	public function content_page_duplicate($node_id = 0)
	{
		if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->duplicate_page_node($user_id, (int)$node_id);
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes'), 'refresh');
	}


	public function content_article_submit($article_id = 0)
	{
		if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$this->load->model('email_model');
		$this->load->model('Moderation_model', 'moderation');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->submit_article_for_review($user_id, (int)$article_id);
		if (!empty($res['ok'])) {
			$this->moderation->record('article', (int)$article_id, 'submit', 'draft', 'in_review', '', $user_id, $user_id);
			$admins = $this->db->get_where('users', ['role_id' => 1])->result_array();
			foreach ($admins as $admin) {
				$this->email_model->notify('content_page_approval_request', (int)$admin['id'], 'Article approval request', 'A tutor has submitted an article for approval.', $user_id);
			}
		}
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes?section=article'), 'refresh');
	}

	public function content_book_submit($book_id = 0)
	{
		if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
		$this->_require_tutor_docs_access();
		$this->load->model('Content_docs_model', 'content_docs_model');
		$this->load->model('email_model');
		$this->load->model('Moderation_model', 'moderation');
		$user_id = (int)$this->session->userdata('user_id');
		$res = $this->content_docs_model->submit_book_for_review($user_id, (int)$book_id);
		if (!empty($res['ok'])) {
			$this->moderation->record('book', (int)$book_id, 'submit', 'draft', 'in_review', '', $user_id, $user_id);
			$admins = $this->db->get_where('users', ['role_id' => 1])->result_array();
			foreach ($admins as $admin) {
				$this->email_model->notify('content_page_approval_request', (int)$admin['id'], 'Book approval request', 'A tutor has submitted a book for approval.', $user_id);
			}
		}
		$this->session->set_flashdata(!empty($res['ok']) ? 'flash_message' : 'error_message', $res['message']);
		redirect(site_url('user/content_nodes'), 'refresh');
	}


    public function content_public_exams()
    {
        $this->_require_tutor_docs_access();
        $this->load->model('Exam_model', 'exam_model');

        $user_id = (int)$this->session->userdata('user_id');
        $all_exams = $this->exam_model->get_all_public_exams_for_admin($user_id, 0);
        $exams = array_values(array_filter($all_exams, function($exam) use ($user_id) {
            return (int)($exam['tutor_id'] ?? 0) === $user_id || (int)($exam['created_by'] ?? 0) === $user_id;
        }));

        $summary = array('total_exams' => count($exams), 'published_exams' => 0, 'total_attempts' => 0);
        foreach ($exams as $exam) {
            if (($exam['status'] ?? '') === 'published') $summary['published_exams']++;
            $summary['total_attempts'] += (int)($exam['attempt_count'] ?? 0);
        }

        $page_data['page_name'] = 'content_public_exams';
        $page_data['page_title'] = 'Exam Pattern Builder';
        $page_data['exams'] = $exams;
        $page_data['summary'] = $summary;
        $page_data['workflow_action_token'] = $this->workflow_action_token;
        $this->load->view('backend/index', $page_data);
    }

    public function exam_pattern_builder($exam_id = 0)
    {
        $this->_require_tutor_docs_access();
        $this->load->model('Exam_pattern_model', 'exam_pattern');
        $this->load->model('Question_bank_model', 'question_bank');

        $exam_id = (int)$exam_id;
        $exam = $exam_id > 0 ? $this->exam_pattern->get_exam($exam_id) : null;
        if ($exam_id > 0 && !$exam) show_404();
        if ($exam && !$this->_tutor_owns_exam($exam)) show_error('You can edit only your own exam patterns.', 403);

        $page_data['page_name'] = 'exam_pattern_builder';
        $page_data['page_title'] = $exam ? 'Edit Exam Pattern' : 'Create Exam Pattern';
        $page_data['exam'] = $exam;
        $page_data['sections'] = $exam ? $this->exam_pattern->get_sections($exam_id) : array();
        $user_id = (int)$this->session->userdata('user_id');
        $page_data['bank_questions'] = array();
        $page_data['filter_options'] = $this->question_bank->get_filter_options($user_id);
        $page_data['workflow_action_token'] = $this->workflow_action_token;
        $this->load->view('backend/index', $page_data);
    }

    public function question_bank_pool()
    {
        $this->output->set_content_type('application/json');
        if ($this->session->userdata('user_login') != true) {
            $this->output->set_status_header(403)->set_output(json_encode(array('error' => 'Not authorized')));
            return;
        }
        $this->_require_tutor_docs_access();
        $this->load->model('Exam_pattern_model', 'exam_pattern');
        $result = $this->exam_pattern->search_bank_questions(
            (int)$this->session->userdata('user_id'),
            trim((string)$this->input->get('exam')),
            trim((string)$this->input->get('topic')),
            trim((string)$this->input->get('difficulty')),
            array(),
            1
        );
        unset($result['questions']);
        $this->output->set_output(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    public function exam_pattern_save($exam_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();

        $this->load->model('Exam_pattern_model', 'exam_pattern');
        $this->load->model('Moderation_model', 'moderation');

        $exam_id = (int)$exam_id;
        $before = $exam_id > 0 ? $this->exam_pattern->get_exam($exam_id) : null;
        if ($exam_id > 0 && (!$before || !$this->_tutor_owns_exam($before))) {
            show_error('You can save only your own exam patterns.', 403);
        }

        $user_id = (int)$this->session->userdata('user_id');
        $result = $this->exam_pattern->save_pattern($user_id, $this->input->post(null, false), $exam_id, 0);
        if (!empty($result['ok']) && (string)$this->input->post('status') === 'in_review') {
            $target_id = (int)($result['exam_id'] ?? $exam_id);
            $this->moderation->record('exam', $target_id, 'submit', $before['review_status'] ?? 'draft', 'in_review', '', $user_id, $user_id);
        }

        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        $target = !empty($result['exam_id']) ? (int)$result['exam_id'] : $exam_id;
        redirect($target > 0 ? site_url('user/exam_pattern_builder/'.$target) : site_url('user/exam_pattern_builder'), 'refresh');
    }

    public function exam_pattern_archive($exam_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $this->load->model('Exam_pattern_model', 'exam_pattern');

        $exam = $this->exam_pattern->get_exam((int)$exam_id);
        if (!$exam || !$this->_tutor_owns_exam($exam)) {
            show_error('You can archive only your own exam patterns.', 403);
        }

        $result = $this->exam_pattern->archive_exam((int)$this->session->userdata('user_id'), (int)$exam_id);
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect(site_url('user/content_public_exams'), 'refresh');
    }

    public function question_bank()
    {
        $this->_require_tutor_docs_access();
        $this->load->model('Question_bank_model', 'question_bank');

        $filters = array(
            'search' => trim((string)$this->input->get('search')),
            'difficulty' => trim((string)$this->input->get('difficulty')),
            'exam_type' => trim((string)$this->input->get('exam_type')),
            'topic' => trim((string)$this->input->get('topic')),
            'question_section' => trim((string)$this->input->get('question_section')),
            'question_type' => trim((string)$this->input->get('question_type')),
            'status' => trim((string)$this->input->get('status')),
            'class_id' => (int)$this->input->get('class_id'),
            'subject_id' => (int)$this->input->get('subject_id'),
            'chapter' => trim((string)$this->input->get('chapter')),
            'learning_outcome' => trim((string)$this->input->get('learning_outcome')),
            'cognitive_level' => trim((string)$this->input->get('cognitive_level')),
        );
        $per_page = 25;
        $page = max(1, (int)$this->input->get('page'));
        $user_id = (int)$this->session->userdata('user_id');
        $result = $this->question_bank->search($filters, $per_page, ($page - 1) * $per_page, $user_id);

        $edit_question = null;
        $edit_id = (int)$this->input->get('edit');
        if ($edit_id > 0) {
            $candidate = $this->question_bank->get_question($edit_id);
            if ($candidate && $this->_tutor_owns_question($candidate)) {
                $edit_question = $candidate;
            } else {
                $this->session->set_flashdata('error_message', 'You can edit only questions created by you.');
            }
        }

        $page_data['page_name'] = 'question_bank';
        $page_data['page_title'] = 'Question Bank';
        $page_data['questions'] = $result['rows'];
        $page_data['total_questions'] = $result['total'];
        $page_data['summary'] = $this->question_bank->get_summary($user_id);
        $page_data['quality_summary'] = $this->question_bank->get_quality_summary($user_id);
        $page_data['recent_imports'] = $this->question_bank->get_recent_imports(10, $user_id);
        $page_data['filter_options'] = $this->question_bank->get_filter_options($user_id);
        $page_data['filters'] = $filters;
        $page_data['current_page'] = $page;
        $page_data['page'] = $page;
        $page_data['per_page'] = $per_page;
        $page_data['edit_question'] = $edit_question;
        $page_data['current_user_id'] = (int)$this->session->userdata('user_id');
        $page_data['workflow_action_token'] = $this->workflow_action_token;
        $this->load->view('backend/index', $page_data);
    }

    public function question_bank_exam_master_save()
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $this->load->model('Question_bank_model', 'question_bank');
        $result = $this->question_bank->save_exam_master(
            (int)$this->session->userdata('user_id'),
            (string)$this->input->post('exam_name', true),
            (string)$this->input->post('description', true)
        );
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect($this->input->server('HTTP_REFERER') ?: site_url('user/question_bank'), 'refresh');
    }

    public function question_bank_section_master_save()
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $this->load->model('Question_bank_model', 'question_bank');
        $result = $this->question_bank->save_section_master(
            (int)$this->session->userdata('user_id'),
            (string)$this->input->post('exam_name', true),
            (string)$this->input->post('section_name', true),
            (string)$this->input->post('description', true)
        );
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect($this->input->server('HTTP_REFERER') ?: site_url('user/question_bank'), 'refresh');
    }

    public function question_bank_save($question_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $requested_status = (string)$this->input->post('status');
        if (!in_array($requested_status, array('draft','in_review','archived'), true)) {
            $this->session->set_flashdata('error_message', 'Questions must be submitted for admin review before activation.');
            redirect($this->input->server('HTTP_REFERER') ?: site_url('user/question_bank'), 'refresh');
            return;
        }

        $this->load->model('Question_bank_model', 'question_bank');
        $this->load->model('Moderation_model', 'moderation');
        $question_id = (int)$question_id;
        $before = $question_id > 0 ? $this->question_bank->get_question($question_id) : null;
        if ($question_id > 0 && (!$before || !$this->_tutor_owns_question($before))) {
            show_error('You can save only questions created by you.', 403);
        }

        $user_id = (int)$this->session->userdata('user_id');
        $result = $this->question_bank->save_question($user_id, $this->input->post(null, false), $question_id);
        if (!empty($result['ok']) && $requested_status === 'in_review') {
            $target_id = (int)($result['question_id'] ?? $question_id);
            $this->moderation->record('question', $target_id, 'submit', $before['review_status'] ?? 'draft', 'in_review', '', $user_id, $user_id);
        }
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
    }

    public function question_bank_status($question_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $requested_status = (string)$this->input->post('status');
        if (!in_array($requested_status, array('draft','in_review','archived'), true)) {
            $this->session->set_flashdata('error_message', 'Questions must be submitted for admin review before activation.');
            redirect($this->input->server('HTTP_REFERER') ?: site_url('user/question_bank'), 'refresh');
            return;
        }

        $this->load->model('Question_bank_model', 'question_bank');
        $this->load->model('Moderation_model', 'moderation');
        $before = $this->question_bank->get_question((int)$question_id);
        if (!$before || !$this->_tutor_owns_question($before)) {
            show_error('You can update only questions created by you.', 403);
        }

        $user_id = (int)$this->session->userdata('user_id');
        $result = $this->question_bank->change_status($user_id, (int)$question_id, $requested_status, (string)$this->input->post('admin_remark'));
        if (!empty($result['ok']) && $requested_status === 'in_review') {
            $this->moderation->record('question', (int)$question_id, 'submit', $before['review_status'] ?? $before['status'], 'in_review', '', $user_id, $user_id);
        }
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect($this->input->server('HTTP_REFERER') ?: site_url('user/question_bank'), 'refresh');
    }

    public function question_bank_duplicate($question_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $this->load->model('Question_bank_model', 'question_bank');
        $result = $this->question_bank->duplicate_question((int)$this->session->userdata('user_id'), (int)$question_id);
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect(site_url('user/question_bank?qb_tab=search'), 'refresh');
    }

    public function question_bank_delete($question_id = 0)
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        $this->load->model('Question_bank_model', 'question_bank');
        $question = $this->question_bank->get_question((int)$question_id);
        if (!$question || !$this->_tutor_owns_question($question)) {
            show_error('You can delete only questions created by you.', 403);
        }
        $result = $this->question_bank->delete_question((int)$question_id);
        $this->session->set_flashdata(!empty($result['ok']) ? 'flash_message' : 'error_message', $result['message']);
        redirect(site_url('user/question_bank?qb_tab=search'), 'refresh');
    }

    public function question_bank_export()
    {
        $this->_require_tutor_docs_access();
        $this->load->model('Question_bank_model', 'question_bank');
        $filters = array(
            'search' => trim((string)$this->input->get('search')),
            'difficulty' => trim((string)$this->input->get('difficulty')),
            'exam_type' => trim((string)$this->input->get('exam_type')),
            'topic' => trim((string)$this->input->get('topic')),
            'question_section' => trim((string)$this->input->get('question_section')),
            'question_type' => trim((string)$this->input->get('question_type')),
            'status' => trim((string)$this->input->get('status')),
        );
        $rows = $this->question_bank->export_rows($filters, 50000, (int)$this->session->userdata('user_id'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lvalues_tutor_question_bank_export_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('exam_name','sub_category','topic','question_code','question_text','question_type','option_a','option_b','option_c','option_d','option_e','correct_answers','difficulty','tags','marks','negative_marks','explanation','status'));
        foreach ($rows as $row) {
            $opt_text = array_fill(0, 5, '');
            $correct = array();
            foreach (($row['options'] ?? array()) as $i => $option) {
                if ($i > 4) break;
                $opt_text[$i] = (string)$option['option_text'];
                if (!empty($option['is_correct'])) $correct[] = chr(65 + $i);
            }
            fputcsv($out, array(
                $row['exam_type'] ?? '',
                ($row['question_section'] ?? '') ?: ($row['topic'] ?? ''),
                $row['topic'] ?? '',
                $row['question_code'] ?? '',
                $row['question_text'] ?? '',
                $row['question_type'] ?? '',
                $opt_text[0], $opt_text[1], $opt_text[2], $opt_text[3], $opt_text[4],
                implode(',', $correct),
                $row['difficulty'] ?? '',
                $row['tags'] ?? '',
                $row['marks'] ?? '',
                $row['negative_marks'] ?? '',
                strip_tags((string)($row['explanation'] ?? '')),
                $row['status'] ?? ''
            ));
        }
        fclose($out);
        exit;
    }

    public function question_bank_import_template()
    {
        $this->_require_tutor_docs_access();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lvalues_tutor_question_bank_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('exam_name','sub_category','topic','question_text','question_type','option_a','option_b','option_c','option_d','option_e','correct_answers','difficulty','tags','marks','negative_marks','explanation','status'));
        fputcsv($out, array('SBI PO PRE','Quantitative Aptitude','Percentage','What is 20% of 150?','single','20','25','30','35','','C','Beginner','percentage,aptitude','1','0.25','20 percent of 150 is 30.','draft'));
        fclose($out);
        exit;
    }

    public function question_bank_import()
    {
        $this->_require_tutor_docs_access();
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $this->_require_workflow_action();
        if (empty($_FILES['question_file']['name'])) {
            $this->session->set_flashdata('error_message', 'Please choose a CSV or XLSX file.');
            redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
        }
        $name = (string)$_FILES['question_file']['name'];
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, array('csv','xlsx'), true) || (int)$_FILES['question_file']['size'] > 10 * 1024 * 1024) {
            $this->session->set_flashdata('error_message', 'Upload a CSV or XLSX file no larger than 10 MB.');
            redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
        }
        $this->load->library('Tabular_import');
        try {
            $table_rows = $this->tabular_import->rows($_FILES['question_file']['tmp_name'], $extension);
        } catch (Throwable $exception) {
            $table_rows = array();
            log_message('error', 'Tutor question import parse failed: ' . $exception->getMessage());
        }
        $header = !empty($table_rows) ? array_shift($table_rows) : false;
        if (!$header) {
            $this->session->set_flashdata('error_message', 'Import file is empty or unreadable.');
            redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
        }
        $header = array_map(function($value){ return strtolower(trim((string)$value)); }, $header);
        $required = array('exam_name','sub_category','question_text','question_type','option_a','option_b','correct_answers','marks');
        $missing = array_diff($required, $header);
        if ($missing) {
            $this->session->set_flashdata('error_message', 'Missing required columns: ' . implode(', ', $missing));
            redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
        }
        $rows = array();
        $line = 1;
        foreach ($table_rows as $values) {
            $line++;
            if (!array_filter($values, 'strlen')) continue;
            $row = array();
            foreach ($header as $index => $column) {
                $value = isset($values[$index]) ? trim((string)$values[$index]) : '';
                if ($value !== '' && preg_match('/^(?:[=+@]|-\D)/', $value)) $value = "'" . $value;
                $row[$column] = $value;
            }
            $rows[$line] = $row;
        }

        $this->load->model('Question_bank_model', 'question_bank');
        $summary = $this->question_bank->import_rows((int)$this->session->userdata('user_id'), $rows, $name);
        $message = 'Import complete. Total: ' . $summary['total']
            . ', Imported: ' . $summary['imported']
            . ', Duplicates: ' . $summary['duplicates']
            . ', Failed: ' . $summary['failed'] . '.';
        if (!empty($summary['errors'])) {
            $message .= ' First errors: ' . implode(' | ', array_slice($summary['errors'], 0, 5));
        }
        $this->session->set_flashdata($summary['imported'] > 0 ? 'flash_message' : 'error_message', $message);
        redirect(site_url('user/question_bank?qb_tab=upload'), 'refresh');
    }

    private function _tutor_owns_exam($exam)
    {
        $user_id = (int)$this->session->userdata('user_id');
        return is_array($exam) && ((int)($exam['tutor_id'] ?? 0) === $user_id || (int)($exam['created_by'] ?? 0) === $user_id);
    }

    private function _tutor_owns_question($question)
    {
        return is_array($question) && (int)($question['created_by'] ?? 0) === (int)$this->session->userdata('user_id');
    }

    private function _require_workflow_action()
    {
        if ($this->input->method(true) !== 'POST') {
            show_error('Method not allowed', 405);
        }
        $submitted = (string)$this->input->post('workflow_action_token');
        if ($submitted === '' || !hash_equals($this->workflow_action_token, $submitted)) {
            show_error('This tutor action could not be verified. Refresh the page and try again.', 403);
        }
    }


    // Exam and question authoring is intentionally admin-only.
    public function content_exam_save($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_publish($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_archive($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_question_save($exam_id = 0, $question_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_question_delete($question_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_question_duplicate($question_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_attempts($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }



    public function content_exam_import_template()
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_import_questions($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function content_exam_export_questions($exam_id = 0)
    {
        return $this->_deny_tutor_exam_authoring();
    }

    public function my_practice_tests()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }
        $this->load->model('Exam_model', 'exam_model');
        $page_data['page_name'] = 'my_practice_tests';
        $page_data['page_title'] = 'My Practice Tests / Mock Tests';
        $page_data['attempts'] = $this->exam_model->get_student_attempts((int)$this->session->userdata('user_id'));
        $page_data['analytics'] = $this->exam_model->get_student_exam_analytics((int)$this->session->userdata('user_id'));
        $this->load->view('backend/index', $page_data);
    }

    function start_quiz($quiz_id = "", $retake = "")
    {
        $quiz_details = $this->crud_model->get_lessons('lesson', $quiz_id)->row_array();

        $data['quiz_id'] = $quiz_details['id'];
        $data['user_id'] = $this->session->userdata('user_id');
        $data['user_answers'] = json_encode(array());
        $data['correct_answers'] = json_encode(array());
        $data['date_added'] = time();
        $data['date_updated'] = time();
        $data['is_submitted'] = 0;
        $data['total_obtained_marks'] = 0;

        $row = $this->db->get_where('quiz_results', array('user_id' => $data['user_id'], 'quiz_id' => $quiz_id));
        $total_attemped = $this->db->where('quiz_id', $quiz_id)->where('user_id', $data['user_id'])->get('quiz_results')->num_rows();
        if ($quiz_details['quiz_attempt'] == 0 && $row->num_rows() <= 0 || $quiz_details['quiz_attempt'] > ($total_attemped - 1)) :

            if ($this->db->get_where('quiz_results', array('user_id' => $data['user_id'], 'is_submitted' => 0, 'quiz_id' => $quiz_id))->num_rows() == 0) :
                $this->db->insert('quiz_results', $data);
            endif;
        endif;

        if ($retake != "") {
            $course_title = $this->crud_model->get_course_by_id($quiz_details['course_id'])->row('title');
            redirect(site_url('home/lesson/' . slugify($course_title) . '/' . $quiz_details['course_id'] . '/' . $quiz_details['id']), 'refresh');
        }

        $page_data['quiz_questions'] = $this->db->get_where('question', array('quiz_id' => $quiz_id));
        $page_data['quiz_id'] = $quiz_id;
        $this->load->view('lessons/quiz_answer_sheet', $page_data);
    }

    function submit_quiz_answer($quiz_id = "", $question_id = "", $question_type = "")
    {

        //Quize details
        $user_id = $this->session->userdata('user_id');
        $quiz_details = $this->crud_model->get_lessons('lesson', $quiz_id)->row_array();
        $total_seconds = time_to_seconds($quiz_details['duration']);
        $total_marks = json_decode($quiz_details['attachment'], true)['total_marks'];

        //Question details
        $question_details = $this->db->get_where('question', array('id' => $question_id))->row_array();


        $results = $this->db->order_by('quiz_result_id', 'desc')->get_where('quiz_results', array('quiz_id' => $quiz_id, 'user_id' => $user_id));

        if ($results->num_rows() > 0 && ($total_seconds + $results->row('date_added')) > time() || $total_seconds == 0) {
            $result = $results->row_array();
            $correct_answer_question_ids = json_decode($result['correct_answers'], true);

            $answers = $this->input->post('answer');

            $user_answers = json_decode($result['user_answers'], true);
            $user_answers[$question_id] = $answers;

            if ($question_type == 'multiple_choice') {
                $is_correct_answer = 1;
                $currect_answers = json_decode($question_details['correct_answers'], true);
                foreach ($answers as $answer) {
                    if (!in_array($answer, $currect_answers)) {
                        $is_correct_answer = 0;
                    }
                }
                if (!is_array($answers) || count($answers) <= 0 || count($currect_answers) != count($answers)) {
                    $is_correct_answer = 0;
                }
            } elseif ($question_type == 'single_choice') {
                $is_correct_answer = 0;
                $currect_answers = json_decode($question_details['correct_answers'], true);
                if (in_array($answers[0], $currect_answers)) {
                    $is_correct_answer = 1;
                }
            } elseif ($question_type == 'fill_in_the_blank') {
                $is_correct_answer = 1;
                $currect_answers = json_decode(strtolower($question_details['correct_answers']), true);
                foreach ($answers as $key => $answer) {
                    $answer = strtolower($answer);
                    if ($answer != $currect_answers[$key]) {
                        $is_correct_answer = 0;
                    }
                }
                if (!is_array($answers) || count($answers) <= 0 || count($currect_answers) != count($answers)) {
                    $is_correct_answer = 0;
                }
            }

            if ($is_correct_answer == 1) {
                if (!in_array($question_id, $correct_answer_question_ids)) {
                    array_push($correct_answer_question_ids, $question_id);
                }
            } else {
                $updated_correct_answer_question_ids = array();
                foreach ($correct_answer_question_ids as $correct_answer_question_id) {
                    if ($correct_answer_question_id != $question_id) {
                        array_push($updated_correct_answer_question_ids, $correct_answer_question_id);
                    }
                }
                $correct_answer_question_ids = $updated_correct_answer_question_ids;
            }

            $total_questions = $this->db->get_where('question', array('quiz_id' => $quiz_id))->num_rows();
            $data['total_obtained_marks'] = round(($total_marks / $total_questions) * count($correct_answer_question_ids), 1);

            $data['user_answers'] = json_encode($user_answers);
            $data['correct_answers'] = json_encode($correct_answer_question_ids);
            $data['date_updated'] = time();
            $this->db->where('user_id', $user_id);
            $this->db->where('quiz_id', $quiz_id);
            $this->db->where('is_submitted', 0);
            $this->db->update('quiz_results', $data);
        } else {
            $this->finish_quize_submission($quiz_id);
            $response['status'] = 'time_over';
            $response['message'] = site_phrase('time_over');
            echo json_encode($response);
        }
    }

    function finish_quize_submission($quiz_id = "")
    {
        $user_id = $this->session->userdata('user_id');
        $data['is_submitted'] = 1;
        $this->db->where('user_id', $user_id);
        $this->db->where('is_submitted', 0);
        $this->db->where('quiz_id', $quiz_id);
        $this->db->update('quiz_results', $data);

        //Mark this quiz as completed
        $lesson = $this->crud_model->get_lessons('lesson', $quiz_id)->row_array();
        $completed_lessons = $this->crud_model->get_watch_histories($user_id, $lesson['course_id'])->row_array();
        $course_details = $this->crud_model->get_course_by_id($lesson['course_id'])->row_array();
        $quiz_results = $this->db->where('user_id', $user_id)->where('quiz_id', $quiz_id)->order_by('quiz_result_id', 'DESC')->get('quiz_results')->row_array();
        $completed_lessons = json_decode($completed_lessons['completed_lesson'], true);
        if (!is_array($completed_lessons) || !in_array($quiz_id, $completed_lessons)) {

            //check passing mark
            $quiz_attribute = json_decode($lesson['attachment'], true);
            $pass_mark = $quiz_attribute['pass_mark'] ?? 0;
            $drip_content_for_passing_rule = $quiz_attribute['drip_content_for_passing_rule'] ?? 'not_applicable';

            if ($course_details['enable_drip_content'] && $drip_content_for_passing_rule == 'applicable') {
                if ($pass_mark <= $quiz_results['total_obtained_marks']) {
                    $this->crud_model->update_watch_history_manually($quiz_id, $lesson['course_id'], $user_id);
                }
            } else {
                $this->crud_model->update_watch_history_manually($quiz_id, $lesson['course_id'], $user_id);
            }
        }

        $response['status'] = 'submit';
        $response['message'] = site_phrase('quiz_submission_successfully');
        echo json_encode($response);
    }

    function ai_img_download()
    {
        $this->load->model('addons/ai_model');
        $this->ai_model->ai_img_download();
    }

    function chat_gpt()
    {
        if (isset($_POST['service_type']) && !empty($_POST['service_type'])) {
            $this->load->model('addons/ai_model');
            echo $this->ai_model->chat_gpt();
        } else {
            $this->load->view('backend/admin/chat_gpt');
        }
    }

    function gpt_assistant()
    {
        $this->load->model('addons/ai_model');
        echo $this->ai_model->gpt_assistant();
    }

    function student_academic_progress($course_id = "")
    {
        $course_details = $this->crud_model->get_course_by_id($course_id)->row_array();
        $multi_instructors = explode(',', $course_details['user_id']);

        if (!in_array($this->session->userdata('user_id'), $multi_instructors)) {
            return false;
        }

        $page_data['course_details'] = $course_details;
        $this->load->view('backend/user/student_academic_progress', $page_data);
    }

    function student_academic_quiz_result($course_id = "", $student_id = "")
    {
        $course_details = $this->crud_model->get_course_by_id($course_id)->row_array();
        $multi_instructors = explode(',', $course_details['user_id']);

        if (!in_array($this->session->userdata('user_id'), $multi_instructors)) {
            return false;
        }

        $page_data['course_details'] = $course_details;
        $page_data['student_id'] = $student_id;
        $this->load->view('backend/user/student_academic_quiz_result', $page_data);
    }

    function student_certificate($user_id = "", $course_id = "")
    {
        $this->load->model('addons/Certificate_model', 'certificate_model');
        $course_progress = $this->crud_model->get_watch_histories($user_id, $course_id)->row('course_progress');
        if ($course_progress >= 100) {
            $this->certificate_model->check_certificate_eligibility($course_id, $user_id);
            $certificate = $this->db->get_where('certificates', array('course_id' => $course_id, 'student_id' => $user_id));
            redirect(site_url('certificate/' . $certificate->row('shareable_url')));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('The course is not compleated yet'));
            redirect(site_url('user/course_form/course_edit/' . $certificate->row('shareable_url')));
        }
    }



    function resource_files($param1 = "", $param2 = "")
    {
        if ($param1 == 'add') {
            if (isset($_FILES['resource_file']['name']) && $_FILES['resource_file']['name'] != "") {
                $data['file_name'] = random(20) . '.' . pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['resource_file']['tmp_name'], 'uploads/resource_files/' . $data['file_name']);
            }

            $data['title'] = $this->input->post('title');
            $data['lesson_id'] = $param2;
            $data['created_at'] = time();
            $this->db->insert('resource_files', $data);

            $response['replace'] = ['elem' => '.resource_file_content', 'content' => $this->load->view('backend/user/resource_files', ['param2' => $param2], true)];
            echo json_encode($response);
        } elseif ($param1 == 'update') {
            $file_details = $this->db->get_where('resource_files', ['id' => $param2])->row_array();
            if (isset($_FILES['resource_file']['name']) && $_FILES['resource_file']['name'] != "") {
                if (file_exists('uploads/resource_files/' . $file_details['file_name']) && $file_details['file_name']) {
                    unlink('uploads/resource_files/' . $file_details['file_name']);
                }
                $data['file_name'] = random(20) . '.' . pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION);
                move_uploaded_file($_FILES['resource_file']['tmp_name'], 'uploads/resource_files/' . $data['file_name']);
            }

            $data['title'] = $this->input->post('title');
            $data['updated_at'] = time();
            $this->db->where('id', $param2);
            $this->db->update('resource_files', $data);

            $response['replace'] = ['elem' => '.resource_file_content', 'content' => $this->load->view('backend/user/resource_files', ['param2' => $file_details['lesson_id']], true)];
            echo json_encode($response);
        } elseif ($param1 == 'delete') {
            $file_details = $this->db->get_where('resource_files', ['id' => $param2])->row_array();
            if (file_exists('uploads/resource_files/' . $file_details['file_name']) && $file_details['file_name']) {
                unlink('uploads/resource_files/' . $file_details['file_name']);
            }

            $this->db->where('id', $param2);
            $this->db->delete('resource_files');

            $response['replace'] = ['elem' => '.resource_file_content', 'content' => $this->load->view('backend/user/resource_files', ['param2' => $file_details['lesson_id']], true)];
            $response['success'] = get_phrase('Resource deleted successfully');
            $response['fadeOut'] = '#resource_file_' . $file_details['id'];
            echo json_encode($response);
        }
    }


    function save_bbb_meeting($course_id = "")
    {
        $user_id = $this->session->userdata('user_id');
        if (!$this->crud_model->is_course_instructor($course_id, $user_id)) {
            return;
        }
        $data['meeting_id'] = $this->input->post('bbb_meeting_id');
        $data['moderator_pw'] = $this->input->post('bbb_moderator_pw');
        $data['viewer_pw'] = $this->input->post('bbb_viewer_pw');
        $data['instructions'] = $this->input->post('instructions');

        if ($this->db->where('course_id', $course_id)->get('bbb_meetings')->num_rows() > 0) {
            $data['updated_at'] = time();
            $this->db->where('course_id', $course_id)->update('bbb_meetings', $data);
        } else {
            $data['course_id'] = $course_id;
            $data['created_at'] = time();
            $data['updated_at'] = $data['created_at'];
            $this->db->insert('bbb_meetings', $data);
        }

        echo get_phrase("BigBlueButton Meeting has been updated");
    }

    function start_bbb_meeting($course_id = "")
    {
        $user_id = $this->session->userdata('user_id');
        if (!$this->crud_model->is_course_instructor($course_id, $user_id)) {
            return;
        }

        $course_details = $this->crud_model->get_courses($course_id)->row_array();
        $bbb_meeting = $this->db->where('course_id', $course_id)->get('bbb_meetings');
        $current_url = site_url('user/course_form/course_edit/' . $course_id . '?tab=bbb-live-class');

        if ($bbb_meeting->num_rows() > 0) {
            $bbb_meeting = $bbb_meeting->row_array();
            //Sanitize API URL START
            $api_url = get_settings('bbb_setting', true)['endpoint'] ?? '';
            // Parse the URL
            $parsed_url = parse_url($api_url);
            // Remove the 'api' part if it exists in the path
            $path = rtrim(str_replace('/api', '', $parsed_url['path']), '/');
            // Rebuild the URL
            $api_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $path;
            //Sanitize API URL END

            //Create BBB meeting START
            $query_data = http_build_query([
                'name' => $course_details['title'],
                'meetingID' => $bbb_meeting['meeting_id'],
                'attendeePW' => $bbb_meeting['viewer_pw'],
                'moderatorPW' => $bbb_meeting['moderator_pw'],
                'redirectURL' => $current_url,
            ]);
            $response = $this->crud_model->callBbbApi('create', $query_data);
            //Create BBB meeting END

            // Handle response & redirect to meeting url
            if ($response) {
                $xml = simplexml_load_string($response);
                $returncode = (string)$xml->returncode;

                if ($returncode == 'SUCCESS') {
                    $moderator_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
                    //JOIN AS A viewer
                    $full_name = $moderator_details['first_name'] . ' ' . $moderator_details['last_name']; // The full name of the participant
                    $role = 'moderator'; // The role of the user (either "viewer" or "moderator")
                    $join_url = $api_url . "/api/join?meetingID=" . $bbb_meeting['meeting_id'] . "&fullName=$full_name&password=" . $bbb_meeting['moderator_pw'] . "&joinViaHtml5=true&redirect=true&joinParam[role]=$role";
                    echo $join_url;
                    return;
                } else {
                    $this->session->set_flashdata('error_message', get_phrase("Failed to create meeting. Error code: ____", [$returncode]));
                }
            } else {
                $this->session->set_flashdata('error_message', get_phrase("Failed to connect to BigBlueButton API"));
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase("Please save your meeting info first"));
        }
        echo $current_url;
    }

    function join_bbb_meeting($course_id = "")
    {
        if (enroll_status($course_id) == 'valid') {
            $bbb_meeting = $this->db->where('course_id', $course_id)->get('bbb_meetings');
            $current_url = $_SERVER['HTTP_REFERER'];

            if ($bbb_meeting->num_rows() > 0) {
                $bbb_meeting = $bbb_meeting->row_array();

                //Sanitize API URL START
                $api_url = get_settings('bbb_setting', true)['endpoint'] ?? '';
                // Parse the URL
                $parsed_url = parse_url($api_url);
                // Remove the 'api' part if it exists in the path
                $path = rtrim(str_replace('/api', '', $parsed_url['path']), '/');
                // Rebuild the URL
                $api_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $path;
                //Sanitize API URL END
                
                //JOIN AS A viewer [JOIN LINK]
                $viewer_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
                $full_name = $viewer_details['first_name'] . ' ' . $viewer_details['last_name']; // The full name of the participant
                $role = 'viewer'; // The role of the user (either "viewer" or "moderator")
                $join_url = $api_url . "/api/join?meetingID=" . $bbb_meeting['meeting_id'] . "&fullName=$full_name&password=" . $bbb_meeting['moderator_pw'] . "&joinViaHtml5=true&redirect=true&joinParam[role]=$role";
                redirect($join_url, 'refresh');
            } else {
                $this->session->set_flashdata('error_message', get_phrase("Meeting not scheduled yet"));
            }
            redirect($current_url, 'refresh');
        } else {
            $this->session->set_flashdata('error_message', get_phrase("Please purchase this course first"));
            redirect(site_url('home/my_courses'), 'refresh');
        }
    }
	
	/*public function content_nodes($param1 = "", $param2 = "")
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if (!$this->session->userdata('is_instructor')) {
			$this->session->set_flashdata('error_message', 'Only tutors can access Content (Docs).');
			redirect(site_url('user/dashboard'), 'refresh');
		}

		$page_data['page_name']  = 'content_nodes';
		$page_data['page_title'] = get_phrase('nodes_(tree)');
		$this->load->view('backend/index', $page_data);
	}

	public function content_pages($param1 = "", $param2 = "")
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if (!$this->session->userdata('is_instructor')) {
			$this->session->set_flashdata('error_message', 'Only tutors can access Content (Docs).');
			redirect(site_url('user/dashboard'), 'refresh');
		}

		$page_data['page_name']  = 'content_pages';
		$page_data['page_title'] = get_phrase('pages');
		$this->load->view('backend/index', $page_data);
	}*/

	
	


    public function tutor_teaching_profile()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if (!$this->session->userdata('is_instructor')) {
            $this->session->set_flashdata('error_message', 'Only tutors can update teaching profile.');
            redirect(site_url('user/dashboard'), 'refresh');
        }

        $this->load->model('Tutor_master_model', 'tutor_master_model');
        $user_id = (int) $this->session->userdata('user_id');

        $page_data['tutor_registration_tree'] = $this->tutor_master_model->get_registration_tree();
        $page_data['tutor_profile'] = $this->tutor_master_model->get_tutor_profile_by_user_id($user_id);
        $page_data['selected_category_ids'] = $this->tutor_master_model->get_selected_category_ids_by_user_id($user_id);
        $page_data['selected_class_ids'] = $this->tutor_master_model->get_selected_class_ids_by_user_id($user_id);
        $page_data['selected_subject_ids'] = $this->tutor_master_model->get_selected_subject_ids_by_user_id($user_id);
        $application = $this->user_model->get_applications($user_id, 'user');
        $page_data['tutor_application'] = $application->num_rows() > 0 ? $application->row_array() : [];
        $page_data['page_name'] = 'tutor_teaching_profile';
        $page_data['page_title'] = 'teaching_profile';

        $this->load->view('backend/index', $page_data);
    }

    public function update_tutor_teaching_profile()
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if (!$this->session->userdata('is_instructor')) {
            $this->session->set_flashdata('error_message', 'Only tutors can update teaching profile.');
            redirect(site_url('user/dashboard'), 'refresh');
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            redirect(site_url('user/tutor_teaching_profile'), 'refresh');
        }

        $this->load->model('Tutor_master_model', 'tutor_master_model');
        $user_id = (int) $this->session->userdata('user_id');

        $tutor_lat = trim((string) $this->input->post('tutor_lat', true));
        $tutor_lng = trim((string) $this->input->post('tutor_lng', true));

        $payload = [
            'tutor_category_ids' => (array) $this->input->post('tutor_category_ids'),
            'tutor_class_ids' => (array) $this->input->post('tutor_class_ids'),
            'tutor_subject_ids' => (array) $this->input->post('tutor_subject_ids'),
            'tutor_headline' => $this->input->post('tutor_headline', true),
            'tutor_qualification' => $this->input->post('tutor_qualification', true),
            'tutor_experience_years' => $this->input->post('tutor_experience_years', true),
            'tutor_teaching_mode' => $this->input->post('tutor_teaching_mode', true),
            'tutor_hourly_fee' => $this->input->post('tutor_hourly_fee', true),
            'tutor_city' => $this->input->post('tutor_city', true),
            'tutor_state' => $this->input->post('tutor_state', true),
            'tutor_country' => $this->input->post('tutor_country', true),
            'tutor_pincode' => $this->input->post('tutor_pincode', true),
            'tutor_bio' => $this->input->post('tutor_bio'),
            'tutor_lat' => is_numeric($tutor_lat) ? $tutor_lat : '',
            'tutor_lng' => is_numeric($tutor_lng) ? $tutor_lng : '',
        ];

        $document_result = $this->save_tutor_verification_document($user_id);
        if (empty($document_result['status'])) {
            $this->session->set_flashdata('error_message', $document_result['message']);
            redirect(site_url('user/tutor_teaching_profile'), 'refresh');
        }

        $result = $this->tutor_master_model->save_tutor_teaching_profile($user_id, $payload);

        if (!empty($result['status'])) {
            $message = $result['message'];
            if (!empty($document_result['message'])) {
                $message .= ' ' . $document_result['message'];
            }
            $this->session->set_flashdata('flash_message', $message);
        } else {
            $this->session->set_flashdata('error_message', $result['message']);
        }

        redirect(site_url('user/tutor_teaching_profile'), 'refresh');
    }

    private function save_tutor_verification_document($user_id)
    {
        if (!isset($_FILES['verification_document']) || empty($_FILES['verification_document']['name'])) {
            return ['status' => true, 'message' => ''];
        }

        $accepted_ext = ['doc', 'docx', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($_FILES['verification_document']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $accepted_ext, true)) {
            return ['status' => false, 'message' => 'Invalid verification document. Allowed: doc, docx, pdf, txt, png, jpg, jpeg.'];
        }

        if (!file_exists('uploads/document') && !mkdir('uploads/document', 0777, true)) {
            return ['status' => false, 'message' => 'Could not create uploads/document directory.'];
        }

        $document_name = random(15) . '.' . $ext;
        $target_path = 'uploads/document/' . $document_name;
        if (!move_uploaded_file($_FILES['verification_document']['tmp_name'], $target_path)) {
            return ['status' => false, 'message' => 'Verification document upload failed.'];
        }

        $application = $this->user_model->get_applications((int)$user_id, 'user')->row_array();
        if (empty($application)) {
            if (file_exists($target_path)) {
                @unlink($target_path);
            }
            return ['status' => false, 'message' => 'Tutor application was not found for this document upload.'];
        }

        $this->db->where('id', (int)$application['id'])->update('applications', ['document' => $document_name]);

        if ((int)($application['status'] ?? 0) === 1 && $this->db->table_exists('tutor_profiles')) {
            $this->db->where('user_id', (int)$user_id)->update('tutor_profiles', ['status' => 'active']);
        }

        return ['status' => true, 'message' => 'Verification document uploaded successfully.'];
    }

    public function student_requests($status = 'all')
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        $this->load->model('Tutor_request_model', 'tutor_request_model');
        $allowed = ['all', 'pending', 'approved', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $page_data['request_status_filter'] = $status;
        $page_data['requests'] = $this->tutor_request_model->get_incoming_requests_for_tutor((int) $this->session->userdata('user_id'), $status);
        $page_data['pending_request_count'] = $this->tutor_request_model->count_pending_for_tutor((int) $this->session->userdata('user_id'));
        $page_data['page_name'] = 'student_requests';
        $page_data['page_title'] = get_phrase('student_requests');
        $this->load->view('backend/index', $page_data);
    }

    public function update_student_request($request_id = 0, $action = '')
    {
        if ($this->session->userdata('user_login') != true) {
            redirect(site_url('login'), 'refresh');
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            redirect(site_url('user/student_requests'), 'refresh');
        }

        $this->load->model('Tutor_request_model', 'tutor_request_model');
        $response_message = trim((string) $this->input->post('response_message'));
        $result = $this->tutor_request_model->respond_to_request((int) $request_id, (int) $this->session->userdata('user_id'), strtolower(trim($action)), $response_message);

        if (!empty($result['status'])) {
            $this->session->set_flashdata('flash_message', $result['message']);
        } else {
            $this->session->set_flashdata('error_message', $result['message']);
        }

        redirect(site_url('user/student_requests'), 'refresh');
    }

}
