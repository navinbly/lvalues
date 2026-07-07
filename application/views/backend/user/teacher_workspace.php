<?php
$agenda = isset($agenda) && is_array($agenda) ? $agenda : [];
$calendar = isset($calendar) && is_array($calendar) ? $calendar : [];
$templates = isset($templates) && is_array($templates) ? $templates : [];
?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body d-flex justify-content-between align-items-center flex-wrap">
    <div><h4 class="page-title mb-1"><i class="mdi mdi-view-dashboard-outline title_icon"></i> Daily Workspace</h4><p class="text-muted mb-0">Classes, grading, requests, follow-ups and overdue work in priority order.</p></div>
    <div><a class="btn btn-primary btn-sm" href="<?php echo site_url('tutor_batch/create'); ?>">Create batch</a> <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('tutor_batch'); ?>">Manage batches</a> <a class="btn btn-outline-info btn-sm" href="<?php echo site_url('teacher-analytics'); ?>">Analytics & Quality</a></div>
</div></div></div></div>

<div class="row">
<div class="col-lg-7"><div class="card"><div class="card-body">
    <h5>Prioritized Agenda</h5>
    <?php if ($agenda): ?><div class="list-group list-group-flush">
    <?php foreach ($agenda as $item): ?>
        <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
            <div class="mr-3"><span class="badge badge-<?php echo (int)$item['priority'] >= 90 ? 'danger' : ((int)$item['priority'] >= 75 ? 'warning' : 'info'); ?>-lighten"><?php echo html_escape(ucfirst(str_replace('_',' ',$item['item_type']))); ?></span>
            <strong class="d-block mt-1"><?php echo html_escape($item['title']); ?></strong>
            <small class="text-muted"><?php echo $item['due_at'] ? html_escape(date('d M, h:i A',strtotime($item['due_at']))) : 'No due time'; ?></small></div>
            <div class="text-nowrap"><?php if(!empty($item['action_url'])):?><a class="btn btn-outline-primary btn-sm" href="<?php echo html_escape($item['action_url']); ?>">Open</a><?php endif;?>
            <form class="d-inline teacherAgendaDone" method="post" action="<?php echo site_url('teacher-workflow/complete/'.$item['id']); ?>"><button class="btn btn-outline-success btn-sm" type="submit">Done</button></form></div>
        </div>
    <?php endforeach; ?></div><?php else:?><p class="text-muted">Your agenda is clear.</p><?php endif;?>
</div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-body">
    <h5>Upcoming Calendar</h5>
    <?php foreach(array_slice($calendar,0,12) as $event):?>
    <div class="border rounded p-2 mb-2"><strong><?php echo html_escape($event['title']);?></strong><br><small class="text-muted"><?php echo html_escape($event['session_date'].' '.substr($event['start_time'],0,5).' '.$event['timezone']);?> · <?php echo html_escape($event['batch_title']);?></small>
    <div class="mt-1"><a class="btn btn-outline-info btn-sm" href="<?php echo site_url('tutor_batch/attendance/'.$event['id']);?>">Attendance</a> <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('live-learning/join/'.$event['id']);?>">Join/Test</a></div></div>
    <?php endforeach;?>
</div></div></div>
</div>
<?php if($templates):?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body"><h5>Batch Templates</h5><div class="row">
<?php foreach($templates as $template):?><div class="col-lg-4 mb-3"><form method="post" action="<?php echo site_url('teacher-workflow/use-template/'.$template['id']);?>" class="border rounded p-3 h-100"><strong><?php echo html_escape($template['name']);?></strong><p class="small text-muted"><?php echo html_escape($template['description']??'Reusable batch structure');?></p><input name="title" class="form-control form-control-sm mb-2" placeholder="New batch title" required><input type="date" name="start_date" class="form-control form-control-sm mb-2" min="<?php echo date('Y-m-d');?>" required><button class="btn btn-primary btn-sm btn-block">Create from template</button></form></div><?php endforeach;?>
</div></div></div></div></div>
<?php endif;?>
<script>
document.querySelectorAll('.teacherAgendaDone').forEach(function(form){form.addEventListener('submit',function(e){e.preventDefault();fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){if(r.status)form.closest('.list-group-item').remove();});});});
</script>
