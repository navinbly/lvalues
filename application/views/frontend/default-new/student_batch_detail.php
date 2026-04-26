<?php
$user_details = isset($user_details) && is_array($user_details) ? $user_details : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$progress = isset($batch['progress_percent']) ? (float)$batch['progress_percent'] : 0;
$attendance = isset($batch['attendance_percent']) ? (float)$batch['attendance_percent'] : 0;
?>
<?php include 'breadcrumb.php'; ?>
<section class="wish-list-body message pb-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <?php include 'profile_menus.php'; ?>
            </div>
            <div class="col-lg-9 col-md-8">
                <div class="common-card p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><?php echo html_escape($batch['title'] ?? $batch['batch_title'] ?? 'Batch Detail'); ?></h4>
                            <p class="text-muted mb-0"><?php echo html_escape($batch['description'] ?? $batch['batch_description'] ?? ''); ?></p>
                        </div>
                        <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">Back to My Batches</a>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Progress</small>
                                <strong><?php echo html_escape((string)$progress); ?>%</strong>
                                <div class="progress mt-2" style="height:8px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Attendance</small>
                                <strong><?php echo html_escape((string)$attendance); ?>%</strong>
                                <div class="progress mt-2" style="height:8px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $attendance; ?>%;" aria-valuenow="<?php echo $attendance; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Tutor</small>
                                <strong><?php echo html_escape(trim(($batch['tutor_first_name'] ?? '') . ' ' . ($batch['tutor_last_name'] ?? ''))); ?></strong>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3">Live Sessions</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Session</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($batch['sessions'] ?? []) as $session): ?>
                                    <tr>
                                        <td><?php echo html_escape($session['session_date'] ?? ''); ?></td>
                                        <td>
                                            <strong><?php echo html_escape($session['title'] ?? ''); ?></strong>
                                            <?php if (!empty($session['agenda'])): ?>
                                                <br><small class="text-muted"><?php echo html_escape($session['agenda']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo html_escape(($session['start_time'] ?? '') . ' - ' . ($session['end_time'] ?? '')); ?></td>
                                        <td><?php echo ucfirst(html_escape($session['session_status'] ?? 'scheduled')); ?></td>
                                        <td>
                                            <?php if (!empty($session['student_join_url'])): ?>
                                                <a href="<?php echo html_escape($session['student_join_url']); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Join Live</a>
                                            <?php else: ?>
                                                <span class="text-muted">Not available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($batch['sessions'])): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No sessions scheduled.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-3">Recordings</h5>
                    <div class="row mb-4">
                        <?php foreach (($batch['recordings'] ?? []) as $recording): ?>
                            <?php $recording_url = !empty($recording['embed_url']) ? $recording['embed_url'] : ($recording['playback_url'] ?? ''); ?>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6><?php echo html_escape($recording['title'] ?: 'Session Recording'); ?></h6>
                                    <?php if (!empty($recording_url)): ?>
                                        <?php if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $recording_url)): ?>
                                            <video width="100%" controls controlsList="nodownload" oncontextmenu="return false;">
                                                <source src="<?php echo html_escape($recording_url); ?>">
                                                Your browser does not support video playback.
                                            </video>
                                        <?php else: ?>
                                            <a href="<?php echo html_escape($recording_url); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">View Recording</a>
                                        <?php endif; ?>
                                        <small class="d-block text-muted mt-2">Download option is disabled in the student interface.</small>
                                    <?php else: ?>
                                        <span class="text-muted">Recording link not available.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($batch['recordings'])): ?>
                            <div class="col-12"><div class="border rounded p-3 text-muted">No recordings available yet.</div></div>
                        <?php endif; ?>
                    </div>

                    <h5 class="mb-3">Assignments / Tests</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Details</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($batch['tasks'] ?? []) as $task): ?>
                                    <tr>
                                        <td><?php echo ucfirst(html_escape($task['task_type'] ?? 'assignment')); ?></td>
                                        <td><strong><?php echo html_escape($task['title'] ?? ''); ?></strong></td>
                                        <td>
                                            <?php if (!empty($task['description'])): ?>
                                                <?php echo nl2br(html_escape($task['description'])); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($task['max_marks'])): ?>
                                                <small>Max Marks: <?php echo html_escape($task['max_marks']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo html_escape((string)($task['due_at'] ?? '')); ?></td>
                                        <td><?php echo ucfirst(html_escape($task['assignment_status'] ?? $task['evaluation_status'] ?? 'assigned')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($batch['tasks'])): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No assignments/tests available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
