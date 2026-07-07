<?php
$user_details = isset($user_details) && is_array($user_details) ? $user_details : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$is_dashboard_embed = !empty($is_dashboard_embed);
?>
<?php if (!$is_dashboard_embed): ?>
    <?php include 'breadcrumb.php'; ?>
<?php endif; ?>
<section class="wish-list-body message pb-5">
    <div class="container">
        <div class="row">
            <?php if (!$is_dashboard_embed): ?>
                <div class="col-lg-3 col-md-4">
                    <?php include 'profile_menus.php'; ?>
                </div>
            <?php endif; ?>
            <div class="<?php echo $is_dashboard_embed ? 'col-12' : 'col-lg-9 col-md-8'; ?>">
                <div class="common-card p-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-1">My Batch Invites</h4>
                                <p class="text-muted mb-0">Review tutor invitations and accept batches that match your learning profile.</p>
                            </div>
                            <?php if (!$is_dashboard_embed): ?>
                            <div class="mt-2 mt-md-0">
                                <a href="<?php echo site_url('home/student_dashboard'); ?>" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
                                <a href="<?php echo site_url('student_batch/my_batches'); ?>" class="btn btn-outline-primary btn-sm">My Batches</a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Tutor</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach (($invites ?? []) as $invite): ?>
                                    <tr>
                                        <td><?php echo html_escape($invite['batch_title']); ?></td>
                                        <td><?php echo html_escape(trim(($invite['tutor_first_name'] ?? '') . ' ' . ($invite['tutor_last_name'] ?? ''))); ?></td>
                                        <td><?php echo ucfirst(html_escape($invite['invite_status'])); ?></td>
                                        <td><?php echo nl2br(html_escape((string)($invite['invite_message'] ?? ''))); ?></td>
                                        <td>
                                            <?php if (($invite['invite_status'] ?? '') === 'pending'): ?>
                                                <form method="post" class="d-inline" action="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/accepted' . ($is_dashboard_embed ? '?dashboard=1' : '')); ?>"><button class="btn btn-success btn-sm">Accept</button></form>
                                                <form method="post" class="d-inline" action="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/rejected' . ($is_dashboard_embed ? '?dashboard=1' : '')); ?>"><button class="btn btn-outline-danger btn-sm">Reject</button></form>
                                            <?php else: ?>
                                                <span class="text-muted">Already <?php echo html_escape($invite['invite_status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($invites)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No batch invites yet.</td></tr>
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
