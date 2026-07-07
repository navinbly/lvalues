<?php
$user_details = isset($user_details) && is_array($user_details) ? $user_details : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$student_360 = isset($student_360) && is_array($student_360) ? $student_360 : [];
$course_dashboard = isset($course_dashboard) && is_array($course_dashboard) ? $course_dashboard : [];
$student_command_center = isset($student_command_center) && is_array($student_command_center) ? $student_command_center : [];
$student_command_metrics = isset($student_command_center['metrics']) && is_array($student_command_center['metrics']) ? $student_command_center['metrics'] : [];
$student_command_actions = isset($student_command_center['actions']) && is_array($student_command_center['actions']) ? $student_command_center['actions'] : [];
$student_command_attention = isset($student_command_center['attention']) && is_array($student_command_center['attention']) ? $student_command_center['attention'] : [];

$summary = isset($student_360['summary']) && is_array($student_360['summary']) ? $student_360['summary'] : [];
$today_sessions = isset($student_360['today_sessions']) && is_array($student_360['today_sessions']) ? $student_360['today_sessions'] : [];
$upcoming_sessions = isset($student_360['upcoming_sessions']) && is_array($student_360['upcoming_sessions']) ? $student_360['upcoming_sessions'] : [];
$pending_assignments = isset($student_360['pending_assignments']) && is_array($student_360['pending_assignments']) ? $student_360['pending_assignments'] : [];
$pending_tests = isset($student_360['pending_tests']) && is_array($student_360['pending_tests']) ? $student_360['pending_tests'] : [];
$pending_invites = isset($student_360['pending_invites']) && is_array($student_360['pending_invites']) ? $student_360['pending_invites'] : [];
$batch_progress = isset($student_360['batch_progress']) && is_array($student_360['batch_progress']) ? $student_360['batch_progress'] : [];
$latest_teacher_remarks = isset($student_360['latest_teacher_remarks']) && is_array($student_360['latest_teacher_remarks']) ? $student_360['latest_teacher_remarks'] : [];
$latest_notifications = isset($student_360['latest_notifications']) && is_array($student_360['latest_notifications']) ? $student_360['latest_notifications'] : [];
$learning_insights = isset($student_360['learning_insights']) && is_array($student_360['learning_insights']) ? $student_360['learning_insights'] : [];
$weak_areas = isset($learning_insights['weak_areas']) && is_array($learning_insights['weak_areas']) ? $learning_insights['weak_areas'] : [];
$strong_areas = isset($learning_insights['strong_areas']) && is_array($learning_insights['strong_areas']) ? $learning_insights['strong_areas'] : [];
$missed_classes = isset($learning_insights['missed_classes']) && is_array($learning_insights['missed_classes']) ? $learning_insights['missed_classes'] : [];
$learning_profile = isset($student_360['learning_profile']) && is_array($student_360['learning_profile']) ? $student_360['learning_profile'] : [];
$continue_courses = isset($course_dashboard['continue_courses']) && is_array($course_dashboard['continue_courses']) ? $course_dashboard['continue_courses'] : [];
$profile_ready = !empty($learning_profile['category_id']) && !empty($learning_profile['class_id']);
$active_course_count = (int)($course_dashboard['enrolled_course_count'] ?? 0);
$student_full_name = trim(($user_details['first_name'] ?? '') . ' ' . ($user_details['last_name'] ?? ''));
$student_full_name = $student_full_name !== '' ? $student_full_name : 'Student';
$student_photo = $this->user_model->get_user_image_url($this->session->userdata('user_id'));
$student_user_id = (int)$this->session->userdata('user_id');
$pending_work_count = (int)($student_360['pending_work_count'] ?? 0);
$today_class_count = (int)($student_360['today_class_count'] ?? 0);
$active_batch_count = (int)($student_360['active_batch_count'] ?? 0);
$average_course_progress = (float)($course_dashboard['average_course_progress'] ?? 0);
$completed_course_count = (int)($course_dashboard['completed_course_count'] ?? 0);
$wishlist_items = [];
if (!empty($user_details['wishlist'])) {
    $wishlist_items = json_decode($user_details['wishlist'], true);
    $wishlist_items = is_array($wishlist_items) ? $wishlist_items : [];
}
$recommended_courses = array_slice($this->crud_model->get_latest_10_course(), 0, 3);
$certificate_count = 0;
if ($this->db->table_exists('certificates')) {
    $certificate_count = (int)$this->db->where('student_id', $student_user_id)->count_all_results('certificates');
}

$recommended_next_step = [
    'icon' => 'fa-solid fa-compass',
    'title' => 'Explore a guided course',
    'body' => 'Start with a structured course or request help from a tutor to build momentum.',
    'url' => site_url('home/courses'),
    'cta' => 'Browse Courses',
];
if (!$profile_ready) {
    $recommended_next_step = [
        'icon' => 'fa-regular fa-id-card',
        'title' => 'Complete your learning profile',
        'body' => 'Add your category and class so Lvalues can recommend the right tutors, batches, and courses.',
        'url' => site_url('home/profile/user_profile'),
        'cta' => 'Complete Profile',
    ];
} elseif ($pending_work_count > 0) {
    $recommended_next_step = [
        'icon' => 'fa-regular fa-clipboard',
        'title' => 'Clear pending work',
        'body' => 'Finish open assignments and tests first so your progress stays current.',
        'url' => site_url('student_batch/my_batches'),
        'cta' => 'Review Work',
    ];
} elseif ($today_class_count > 0) {
    $recommended_next_step = [
        'icon' => 'fa-regular fa-calendar-check',
        'title' => 'Prepare for today',
        'body' => 'Check your live session schedule, notes, and tutor instructions before class starts.',
        'url' => site_url('student_batch/my_batches'),
        'cta' => 'Open Schedule',
    ];
} elseif (!empty($continue_courses)) {
    $next_course = $continue_courses[0]['course'] ?? [];
    $recommended_next_step = [
        'icon' => 'fa-solid fa-play',
        'title' => 'Continue your course',
        'body' => 'Resume the course with the most recent progress and keep your weekly goal moving.',
        'url' => site_url('home/lesson/' . slugify($next_course['title'] ?? 'course') . '/' . (int)($next_course['id'] ?? 0)),
        'cta' => 'Continue Learning',
    ];
}

$learning_path_steps = [
    ['label' => 'Profile ready', 'detail' => 'Class and category selected', 'done' => $profile_ready],
    ['label' => 'Learning plan active', 'detail' => 'Courses or tutor batches connected', 'done' => ($active_course_count + $active_batch_count) > 0],
    ['label' => 'Attend sessions', 'detail' => 'Join live classes and stay consistent', 'done' => (float)($summary['attendance_percent'] ?? 0) >= 70],
    ['label' => 'Submit practice', 'detail' => 'Assignments and tests completed on time', 'done' => $pending_work_count === 0 && ($active_course_count + $active_batch_count) > 0],
    ['label' => 'Earn certificates', 'detail' => 'Complete courses and build proof of learning', 'done' => $certificate_count > 0 || $completed_course_count > 0],
];

$agenda_items = [];
foreach (array_slice($today_sessions, 0, 3) as $session) {
    $agenda_items[] = [
        'type' => 'Class',
        'title' => $session['title'] ?? 'Live session',
        'meta' => trim(($session['session_date'] ?? date('Y-m-d')) . ' ' . substr((string)($session['start_time'] ?? ''), 0, 5)),
        'url' => site_url('student_batch/view/' . (int)($session['batch_id'] ?? 0)),
    ];
}
foreach (array_slice($upcoming_sessions, 0, 3) as $session) {
    $agenda_items[] = [
        'type' => 'Upcoming',
        'title' => $session['title'] ?? 'Upcoming class',
        'meta' => trim(($session['session_date'] ?? '') . ' ' . substr((string)($session['start_time'] ?? ''), 0, 5)),
        'url' => site_url('student_batch/view/' . (int)($session['batch_id'] ?? 0)),
    ];
}
foreach (array_slice($pending_assignments, 0, 2) as $assignment) {
    $agenda_items[] = [
        'type' => 'Assignment',
        'title' => $assignment['title'] ?? 'Assignment',
        'meta' => !empty($assignment['due_at']) ? 'Due ' . $assignment['due_at'] : ($assignment['batch_title'] ?? 'Batch'),
        'url' => site_url('student_batch/view/' . (int)($assignment['batch_id'] ?? 0)),
    ];
}
foreach (array_slice($pending_tests, 0, 2) as $test) {
    $agenda_items[] = [
        'type' => 'Test',
        'title' => $test['title'] ?? 'Test',
        'meta' => $test['batch_title'] ?? 'Batch',
        'url' => site_url('student_batch/start_test/' . (int)($test['id'] ?? 0)),
    ];
}
$agenda_items = array_slice($agenda_items, 0, 6);

$weekly_goal_percent = max(
    0,
    min(100, round(((float)($summary['attendance_percent'] ?? 0) + (float)($summary['assignment_percent'] ?? 0) + max($average_course_progress, (float)($summary['overall_percent'] ?? 0))) / 3))
);
?>
<style>
    body {
        background: #f5f7fb;
    }
    body > header,
    body > .footer,
    body > footer {
        display: none !important;
    }
    .student-dashboard-topbar {
        align-items: center;
        background: #313a46;
        box-shadow: 0 1px 0 rgba(255,255,255,.04);
        display: flex;
        height: 70px;
        justify-content: space-between;
        padding: 0 48px;
        width: 100%;
    }
    .student-dashboard-topbar .sd-topbar-left,
    .student-dashboard-topbar .sd-topbar-right {
        align-items: center;
        display: flex;
        gap: 18px;
    }
    .student-dashboard-topbar .sd-brand {
        align-items: center;
        color: #fff;
        display: inline-flex;
        gap: 12px;
        font-size: 18px;
        font-weight: 700;
        text-decoration: none;
    }
    .student-dashboard-topbar .sd-brand img {
        display: block;
        height: 42px;
        max-width: 52px;
        object-fit: contain;
    }
    .student-dashboard-topbar .sd-visit-site {
        border: 1px solid rgba(255,255,255,.75);
        border-radius: 3px;
        color: #fff;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        padding: 12px 16px;
        text-decoration: none;
    }
    .student-dashboard-topbar .sd-visit-site:hover {
        background: rgba(255,255,255,.08);
        color: #fff;
        text-decoration: none;
    }
    .student-dashboard-topbar .sd-topbar-icon {
        align-items: center;
        color: #adb9c6;
        display: inline-flex;
        font-size: 18px;
        height: 38px;
        justify-content: center;
        width: 38px;
    }
    .student-dashboard-topbar .sd-user-card {
        align-items: center;
        align-self: stretch;
        background: rgba(0,0,0,.13);
        color: #fff;
        display: flex;
        gap: 10px;
        min-width: 154px;
        padding: 0 16px;
        text-decoration: none;
    }
    .student-dashboard-topbar .sd-user-card img {
        border-radius: 50%;
        height: 34px;
        object-fit: cover;
        width: 34px;
    }
    .student-dashboard-topbar .sd-user-name,
    .student-dashboard-topbar .sd-user-role {
        display: block;
        line-height: 1.15;
    }
    .student-dashboard-topbar .sd-user-name {
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .student-dashboard-topbar .sd-user-role {
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        opacity: .95;
        text-transform: capitalize;
    }
    .student-360 {
        background: #f5f7fb;
        padding-top: 32px;
    }
    .student-360 .footer,
    .student-360 footer {
        display: none !important;
    }
    .student-360 .student-dashboard-shell {
        align-items: flex-start;
        display: flex;
        gap: 28px;
    }
    .student-360 .student-dashboard-sidebar {
        flex: 0 0 268px;
        position: sticky;
        top: 92px;
        z-index: 2;
    }
    .student-360 .student-dashboard-main {
        flex: 1 1 auto;
        min-width: 0;
    }
    .student-360 .student-dashboard-header {
        align-items: center;
        background: #fff;
        border: 1px solid #edf1f6;
        border-radius: 4px;
        box-shadow: 0 8px 22px rgba(31, 45, 61, .04);
        display: flex;
        justify-content: space-between;
        margin-bottom: 24px;
        padding: 18px 24px;
    }
    .student-360 .student-dashboard-header h4 {
        color: #3f4d67;
        font-size: 18px;
        font-weight: 700;
        margin: 0;
    }
    .student-360 .student-dashboard-breadcrumb {
        color: #98a6ad;
        font-size: 12px;
        margin-top: 4px;
    }
    .student-360 .dashboard-loader {
        align-items: center;
        background: rgba(245, 247, 251, .78);
        border-radius: 4px;
        display: none;
        inset: 0;
        justify-content: center;
        position: absolute;
        z-index: 4;
    }
    .student-360 .dashboard-content-frame {
        min-height: 360px;
        opacity: 1;
        position: relative;
        transition: opacity .18s ease;
    }
    .student-360 .dashboard-content-frame.is-loading {
        opacity: .45;
    }
    .student-360 .dashboard-content-frame.is-loading .dashboard-loader {
        display: flex;
    }
    .student-360 .loader-dot {
        animation: sdPulse 1s infinite ease-in-out;
        background: #727cf5;
        border-radius: 50%;
        height: 10px;
        margin: 0 4px;
        width: 10px;
    }
    .student-360 .loader-dot:nth-child(2) {
        animation-delay: .12s;
    }
    .student-360 .loader-dot:nth-child(3) {
        animation-delay: .24s;
    }
    @keyframes sdPulse {
        0%, 80%, 100% { opacity: .35; transform: scale(.75); }
        40% { opacity: 1; transform: scale(1); }
    }
    .student-360 .container {
        max-width: none;
        padding-left: 48px;
        padding-right: 48px;
    }
    .student-360 .wish-list-search {
        background: #fff;
        border: 1px solid #edf1f6;
        border-radius: 4px;
        box-shadow: 0 8px 22px rgba(31, 45, 61, .04);
        margin-bottom: 0 !important;
        padding: 24px 18px;
        min-height: calc(100vh - 140px);
    }
    .student-360 .student-profile-info {
        border-bottom: 1px solid #edf1f6;
        margin-bottom: 24px;
        padding-bottom: 24px;
        text-align: center;
    }
    .student-360 .student-profile-info .profile-image {
        height: 54px;
        width: 54px;
        object-fit: cover;
    }
    .student-360 .student-profile-info h4 {
        color: #313a46;
        font-size: 14px;
        font-weight: 700;
        margin: 10px 0 2px;
    }
    .student-360 .student-profile-info span {
        color: #98a6ad;
        display: block;
        font-size: 12px;
        word-break: break-word;
    }
    .student-360 .wish-list-course:before {
        color: #6c757d;
        content: "NAVIGATION";
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0 0 14px 6px;
        text-transform: uppercase;
    }
    .student-360 .btn-profile-menu {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 4px;
        color: #53627c;
        display: flex;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 6px;
        padding: 10px 12px;
        width: 100%;
    }
    .student-360 .btn-profile-menu i,
    .student-360 .btn-profile-menu svg {
        color: #53627c;
        min-width: 18px;
    }
    .student-360 .btn-profile-menu:hover,
    .student-360 .btn-profile-menu.active {
        background: #f3f5ff;
        color: #727cf5;
    }
    .student-360 .btn-profile-menu:hover i,
    .student-360 .btn-profile-menu.active i {
        color: #727cf5;
    }
    .student-360 .sd-card {
        background: #fff;
        border: 1px solid #edf1f6;
        border-radius: 4px;
        box-shadow: 0 8px 22px rgba(31, 45, 61, .04);
    }
    .student-360 .sd-hero {
        border-left: 0;
    }
    .student-360 .sd-kicker {
        color: #8a94a6;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: uppercase;
    }
    .student-360 .sd-title {
        color: #3f4d67;
        font-size: 20px;
        font-weight: 700;
    }
    .student-360 .sd-section-title {
        color: #53627c;
        font-size: 15px;
        font-weight: 700;
    }
    .student-360 .sd-metric small {
        color: #8a94a6;
        font-weight: 600;
    }
    .student-360 .sd-metric h3 {
        color: #3f4d67;
        font-size: 24px;
        font-weight: 700;
    }
    .student-360 .btn {
        border-radius: 4px;
        font-weight: 600;
    }
    .student-360 .table th {
        border-top: 0;
        color: #53627c;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .student-360 .table td {
        color: #53627c;
        vertical-align: middle;
    }
    .student-360 .progress {
        background: #e9edf3;
        border-radius: 4px;
    }
    .student-360 .badge {
        border-radius: 4px;
        font-weight: 600;
    }
    .student-360 .sd-empty {
        background: #f8fafc;
        border: 1px dashed #dbe3ef;
        border-radius: 6px;
    }
    .student-360 .sd-modern-panel {
        background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
        border: 1px solid #e5ebf4;
        border-radius: 6px;
    }
    .student-360 .sd-icon-box {
        align-items: center;
        background: #eef5ff;
        border-radius: 6px;
        color: #2f6fed;
        display: inline-flex;
        flex: 0 0 42px;
        font-size: 17px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .student-360 .sd-path-list,
    .student-360 .sd-agenda-list,
    .student-360 .sd-mini-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .student-360 .sd-path-step {
        align-items: flex-start;
        border-bottom: 1px solid #edf1f7;
        display: flex;
        gap: 12px;
        padding: 12px 0;
    }
    .student-360 .sd-path-step:last-child,
    .student-360 .sd-agenda-item:last-child,
    .student-360 .sd-mini-list li:last-child {
        border-bottom: 0;
    }
    .student-360 .sd-step-dot {
        align-items: center;
        background: #eef1f6;
        border-radius: 50%;
        color: #8a94a6;
        display: inline-flex;
        flex: 0 0 26px;
        font-size: 11px;
        height: 26px;
        justify-content: center;
        margin-top: 2px;
        width: 26px;
    }
    .student-360 .sd-path-step.is-done .sd-step-dot {
        background: #e6f6ee;
        color: #1f9d63;
    }
    .student-360 .sd-path-step.is-current .sd-step-dot {
        background: #fff4df;
        color: #c47a00;
    }
    .student-360 .sd-agenda-item,
    .student-360 .sd-mini-list li {
        border-bottom: 1px solid #edf1f7;
        padding: 10px 0;
    }
    .student-360 .sd-goal-meter {
        background: #eef1f6;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
    }
    .student-360 .sd-goal-meter span {
        background: linear-gradient(90deg, #2f6fed, #1f9d63);
        display: block;
        height: 100%;
    }
    .student-360 .sd-ai-prompt {
        background: #f7f9fc;
        border: 1px solid #e7ecf4;
        border-radius: 6px;
        color: #53627c;
        display: block;
        font-size: 13px;
        padding: 10px 12px;
    }
    .student-360 .sd-command-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .student-360 .sd-command-card {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #e7ecf4;
        border-radius: 8px;
        color: #53627c;
        display: flex;
        gap: 12px;
        min-height: 122px;
        padding: 16px;
        text-decoration: none;
        transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .student-360 .sd-command-card:hover {
        border-color: #bfc9ff;
        box-shadow: 0 12px 26px rgba(31, 45, 61, .08);
        color: #313a46;
        text-decoration: none;
        transform: translateY(-1px);
    }
    .student-360 .sd-command-card.is-primary {
        background: #f8f9ff;
        border-color: #ccd4ff;
    }
    .student-360 .sd-command-icon {
        align-items: center;
        background: #eef5ff;
        border-radius: 8px;
        color: #2f6fed;
        display: inline-flex;
        flex: 0 0 42px;
        font-size: 18px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .student-360 .sd-command-card h5 {
        color: #313a46;
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 5px;
    }
    .student-360 .sd-command-card p {
        color: #6c757d;
        font-size: 13px;
        line-height: 1.45;
        margin: 0;
    }
    .student-360 .sd-command-value {
        color: #2f6fed;
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-top: 8px;
    }
    .student-360 .sd-attention-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .student-360 .sd-attention-pill {
        align-items: center;
        background: #fff;
        border: 1px solid #e7ecf4;
        border-radius: 999px;
        color: #53627c;
        display: inline-flex;
        font-size: 13px;
        gap: 8px;
        padding: 8px 12px;
        text-decoration: none;
    }
    .student-360 .sd-attention-pill:hover {
        border-color: #bfc9ff;
        color: #313a46;
        text-decoration: none;
    }
    .student-360 .sd-attention-pill span {
        border-radius: 999px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        min-width: 24px;
        padding: 3px 7px;
        text-align: center;
    }
    .student-360 .sd-tone-primary span { background: #2f6fed; }
    .student-360 .sd-tone-success span { background: #1f9d63; }
    .student-360 .sd-tone-warning span { background: #c47a00; }
    .student-360 .sd-tone-info span { background: #168aad; }
    .student-360 .sd-tone-danger span { background: #d63939; }
    @media (max-width: 991px) {
        .student-dashboard-topbar {
            height: auto;
            padding: 12px 16px;
        }
        .student-dashboard-topbar .sd-topbar-left,
        .student-dashboard-topbar .sd-topbar-right {
            gap: 10px;
        }
        .student-dashboard-topbar .sd-brand {
            font-size: 16px;
        }
        .student-dashboard-topbar .sd-brand img {
            height: 36px;
        }
        .student-dashboard-topbar .sd-visit-site {
            padding: 10px 12px;
        }
        .student-dashboard-topbar .sd-topbar-icon {
            display: none;
        }
        .student-dashboard-topbar .sd-user-card {
            min-width: 0;
            padding: 0 10px;
        }
        .student-dashboard-topbar .sd-user-name {
            max-width: 92px;
        }
        .student-360 .sd-command-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .student-360 .container {
            padding-left: 16px;
            padding-right: 16px;
        }
        .student-360 .student-dashboard-shell {
            display: block;
        }
        .student-360 .student-dashboard-sidebar {
            position: static;
        }
        .student-360 .student-dashboard-header {
            align-items: flex-start;
            display: block;
            padding: 16px;
        }
        .student-360 .wish-list-search {
            min-height: auto;
            margin-bottom: 20px !important;
        }
    }
    @media (max-width: 575px) {
        .student-360 .sd-command-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="student-dashboard-topbar">
    <div class="sd-topbar-left">
        <a class="sd-brand" href="<?php echo site_url('home/student_dashboard'); ?>">
            <img src="<?php echo base_url('uploads/system/' . get_frontend_settings('small_logo')); ?>" alt="Lvalues">
            <span>lvalues</span>
        </a>
        <a class="sd-visit-site" href="<?php echo site_url(); ?>">Visit Website</a>
    </div>
    <div class="sd-topbar-right">
        <span class="sd-topbar-icon" aria-hidden="true"><i class="fas fa-language"></i></span>
        <span class="sd-topbar-icon" aria-hidden="true"><i class="dripicons-view-apps"></i></span>
        <a class="sd-user-card" href="<?php echo site_url('home/student_dashboard#user-profile'); ?>" data-dashboard-section="user-profile" data-dashboard-url="<?php echo site_url('home/profile/user_profile'); ?>">
            <img src="<?php echo html_escape($student_photo); ?>" alt="<?php echo html_escape($student_full_name); ?>">
            <span>
                <span class="sd-user-name"><?php echo html_escape($student_full_name); ?></span>
                <span class="sd-user-role">Student</span>
            </span>
        </a>
    </div>
</div>

<section class="wish-list-body message pb-5 student-360">
    <div class="container">
        <div class="student-dashboard-shell">
            <aside class="student-dashboard-sidebar">
                <?php include 'profile_menus.php'; ?>
            </aside>
            <main class="student-dashboard-main">
                <div class="student-dashboard-header">
                    <div>
                        <h4 id="studentDashboardTitle">Dashboard</h4>
                        <div class="student-dashboard-breadcrumb">Student Dashboard / <span id="studentDashboardCrumb">Dashboard</span></div>
                    </div>
                    <span class="badge bg-light text-dark" id="studentDashboardStatus">Ready</span>
                </div>
                <div id="dashboard-content-frame" class="dashboard-content-frame">
                    <div class="dashboard-loader" aria-live="polite" aria-label="Loading dashboard section">
                        <span class="loader-dot"></span>
                        <span class="loader-dot"></span>
                        <span class="loader-dot"></span>
                    </div>
                    <div id="dashboard-content" data-section-title="Dashboard">
                <div class="common-card sd-card sd-hero p-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div class="mb-2">
                            <p class="sd-kicker mb-1"><?php echo date('d M Y'); ?></p>
                            <h3 class="sd-title mb-1">Welcome back, <?php echo html_escape($user_details['first_name'] ?? 'Student'); ?></h3>
                            <p class="text-muted mb-0">Your classes, courses, assignments, tests, and progress in one place.</p>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end" style="gap: 8px;">
                            <?php if (!empty($continue_courses)): ?>
                                <?php $first_course = $continue_courses[0]['course'] ?? []; ?>
                                <a href="<?php echo site_url('home/lesson/' . slugify($first_course['title'] ?? 'course') . '/' . (int)($first_course['id'] ?? 0)); ?>" class="btn btn-primary btn-sm mt-2">Continue Learning</a>
                            <?php else: ?>
                                <a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-outline-primary btn-sm mt-2">View My Courses</a>
                            <?php endif; ?>
                            <a href="<?php echo site_url('student_batch/export_progress_pdf'); ?>" class="btn btn-outline-secondary btn-sm mt-2">Export Progress PDF</a>
                        </div>
                    </div>
                </div>

                <div class="common-card sd-card p-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="sd-section-title mb-1">Learning command center</h5>
                            <p class="text-muted mb-0">Everything a student needs most often: continue learning, attend class, finish work, practice tests, tutor help, and progress.</p>
                        </div>
                        <a href="<?php echo site_url('home/search?search_for=tutor'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Find a Tutor</a>
                    </div>
                    <div class="sd-command-grid">
                        <?php foreach ($student_command_actions as $action): ?>
                            <a href="<?php echo html_escape($action['url']); ?>" class="sd-command-card <?php echo !empty($action['primary']) ? 'is-primary' : ''; ?>">
                                <span class="sd-command-icon"><i class="<?php echo html_escape($action['icon']); ?>"></i></span>
                                <span>
                                    <h5><?php echo html_escape($action['title']); ?></h5>
                                    <p><?php echo html_escape($action['text']); ?></p>
                                    <span class="sd-command-value"><?php echo html_escape((string)$action['value']); ?> <?php echo html_escape($action['label']); ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($student_command_attention)): ?>
                        <div class="border-top mt-3 pt-3">
                            <div class="sd-attention-list">
                                <?php foreach ($student_command_attention as $item): ?>
                                    <a href="<?php echo html_escape($item['url']); ?>" class="sd-attention-pill sd-tone-<?php echo html_escape($item['tone']); ?>">
                                        <?php echo html_escape($item['label']); ?>
                                        <span><?php echo html_escape((string)$item['value']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-3 col-sm-6 mb-3">
                        <div class="common-card sd-card sd-metric p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Active Courses</small>
                                <i class="fa-solid fa-book-open-reader text-primary"></i>
                            </div>
                            <h3 class="mb-0"><?php echo $active_course_count; ?></h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 mb-3">
                        <div class="common-card sd-card sd-metric p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Active Batches</small>
                                <i class="fa-solid fa-users text-success"></i>
                            </div>
                            <h3 class="mb-0"><?php echo (int)($student_360['active_batch_count'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 mb-3">
                        <div class="common-card sd-card sd-metric p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Today's Classes</small>
                                <i class="fa-regular fa-calendar-check text-info"></i>
                            </div>
                            <h3 class="mb-0"><?php echo (int)($student_360['today_class_count'] ?? 0); ?></h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 mb-3">
                        <div class="common-card sd-card sd-metric p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Pending Work</small>
                                <i class="fa-regular fa-clipboard text-warning"></i>
                            </div>
                            <h3 class="mb-0"><?php echo (int)($student_360['pending_work_count'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>

                <?php if (!$profile_ready): ?>
                    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <div>
                            <strong>Complete your learning profile</strong>
                            <p class="mb-0">Select your category and class so tutors can match you with the right batches.</p>
                        </div>
                        <a href="<?php echo site_url('home/profile/user_profile'); ?>" class="btn btn-outline-dark btn-sm mt-2 mt-md-0">Complete Learning Profile</a>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-lg-4 mb-3">
                        <div class="common-card sd-card sd-modern-panel p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">Today</h5>
                                    <p class="text-muted mb-0">Your immediate learning focus.</p>
                                </div>
                                <span class="sd-icon-box"><i class="fa-regular fa-calendar-check"></i></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Live classes</span>
                                <strong><?php echo $today_class_count; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Pending work</span>
                                <strong><?php echo $pending_work_count; ?></strong>
                            </div>
                            <?php if (!empty($agenda_items)): ?>
                                <small class="text-muted d-block mb-1">Next up</small>
                                <strong><?php echo html_escape($agenda_items[0]['title']); ?></strong>
                                <p class="text-muted mb-3"><?php echo html_escape($agenda_items[0]['type'] . ' - ' . $agenda_items[0]['meta']); ?></p>
                                <a href="<?php echo html_escape($agenda_items[0]['url']); ?>" class="btn btn-outline-primary btn-sm">Open Item</a>
                            <?php else: ?>
                                <p class="text-muted mb-3">No urgent classes or tasks are scheduled right now.</p>
                                <a href="<?php echo site_url('home/courses'); ?>" class="btn btn-outline-primary btn-sm">Explore Courses</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="common-card sd-card sd-modern-panel p-4 h-100">
                            <div class="d-flex align-items-start mb-3" style="gap: 12px;">
                                <span class="sd-icon-box"><i class="<?php echo html_escape($recommended_next_step['icon']); ?>"></i></span>
                                <div>
                                    <h5 class="sd-section-title mb-1">Recommended Next Step</h5>
                                    <p class="text-muted mb-0">Personalized from your current activity.</p>
                                </div>
                            </div>
                            <h6 class="mb-2"><?php echo html_escape($recommended_next_step['title']); ?></h6>
                            <p class="text-muted"><?php echo html_escape($recommended_next_step['body']); ?></p>
                            <a href="<?php echo html_escape($recommended_next_step['url']); ?>" class="btn btn-primary btn-sm"><?php echo html_escape($recommended_next_step['cta']); ?></a>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="common-card sd-card sd-modern-panel p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">AI Study Help</h5>
                                    <p class="text-muted mb-0">Guided prompts for faster revision.</p>
                                </div>
                                <span class="sd-icon-box"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                            </div>
                            <div class="mb-2 sd-ai-prompt">Explain my weakest topic in simple steps.</div>
                            <div class="mb-2 sd-ai-prompt">Create a 20-minute revision plan for today.</div>
                            <div class="mb-3 sd-ai-prompt">Generate practice questions from my latest lesson.</div>
                            <a href="<?php echo site_url('home/contact_us?type=ai-study-help'); ?>" class="btn btn-outline-primary btn-sm">Request Study Help</a>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-7 mb-3">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">My Learning Path</h5>
                                    <p class="text-muted mb-0">A simple progression from onboarding to measurable outcomes.</p>
                                </div>
                                <a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">My Courses</a>
                            </div>
                            <ul class="sd-path-list">
                                <?php $first_open_step_marked = false; ?>
                                <?php foreach ($learning_path_steps as $step): ?>
                                    <?php
                                        $is_done = !empty($step['done']);
                                        $is_current = !$is_done && !$first_open_step_marked;
                                        if ($is_current) {
                                            $first_open_step_marked = true;
                                        }
                                    ?>
                                    <li class="sd-path-step <?php echo $is_done ? 'is-done' : ($is_current ? 'is-current' : ''); ?>">
                                        <span class="sd-step-dot"><i class="fa-solid <?php echo $is_done ? 'fa-check' : ($is_current ? 'fa-arrow-right' : 'fa-circle'); ?>"></i></span>
                                        <span>
                                            <strong><?php echo html_escape($step['label']); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($step['detail']); ?></small>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">Goals & Calendar</h5>
                                    <p class="text-muted mb-0">Weekly progress plus your next agenda.</p>
                                </div>
                                <span class="badge bg-light text-dark"><?php echo $weekly_goal_percent; ?>%</span>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Weekly learning goal</span>
                                    <strong><?php echo $weekly_goal_percent; ?>%</strong>
                                </div>
                                <div class="sd-goal-meter mt-2"><span style="width: <?php echo $weekly_goal_percent; ?>%;"></span></div>
                            </div>
                            <?php if (!empty($agenda_items)): ?>
                                <ul class="sd-agenda-list">
                                    <?php foreach ($agenda_items as $agenda): ?>
                                        <li class="sd-agenda-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="badge bg-light text-dark me-1"><?php echo html_escape($agenda['type']); ?></span>
                                                    <strong><?php echo html_escape($agenda['title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo html_escape($agenda['meta']); ?></small>
                                                </div>
                                                <a href="<?php echo html_escape($agenda['url']); ?>" class="btn btn-link btn-sm p-0">Open</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-regular fa-calendar d-block mb-2" style="font-size: 28px;"></i>
                                    <h6 class="mb-1">Calendar is clear</h6>
                                    <p class="mb-0">Upcoming sessions, assignments, and tests will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-7 mb-3">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">Recommended Courses & Tutors</h5>
                                    <p class="text-muted mb-0">Next learning options for skills, school support, and career goals.</p>
                                </div>
                                <a href="<?php echo site_url('home/search?search_for=tutor'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Find Tutors</a>
                            </div>
                            <?php if (!empty($recommended_courses)): ?>
                                <div class="row">
                                    <?php foreach ($recommended_courses as $course): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="border rounded p-3 h-100">
                                                <strong><?php echo html_escape($course['title'] ?? 'Course'); ?></strong>
                                                <p class="text-muted mb-2"><?php echo html_escape(ucfirst($course['level'] ?? 'All levels')); ?></p>
                                                <a href="<?php echo site_url('home/course/' . slugify($course['title'] ?? 'course') . '/' . (int)($course['id'] ?? 0)); ?>" class="btn btn-outline-primary btn-sm">View Course</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-solid fa-magnifying-glass d-block mb-2" style="font-size: 28px;"></i>
                                    <h6 class="mb-1">Recommendations are being prepared</h6>
                                    <p class="mb-3">Browse the catalog while your profile signals build up.</p>
                                    <a href="<?php echo site_url('home/courses'); ?>" class="btn btn-outline-primary btn-sm">Browse Catalog</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <div class="common-card sd-card p-4 h-100">
                            <h5 class="sd-section-title mb-3">Certificates, Notes & Bookmarks</h5>
                            <ul class="sd-mini-list">
                                <li>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>Certificates</strong><br>
                                            <small class="text-muted"><?php echo $certificate_count; ?> issued, <?php echo $completed_course_count; ?> completed courses</small>
                                        </div>
                                        <a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-link btn-sm p-0">View</a>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>Notes</strong><br>
                                            <small class="text-muted">Use teacher remarks and batch updates as your study notes hub.</small>
                                        </div>
                                        <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-link btn-sm p-0">Open</a>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>Bookmarks</strong><br>
                                            <small class="text-muted"><?php echo count($wishlist_items); ?> saved courses. Tap the heart icon on any course card to add more.</small>
                                        </div>
                                        <a href="<?php echo site_url('home/my_wishlist'); ?>" class="btn btn-link btn-sm p-0">Open</a>
                                    </div>
                                </li>
                            </ul>
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>Save courses to Wishlist</strong>
                                        <p class="text-muted mb-0">Browse courses and use the heart icon on course cards or course details to save them here.</p>
                                    </div>
                                    <a href="<?php echo site_url('home/courses'); ?>" class="btn btn-outline-primary btn-sm">Browse Courses</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($pending_invites)): ?>
                    <div class="common-card sd-card p-4 mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="sd-section-title mb-1">Pending Batch Invites</h5>
                                <p class="text-muted mb-0">Accept a batch invite to start attending classes and receiving assignments.</p>
                            </div>
                            <a href="<?php echo site_url('student_batch/invites'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">View all invites</a>
                        </div>
                        <div class="row">
                            <?php foreach (array_slice($pending_invites, 0, 2) as $invite): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="border rounded p-3 h-100">
                                        <strong><?php echo html_escape($invite['batch_title'] ?? 'Batch invite'); ?></strong><br>
                                        <small class="text-muted"><?php echo html_escape(trim(($invite['tutor_first_name'] ?? '') . ' ' . ($invite['tutor_last_name'] ?? ''))); ?></small>
                                        <div class="mt-3">
                                            <form method="post" class="d-inline" action="<?php echo site_url('student_batch/respond/' . ($invite['invite_token'] ?? '') . '/accepted'); ?>"><button class="btn btn-success btn-sm">Accept Invite</button></form>
                                            <a href="<?php echo site_url('student_batch/invites'); ?>" class="btn btn-outline-secondary btn-sm">Review</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="sd-section-title mb-0">Today's Live Sessions & Upcoming Sessions</h5>
                                <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-outline-primary btn-sm">My Batches</a>
                            </div>
                            <?php if (!empty($today_sessions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead><tr><th>Time</th><th>Session</th><th>Batch</th><th>Action</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($today_sessions as $session): ?>
                                                <?php
                                                    $meeting_url = $session['meeting_url'] ?? ($session['student_join_url'] ?? '');
                                                    $session_date = trim((string)($session['session_date'] ?? date('Y-m-d')));
                                                    $start_time_value = trim((string)($session['start_time'] ?? ''));
                                                    $end_time_value = trim((string)($session['end_time'] ?? ''));
                                                    $start = strtotime(trim($session_date . ' ' . $start_time_value));
                                                    $end = strtotime(trim($session_date . ' ' . $end_time_value));
                                                    $now = time();
                                                    $can_join = !empty($meeting_url)
                                                        && $start
                                                        && $end
                                                        && $now >= ($start - (10 * 60))
                                                        && $now <= ($end + (15 * 60))
                                                        && (($session['session_status'] ?? 'scheduled') !== 'cancelled');
                                                ?>
                                                <tr>
                                                    <td><?php echo html_escape(substr((string)($session['start_time'] ?? ''), 0, 5)); ?></td>
                                                    <td>
                                                        <strong><?php echo html_escape($session['title'] ?? 'Session'); ?></strong><br>
                                                        <small class="text-muted"><?php echo html_escape(trim(($session['tutor_first_name'] ?? '') . ' ' . ($session['tutor_last_name'] ?? ''))); ?></small>
                                                    </td>
                                                    <td><?php echo html_escape($session['batch_title'] ?? 'Batch'); ?></td>
                                                    <td>
                                                        <?php if ($can_join): ?>
                                                            <a href="<?php echo site_url('student_batch/join_session/' . (int)$session['id']); ?>" class="btn btn-success btn-sm">Join Class</a>
                                                        <?php elseif (empty($meeting_url)): ?>
                                                            <button type="button" class="btn btn-light btn-sm" disabled title="Join link is not available yet.">Link Pending</button>
                                                            <a href="<?php echo site_url('student_batch/view/' . (int)$session['batch_id']); ?>" class="btn btn-outline-primary btn-sm">View</a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-light btn-sm" disabled title="Join opens 10 minutes before class time.">Not Live</button>
                                                            <a href="<?php echo site_url('student_batch/view/' . (int)$session['batch_id']); ?>" class="btn btn-outline-primary btn-sm">View</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php elseif (!empty($upcoming_sessions)): ?>
                                <p class="text-muted mb-3">No classes today. Your next scheduled sessions are below.</p>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach (array_slice($upcoming_sessions, 0, 4) as $session): ?>
                                        <li class="border-bottom py-2">
                                            <strong><?php echo html_escape($session['title'] ?? 'Session'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($session['session_date'] ?? ''); ?>, <?php echo html_escape(substr((string)($session['start_time'] ?? ''), 0, 5)); ?> - <?php echo html_escape($session['batch_title'] ?? 'Batch'); ?></small>
                                            <div class="mt-2"><a href="<?php echo site_url('student_batch/view/' . (int)$session['batch_id']); ?>" class="btn btn-outline-primary btn-sm">View Upcoming Class</a></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-regular fa-calendar-check d-block mb-2" style="font-size: 28px;"></i>
                                    <h6 class="mb-1">No classes today</h6>
                                    <p class="mb-3">When your tutor schedules a session, it will appear here with a join action.</p>
                                    <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-outline-primary btn-sm">View My Batches</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <h5 class="sd-section-title mb-3">Progress Snapshot</h5>
                            <?php
                                $progress_items = [
                                    ['label' => 'Overall', 'value' => (float)($summary['overall_percent'] ?? 0), 'class' => 'bg-primary'],
                                    ['label' => 'Attendance', 'value' => (float)($summary['attendance_percent'] ?? 0), 'class' => 'bg-success'],
                                    ['label' => 'Assignments', 'value' => (float)($summary['assignment_percent'] ?? 0), 'class' => 'bg-info'],
                                    ['label' => 'Average test score', 'value' => (float)($summary['test_percent'] ?? 0), 'class' => 'bg-warning'],
                                ];
                            ?>
                            <?php foreach ($progress_items as $item): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo html_escape($item['label']); ?></span>
                                        <strong><?php echo html_escape((string)$item['value']); ?>%</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;" role="progressbar" aria-valuenow="<?php echo $item['value']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar <?php echo $item['class']; ?>" style="width: <?php echo $item['value']; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="sd-section-title mb-0">Continue Learning</h5>
                                <a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-outline-primary btn-sm">All Courses</a>
                            </div>
                            <?php if (!empty($continue_courses)): ?>
                                <?php foreach ($continue_courses as $item): ?>
                                    <?php $course = $item['course'] ?? []; ?>
                                    <div class="border rounded p-3 mb-2">
                                        <strong><?php echo html_escape($course['title'] ?? 'Course'); ?></strong>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                                <div class="progress-bar" style="width: <?php echo (float)($item['progress'] ?? 0); ?>%;"></div>
                                            </div>
                                            <small><?php echo html_escape((string)($item['progress'] ?? 0)); ?>%</small>
                                        </div>
                                        <a href="<?php echo site_url('home/lesson/' . slugify($course['title'] ?? 'course') . '/' . (int)($course['id'] ?? 0)); ?>" class="btn btn-primary btn-sm mt-2">Continue Course</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-solid fa-book-open-reader d-block mb-2" style="font-size: 28px;"></i>
                                    <?php if ($active_course_count > 0): ?>
                                        <h6 class="mb-1">All courses are completed or ready</h6>
                                        <p class="mb-3">Open My Courses to review completed lessons or start another enrolled course.</p>
                                        <a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-outline-primary btn-sm">Open My Courses</a>
                                    <?php else: ?>
                                        <h6 class="mb-1">No enrolled courses yet</h6>
                                        <p class="mb-3">Explore courses and enroll to start learning.</p>
                                        <a href="<?php echo site_url('home/courses'); ?>" class="btn btn-outline-primary btn-sm">Explore Courses</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <h5 class="sd-section-title mb-3">Pending Assignments & Tests</h5>
                            <?php if (!empty($pending_assignments) || !empty($pending_tests)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($pending_assignments as $task): ?>
                                        <li class="border-bottom py-2">
                                            <span class="badge bg-info me-1">Assignment</span>
                                            <strong><?php echo html_escape($task['title'] ?? 'Assignment'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($task['batch_title'] ?? 'Batch'); ?><?php echo !empty($task['due_at']) ? ' - Due ' . html_escape($task['due_at']) : ''; ?></small>
                                            <div><a href="<?php echo site_url('student_batch/view/' . (int)$task['batch_id']); ?>" class="btn btn-outline-primary btn-sm mt-2">Submit Assignment</a></div>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php foreach ($pending_tests as $test): ?>
                                        <li class="border-bottom py-2">
                                            <span class="badge bg-warning text-dark me-1">Test</span>
                                            <strong><?php echo html_escape($test['title'] ?? 'Test'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($test['batch_title'] ?? 'Batch'); ?></small>
                                            <div><a href="<?php echo site_url('student_batch/start_test/' . (int)$test['id']); ?>" class="btn btn-outline-primary btn-sm mt-2">Attempt Test</a></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-regular fa-circle-check d-block mb-2" style="font-size: 28px;"></i>
                                    <h6 class="mb-1">No pending work</h6>
                                    <p class="mb-3">You are clear for now. New assignments and tests will appear here.</p>
                                    <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-outline-primary btn-sm">Review Batches</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="common-card sd-card p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="sd-section-title mb-1">Learning Insights</h5>
                                    <p class="text-muted mb-0">Signals based on attendance, assignment completion, and test scores.</p>
                                </div>
                                <span class="badge bg-light text-dark">Consistency: <?php echo html_escape((string)($learning_insights['learning_consistency'] ?? 0)); ?>%</span>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Weak areas</strong>
                                            <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                                        </div>
                                        <ul class="mb-0 pl-3">
                                            <?php foreach ($weak_areas as $area): ?>
                                                <li><?php echo html_escape($area); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Strong areas</strong>
                                            <i class="fa-solid fa-circle-check text-success"></i>
                                        </div>
                                        <ul class="mb-0 pl-3">
                                            <?php foreach ($strong_areas as $area): ?>
                                                <li><?php echo html_escape($area); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-lg-4 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Missed classes</strong>
                                            <span class="badge bg-warning text-dark"><?php echo (int)($learning_insights['missed_class_count'] ?? 0); ?></span>
                                        </div>
                                        <?php if (!empty($missed_classes)): ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach (array_slice($missed_classes, 0, 3) as $missed): ?>
                                                    <li class="border-bottom py-2">
                                                        <strong><?php echo html_escape($missed['title'] ?? 'Session'); ?></strong><br>
                                                        <small class="text-muted"><?php echo html_escape($missed['batch_title'] ?? 'Batch'); ?> - <?php echo html_escape($missed['session_date'] ?? ''); ?> <?php echo html_escape(substr((string)($missed['start_time'] ?? ''), 0, 5)); ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No missed classes recorded.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <h5 class="sd-section-title mb-3">Batch-wise Progress</h5>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Batch</th><th>Tutor</th><th>Attendance</th><th>Assignments</th><th>Tests</th><th>Overall</th><th>Trend</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($batch_progress as $item): ?>
                                            <?php
                                                $batch = $item['batch'] ?? [];
                                                $item_summary = $item['summary'] ?? [];
                                                $trend = $item['test_trend'] ?? [];
                                                $trend_direction = $trend['direction'] ?? 'neutral';
                                                $trend_class = $trend_direction === 'up' ? 'bg-success' : ($trend_direction === 'down' ? 'bg-danger' : 'bg-secondary');
                                            ?>
                                            <tr>
                                                <td><strong><?php echo html_escape($batch['batch_title'] ?? 'Batch'); ?></strong></td>
                                                <td><?php echo html_escape(trim(($batch['tutor_first_name'] ?? '') . ' ' . ($batch['tutor_last_name'] ?? ''))); ?></td>
                                                <td><?php echo html_escape((string)($item_summary['attendance_percent'] ?? 0)); ?>%</td>
                                                <td><?php echo html_escape((string)($item_summary['assignment_percent'] ?? 0)); ?>%</td>
                                                <td><?php echo html_escape((string)($item_summary['test_percent'] ?? 0)); ?>%</td>
                                                <td><?php echo html_escape((string)($item_summary['overall_percent'] ?? 0)); ?>%</td>
                                                <td><span class="badge <?php echo $trend_class; ?>"><?php echo html_escape($trend['label'] ?? 'No trend'); ?></span></td>
                                                <td><a href="<?php echo site_url('student_batch/view/' . (int)($batch['batch_id'] ?? 0)); ?>" class="btn btn-outline-primary btn-sm">View</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($batch_progress)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <h6 class="mb-1">No active batches yet</h6>
                                                    <p class="mb-3">Accept an invite or ask a tutor to add you to a batch.</p>
                                                    <a href="<?php echo site_url('student_batch/invites'); ?>" class="btn btn-outline-primary btn-sm">Check Invites</a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <h5 class="sd-section-title mb-3">Latest Teacher Remarks</h5>
                            <?php if (!empty($latest_teacher_remarks)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($latest_teacher_remarks as $remark): ?>
                                        <li class="border-bottom py-3">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap">
                                                <div class="mb-2">
                                                    <strong><?php echo html_escape($remark['task_title'] ?? 'Assignment'); ?></strong><br>
                                                    <small class="text-muted"><?php echo html_escape($remark['batch_title'] ?? 'Batch'); ?> - <?php echo html_escape($remark['evaluated_at'] ?? ''); ?></small>
                                                </div>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo html_escape((string)($remark['marks_obtained'] ?? 0)); ?> / <?php echo html_escape((string)($remark['max_marks'] ?? 0)); ?>
                                                </span>
                                            </div>
                                            <p class="mb-0"><?php echo html_escape($remark['tutor_remarks'] ?? ''); ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center text-muted sd-empty py-4">
                                    <i class="fa-regular fa-comment-dots d-block mb-2" style="font-size: 28px;"></i>
                                    <h6 class="mb-1">No teacher remarks yet</h6>
                                    <p class="mb-0">Remarks will appear after your tutor evaluates submitted assignments.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-4">
                        <div class="common-card sd-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="sd-section-title mb-0">Latest Notifications</h5>
                                <a href="<?php echo site_url('notifications'); ?>" class="btn btn-outline-primary btn-sm"><?php echo (int)($student_360['unread_notifications'] ?? 0); ?> unread</a>
                            </div>
                            <?php if (!empty($latest_notifications)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($latest_notifications as $notification): ?>
                                        <li class="border-bottom py-2">
                                            <strong><?php echo html_escape($notification['title'] ?? 'Notification'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($notification['created_at'] ?? ''); ?></small>
                                            <?php if (!empty($notification['target_url'])): ?>
                                                <div><a href="<?php echo html_escape($notification['target_url']); ?>" class="btn btn-link btn-sm p-0">Open</a></div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No notifications yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>
<script>
(function () {
    var shell = document.querySelector('.student-360');
    if (!shell) {
        return;
    }

    var content = document.getElementById('dashboard-content');
    var frame = document.getElementById('dashboard-content-frame');
    var titleEl = document.getElementById('studentDashboardTitle');
    var crumbEl = document.getElementById('studentDashboardCrumb');
    var statusEl = document.getElementById('studentDashboardStatus');
    var dashboardUrl = <?php echo json_encode(site_url('home/student_dashboard')); ?>;
    var profileUrl = <?php echo json_encode(site_url('home/profile/user_profile')); ?>;
    var cache = {
        dashboard: content ? content.innerHTML : ''
    };
    var labels = {
        dashboard: 'Dashboard',
        'user-profile': 'User Profile'
    };
    var urls = {
        dashboard: dashboardUrl,
        'user-profile': profileUrl
    };

    function normalizeText(text) {
        return (text || '').replace(/\s+/g, ' ').trim();
    }

    function slugify(text) {
        return normalizeText(text).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'dashboard';
    }

    function sectionFromLink(link) {
        if (!link) {
            return 'dashboard';
        }
        var label = normalizeText(link.textContent);
        var href = link.getAttribute('href') || dashboardUrl;
        if (href.indexOf('student_dashboard') !== -1 || label.toLowerCase() === 'dashboard') {
            return 'dashboard';
        }
        return slugify(label);
    }

    function registerMenuLinks() {
        shell.querySelectorAll('.btn-profile-menu[href]').forEach(function (link) {
            var section = sectionFromLink(link);
            link.setAttribute('data-dashboard-section', section);
            var dashboardSectionUrl = link.href;
            if (section === 'batch-invites' && dashboardSectionUrl.indexOf('dashboard=1') === -1) {
                dashboardSectionUrl += (dashboardSectionUrl.indexOf('?') === -1 ? '?' : '&') + 'dashboard=1';
            }
            link.setAttribute('data-dashboard-url', dashboardSectionUrl);
            labels[section] = normalizeText(link.textContent) || 'Dashboard';
            urls[section] = dashboardSectionUrl;
        });
    }

    function setChrome(section, loading) {
        var label = labels[section] || 'Dashboard';
        if (titleEl) {
            titleEl.textContent = label;
        }
        if (crumbEl) {
            crumbEl.textContent = label;
        }
        if (statusEl) {
            statusEl.textContent = loading ? 'Loading' : 'Ready';
        }
        shell.querySelectorAll('.btn-profile-menu').forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('data-dashboard-section') === section);
        });
    }

    function setLoading(isLoading) {
        if (frame) {
            frame.classList.toggle('is-loading', isLoading);
        }
    }

    function extractContent(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var selectors = [
            '#dashboard-content',
            '.wish-list-body .col-lg-9.col-md-8',
            '.wish-list-body .col-lg-9',
            '.wish-list-body .col-md-8',
            '.grid-view .col-lg-10',
            '.grid-view .container',
            '.message .container'
        ];
        var node = null;
        for (var i = 0; i < selectors.length; i++) {
            node = doc.querySelector(selectors[i]);
            if (node) {
                break;
            }
        }
        if (!node) {
            node = doc.body;
        }
        node.querySelectorAll('.col-lg-3.col-md-4, .wish-list-search, .breadcrumb, .footer, footer, script').forEach(function (el) {
            el.remove();
        });
        return node.innerHTML;
    }

    function runEmbeddedScripts(container) {
        container.querySelectorAll('script').forEach(function (oldScript) {
            var script = document.createElement('script');
            Array.prototype.slice.call(oldScript.attributes).forEach(function (attr) {
                script.setAttribute(attr.name, attr.value);
            });
            script.text = oldScript.text || oldScript.textContent || '';
            oldScript.parentNode.replaceChild(script, oldScript);
        });
    }

    function loadSection(section, pushHistory) {
        section = section || 'dashboard';
        registerMenuLinks();
        setChrome(section, true);

        if (cache[section]) {
            content.innerHTML = cache[section];
            runEmbeddedScripts(content);
            setLoading(false);
            setChrome(section, false);
            if (pushHistory) {
                history.pushState({ section: section }, labels[section] || 'Dashboard', '#' + section);
            }
            return;
        }

        var url = urls[section];
        if (!url) {
            setLoading(false);
            setChrome('dashboard', false);
            return;
        }

        setLoading(true);
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load section');
                }
                return response.text();
            })
            .then(function (html) {
                var extracted = extractContent(html);
                cache[section] = extracted;
                content.innerHTML = extracted;
                runEmbeddedScripts(content);
                registerMenuLinks();
                setChrome(section, false);
                if (pushHistory) {
                    history.pushState({ section: section }, labels[section] || 'Dashboard', '#' + section);
                }
            })
            .catch(function () {
                content.innerHTML = '<div class="common-card sd-card p-4 text-center text-muted"><h5 class="mb-2">Section could not be loaded</h5><p class="mb-3">Please try again, or open the section directly.</p><a class="btn btn-outline-primary btn-sm" href="' + url + '">Open Section</a></div>';
                setChrome(section, false);
            })
            .finally(function () {
                setLoading(false);
            });
    }

    registerMenuLinks();

    function handleDashboardLink(event) {
        var link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        var href = link.getAttribute('href') || '';
        if (href.indexOf('login/logout') !== -1 || href.indexOf('javascript:') === 0) {
            return;
        }
        var section = link.getAttribute('data-dashboard-section') || '';
        if (!section) {
            Object.keys(urls).some(function (key) {
                if (urls[key] === link.href) {
                    section = key;
                    return true;
                }
                return false;
            });
        }
        if (!section) {
            return;
        }
        event.preventDefault();
        loadSection(section, true);
    }

    shell.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || !shell.contains(link)) {
            return;
        }
        handleDashboardLink(event);
    });

    var topbar = document.querySelector('.student-dashboard-topbar');
    if (topbar) {
        topbar.addEventListener('click', handleDashboardLink);
    }

    window.addEventListener('popstate', function () {
        var section = (location.hash || '#dashboard').replace('#', '') || 'dashboard';
        loadSection(section, false);
    });

    var initialSection = (location.hash || '#dashboard').replace('#', '') || 'dashboard';
    setChrome(initialSection, false);
    if (initialSection !== 'dashboard') {
        loadSection(initialSection, false);
    }
})();
</script>
