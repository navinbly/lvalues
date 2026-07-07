<?php
$invites = isset($invites) && is_array($invites) ? $invites : [];
$eligible_students = isset($eligible_students) && is_array($eligible_students) ? $eligible_students : [];
$invite_students = isset($invite_students) && is_array($invite_students) ? $invite_students : $eligible_students;
$enrolled_students = isset($enrolled_students) && is_array($enrolled_students) ? $enrolled_students : [];
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];
$students = isset($students) && is_array($students) ? $students : [];
$sessions = isset($sessions) && is_array($sessions) ? $sessions : [];
$tasks = isset($tasks) && is_array($tasks) ? $tasks : [];
$tests = isset($tests) && is_array($tests) ? $tests : [];
$assignment_submissions = isset($assignment_submissions) && is_array($assignment_submissions) ? $assignment_submissions : [];
$progress_reports = isset($progress_reports) && is_array($progress_reports) ? $progress_reports : [];
$batch_360 = isset($batch_360) && is_array($batch_360) ? $batch_360 : [];
$batch_360_attendance = isset($batch_360['attendance']) && is_array($batch_360['attendance']) ? $batch_360['attendance'] : [];
$batch_360_marks = isset($batch_360['marks']) && is_array($batch_360['marks']) ? $batch_360['marks'] : [];
$batch_360_tests = isset($batch_360['tests']) && is_array($batch_360['tests']) ? $batch_360['tests'] : [];
$batch_360_communication_log = isset($batch_360['communication_log']) && is_array($batch_360['communication_log']) ? $batch_360['communication_log'] : [];
$manage_section = isset($manage_section) ? $manage_section : 'overview';

$batch_filter_tree = [];
foreach ($tutor_registration_tree as $category) {
    $category_id = (int)($category['id'] ?? 0);
    if ($category_id <= 0) {
        continue;
    }

    $category_node = [
        'id' => $category_id,
        'name' => (string)($category['name'] ?? ''),
        'classes' => [],
    ];

    foreach (($category['classes'] ?? []) as $class) {
        $class_id = (int)($class['id'] ?? 0);
        if ($class_id <= 0) {
            continue;
        }

        $class_node = [
            'id' => $class_id,
            'name' => (string)($class['name'] ?? ''),
            'subjects' => [],
        ];

        foreach (($class['subjects'] ?? []) as $subject) {
            $subject_id = (int)($subject['id'] ?? 0);
            if ($subject_id <= 0) {
                continue;
            }

            $class_node['subjects'][] = [
                'id' => $subject_id,
                'name' => (string)($subject['name'] ?? ''),
            ];
        }

        $category_node['classes'][] = $class_node;
    }

    $batch_filter_tree[] = $category_node;
}

if (!function_exists('format_whatsapp_link')) {
    function format_whatsapp_link($phone, $message) {
        $phone = preg_replace('/\D+/', '', (string)$phone);
        if (strlen($phone) == 10) {
            $phone = '91' . $phone;
        }
        return "https://wa.me/" . $phone . "?text=" . rawurlencode($message);
    }
}

if (!function_exists('batch_section_active')) {
    function batch_section_active($current, $target) {
        return $current === $target ? 'active' : '';
    }
}

// phase4_batch_metrics
$student_count = count($students);
$average_progress = 0;
if ($student_count > 0) {
    $progress_total = 0;
    foreach ($students as $student_row) {
        $progress_total += isset($student_row['progress_percent']) ? (float)$student_row['progress_percent'] : 0;
    }
    $average_progress = round($progress_total / max(1, $student_count), 1);
}
$pending_submission_count = 0;
$evaluated_submission_count = 0;
foreach ($assignment_submissions as $submission_row) {
    if (($submission_row['evaluation_status'] ?? '') === 'evaluated') {
        $evaluated_submission_count++;
    } else {
        $pending_submission_count++;
    }
}
$demo_session_count = 0;
$upcoming_session_count = 0;
$ready_recording_count = 0;
foreach ($sessions as $session_row) {
    if (($session_row['session_type'] ?? '') === 'demo_class') {
        $demo_session_count++;
    }
    if (($session_row['session_status'] ?? '') !== 'cancelled' && strtotime(trim(($session_row['session_date'] ?? '') . ' ' . ($session_row['start_time'] ?? ''))) >= strtotime(date('Y-m-d 00:00:00'))) {
        $upcoming_session_count++;
    }
    if (($session_row['recording_status'] ?? '') === 'available' && !empty($session_row['recording_url']) && (empty($session_row['recording_expires_at']) || strtotime((string)$session_row['recording_expires_at']) > time())) {
        $ready_recording_count++;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="page-title mb-1"><?php echo html_escape($batch['title']); ?></h4>
                    <p class="text-muted mb-0">Batch Code: <?php echo html_escape($batch['batch_code']); ?></p>
                </div>
                <a href="<?php echo site_url('tutor_batch'); ?>" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h3><?php echo (int)$summary['students']; ?></h3><p class="mb-0">Students</p></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h3><?php echo (int)$summary['pending_invites']; ?></h3><p class="mb-0">Pending Invites</p></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h3><?php echo (int)$summary['sessions']; ?></h3><p class="mb-0">Sessions</p></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h3><?php echo (int)$summary['tasks']; ?></h3><p class="mb-0">Assignments/Tests</p></div></div></div>
</div>
<div class="row">
    <div class="col-md-3"><div class="card"><div class="card-body"><p class="text-muted mb-1">Demo classes</p><h3><?php echo (int)$demo_session_count; ?></h3><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/schedule'); ?>" class="btn btn-outline-primary btn-sm">Schedule demo</a></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><p class="text-muted mb-1">Average progress</p><h3><?php echo html_escape((string)$average_progress); ?>%</h3><div class="progress" style="height:6px;"><div class="progress-bar" role="progressbar" style="width: <?php echo $average_progress; ?>%;" aria-valuenow="<?php echo $average_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><p class="text-muted mb-1">Pending grading</p><h3><?php echo (int)$pending_submission_count; ?></h3><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/assignments'); ?>" class="btn btn-outline-primary btn-sm">Grade work</a></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><p class="text-muted mb-1">Live class library</p><h3><?php echo (int)$ready_recording_count; ?></h3><small class="text-muted"><?php echo (int)$upcoming_session_count; ?> upcoming sessions</small></div></div></div>
</div>
<!-- phase4_batch_success_dashboard -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Manage Batch</h5>
                <div class="d-flex flex-wrap" style="gap:8px;">
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'overview'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/overview'); ?>">Overview</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'invite'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/invite'); ?>">Invite Students</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'schedule'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/schedule'); ?>">Schedule Live Session</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'tasks'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/tasks'); ?>">Create Assignment/Test</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'students'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/students'); ?>">Students</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'sessions'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/sessions'); ?>">Sessions</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'assignments'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/assignments'); ?>">Assignments/Tests</a>
                    <a class="btn btn-sm btn-outline-primary <?php echo batch_section_active($manage_section, 'progress'); ?>" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/progress'); ?>">Progress Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($manage_section === 'overview'): ?>
<div class="row">
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Reusable Template</h5><p class="text-muted">Save this batch structure including schedule, assignments and tests.</p><form method="post" action="<?php echo site_url('teacher-workflow/template/'.$batch['id']);?>"><input name="name" class="form-control mb-2" value="<?php echo html_escape($batch['title'].' Template');?>" required><button class="btn btn-outline-primary btn-sm">Save as template</button></form></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-body"><h5>Health Components</h5><?php foreach(($batch_health['components']??[]) as $label=>$value):?><div class="d-flex justify-content-between mb-1"><span><?php echo html_escape(ucfirst($label));?></span><strong><?php echo (float)$value;?>%</strong></div><div class="progress mb-2" style="height:5px"><div class="progress-bar" style="width:<?php echo min(100,(float)$value);?>%"></div></div><?php endforeach;?></div></div></div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card"><div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
                <div>
                    <h5 class="mb-1">Batch 360 Workspace</h5>
                    <p class="text-muted mb-0">Students, sessions, attendance, assignments/tests, marks and communication in one working page.</p>
                </div>
                <div class="text-right">
                    <span class="badge badge-info-lighten"><?php echo ucfirst(html_escape($batch['status'] ?? '')); ?></span>
                    <div class="small text-muted mt-1"><?php echo html_escape(($batch['start_date'] ?? '-') . ' to ' . ($batch['end_date'] ?? '-')); ?></div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Students</small><h3 class="mb-0"><?php echo (int)$summary['students']; ?></h3><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/students'); ?>" class="small">View roster</a></div></div>
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Sessions</small><h3 class="mb-0"><?php echo (int)$summary['sessions']; ?></h3><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/sessions'); ?>" class="small">Manage sessions</a></div></div>
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Attendance</small><h3 class="mb-0"><?php echo html_escape((string)($batch_360_attendance['attendance_percent'] ?? 0)); ?>%</h3><small class="text-muted"><?php echo (int)($batch_360_attendance['present_records'] ?? 0); ?>/<?php echo (int)($batch_360_attendance['total_records'] ?? 0); ?> present/late</small></div></div>
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Assignments/Tests</small><h3 class="mb-0"><?php echo (int)$summary['tasks'] + (int)($batch_360_tests['tests'] ?? 0); ?></h3><a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/assignments'); ?>" class="small">Review work</a></div></div>
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Marks overview</small><h3 class="mb-0"><?php echo html_escape((string)($batch_360_marks['average_percent'] ?? 0)); ?>%</h3><small class="text-muted"><?php echo (int)($batch_360_marks['evaluated'] ?? 0); ?>/<?php echo (int)($batch_360_marks['submissions'] ?? 0); ?> evaluated</small></div></div>
                <div class="col-md-2 col-sm-6 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">Communication</small><h3 class="mb-0"><?php echo count($batch_360_communication_log); ?></h3><small class="text-muted">Recent invites, sessions, tasks</small></div></div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Students</h5>
                            <a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/invite'); ?>" class="btn btn-outline-primary btn-sm">Invite</a>
                        </div>
                        <?php if (!empty($students)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Student</th><th>Status</th><th>Progress</th></tr></thead>
                                    <tbody>
                                    <?php foreach (array_slice($students, 0, 5) as $student): ?>
                                        <tr>
                                            <td><?php echo html_escape(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?><br><small class="text-muted"><?php echo html_escape($student['email'] ?? ''); ?></small></td>
                                            <td><?php echo html_escape($student['membership_status'] ?? ''); ?></td>
                                            <td><?php echo html_escape((string)((float)($student['progress_percent'] ?? 0))); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No enrolled students yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Sessions & Attendance</h5>
                            <a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/schedule'); ?>" class="btn btn-outline-primary btn-sm">Schedule</a>
                        </div>
                        <?php if (!empty($sessions)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach (array_slice($sessions, 0, 5) as $session): ?>
                                    <li class="border-bottom py-2">
                                        <strong><?php echo html_escape($session['title'] ?? 'Session'); ?></strong><br>
                                        <small class="text-muted"><?php echo html_escape(($session['session_date'] ?? '') . ' ' . substr((string)($session['start_time'] ?? ''), 0, 5)); ?> · <?php echo html_escape($session['session_status'] ?? ''); ?></small>
                                        <a href="<?php echo site_url('tutor_batch/attendance/' . (int)$session['id']); ?>" class="small ml-2">Attendance</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No sessions scheduled yet.</p>
                        <?php endif; ?>
                        <div class="progress mt-3" style="height:6px;"><div class="progress-bar bg-success" style="width: <?php echo (float)($batch_360_attendance['attendance_percent'] ?? 0); ?>%;"></div></div>
                        <small class="text-muted">Attendance present/late rate</small>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Assignments, Tests & Marks</h5>
                            <a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/tasks'); ?>" class="btn btn-outline-primary btn-sm">Create</a>
                        </div>
                        <p class="text-muted mb-2">Tasks: <?php echo (int)$summary['tasks']; ?> · Online tests: <?php echo (int)($batch_360_tests['tests'] ?? 0); ?> · Published tests: <?php echo (int)($batch_360_tests['published_tests'] ?? 0); ?></p>
                        <?php if (!empty($tasks)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach (array_slice($tasks, 0, 5) as $task): ?>
                                    <li class="border-bottom py-2">
                                        <strong><?php echo ucfirst(html_escape($task['task_type'] ?? 'task')); ?>:</strong> <?php echo html_escape($task['title'] ?? ''); ?><br>
                                        <small class="text-muted">Due <?php echo html_escape((string)($task['due_at'] ?? '')); ?> · Assigned <?php echo (int)($task['assigned_students'] ?? 0); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No assignments/tests created yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h5 class="mb-2">Marks overview</h5>
                        <div class="row text-center">
                            <div class="col"><h4><?php echo (int)($batch_360_marks['submissions'] ?? 0); ?></h4><small class="text-muted">Submissions</small></div>
                            <div class="col"><h4><?php echo (int)($batch_360_marks['evaluated'] ?? 0); ?></h4><small class="text-muted">Evaluated</small></div>
                            <div class="col"><h4><?php echo html_escape((string)($batch_360_marks['average_marks'] ?? 0)); ?></h4><small class="text-muted">Avg marks</small></div>
                            <div class="col"><h4><?php echo html_escape((string)($batch_360_marks['average_percent'] ?? 0)); ?>%</h4><small class="text-muted">Avg %</small></div>
                        </div>
                        <div class="progress mt-3" style="height:6px;"><div class="progress-bar" style="width: <?php echo (float)($batch_360_marks['average_percent'] ?? 0); ?>%;"></div></div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h5 class="mb-2">Communication log</h5>
                        <?php if (!empty($batch_360_communication_log)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Type</th><th>Message</th><th>Status</th><th>When</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($batch_360_communication_log as $log): ?>
                                        <tr>
                                            <td><?php echo html_escape($log['type'] ?? ''); ?></td>
                                            <td><?php echo html_escape($log['title'] ?? ''); ?><br><small class="text-muted"><?php echo html_escape($log['channel'] ?? ''); ?></small></td>
                                            <td><?php echo html_escape($log['status'] ?? ''); ?></td>
                                            <td><small><?php echo html_escape($log['created_at'] ?? ''); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No communication activity yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div></div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.teacherAutosaveForm').forEach(function (form) {
        var timer, badge = document.createElement('small');
        badge.className = 'text-muted ml-2 teacherAutosaveStatus';
        badge.textContent = 'Draft autosave ready';
        var submit = form.querySelector('[type="submit"]');
        if (submit) submit.insertAdjacentElement('afterend', badge);

        function values() {
            var data = {};
            new FormData(form).forEach(function (value, key) {
                if (key.indexOf('csrf') === -1 && !(value instanceof File)) data[key] = value;
            });
            return data;
        }
        function save() {
            var payload = new FormData();
            var csrf = form.querySelector('input[type="hidden"][name*="csrf"]');
            if (csrf) payload.append(csrf.name, csrf.value);
            payload.append('entity_type', form.dataset.entityType);
            payload.append('entity_id', form.dataset.entityId);
            payload.append('editor_key', form.dataset.editorKey);
            payload.append('content', JSON.stringify(values()));
            payload.append('save_type', 'autosave');
            badge.textContent = 'Saving...';
            fetch('<?php echo site_url('teacher-workflow/autosave'); ?>', {method:'POST',body:payload,credentials:'same-origin'})
                .then(function(r){return r.json();}).then(function(r){badge.textContent = r.status ? 'Last saved ' + r.saved_at + ' (v' + r.version + ')' : 'Autosave failed';})
                .catch(function(){badge.textContent='Autosave failed';});
        }
        form.addEventListener('input', function(){clearTimeout(timer);timer=setTimeout(save,1200);});
        var query = new URLSearchParams({entity_type:form.dataset.entityType,entity_id:form.dataset.entityId,editor_key:form.dataset.editorKey});
        fetch('<?php echo site_url('teacher-workflow/versions'); ?>?' + query.toString(), {credentials:'same-origin'})
            .then(function(r){return r.json();}).then(function(r){
                if (!r.versions || !r.versions.length) return;
                var draft = JSON.parse(r.versions[0].content_json || '{}'), hasValue = Object.values(draft).some(function(v){return String(v).trim()!=='';});
                if (hasValue && confirm('A saved draft from ' + r.versions[0].created_at + ' is available. Restore it?')) {
                    Object.keys(draft).forEach(function(key){var field=form.elements[key];if(field&&field.type!=='hidden'){if(field.type==='checkbox')field.checked=!!draft[key];else field.value=draft[key];}});
                    badge.textContent='Recovered draft v'+r.versions[0].version_no;
                }
            });
    });
});
</script>

<?php if ($manage_section === 'invite'): ?>
<div class="row">
    <div class="col-12">
        <div class="card"><div class="card-body">
            <h5>Invite Students</h5>
            <p class="text-muted">Filter by category, class/degree/level and subject. Then select one or multiple students.</p>
            <form method="post" action="<?php echo site_url('tutor_batch/bulk_invite/' . (int)$batch['id']); ?>">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label>Category</label>
                        <select id="invite_category_filter" class="form-control">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Class / Degree / Level</label>
                        <select id="invite_class_filter" class="form-control">
                            <option value="">Select Category First</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label>Subject</label>
                        <select id="invite_subject_filter" class="form-control">
                            <option value="">Select Class First</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label>Select Student(s)</label>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover mb-0" id="invite_student_table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email ID</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width:90px;">CheckBox</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invite_students as $student): ?>
                                    <?php
                                        $status = strtolower((string)($student['existing_invite_status'] ?? ''));
                                        $status_label = $status !== '' ? ucfirst($status) : 'Not invited';
                                        $is_disabled = in_array($status, ['pending', 'accepted'], true);
                                        $student_value = (int)$student['id'].'|'.(int)$student['category_id'].'|'.(int)$student['class_id'].'|'.(int)$student['subject_id'];
                                    ?>
                                    <tr
                                        data-category="<?php echo (int)$student['category_id']; ?>"
                                        data-class="<?php echo (int)$student['class_id']; ?>"
                                        data-subject="<?php echo (int)$student['subject_id']; ?>">
                                        <td><?php echo html_escape(trim(($student['first_name'] ?? '').' '.($student['last_name'] ?? ''))); ?></td>
                                        <td><?php echo html_escape($student['email'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $status === 'accepted' ? 'success' : ($status === 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo html_escape($status_label); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                name="student_ids[]"
                                                value="<?php echo html_escape($student_value); ?>"
                                                <?php echo $is_disabled ? 'disabled' : ''; ?>
                                                aria-label="Select <?php echo html_escape(trim(($student['first_name'] ?? '').' '.($student['last_name'] ?? ''))); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($invite_students)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No eligible students found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Only students without pending/accepted invites can be selected.</small>
                    <div class="invalid-feedback d-block d-none" id="invite_student_error">Please select at least one available student.</div>
                </div>

                <div class="form-group mb-2">
                    <label>Invite Message</label>
                    <textarea name="invite_message" class="form-control" rows="5" required>Hi,

You are invited to join the batch "<?php echo html_escape($batch['title']); ?>".

This batch includes structured live sessions, assignments, tests, recordings, and progress tracking.

Please accept the invitation from your student dashboard.

Regards,
Lvalues Team</textarea>
                    <small class="text-muted">Required. The confirmation after submit will show created, skipped, email and WhatsApp counts.</small>
                </div>
                <div class="form-check mb-2"><input type="checkbox" name="send_email" value="1" class="form-check-input" id="send_email" checked><label class="form-check-label" for="send_email">Send professional email invite</label></div>
                <div class="form-check mb-3"><input type="checkbox" name="prepare_whatsapp" value="1" class="form-check-input" id="prepare_whatsapp" checked><label class="form-check-label" for="prepare_whatsapp">Prepare WhatsApp buttons after invite</label></div>
                <button type="submit" class="btn btn-primary btn-sm">Send Invite</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body">
            <h5>Invites</h5>
            <div class="table-responsive"><table class="table table-sm table-striped">
                <thead><tr><th>Student</th><th>Email</th><th>Subject</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($invites as $invite): ?>
                        <?php $msg = "You have been invited to join batch " . $batch['title'] . ".\n\n" . ($invite['invite_message'] ?? '') . "\n\nOpen invitation: " . site_url('student_batch/invites'); $wa_link = format_whatsapp_link($invite['invite_phone'] ?? '', $msg); ?>
                        <tr>
                            <td><?php echo html_escape(trim(($invite['first_name'] ?? '') . ' ' . ($invite['last_name'] ?? ''))); ?></td>
                            <td><?php echo html_escape($invite['invite_email']); ?></td>
                            <td><?php echo html_escape($invite['target_subject_name'] ?? '-'); ?></td>
                            <td><?php echo ucfirst(html_escape($invite['invite_status'])); ?></td>
                            <td><?php if (!empty($invite['invite_phone'])): ?><a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-sm">WhatsApp</a><?php else: ?><span class="text-muted">No phone</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($invites)): ?><tr><td colspan="5" class="text-center text-muted">No invites</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($manage_section === 'schedule'): ?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
        <div>
            <h5 class="mb-1">Schedule Online Class</h5>
            <p class="text-muted mb-0">Create the class, share the meeting link with enrolled students, enable recording, and publish the replay after class.</p>
        </div>
        <span class="badge badge-info-lighten"><?php echo (int)$upcoming_session_count; ?> upcoming</span>
    </div>
    <form method="post" action="<?php echo site_url('tutor_batch/add_session/' . (int)$batch['id']); ?>" class="teacherAutosaveForm" data-entity-type="batch_session" data-entity-id="<?php echo (int)$batch['id']; ?>" data-editor-key="schedule">
        <div class="row">
            <div class="col-md-12 mb-2"><label>Class title</label><input type="text" name="title" class="form-control" placeholder="Example: Algebra live practice - Chapter 3" required></div>
            <div class="col-md-4 mb-2"><label>Class type</label><select name="session_type" class="form-control" required><?php foreach (['live_class' => 'Live class','demo_class' => 'Demo class','revision' => 'Revision','doubt_session' => 'Doubt session','assignment_discussion' => 'Assignment discussion','test_discussion' => 'Test discussion'] as $value => $label): ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4 mb-2"><label>Visibility</label><select name="session_status" class="form-control" required><?php foreach (['scheduled' => 'Scheduled - visible to students','draft' => 'Draft - hidden until ready'] as $value => $label): ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4 mb-2"><label>Timezone</label><input type="text" name="timezone" class="form-control" value="Asia/Kolkata" placeholder="Timezone"></div>
            <div class="col-md-6 mb-2"><label>Date</label><input type="date" name="session_date" class="form-control" required></div>
            <div class="col-md-3 mb-2"><label>Start time</label><input type="time" name="start_time" class="form-control" required></div>
            <div class="col-md-3 mb-2"><label>End time</label><input type="time" name="end_time" class="form-control" required><small class="text-muted">End time must be after start time.</small></div>
            <div class="col-md-4 mb-2"><label>Class platform</label><select name="provider_type" class="form-control"><?php foreach (['manual','zoom','jitsi','bbb','100ms','custom'] as $provider): ?><option value="<?php echo $provider; ?>"><?php echo strtoupper($provider); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-8 mb-2"><label>Student join link</label><input type="url" name="student_join_url" class="form-control" placeholder="Paste Zoom, Meet, Jitsi, BBB or custom class URL"></div>
            <div class="col-md-8 mb-2"><label>Fallback join link</label><input type="url" name="fallback_join_url" class="form-control" placeholder="Optional backup link if the main platform fails"></div>
            <div class="col-md-4 mb-2"><input type="number" name="reminder_minutes" class="form-control" value="30" min="5" max="10080" placeholder="Reminder minutes"></div>
            <div class="col-md-4 mb-2"><select name="recurrence_frequency" class="form-control"><option value="">One-time session</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="biweekly">Every two weeks</option></select></div>
            <div class="col-md-4 mb-2"><input type="number" name="recurrence_occurrences" class="form-control" value="1" min="1" max="52" placeholder="Occurrences"></div>
            <div class="col-md-2 mb-2"><input type="number" name="join_opens_minutes" class="form-control" value="15" min="0" max="1440" title="Join opens minutes before start"></div>
            <div class="col-md-2 mb-2"><input type="number" name="join_closes_minutes" class="form-control" value="30" min="0" max="1440" title="Join closes minutes after end"></div>
            <div class="col-md-12 mb-2"><textarea name="agenda" rows="2" class="form-control" placeholder="Agenda / notes"></textarea></div>
            <div class="col-md-12 mb-2 form-check"><input type="checkbox" name="is_recording_enabled" value="1" class="form-check-input" id="rec_enabled"><label class="form-check-label" for="rec_enabled">Record this class and publish replay after class</label></div>
            <div class="col-md-12 mb-2 form-check"><input type="checkbox" name="recording_consent_required" value="1" class="form-check-input" id="rec_consent"><label class="form-check-label" for="rec_consent">Require student recording consent before joining</label></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Schedule Class</button>
    </form>
</div></div></div></div>
<?php endif; ?>

<?php if ($manage_section === 'tasks'): ?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
    <h5>Create Assignment / Test</h5>
    <p class="text-muted">Filter accepted/enrolled batch students by category, class/degree/level and subject, then assign the task/test.</p>
    <!-- phase4_lesson_planner -->
<div class="border rounded p-3 mb-3 bg-light">
    <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
        <div>
            <h6 class="mb-1">Lesson planning and worksheet helper</h6>
            <p class="text-muted mb-0">Generate a structured plan locally, then paste the parts you want into the assignment or test fields.</p>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="generateLessonPlanBtn">Generate plan</button>
    </div>
    <div class="row">
        <div class="col-md-4 mb-2"><input type="text" class="form-control form-control-sm" id="lessonTopicInput" placeholder="Topic, e.g. Fractions"></div>
        <div class="col-md-4 mb-2"><input type="text" class="form-control form-control-sm" id="lessonLevelInput" placeholder="Level, e.g. Class 5"></div>
        <div class="col-md-4 mb-2"><input type="text" class="form-control form-control-sm" id="lessonOutcomeInput" placeholder="Outcome, e.g. solve word problems"></div>
    </div>
    <textarea class="form-control" id="lessonPlanOutput" rows="6" readonly placeholder="Generated plan will appear here."></textarea>
</div>
    <form method="post" action="<?php echo site_url('tutor_batch/add_task/' . (int)$batch['id']); ?>" class="teacherAutosaveForm" data-entity-type="batch_task" data-entity-id="<?php echo (int)$batch['id']; ?>" data-editor-key="task">
        <div class="row">
            <div class="col-md-4 mb-2"><label>Category</label><select id="task_category_filter" class="form-control"><option value="">Select Category</option></select></div>
            <div class="col-md-4 mb-2"><label>Class / Degree / Level</label><select id="task_class_filter" class="form-control"><option value="">Select Category First</option></select></div>
            <div class="col-md-4 mb-2"><label>Subject</label><select id="task_subject_filter" class="form-control"><option value="">Select Class First</option></select></div>
        </div>
        <div class="form-group mb-2"><label>Select Student(s)</label><select name="task_student_ids[]" id="task_student_selector" class="form-control" multiple required style="height:190px;">
            <?php foreach ($enrolled_students as $student): ?>
                <option value="<?php echo (int)$student['id'].'|'.(int)$student['category_id'].'|'.(int)$student['class_id'].'|'.(int)$student['subject_id']; ?>" data-category="<?php echo (int)$student['category_id']; ?>" data-class="<?php echo (int)$student['class_id']; ?>" data-subject="<?php echo (int)$student['subject_id']; ?>">
                    <?php echo html_escape(trim(($student['first_name'] ?? '').' '.($student['last_name'] ?? '')).' - '.($student['email'] ?? '').' | '.($student['category_name'] ?? '').' | '.($student['class_name'] ?? '').' | '.($student['subject_name'] ?? '')); ?>
                </option>
            <?php endforeach; ?>
        </select><small class="text-muted">Only accepted/enrolled batch students are available here. Hold CTRL to select multiple students.</small>
        <?php if (empty($enrolled_students)): ?><div class="text-warning small mt-1">No enrolled students found. Invite students first and ask them to accept before creating assignments/tests.</div><?php endif; ?></div>
        <div class="row">
            <div class="col-md-6 mb-2"><input type="text" name="title" class="form-control" placeholder="Title" required><small class="text-muted">A clear success message will confirm whether an assignment or test was created.</small></div>
            <div class="col-md-3 mb-2"><select name="task_type" class="form-control"><option value="assignment">Assignment</option><option value="test">Test</option></select></div>
            <div class="col-md-3 mb-2"><input type="number" name="max_marks" class="form-control" placeholder="Max marks"></div>
            <div class="col-md-6 mb-2"><input type="datetime-local" name="due_at" class="form-control" required><small class="text-muted">Required. Invalid or empty dates are not saved.</small></div>
            <div class="col-md-6 mb-2"><select name="evaluation_status" class="form-control"><option value="published">Published</option><option value="draft">Draft</option><option value="closed">Closed</option></select></div>
            <div class="col-md-6 mb-2"><select name="rubric_id" class="form-control"><option value="">No rubric</option><?php foreach(($assessment_rubrics??[]) as $rubric):?><option value="<?php echo (int)$rubric['id'];?>"><?php echo html_escape($rubric['title']);?></option><?php endforeach;?></select></div>
            <div class="col-md-4 mb-2"><select name="late_policy" class="form-control"><option value="allow">Allow late work</option><option value="deduct">Allow with deduction</option><option value="block">Block after deadline</option></select></div>
            <div class="col-md-4 mb-2"><input type="number" min="0" max="100" step=".01" name="late_penalty_percent" class="form-control" placeholder="Late penalty %"></div>
            <div class="col-md-4 mb-2"><input type="number" min="0" max="20" name="max_retakes" class="form-control" placeholder="Retakes allowed"></div>
            <div class="col-md-12 mb-2"><label><input type="checkbox" name="plagiarism_check_enabled" value="1"> Queue submissions for plagiarism checking</label></div>
            <div class="col-md-12 mb-2"><textarea name="description" rows="3" class="form-control" placeholder="Description"></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Add Task</button>
    </form>
</div></div></div></div>
<?php endif; ?>

<?php if ($manage_section === 'students'): ?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
    <h5>Students</h5>
    <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Name</th><th>Contact</th><th>Parent</th><th>Status</th><th>Progress</th></tr></thead><tbody>
        <?php foreach ($students as $student): ?>
            <tr><td><?php echo html_escape(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?></td><td><a href="mailto:<?php echo html_escape($student['email'] ?? ''); ?>"><?php echo html_escape($student['email'] ?? ''); ?></a><?php if(!empty($student['phone'])):?><br><a href="<?php echo format_whatsapp_link($student['phone'],'Hello from '.$batch['title']);?>" target="_blank">WhatsApp student</a><?php endif;?></td><td><?php echo html_escape($student['parent_name']??'');?><?php if(!empty($student['parent_phone'])):?><br><a href="<?php echo format_whatsapp_link($student['parent_phone'],'Hello regarding '.$batch['title']);?>" target="_blank"><?php echo html_escape($student['parent_phone']);?></a><?php endif;?></td><td><?php echo html_escape($student['membership_status']); ?></td><td><?php $student_progress = isset($student['progress_percent']) ? (float)$student['progress_percent'] : 0; ?><strong><?php echo html_escape((string)$student_progress); ?>%</strong><div class="progress mt-1" style="height:6px;"><div class="progress-bar" role="progressbar" style="width: <?php echo $student_progress; ?>%;"></div></div></td></tr>
        <?php endforeach; ?>
        <?php if (empty($students)): ?><tr><td colspan="5" class="text-muted text-center">No students yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></div></div></div>
<div class="row">
<div class="col-lg-6"><div class="card"><div class="card-body"><h5>Enrollment Requests</h5>
<?php foreach(($enrollment_requests ?? []) as $request):?><div class="border rounded p-2 mb-2"><strong><?php echo html_escape(trim(($request['first_name']??'').' '.($request['last_name']??'')));?></strong> <span class="badge badge-info-lighten"><?php echo html_escape($request['status']);?></span><br><small class="text-muted"><?php echo html_escape($request['parent_name']??'');?> <?php echo html_escape($request['parent_phone']??'');?></small>
<?php if($request['status']==='pending'):?><div class="mt-2"><form class="d-inline" method="post" action="<?php echo site_url('teacher-workflow/enrollment/'.$request['id'].'/approved');?>"><button class="btn btn-success btn-sm">Approve / Waitlist</button></form> <form class="d-inline" method="post" action="<?php echo site_url('teacher-workflow/enrollment/'.$request['id'].'/rejected');?>"><button class="btn btn-outline-danger btn-sm">Reject</button></form></div><?php endif;?></div><?php endforeach;?><?php if(empty($enrollment_requests)):?><p class="text-muted">No enrollment requests.</p><?php endif;?>
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-body"><h5>Waitlist</h5>
<?php foreach(($waitlist ?? []) as $entry):?><div class="border-bottom py-2"><strong>#<?php echo (int)$entry['position'];?> <?php echo html_escape(trim(($entry['first_name']??'').' '.($entry['last_name']??'')));?></strong> <span class="badge badge-warning-lighten"><?php echo html_escape($entry['status']);?></span><br><small class="text-muted"><?php echo html_escape($entry['parent_phone']??'');?></small></div><?php endforeach;?><?php if(empty($waitlist)):?><p class="text-muted">Waitlist is empty.</p><?php endif;?>
</div></div></div></div>
<div class="row"><div class="col-12"><div class="card"><div class="card-body"><h5>Student History</h5>
<?php foreach(($student_history??[]) as $history):?><div class="border-bottom py-2"><strong><?php echo html_escape(ucwords(str_replace('_',' ',$history['event_type'])));?></strong> <span class="text-muted"><?php echo html_escape($history['from_status']??'');?><?php echo !empty($history['to_status'])?' → '.html_escape($history['to_status']):'';?></span><br><small class="text-muted"><?php echo html_escape($history['created_at']);?> · Student #<?php echo (int)$history['student_user_id'];?></small></div><?php endforeach;?><?php if(empty($student_history)):?><p class="text-muted mb-0">No student history recorded yet.</p><?php endif;?>
</div></div></div></div>
<?php endif; ?>

<?php if ($manage_section === 'sessions'): ?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
        <div>
            <h5 class="mb-1">Online Classes & Recordings</h5>
            <p class="text-muted mb-0">Host classes, mark attendance, publish recording links, and review playback analytics from here.</p>
        </div>
        <a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/schedule'); ?>" class="btn btn-primary btn-sm">Schedule Class</a>
    </div>
    <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date/Time</th><th>Class</th><th>Platform</th><th>Replay</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($sessions as $session): ?>
            <?php $existing_meeting_url = $session['student_join_url'] ?? ''; $existing_recording_url = $session['recording_url'] ?? ''; $current_session_status = $session['session_status'] ?? 'scheduled'; $calendar_title = trim((string)($session['title'] ?? 'Live session')); $calendar_date = (string)($session['session_date'] ?? ''); $calendar_start = (string)($session['start_time'] ?? ''); $calendar_end = (string)($session['end_time'] ?? ''); $calendar_agenda = trim((string)($session['agenda'] ?? '')); ?>
            <tr>
                <td><?php echo html_escape(($session['session_date'] ?? '') . ' ' . ($session['start_time'] ?? '') . ' - ' . ($session['end_time'] ?? '')); ?></td>
                <td><strong><?php echo html_escape($session['title']); ?></strong><br><small class="text-muted"><?php echo ucfirst(html_escape($session['session_status'] ?? 'scheduled')); ?> - join opens <?php echo (int)($session['join_opens_minutes'] ?? 15); ?> min before class</small></td>
                <td><?php echo strtoupper(html_escape($session['provider_type'] ?? 'manual')); ?></td>
                <td><?php echo !empty($existing_recording_url) ? '<span class="badge badge-success">Ready for enrolled students</span>' : '<span class="badge badge-secondary">Not published</span>'; ?><?php if(!empty($session['recording_expires_at'])):?><br><small class="text-muted">Until <?php echo html_escape(date('d M Y, h:i A', strtotime($session['recording_expires_at']))); ?></small><?php endif;?></td>
                <td>
                    <a href="<?php echo site_url('tutor_batch/attendance/' . (int)$session['id']); ?>" class="btn btn-outline-info btn-sm mb-2">Mark Attendance</a> <a href="<?php echo site_url('live-learning/join/'.(int)$session['id']);?>" class="btn btn-outline-success btn-sm mb-2">Host</a> <button type="button" class="btn btn-outline-success btn-sm mb-2 liveConnectionTest" data-session="<?php echo (int)$session['id'];?>">Connection Test</button> <button type="button" class="btn btn-outline-dark btn-sm mb-2 liveAnalytics" data-session="<?php echo (int)$session['id'];?>">Playback Analytics</button> <button type="button" class="btn btn-outline-secondary btn-sm mb-2 phase4CalendarBtn" data-title="<?php echo html_escape($calendar_title); ?>" data-date="<?php echo html_escape($calendar_date); ?>" data-start="<?php echo html_escape($calendar_start); ?>" data-end="<?php echo html_escape($calendar_end); ?>" data-url="<?php echo html_escape($existing_meeting_url); ?>" data-description="<?php echo html_escape($calendar_agenda); ?>">Add to calendar</button>
                    <form method="post" action="<?php echo site_url('live-learning/sync-attendance/'.(int)$session['id']);?>" class="d-inline liveSyncAttendance"><button class="btn btn-outline-warning btn-sm mb-2">Sync Attendance</button></form>
                    <div class="small liveResult mb-2" aria-live="polite"></div>
                    <form method="post" action="<?php echo site_url('teacher-workflow/reschedule/'.(int)$session['id']);?>" class="border rounded p-2 mb-2"><strong class="small">Reschedule</strong><div class="form-row"><div class="col"><input type="date" name="session_date" value="<?php echo html_escape($session['session_date']);?>" class="form-control form-control-sm" required></div><div class="col"><input type="time" name="start_time" value="<?php echo html_escape(substr($session['start_time'],0,5));?>" class="form-control form-control-sm" required></div><div class="col"><input type="time" name="end_time" value="<?php echo html_escape(substr($session['end_time'],0,5));?>" class="form-control form-control-sm" required></div></div><button class="btn btn-outline-primary btn-sm mt-2">Check and reschedule</button></form>
                    <form method="post" action="<?php echo site_url('live-learning/incident/'.(int)$session['id']);?>" class="border rounded p-2 mb-2 liveIncident"><strong class="small">Report incident</strong><div class="form-row"><div class="col"><select name="incident_type" class="form-control form-control-sm"><option value="connection">Connection</option><option value="provider">Provider</option><option value="audio">Audio</option><option value="video">Video</option><option value="recording">Recording</option><option value="attendance">Attendance</option><option value="other">Other</option></select></div><div class="col"><select name="severity" class="form-control form-control-sm"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div></div><textarea name="description" class="form-control form-control-sm mt-2" placeholder="What happened?" required></textarea><button class="btn btn-outline-danger btn-sm mt-2">Log incident</button></form>
                    <form method="post" action="<?php echo site_url('tutor_batch/update_session_links/' . (int)$session['id']); ?>" class="border rounded p-2 bg-light">
                        <input type="url" name="meeting_url" class="form-control form-control-sm mb-2" placeholder="Live meeting link" value="<?php echo html_escape($existing_meeting_url); ?>">
                        <input type="url" name="recording_url" class="form-control form-control-sm mb-2" placeholder="Recording replay URL for enrolled students" value="<?php echo html_escape($existing_recording_url); ?>">
                        <input type="url" name="captions_url" class="form-control form-control-sm mb-2" placeholder="Captions / transcript URL" value="<?php echo html_escape($session['captions_url'] ?? ''); ?>">
                        <input type="url" name="fallback_join_url" class="form-control form-control-sm mb-2" placeholder="Fallback meeting URL" value="<?php echo html_escape($session['fallback_join_url'] ?? ''); ?>">
                        <input type="datetime-local" name="recording_expires_at" class="form-control form-control-sm mb-2" value="<?php echo !empty($session['recording_expires_at']) ? html_escape(date('Y-m-d\TH:i',strtotime($session['recording_expires_at']))) : ''; ?>" title="Recording access expiry">
                        <select name="session_status" class="form-control form-control-sm mb-2"><option value="scheduled" <?php echo $current_session_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option><option value="live" <?php echo $current_session_status === 'live' ? 'selected' : ''; ?>>Live</option><option value="completed" <?php echo $current_session_status === 'completed' ? 'selected' : ''; ?>>Completed</option><option value="cancelled" <?php echo $current_session_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select>
                        <button type="submit" class="btn btn-primary btn-sm">Save Links</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sessions)): ?><tr><td colspan="5" class="text-center text-muted">No sessions scheduled.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></div></div></div>
<?php endif; ?>

<?php if ($manage_section === 'assignments'): ?>
<div class="row">
    <div class="col-12"><div class="card"><div class="card-body">
        <h5>Assignments / Tests</h5>
        <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Type</th><th>Title</th><th>Due</th><th>Assigned</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($tasks as $task): ?><tr><td><?php echo ucfirst(html_escape($task['task_type'])); ?></td><td><?php echo html_escape($task['title']); ?></td><td><?php echo html_escape((string)$task['due_at']); ?></td><td><?php echo (int)($task['assigned_students'] ?? 0); ?></td><td><?php echo html_escape($task['evaluation_status']); ?></td></tr><?php endforeach; ?>
            <?php if (empty($tasks)): ?><tr><td colspan="5" class="text-center text-muted">No tasks created.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div></div></div>

    <div class="col-12"><div class="card"><div class="card-body">
        <h5>Assignment Submissions</h5>
        <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Assignment</th><th>Student</th><th>Submitted</th><th>File / Link</th><th>Status</th><th>Evaluate</th></tr></thead><tbody>
            <?php foreach ($assignment_submissions as $sub): ?>
                <tr>
                    <td><strong><?php echo html_escape($sub['task_title']); ?></strong><br><small>Max: <?php echo html_escape((string)$sub['max_marks']); ?></small></td>
                    <td><?php echo html_escape(trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''))); ?><br><small><?php echo html_escape($sub['email'] ?? ''); ?></small></td>
                    <td><?php echo html_escape($sub['submitted_at']); ?></td>
                    <td><?php if (!empty($sub['submission_file'])): ?><a href="<?php echo base_url($sub['submission_file']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">View File</a><?php endif; ?> <?php if (!empty($sub['external_link'])): ?><a href="<?php echo html_escape($sub['external_link']); ?>" target="_blank" class="btn btn-outline-info btn-sm">Open Link</a><?php endif; ?><?php if (!empty($sub['student_comment'])): ?><br><small>Comment: <?php echo html_escape($sub['student_comment']); ?></small><?php endif; ?></td>
                    <td><?php if (($sub['evaluation_status'] ?? '') === 'evaluated'): ?><span class="badge badge-success">Evaluated</span><br><small>Marks: <?php echo html_escape((string)$sub['marks_obtained']); ?></small><?php else: ?><span class="badge badge-warning">Pending</span><?php endif; ?></td>
                    <td><form method="post" action="<?php echo site_url('tutor_batch/evaluate_assignment/' . (int)$sub['id']); ?>"><input type="number" step="0.01" name="marks_obtained" class="form-control form-control-sm mb-1" placeholder="Marks" value="<?php echo html_escape((string)($sub['marks_obtained'] ?? '')); ?>"><textarea name="tutor_remarks" rows="2" class="form-control form-control-sm mb-1" placeholder="Teacher remarks"><?php echo html_escape($sub['tutor_remarks'] ?? $sub['tutor_feedback'] ?? ''); ?></textarea><button class="btn btn-primary btn-sm" type="submit">Save</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assignment_submissions)): ?><tr><td colspan="6" class="text-center text-muted">No submissions yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div></div></div>

    <?php if (!empty($tests)): ?>
    <div class="col-12"><div class="card"><div class="card-body">
        <h5>Existing Tests</h5>
        <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Title</th><th>Status</th><th>Duration</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($tests as $test): ?><tr><td><?php echo html_escape($test['title']); ?></td><td><?php echo html_escape($test['status'] ?? 'draft'); ?></td><td><?php echo html_escape((string)($test['duration_minutes'] ?? '')); ?> min</td><td><?php if (($test['status'] ?? '') !== 'published'): ?><form method="post" action="<?php echo site_url('tutor_batch/publish_test/' . (int)$test['id']); ?>" style="display:inline;" onsubmit="return confirm('Publish this test? Students will be able to attempt it.');"><button class="btn btn-success btn-sm">Publish</button></form><?php endif; ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div></div></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($manage_section === 'progress'): ?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
    <div class="d-flex align-items-start justify-content-between flex-wrap mb-3">
        <div>
            <h5 class="mb-1">Student Progress Reports</h5>
            <p class="text-muted mb-0">Foundation report data for attendance, assignment completion, test score trend and teacher remarks. PDF export will come later.</p>
        </div>
        <span class="badge badge-info-lighten mt-1">Data first - PDF later</span>
    </div>

    <?php if (!empty($progress_reports)): ?>
        <?php foreach ($progress_reports as $report): ?>
            <?php
                $student = $report['student'] ?? [];
                $summary = $report['summary'] ?? [];
                $trend = $report['test_trend'] ?? [];
                $attempts = isset($report['test_attempts']) && is_array($report['test_attempts']) ? $report['test_attempts'] : [];
                $remarks = isset($report['teacher_remarks']) && is_array($report['teacher_remarks']) ? $report['teacher_remarks'] : [];
                $trend_class = (($trend['direction'] ?? '') === 'up') ? 'success' : ((($trend['direction'] ?? '') === 'down') ? 'danger' : 'secondary');
                $student_name = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
            ?>
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h5 class="mb-1"><?php echo html_escape($student_name ?: 'Student'); ?></h5>
                        <p class="text-muted mb-0"><?php echo html_escape($student['email'] ?? ''); ?> - <?php echo html_escape($student['membership_status'] ?? ''); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="h4 mb-0"><?php echo html_escape((string)($summary['overall_percent'] ?? 0)); ?>%</div>
                        <small class="text-muted">Overall progress</small>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-3"><div class="bg-light rounded p-3 h-100"><small class="text-muted">Attendance</small><h4 class="mb-1"><?php echo html_escape((string)($summary['attendance_percent'] ?? 0)); ?>%</h4><div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width: <?php echo (float)($summary['attendance_percent'] ?? 0); ?>%;"></div></div></div></div>
                    <div class="col-md-3 col-sm-6 mb-3"><div class="bg-light rounded p-3 h-100"><small class="text-muted">Assignment completion</small><h4 class="mb-1"><?php echo html_escape((string)($summary['assignment_percent'] ?? 0)); ?>%</h4><small><?php echo (int)($summary['completed_assignments'] ?? 0); ?>/<?php echo (int)($summary['total_assignments'] ?? 0); ?> completed</small></div></div>
                    <div class="col-md-3 col-sm-6 mb-3"><div class="bg-light rounded p-3 h-100"><small class="text-muted">Average test score</small><h4 class="mb-1"><?php echo html_escape((string)($summary['test_percent'] ?? 0)); ?>%</h4><span class="badge badge-<?php echo $trend_class; ?>-lighten"><?php echo html_escape($trend['label'] ?? 'No trend'); ?></span></div></div>
                    <div class="col-md-3 col-sm-6 mb-3"><div class="bg-light rounded p-3 h-100"><small class="text-muted">Teacher remarks</small><h4 class="mb-1"><?php echo count($remarks); ?></h4><small>Recent evaluated feedback notes</small></div></div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <h6>Test score trend</h6>
                        <?php if (!empty($attempts)): ?>
                            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Test</th><th>Score</th><th>%</th><th>Submitted</th></tr></thead><tbody>
                                <?php foreach ($attempts as $attempt): ?>
                                    <tr><td><?php echo html_escape($attempt['title'] ?? 'Test'); ?></td><td><?php echo html_escape((string)($attempt['score'] ?? 0)); ?> / <?php echo html_escape((string)($attempt['total_marks'] ?? 0)); ?></td><td><?php echo html_escape((string)($attempt['percentage'] ?? 0)); ?>%</td><td><small><?php echo html_escape($attempt['submitted_at'] ?? ''); ?></small></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No submitted tests yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <h6>Teacher remarks</h6>
                        <?php if (!empty($remarks)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($remarks as $remark): ?>
                                    <li class="border-bottom py-2"><strong><?php echo html_escape($remark['task_title'] ?? 'Assignment'); ?></strong><small class="text-muted"> - <?php echo html_escape((string)($remark['marks_obtained'] ?? 0)); ?> / <?php echo html_escape((string)($remark['max_marks'] ?? 0)); ?></small><br><span><?php echo html_escape($remark['tutor_remarks'] ?? ''); ?></span><br><small class="text-muted"><?php echo html_escape($remark['evaluated_at'] ?? ''); ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">No teacher remarks yet. Add remarks while grading assignments.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center text-muted py-4">
            <p>No enrolled students found for progress reports.</p>
            <a href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id'] . '/invite'); ?>" class="btn btn-outline-primary btn-sm">Invite students</a>
        </div>
    <?php endif; ?>
</div></div></div></div>
<?php endif; ?>

<script>
(function () {
    const batchFilterTree = <?php echo json_encode($batch_filter_tree, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    function clearSelect(select, placeholder) {
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
    }

    function appendOption(select, value, label, dataset) {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = label;
        Object.keys(dataset || {}).forEach(function (key) {
            option.dataset[key] = String(dataset[key]);
        });
        select.appendChild(option);
    }

    function findCategory(categoryId) {
        return batchFilterTree.find(function (category) {
            return String(category.id) === String(categoryId);
        }) || null;
    }

    function findClass(category, classId) {
        if (!category) return null;
        return (category.classes || []).find(function (item) {
            return String(item.id) === String(classId);
        }) || null;
    }

    function refreshStudentVisibility(categoryFilter, classFilter, subjectFilter, studentSelector, studentRows) {
        const selectedCategory = categoryFilter.value;
        const selectedClass = classFilter.value;
        const selectedSubject = subjectFilter.value;
        const studentOptions = studentSelector ? Array.from(studentSelector.options) : [];

        studentOptions.forEach(function (option) {
            const categoryMatch = !selectedCategory || option.dataset.category === selectedCategory;
            const classMatch = !selectedClass || option.dataset.class === selectedClass;
            const subjectMatch = !selectedSubject || option.dataset.subject === selectedSubject;
            const visible = categoryMatch && classMatch && subjectMatch;
            option.hidden = !visible;
            option.disabled = !visible;
            if (!visible) option.selected = false;
        });

        studentRows.forEach(function (row) {
            const categoryMatch = !selectedCategory || row.dataset.category === selectedCategory;
            const classMatch = !selectedClass || row.dataset.class === selectedClass;
            const subjectMatch = !selectedSubject || row.dataset.subject === selectedSubject;
            const visible = categoryMatch && classMatch && subjectMatch;
            row.style.display = visible ? '' : 'none';
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox && !visible) checkbox.checked = false;
        });
    }

    function initStudentFilter(prefix) {
        const categoryFilter = document.getElementById(prefix + '_category_filter');
        const classFilter = document.getElementById(prefix + '_class_filter');
        const subjectFilter = document.getElementById(prefix + '_subject_filter');
        const studentSelector = document.getElementById(prefix + '_student_selector');
        const studentTable = document.getElementById(prefix + '_student_table');
        if (!categoryFilter || !classFilter || !subjectFilter || (!studentSelector && !studentTable)) return;
        const studentRows = studentTable ? Array.from(studentTable.querySelectorAll('tbody tr[data-category]')) : [];

        function populateCategories() {
            clearSelect(categoryFilter, 'Select Category');
            batchFilterTree.forEach(function (category) {
                appendOption(categoryFilter, category.id, category.name, {});
            });
        }

        function refreshClasses() {
            const selectedCategory = categoryFilter.value;
            clearSelect(classFilter, selectedCategory ? 'Select Class / Course Group' : 'Select Category First');
            clearSelect(subjectFilter, 'Select Class First');
            classFilter.disabled = !selectedCategory;
            subjectFilter.disabled = true;

            const category = findCategory(selectedCategory);
            if (category) {
                (category.classes || []).forEach(function (item) {
                    appendOption(classFilter, item.id, item.name, {category: selectedCategory});
                });
            }

            refreshStudentVisibility(categoryFilter, classFilter, subjectFilter, studentSelector, studentRows);
        }

        function refreshSubjects() {
            const selectedCategory = categoryFilter.value;
            const selectedClass = classFilter.value;
            clearSelect(subjectFilter, selectedClass ? 'Select Subject' : 'Select Class First');
            subjectFilter.disabled = !selectedClass;

            const category = findCategory(selectedCategory);
            const classNode = findClass(category, selectedClass);
            if (category && classNode) {
                (classNode.subjects || []).forEach(function (subject) {
                    appendOption(
                        subjectFilter,
                        subject.id,
                        subject.name + ' — ' + classNode.name + ' (' + category.name + ')',
                        {category: selectedCategory, class: selectedClass}
                    );
                });
            }

            refreshStudentVisibility(categoryFilter, classFilter, subjectFilter, studentSelector, studentRows);
        }

        function refreshStudents() {
            refreshStudentVisibility(categoryFilter, classFilter, subjectFilter, studentSelector, studentRows);
        }

        categoryFilter.addEventListener('change', refreshClasses);
        classFilter.addEventListener('change', refreshSubjects);
        subjectFilter.addEventListener('change', refreshStudents);
        populateCategories();
        classFilter.disabled = true;
        subjectFilter.disabled = true;
        refreshStudentVisibility(categoryFilter, classFilter, subjectFilter, studentSelector, studentRows);
    }
    initStudentFilter('invite');
    initStudentFilter('task');
    const inviteForm = document.querySelector('form[action*="tutor_batch/bulk_invite"]');
    if (inviteForm) {
        inviteForm.addEventListener('submit', function(event) {
            const checked = inviteForm.querySelectorAll('input[name="student_ids[]"]:checked').length;
            const error = document.getElementById('invite_student_error');
            if (checked <= 0) {
                event.preventDefault();
                if (error) error.classList.remove('d-none');
            } else if (error) {
                error.classList.add('d-none');
            }
        });
    }
})();
</script>

<!-- phase4_calendar_and_planner_js -->
<script>
(function () {
    function csrfFormData() {
        const data = new FormData();
        const token = document.querySelector('input[type="hidden"][name="<?php echo $this->security->get_csrf_token_name(); ?>"]');
        if (token) data.append(token.name, token.value);
        return data;
    }
    function showLiveResult(button, message, ok) {
        const result = button.closest('td').querySelector('.liveResult');
        if (!result) return;
        result.className = 'small liveResult mb-2 text-' + (ok ? 'success' : 'danger');
        result.textContent = message;
    }
    document.querySelectorAll('.liveConnectionTest').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = csrfFormData();
            data.append('session_id', button.dataset.session);
            button.disabled = true;
            fetch('<?php echo site_url('live-learning/connection-test'); ?>', {method:'POST', body:data, credentials:'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    showLiveResult(button, result.reachable ? 'Connection ready (' + result.latency_ms + ' ms).' : (result.error || 'Meeting link is not reachable.'), !!result.reachable);
                })
                .catch(function () { showLiveResult(button, 'Connection test failed.', false); })
                .finally(function () { button.disabled = false; });
        });
    });
    document.querySelectorAll('.liveAnalytics').forEach(function (button) {
        button.addEventListener('click', function () {
            fetch('<?php echo site_url('live-learning/analytics'); ?>/' + button.dataset.session, {credentials:'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    const analytics = result.analytics || {};
                    showLiveResult(button, (analytics.unique_viewers || 0) + ' viewer(s), ' + (analytics.total_events || 0) + ' playback event(s), furthest position ' + (analytics.furthest_position || 0) + 's.', true);
                })
                .catch(function () { showLiveResult(button, 'Playback analytics could not be loaded.', false); });
        });
    });
    document.querySelectorAll('.liveIncident').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"],button:not([type])');
            fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin'})
                .then(function (response) { return response.json(); })
                .then(function (result) {
                    showLiveResult(button, result.message || (result.status ? 'Incident recorded.' : 'Incident could not be recorded.'), !!result.status);
                    if (result.status) form.querySelector('textarea').value = '';
                })
                .catch(function () { showLiveResult(button, 'Incident could not be recorded.', false); });
        });
    });
    function toCalendarDate(dateValue, timeValue) {
        if (!dateValue) return '';
        const safeTime = timeValue || '00:00';
        return dateValue.replace(/-/g, '') + 'T' + safeTime.replace(':', '') + '00';
    }
    document.querySelectorAll('.phase4CalendarBtn').forEach(function (button) {
        button.addEventListener('click', function () {
            const title = button.dataset.title || 'Lvalues session';
            const start = toCalendarDate(button.dataset.date, button.dataset.start);
            const end = toCalendarDate(button.dataset.date, button.dataset.end || button.dataset.start);
            const description = [button.dataset.description || '', button.dataset.url ? 'Join: ' + button.dataset.url : ''].filter(Boolean).join('\n');
            if (!start) return;
            const ics = [
                'BEGIN:VCALENDAR',
                'VERSION:2.0',
                'PRODID:-//Lvalues//Tutor Session//EN',
                'BEGIN:VEVENT',
                'UID:' + Date.now() + '@lvalues.local',
                'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z',
                'DTSTART:' + start,
                'DTEND:' + end,
                'SUMMARY:' + title,
                'DESCRIPTION:' + description.replace(/\n/g, '\\n'),
                button.dataset.url ? 'URL:' + button.dataset.url : '',
                'END:VEVENT',
                'END:VCALENDAR'
            ].filter(Boolean).join('\r\n');
            const blob = new Blob([ics], {type: 'text/calendar;charset=utf-8'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = title.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() + '.ics';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        });
    });

    const planBtn = document.getElementById('generateLessonPlanBtn');
    if (planBtn) {
        planBtn.addEventListener('click', function () {
            const topic = document.getElementById('lessonTopicInput').value || 'today\'s topic';
            const level = document.getElementById('lessonLevelInput').value || 'the selected student level';
            const outcome = document.getElementById('lessonOutcomeInput').value || 'clear understanding and independent practice';
            document.getElementById('lessonPlanOutput').value =
                'Lesson plan for ' + topic + ' (' + level + ')\n\n' +
                '1. Warm-up: 3 quick questions to check prior knowledge.\n' +
                '2. Teaching goal: Students should be able to ' + outcome + '.\n' +
                '3. Demo activity: Solve one guided example with tutor prompts.\n' +
                '4. Practice set: 5 easy, 5 medium, and 2 challenge questions.\n' +
                '5. Worksheet instructions: Show steps, mark doubts, submit before the due date.\n' +
                '6. Review note: Add feedback and next action after grading.';
        });
    }
})();
</script>
