<?php
    // Dashboard metrics use COUNT(*) so no full table is ever fetched just to count rows.
    $pending_course_count = (int)$this->db->where('status', 'pending')->count_all_results('course');
    $active_course_count = (int)$this->db->where('status', 'active')->count_all_results('course');
    $number_of_courses = $pending_course_count + $active_course_count;
    $number_of_lessons = (int)$this->db->count_all('lesson');
    $number_of_enrolment = (int)$this->db->count_all('enrol');
    $number_of_students = (int)$this->db->where('role_id', 2)->count_all_results('users');

    // phase4_admin_quality_metrics
    $pending_tutor_applications = (int)$this->db->where('status', 0)->count_all_results('applications');
    $active_tutor_profiles = $this->db->table_exists('tutor_profiles') ? (int)$this->db->where('status', 'active')->count_all_results('tutor_profiles') : 0;
    $pending_tutor_profiles = $this->db->table_exists('tutor_profiles') ? (int)$this->db->where('status !=', 'active')->count_all_results('tutor_profiles') : 0;
    $pending_content_nodes = $this->db->table_exists('content_nodes') ? (int)$this->db->where('status', 'pending')->count_all_results('content_nodes') : 0;
    $published_content_nodes = 0;
    if ($this->db->table_exists('content_nodes')) {
        if ($this->db->field_exists('is_deleted', 'content_nodes')) {
            $this->db->where('is_deleted', 0);
        }
        $this->db->group_start();
        $this->db->where('status', 'published');
        if ($this->db->field_exists('review_status', 'content_nodes')) {
            $this->db->or_where('review_status', 'published');
            $this->db->or_where('review_status', 'approved');
        }
        $this->db->group_end();
        $published_content_nodes = (int)$this->db->count_all_results('content_nodes');
    }
    $question_bank_total = $this->db->table_exists('question_bank_questions') ? (int)$this->db->count_all_results('question_bank_questions') : 0;
    $question_bank_active = $this->db->table_exists('question_bank_questions') ? (int)$this->db->where('status', 'active')->count_all_results('question_bank_questions') : 0;
    $question_bank_draft = $this->db->table_exists('question_bank_questions') ? (int)$this->db->where('status', 'draft')->count_all_results('question_bank_questions') : 0;
    $exam_pattern_total = $this->db->table_exists('content_exams') ? (int)$this->db->count_all_results('content_exams') : 0;
    $exam_pattern_published = 0;
    $exam_pattern_draft = 0;
    if ($this->db->table_exists('content_exams')) {
        if ($this->db->field_exists('is_published', 'content_exams')) {
            $exam_pattern_published = (int)$this->db->where('is_published', 1)->count_all_results('content_exams');
        } else {
            $exam_pattern_published = (int)$this->db->where('status', 'published')->count_all_results('content_exams');
        }
        $this->db->group_start();
        $this->db->where('status', 'draft');
        if ($this->db->field_exists('review_status', 'content_exams')) {
            $this->db->or_where('review_status', 'in_review');
        }
        $this->db->group_end();
        $exam_pattern_draft = (int)$this->db->count_all_results('content_exams');
    }
    $payout_summary = $this->db->select('COUNT(*) AS pending_count, COALESCE(SUM(amount), 0) AS pending_total', false)
        ->where('status', 0)->get('payout')->row_array();
    $pending_payout_count = (int)($payout_summary['pending_count'] ?? 0);
    $pending_payout_total = (float)($payout_summary['pending_total'] ?? 0);
    $pending_payout_total_label = $pending_payout_total > 0 ? currency($pending_payout_total) : '0';
    $course_completeness_rate = $number_of_courses > 0 ? round(($number_of_lessons / $number_of_courses) * 100, 1) : 0;
    $student_activation_rate = $number_of_students > 0 ? round(($number_of_enrolment / $number_of_students) * 100, 1) : 0;
    $today_task_count = $pending_tutor_applications + $pending_tutor_profiles + $pending_content_nodes + $pending_course_count + $pending_payout_count + $question_bank_draft + $exam_pattern_draft;
    $recent_students = $this->db->table_exists('users') ? $this->db->order_by('date_added', 'DESC')->limit(3)->get('users')->result_array() : array();
    $recent_courses = $this->db->table_exists('course') ? $this->db->order_by('date_added', 'DESC')->limit(3)->get('course')->result_array() : array();
    $contact_count = $this->db->table_exists('contact') ? $this->db->count_all_results('contact') : 0;
    $ai_review_signals = $pending_tutor_applications + $pending_course_count + $contact_count;
?>
<style>
    .admin-command-card { border: 1px solid #edf1f7; border-radius: 8px; box-shadow: 0 10px 30px rgba(31,45,61,.04); }
    .admin-command-card .metric-label { color: #8492a6; font-size: 13px; margin-bottom: 6px; }
    .admin-command-card .metric-value { color: #344054; font-size: 28px; font-weight: 700; line-height: 1; }
    .admin-command-card .metric-note { color: #98a2b3; font-size: 12px; }
    .ops-task { border: 1px solid #edf1f7; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
    .ops-task:hover { border-color: #727cf5; background: #f8f9ff; }
    .ops-task .task-title { color: #344054; font-weight: 700; margin-bottom: 2px; }
    .ops-task .task-copy { color: #8492a6; font-size: 13px; margin-bottom: 0; }
    .admin-action-card { display: block; height: 100%; border: 1px solid #edf1f7; border-radius: 8px; padding: 16px; color: #344054; background: #fff; }
    .admin-action-card:hover { border-color: #727cf5; color: #344054; background: #f8f9ff; }
    .admin-action-card i { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px; color: #5369f8; background: #eef2ff; font-size: 20px; }
    .admin-action-card strong { display: block; margin-bottom: 4px; }
    .admin-action-card span { color: #8492a6; font-size: 13px; }
    .admin-workflow-step { border-left: 3px solid #727cf5; padding: 10px 12px; background: #fbfcff; border-radius: 0 8px 8px 0; height: 100%; }
    .admin-workflow-step strong { display: block; color: #344054; }
    .admin-workflow-step span { color: #8492a6; font-size: 12px; }
    .health-pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 700; }
    .health-pill.warning { background: #fff8e6; color: #b7791f; }
    .health-pill.good { background: #e7f8f2; color: #087f5b; }
    .health-pill.info { background: #eef4ff; color: #3b5bdb; }
    .quick-action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; }
    .quick-action-grid .btn { text-align: left; white-space: normal; }
    .empty-state-inline { border: 1px dashed #d8dee9; border-radius: 8px; padding: 22px; color: #8492a6; background: #fbfcff; }
    @media (max-width: 767px) {
        .admin-command-card .metric-value { font-size: 24px; }
        .chartjs-chart.admin-revenue-chart { height: 220px !important; }
        .quick-action-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('dashboard'); ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-12">
        <div class="card admin-command-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1">Operations command center</h4>
                        <p class="text-muted mb-0">Prioritize tutor approval, book/article publishing, question bank quality, exams, payouts, and learner growth from one screen.</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <span class="health-pill <?php echo $today_task_count > 0 ? 'warning' : 'good'; ?>">
                            <i class="mdi <?php echo $today_task_count > 0 ? 'mdi-alert-circle-outline' : 'mdi-check-circle-outline'; ?>"></i>
                            <?php echo (int)$today_task_count; ?> open admin tasks
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/instructor_application'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Tutor applications</p>
                                <div class="metric-value"><?php echo (int)$pending_tutor_applications; ?></div>
                                <span class="metric-note">Pending approval</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/courses?category_id=all&status=pending&instructor_id=all&price=all&button='); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Course approvals</p>
                                <div class="metric-value"><?php echo (int)$pending_course_count; ?></div>
                                <span class="metric-note">Pending publish review</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/content_nodes_pending'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Books/articles review</p>
                                <div class="metric-value"><?php echo (int)$pending_content_nodes; ?></div>
                                <span class="metric-note"><?php echo (int)$published_content_nodes; ?> published</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/instructor_payout'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Payout exceptions</p>
                                <div class="metric-value"><?php echo (int)$pending_payout_count; ?></div>
                                <span class="metric-note"><?php echo $pending_payout_total_label; ?> pending</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/question_bank?qb_tab=create_exam'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Question bank</p>
                                <div class="metric-value"><?php echo (int)$question_bank_active; ?></div>
                                <span class="metric-note"><?php echo (int)$question_bank_total; ?> total, <?php echo (int)$question_bank_draft; ?> drafts</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/content_public_exams'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Exam/mock tests</p>
                                <div class="metric-value"><?php echo (int)$exam_pattern_published; ?></div>
                                <span class="metric-note"><?php echo (int)$exam_pattern_total; ?> total, <?php echo (int)$exam_pattern_draft; ?> draft/review</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/student_success'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Students</p>
                                <div class="metric-value"><?php echo (int)$number_of_students; ?></div>
                                <span class="metric-note"><?php echo (int)$number_of_enrolment; ?> enrollments</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="<?php echo site_url('admin/tutor_performance'); ?>" class="text-body">
                            <div class="border rounded p-3 h-100">
                                <p class="metric-label">Verified tutors</p>
                                <div class="metric-value"><?php echo (int)$active_tutor_profiles; ?></div>
                                <span class="metric-note"><?php echo (int)$pending_tutor_profiles; ?> profiles need attention</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="mt-3">
                    <h5 class="mb-2">Daily admin workflow</h5>
                    <div class="row">
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Approve tutors</strong><span>Applications and documents</span></div></div>
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Publish books</strong><span>Notes, pages, and articles</span></div></div>
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Build Q bank</strong><span>Exam, section, topic, quality</span></div></div>
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Create tests</strong><span>Mock and real exam patterns</span></div></div>
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Track learners</strong><span>Progress and intervention</span></div></div>
                        <div class="col-md-2 col-sm-6 mb-2"><div class="admin-workflow-step"><strong>Resolve ops</strong><span>Support, finance, governance</span></div></div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-2"><a href="<?php echo site_url('admin/content_nodes?action=create_notes'); ?>" class="admin-action-card"><i class="mdi mdi-book-open-page-variant"></i><strong>Create book/notes</strong><span>Publish structured learning material.</span></a></div>
                    <div class="col-md-3 col-sm-6 mb-2"><a href="<?php echo site_url('admin/question_bank?qb_tab=create_exam'); ?>" class="admin-action-card"><i class="mdi mdi-database-search"></i><strong>Create question bank</strong><span>Add section-wise reusable questions.</span></a></div>
                    <div class="col-md-3 col-sm-6 mb-2"><a href="<?php echo site_url('admin/content_public_exams'); ?>" class="admin-action-card"><i class="mdi mdi-clipboard-text-outline"></i><strong>Build exam/mock test</strong><span>Select questions and publish tests.</span></a></div>
                    <div class="col-md-3 col-sm-6 mb-2"><a href="<?php echo site_url('admin/moderation_center'); ?>" class="admin-action-card"><i class="mdi mdi-shield-check-outline"></i><strong>Review center</strong><span>Govern submitted content and quality.</span></a></div>
                </div>
                <div class="mt-3 ai-command-strip">
                    <div>
                        <span class="badge badge-info-lighten mb-1">Phase 4 AI readiness</span>
                        <strong class="d-block">AI recommendation queue</strong>
                        <span class="text-muted"><?php echo (int)$ai_review_signals; ?> tutor, course, and support records can be prepared for explainable AI review.</span>
                    </div>
                    <a href="<?php echo site_url('admin/ai_readiness'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Open AI readiness</a>
                </div>
                <div class="mt-3 scale-command-strip">
                    <div>
                        <span class="badge badge-warning-lighten mb-1">Phase 6 scalability</span>
                        <strong class="d-block">Scale foundations implemented</strong>
                        <span class="text-muted">Audit log, workflow task, async export, KPI snapshot schema, and index migration are ready for deployment.</span>
                    </div>
                    <a href="<?php echo site_url('admin/scalability_review'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Open scalability review</a>
                </div>
                <div class="mt-3 roadmap-command-strip">
                    <div>
                        <span class="badge badge-success-lighten mb-1">Phase 7 roadmap</span>
                        <strong class="d-block">Implementation tracker</strong>
                        <span class="text-muted">Track immediate wins, professional SaaS work, and AI-powered platform milestones.</span>
                    </div>
                    <a href="<?php echo site_url('admin/implementation_roadmap'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Open roadmap</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card admin-command-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="header-title mb-1">Today's admin queue</h4>
                        <p class="text-muted mb-0">High-impact work that should be handled before growth campaigns or catalog expansion.</p>
                    </div>
                </div>
                <a href="<?php echo site_url('admin/instructor_application'); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Review tutor applications</span>
                        <p class="task-copy">Check identity, documents, profile quality, and fit before approval.</p>
                    </span>
                    <span class="badge badge-<?php echo $pending_tutor_applications > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$pending_tutor_applications; ?></span>
                </a>
                <a href="<?php echo site_url('admin/courses?category_id=all&status=pending&instructor_id=all&price=all&button='); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Approve pending courses</span>
                        <p class="task-copy">Validate title, category, description, lessons, pricing, media, and SEO before publishing.</p>
                    </span>
                    <span class="badge badge-<?php echo $pending_course_count > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$pending_course_count; ?></span>
                </a>
                <a href="<?php echo site_url('admin/content_nodes_pending'); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Review book and article submissions</span>
                        <p class="task-copy">Approve notes, book pages, uploaded notes, and tutor-created articles before public release.</p>
                    </span>
                    <span class="badge badge-<?php echo $pending_content_nodes > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$pending_content_nodes; ?></span>
                </a>
                <a href="<?php echo site_url('admin/instructor_payout'); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Resolve payout requests</span>
                        <p class="task-copy">Review pending withdrawals and keep tutor trust high.</p>
                    </span>
                    <span class="badge badge-<?php echo $pending_payout_count > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$pending_payout_count; ?></span>
                </a>
                <a href="<?php echo site_url('admin/question_bank?qb_tab=search'); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Improve question bank quality</span>
                        <p class="task-copy">Activate high-quality questions and clean up drafts before adding them to exams.</p>
                    </span>
                    <span class="badge badge-<?php echo $question_bank_draft > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$question_bank_draft; ?></span>
                </a>
                <a href="<?php echo site_url('admin/content_public_exams'); ?>" class="ops-task d-flex justify-content-between align-items-center">
                    <span>
                        <span class="task-title d-block">Publish exam and mock-test patterns</span>
                        <p class="task-copy">Convert section-wise question bank selections into real exams and mock tests.</p>
                    </span>
                    <span class="badge badge-<?php echo $exam_pattern_draft > 0 ? 'warning' : 'success'; ?>-lighten"><?php echo (int)$exam_pattern_draft; ?></span>
                </a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card admin-command-card">
            <div class="card-body">
                <h4 class="header-title mb-3">Platform health</h4>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Course content completeness</span>
                        <strong><?php echo $course_completeness_rate; ?>%</strong>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar <?php echo $course_completeness_rate < 25 ? 'bg-warning' : 'bg-success'; ?>" style="width: <?php echo min(100, $course_completeness_rate); ?>%;"></div>
                    </div>
                    <small class="text-muted">Based on lessons per course. Low values indicate courses needing curriculum work.</small>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Student activation</span>
                        <strong><?php echo $student_activation_rate; ?>%</strong>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: <?php echo min(100, $student_activation_rate); ?>%;"></div>
                    </div>
                    <small class="text-muted">Enrollment count compared with registered students.</small>
                </div>
                <div class="mb-3">
                    <span class="health-pill info"><i class="mdi mdi-account-check-outline"></i><?php echo (int)$active_tutor_profiles; ?> verified tutors</span>
                    <span class="health-pill <?php echo $pending_tutor_profiles > 0 ? 'warning' : 'good'; ?> mt-2"><i class="mdi mdi-account-alert-outline"></i><?php echo (int)$pending_tutor_profiles; ?> profiles need attention</span>
                </div>
                <div class="quick-action-grid">
                    <a href="<?php echo site_url('admin/courses'); ?>" class="btn btn-light"><i class="mdi mdi-book-open-variant mr-1"></i> Course quality</a>
                    <a href="<?php echo site_url('admin/users'); ?>" class="btn btn-light"><i class="mdi mdi-account-group mr-1"></i> Student list</a>
                    <a href="<?php echo site_url('admin/contact'); ?>" class="btn btn-light"><i class="mdi mdi-lifebuoy mr-1"></i> Support inbox</a>
                    <a href="<?php echo site_url('admin/admins'); ?>" class="btn btn-light"><i class="mdi mdi-shield-account mr-1"></i> Admin roles</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4">
        <div class="card admin-command-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="header-title mb-0">SLA watchlist</h4>
                    <span class="badge badge-warning-lighten">Phase 3</span>
                </div>
                <div class="ops-task">
                    <span class="task-title d-block">Tutor approval SLA</span>
                    <p class="task-copy"><?php echo (int)$pending_tutor_applications; ?> applications waiting. Target first review: 24 hours.</p>
                </div>
                <div class="ops-task">
                    <span class="task-title d-block">Support SLA</span>
                    <p class="task-copy"><?php echo (int)$contact_count; ?> contact messages in the support intake surface.</p>
                </div>
                <div class="ops-task mb-0">
                    <span class="task-title d-block">Finance SLA</span>
                    <p class="task-copy"><?php echo (int)$pending_payout_count; ?> payout exceptions require finance review.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card admin-command-card">
            <div class="card-body">
                <h4 class="header-title mb-3">Student growth</h4>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Registered students</span><strong><?php echo (int)$number_of_students; ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total enrollments</span><strong><?php echo (int)$number_of_enrolment; ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Activation rate</span><strong><?php echo $student_activation_rate; ?>%</strong></div>
                <a href="<?php echo site_url('admin/student_success'); ?>" class="btn btn-outline-primary btn-sm mt-2">Open student success</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card admin-command-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="header-title mb-0">Recent activity</h4>
                    <a href="<?php echo site_url('admin/audit_activity'); ?>" class="btn btn-link btn-sm">Audit log</a>
                </div>
                <?php foreach ($recent_courses as $course): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?php echo html_escape(ellipsis($course['title'], 30)); ?></span>
                        <span class="badge badge-info-lighten"><?php echo html_escape($course['status']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($recent_students as $student): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?php echo html_escape(trim($student['first_name'] . ' ' . $student['last_name'])); ?></span>
                        <span class="badge badge-success-lighten">User</span>
                    </div>
                <?php endforeach; ?>
                <?php if (count($recent_courses) == 0 && count($recent_students) == 0): ?>
                    <p class="text-muted mb-0">No recent activity records available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1"><?php echo get_phrase('admin_revenue_this_year'); ?></h4>
                        <p class="text-muted mb-0">Monthly admin revenue trend. Use finance reports for purchase, instructor revenue, and payout details.</p>
                    </div>
                    <a href="<?php echo site_url('admin/admin_revenue'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Open finance report</a>
                </div>

                <div class="mt-3 chartjs-chart admin-revenue-chart" style="height: 320px;">
                    <canvas id="task-area-chart"></canvas>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <div class="row no-gutters">
                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('admin/courses'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0">
                                <div class="card-body text-center">
                                    <i class="dripicons-archive text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $number_of_courses; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('admin/courses'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-camcorder text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $number_of_lessons; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_of_lessons'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('admin/enrol_history'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-network-3 text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $number_of_enrolment; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_of_enrolment'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo site_url('admin/users'); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-user-group text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $number_of_students; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('number_of_student'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                </div> <!-- end row -->
            </div>
        </div> <!-- end card-box-->
    </div> <!-- end col-->
</div>
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="header-title mb-0"><?php echo get_phrase('course_overview'); ?></h4>
                    <a href="<?php echo site_url('admin/courses'); ?>" class="btn btn-link btn-sm">View all</a>
                </div>
                <div class="my-4 chartjs-chart" style="height: 202px;">
                    <canvas id="project-status-chart"></canvas>
                </div>
                <div class="row text-center mt-2 py-2">
                    <div class="col-6">
                        <i class="mdi mdi-trending-up text-success mt-3 h3"></i>
                        <h3 class="font-weight-normal">
                            <span><?php echo $active_course_count; ?></span>
                        </h3>
                        <p class="text-muted mb-0"><?php echo get_phrase('active_courses'); ?></p>
                    </div>
                    <div class="col-6">
                        <i class="mdi mdi-trending-down text-warning mt-3 h3"></i>
                        <h3 class="font-weight-normal">
                            <span><?php echo $pending_course_count; ?></span>
                        </h3>
                        <p class="text-muted mb-0"> <?php echo get_phrase('pending_courses'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card" id = 'unpaid-instructor-revenue'>
            <div class="card-body">
                <h4 class="header-title mb-3"><?php echo get_phrase('requested_withdrawal'); ?>
                    <a href="<?php echo site_url('admin/instructor_payout'); ?>" class="alignToTitle" id ="go-to-instructor-revenue"> <i class="mdi mdi-logout"></i> </a>
                </h4>
                <div class="table-responsive">
                    <?php if ($pending_payout_count > 0): ?>
                        <table class="table table-centered table-hover mb-0">
                            <tbody>
                            <?php foreach ($pending_payouts as $key => $pending_payout):
                                $instructor_details = $this->user_model->get_all_user($pending_payout['user_id'])->row_array();
                            ?>
                            <tr>
                                <td>
                                    <h5 class="font-14 my-1"><a href="javascript:void(0);" class="text-body" style="cursor: auto;"><?php echo $instructor_details['first_name'].' '.$instructor_details['last_name']; ?></a></h5>
                                    <small><?php echo get_phrase('email'); ?>: <span class="text-muted font-13"><?php echo $instructor_details['email']; ?></span></small>
                                </td>
                                <td>
                                    <h5 class="font-14 my-1"><a href="javascript:void(0);" class="text-body" style="cursor: auto;"><?php echo currency($pending_payout['amount']); ?></a></h5>
                                    <small><span class="text-muted font-13"><?php echo get_phrase('requested_withdrawal_amount'); ?></span></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-inline">
                            <h5 class="mb-1">No withdrawal requests need review.</h5>
                            <p class="mb-2">Use this space for payout exceptions, refund risks, and finance follow-ups when they appear.</p>
                            <a href="<?php echo site_url('admin/instructor_payout'); ?>" class="btn btn-outline-primary btn-sm">Open payout report</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('#unpaid-instructor-revenue').mouseenter(function() {
        $('#go-to-instructor-revenue').show();
    });
    $('#unpaid-instructor-revenue').mouseleave(function() {
        $('#go-to-instructor-revenue').hide();
    });
</script>
