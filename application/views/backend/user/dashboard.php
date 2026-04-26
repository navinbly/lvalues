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
?>

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
?>

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
                                    <p class="text-muted mb-0"><?php echo get_phrase('No student requests yet.'); ?></p>
                                <?php endif; ?>
                            </div>
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
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary">
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
                        <a href="<?php echo site_url('tutor_batch/create'); ?>" class="text-secondary">
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
                        <a href="<?php echo site_url('tutor_batch'); ?>" class="text-secondary">
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