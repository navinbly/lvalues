<?php
$this->load->model('Course_workflow_model','course_workflow_panel');
$course_workflow_course=$course_details??$this->course_workflow_panel->get_course((int)$course_id);
$course_versions=$course_versions??$this->course_workflow_panel->versions((int)$course_id);
$course_accessibility=$course_accessibility??$this->course_workflow_panel->accessibility_results((int)$course_id);
$course_library=$course_library??$this->course_workflow_panel->library((int)$this->session->userdata('user_id'));
$this->load->model('Moderation_model','course_moderation_panel');
$course_moderation_history=$course_moderation_history??$this->course_moderation_panel->get_history('course',(int)$course_id,20);
$is_course_admin=$this->session->userdata('admin_login')==true;
$workflow=$course_workflow_course['workflow_status']??($course_workflow_course['status']==='active'?'published':$course_workflow_course['status']);
$badge=array('draft'=>'secondary','pending'=>'primary','changes_requested'=>'warning','approved'=>'info','published'=>'success','archived'=>'dark');
$course_sections=$this->db->where('course_id',(int)$course_id)->order_by('order')->get('section')->result_array();
?>
<div class="row"><div class="col-12"><div class="card border"><div class="card-body">
<div class="d-flex justify-content-between align-items-start flex-wrap">
  <div><h5 class="mb-1">Course Workflow</h5><span class="badge badge-<?php echo $badge[$workflow]??'secondary';?>"><?php echo html_escape(ucwords(str_replace('_',' ',$workflow)));?></span>
  <?php if(!empty($course_workflow_course['scheduled_publish_at'])):?><small class="ml-2 text-muted">Scheduled: <?php echo html_escape($course_workflow_course['scheduled_publish_at']);?></small><?php endif;?></div>
  <div><a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?php echo site_url(($is_course_admin?'admin':'user').'/preview/'.$course_id);?>">Preview as Student</a></div>
</div>
<?php if(!empty($course_workflow_course['moderation_reason'])):?><div class="alert alert-warning mt-3 mb-2"><strong>Moderation reason:</strong> <?php echo html_escape($course_workflow_course['moderation_reason']);?></div><?php endif;?>
<p class="small text-muted">Resubmissions: <?php echo (int)($course_workflow_course['resubmission_count']??0);?> · Accessibility: <?php echo (float)($course_workflow_course['accessibility_score']??0);?>%</p>
<div class="d-flex flex-wrap" style="gap:6px">
  <form method="post" action="<?php echo site_url('course-workflow/snapshot/'.$course_id);?>"><input type="hidden" name="summary" value="Manual snapshot"><button class="btn btn-outline-primary btn-sm">Save Version</button></form>
  <form method="post" action="<?php echo site_url('course-workflow/accessibility/'.$course_id);?>"><button class="btn btn-outline-info btn-sm">Run Accessibility Check</button></form>
  <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="collapse" data-target="#duplicateCourse">Duplicate</button>
  <?php if(!$is_course_admin&&in_array($workflow,array('draft','changes_requested'),true)):?><form method="post" action="<?php echo site_url('course-workflow/transition/'.$course_id.'/pending');?>"><button class="btn btn-primary btn-sm">Submit for Review</button></form><?php endif;?>
  <?php if($is_course_admin&&$workflow==='pending'):?><form method="post" action="<?php echo site_url('course-workflow/transition/'.$course_id.'/approved');?>"><button class="btn btn-outline-success btn-sm">Approve</button></form><?php endif;?>
  <?php if($is_course_admin&&$workflow==='approved'):?><form method="post" action="<?php echo site_url('course-workflow/transition/'.$course_id.'/published');?>"><button class="btn btn-success btn-sm">Publish Now</button></form><?php endif;?>
</div>
<div id="duplicateCourse" class="collapse mt-2"><form method="post" action="<?php echo site_url('course-workflow/duplicate/'.$course_id);?>" class="form-inline"><input class="form-control form-control-sm mr-2" name="title" value="<?php echo html_escape($course_workflow_course['title'].' Copy');?>" required><button class="btn btn-primary btn-sm">Create Draft Copy</button></form></div>
<?php if($is_course_admin):?>
<div class="row mt-3">
<?php if(in_array($workflow,array('pending','approved'),true)):?><div class="col-lg-6"><form method="post" action="<?php echo site_url('course-workflow/transition/'.$course_id.'/changes_requested');?>" class="input-group"><input class="form-control form-control-sm" name="reason" placeholder="Required changes and reason" required><div class="input-group-append"><button class="btn btn-warning btn-sm">Request Changes</button></div></form></div><?php endif;?>
<?php if($workflow==='approved'):?><div class="col-lg-6"><form method="post" action="<?php echo site_url('course-workflow/schedule/'.$course_id);?>" class="input-group"><input type="datetime-local" class="form-control form-control-sm" name="scheduled_publish_at" required><div class="input-group-append"><button class="btn btn-info btn-sm">Schedule Publish</button></div></form></div><?php endif;?>
</div>
<?php endif;?>
</div></div></div></div>
<div class="row">
<div class="col-lg-4"><div class="card"><div class="card-body"><h6>Version History</h6><?php foreach(array_slice($course_versions,0,8) as $version):?><div class="border-bottom py-2"><strong>Version <?php echo (int)$version['version_no'];?></strong><br><small><?php echo html_escape($version['change_summary']?:'Saved version');?> · <?php echo html_escape($version['created_at']);?></small><form method="post" action="<?php echo site_url('course-workflow/restore/'.$version['id']);?>" class="mt-1" onsubmit="return confirm('Restore this version as the current draft?');"><button class="btn btn-outline-warning btn-xs">Restore</button></form></div><?php endforeach;?><?php if(empty($course_versions)):?><p class="text-muted small">No server versions yet.</p><?php endif;?></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-body"><h6>Accessibility</h6><?php foreach($course_accessibility as $check):?><div class="d-flex justify-content-between border-bottom py-2"><span><?php echo html_escape(ucwords(str_replace('_',' ',$check['check_type'])));?><br><small class="text-muted"><?php echo html_escape($check['details']);?></small></span><span class="badge badge-<?php echo $check['status']==='pass'?'success':($check['status']==='fail'?'danger':'warning');?>"><?php echo (float)$check['score'];?>%</span></div><?php endforeach;?><?php if(empty($course_accessibility)):?><p class="text-muted small">Run the check to review captions, alt text, reading order, contrast and keyboard support.</p><?php endif;?></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-body"><h6>Reusable Lesson Library</h6><?php if($course_sections&&$course_library):?><form method="post" action="<?php echo site_url('course-workflow/library/use/'.$course_id);?>"><select class="form-control form-control-sm mb-2" name="library_id" required><option value="">Select lesson</option><?php foreach($course_library as $item):?><option value="<?php echo (int)$item['id'];?>"><?php echo html_escape($item['title']);?></option><?php endforeach;?></select><select class="form-control form-control-sm mb-2" name="section_id" required><option value="">Select section</option><?php foreach($course_sections as $section):?><option value="<?php echo (int)$section['id'];?>"><?php echo html_escape($section['title']);?></option><?php endforeach;?></select><button class="btn btn-outline-primary btn-sm btn-block">Add Reusable Lesson</button></form><?php else:?><p class="text-muted small">Save lessons from the curriculum, then reuse them in any course section.</p><?php endif;?></div></div></div>
</div>
<?php if($course_moderation_history):?><div class="card"><div class="card-body"><h6>Moderation and Resubmission History</h6><?php foreach($course_moderation_history as $event):?><div class="border-bottom py-2"><strong><?php echo html_escape(ucwords(str_replace('_',' ',$event['action'])));?></strong> <span class="text-muted"><?php echo html_escape($event['from_status']);?> → <?php echo html_escape($event['to_status']);?></span><br><small><?php echo html_escape($event['reason']);?> · <?php echo html_escape($event['created_at']);?></small></div><?php endforeach;?></div></div><?php endif;?>
