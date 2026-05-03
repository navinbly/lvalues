<?php
$invites = isset($invites) && is_array($invites) ? $invites : [];
$eligible_students = isset($eligible_students) && is_array($eligible_students) ? $eligible_students : [];
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];

if (!function_exists('format_whatsapp_link')) {
    function format_whatsapp_link($phone, $message) {
        $phone = preg_replace('/\D+/', '', (string)$phone);
        if (strlen($phone) == 10) {
            $phone = '91' . $phone;
        }
        return "https://wa.me/" . $phone . "?text=" . rawurlencode($message);
    }
}

if (!function_exists('student_context_option_value')) {
    function student_context_option_value($student) {
        return (int)$student['id'] . '|' .
               (int)$student['category_id'] . '|' .
               (int)$student['class_id'] . '|' .
               (!empty($student['subject_interest_id']) ? (int)$student['subject_interest_id'] : '');
    }
}

if (!function_exists('student_context_label')) {
    function student_context_label($student) {
        $status_text = '';
        if (!empty($student['existing_invite_status'])) {
            $status_text = ' | ' . ucfirst($student['existing_invite_status']);
        }

        return trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) .
            ' - ' . ($student['email'] ?? '') .
            ' | ' . ($student['category_name'] ?? '') .
            ' | ' . ($student['class_name'] ?? '') .
            (!empty($student['subject_name']) ? ' | ' . $student['subject_name'] : '') .
            $status_text;
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
    <div class="col-md-6">

        <div class="card">
            <div class="card-body">
                <h5>Invite Students</h5>
                <p class="text-muted">Filter by category, class/degree/level and subject. Then select one or multiple students.</p>

                <form method="post" action="<?php echo site_url('tutor_batch/bulk_invite/' . (int)$batch['id']); ?>">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Category</label>
                            <select id="invite_category_filter" class="form-control student-filter-category" data-prefix="invite">
                                <option value="">Select Category</option>
                                <?php foreach ($tutor_registration_tree as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>"><?php echo html_escape($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Class / Degree / Level</label>
                            <select id="invite_class_filter" class="form-control student-filter-class" data-prefix="invite">
                                <option value="">Select Class / Degree / Level</option>
                                <?php foreach ($tutor_registration_tree as $category): ?>
                                    <?php foreach (($category['classes'] ?? []) as $class): ?>
                                        <option value="<?php echo (int)$class['id']; ?>" data-category="<?php echo (int)$category['id']; ?>">
                                            <?php echo html_escape($class['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Subject</label>
                            <select id="invite_subject_filter" class="form-control student-filter-subject" data-prefix="invite">
                                <option value="">Select Subject</option>
                                <?php foreach ($tutor_registration_tree as $category): ?>
                                    <?php foreach (($category['classes'] ?? []) as $class): ?>
                                        <?php foreach (($class['subjects'] ?? []) as $subject): ?>
                                            <option value="<?php echo (int)$subject['id']; ?>" data-category="<?php echo (int)$category['id']; ?>" data-class="<?php echo (int)$class['id']; ?>">
                                                <?php echo html_escape($subject['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label>Select Student(s)</label>
                        <select name="student_ids[]" id="invite_student_selector" class="form-control student-context-selector" data-prefix="invite" multiple required style="height:190px;">
                            <?php foreach ($eligible_students as $student): ?>
                                <?php
                                    $status = strtolower((string)($student['existing_invite_status'] ?? ''));
                                    $disabled = in_array($status, ['pending', 'accepted'], true);
                                ?>
                               <option
								value="<?php echo (int)$student['id'].'|'.(int)$student['category_id'].'|'.(int)$student['class_id'].'|'.(int)$student['subject_id']; ?>"
								data-category="<?php echo (int)$student['category_id']; ?>"
								data-class="<?php echo (int)$student['class_id']; ?>"
								data-subject="<?php echo (int)$student['subject_id']; ?>"
								<?php echo in_array(($student['existing_invite_status'] ?? ''), ['pending', 'accepted']) ? 'disabled' : ''; ?>>
								<?php
									echo html_escape(
										trim($student['first_name'].' '.$student['last_name']) .
										' - ' . $student['email'] .
										' | ' . $student['category_name'] .
										' | ' . $student['class_name'] .
										' | ' . $student['subject_name'] .
										(!empty($student['existing_invite_status']) ? ' | ' . ucfirst($student['existing_invite_status']) : '')
									);
								?>
							</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold CTRL to select multiple students. Pending/accepted invite rows are shown but disabled.</small>
                    </div>

                    <div class="form-group mb-2">
                        <label>Invite Message</label>
                        <textarea name="invite_message" class="form-control" rows="5" required>Hi,

You are invited to join the batch "<?php echo html_escape($batch['title']); ?>".

This batch includes structured live sessions, assignments, tests, recordings, and progress tracking.

Please accept the invitation from your student dashboard.

Regards,
Lvalues Team</textarea>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="send_email" checked>
                        <label class="form-check-label" for="send_email">Send professional email invite</label>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="prepare_whatsapp" value="1" class="form-check-input" id="prepare_whatsapp" checked>
                        <label class="form-check-label" for="prepare_whatsapp">Prepare WhatsApp buttons after invite</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Send Invite</button>
                </form>
            </div>
        </div>

        <div class="card"><div class="card-body">
            <h5>Invites</h5>
            <div class="table-responsive"><table class="table table-sm table-striped">
                <thead><tr><th>Student</th><th>Email</th><th>Subject</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($invites as $invite): ?>
                        <?php
                        $msg = "You have been invited to join batch " . $batch['title'] . ".\n\n" .
                               ($invite['invite_message'] ?? '') . "\n\n" .
                               "Open invitation: " . site_url('student_batch/invites');
                        $wa_link = format_whatsapp_link($invite['invite_phone'] ?? '', $msg);
                        ?>
                        <tr>
                            <td><?php echo html_escape(trim(($invite['first_name'] ?? '') . ' ' . ($invite['last_name'] ?? ''))); ?></td>
                            <td><?php echo html_escape($invite['invite_email']); ?></td>
                            <td>
                                <?php
                                    $subject_name = $invite['target_subject_name'] ?? '';
                                    echo html_escape($subject_name ?: '-');
                                ?>
                            </td>
                            <td><?php echo ucfirst(html_escape($invite['invite_status'])); ?></td>
                            <td>
                                <?php if (!empty($invite['invite_phone'])): ?>
                                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success btn-sm">WhatsApp</a>
                                <?php else: ?>
                                    <span class="text-muted">No phone</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($invites)): ?><tr><td colspan="5" class="text-center text-muted">No invites</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>

        <div class="card"><div class="card-body">
            <h5>Students</h5>
            <div class="table-responsive"><table class="table table-sm table-striped">
                <thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Status</th>
					<th>Progress</th>
				</tr>
				</thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo html_escape(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?></td>
                            <td><?php echo html_escape($student['email'] ?? ''); ?></td>
                            <td><?php echo html_escape($student['membership_status']); ?></td>
                            <td>
								<?php $student_progress = isset($student['progress_percent']) ? (float)$student['progress_percent'] : 0; ?>
								<strong><?php echo html_escape((string)$student_progress); ?>%</strong>
								<div class="progress mt-1" style="height:6px;">
									<div class="progress-bar" role="progressbar"
										 style="width: <?php echo $student_progress; ?>%;"></div>
								</div>
							</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?><tr><td colspan="4" class="text-muted text-center">No students yet.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>

    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Schedule Live Session</h5>
            <form method="post" action="<?php echo site_url('tutor_batch/add_session/' . (int)$batch['id']); ?>">
                <div class="row">
                    <div class="col-md-12 mb-2"><input type="text" name="title" class="form-control" placeholder="Session title" required></div>
                    <div class="col-md-6 mb-2"><input type="date" name="session_date" class="form-control" required></div>
                    <div class="col-md-3 mb-2"><input type="time" name="start_time" class="form-control" required></div>
                    <div class="col-md-3 mb-2"><input type="time" name="end_time" class="form-control" required></div>
                    <div class="col-md-4 mb-2">
                        <select name="provider_type" class="form-control">
                            <?php foreach (['manual','zoom','jitsi','bbb','100ms','custom'] as $provider): ?>
                            <option value="<?php echo $provider; ?>"><?php echo strtoupper($provider); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 mb-2"><input type="text" name="student_join_url" class="form-control" placeholder="Student join URL"></div>
                    <div class="col-md-12 mb-2"><textarea name="agenda" rows="2" class="form-control" placeholder="Agenda / notes"></textarea></div>
                    <div class="col-md-12 mb-2 form-check"><input type="checkbox" name="is_recording_enabled" value="1" class="form-check-input" id="rec_enabled"><label class="form-check-label" for="rec_enabled">Enable recording</label></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Add Session</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body">
            <h5>Create Assignment / Test</h5>
            <p class="text-muted">Filter students by category, class/degree/level and subject, then assign the task/test.</p>

            <form method="post" action="<?php echo site_url('tutor_batch/add_task/' . (int)$batch['id']); ?>">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label>Category</label>
                        <select id="task_category_filter" class="form-control student-filter-category" data-prefix="task">
                            <option value="">Select Category</option>
                            <?php foreach ($tutor_registration_tree as $category): ?>
                                <option value="<?php echo (int)$category['id']; ?>"><?php echo html_escape($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label>Class / Degree / Level</label>
                        <select id="task_class_filter" class="form-control student-filter-class" data-prefix="task">
                            <option value="">Select Class / Degree / Level</option>
                            <?php foreach ($tutor_registration_tree as $category): ?>
                                <?php foreach (($category['classes'] ?? []) as $class): ?>
                                    <option value="<?php echo (int)$class['id']; ?>" data-category="<?php echo (int)$category['id']; ?>">
                                        <?php echo html_escape($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label>Subject</label>
                        <select id="task_subject_filter" class="form-control student-filter-subject" data-prefix="task">
                            <option value="">Select Subject</option>
                            <?php foreach ($tutor_registration_tree as $category): ?>
                                <?php foreach (($category['classes'] ?? []) as $class): ?>
                                    <?php foreach (($class['subjects'] ?? []) as $subject): ?>
                                        <option value="<?php echo (int)$subject['id']; ?>" data-category="<?php echo (int)$category['id']; ?>" data-class="<?php echo (int)$class['id']; ?>">
                                            <?php echo html_escape($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12 mb-2">
                        <label>Select Student(s)</label>
                        <select name="task_student_ids[]" id="task_student_selector" class="form-control student-context-selector" data-prefix="task" multiple required style="height:170px;">
                            <?php foreach ($eligible_students as $student): ?>
                                <option
									value="<?php echo (int)$student['id'].'|'.(int)$student['category_id'].'|'.(int)$student['class_id'].'|'.(int)$student['subject_id']; ?>"
									data-category="<?php echo (int)$student['category_id']; ?>"
									data-class="<?php echo (int)$student['class_id']; ?>"
									data-subject="<?php echo (int)$student['subject_id']; ?>">
									<?php
										echo html_escape(
											trim($student['first_name'].' '.$student['last_name']) .
											' - ' . $student['email'] .
											' | ' . $student['category_name'] .
											' | ' . $student['class_name'] .
											' | ' . $student['subject_name']
										);
									?>
								</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold CTRL to select multiple students.</small>
                    </div>

                    <div class="col-md-12 mb-2"><input type="text" name="title" class="form-control" placeholder="Assignment/Test title" required></div>
                    <div class="col-md-4 mb-2"><select name="task_type" class="form-control"><option value="assignment">Assignment</option><option value="test">Test</option></select></div>
                    <div class="col-md-4 mb-2"><input type="number" step="0.01" name="max_marks" class="form-control" placeholder="Max marks"></div>
                    <div class="col-md-4 mb-2"><input type="datetime-local" name="due_at" class="form-control"></div>
                    <div class="col-md-12 mb-2">
						<label>Status</label>
						<select name="evaluation_status" class="form-control">
							<option value="published">Published</option>
							<option value="draft">Draft</option>
							<option value="closed">Closed</option>
						</select>
					</div>
					
					<div class="col-md-12 mb-2"><textarea name="description" rows="3" class="form-control" placeholder="Description"></textarea></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Create & Assign</button>
            </form>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Sessions</h5>
            <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Title</th><th>Provider</th><th>Recording</th></tr></thead><tbody>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo html_escape($session['session_date'] . ' ' . $session['start_time']); ?></td>
                        <td><?php echo html_escape($session['title']); ?></td>
                        <td><?php echo strtoupper(html_escape($session['provider_type'])); ?></td>
                        <td><?php echo (int)$session['is_recording_enabled'] ? html_escape($session['recording_status']) : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sessions)): ?><tr><td colspan="4" class="text-center text-muted">No sessions scheduled.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h5>Assignments / Tests</h5>
            <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Type</th><th>Title</th><th>Due</th><th>Assigned</th><th>Status</th></tr></thead><tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo ucfirst(html_escape($task['task_type'])); ?></td>
                        <td><?php echo html_escape($task['title']); ?></td>
                        <td><?php echo html_escape((string)$task['due_at']); ?></td>
                        <td><?php echo (int)($task['assigned_students'] ?? 0); ?></td>
                        <td><?php echo html_escape($task['evaluation_status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($tasks)): ?><tr><td colspan="5" class="text-center text-muted">No tasks created.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div>
    </div>
	
    <div class="col-12">
	<div class="card">
		<div class="card-body">
			<h5>Assignment Submissions</h5>

			<div class="table-responsive">
				<table class="table table-sm table-striped">
					<thead>
						<tr>
							<th>Assignment</th>
							<th>Student</th>
							<th>Submitted</th>
							<th>File / Link</th>
							<th>Status</th>
							<th>Evaluate</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach (($assignment_submissions ?? []) as $sub): ?>
							<tr>
								<td>
									<strong><?php echo html_escape($sub['task_title']); ?></strong><br>
									<small>Max: <?php echo html_escape((string)$sub['max_marks']); ?></small>
								</td>

								<td>
									<?php echo html_escape(trim(($sub['first_name'] ?? '') . ' ' . ($sub['last_name'] ?? ''))); ?><br>
									<small><?php echo html_escape($sub['email'] ?? ''); ?></small>
								</td>

								<td>
									<?php echo html_escape($sub['submitted_at']); ?>
								</td>

								<td>
									<?php if (!empty($sub['submission_file'])): ?>
										<a href="<?php echo base_url($sub['submission_file']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
											View File
										</a>
									<?php endif; ?>

									<?php if (!empty($sub['external_link'])): ?>
										<a href="<?php echo html_escape($sub['external_link']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
											Open Link
										</a>
									<?php endif; ?>

									<?php if (!empty($sub['student_comment'])): ?>
										<br>
										<small>
											Comment: <?php echo html_escape($sub['student_comment']); ?>
										</small>
									<?php endif; ?>
								</td>

								<td>
									<?php if (($sub['evaluation_status'] ?? '') === 'evaluated'): ?>
										<span class="badge badge-success">Evaluated</span><br>
										<small>Marks: <?php echo html_escape((string)$sub['marks_obtained']); ?></small>
									<?php else: ?>
										<span class="badge badge-warning">Pending</span>
									<?php endif; ?>
								</td>

								<td>
									<form method="post" action="<?php echo site_url('tutor_batch/evaluate_assignment/' . (int)$sub['id']); ?>">
										<input type="number"
											   step="0.01"
											   min="0"
											   max="<?php echo html_escape((string)$sub['max_marks']); ?>"
											   name="marks_obtained"
											   class="form-control form-control-sm mb-1"
											   placeholder="Marks"
											   value="<?php echo html_escape((string)($sub['marks_obtained'] ?? '')); ?>"
											   required>

										<textarea name="tutor_remarks"
												  class="form-control form-control-sm mb-1"
												  rows="2"
												  placeholder="Remarks"><?php echo html_escape((string)($sub['tutor_remarks'] ?? '')); ?></textarea>

										<button type="submit" class="btn btn-success btn-sm">
											Save Evaluation
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php if (empty($assignment_submissions)): ?>
							<tr>
								<td colspan="6" class="text-center text-muted">
									No assignment submissions yet.
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	
	    </div>
		
    <div class="col-12">
		<div class="card mt-4">
		<div class="card-body">
			<h5>Create Online Test</h5>

			<form method="post" action="<?php echo site_url('tutor_batch/create_test/' . (int)$batch['id']); ?>">
				<div class="row">
					<div class="col-md-4 mb-2">
						<input type="text" name="title" class="form-control" placeholder="Test title" required>
					</div>

					<div class="col-md-4 mb-2">
						<input type="number" name="duration_minutes" class="form-control" placeholder="Duration in minutes" value="30" min="1" required>
					</div>

					<div class="col-md-4 mb-2">
						<button type="submit" class="btn btn-primary">
							Create Test
						</button>
					</div>

					<div class="col-md-12 mb-2">
						<textarea name="description" class="form-control" rows="2" placeholder="Test description"></textarea>
					</div>
				</div>
			</form>

			<hr>

			<h5>Existing Tests</h5>

			<?php foreach (($tests ?? []) as $test): ?>
				<div class="border rounded p-3 mb-3">
					<div class="d-flex justify-content-between">
						<div>
							<strong><?php echo html_escape($test['title']); ?></strong>
							<br>
							<small>
								Duration: <?php echo (int)$test['duration_minutes']; ?> minutes |
								Marks: <?php echo html_escape((string)$test['total_marks']); ?> |
								Status: <?php echo html_escape($test['status']); ?>
							</small>
						</div>

						<div>
							<?php if (($test['status'] ?? '') !== 'published'): ?>
								<a href="<?php echo site_url('tutor_batch/publish_test/' . (int)$test['id']); ?>"
								   class="btn btn-success btn-sm"
								   onclick="return confirm('Publish this test? Students will be able to attempt it.');">
									Publish
								</a>
							<?php else: ?>
								<span class="badge badge-success">Published</span>
							<?php endif; ?>
						</div>
					</div>

					<form method="post" class="mt-3" action="<?php echo site_url('tutor_batch/add_test_question/' . (int)$test['id']); ?>">
						<div class="form-group">
							<textarea name="question_text" class="form-control" rows="2" placeholder="Question" required></textarea>
						</div>

						<div class="row">
							<div class="col-md-3 mb-2">
								<input type="text" name="option_a" class="form-control" placeholder="Option A" required>
							</div>
							<div class="col-md-3 mb-2">
								<input type="text" name="option_b" class="form-control" placeholder="Option B" required>
							</div>
							<div class="col-md-3 mb-2">
								<input type="text" name="option_c" class="form-control" placeholder="Option C" required>
							</div>
							<div class="col-md-3 mb-2">
								<input type="text" name="option_d" class="form-control" placeholder="Option D" required>
							</div>
						</div>

						<div class="row">
							<div class="col-md-3 mb-2">
								<select name="correct_option" class="form-control" required>
									<option value="">Correct Option</option>
									<option value="A">A</option>
									<option value="B">B</option>
									<option value="C">C</option>
									<option value="D">D</option>
								</select>
							</div>

							<div class="col-md-3 mb-2">
								<input type="number" name="marks" class="form-control" value="1" min="1" step="0.01">
							</div>

							<div class="col-md-3 mb-2">
								<button type="submit" class="btn btn-info">
									Add Question
								</button>
							</div>
						</div>
					</form>

					<?php if (!empty($test['questions'])): ?>
						<ol>
							<?php foreach ($test['questions'] as $q): ?>
								<li>
									<?php echo html_escape($q['question_text']); ?>
									<br>
									<small>
										Correct: <?php echo html_escape($q['correct_option']); ?> |
										Marks: <?php echo html_escape((string)$q['marks']); ?>
									</small>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php if (empty($tests)): ?>
				<p class="text-muted">No tests created yet.</p>
			<?php endif; ?>
		</div>
	</div>
	
	
	
	
</div>

<script>
(function () {
    function initStudentFilter(prefix) {
        const categoryFilter = document.getElementById(prefix + '_category_filter');
        const classFilter = document.getElementById(prefix + '_class_filter');
        const subjectFilter = document.getElementById(prefix + '_subject_filter');
        const studentSelector = document.getElementById(prefix + '_student_selector');

        if (!categoryFilter || !classFilter || !subjectFilter || !studentSelector) {
            return;
        }

        const classOptions = Array.from(classFilter.options);
        const subjectOptions = Array.from(subjectFilter.options);
        const studentOptions = Array.from(studentSelector.options);

        function refreshClasses() {
            const selectedCategory = categoryFilter.value;

            classOptions.forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = selectedCategory && option.dataset.category !== selectedCategory;
            });

            classFilter.value = '';
            subjectFilter.value = '';
            refreshSubjects();
            refreshStudents();
        }

        function refreshSubjects() {
            const selectedClass = classFilter.value;

            subjectOptions.forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = selectedClass && option.dataset.class !== selectedClass;
            });

            subjectFilter.value = '';
            refreshStudents();
        }

        function refreshStudents() {
            const selectedCategory = categoryFilter.value;
            const selectedClass = classFilter.value;
            const selectedSubject = subjectFilter.value;

            studentOptions.forEach(option => {
                const categoryMatch = !selectedCategory || option.dataset.category === selectedCategory;
                const classMatch = !selectedClass || option.dataset.class === selectedClass;
                const subjectMatch = !selectedSubject || option.dataset.subject === selectedSubject;

                option.hidden = !(categoryMatch && classMatch && subjectMatch);
                option.selected = false;
            });
        }

        categoryFilter.addEventListener('change', refreshClasses);
        classFilter.addEventListener('change', refreshSubjects);
        subjectFilter.addEventListener('change', refreshStudents);

        refreshClasses();
    }

    initStudentFilter('invite');
    initStudentFilter('task');
})();
</script>
