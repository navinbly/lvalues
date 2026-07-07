<?php defined('BASEPATH') OR exit('No direct script access allowed');
function lv_content_status_label($s){ if($s==='pending') return 'In Review'; if($s==='published') return 'Published'; if($s==='rejected') return 'Rejected'; if($s==='on_hold') return 'On Hold'; if($s==='update_required') return 'Update Required'; if($s==='deleted') return 'Deleted'; return 'Draft'; }
function lv_content_status_badge($s){ $c=$s==='published'?'success':($s==='pending'?'primary':($s==='rejected'?'danger':($s==='on_hold'?'warning':($s==='update_required'?'warning':($s==='deleted'?'dark':'secondary'))))); return '<span class="badge badge-'.$c.'">'.lv_content_status_label($s).'</span>'; }
function lv_content_type($node){ $root_key=(string)($node['root_key']??''); return (strpos($root_key,'article_')===0 || ($node['content_type']??'')==='article') ? 'Article / Blog' : ((strpos($root_key,'book_')===0 || ($node['content_type']??'')==='book') ? 'Book' : 'Content'); }
function lv_tutor_name($CI, $uid){ $u=$CI->db->get_where('users', ['id'=>(int)$uid])->row_array(); return $u ? html_escape(trim(($u['first_name']??'').' '.($u['last_name']??''))) : 'User #'.(int)$uid; }
$CI =& get_instance();
$pending_nodes = $pending_nodes ?? [];
$deleted_nodes = $deleted_nodes ?? [];
$published_nodes = $published_nodes ?? [];
$review_filter = $this->input->get('review_filter') ?: 'book';
if (!in_array($review_filter, array('book','article','published','recycle'), true)) $review_filter = 'book';
$filtered_pending_nodes = array_values(array_filter($pending_nodes, function($node) use ($review_filter) {
    $type = strtolower(lv_content_type($node));
    if ($review_filter === 'article') return strpos($type, 'article') !== false;
    if ($review_filter === 'book') return strpos($type, 'book') !== false;
    return true;
}));
?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body">
  <h4 class="page-title mb-2"><i class="mdi mdi-clipboard-check-outline title_icon"></i> Book / Article Review</h4>
  <p class="text-muted mb-0">Approve, reject, put on hold, ask tutor to update, restore deleted content, or permanently delete old content.</p>
</div></div></div></div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><a class="nav-link <?php echo $review_filter==='book'?'active':''; ?>" href="<?php echo site_url('admin/content_nodes_pending?review_filter=book'); ?>">Books Review</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $review_filter==='article'?'active':''; ?>" href="<?php echo site_url('admin/content_nodes_pending?review_filter=article'); ?>">Articles Review</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $review_filter==='published'?'active':''; ?>" href="<?php echo site_url('admin/content_nodes_pending?review_filter=published'); ?>">Published Content</a></li>
  <li class="nav-item"><a class="nav-link <?php echo $review_filter==='recycle'?'active':''; ?>" href="<?php echo site_url('admin/content_nodes_pending?review_filter=recycle'); ?>">Recycle Bin</a></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade <?php echo in_array($review_filter,array('book','article'),true)?'show active':''; ?>" id="reviewTab" role="tabpanel">
    <div class="card"><div class="card-body">
      <h4 class="header-title mb-3"><?php echo $review_filter==='article' ? 'Articles Waiting for Admin Action' : 'Books Waiting for Admin Action'; ?></h4>
      <div class="table-responsive-sm"><table class="table table-striped table-centered mb-0">
        <thead><tr><th>#</th><th>Type</th><th>Title</th><th>Tutor</th><th>Status</th><th>Admin Reason</th><th>Updated</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(!empty($filtered_pending_nodes)): foreach($filtered_pending_nodes as $key=>$node): $note=!empty($node['admin_remark'])?$node['admin_remark']:($node['review_note']??''); ?>
          <tr>
            <td><?php echo $key+1; ?></td>
            <td><span class="badge badge-secondary"><?php echo html_escape(lv_content_type($node)); ?></span></td>
            <td><strong><?php echo html_escape($node['title']??''); ?></strong><br><small class="text-muted"><?php echo html_escape($node['root_key']??''); ?></small></td>
            <td><?php echo lv_tutor_name($CI, $node['created_by']??0); ?></td>
            <td><?php echo lv_content_status_badge($node['status']??'draft'); ?></td>
            <td style="max-width:260px;white-space:normal;"><?php echo $note !== '' ? html_escape($note) : '<span class="text-muted">No note</span>'; ?></td>
            <td><?php echo html_escape($node['updated_at'] ?? $node['created_at'] ?? ''); ?></td>
            <td style="min-width:290px;">
              <button type="button" class="btn btn-sm btn-outline-primary review-action" data-id="<?php echo (int)$node['node_id']; ?>" data-action="approve">Approve</button>
              <button type="button" class="btn btn-sm btn-outline-info review-action" data-id="<?php echo (int)$node['node_id']; ?>" data-action="hold">On Hold</button>
              <button type="button" class="btn btn-sm btn-outline-warning review-action" data-id="<?php echo (int)$node['node_id']; ?>" data-action="ask_update">Ask Update</button>
              <button type="button" class="btn btn-sm btn-outline-danger review-action" data-id="<?php echo (int)$node['node_id']; ?>" data-action="reject">Reject</button>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" class="text-center text-muted">No matching pending/on-hold/rejected/update-required content found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table></div>
    </div></div>
  </div>

  <div class="tab-pane fade <?php echo $review_filter==='published'?'show active':''; ?>" id="publishedTab" role="tabpanel">
    <div class="card"><div class="card-body">
      <h4 class="header-title mb-3">Published Content</h4>
      <div class="table-responsive-sm"><table class="table table-striped table-centered mb-0">
        <thead><tr><th>#</th><th>Type</th><th>Title</th><th>Tutor</th><th>Status</th><th>Approved At</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(!empty($published_nodes)): foreach($published_nodes as $key=>$node): ?>
          <tr>
            <td><?php echo $key+1; ?></td><td><?php echo html_escape(lv_content_type($node)); ?></td><td><strong><?php echo html_escape($node['title']??''); ?></strong></td><td><?php echo lv_tutor_name($CI, $node['created_by']??0); ?></td><td><?php echo lv_content_status_badge($node['status']??'published'); ?></td><td><?php echo html_escape($node['approved_at']??''); ?></td>
            <td><form method="post" action="<?php echo site_url('admin/content_nodes_pending/soft_delete/'.(int)$node['node_id']); ?>"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Move this published content to recycle bin?');">Soft Delete</button></form></td>
          </tr>
        <?php endforeach; else: ?><tr><td colspan="7" class="text-center text-muted">No published content found.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div></div>
  </div>

  <div class="tab-pane fade <?php echo $review_filter==='recycle'?'show active':''; ?>" id="recycleTab" role="tabpanel">
    <div class="card"><div class="card-body">
      <h4 class="header-title mb-3">Recycle Bin</h4>
      <div class="table-responsive-sm"><table class="table table-striped table-centered mb-0">
        <thead><tr><th>#</th><th>Type</th><th>Title</th><th>Deleted By</th><th>Deleted At</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(!empty($deleted_nodes)): foreach($deleted_nodes as $key=>$node): ?>
          <tr>
            <td><?php echo $key+1; ?></td><td><?php echo html_escape(lv_content_type($node)); ?></td><td><strong><?php echo html_escape($node['title']??''); ?></strong></td><td><?php echo lv_tutor_name($CI, $node['deleted_by']??0); ?></td><td><?php echo html_escape($node['deleted_at']??''); ?></td>
            <td>
              <form method="post" action="<?php echo site_url('admin/content_nodes_pending/restore/'.(int)$node['node_id']); ?>" class="d-inline"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><button class="btn btn-sm btn-outline-success" onclick="return confirm('Restore this content as Draft?');">Restore</button></form>
              <form method="post" action="<?php echo site_url('admin/content_nodes_pending/permanent_delete/'.(int)$node['node_id']); ?>" class="d-inline"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete? This cannot be undone.');">Permanent Delete</button></form>
            </td>
          </tr>
        <?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted">Recycle bin is empty.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div></div>
  </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="post" id="reviewForm" class="modal-content">
      <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
      <div class="modal-header"><h5 class="modal-title" id="reviewTitle">Review Content</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <label>Admin note / reason</label>
        <textarea name="review_note" class="form-control" rows="4" placeholder="Example: Please add more examples in Chapter 2, or approved after review."></textarea>
        <small class="text-muted">This note will be visible to the tutor.</small>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Submit Action</button></div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.review-action').forEach(function(btn){
  btn.addEventListener('click', function(){
    var action=this.dataset.action, id=this.dataset.id;
    var labels={approve:'Approve and Publish',hold:'Put On Hold',ask_update:'Ask Tutor to Update',reject:'Reject'};
    document.getElementById('reviewTitle').textContent=labels[action]||'Review Content';
    document.getElementById('reviewForm').action='<?php echo site_url('admin/content_nodes_pending'); ?>/'+action+'/'+id;
    document.querySelector('#reviewForm textarea[name="review_note"]').required=action!=='approve';
    $('#reviewModal').modal('show');
  });
});
</script>
