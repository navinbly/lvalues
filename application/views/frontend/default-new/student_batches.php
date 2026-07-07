<?php
$user_details = isset($user_details) && is_array($user_details) ? $user_details : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$pending_invites = [];
foreach (($invites ?? []) as $invite_item) {
    if (($invite_item['invite_status'] ?? '') === 'pending') {
        $pending_invites[] = $invite_item;
    }
}
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
                            <h4 class="mb-1">My Batches</h4>
                            <p class="text-muted mb-0">Accept batch invites, join live sessions, view recordings, and track assignments.</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="<?php echo site_url('home/student_dashboard'); ?>" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
                            <a href="<?php echo site_url('student_batch/invites'); ?>" class="btn btn-outline-primary btn-sm">Batch Invites</a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Active Batches</small>
                                <strong class="fs-5"><?php echo count($batches ?? []); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Pending Invites</small>
                                <strong class="fs-5"><?php echo count($pending_invites); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block">Progress Tracking</small>
                                <strong class="fs-5">Enabled</strong>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3">Pending Batch Invites</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>Tutor</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_invites as $invite): ?>
                                    <tr>
                                        <td><?php echo html_escape($invite['batch_title'] ?? ''); ?></td>
                                        <td><?php echo html_escape(trim(($invite['tutor_first_name'] ?? '') . ' ' . ($invite['tutor_last_name'] ?? ''))); ?></td>
                                        <td>
                                            <?php
                                                $subject_text = [];
                                                if (!empty($invite['target_category_name'])) $subject_text[] = $invite['target_category_name'];
                                                if (!empty($invite['target_class_name'])) $subject_text[] = $invite['target_class_name'];
                                                if (!empty($invite['target_subject_name'])) $subject_text[] = $invite['target_subject_name'];
                                                echo html_escape(implode(' / ', $subject_text));
                                            ?>
                                        </td>
                                        <td><?php echo nl2br(html_escape($invite['invite_message'] ?? '')); ?></td>
                                        <td class="text-nowrap">
                                            <form method="post" class="d-inline" action="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/accepted'); ?>"><button class="btn btn-success btn-sm">Accept</button></form>
                                            <form method="post" class="d-inline" action="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/rejected'); ?>"><button class="btn btn-outline-danger btn-sm">Reject</button></form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pending_invites)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No pending invites.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-3">Active Batches</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>Tutor</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($batches ?? []) as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo html_escape($item['batch_title']); ?></strong>
                                            <?php if (!empty($item['batch_description'])): ?>
                                                <br><small class="text-muted"><?php echo html_escape(character_limiter(strip_tags($item['batch_description']), 80)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo html_escape(trim(($item['tutor_first_name'] ?? '') . ' ' . ($item['tutor_last_name'] ?? ''))); ?></td>
                                        <td><?php echo ucfirst(html_escape($item['delivery_mode'] ?? '')); ?></td>
                                        <td><?php echo ucfirst(html_escape($item['membership_status'] ?? '')); ?></td>
                                        <td>
                                            <div class="progress" style="height: 8px; min-width: 100px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo (float)($item['progress_percent'] ?? 0); ?>%;" aria-valuenow="<?php echo (float)($item['progress_percent'] ?? 0); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small><?php echo html_escape((string)($item['progress_percent'] ?? 0)); ?>%</small>
                                        </td>
                                        <td>
                                            <a href="<?php echo site_url('student_batch/view/' . (int)$item['batch_id']); ?>" class="btn btn-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($batches)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No active batches yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
