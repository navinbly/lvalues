<?php include 'breadcrumb.php'; ?>
<section class="grid-view courses-list-view pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                            <h4 class="mb-0"><?php echo get_phrase('my_tutor_requests'); ?></h4>
                            <a href="<?php echo site_url('home/search?search_for=tutor'); ?>" class="btn btn-outline-primary btn-sm"><?php echo get_phrase('Find More Tutors'); ?></a>
                        </div>

                        <?php if (!empty($requests)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php echo get_phrase('Tutor'); ?></th>
                                            <th><?php echo get_phrase('Requirement'); ?></th>
                                            <th><?php echo get_phrase('Mode'); ?></th>
                                            <th><?php echo get_phrase('Status'); ?></th>
                                            <th><?php echo get_phrase('Payment'); ?></th>
                                            <th><?php echo get_phrase('Tutor response'); ?></th>
                                            <th><?php echo get_phrase('Date'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <?php $status_class = $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'approved' ? 'success' : 'danger'); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo html_escape(trim(($request['tutor_first_name'] ?? '') . ' ' . ($request['tutor_last_name'] ?? ''))); ?></strong>
                                                <?php if (!empty($request['tutor_headline'])): ?>
                                                    <div class="text-muted small"><?php echo html_escape($request['tutor_headline']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo html_escape($request['subject_name_snapshot'] ?: $request['query_text']); ?></strong>
                                                <?php if (!empty($request['message'])): ?>
                                                    <div class="text-muted small mt-1"><?php echo nl2br(html_escape($request['message'])); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo ucfirst(html_escape($request['preferred_mode'])); ?></td>
                                            <td><span class="badge badge-<?php echo $status_class; ?>-lighten"><?php echo ucfirst($request['status']); ?></span></td>
                                            <td>
                                                <?php if ($request['status'] === 'approved'): ?>
                                                    <?php
                                                        $one_time_amount = (float)($request['one_time_payment_amount'] ?? $request['hourly_fee'] ?? 0);
                                                        $monthly_amount = (float)($request['monthly_payment_amount'] ?? $request['hourly_fee'] ?? 0);
                                                        $payment_note = !empty($request['one_time_disabled_reason'])
                                                            ? $request['one_time_disabled_reason']
                                                            : 'Payments are intentionally disabled until Lvalues enables verified marketplace payment processing.';
                                                    ?>
                                                    <div class="d-flex flex-column gap-1">
                                                        <button type="button" class="btn btn-primary btn-sm mb-1" disabled aria-disabled="true" title="<?php echo html_escape($payment_note); ?>">
                                                            Pay one-time <?php echo $one_time_amount > 0 ? '₹' . number_format($one_time_amount, 2) : ''; ?>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" disabled aria-disabled="true" title="<?php echo html_escape($payment_note); ?>">
                                                            Pay monthly <?php echo $monthly_amount > 0 ? '₹' . number_format($monthly_amount, 2) : ''; ?>
                                                        </button>
                                                        <span class="badge badge-warning-lighten">Payment disabled</span>
                                                        <small class="text-muted d-block">Future enablement note: turn on only after verified student/tutor payment ledger, gateway webhook, and admin payout reconciliation are ready. No transaction can be made now.</small>
                                                    </div>
                                                <?php elseif ($request['status'] === 'pending'): ?>
                                                    <span class="text-muted small">Waiting for tutor acceptance.</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Payment unavailable for rejected/cancelled requests.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo !empty($request['tutor_response']) ? nl2br(html_escape($request['tutor_response'])) : '<span class="text-muted">Waiting for tutor response</span>'; ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0"><?php echo get_phrase('You have not submitted any tutor request yet.'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
