<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0"><i class="mdi mdi-account-group title_icon"></i> My Batches</h4>
                <a href="<?php echo site_url('tutor_batch/create'); ?>" class="btn btn-primary btn-sm">Create Batch</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th>Mode</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $batch): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo html_escape($batch['title']); ?></strong><br>
                                        <small class="text-muted"><?php echo html_escape($batch['batch_code']); ?></small>
                                    </td>
                                    <td><?php echo ucfirst(html_escape($batch['delivery_mode'])); ?></td>
                                    <td><?php echo (int)$batch['total_students']; ?> / <?php echo (int)$batch['capacity']; ?></td>
                                    <td><span class="badge badge-info-lighten"><?php echo ucfirst(html_escape($batch['status'])); ?></span></td>
                                    <td><?php echo html_escape((string)$batch['start_date']); ?> to <?php echo html_escape((string)$batch['end_date']); ?></td>
                                    <td>
                                        <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id']); ?>">Manage</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tutor_batch/create/' . (int)$batch['id']); ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($batches)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No batches created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
