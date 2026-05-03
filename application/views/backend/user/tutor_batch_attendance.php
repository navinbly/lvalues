<?php
$session = $attendance_data['session'];
$students = $attendance_data['students'];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">

                <h4>Mark Attendance</h4>

                <p>
                    <strong>Batch:</strong>
                    <?php echo html_escape($session['batch_title']); ?>
                    <br>

                    <strong>Session:</strong>
                    <?php echo html_escape($session['title'] ?? 'Session'); ?>
                    <br>

                    <strong>Date/Time:</strong>
                    <?php echo html_escape($session['start_time'] ?? ''); ?>
                </p>

                <form method="post" action="<?php echo site_url('tutor_batch/save_attendance/' . (int)$session['id']); ?>">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Attendance</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                        $student_id = (int)$student['student_user_id'];
                                        $current_status = $student['attendance_status'] ?? 'absent';
                                    ?>

                                    <tr>
                                        <td>
                                            <?php echo html_escape(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?>
                                        </td>

                                        <td>
                                            <?php echo html_escape($student['email'] ?? ''); ?>
                                        </td>

                                        <td>
                                            <select name="attendance[<?php echo $student_id; ?>]" class="form-control">
                                                <option value="present" <?php echo $current_status === 'present' ? 'selected' : ''; ?>>
                                                    Present
                                                </option>

                                                <option value="late" <?php echo $current_status === 'late' ? 'selected' : ''; ?>>
                                                    Late
                                                </option>

                                                <option value="absent" <?php echo $current_status === 'absent' ? 'selected' : ''; ?>>
                                                    Absent
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No accepted students found in this batch.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-success">
                        Save Attendance
                    </button>

                    <a href="<?php echo site_url('tutor_batch/manage/' . (int)$session['batch_id']); ?>"
                       class="btn btn-secondary">
                        Back
                    </a>

                </form>

            </div>
        </div>
    </div>
</div>