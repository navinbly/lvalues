<?php
$is_edit = !empty($batch['id']);
$minimum_start_date = $is_edit && !empty($batch['start_date']) && $batch['start_date'] < date('Y-m-d')
    ? $batch['start_date']
    : date('Y-m-d');
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"><?php echo $is_edit ? 'Edit Batch' : 'Create Batch'; ?></h4>
                <form method="post" action="<?php echo site_url('tutor_batch/save/' . (int)($batch['id'] ?? 0)); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Batch Title</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo html_escape($batch['title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Batch Code</label>
                            <input type="text" name="batch_code" class="form-control" value="<?php echo html_escape($batch['batch_code'] ?? ''); ?>" placeholder="Generated automatically">
                            <small class="text-muted">Leave blank to generate a unique code.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Enrollment</label>
                            <select name="enrollment_mode" class="form-control"><option value="approval" <?php echo (($batch['enrollment_mode'] ?? 'approval')==='approval')?'selected':'';?>>Teacher approval</option><option value="open" <?php echo (($batch['enrollment_mode'] ?? '')==='open')?'selected':'';?>>Open enrollment</option></select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <label class="mb-2"><input type="checkbox" name="waitlist_enabled" value="1" <?php echo !isset($batch['waitlist_enabled']) || !empty($batch['waitlist_enabled']) ? 'checked' : ''; ?>> Enable waitlist when full</label>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Capacity</label>
                            <input type="number" min="1" name="capacity" class="form-control" value="<?php echo html_escape($batch['capacity'] ?? 1); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Mode</label>
                            <select name="delivery_mode" class="form-control">
                                <?php foreach (['online','offline','hybrid'] as $mode): ?>
                                    <option value="<?php echo $mode; ?>" <?php echo (($batch['delivery_mode'] ?? 'online') === $mode) ? 'selected' : ''; ?>><?php echo ucfirst($mode); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Start Date</label>
                            <input type="date" id="batch_start_date" name="start_date" class="form-control" min="<?php echo html_escape($minimum_start_date); ?>" required value="<?php echo html_escape($batch['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>End Date</label>
                            <input type="date" id="batch_end_date" name="end_date" class="form-control" min="<?php echo html_escape($batch['start_date'] ?? $minimum_start_date); ?>" required value="<?php echo html_escape($batch['end_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach (['draft','published','completed','cancelled','archived'] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo (($batch['status'] ?? 'draft') === $status) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo html_escape($batch['price'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3"></div>
                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea name="description" rows="5" class="form-control"><?php echo html_escape($batch['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit"><?php echo $is_edit ? 'Update Batch' : 'Create Batch'; ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const startDate = document.getElementById('batch_start_date');
    const endDate = document.getElementById('batch_end_date');
    if (!startDate || !endDate) return;

    function syncEndDate() {
        const minimum = startDate.value || startDate.min;
        endDate.min = minimum;
        if (endDate.value && minimum && endDate.value < minimum) {
            endDate.value = minimum;
        }
    }

    startDate.addEventListener('change', syncEndDate);
    syncEndDate();
})();
</script>
