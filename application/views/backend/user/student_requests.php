<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                    <h4 class="page-title mb-0"><i class="mdi mdi-account-multiple-check title_icon"></i> <?php echo get_phrase('student_requests'); ?></h4>
                    <div>
                        <a href="<?php echo site_url('user/student_requests/all'); ?>" class="btn btn-sm <?php echo $request_status_filter === 'all' ? 'btn-primary' : 'btn-light'; ?>">All</a>
                        <a href="<?php echo site_url('user/student_requests/pending'); ?>" class="btn btn-sm <?php echo $request_status_filter === 'pending' ? 'btn-primary' : 'btn-light'; ?>">Pending</a>
                        <a href="<?php echo site_url('user/student_requests/approved'); ?>" class="btn btn-sm <?php echo $request_status_filter === 'approved' ? 'btn-primary' : 'btn-light'; ?>">Accepted</a>
                        <a href="<?php echo site_url('user/student_requests/rejected'); ?>" class="btn btn-sm <?php echo $request_status_filter === 'rejected' ? 'btn-primary' : 'btn-light'; ?>">Rejected</a>
                    </div>
                </div>

                <?php if (!empty($requests)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-centered mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('subject'); ?></th>
                                    <th><?php echo get_phrase('preferred_mode'); ?></th>
                                    <th><?php echo get_phrase('location'); ?></th>
                                    <th><?php echo get_phrase('status'); ?></th>
                                    <th><?php echo get_phrase('requested_on'); ?></th>
                                    <th><?php echo get_phrase('action'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $request): ?>
                                <?php $status_class = $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'approved' ? 'success' : 'danger'); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo html_escape(trim(($request['student_first_name'] ?? '') . ' ' . ($request['student_last_name'] ?? '')) ?: $request['student_name_snapshot']); ?></strong>
                                        <div class="text-muted small"><?php echo html_escape($request['student_email'] ?: $request['student_email_snapshot']); ?></div>
                                        <?php if (!empty($request['student_phone']) || !empty($request['student_phone_snapshot'])): ?>
                                            <div class="text-muted small"><?php echo html_escape($request['student_phone'] ?: $request['student_phone_snapshot']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo html_escape($request['subject_name_snapshot'] ?: $request['query_text']); ?></strong>
                                        <?php if (!empty($request['message'])): ?>
                                            <div class="text-muted small mt-1"><?php echo nl2br(html_escape($request['message'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst(html_escape($request['preferred_mode'])); ?></td>
                                    <td><?php echo html_escape($request['student_location_text']); ?></td>
                                    <td><span class="badge badge-<?php echo $status_class; ?>-lighten"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success mb-1" data-toggle="modal" data-target="#acceptRequestModal_<?php echo (int)$request['id']; ?>">Accept</button>
                                            <button class="btn btn-sm btn-danger mb-1" data-toggle="modal" data-target="#rejectRequestModal_<?php echo (int)$request['id']; ?>">Reject</button>
                                        <?php else: ?>
                                            <span class="text-muted small">Updated <?php echo !empty($request['responded_at']) ? date('d M Y, h:i A', strtotime($request['responded_at'])) : '-'; ?></span>
                                            <?php if (!empty($request['tutor_response'])): ?>
                                                <div class="small mt-1"><?php echo nl2br(html_escape($request['tutor_response'])); ?></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="modal fade" id="acceptRequestModal_<?php echo (int)$request['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <form action="<?php echo site_url('user/update_student_request/' . (int)$request['id'] . '/approved'); ?>" method="post">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Accept student request</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group mb-0">
                                                            <label><?php echo get_phrase('Response message'); ?></label>
                                                            <textarea name="response_message" class="form-control" rows="4" placeholder="Write a professional acceptance message for the student"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Accept Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="rejectRequestModal_<?php echo (int)$request['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <form action="<?php echo site_url('user/update_student_request/' . (int)$request['id'] . '/rejected'); ?>" method="post">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Reject student request</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group mb-0">
                                                            <label><?php echo get_phrase('Response message'); ?></label>
                                                            <textarea name="response_message" class="form-control" rows="4" placeholder="Write a polite reason for rejection"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mb-0"><?php echo get_phrase('No student requests found for the selected filter.'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
