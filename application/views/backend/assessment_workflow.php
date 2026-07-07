<?php
$role = $assessment_role ?? 'tutor';
$is_admin = ($role === 'admin');
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Assessment Center</h4>
                <p class="text-muted">Reusable grading tools, moderation, plagiarism checks, and mastery reporting.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5>Rubrics</h5>
                <form method="post" action="<?php echo site_url('assessment-center/rubric'); ?>">
                    <input type="hidden" name="return_role" value="<?php echo html_escape($role); ?>">
                    <div class="form-group">
                        <label>Name</label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Criteria</label>
                        <textarea class="form-control" name="criteria" rows="5" placeholder="Accuracy | 10 | Correct and complete&#10;Clarity | 5 | Easy to follow" required></textarea>
                        <small class="text-muted">One criterion per line: name | points | description.</small>
                    </div>
                    <button class="btn btn-primary">Save rubric</button>
                </form>
                <hr>
                <?php foreach ($rubrics as $rubric): ?>
                    <div class="border rounded p-2 mb-2">
                        <strong><?php echo html_escape($rubric['name']); ?></strong>
                        <span class="badge badge-light"><?php echo (int) $rubric['total_points']; ?> points</span>
                        <div class="small text-muted">
                            <?php echo html_escape(trim($rubric['class_name'].' '.$rubric['subject_name'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h5>Reusable Feedback</h5>
                <form method="post" action="<?php echo site_url('assessment-center/feedback'); ?>">
                    <input type="hidden" name="return_role" value="<?php echo html_escape($role); ?>">
                    <div class="form-group">
                        <input class="form-control" name="title" placeholder="Feedback title" required>
                    </div>
                    <div class="form-group">
                        <input class="form-control" name="category" placeholder="Category, e.g. Writing">
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" name="feedback_text" rows="4" placeholder="Feedback text" required></textarea>
                    </div>
                    <button class="btn btn-primary">Save feedback</button>
                </form>
                <hr>
                <?php foreach ($feedback as $item): ?>
                    <div class="border rounded p-2 mb-2">
                        <strong><?php echo html_escape($item['title']); ?></strong>
                        <span class="badge badge-light"><?php echo html_escape($item['category']); ?></span>
                        <div class="small"><?php echo nl2br(html_escape($item['feedback_text'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h5>Bulk Grading and Plagiarism Review</h5>
                <form method="post" action="<?php echo site_url('assessment-center/bulk-grade'); ?>">
                    <input type="hidden" name="return_role" value="<?php echo html_escape($role); ?>">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Attempt</th>
                                    <th>Plagiarism</th>
                                    <th>Marks</th>
                                    <th>Feedback</th>
                                    <th>Include</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <?php echo html_escape(trim($submission['first_name'].' '.$submission['last_name'])); ?>
                                            <br>
                                            <a class="small" target="_blank" href="<?php echo site_url('assessment-center/parent-summary/'.$submission['student_user_id']); ?>">Parent summary</a>
                                        </td>
                                        <td><?php echo html_escape($submission['task_title']); ?></td>
                                        <td><?php echo (int) $submission['submission_attempt']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $submission['plagiarism_status'] === 'clear' ? 'success' : ($submission['plagiarism_status'] === 'blocked' ? 'danger' : 'warning'); ?>">
                                                <?php echo html_escape($submission['plagiarism_status']); ?>
                                            </span>
                                            <?php if ($submission['plagiarism_score'] !== null): ?>
                                                <?php echo number_format((float) $submission['plagiarism_score'], 1); ?>%
                                            <?php endif; ?>
                                            <button class="btn btn-link btn-sm p-0 d-block" type="submit" form="plagiarism-<?php echo (int) $submission['id']; ?>">Run check</button>
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="marks[<?php echo (int) $submission['id']; ?>]" value="<?php echo html_escape($submission['marks']); ?>">
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm" name="feedback[<?php echo (int) $submission['id']; ?>]" value="<?php echo html_escape($submission['tutor_feedback']); ?>">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="submission_ids[]" value="<?php echo (int) $submission['id']; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($submissions)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No submissions are awaiting grading.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-success">Grade selected submissions</button>
                </form>

                <?php foreach ($submissions as $submission): ?>
                    <form id="plagiarism-<?php echo (int) $submission['id']; ?>" method="post" action="<?php echo site_url('assessment-center/plagiarism/'.$submission['id']); ?>">
                        <input type="hidden" name="return_role" value="<?php echo html_escape($role); ?>">
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h5>Manual Result Moderation</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Submitted</th><th>Decision</th></tr></thead>
                        <tbody>
                            <?php foreach (($pending_attempts ?? array()) as $attempt): ?>
                                <tr>
                                    <td><?php echo html_escape(trim($attempt['first_name'].' '.$attempt['last_name'])); ?></td>
                                    <td><?php echo html_escape($attempt['title']); ?></td>
                                    <td><?php echo number_format((float)$attempt['percentage'],1); ?>%</td>
                                    <td><?php echo html_escape($attempt['submitted_at']); ?></td>
                                    <td>
                                        <form class="form-inline" method="post" action="<?php echo site_url('assessment-center/moderate-attempt/'.$attempt['id']); ?>">
                                            <select class="form-control form-control-sm mr-1" name="decision"><option value="approved">Approve</option><option value="adjusted">Mark adjusted</option></select>
                                            <input class="form-control form-control-sm mr-1" name="note" placeholder="Moderation note">
                                            <button class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pending_attempts)): ?><tr><td colspan="5" class="text-center text-muted">No exam results are awaiting moderation.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h5>Mastery Reports</h5>
                <?php if ($is_admin): ?>
                    <form class="form-inline mb-3" method="post" action="<?php echo site_url('assessment-center/rebuild-mastery'); ?>">
                        <input type="hidden" name="return_role" value="<?php echo html_escape($role); ?>">
                        <button class="btn btn-outline-primary">Rebuild mastery from exam insights</button>
                    </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Dimension</th><th>Type</th><th>Students</th><th>Mastery</th><th>Questions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mastery as $row): ?>
                                <tr>
                                    <td><?php echo html_escape($row['dimension_label']); ?></td>
                                    <td><?php echo html_escape(ucfirst($row['dimension_type'])); ?></td>
                                    <td><?php echo (int) $row['students']; ?></td>
                                    <td><?php echo number_format((float) $row['score'], 1); ?>%</td>
                                    <td><?php echo (int) $row['questions']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mastery)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No mastery evidence has been calculated yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
