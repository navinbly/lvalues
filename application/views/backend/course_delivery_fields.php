<?php
$delivery_course = isset($course_details) && is_array($course_details) ? $course_details : [];
$delivery_mode = $delivery_course['delivery_mode'] ?? 'online';
?>
<div class="form-group row mb-3">
    <label class="col-md-2 col-form-label" for="delivery_mode">Course mode</label>
    <div class="col-md-10">
        <select class="form-control" name="delivery_mode" id="delivery_mode">
            <option value="online" <?php echo $delivery_mode === 'online' ? 'selected' : ''; ?>>Online</option>
            <option value="offline" <?php echo $delivery_mode === 'offline' ? 'selected' : ''; ?>>Offline</option>
            <option value="both" <?php echo $delivery_mode === 'both' ? 'selected' : ''; ?>>Both</option>
        </select>
    </div>
</div>
<div class="form-group row mb-3">
    <label class="col-md-2 col-form-label" for="schedule_text">Course timing</label>
    <div class="col-md-10"><input class="form-control" name="schedule_text" id="schedule_text" maxlength="191" value="<?php echo html_escape($delivery_course['schedule_text'] ?? ''); ?>" placeholder="Weekends, 7 PM - 9 PM"></div>
</div>
<div class="form-group row mb-3">
    <label class="col-md-2 col-form-label" for="start_date">Start date</label>
    <div class="col-md-10"><input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo html_escape($delivery_course['start_date'] ?? ''); ?>"></div>
</div>
<div class="form-group row mb-3">
    <label class="col-md-2 col-form-label" for="location_text">Offline location</label>
    <div class="col-md-10"><input class="form-control" name="location_text" id="location_text" maxlength="255" value="<?php echo html_escape($delivery_course['location_text'] ?? ''); ?>" placeholder="Required for offline courses"></div>
</div>
