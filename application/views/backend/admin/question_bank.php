<?php defined('BASEPATH') OR exit('No direct script access allowed');
$edit = is_array($edit_question ?? null) ? $edit_question : array();
$edit_options = $edit['options'] ?? array();
$active_tab = $this->input->get('qb_tab') ?: ($edit ? 'upload' : 'create_exam');
if (!in_array($active_tab, array('create_exam','upload','search'), true)) $active_tab = 'create_exam';
$total_pages = max(1, (int)ceil(($total_questions ?? 0) / max(1, $per_page ?? 25)));
function lv_qb_query($overrides = array()) {
    $query = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') unset($query[$key]); else $query[$key] = $value;
    }
    return http_build_query($query);
}
function lv_qb_badge($status) {
    $classes = array('active'=>'success','in_review'=>'primary','draft'=>'secondary','rejected'=>'danger','on_hold'=>'warning','update_required'=>'warning','archived'=>'dark');
    return '<span class="badge badge-'.($classes[$status] ?? 'secondary').'">'.html_escape(ucwords(str_replace('_',' ',$status))).'</span>';
}
$exam_names = array_filter(array_map(function($r){ return $r['name'] ?? ''; }, $filter_options['exam_names'] ?? array()));
$qb_classes = $filter_options['classes'] ?? array();
$qb_subjects = $filter_options['subjects'] ?? array();
$sections_by_exam = array();
foreach (($filter_options['sub_categories'] ?? array()) as $row) {
    $examKey = (string)($row['exam_name'] ?? '');
    if (!isset($sections_by_exam[$examKey])) $sections_by_exam[$examKey] = array();
    if (!empty($row['name'])) $sections_by_exam[$examKey][] = (string)$row['name'];
}
?>
<style>
.qb-shell{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;margin-bottom:18px;box-shadow:0 8px 24px rgba(15,23,42,.04)}.qb-tab-card{display:block;border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:24px;color:#111827;min-height:116px;transition:.18s ease;box-shadow:none}.qb-tab-card:hover{text-decoration:none;border-color:#2563eb;box-shadow:0 8px 22px rgba(37,99,235,.08)}.qb-tab-card.active{border-color:#2563eb;background:#f8fbff}.qb-tab-card h5{font-size:18px;font-weight:700;margin:0 0 12px;color:#111827}.qb-tab-card small{display:block;font-size:14px;line-height:1.55;color:#6b7280}.qb-metric{border:1px solid #e7edf5;border-radius:16px;background:#fff;padding:14px;box-shadow:0 8px 22px rgba(30,41,59,.05)}.qb-metric h3{margin:0;color:#145388}.qb-panel{border:1px solid #e7edf5;border-radius:16px;background:#fff;box-shadow:0 8px 22px rgba(30,41,59,.04)}.qb-panel .card-body{padding:22px}.qb-filter{border:1px solid #e7edf5;border-radius:16px;background:#fbfdff;padding:14px}.qb-question{max-width:560px;white-space:normal}.qb-classification{font-size:12px;line-height:1.6}.qb-chip{display:inline-block;background:#eef6ff;color:#145388;border-radius:999px;padding:2px 8px;margin:1px}.qb-option{margin-bottom:8px}.btn-primary{background:#2f80ed;border-color:#2f80ed}.qb-help{background:#f8fbff;border:1px solid #dbeafe;border-radius:14px;padding:12px}.qb-step{display:flex;gap:10px;margin-bottom:8px}.qb-step span{width:26px;height:26px;border-radius:50%;background:#eef6ff;color:#2f80ed;display:inline-flex;align-items:center;justify-content:center;font-weight:700}.table td,.table th{vertical-align:middle}.qb-actions .btn{margin:2px}
</style>



<div class="row mb-3">
  <div class="col-md-3 mb-2"><div class="qb-metric"><small class="text-muted">Total Questions</small><h3><?php echo (int)($summary['total_questions'] ?? 0); ?></h3></div></div>
  <div class="col-md-3 mb-2"><div class="qb-metric"><small class="text-muted">Active</small><h3><?php echo (int)($summary['active_questions'] ?? 0); ?></h3></div></div>
  <div class="col-md-3 mb-2"><div class="qb-metric"><small class="text-muted">Draft</small><h3><?php echo (int)($summary['draft_questions'] ?? 0); ?></h3></div></div>
  <div class="col-md-3 mb-2"><div class="qb-metric"><small class="text-muted">On Hold</small><h3><?php echo (int)($summary['on_hold_questions'] ?? 0); ?></h3></div></div>
</div>

<?php if($active_tab === 'create_exam'): ?>
<div class="row">
  <div class="col-lg-5 mb-3">
    <div class="card qb-panel"><div class="card-body">
      <h4 class="header-title">Create Exam Name</h4>
      <p class="text-muted small">Create the parent exam first. Example: <b>Bank PO Pre</b>, <b>SSC CGL Tier 1</b>.</p>
      <form method="post" action="<?php echo site_url('admin/question_bank_exam_master_save'); ?>">
        <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
        <input type="hidden" name="qb_tab" value="create_exam">
        <div class="form-group"><label>Exam Name</label><input class="form-control" name="exam_name" placeholder="Example: Bank PO Pre" required></div>
        <button class="btn btn-primary btn-block">Add Exam</button>
      </form>
    </div></div>
  </div>
  <div class="col-lg-7 mb-3">
    <div class="card qb-panel"><div class="card-body">
      <h4 class="header-title">Add Category / Section</h4>
      <p class="text-muted small">Create categories under the exam. These categories will be used for random question selection in Exam Pattern Builder.</p>
      <form method="post" action="<?php echo site_url('admin/question_bank_section_master_save'); ?>">
        <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
        <input type="hidden" name="qb_tab" value="create_exam">
        <div class="row"><div class="col-md-5 mb-2"><label>Exam</label><select class="form-control" name="exam_name" required><option value="">Select Exam</option><?php foreach($exam_names as $name): ?><option value="<?php echo html_escape($name); ?>"><?php echo html_escape($name); ?></option><?php endforeach; ?></select></div><div class="col-md-5 mb-2"><label>Category / Section</label><input class="form-control" name="section_name" placeholder="Example: Reasoning Ability" required></div><div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Add</button></div></div>
      </form>
    </div></div>
  </div>
</div>
<div class="card qb-panel"><div class="card-body">
  <h4 class="header-title">Created Exam Structure</h4>
  <?php if(empty($exam_names)): ?><div class="alert alert-light border mb-0">No exam master created yet.</div><?php else: ?>
  <div class="row"><?php foreach($exam_names as $name): ?><div class="col-md-4 mb-3"><div class="qb-help h-100"><strong><?php echo html_escape($name); ?></strong><div class="mt-2"><?php $items=$sections_by_exam[$name]??array(); if(empty($items)): ?><span class="text-muted small">No category added yet.</span><?php else: foreach($items as $sec): ?><span class="qb-chip"><?php echo html_escape($sec); ?></span><?php endforeach; endif; ?></div></div></div><?php endforeach; ?></div>
  <?php endif; ?>
</div></div>
<?php endif; ?>

<?php if($active_tab === 'upload'): ?>
<div class="row">
  <div class="col-xl-5 mb-3">
    <div class="card qb-panel"><div class="card-body">
      <h4 class="header-title">Bulk CSV / Excel Import Questions. Please see attached Template</h4>
      <p class="text-muted small">Use this for large question sets. Download the template, fill questions, then import.</p>
      <div class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('admin/question_bank_import_template'); ?>">Download CSV Template</a> <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('admin/question_bank_export?'.lv_qb_query(array('qb_tab'=>'upload'))); ?>">Export Current Questions</a></div>
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('admin/question_bank_import'); ?>">
        <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
        <div class="form-group"><label>Question File</label><input type="file" class="form-control" name="question_file" accept=".csv,.xlsx" required></div>
        <button class="btn btn-primary btn-block">Import Questions</button>
      </form>
      <?php if(!empty($recent_imports)): ?><div class="table-responsive mt-3"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>File</th><th>Status</th><th>Imported</th><th>Failed</th></tr></thead><tbody><?php foreach($recent_imports as $job): ?><tr><td><?php echo html_escape($job['created_at']); ?></td><td><?php echo html_escape($job['original_filename']); ?></td><td><?php echo html_escape(ucwords(str_replace('_',' ',$job['status']))); ?></td><td><?php echo (int)$job['imported_rows']; ?></td><td><?php echo (int)$job['failed_rows']; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div></div>
  </div>
  <div class="col-xl-7 mb-3">
    <div class="card qb-panel"><div class="card-body">
      <div class="d-flex justify-content-between"><h4 class="header-title"><?php echo $edit?'Edit Question':'Add Question One by One'; ?></h4><?php if($edit): ?><a href="<?php echo site_url('admin/question_bank?qb_tab=upload'); ?>">New Question</a><?php endif; ?></div>
      <form method="post" action="<?php echo site_url('admin/question_bank_save/'.(int)($edit['id']??0)); ?>">
        <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Exam Name</label><select class="form-control qb-exam-select" name="exam_type" required data-selected="<?php echo html_escape($edit['exam_type'] ?? ($filters['exam_type'] ?? '')); ?>"><option value="">Select Exam Name</option><?php foreach($exam_names as $name): ?><option value="<?php echo html_escape($name); ?>" <?php echo ($edit['exam_type'] ?? ($filters['exam_type'] ?? ''))===$name?'selected':''; ?>><?php echo html_escape($name); ?></option><?php endforeach; ?></select></div></div><div class="col-md-6"><div class="form-group"><label>Category / Section</label><select class="form-control qb-section-select" name="question_section" required data-selected="<?php echo html_escape(($edit['question_section'] ?? '') ?: ($edit['topic'] ?? ($filters['question_section'] ?? ''))); ?>"><option value="">Select Category</option></select></div></div></div>
        <div class="form-group"><label>Question Text</label><textarea class="form-control" rows="4" name="question_text" id="qbQuestionText" required><?php echo html_escape($edit['question_text'] ?? ''); ?></textarea></div>
        <div class="row"><div class="col-md-4"><div class="form-group"><label>Topic</label><input class="form-control" name="topic" value="<?php echo html_escape(!empty($edit['question_section']) ? ($edit['topic'] ?? '') : ''); ?>" placeholder="Percentage"></div></div><div class="col-md-4"><div class="form-group"><label>Type</label><select class="form-control" name="question_type"><option value="single" <?php echo ($edit['question_type']??'')==='single'?'selected':''; ?>>Single Choice</option><option value="multiple" <?php echo ($edit['question_type']??'')==='multiple'?'selected':''; ?>>Multiple Choice</option></select></div></div><div class="col-md-4"><div class="form-group"><label>Workflow</label><select class="form-control" name="status"><option value="draft">Save Draft</option><option value="in_review">Submit for Review</option><option value="active">Active</option><option value="archived">Archive</option></select></div></div></div>
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Class</label><select class="form-control" name="class_id"><option value="0">Not mapped</option><?php foreach($qb_classes as $row):?><option value="<?php echo (int)$row['id'];?>" <?php echo (int)($edit['class_id']??0)===(int)$row['id']?'selected':'';?>><?php echo html_escape($row['name']);?></option><?php endforeach;?></select></div></div><div class="col-md-6"><div class="form-group"><label>Subject</label><select class="form-control" name="subject_id"><option value="0">Not mapped</option><?php foreach($qb_subjects as $row):?><option value="<?php echo (int)$row['id'];?>" <?php echo (int)($edit['subject_id']??0)===(int)$row['id']?'selected':'';?>><?php echo html_escape($row['name']);?></option><?php endforeach;?></select></div></div></div>
        <div class="row"><div class="col-md-4"><div class="form-group"><label>Chapter</label><input class="form-control" name="chapter" value="<?php echo html_escape($edit['chapter']??'');?>"></div></div><div class="col-md-4"><div class="form-group"><label>Marks</label><input class="form-control" type="number" step="0.01" min="0.01" name="marks" value="<?php echo html_escape($edit['marks'] ?? '1'); ?>"></div></div><div class="col-md-4"><div class="form-group"><label>Negative Marks</label><input class="form-control" type="number" step="0.01" min="0" name="negative_marks" value="<?php echo html_escape($edit['negative_marks'] ?? '0'); ?>"></div></div></div>
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Difficulty</label><select class="form-control" name="difficulty"><?php foreach(array('Beginner','Intermediate','Advanced') as $value): ?><option <?php echo ($edit['difficulty']??'Beginner')===$value?'selected':''; ?>><?php echo $value; ?></option><?php endforeach; ?></select></div></div><div class="col-md-6"><div class="form-group"><label>Tags</label><input class="form-control" name="tags" value="<?php echo html_escape($edit['tags'] ?? ''); ?>" placeholder="comma separated"></div></div></div>
        <div class="form-group"><label>Learning Outcome</label><input class="form-control" name="learning_outcome" value="<?php echo html_escape($edit['learning_outcome']??'');?>" placeholder="What should the learner demonstrate?"></div>
        <label>Answer Options</label><?php for($i=0;$i<5;$i++): $opt=$edit_options[$i]??array('option_text'=>'','is_correct'=>0); ?><div class="input-group qb-option"><div class="input-group-prepend"><span class="input-group-text"><?php echo chr(65+$i); ?></span></div><input class="form-control" name="options[<?php echo $i; ?>]" value="<?php echo html_escape($opt['option_text'] ?? ''); ?>"><div class="input-group-append"><label class="input-group-text"><input type="checkbox" name="correct_options[]" value="<?php echo $i; ?>" <?php echo !empty($opt['is_correct'])?'checked':''; ?>> Correct</label></div></div><?php endfor; ?>
        <div class="form-group"><label>Explanation</label><textarea class="form-control" rows="3" name="explanation"><?php echo html_escape($edit['explanation'] ?? ''); ?></textarea></div>
        <button type="button" class="btn btn-outline-secondary btn-block" id="qbPreviewButton">Preview as Student</button><div class="alert alert-light border mt-2 d-none" id="qbStudentPreview" aria-live="polite"></div><button class="btn btn-primary btn-block mt-3"><?php echo $edit?'Update Question':'Add to Question Bank'; ?></button>
      </form>
    </div></div>
  </div>
</div>
<?php endif; ?>

<?php if($active_tab === 'search'): ?>
<div class="card qb-panel mb-3"><div class="card-body">
  <h4 class="header-title">Search Question</h4>
  <form method="get" action="<?php echo site_url('admin/question_bank'); ?>"><input type="hidden" name="qb_tab" value="search">
    <div class="row"><div class="col-md-4 mb-2"><input class="form-control" name="search" value="<?php echo html_escape($filters['search'] ?? ''); ?>" placeholder="Search question, code, topic or tag"></div><div class="col-md-2 mb-2"><select class="form-control" name="status"><option value="">All statuses</option><?php foreach(array('draft','in_review','active','rejected','on_hold','update_required','archived') as $value): ?><option value="<?php echo $value; ?>" <?php echo ($filters['status']??'')===$value?'selected':''; ?>><?php echo ucwords(str_replace('_',' ',$value)); ?></option><?php endforeach; ?></select></div><div class="col-md-2 mb-2"><select class="form-control" name="difficulty"><option value="">All difficulties</option><?php foreach(array('Beginner','Intermediate','Advanced') as $value): ?><option <?php echo ($filters['difficulty']??'')===$value?'selected':''; ?>><?php echo $value; ?></option><?php endforeach; ?></select></div><div class="col-md-2 mb-2"><select class="form-control qb-exam-select" name="exam_type"><option value="">All exams</option><?php foreach($exam_names as $name): ?><option value="<?php echo html_escape($name); ?>" <?php echo ($filters['exam_type']??'')===$name?'selected':''; ?>><?php echo html_escape($name); ?></option><?php endforeach; ?></select></div><div class="col-md-2 mb-2"><button class="btn btn-primary btn-block">Filter</button></div><div class="col-md-3 mb-2"><select class="form-control qb-section-select" name="question_section" data-selected="<?php echo html_escape($filters['question_section'] ?? ''); ?>"><option value="">All categories</option></select></div><div class="col-md-3 mb-2"><input class="form-control" name="topic" value="<?php echo html_escape($filters['topic'] ?? ''); ?>" placeholder="Topic"></div><div class="col-md-2 mb-2"><select class="form-control" name="question_type"><option value="">All types</option><option value="single" <?php echo ($filters['question_type']??'')==='single'?'selected':''; ?>>Single choice</option><option value="multiple" <?php echo ($filters['question_type']??'')==='multiple'?'selected':''; ?>>Multiple choice</option></select></div><div class="col-md-2 mb-2"><input class="form-control" name="chapter" value="<?php echo html_escape($filters['chapter']??'');?>" placeholder="Chapter"></div><div class="col-md-2 mb-2"><a class="btn btn-light btn-block" href="<?php echo site_url('admin/question_bank?qb_tab=search'); ?>">Clear</a></div></div>
  </form>
</div></div>
<div class="card qb-panel"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h4 class="header-title mb-0">Questions</h4><span class="text-muted"><?php echo (int)$total_questions; ?> result(s)</span></div><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Code</th><th>Question</th><th>Classification</th><th>Marks</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if(empty($questions)): ?><tr><td colspan="6" class="text-center text-muted py-4">No questions found.</td></tr><?php else: foreach($questions as $question): ?><tr><td><small><?php echo html_escape($question['question_code'] ?? ('Q-'.$question['id'])); ?></small></td><td class="qb-question"><strong><?php echo html_escape($question['question_text']); ?></strong><br><small class="text-muted"><?php echo html_escape($question['question_type']); ?> · <?php echo html_escape($question['difficulty']); ?></small></td><td class="qb-classification"><span class="qb-chip"><?php echo html_escape($question['exam_type'] ?: 'No exam'); ?></span><br><span class="qb-chip"><?php echo html_escape(($question['question_section'] ?? '') ?: ($question['topic'] ?: 'No category')); ?></span></td><td><?php echo html_escape($question['marks']); ?><br><small class="text-danger">-<?php echo html_escape($question['negative_marks']); ?></small></td><td><?php echo lv_qb_badge($question['status']); ?></td><td class="qb-actions" style="min-width:220px"><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('admin/question_bank?'.lv_qb_query(array('qb_tab'=>'upload','edit'=>(int)$question['id']))); ?>">Edit</a><?php if(in_array($question['status'],array('draft','rejected','on_hold','update_required'),true)): ?><form method="post" action="<?php echo site_url('admin/question_bank_status/'.(int)$question['id']); ?>" class="d-inline"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><input type="hidden" name="status" value="in_review"><button class="btn btn-sm btn-outline-info">Submit Review</button></form><?php endif; ?><form method="post" action="<?php echo site_url('admin/question_bank_duplicate/'.(int)$question['id']); ?>" class="d-inline"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><button class="btn btn-sm btn-outline-secondary">Duplicate</button></form><form method="post" action="<?php echo site_url('admin/question_bank_status/'.(int)$question['id']); ?>" class="d-inline"><input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>"><input type="hidden" name="status" value="archived"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this question?');">Archive</button></form></td></tr><?php endforeach; endif; ?></tbody></table></div><?php if($total_pages > 1): ?><nav><ul class="pagination justify-content-end"><?php for($p=1;$p<=$total_pages;$p++): ?><li class="page-item <?php echo $p==(int)($page??1)?'active':''; ?>"><a class="page-link" href="?<?php echo lv_qb_query(array('qb_tab'=>'search','page'=>$p)); ?>"><?php echo $p; ?></a></li><?php endfor; ?></ul></nav><?php endif; ?></div></div>
<?php endif; ?>

<script>
var qbSectionsByExam = <?php echo json_encode($sections_by_exam); ?>;
function escQb(value){return String(value||'').replace(/[&<>'"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c];});}
function fillQbSection(select) {
  var container = select.closest('form') || document;
  var examSelect = container.querySelector('.qb-exam-select');
  var sectionSelect = container.querySelector('.qb-section-select');
  if (!examSelect || !sectionSelect) return;
  var selected = sectionSelect.getAttribute('data-selected') || sectionSelect.value || '';
  var exam = examSelect.value || '';
  var list = qbSectionsByExam[exam] || [];
  var label = sectionSelect.required ? 'Select Category / Section' : 'All categories';
  var html = '<option value="">'+label+'</option>';
  list.forEach(function(name){ html += '<option value="'+escQb(name)+'">'+escQb(name)+'</option>'; });
  sectionSelect.innerHTML = html;
  if (selected) sectionSelect.value = selected;
}
document.querySelectorAll('.qb-exam-select').forEach(function(examSelect){fillQbSection(examSelect);examSelect.addEventListener('change', function(){var container = examSelect.closest('form') || document;var sectionSelect = container.querySelector('.qb-section-select');if (sectionSelect) sectionSelect.setAttribute('data-selected','');fillQbSection(examSelect);});});
var previewBtn = document.getElementById('qbPreviewButton');
if (previewBtn) previewBtn.addEventListener('click', function(){var preview = document.getElementById('qbStudentPreview');var question = document.getElementById('qbQuestionText').value.trim();var options = Array.prototype.filter.call(document.querySelectorAll('input[name^="options["]'), function(input){ return input.value.trim() !== ''; });preview.innerHTML = '<strong>'+escQb(question || 'Question preview')+'</strong><ol class="mt-2 mb-0">'+options.map(function(input){return '<li>'+escQb(input.value)+'</li>';}).join('')+'</ol>';preview.classList.remove('d-none');});
</script>
