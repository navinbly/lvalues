<?php include 'breadcrumb.php'; ?>
<section class="grid-view courses-list-view pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">My Batch Invites</h4>
                            
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
                                                <a class="btn btn-success btn-sm" href="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/accepted'); ?>">Accept</a>
                                                <a class="btn btn-outline-danger btn-sm" href="<?php echo site_url('student_batch/respond/' . $invite['invite_token'] . '/rejected'); ?>">Reject</a>
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
