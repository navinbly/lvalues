<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <h4 class="page-title mb-0"><i class="mdi mdi-account-group title_icon"></i> My Batches</h4>
                <a href="<?php echo site_url('tutor_batch/create'); ?>" class="btn btn-primary btn-sm" aria-label="Create a new batch">Create Batch</a>
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
                                <th>Health</th>
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
                                    <td><span class="badge badge-<?php echo (($batch['health']['score'] ?? 0) >= 80) ? 'success' : ((($batch['health']['score'] ?? 0) >= 60) ? 'warning' : 'danger'); ?>-lighten"><?php echo html_escape($batch['health']['label'] ?? 'No data'); ?> <?php echo (float)($batch['health']['score'] ?? 0); ?></span></td>
                                    <td><?php echo html_escape((string)$batch['start_date']); ?> to <?php echo html_escape((string)$batch['end_date']); ?></td>
                                    <td>
                                        <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('tutor_batch/manage/' . (int)$batch['id']); ?>" aria-label="Manage batch <?php echo html_escape($batch['title']); ?>">Manage</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tutor_batch/create/' . (int)$batch['id']); ?>" aria-label="Edit batch <?php echo html_escape($batch['title']); ?>">Edit</a>
                                        <button class="btn btn-outline-info btn-sm" type="button" data-toggle="collapse" data-target="#duplicateBatch<?php echo (int)$batch['id']; ?>">Duplicate</button>
                                        <div id="duplicateBatch<?php echo (int)$batch['id']; ?>" class="collapse mt-2"><form method="post" action="<?php echo site_url('teacher-workflow/duplicate/'.$batch['id']); ?>" class="border rounded p-2 bg-light"><input name="title" class="form-control form-control-sm mb-1" value="<?php echo html_escape($batch['title'].' Copy'); ?>" required><input type="date" name="start_date" class="form-control form-control-sm mb-1" min="<?php echo date('Y-m-d'); ?>" required><label class="small"><input type="checkbox" name="copy_students" value="1"> Copy students as invited</label><button class="btn btn-primary btn-sm btn-block">Create duplicate</button></form></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($batches)): ?>
                                <tr><td colspan="7" class="text-center py-4"><h5 class="mb-1">No batches yet</h5><p class="text-muted mb-0">Use Create Batch above to invite students, schedule sessions, and assign work.</p></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
