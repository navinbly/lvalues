<?php
$user_details = isset($user_details) && is_array($user_details) ? $user_details : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();

$progress_summary = $batch['progress_summary'] ?? [];

$assignment_percent = isset($progress_summary['assignment_percent']) ? (float)$progress_summary['assignment_percent'] : 0;
$test_percent = isset($progress_summary['test_percent']) ? (float)$progress_summary['test_percent'] : 0;
$attendance = isset($progress_summary['attendance_percent']) ? (float)$progress_summary['attendance_percent'] : 0;
$progress = isset($progress_summary['overall_percent']) ? (float)$progress_summary['overall_percent'] : 0;

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
						<div class="col-md-3 mb-2">
							<div class="border rounded p-3 h-100">
								<small class="text-muted d-block">Overall Progress</small>
								<strong><?php echo html_escape((string)$progress); ?>%</strong>
								<div class="progress mt-2" style="height:8px;">
									<div class="progress-bar" role="progressbar"
										 style="width: <?php echo $progress; ?>%;"
										 aria-valuenow="<?php echo $progress; ?>"
										 aria-valuemin="0"
										 aria-valuemax="100"></div>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-2">
							<div class="border rounded p-3 h-100">
								<small class="text-muted d-block">Assignments</small>
								<strong><?php echo html_escape((string)$assignment_percent); ?>%</strong>
								<div class="progress mt-2" style="height:8px;">
									<div class="progress-bar" role="progressbar"
										 style="width: <?php echo $assignment_percent; ?>%;"></div>
								</div>
								<small class="text-muted">
									<?php echo (int)($progress_summary['completed_assignments'] ?? 0); ?>
									/
									<?php echo (int)($progress_summary['total_assignments'] ?? 0); ?>
									completed
								</small>
							</div>
						</div>

						<div class="col-md-3 mb-2">
							<div class="border rounded p-3 h-100">
								<small class="text-muted d-block">Tests</small>
								<strong><?php echo html_escape((string)$test_percent); ?>%</strong>
								<div class="progress mt-2" style="height:8px;">
									<div class="progress-bar" role="progressbar"
										 style="width: <?php echo $test_percent; ?>%;"></div>
								</div>
								<small class="text-muted">Coming in Step 4</small>
							</div>
						</div>

						<div class="col-md-3 mb-2">
							<div class="border rounded p-3 h-100">
								<small class="text-muted d-block">Attendance</small>
								<strong><?php echo html_escape((string)$attendance); ?>%</strong>
								<div class="progress mt-2" style="height:8px;">
									<div class="progress-bar" role="progressbar"
										 style="width: <?php echo $attendance; ?>%;"></div>
								</div>
								<small class="text-muted">Coming in Step 5</small>
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
									<th>Submit</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach (($batch['tasks'] ?? []) as $task): ?>
									<?php
										$is_assignment = (($task['task_type'] ?? '') === 'assignment');
										$is_submitted = !empty($task['submission_id']);
									?>
									<tr>
										<td><?php echo ucfirst(html_escape($task['task_type'] ?? 'assignment')); ?></td>

										<td>
											<strong><?php echo html_escape($task['title'] ?? ''); ?></strong>
										</td>

										<td>
											<?php if (!empty($task['description'])): ?>
												<?php echo nl2br(html_escape($task['description'])); ?><br>
											<?php endif; ?>

											<?php if (!empty($task['max_marks'])): ?>
												<small>Max Marks: <?php echo html_escape($task['max_marks']); ?></small>
											<?php endif; ?>

											<?php if ($is_submitted): ?>
												<br>
												<small class="text-success">
													Submitted on: <?php echo html_escape($task['submitted_at']); ?>
												</small>

												<?php if (!empty($task['marks_obtained'])): ?>
													<br>
													<small>
														Marks: <?php echo html_escape($task['marks_obtained']); ?>
													</small>
												<?php endif; ?>

												<?php if (!empty($task['tutor_remarks'])): ?>
													<br>
													<small>
														Tutor Remarks: <?php echo html_escape($task['tutor_remarks']); ?>
													</small>
												<?php endif; ?>
											<?php endif; ?>
										</td>

										<td><?php echo html_escape((string)($task['due_at'] ?? '')); ?></td>

										<td>
											<?php if ($is_submitted): ?>
												<span class="badge badge-success">Submitted</span>
											<?php else: ?>
												<span class="badge badge-warning">Not Submitted</span>
											<?php endif; ?>
										</td>

										<td>
											<?php if ($is_assignment): ?>
												<button class="btn btn-primary btn-sm" type="button" data-toggle="collapse" data-target="#submitAssignment<?php echo (int)$task['id']; ?>">
													<?php echo $is_submitted ? 'Resubmit' : 'Submit'; ?>
												</button>
											<?php else: ?>
												<span class="text-muted">Test module next</span>
											<?php endif; ?>
										</td>
									</tr>

									<?php if ($is_assignment): ?>
										<tr class="collapse" id="submitAssignment<?php echo (int)$task['id']; ?>">
											<td colspan="6">
												<form method="post"
													  enctype="multipart/form-data"
													  action="<?php echo site_url('student_batch/submit_assignment/' . (int)$task['id']); ?>">

													<div class="row">
														<div class="col-md-4 mb-2">
															<label>Upload File</label>
															<input type="file" name="submission_file" class="form-control">
															<small class="text-muted">Allowed: PDF, DOC, DOCX, JPG, PNG. Max 5 MB.</small>
														</div>

														<div class="col-md-4 mb-2">
															<label>External Link</label>
															<input type="url" name="external_link" class="form-control" placeholder="Google Drive / GitHub link">
														</div>

														<div class="col-md-4 mb-2">
															<label>Comment</label>
															<textarea name="student_comment" class="form-control" rows="2" placeholder="Write your note"></textarea>
														</div>
													</div>

													<button type="submit" class="btn btn-success btn-sm">
														Submit Assignment
													</button>

													<?php if (!empty($task['submission_file'])): ?>
														<a href="<?php echo base_url($task['submission_file']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
															View Uploaded File
														</a>
													<?php endif; ?>

													<?php if (!empty($task['submitted_external_link'])): ?>
														<a href="<?php echo html_escape($task['submitted_external_link']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
															View Submitted Link
														</a>
													<?php endif; ?>
												</form>
											</td>
										</tr>
									<?php endif; ?>

								<?php endforeach; ?>

								<?php if (empty($batch['tasks'])): ?>
									<tr>
										<td colspan="6" class="text-center text-muted">
											No assignments/tests available.
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>

                    <h5 class="mt-4 mb-3">Online Tests</h5>

                    <div class="table-responsive mb-4">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Duration</th>
                                    <th>Marks</th>
                                    <th>Status</th>
                                    <th>Result</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($batch['tests'] ?? []) as $test): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo html_escape($test['title'] ?? ''); ?></strong><br>
                                            <small class="text-muted"><?php echo html_escape($test['description'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo (int)($test['duration_minutes'] ?? 0); ?> min</td>
                                        <td><?php echo html_escape((string)($test['total_marks'] ?? 0)); ?></td>
                                        <td>
                                            <?php if (($test['attempt_status'] ?? '') === 'submitted'): ?>
                                                <span class="badge badge-success">Submitted</span>
                                            <?php elseif (($test['attempt_status'] ?? '') === 'in_progress'): ?>
                                                <span class="badge badge-warning">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Not Attempted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($test['attempt_status'] ?? '') === 'submitted'): ?>
                                                <?php echo html_escape((string)($test['score'] ?? 0)); ?>
                                                /
                                                <?php echo html_escape((string)($test['attempt_total_marks'] ?? $test['total_marks'] ?? 0)); ?>
                                                <br>
                                                <strong><?php echo html_escape((string)($test['percentage'] ?? 0)); ?>%</strong>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($test['attempt_status'] ?? '') === 'submitted'): ?>
                                                <button class="btn btn-secondary btn-sm" disabled>Completed</button>
                                            <?php else: ?>
                                                <a href="<?php echo site_url('student_batch/start_test/' . (int)$test['id']); ?>"
                                                   class="btn btn-primary btn-sm">
                                                    Start Test
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($batch['tests'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No online tests available.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

					</div>
                </div>
            </div>
        </div>
    </div>
</section>
