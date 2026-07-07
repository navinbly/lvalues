<?php defined('BASEPATH') OR exit('No direct script access allowed');
$CI =& get_instance();
$CI->load->model('Exam_model', 'exam_model');
$tutor_id = (int)$CI->session->userdata('user_id');
$exams = $CI->exam_model->get_tutor_exams($tutor_id);
$analytics = $CI->exam_model->get_tutor_analytics($tutor_id);
$selected_exam_id = (int)$CI->input->get('exam_id');
$selected_exam = $selected_exam_id ? $CI->exam_model->get_exam($selected_exam_id) : null;
if (!empty($selected_exam) && (int)$selected_exam['tutor_id'] !== $tutor_id) $selected_exam = null;
$questions = !empty($selected_exam) ? $CI->exam_model->get_questions_with_options((int)$selected_exam['id'], false, true) : array();
function lv_exam_limit($text, $limit = 90) { $text = trim(strip_tags((string)$text)); return strlen($text) > $limit ? substr($text, 0, $limit).'...' : $text; }
function lv_exam_badge($status){ $c='secondary'; if($status==='published')$c='success'; elseif($status==='archived')$c='dark'; return '<span class="badge badge-'.$c.'">'.htmlspecialchars(ucfirst($status ?: 'draft')).'</span>'; }
function lv_exam_time($seconds){ $seconds=(int)$seconds; if($seconds<=0)return '0m'; return floor($seconds/60).'m '.($seconds%60).'s'; }
$summary = $analytics['summary'] ?? array();
?>
<style>
.exam-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;padding:16px;height:100%;box-shadow:0 1px 2px rgba(15,23,42,.04)}.exam-card:hover{box-shadow:0 12px 28px rgba(15,23,42,.08)}.exam-metric{background:#f8fafc;border-radius:12px;padding:9px 11px}.question-box{border:1px solid #e5e7eb;border-radius:14px;background:#fff;margin-bottom:12px}.question-head{padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e5e7eb}.option-row{font-size:13px;padding:5px 0}.public-link{word-break:break-all}.analytics-tile{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:14px}.analytics-tile h3{margin:0}.import-panel{border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;padding:14px}.small-muted{font-size:12px;color:#64748b}
</style>

<div class="row"><div class="col-12"><div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-start flex-wrap">
    <div><h4 class="page-title mb-1"><i class="mdi mdi-clipboard-text-outline title_icon"></i> Create Public Exam</h4><p class="text-muted mb-0">Create public exams, import question banks, publish attempts, and track results/weak areas.</p></div>
    <button class="btn btn-primary mt-2 mt-md-0" data-toggle="modal" data-target="#examModal"><i class="mdi mdi-plus"></i> Create Exam</button>
  </div>
</div></div></div></div>

<div class="row mb-3">
  <div class="col-md-3 mb-2"><div class="analytics-tile"><small class="text-muted">Total Attempts</small><h3><?php echo (int)($summary['total_attempts'] ?? 0); ?></h3></div></div>
  <div class="col-md-3 mb-2"><div class="analytics-tile"><small class="text-muted">Average Score</small><h3><?php echo round((float)($summary['avg_score'] ?? 0),2); ?>%</h3></div></div>
  <div class="col-md-3 mb-2"><div class="analytics-tile"><small class="text-muted">Pass Percentage</small><h3><?php echo round((float)($summary['pass_percentage'] ?? 0),2); ?>%</h3></div></div>
  <div class="col-md-3 mb-2"><div class="analytics-tile"><small class="text-muted">Avg Time Taken</small><h3><?php echo lv_exam_time((int)($summary['avg_time_seconds'] ?? 0)); ?></h3></div></div>
</div>

<div class="row">
  <?php if(empty($exams)): ?><div class="col-12"><div class="alert alert-info">No exams yet. Click <b>Create Exam</b> to add your first public exam.</div></div><?php endif; ?>
  <?php foreach($exams as $exam): ?>
    <div class="col-lg-4 col-md-6 mb-3"><div class="exam-card">
      <div class="d-flex justify-content-between"><h5 class="mb-1"><?php echo html_escape($exam['title']); ?></h5><?php echo lv_exam_badge($exam['status']); ?></div>
      <p class="text-muted small mb-2"><?php echo html_escape(lv_exam_limit($exam['description'], 90)); ?></p>
      <div class="row text-center mb-3">
        <div class="col-4"><div class="exam-metric"><b><?php echo (int)$exam['question_count']; ?></b><br><small>Questions</small></div></div>
        <div class="col-4"><div class="exam-metric"><b><?php echo (int)$exam['attempt_count']; ?></b><br><small>Attempts</small></div></div>
        <div class="col-4"><div class="exam-metric"><b><?php echo round((float)$exam['avg_score'],1); ?>%</b><br><small>Avg</small></div></div>
      </div>
      <small class="text-muted d-block">Time: <?php echo (int)$exam['time_limit_minutes']; ?> min · Questions shown: <?php echo (int)$exam['question_limit']; ?></small>
      <?php if(($exam['status'] ?? '') === 'published'): ?><small class="text-muted public-link d-block">Public: <?php echo site_url('mock-tests/'.$exam['slug']); ?></small><?php endif; ?>
      <div class="mt-3 d-flex flex-wrap" style="gap:6px;">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('user/content_nodes?section=exam&exam_id='.(int)$exam['id']); ?>">Manage</a>
        <button class="btn btn-sm btn-outline-secondary edit-exam" data-exam='<?php echo html_escape(json_encode($exam), ENT_QUOTES); ?>'>Edit Settings</button>
        <?php if(($exam['status'] ?? '') !== 'published'): ?><a class="btn btn-sm btn-success" onclick="return confirm('Publish this exam publicly?');" href="<?php echo site_url('user/content_exam_publish/'.(int)$exam['id']); ?>">Publish</a><?php else: ?><a class="btn btn-sm btn-outline-info" target="_blank" href="<?php echo site_url('mock-tests/'.$exam['slug']); ?>">Preview</a><?php endif; ?>
        <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this exam?');" href="<?php echo site_url('user/content_exam_archive/'.(int)$exam['id']); ?>">Archive</a>
      </div>
    </div></div>
  <?php endforeach; ?>
</div>

<?php if(!empty($selected_exam)): ?>
<div class="row"><div class="col-12"><div class="import-panel mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap">
    <div><b>Import / Export Questions</b><div class="small-muted">CSV columns: exam_title, question_text, question_type, option_a, option_b, option_c, option_d, option_e, correct_answers, marks, explanation, topic, difficulty</div></div>
    <div class="mt-2 mt-md-0"><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('user/content_exam_import_template'); ?>">Download Sample CSV</a> <a class="btn btn-sm btn-outline-info" href="<?php echo site_url('user/content_exam_export_questions/'.(int)$selected_exam['id']); ?>">Export CSV</a></div>
  </div>
  <form class="mt-2" method="post" enctype="multipart/form-data" action="<?php echo site_url('user/content_exam_import_questions/'.(int)$selected_exam['id']); ?>">
    <div class="input-group"><input type="file" name="question_file" class="form-control" accept=".csv" required><div class="input-group-append"><button class="btn btn-primary" type="submit">Import Questions</button></div></div>
    <small class="text-muted">Invalid rows are skipped with row-wise error summary. Duplicate questions are detected by exam + question text.</small>
  </form>
</div></div></div>

<div class="row"><div class="col-lg-5"><div class="card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-center mb-2"><h4 class="header-title mb-0">Question Bank</h4><button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#questionModal">+ Add Question</button></div>
  <p class="text-muted small">Active questions: <?php echo $CI->exam_model->count_active_questions((int)$selected_exam['id']); ?>. Required to publish: <?php echo (int)$selected_exam['question_limit']; ?>.</p>
  <?php foreach($questions as $idx=>$q): ?>
    <div class="question-box"><div class="question-head d-flex justify-content-between"><b>Q<?php echo $idx+1; ?>. <?php echo html_escape(lv_exam_limit($q['question_text'],80)); ?></b><span class="badge badge-<?php echo ($q['status']==='active'?'success':'secondary'); ?>"><?php echo html_escape($q['status']); ?></span></div>
      <div class="p-3">
        <small class="text-muted d-block">Type: <?php echo html_escape($q['question_type']); ?> · Marks: <?php echo (float)$q['marks']; ?> · Topic: <?php echo html_escape($q['topic']); ?></small>
        <?php foreach($q['options'] as $o): ?><div class="option-row <?php echo !empty($o['is_correct'])?'text-success font-weight-bold':''; ?>">• <?php echo html_escape($o['option_text']); ?> <?php echo !empty($o['is_correct'])?'✓':''; ?></div><?php endforeach; ?>
        <?php if(!empty($q['explanation'])): ?><small class="text-muted d-block mt-1"><b>Explanation:</b> <?php echo html_escape($q['explanation']); ?></small><?php endif; ?>
        <div class="mt-2 d-flex flex-wrap" style="gap:6px;"><button class="btn btn-sm btn-outline-secondary edit-question" data-question='<?php echo html_escape(json_encode($q), ENT_QUOTES); ?>'>Edit</button><button class="btn btn-sm btn-outline-info preview-question" data-question='<?php echo html_escape(json_encode($q), ENT_QUOTES); ?>'>Preview</button><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('user/content_exam_question_duplicate/'.(int)$q['id']); ?>">Duplicate</a><a class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this question?');" href="<?php echo site_url('user/content_exam_question_delete/'.(int)$q['id']); ?>">Delete</a></div>
      </div>
    </div>
  <?php endforeach; ?>
</div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-body">
  <h4 class="header-title">Attempt Report</h4>
  <?php $attempts = $CI->exam_model->get_attempts_for_tutor($tutor_id, (int)$selected_exam['id']); ?>
  <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Name</th><th>Email</th><th>Score</th><th>%</th><th>Result</th><th>Weak Topics</th><th>Time</th><th>Date</th></tr></thead><tbody>
    <?php foreach($attempts as $a): ?><tr><td><?php echo html_escape($a['participant_name'] ?: 'Guest'); ?></td><td><?php echo html_escape($a['participant_email']); ?></td><td><?php echo (float)$a['marks_obtained']; ?>/<?php echo (float)$a['total_marks']; ?></td><td><?php echo (float)$a['percentage']; ?></td><td><?php echo html_escape(strtoupper($a['result_status'])); ?></td><td><?php echo html_escape($a['weak_topics'] ?? $a['improvement_suggestions']); ?></td><td><?php echo lv_exam_time((int)($a['time_taken_seconds'] ?? 0)); ?></td><td><?php echo html_escape($a['submitted_at'] ?: $a['started_at']); ?></td></tr><?php endforeach; ?>
    <?php if(empty($attempts)): ?><tr><td colspan="8" class="text-center text-muted">No attempts yet.</td></tr><?php endif; ?>
  </tbody></table></div>
</div></div>
<div class="card mt-3"><div class="card-body"><h4 class="header-title">Topic-wise Performance</h4><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Topic</th><th>Attempts</th><th>Accuracy</th></tr></thead><tbody><?php foreach(($analytics['topics'] ?? []) as $t): ?><tr><td><?php echo html_escape($t['topic']); ?></td><td><?php echo (int)$t['attempts']; ?></td><td><?php echo (float)$t['accuracy']; ?>%</td></tr><?php endforeach; ?><?php if(empty($analytics['topics'])): ?><tr><td colspan="3" class="text-muted text-center">No topic analytics yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div></div>
<?php endif; ?>

<div class="modal fade" id="examModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" action="<?php echo site_url('user/content_exam_save'); ?>" class="modal-content" id="examForm"><div class="modal-header"><h5 class="modal-title">Exam Settings</h5><button class="close" type="button" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" id="exam_id_field" value="0"><div class="row">
  <div class="col-md-8"><label>Exam Title</label><input name="title" id="exam_title" class="form-control" required></div><div class="col-md-4"><label>Difficulty</label><select name="difficulty" id="exam_difficulty" class="form-control"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select></div>
  <div class="col-md-12 mt-2"><label>Description</label><textarea name="description" id="exam_description" class="form-control" rows="2"></textarea></div>
  <div class="col-md-4 mt-2"><label>Category ID</label><input type="number" name="category_id" id="exam_category_id" class="form-control" value="0"></div><div class="col-md-4 mt-2"><label>Class / Level ID</label><input type="number" name="class_id" id="exam_class_id" class="form-control" value="0"></div><div class="col-md-4 mt-2"><label>Subject ID</label><input type="number" name="subject_id" id="exam_subject_id" class="form-control" value="0"></div>
  <div class="col-md-12 mt-2"><label>Instructions</label><textarea name="instructions" id="exam_instructions" class="form-control" rows="3"></textarea></div>
  <div class="col-md-3 mt-2"><label>Time Limit</label><input type="number" name="time_limit_minutes" id="exam_time_limit_minutes" class="form-control" value="30" min="1" required></div><div class="col-md-3 mt-2"><label>Total Marks</label><input type="number" step="0.01" name="total_marks" id="exam_total_marks" class="form-control" value="100" required></div><div class="col-md-3 mt-2"><label>Passing Marks</label><input type="number" step="0.01" name="passing_marks" id="exam_passing_marks" class="form-control" value="40" required></div><div class="col-md-3 mt-2"><label>Questions to Show</label><input type="number" name="question_limit" id="exam_question_limit" class="form-control" value="10" min="1" required></div>
  <div class="col-md-4 mt-2"><label>Start Date</label><input type="datetime-local" name="starts_at" id="exam_starts_at" class="form-control"></div><div class="col-md-4 mt-2"><label>End Date</label><input type="datetime-local" name="ends_at" id="exam_ends_at" class="form-control"></div><div class="col-md-4 mt-2"><label>Status</label><select name="status" id="exam_status" class="form-control"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></div>
  <div class="col-md-12 mt-3"><div class="row"><div class="col-md-4"><label><input type="checkbox" name="randomize_questions" id="exam_randomize_questions" value="1" checked> Random questions</label></div><div class="col-md-4"><label><input type="checkbox" name="shuffle_options" id="exam_shuffle_options" value="1" checked> Shuffle options</label></div><div class="col-md-4"><label><input type="checkbox" name="show_result_immediately" id="exam_show_result_immediately" value="1" checked> Immediate result</label></div><div class="col-md-4"><label><input type="checkbox" name="show_correct_answers" id="exam_show_correct_answers" value="1" checked> Show correct answers</label></div><div class="col-md-4"><label><input type="checkbox" name="show_explanations" id="exam_show_explanations" value="1" checked> Show explanations</label></div></div></div>
</div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save Exam</button></div></form></div></div>

<?php if(!empty($selected_exam)): ?>
<div class="modal fade" id="questionModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" action="<?php echo site_url('user/content_exam_question_save/'.(int)$selected_exam['id']); ?>" class="modal-content" id="questionForm"><div class="modal-header"><h5 class="modal-title">Question</h5><button class="close" type="button" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="row"><div class="col-md-12"><label>Question Text</label><textarea name="question_text" id="q_text" class="form-control" rows="3" required></textarea></div><div class="col-md-4 mt-2"><label>Type</label><select name="question_type" id="q_type" class="form-control"><option value="single">Single Choice</option><option value="multiple">Multiple Choice</option></select></div><div class="col-md-4 mt-2"><label>Marks</label><input type="number" step="0.01" name="marks" id="q_marks" class="form-control" value="1"></div><div class="col-md-4 mt-2"><label>Status</label><select name="status" id="q_status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="archived">Archived</option></select></div><div class="col-md-6 mt-2"><label>Topic / Chapter</label><input name="topic" id="q_topic" class="form-control"></div><div class="col-md-6 mt-2"><label>Difficulty</label><select name="difficulty" id="q_difficulty" class="form-control"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select></div></div><hr><div class="row">
<?php foreach(['A','B','C','D','E'] as $i=>$label): ?><div class="col-md-12 mb-2"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><?php echo $label; ?></span></div><input name="options[<?php echo $i; ?>]" id="q_option_<?php echo $i; ?>" class="form-control"><div class="input-group-append"><span class="input-group-text"><input type="checkbox" name="correct_options[]" id="q_correct_<?php echo $i; ?>" value="<?php echo $i; ?>"> Correct</span></div></div></div><?php endforeach; ?>
</div><label>Explanation / Solution</label><textarea name="explanation" id="q_explanation" class="form-control" rows="2"></textarea></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save Question</button></div></form></div></div>
<?php endif; ?>

<script>
$('.edit-exam').on('click', function(){ const e=$(this).data('exam'); $('#examForm').attr('action','<?php echo site_url('user/content_exam_save/'); ?>'+e.id); $('#exam_title').val(e.title); $('#exam_description').val(e.description); $('#exam_category_id').val(e.category_id||0); $('#exam_class_id').val(e.class_id||0); $('#exam_subject_id').val(e.subject_id||0); $('#exam_difficulty').val(e.difficulty||'Beginner'); $('#exam_instructions').val(e.instructions||e.policy||''); $('#exam_time_limit_minutes').val(e.time_limit_minutes||30); $('#exam_total_marks').val(e.total_marks||100); $('#exam_passing_marks').val(e.passing_marks||40); $('#exam_question_limit').val(e.question_limit||10); $('#exam_status').val(e.status||'draft'); $('#exam_randomize_questions').prop('checked', parseInt(e.randomize_questions||0)===1); $('#exam_shuffle_options').prop('checked', parseInt(e.shuffle_options||0)===1); $('#exam_show_result_immediately').prop('checked', parseInt(e.show_result_immediately||0)===1); $('#exam_show_correct_answers').prop('checked', parseInt(e.show_correct_answers||0)===1); $('#exam_show_explanations').prop('checked', parseInt(e.show_explanations||0)===1); $('#examModal').modal('show'); });
$('#examModal').on('hidden.bs.modal', function(){ $('#examForm').attr('action','<?php echo site_url('user/content_exam_save'); ?>')[0].reset(); });
$('.edit-question').on('click', function(){ const q=$(this).data('question'); $('#questionForm').attr('action','<?php echo !empty($selected_exam) ? site_url('user/content_exam_question_save/'.(int)$selected_exam['id'].'/') : ''; ?>'+q.id); $('#q_text').val(q.question_text); $('#q_type').val(q.question_type||'single'); $('#q_marks').val(q.marks||1); $('#q_status').val(q.status||'active'); $('#q_topic').val(q.topic||''); $('#q_difficulty').val(q.difficulty||'Beginner'); $('#q_explanation').val(q.explanation||''); for(let i=0;i<5;i++){ $('#q_option_'+i).val(''); $('#q_correct_'+i).prop('checked',false); } (q.options||[]).forEach((o,i)=>{ $('#q_option_'+i).val(o.option_text); $('#q_correct_'+i).prop('checked', parseInt(o.is_correct||0)===1); }); $('#questionModal').modal('show'); });
$('.preview-question').on('click', function(){ const q=$(this).data('question'); let text=q.question_text+'\n\n'; (q.options||[]).forEach(o=>{text+='• '+o.option_text+'\n';}); text+='\nExplanation: '+((q.explanation)||'No explanation'); alert(text); });
$('#questionModal').on('hidden.bs.modal', function(){ $('#questionForm').attr('action','<?php echo !empty($selected_exam) ? site_url('user/content_exam_question_save/'.(int)$selected_exam['id']) : ''; ?>')[0].reset(); });
</script>
