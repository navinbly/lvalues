<?php defined('BASEPATH') OR exit('No direct script access allowed');
$student = is_array($student ?? null) ? $student : array();
$enrolments = $this->crud_model->enrol_history_by_user_id((int)$user_id)->result_array();
?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body d-flex justify-content-between align-items-center flex-wrap">
  <div><h4 class="page-title mb-1"><i class="mdi mdi-account-details title_icon"></i> Student Details</h4><p class="text-muted mb-0">Read-only account and enrollment overview.</p></div>
  <div class="mt-2 mt-md-0"><a class="btn btn-outline-primary" href="<?php echo site_url('admin/user_form/edit_user_form/'.(int)$user_id); ?>">Edit Student</a> <a class="btn btn-light" href="<?php echo site_url('admin/users'); ?>">Back</a></div>
</div></div></div></div>
<div class="row">
  <div class="col-lg-4"><div class="card"><div class="card-body text-center">
    <img class="rounded-circle img-thumbnail mb-3" style="width:110px;height:110px;object-fit:cover" src="<?php echo $this->user_model->get_user_image_url((int)$user_id); ?>" alt="">
    <h4><?php echo html_escape(trim(($student['first_name'] ?? '').' '.($student['last_name'] ?? ''))); ?></h4>
    <span class="badge badge-<?php echo (int)($student['status'] ?? 0) === 1 ? 'success' : 'warning'; ?>"><?php echo (int)($student['status'] ?? 0) === 1 ? 'Active' : 'Unverified'; ?></span>
  </div></div></div>
  <div class="col-lg-8"><div class="card"><div class="card-body">
    <h4 class="header-title">Account Information</h4>
    <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
      <tr><th style="width:180px">Email</th><td><?php echo html_escape($student['email'] ?? ''); ?></td></tr>
      <tr><th>Phone</th><td><?php echo html_escape($student['phone'] ?? 'Not provided'); ?></td></tr>
      <tr><th>Address</th><td><?php echo html_escape($student['address'] ?? 'Not provided'); ?></td></tr>
      <tr><th>Joined</th><td><?php echo !empty($student['date_added']) ? date('d M Y, h:i A', (int)$student['date_added']) : 'Not available'; ?></td></tr>
      <tr><th>Enrolled courses</th><td><?php echo count($enrolments); ?></td></tr>
    </table></div>
    <?php if (!empty($enrolments)): ?><hr><h4 class="header-title">Enrollments</h4><ul class="mb-0">
      <?php foreach ($enrolments as $enrolment): $course = $this->crud_model->get_course_by_id((int)$enrolment['course_id'])->row_array(); ?>
        <li><?php echo html_escape($course['title'] ?? ('Course #'.(int)$enrolment['course_id'])); ?></li>
      <?php endforeach; ?>
    </ul><?php endif; ?>
  </div></div></div>
</div>
