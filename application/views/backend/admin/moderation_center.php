<?php
defined('BASEPATH') OR exit('No direct script access allowed');
function lv_mod_badge($status) {
    $classes=array('in_review'=>'primary','on_hold'=>'warning','update_required'=>'warning','rejected'=>'danger','published'=>'success');
    return '<span class="badge badge-'.($classes[$status]??'secondary').'">'.html_escape(ucwords(str_replace('_',' ',$status))).'</span>';
}
?>
<style>
.review-metric{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#fff}.review-table td,.review-table th{vertical-align:middle}.review-note{max-width:260px;white-space:normal}.review-actions{min-width:310px}
</style>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
  <h4 class="page-title mb-1"><i class="mdi mdi-clipboard-check-multiple-outline title_icon"></i> Admin Review Center</h4>
  <p class="text-muted mb-0">Publish, reject, hold, or request updates for exam patterns and question-bank items. Books and articles remain in their established review queue.</p>
</div></div></div></div>
<div class="row mb-3">
  <div class="col-md-4 mb-2"><div class="review-metric"><small class="text-muted">Exam Reviews</small><h3><?php echo count($review_exams ?? array()); ?></h3></div></div>
  <div class="col-md-4 mb-2"><div class="review-metric"><small class="text-muted">Question Reviews</small><h3><?php echo count($review_questions ?? array()); ?></h3></div></div>
  <div class="col-md-4 mb-2"><div class="review-metric"><small class="text-muted">Book / Article Reviews</small><div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('admin/content_nodes_pending'); ?>">Open Existing Queue</a></div></div></div>
</div>
<div class="card"><div class="card-body"><h4 class="header-title">Exam Pattern Review</h4><div class="table-responsive"><table class="table table-striped review-table"><thead><tr><th>Exam</th><th>Pattern</th><th>Status</th><th>Reviewer Note</th><th>Updated</th><th>Action</th></tr></thead><tbody>
<?php foreach(($review_exams ?? array()) as $exam): ?><tr><td><b><?php echo html_escape($exam['title']); ?></b><br><small class="text-muted">Exam #<?php echo (int)$exam['id']; ?></small></td><td><?php echo (int)$exam['section_count']; ?> sections<br><?php echo (int)$exam['question_count']; ?> questions</td><td><?php echo lv_mod_badge($exam['review_status']); ?></td><td class="review-note"><?php echo html_escape($exam['admin_remark'] ?: 'No note'); ?></td><td><?php echo html_escape($exam['updated_at']); ?></td><td class="review-actions"><?php echo lv_review_buttons('exam',(int)$exam['id']); ?></td></tr><?php endforeach; ?>
<?php if(empty($review_exams)): ?><tr><td colspan="6" class="text-center text-muted">No exam patterns waiting for review.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="card"><div class="card-body"><h4 class="header-title">Question Bank Review</h4><div class="table-responsive"><table class="table table-striped review-table"><thead><tr><th>Code</th><th>Question</th><th>Status</th><th>Reviewer Note</th><th>Updated</th><th>Action</th></tr></thead><tbody>
<?php foreach(($review_questions ?? array()) as $question): ?><tr><td><?php echo html_escape($question['question_code']); ?></td><td class="review-note"><b><?php echo html_escape($question['title']); ?></b><br><small class="text-muted"><?php echo html_escape($question['topic'] ?: 'General'); ?> · <?php echo html_escape($question['difficulty']); ?></small></td><td><?php echo lv_mod_badge($question['review_status']); ?></td><td class="review-note"><?php echo html_escape($question['admin_remark'] ?: 'No note'); ?></td><td><?php echo html_escape($question['updated_at']); ?></td><td class="review-actions"><?php echo lv_review_buttons('question',(int)$question['id']); ?></td></tr><?php endforeach; ?>
<?php if(empty($review_questions)): ?><tr><td colspan="6" class="text-center text-muted">No questions waiting for review.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="modal fade" id="moderationModal" tabindex="-1"><div class="modal-dialog"><form method="post" id="moderationForm" class="modal-content"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><div class="modal-header"><h5 class="modal-title" id="moderationTitle">Review</h5><button class="close" type="button" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>Admin reason / note</label><textarea class="form-control" name="reason" id="moderationReason" rows="4"></textarea><small class="text-muted">A reason is mandatory for Hold, Ask Update, and Reject.</small></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary">Submit Decision</button></div></form></div></div>
<div class="card"><div class="card-body"><h4 class="header-title">Recent Moderation History</h4><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Entity</th><th>Decision</th><th>Status Change</th><th>Reviewer</th><th>Reason</th></tr></thead><tbody><?php foreach(($recent_events ?? array()) as $event): ?><tr><td><?php echo html_escape($event['created_at']); ?></td><td><?php echo html_escape(ucfirst($event['entity_type']).' #'.(int)$event['entity_id']); ?></td><td><?php echo html_escape(ucwords(str_replace('_',' ',$event['action']))); ?></td><td><?php echo html_escape(ucwords(str_replace('_',' ',$event['from_status']))); ?> → <?php echo html_escape(ucwords(str_replace('_',' ',$event['to_status']))); ?></td><td><?php echo html_escape(trim(($event['first_name']??'').' '.($event['last_name']??'')) ?: 'Admin #'.(int)$event['actor_id']); ?></td><td class="review-note"><?php echo html_escape($event['reason'] ?: 'No note'); ?></td></tr><?php endforeach; ?><?php if(empty($recent_events)): ?><tr><td colspan="6" class="text-center text-muted">No moderation decisions recorded yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php
function lv_review_buttons($type,$id) {
    $buttons=array('approve'=>array('success','Approve & Publish'),'hold'=>array('info','On Hold'),'ask_update'=>array('warning','Ask Update'),'reject'=>array('danger','Reject'));
    $html='';
    foreach($buttons as $action=>$data) $html.='<button type="button" class="btn btn-sm btn-outline-'.$data[0].' moderation-btn" data-type="'.$type.'" data-id="'.$id.'" data-action="'.$action.'">'.$data[1].'</button> ';
    return $html;
}
?>
<script>
document.querySelectorAll('.moderation-btn').forEach(function(button){
  button.addEventListener('click',function(){
    var action=this.dataset.action;
    document.getElementById('moderationTitle').textContent={approve:'Approve and Publish',hold:'Put On Hold',ask_update:'Ask for Update',reject:'Reject'}[action];
    document.getElementById('moderationReason').required=action!=='approve';
    document.getElementById('moderationReason').value='';
    document.getElementById('moderationForm').action='<?php echo site_url('admin/moderation_action'); ?>/'+this.dataset.type+'/'+this.dataset.id+'/'+action;
    $('#moderationModal').modal('show');
  });
});
</script>
