<?php
    $instructor_id = $this->session->userdata('user_id');
    $number_of_courses = $this->crud_model->get_instructor_wise_courses($instructor_id)->num_rows();
    $number_of_enrolment_result = $this->crud_model->instructor_wise_enrolment($instructor_id);
    if ($number_of_enrolment_result) {
        $number_of_enrolment = $number_of_enrolment_result->num_rows();
    } else {
        $number_of_enrolment = 0;
    }
    $total_pending_amount = $this->crud_model->get_total_pending_amount($instructor_id);
    $requested_withdrawal_amount = $this->crud_model->get_requested_withdrawal_amount($instructor_id);
    $teacher_360 = isset($teacher_360) && is_array($teacher_360) ? $teacher_360 : [];
    $teacher_360_earnings = isset($teacher_360_earnings) && is_array($teacher_360_earnings) ? $teacher_360_earnings : [];
    $todays_classes = isset($teacher_360['todays_classes']) && is_array($teacher_360['todays_classes']) ? $teacher_360['todays_classes'] : [];
    $pending_grading_items = isset($teacher_360['pending_grading']) && is_array($teacher_360['pending_grading']) ? $teacher_360['pending_grading'] : [];
    $demo_followups = isset($teacher_360['demo_followups']) && is_array($teacher_360['demo_followups']) ? $teacher_360['demo_followups'] : [];
    $batch_health_items = isset($teacher_360['batch_health']) && is_array($teacher_360['batch_health']) ? $teacher_360['batch_health'] : [];
    $teacher_pending_amount = isset($teacher_360_earnings['pending']) ? (float)$teacher_360_earnings['pending'] : (float)$total_pending_amount;
    $teacher_requested_amount = isset($teacher_360_earnings['requested']) ? (float)$teacher_360_earnings['requested'] : (float)$requested_withdrawal_amount;
    $teacher_paid_amount = isset($teacher_360_earnings['paid']) ? (float)$teacher_360_earnings['paid'] : 0;
    $teacher_agenda = isset($teacher_agenda) && is_array($teacher_agenda) ? $teacher_agenda : [];
    $teacher_calendar = isset($teacher_calendar) && is_array($teacher_calendar) ? $teacher_calendar : [];
    $teacher_command_center = isset($teacher_command_center) && is_array($teacher_command_center) ? $teacher_command_center : [];
    $teacher_metrics = isset($teacher_command_center['metrics']) && is_array($teacher_command_center['metrics']) ? $teacher_command_center['metrics'] : [];
    $teacher_actions = isset($teacher_command_center['actions']) && is_array($teacher_command_center['actions']) ? $teacher_command_center['actions'] : [];
    $teacher_attention = isset($teacher_command_center['attention']) && is_array($teacher_command_center['attention']) ? $teacher_command_center['attention'] : [];
?>

<style>
.lv-teacher-shell .card{border-radius:8px;}
.lv-teacher-hero{background:#ffffff;border:1px solid #e7ecf3;border-radius:8px;padding:22px 24px;box-shadow:0 8px 26px rgba(15,23,42,.05);}
.lv-teacher-kicker{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#727cf5;margin-bottom:8px;}
.lv-teacher-title{font-size:24px;line-height:1.25;font-weight:700;color:#263238;margin-bottom:6px;}
.lv-teacher-subtitle{font-size:14px;color:#6c757d;margin-bottom:0;max-width:760px;}
.lv-teacher-metric{border-left:1px solid #eef2f6;padding:4px 0 4px 22px;min-width:128px;}
.lv-teacher-metric strong{display:block;font-size:22px;line-height:1.1;color:#263238;}
.lv-teacher-metric span{font-size:12px;color:#7b8492;}
.lv-teacher-action{display:block;height:100%;border:1px solid #e7ecf3;border-radius:8px;padding:16px 16px 14px;color:#3f4854;background:#fff;transition:box-shadow .16s ease,transform .16s ease,border-color .16s ease;}
.lv-teacher-action:hover{color:#263238;border-color:#cfd8ff;box-shadow:0 10px 24px rgba(15,23,42,.08);transform:translateY(-1px);text-decoration:none;}
.lv-teacher-action-icon{width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#eef2ff;color:#5369f8;font-size:22px;flex:0 0 42px;}
.lv-teacher-action h5{font-size:15px;margin:0 0 5px;color:#263238;}
.lv-teacher-action p{font-size:13px;line-height:1.45;color:#6c757d;margin:0;}
.lv-teacher-action-meta{font-size:12px;font-weight:700;color:#5369f8;white-space:nowrap;}
.lv-teacher-flow{display:flex;flex-wrap:wrap;gap:10px;}
.lv-teacher-flow a{display:flex;align-items:center;gap:8px;border:1px solid #e7ecf3;border-radius:8px;padding:10px 12px;color:#4b5563;background:#fff;min-height:42px;}
.lv-teacher-flow a:hover{text-decoration:none;border-color:#cfd8ff;color:#263238;}
.lv-teacher-flow .badge{min-width:24px;}
.lv-teacher-attention{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid #eef2f6;padding:10px 0;}
.lv-teacher-attention:last-child{border-bottom:0;}
.lv-teacher-empty{border:1px dashed #cfd8e3;border-radius:8px;padding:18px;text-align:center;color:#7b8492;background:#fbfcff;}
@media(max-width:767.98px){.lv-teacher-hero{padding:18px}.lv-teacher-metric{border-left:0;border-top:1px solid #eef2f6;padding:14px 0 0;margin-top:14px}.lv-teacher-title{font-size:21px}}
</style>

<div class="lv-teacher-shell">
    <div class="row">
        <div class="col-12">
            <div class="lv-teacher-hero mb-3">
                <div class="d-flex align-items-start justify-content-between flex-wrap">
                    <div class="mb-3 mb-xl-0">
                        <div class="lv-teacher-kicker">Tutor workspace</div>
                        <h3 class="lv-teacher-title">Plan, publish, assess, and track students from one place.</h3>
                        <p class="lv-teacher-subtitle">Your daily teaching flow is organized around the work a tutor does most: create learning material, prepare questions, build tests, run batches, and review student progress.</p>
                    </div>
                    <div class="d-flex flex-wrap">
                        <div class="lv-teacher-metric">
                            <strong><?php echo (int)($teacher_metrics['students'] ?? 0); ?></strong>
                            <span>active students</span>
                        </div>
                        <div class="lv-teacher-metric">
                            <strong><?php echo (int)($teacher_metrics['questions_ready'] ?? 0); ?></strong>
                            <span>ready questions</span>
                        </div>
                        <div class="lv-teacher-metric">
                            <strong><?php echo (int)($teacher_metrics['exams_published'] ?? 0); ?></strong>
                            <span>published tests</span>
                        </div>
                        <div class="lv-teacher-metric">
                            <strong><?php echo (int)($teacher_metrics['upcoming_sessions'] ?? 0); ?></strong>
                            <span>upcoming sessions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php foreach ($teacher_actions as $action): ?>
            <div class="col-xl-4 col-md-6 mb-3">
                <a href="<?php echo $action['url']; ?>" class="lv-teacher-action" aria-label="<?php echo html_escape($action['title']); ?>">
                    <div class="d-flex align-items-start">
                        <div class="lv-teacher-action-icon mr-3">
                            <i class="mdi <?php echo html_escape($action['icon']); ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5><?php echo html_escape($action['title']); ?></h5>
                                <span class="lv-teacher-action-meta"><?php echo (int)$action['count']; ?> <?php echo html_escape($action['count_label']); ?></span>
                            </div>
                            <p><?php echo html_escape($action['text']); ?></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                        <div>
                            <h4 class="header-title mb-1">Teaching workflow</h4>
                        <p class="text-muted mb-0">Use this order when you are preparing a book, class, or new learning plan.</p>
                        </div>
                        <a href="<?php echo site_url('teacher-workspace'); ?>" class="btn btn-outline-primary btn-sm">Open full workspace</a>
                    </div>
                    <div class="lv-teacher-flow">
                        <a href="<?php echo site_url('user/content_nodes?section=book'); ?>"><span class="badge badge-primary-lighten">1</span> Create book/notes</a>
                        <a href="<?php echo site_url('user/question_bank?qb_tab=create_exam'); ?>"><span class="badge badge-success-lighten">2</span> Add questions</a>
                        <a href="<?php echo site_url('user/content_public_exams'); ?>"><span class="badge badge-warning-lighten">3</span> Build mock/real test</a>
                        <a href="<?php echo site_url('tutor_batch/create'); ?>"><span class="badge badge-info-lighten">4</span> Create batch</a>
                        <a href="<?php echo site_url('teacher-analytics'); ?>"><span class="badge badge-dark-lighten">5</span> Track reports</a>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-3 col-6 mb-2">
                            <small class="text-muted d-block">Books</small>
                            <strong><?php echo (int)($teacher_metrics['books'] ?? 0); ?></strong>
                        </div>
                        <div class="col-sm-3 col-6 mb-2">
                            <small class="text-muted d-block">Pages</small>
                            <strong><?php echo (int)($teacher_metrics['pages'] ?? 0); ?></strong>
                        </div>
                        <div class="col-sm-3 col-6 mb-2">
                            <small class="text-muted d-block">Batches</small>
                            <strong><?php echo (int)($teacher_metrics['active_batches'] ?? 0); ?> active</strong>
                        </div>
                        <div class="col-sm-3 col-6 mb-2">
                            <small class="text-muted d-block">Reports</small>
                            <strong><?php echo (int)($teacher_metrics['exam_attempts'] ?? 0); ?> attempts</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="header-title mb-1">Needs attention</h4>
                    <p class="text-muted mb-3">The next useful action based on your current setup.</p>
                    <?php if (!empty($teacher_attention)): ?>
                        <?php foreach ($teacher_attention as $item): ?>
                            <a href="<?php echo $item['url']; ?>" class="lv-teacher-attention text-secondary">
                                <span><?php echo html_escape($item['label']); ?></span>
                                <span class="badge badge-<?php echo html_escape($item['tone']); ?>-lighten"><?php echo html_escape((string)$item['value']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="lv-teacher-empty">No urgent setup gaps. Keep publishing lessons and reviewing student progress.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3"><div><h4 class="header-title mb-1">Daily Workspace</h4><p class="text-muted mb-0">Your highest-priority teaching actions.</p></div><a href="<?php echo site_url('teacher-workspace'); ?>" class="btn btn-primary btn-sm">Open full workspace</a></div>
        <div class="row">
            <div class="col-lg-7"><?php foreach(array_slice($teacher_agenda,0,5) as $item):?><div class="border-bottom py-2 d-flex justify-content-between"><div><span class="badge badge-warning-lighten"><?php echo html_escape(ucfirst(str_replace('_',' ',$item['item_type'])));?></span> <strong><?php echo html_escape($item['title']);?></strong><br><small class="text-muted"><?php echo $item['due_at']?html_escape(date('d M, h:i A',strtotime($item['due_at']))):'No due time';?></small></div><?php if(!empty($item['action_url'])):?><a href="<?php echo html_escape($item['action_url']);?>" class="btn btn-outline-primary btn-sm align-self-center">Open</a><?php endif;?></div><?php endforeach;?><?php if(!$teacher_agenda):?><p class="text-muted">No pending agenda items.</p><?php endif;?></div>
            <div class="col-lg-5"><?php foreach(array_slice($teacher_calendar,0,4) as $event):?><div class="border rounded p-2 mb-2"><strong><?php echo html_escape($event['title']);?></strong><br><small class="text-muted"><?php echo html_escape($event['session_date'].' '.substr($event['start_time'],0,5).' · '.$event['timezone']);?></small></div><?php endforeach;?></div>
        </div>
    </div></div></div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title">
                    <i class="mdi mdi-apple-keyboard-command title_icon"></i>
                    <?php echo get_phrase('dashboard'); ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- phase_f_teacher_360_foundation -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1">Teacher 360 Today</h4>
                        <p class="text-muted mb-0">A lightweight operating snapshot using existing classes, grading, batches and payout data.</p>
                    </div>
                    <span class="badge badge-info-lighten mt-1"><?php echo date('d M Y'); ?></span>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong>Today's classes</strong>
                                    <i class="mdi mdi-calendar-today text-primary"></i>
                                </div>
                                <div class="h3 mb-1"><?php echo (int)($teacher_360['todays_class_count'] ?? count($todays_classes)); ?></div>
                                <p class="text-muted mb-0">Scheduled/live sessions today</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong>Pending grading</strong>
                                    <i class="mdi mdi-clipboard-check-outline text-warning"></i>
                                </div>
                                <div class="h3 mb-1"><?php echo (int)($teacher_360['pending_grading_count'] ?? 0); ?></div>
                                <p class="text-muted mb-0">Assignment submissions waiting</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong>Demo follow-ups</strong>
                                    <i class="mdi mdi-account-clock text-info"></i>
                                </div>
                                <div class="h3 mb-1"><?php echo (int)($teacher_360['demo_followups_count'] ?? 0); ?></div>
                                <p class="text-muted mb-0">Demo sessions to convert/follow up</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <a href="<?php echo site_url('user/payout_report'); ?>" class="text-secondary">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong>Earnings snapshot</strong>
                                    <i class="mdi mdi-cash-multiple text-success"></i>
                                </div>
                                <div class="h5 mb-1"><?php echo $teacher_pending_amount > 0 ? currency($teacher_pending_amount) : currency_code_and_symbol().''.$teacher_pending_amount; ?></div>
                                <p class="text-muted mb-0">Pending balance</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Today’s class list</h5>
                                <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-primary btn-sm">Open batches</a>
                            </div>
                            <?php if (!empty($todays_classes)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($todays_classes as $session): ?>
                                        <li class="border-bottom py-2">
                                            <strong><?php echo html_escape($session['title'] ?? 'Session'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($session['batch_title'] ?? 'Batch'); ?> · <?php echo html_escape(substr((string)($session['start_time'] ?? ''), 0, 5)); ?> - <?php echo html_escape(substr((string)($session['end_time'] ?? ''), 0, 5)); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-2">No classes scheduled for today.</p>
                                <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-primary btn-sm">Schedule a class</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">Pending grading queue</h5>
                                <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-primary btn-sm">Grade work</a>
                            </div>
                            <?php if (!empty($pending_grading_items)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($pending_grading_items as $item): ?>
                                        <li class="border-bottom py-2">
                                            <strong><?php echo html_escape($item['task_title'] ?? 'Assignment'); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($item['batch_title'] ?? 'Batch'); ?> · <?php echo html_escape(trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No pending grading right now.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h5 class="mb-2">Batch health</h5>
                            <p class="text-muted mb-2"><?php echo (int)($teacher_360['active_batch_count'] ?? 0); ?> active/published batches · <?php echo (int)($teacher_360['total_student_count'] ?? 0); ?> enrolled students</p>
                            <?php if (!empty($batch_health_items)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Batch</th><th>Students</th><th>Sessions</th><th>Tasks</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($batch_health_items as $batch): ?>
                                                <tr>
                                                    <td><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id']); ?>"><?php echo html_escape($batch['title']); ?></a></td>
                                                    <td><?php echo (int)($batch['students'] ?? 0); ?></td>
                                                    <td><?php echo (int)($batch['sessions'] ?? 0); ?></td>
                                                    <td><?php echo (int)($batch['tasks'] ?? 0); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-2">No batch data yet.</p>
                                <a href="<?php echo site_url('tutor_batch/create'); ?>" class="btn btn-outline-primary btn-sm">Create batch</a>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between flex-wrap">
                                <small>Requested: <strong><?php echo $teacher_requested_amount > 0 ? currency($teacher_requested_amount) : currency_code_and_symbol().''.$teacher_requested_amount; ?></strong></small>
                                <small>Paid: <strong><?php echo $teacher_paid_amount > 0 ? currency($teacher_paid_amount) : currency_code_and_symbol().''.$teacher_paid_amount; ?></strong></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($demo_followups)): ?>
                    <div class="alert alert-light border mb-0">
                        <strong>Demo follow-ups:</strong>
                        <?php foreach (array_slice($demo_followups, 0, 3) as $index => $demo): ?>
                            <?php echo $index > 0 ? ' · ' : ''; ?>
                            <a href="<?php echo site_url('tutor_batch/manage/' . (int)$demo['batch_id'] . '/sessions'); ?>"><?php echo html_escape($demo['title'] ?? 'Demo class'); ?></a>
                            <small class="text-muted">(<?php echo html_escape($demo['session_date'] ?? ''); ?>)</small>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    $CI =& get_instance();

    if (!isset($CI->tutor_request_model) || !is_object($CI->tutor_request_model)) {
        $CI->load->model('Tutor_request_model', 'tutor_request_model');
    }

    $current_user_id = (int) $CI->session->userdata('user_id');
    $pending_student_request_count = 0;
    $latest_student_requests = [];

    if ($current_user_id > 0) {
        $pending_student_request_count = (int) $CI->tutor_request_model->count_pending_for_tutor($current_user_id);
        $latest_student_requests = $CI->tutor_request_model->get_incoming_requests_for_tutor($current_user_id, 'all', 5);
    }

    if (!isset($CI->tutor_master_model) || !is_object($CI->tutor_master_model)) {
        $CI->load->model('Tutor_master_model', 'tutor_master_model');
    }
    if (!isset($CI->tutor_batch_model) || !is_object($CI->tutor_batch_model)) {
        $CI->load->model('Tutor_batch_model', 'tutor_batch_model');
    }

    $tutor_profile = $current_user_id > 0 ? $CI->tutor_master_model->get_tutor_profile_by_user_id($current_user_id) : [];
    $selected_subject_ids = $current_user_id > 0 ? $CI->tutor_master_model->get_selected_subject_ids_by_user_id($current_user_id) : [];
    $tutor_batches = $current_user_id > 0 ? $CI->tutor_batch_model->get_tutor_batches($current_user_id) : [];
    $current_user = $current_user_id > 0 ? $CI->user_model->get_user($current_user_id)->row_array() : [];
    $payment_keys = json_decode($current_user['payment_keys'] ?? '', true);
    $payout_details = is_array($payment_keys) ? ($payment_keys['payout_details'] ?? []) : [];

    $profile_checks = [
        'basic' => !empty($current_user['first_name']) && !empty($current_user['last_name']) && !empty($current_user['email']),
        'headline' => !empty($tutor_profile['headline']) && !empty($tutor_profile['qualification']),
        'subjects' => !empty($selected_subject_ids),
        'pricing' => !empty($tutor_profile['teaching_mode']) && $tutor_profile['hourly_fee'] !== null && $tutor_profile['hourly_fee'] !== '',
        'location' => !empty($tutor_profile['city']) && !empty($tutor_profile['country']),
        'bio' => !empty($tutor_profile['bio']) && strlen(strip_tags((string)$tutor_profile['bio'])) >= 40,
        'batch' => !empty($tutor_batches),
        'payout' => !empty($payout_details['account_holder_name']) || !empty($payout_details['upi_id']),
    ];
    $completed_profile_checks = count(array_filter($profile_checks));
    $profile_completion = (int) round(($completed_profile_checks / max(1, count($profile_checks))) * 100);

    // phase4_profile_status
    $profile_status = strtolower((string)($tutor_profile['status'] ?? 'inactive'));
    $is_profile_approved = ($profile_status === 'active');
    $profile_status_label = $is_profile_approved ? 'Verified tutor' : 'Awaiting profile approval';
    $profile_status_class = $is_profile_approved ? 'success' : 'warning';
    $active_batch_count = 0;
    foreach ($tutor_batches as $batch_row) {
        if (strtolower((string)($batch_row['status'] ?? '')) === 'active') {
            $active_batch_count++;
        }
    }

    $teacher_next_step = [
        'title' => 'Build your tutor marketplace profile',
        'body' => 'Improve headline, subjects, pricing, bio, and location so students and parents can trust your profile.',
        'url' => site_url('user/tutor_teaching_profile'),
        'cta' => 'Improve profile',
        'icon' => 'mdi-account-badge',
        'tone' => 'primary',
    ];
    if ($pending_student_request_count > 0) {
        $teacher_next_step = [
            'title' => 'Respond to student requests',
            'body' => 'Reply while the request is fresh and convert interest into a demo or batch invite.',
            'url' => site_url('user/student_requests'),
            'cta' => 'Review requests',
            'icon' => 'mdi-message-text-clock',
            'tone' => 'info',
        ];
    } elseif ((int)($teacher_360['pending_grading_count'] ?? 0) > 0) {
        $teacher_next_step = [
            'title' => 'Clear grading queue',
            'body' => 'Evaluate pending submissions so students get timely feedback and progress signals stay accurate.',
            'url' => site_url('tutor_batch'),
            'cta' => 'Grade work',
            'icon' => 'mdi-clipboard-check-outline',
            'tone' => 'warning',
        ];
    } elseif (!empty($todays_classes)) {
        $teacher_next_step = [
            'title' => 'Prepare today\'s classes',
            'body' => 'Open your batch, review attendance, and confirm resources before the next live session.',
            'url' => site_url('tutor_batch'),
            'cta' => 'Open schedule',
            'icon' => 'mdi-calendar-today',
            'tone' => 'success',
        ];
    } elseif (empty($tutor_batches)) {
        $teacher_next_step = [
            'title' => 'Create your first batch',
            'body' => 'Launch a demo or paid batch to start inviting students, scheduling sessions, and assigning practice.',
            'url' => site_url('tutor_batch/create'),
            'cta' => 'Create batch',
            'icon' => 'mdi-account-group',
            'tone' => 'primary',
        ];
    } elseif (empty($payout_details['account_holder_name']) && empty($payout_details['upi_id'])) {
        $teacher_next_step = [
            'title' => 'Add payout details',
            'body' => 'Complete payout setup so your teaching revenue can move from pending to requested.',
            'url' => site_url('user/payout_settings'),
            'cta' => 'Add payout',
            'icon' => 'mdi-bank',
            'tone' => 'success',
        ];
    }

    $teacher_agenda = [];
    foreach (array_slice($todays_classes, 0, 4) as $session) {
        $teacher_agenda[] = [
            'type' => 'Class',
            'title' => $session['title'] ?? 'Live class',
            'meta' => trim(($session['batch_title'] ?? 'Batch') . ' - ' . substr((string)($session['start_time'] ?? ''), 0, 5)),
            'url' => site_url('tutor_batch/manage/' . (int)($session['batch_id'] ?? 0) . '/sessions'),
        ];
    }
    foreach (array_slice($pending_grading_items, 0, 3) as $item) {
        $teacher_agenda[] = [
            'type' => 'Grading',
            'title' => $item['task_title'] ?? 'Assignment',
            'meta' => trim(($item['batch_title'] ?? 'Batch') . ' - ' . ($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')),
            'url' => site_url('tutor_batch/manage/' . (int)($item['batch_id'] ?? 0) . '/tasks'),
        ];
    }
    foreach (array_slice($demo_followups, 0, 2) as $demo) {
        $teacher_agenda[] = [
            'type' => 'Demo',
            'title' => $demo['title'] ?? 'Demo follow-up',
            'meta' => trim(($demo['session_date'] ?? '') . ' - ' . ($demo['batch_title'] ?? 'Batch')),
            'url' => site_url('tutor_batch/manage/' . (int)($demo['batch_id'] ?? 0) . '/sessions'),
        ];
    }
    $teacher_agenda = array_slice($teacher_agenda, 0, 6);

    $batch_risk_count = 0;
    foreach ($batch_health_items as $batch) {
        if ((int)($batch['students'] ?? 0) === 0 || (int)($batch['sessions'] ?? 0) === 0 || (int)($batch['tasks'] ?? 0) === 0) {
            $batch_risk_count++;
        }
    }
    $teaching_quality_score = (int)round((
        $profile_completion
        + (($active_batch_count > 0) ? 100 : 0)
        + (((int)($teacher_360['pending_grading_count'] ?? 0) === 0) ? 100 : 55)
        + ((!empty($payout_details['account_holder_name']) || !empty($payout_details['upi_id'])) ? 100 : 60)
    ) / 4);

    $teacher_pipeline = [
        ['label' => 'Profile ready', 'value' => $profile_completion . '%', 'url' => site_url('user/tutor_teaching_profile')],
        ['label' => 'Student requests', 'value' => (string)$pending_student_request_count, 'url' => site_url('user/student_requests')],
        ['label' => 'Active batches', 'value' => (string)$active_batch_count, 'url' => site_url('tutor_batch')],
        ['label' => 'Pending grading', 'value' => (string)(int)($teacher_360['pending_grading_count'] ?? 0), 'url' => site_url('tutor_batch')],
        ['label' => 'Payout readiness', 'value' => (!empty($payout_details['account_holder_name']) || !empty($payout_details['upi_id'])) ? 'Ready' : 'To do', 'url' => site_url('user/payout_settings')],
    ];
    $ai_teaching_prompts = [
        'Create a lesson plan from my next batch topic.',
        'Draft feedback for a student who missed practice.',
        'Generate 10 questions for the next assessment.',
    ];
?>

<!-- phase5_tutor_command_center -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1">Tutor Command Center</h4>
                        <p class="text-muted mb-0">Your teaching, student follow-up, content quality, and revenue priorities in one operating view.</p>
                    </div>
                    <span class="badge badge-<?php echo $teaching_quality_score >= 80 ? 'success' : ($teaching_quality_score >= 60 ? 'warning' : 'danger'); ?>-lighten mt-1">Quality score: <?php echo $teaching_quality_score; ?>%</span>
                </div>

                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-1">Today</h5>
                                    <p class="text-muted mb-0">Classes, grading, demos, and follow-ups.</p>
                                </div>
                                <i class="mdi mdi-calendar-check text-primary" style="font-size: 24px;"></i>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Classes</span>
                                <strong><?php echo (int)($teacher_360['todays_class_count'] ?? count($todays_classes)); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Grading</span>
                                <strong><?php echo (int)($teacher_360['pending_grading_count'] ?? 0); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Requests</span>
                                <strong><?php echo $pending_student_request_count; ?></strong>
                            </div>
                            <?php if (!empty($teacher_agenda)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach (array_slice($teacher_agenda, 0, 3) as $agenda): ?>
                                        <li class="border-top py-2">
                                            <span class="badge badge-light mr-1"><?php echo html_escape($agenda['type']); ?></span>
                                            <strong><?php echo html_escape($agenda['title']); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($agenda['meta']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-3">No urgent agenda items right now.</p>
                                <a href="<?php echo site_url('tutor_batch/create'); ?>" class="btn btn-outline-primary btn-sm">Plan a batch</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-start mb-3">
                                <i class="mdi <?php echo html_escape($teacher_next_step['icon']); ?> text-<?php echo html_escape($teacher_next_step['tone']); ?> mr-2" style="font-size: 26px;"></i>
                                <div>
                                    <h5 class="mb-1">Recommended Next Step</h5>
                                    <p class="text-muted mb-0">Prioritized from your tutor activity.</p>
                                </div>
                            </div>
                            <h6 class="mb-2"><?php echo html_escape($teacher_next_step['title']); ?></h6>
                            <p class="text-muted"><?php echo html_escape($teacher_next_step['body']); ?></p>
                            <a href="<?php echo html_escape($teacher_next_step['url']); ?>" class="btn btn-primary btn-sm"><?php echo html_escape($teacher_next_step['cta']); ?></a>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">AI Teaching Assistant</h5>
                                    <p class="text-muted mb-0">Prompt starters for planning and feedback.</p>
                                </div>
                                <i class="mdi mdi-auto-fix text-info" style="font-size: 24px;"></i>
                            </div>
                            <?php foreach ($ai_teaching_prompts as $prompt): ?>
                                <div class="bg-light border rounded px-2 py-2 mb-2 small"><?php echo html_escape($prompt); ?></div>
                            <?php endforeach; ?>
                            <a href="<?php echo site_url('user/tutor_teaching_profile'); ?>" class="btn btn-outline-primary btn-sm">Improve teaching profile</a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Teaching Pipeline</h5>
                                <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-primary btn-sm">Open batches</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Stage</th><th>Status</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($teacher_pipeline as $pipeline_item): ?>
                                            <tr>
                                                <td><?php echo html_escape($pipeline_item['label']); ?></td>
                                                <td><strong><?php echo html_escape($pipeline_item['value']); ?></strong></td>
                                                <td><a href="<?php echo html_escape($pipeline_item['url']); ?>" class="btn btn-link btn-sm p-0">Open</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h5 class="mb-2">Student Success & Growth</h5>
                            <p class="text-muted mb-3">Use batch signals to protect learner outcomes and improve repeat purchase.</p>
                            <div class="d-flex justify-content-between">
                                <span>At-risk batches</span>
                                <strong><?php echo $batch_risk_count; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total students</span>
                                <strong><?php echo (int)($teacher_360['total_student_count'] ?? 0); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Pending payout</span>
                                <strong><?php echo $teacher_pending_amount > 0 ? currency($teacher_pending_amount) : currency_code_and_symbol().''.$teacher_pending_amount; ?></strong>
                            </div>
                            <div class="progress mb-3" style="height: 8px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $teaching_quality_score; ?>">
                                <div class="progress-bar bg-success" style="width: <?php echo $teaching_quality_score; ?>%;"></div>
                            </div>
                            <a href="<?php echo site_url('user/sales_report'); ?>" class="btn btn-outline-primary btn-sm">View growth report</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap">
                    <div class="mb-2">
                        <h4 class="header-title mb-1"><?php echo get_phrase('tutor_onboarding'); ?></h4>
                        <p class="text-muted mb-0">Complete your tutor setup so students can understand what you teach and how to contact you.</p>
                    </div>
                    <div class="text-right" aria-label="Profile completion <?php echo $profile_completion; ?> percent">
                        <div class="h3 mb-0"><?php echo $profile_completion; ?>%</div>
                        <div class="text-muted small"><?php echo get_phrase('profile_completion'); ?></div>
                    </div>
                </div>
                <div class="progress mt-2 mb-3" style="height: 8px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $profile_completion; ?>">
                    <div class="progress-bar" style="width: <?php echo $profile_completion; ?>%;"></div>
                </div>
                <div class="row">
                    <?php
                        $onboarding_items = [
                            ['done' => $profile_checks['basic'], 'label' => 'Basic account', 'url' => site_url('home/profile/user_profile'), 'action' => 'Update profile'],
                            ['done' => $profile_checks['headline'], 'label' => 'Headline and qualification', 'url' => site_url('user/tutor_teaching_profile'), 'action' => 'Edit teaching profile'],
                            ['done' => $profile_checks['subjects'], 'label' => 'Teaching subjects', 'url' => site_url('user/tutor_teaching_profile'), 'action' => 'Select subjects'],
                            ['done' => $profile_checks['pricing'], 'label' => 'Teaching mode and fee', 'url' => site_url('user/tutor_teaching_profile'), 'action' => 'Add pricing'],
                            ['done' => $profile_checks['location'], 'label' => 'Location', 'url' => site_url('user/tutor_teaching_profile'), 'action' => 'Add location'],
                            ['done' => $profile_checks['bio'], 'label' => 'Tutor bio', 'url' => site_url('user/tutor_teaching_profile'), 'action' => 'Improve bio'],
                            ['done' => $profile_checks['batch'], 'label' => 'First batch', 'url' => site_url('tutor_batch/create'), 'action' => 'Create batch'],
                            ['done' => $profile_checks['payout'], 'label' => 'Payout details', 'url' => site_url('user/payout_settings'), 'action' => 'Add payout'],
                        ];
                    ?>
                    <?php foreach ($onboarding_items as $item): ?>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a class="d-block border rounded p-2 text-secondary" href="<?php echo $item['url']; ?>" aria-label="<?php echo html_escape($item['action'] . ': ' . $item['label']); ?>">
                                <span class="badge badge-<?php echo $item['done'] ? 'success' : 'warning'; ?>-lighten mr-1"><?php echo $item['done'] ? 'Done' : 'To do'; ?></span>
                                <span><?php echo html_escape($item['label']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <div class="row no-gutters">
                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('user/student_requests'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0">
                                <div class="card-body text-center">
                                    <i class="dripicons-message text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo (int) $pending_student_request_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('pending_student_requests'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-9 border-left">
                        <div class="card shadow-none m-0">
                            <div class="card-body">
                                <h5 class="mt-0 mb-3"><?php echo get_phrase('latest_student_requests'); ?></h5>
                                <?php if (!empty($latest_student_requests)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo get_phrase('student'); ?></th>
                                                    <th><?php echo get_phrase('subject'); ?></th>
                                                    <th><?php echo get_phrase('status'); ?></th>
                                                    <th><?php echo get_phrase('date'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($latest_student_requests as $request): ?>
                                                <?php
                                                    $student_name = '';
                                                    if (!empty($request['student_name_snapshot'])) {
                                                        $student_name = $request['student_name_snapshot'];
                                                    } else {
                                                        $student_name = trim(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? ''));
                                                    }

                                                    $subject_text = !empty($request['subject_name_snapshot']) ? $request['subject_name_snapshot'] : ($request['query_text'] ?? '');

                                                    $status_class = 'warning';
                                                    if (($request['status'] ?? '') === 'approved') {
                                                        $status_class = 'success';
                                                    } elseif (($request['status'] ?? '') === 'rejected') {
                                                        $status_class = 'danger';
                                                    }

                                                    $created_at_text = '';
                                                    if (!empty($request['created_at'])) {
                                                        $timestamp = strtotime($request['created_at']);
                                                        $created_at_text = $timestamp ? date('d M Y, h:i A', $timestamp) : html_escape($request['created_at']);
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?php echo html_escape($student_name); ?></td>
                                                    <td><?php echo html_escape($subject_text); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $status_class; ?>-lighten">
                                                            <?php echo ucfirst($request['status'] ?? 'pending'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $created_at_text; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <h5 class="mb-1"><?php echo get_phrase('No student requests yet.'); ?></h5>
                                        <p class="text-muted mb-3">A complete teaching profile helps students send better requests.</p>
                                        <a href="<?php echo site_url('user/tutor_teaching_profile'); ?>" class="btn btn-outline-primary btn-sm" aria-label="Improve teaching profile"><?php echo get_phrase('improve_teaching_profile'); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- phase4_product_growth_tools -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1">Tutor Growth Tools</h4>
                        <p class="text-muted mb-0">Use these next-level workflows to turn profile views into demos, classes, progress reports, and payout-ready activity.</p>
                    </div>
                    <span class="badge badge-<?php echo $profile_status_class; ?>-lighten mt-1">
                        <?php echo html_escape($profile_status_label); ?>
                    </span>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong>Profile approval</strong>
                                <i class="mdi mdi-shield-check text-<?php echo $is_profile_approved ? 'success' : 'warning'; ?>"></i>
                            </div>
                            <p class="text-muted mb-3"><?php echo $is_profile_approved ? 'Students see a verified tutor badge on search cards.' : 'Complete profile quality items and wait for admin approval.'; ?></p>
                            <a href="<?php echo site_url('user/tutor_teaching_profile'); ?>" class="btn btn-outline-primary btn-sm">Improve profile</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong>Demo booking</strong>
                                <i class="mdi mdi-calendar-clock text-info"></i>
                            </div>
                            <p class="text-muted mb-3">Create a demo-class session and share the calendar invite with students.</p>
                            <a href="<?php echo site_url('tutor_batch/create'); ?>" class="btn btn-outline-primary btn-sm">Create demo batch</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong>Student success</strong>
                                <i class="mdi mdi-chart-timeline-variant text-success"></i>
                            </div>
                            <p class="text-muted mb-3"><?php echo (int)$active_batch_count; ?> active batch<?php echo $active_batch_count === 1 ? '' : 'es'; ?> can show attendance, assignments, and progress.</p>
                            <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-primary btn-sm">Open batches</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong>Earnings</strong>
                                <i class="mdi mdi-cash-multiple text-primary"></i>
                            </div>
                            <p class="text-muted mb-3">Pending: <?php echo $total_pending_amount > 0 ? currency($total_pending_amount) : currency_code_and_symbol().''.$total_pending_amount; ?>. Requested: <?php echo $requested_withdrawal_amount > 0 ? currency($requested_withdrawal_amount) : currency_code_and_symbol().''.$requested_withdrawal_amount; ?>.</p>
                            <a href="<?php echo site_url('user/payout_report'); ?>" class="btn btn-outline-primary btn-sm">View earnings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- NEW: Batch management shortcuts -->
<div class="row">
    <div class="col-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <div class="row no-gutters">
                    <div class="col-sm-6 col-xl-4">
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary" aria-label="Manage batches">
                            <div class="card shadow-none m-0">
                                <div class="card-body text-center">
                                    <i class="dripicons-user-group text-muted" style="font-size: 24px;"></i>
                                    <h3><span>-</span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('manage_batches'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <a href="<?php echo site_url('tutor_batch/create'); ?>" class="text-secondary" aria-label="Create batch">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-plus text-muted" style="font-size: 24px;"></i>
                                    <h3><span>+</span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('create_batch'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary" aria-label="Manage batches">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-calendar text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo get_phrase('go'); ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('batch_sessions_assignments_tests'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-4"><?php echo get_phrase('instructor_revenue'); ?></h4>
                <div class="mt-3 chartjs-chart" style="height: 320px;">
                    <canvas id="task-area-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <div class="row no-gutters">
                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('user/courses'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0">
                                <div class="card-body text-center">
                                    <i class="dripicons-archive text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $number_of_courses; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_of_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card shadow-none m-0 border-left">
                            <div class="card-body text-center">
                                <i class="dripicons-user-group text-muted" style="font-size: 24px;"></i>
                                <h3><span><?php echo $number_of_enrolment; ?></span></h3>
                                <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_of_enrolment'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('user/payout_report'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-inbox text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $total_pending_amount > 0 ? currency($total_pending_amount) : currency_code_and_symbol().''.$total_pending_amount; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('pending_balance'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('user/payout_report'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-pin text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $requested_withdrawal_amount > 0 ? currency($requested_withdrawal_amount) : currency_code_and_symbol().''.$requested_withdrawal_amount; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('requested_withdrawal_amount'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-4"><?php echo get_phrase('course_overview'); ?></h4>
                <div class="my-4 chartjs-chart" style="height: 202px;">
                    <canvas id="project-status-chart"></canvas>
                </div>
                <div class="row text-center mt-2 py-2">
                    <div class="col-6">
                        <i class="mdi mdi-trending-up text-success mt-3 h3"></i>
                        <h3 class="font-weight-normal">
                            <span><?php echo $this->crud_model->get_status_wise_courses_for_instructor('active')->num_rows(); ?></span>
                        </h3>
                        <p class="text-muted mb-0"><?php echo get_phrase('active_courses'); ?></p>
                    </div>
                    <div class="col-6">
                        <i class="mdi mdi-trending-down text-warning mt-3 h3"></i>
                        <h3 class="font-weight-normal">
                            <span><?php echo $this->crud_model->get_status_wise_courses_for_instructor('pending')->num_rows(); ?></span>
                        </h3>
                        <p class="text-muted mb-0"><?php echo get_phrase('pending_courses'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
