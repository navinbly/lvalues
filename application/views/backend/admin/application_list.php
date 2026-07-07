<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('instructor_applications'); ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h4 class="mb-1 header-title">Tutor approval checklist</h4>
                        <p class="text-muted mb-0">Use this rubric before approving tutors so profile quality stays consistent while the marketplace grows.</p>
                    </div>
                    <span class="badge badge-info-lighten mt-2 mt-md-0"><?php echo $pending_applications->num_rows(); ?> pending</span>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-3 h-100">
                            <strong>Identity and document</strong>
                            <p class="text-muted mb-0 small">Confirm the applicant identity and uploaded document before approval.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-3 h-100">
                            <strong>Teaching profile</strong>
                            <p class="text-muted mb-0 small">Check subject fit, experience, bio clarity, and learner-facing credibility.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-3 h-100">
                            <strong>Quality risk</strong>
                            <p class="text-muted mb-0 small">Flag missing data, unclear documents, duplicate accounts, or weak course fit.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-3 h-100">
                            <strong>Next build</strong>
                            <p class="text-muted mb-0 small">Add reviewer notes, rejection reasons, scorecards, and approval SLA tracking.</p>
                        </div>
                    </div>
                </div>
                <div class="ai-review-card mt-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div>
                            <h4 class="header-title mb-1">AI tutor approval assistant</h4>
                            <p class="text-muted mb-0">Pilot recommendation: summarize application, check missing documents, flag suspicious profile signals, then require human approval.</p>
                        </div>
                        <span class="badge badge-warning-lighten mt-2 mt-md-0">Recommend only</span>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-2"><strong>Data required</strong><p class="text-muted mb-0 small">Tutor profile, documents, application answers, account history.</p></div>
                        <div class="col-md-4 mb-2"><strong>Output shown</strong><p class="text-muted mb-0 small">Approve/review/reject recommendation with explanation and confidence.</p></div>
                        <div class="col-md-4 mb-2"><strong>Guardrail</strong><p class="text-muted mb-0 small">Tutor rejection must be confirmed by an admin with an override reason.</p></div>
                    </div>
                </div>
                <div class="table-workflow-toolbar mt-3">
                    <div>
                        <strong class="d-block">Reviewer notes and decision history</strong>
                        <span class="text-muted">Approval, rejection, missing-document, and override notes should be stored with reviewer, timestamp, and audit ID.</span>
                    </div>
                    <div class="btn-group mt-2 mt-md-0" role="group" aria-label="Tutor application review actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" disabled>Add note</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" disabled>Assign reviewer</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" disabled>Batch review</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('list_of_applications'); ?></h4>
                <ul class="nav nav-tabs nav-bordered mb-3">
                    <li class="nav-item">
                        <a href="#pending-b1" data-toggle="tab" aria-expanded="false" class="nav-link active">
                            <i class="mdi mdi-home-variant d-lg-none d-block mr-1"></i>
                            <span class="d-none d-lg-block"><?php echo get_phrase('pending_applications'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#approved-b1" data-toggle="tab" aria-expanded="true" class="nav-link">
                            <i class="mdi mdi-account-circle d-lg-none d-block mr-1"></i>
                            <span class="d-none d-lg-block"><?php echo get_phrase('approved_applications'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#rejected-b1" data-toggle="tab" aria-expanded="false" class="nav-link">
                            <i class="mdi mdi-close-circle-outline d-lg-none d-block mr-1"></i>
                            <span class="d-none d-lg-block">Rejected applications</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane show active" id="pending-b1">
                        <div class="table-responsive-sm mt-4">
                            <table id="pending-application" class="table table-striped table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo get_phrase('name'); ?></th>
                                        <th><?php echo get_phrase('document'); ?></th>
                                        <th><?php echo get_phrase('details'); ?></th>
                                        <th><?php echo get_phrase('status'); ?></th>
                                        <th><?php echo get_phrase('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
									<?php foreach ($pending_applications->result_array() as $key => $pending_application):
										$user_data = $this->user_model->get_all_user($pending_application['user_id'])->row_array();
										$user_data = is_array($user_data) ? $user_data : array();

										$first_name = isset($user_data['first_name']) ? $user_data['first_name'] : '';
										$last_name  = isset($user_data['last_name']) ? $user_data['last_name'] : '';
										$full_name  = trim($first_name . ' ' . $last_name);
									   ?>
										<tr class="gradeU">
											<td>
												<?php echo ++$key; ?>
											</td>
											<td>
												<?php echo $full_name !== '' ? html_escape($full_name) : 'N/A'; ?>
											</td>
                                            <td>
                                                <a href="javascript:;" class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/application_details/'.$pending_application['id']); ?>', '<?php echo get_phrase('applicant_details'); ?>')">
                                                    <i class="fa fa-info-circle"></i> <?php echo get_phrase('application_details'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($pending_application['document'])): ?>
                                                    <a href="<?php echo base_url().'uploads/document/'.$pending_application['document']; ?>" class="btn btn-info" download>
                                                        <i class="fa fa-download"></i> <?php echo get_phrase('download'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Missing document</span>
                                                    <small class="text-muted d-block mt-1">Admin may approve now. The tutor can log in, but verified badge and public listing stay pending until documents are uploaded and approved.</small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($pending_application['status'] == 0): ?>
                                                    <div class="badge badge-danger"><?php echo get_phrase('pending'); ?></div>
                                                <?php elseif($pending_application['status'] == 1): ?>
                                                    <div class="badge badge-success"><?php echo get_phrase('approved'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a class="btn btn-success btn-sm mb-2" href="#" onclick="confirm_modal('<?php echo $application_action_base; ?>/approve/<?php echo $pending_application['id']; ?>');">
                                                    <i class="mdi mdi-check"></i> <?php echo !empty($pending_application['document']) ? get_phrase('approve') : 'Approve, remind documents';?>
                                                </a>
                                                <form method="post" action="<?php echo $application_action_base; ?>/reject/<?php echo $pending_application['id']; ?>" class="mt-1">
                                                    <textarea name="admin_response" class="form-control form-control-sm mb-2" rows="2" required placeholder="Reason or next step for tutor"></textarea>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this tutor application and send this response to the applicant?');">
                                                        <i class="mdi mdi-close"></i> Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="approved-b1">
                        <div class="table-responsive-sm mt-4">
                            <table id="approved-application" class="table table-striped table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo get_phrase('name'); ?></th>
                                        <th><?php echo get_phrase('document'); ?></th>
                                        <th><?php echo get_phrase('details'); ?></th>
                                        <th><?php echo get_phrase('status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
									<?php foreach ($approved_applications->result_array() as $key => $approved_application):
										$user_data = $this->user_model->get_all_user($approved_application['user_id'])->row_array();
										$user_data = is_array($user_data) ? $user_data : array();

										$first_name = isset($user_data['first_name']) ? $user_data['first_name'] : '';
										$last_name  = isset($user_data['last_name']) ? $user_data['last_name'] : '';
										$full_name  = trim($first_name . ' ' . $last_name);
										?>
										<tr class="gradeU">
											<td>
												<?php echo ++$key; ?>
											</td>
											<td>
												<?php echo $full_name !== '' ? html_escape($full_name) : 'N/A'; ?>
											</td>
                                            <td>
                                                <a href="javascript:;" class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/application_details/'.$approved_application['id']); ?>', '<?php echo get_phrase('applicant_details'); ?>')">
                                                    <i class="fa fa-info-circle"></i> <?php echo get_phrase('application_details'); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($approved_application['document'])): ?>
                                                    <a href="<?php echo base_url().'uploads/document/'.$approved_application['document']; ?>" class="btn btn-info" download>
                                                        <i class="fa fa-download"></i> <?php echo get_phrase('download'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($approved_application['status'] == 0): ?>
                                                    <div class="badge badge-danger"><?php echo get_phrase('pending'); ?></div>
                                                <?php elseif($approved_application['status'] == 1): ?>
                                                    <div class="badge badge-success"><?php echo get_phrase('approved'); ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="rejected-b1">
                        <div class="table-responsive-sm mt-4">
                            <table id="rejected-application" class="table table-striped table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo get_phrase('name'); ?></th>
                                        <th><?php echo get_phrase('document'); ?></th>
                                        <th><?php echo get_phrase('details'); ?></th>
                                        <th>Admin response</th>
                                        <th><?php echo get_phrase('status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($rejected_applications)): ?>
                                        <?php foreach ($rejected_applications->result_array() as $key => $rejected_application):
                                            $user_data = $this->user_model->get_all_user($rejected_application['user_id'])->row_array();
                                            $user_data = is_array($user_data) ? $user_data : array();

                                            $first_name = isset($user_data['first_name']) ? $user_data['first_name'] : '';
                                            $last_name  = isset($user_data['last_name']) ? $user_data['last_name'] : '';
                                            $full_name  = trim($first_name . ' ' . $last_name);
                                            ?>
                                            <tr class="gradeU">
                                                <td><?php echo ++$key; ?></td>
                                                <td><?php echo $full_name !== '' ? html_escape($full_name) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="javascript:;" class="btn btn-primary" onclick="showAjaxModal('<?php echo site_url('modal/popup/application_details/'.$rejected_application['id']); ?>', '<?php echo get_phrase('applicant_details'); ?>')">
                                                        <i class="fa fa-info-circle"></i> <?php echo get_phrase('application_details'); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php if (!empty($rejected_application['document'])): ?>
                                                        <a href="<?php echo base_url().'uploads/document/'.$rejected_application['document']; ?>" class="btn btn-info" download>
                                                            <i class="fa fa-download"></i> <?php echo get_phrase('download'); ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo html_escape($rejected_application['admin_response'] ?? ''); ?></td>
                                                <td style="text-align: center;">
                                                    <div class="badge badge-danger">Rejected</div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        initDataTable(['#pending-application', '#approved-application', '#rejected-application']);
    });
</script>
