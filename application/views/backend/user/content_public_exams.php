<?php defined('BASEPATH') OR exit('No direct script access allowed');
$exams = is_array($exams ?? null) ? $exams : array();
$summary = is_array($summary ?? null) ? $summary : array();
?>
<style>
.metric-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:14px}
.metric-card h3{margin:0}
.tutor-exam-table td,.tutor-exam-table th{vertical-align:middle}
</style>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
          <div>
            <h4 class="page-title"><i class="mdi mdi-clipboard-text-outline title_icon"></i> Exam Pattern Builder</h4>
            <p class="text-muted mb-0">Create tutor-managed exam patterns, assign question-bank pools, and submit them for admin review.</p>
          </div>
          <a class="btn btn-primary mt-2 mt-md-0" href="<?php echo site_url('user/exam_pattern_builder'); ?>">
            <i class="mdi mdi-plus"></i> Create Exam Pattern
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row mb-3">
  <div class="col-md-4 mb-2"><div class="metric-card"><small class="text-muted">Your Exam Patterns</small><h3><?php echo (int)($summary['total_exams'] ?? 0); ?></h3></div></div>
  <div class="col-md-4 mb-2"><div class="metric-card"><small class="text-muted">Published Exams</small><h3><?php echo (int)($summary['published_exams'] ?? 0); ?></h3></div></div>
  <div class="col-md-4 mb-2"><div class="metric-card"><small class="text-muted">Total Attempts</small><h3><?php echo (int)($summary['total_attempts'] ?? 0); ?></h3></div></div>
</div>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="header-title">Your Exam Patterns</h4>
        <div class="table-responsive">
          <table class="table table-striped table-centered tutor-exam-table">
            <thead>
              <tr><th>Exam</th><th>Type</th><th>Status</th><th>Questions</th><th>Marks / Time</th><th>Attempts</th><th>Updated</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if(empty($exams)): ?>
              <tr><td colspan="8" class="text-center text-muted">No exam patterns found.</td></tr>
            <?php else: foreach($exams as $e): ?>
              <tr>
                <td><b><?php echo html_escape($e['title']); ?></b><br><small class="text-muted"><?php echo html_escape($e['slug']); ?></small></td>
                <td><?php echo html_escape($e['exam_type'] ?? 'General'); ?></td>
                <td><span class="badge badge-<?php echo ($e['status']==='published')?'success':(($e['status']==='archived')?'dark':'secondary'); ?>"><?php echo html_escape(ucfirst($e['status'])); ?></span></td>
                <td><?php echo (int)$e['question_count']; ?></td>
                <td><?php echo (float)$e['total_marks']; ?> marks<br><small><?php echo (int)$e['time_limit_minutes']; ?> minutes</small></td>
                <td><?php echo (int)($e['attempt_count'] ?? 0); ?><br><small><?php echo round((float)($e['avg_score'] ?? 0),2); ?>% avg</small></td>
                <td><?php echo html_escape($e['updated_at']); ?></td>
                <td style="min-width:220px">
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('user/exam_pattern_builder/'.(int)$e['id']); ?>">Edit Pattern</a>
                  <?php if(!empty($e['slug']) && $e['status'] === 'published'): ?>
                    <a target="_blank" class="btn btn-sm btn-outline-info" href="<?php echo site_url('mock-tests/'.$e['slug']); ?>">Preview</a>
                  <?php endif; ?>
                  <form method="post" action="<?php echo site_url('user/exam_pattern_archive/'.(int)$e['id']); ?>" class="d-inline">
                    <input type="hidden" name="workflow_action_token" value="<?php echo html_escape($workflow_action_token ?? ''); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this exam?');">Archive</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
