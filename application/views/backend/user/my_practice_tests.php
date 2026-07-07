<?php
defined('BASEPATH') OR exit('No direct script access allowed');
function lv_student_time($seconds){$seconds=(int)$seconds;return floor($seconds/60).'m '.($seconds%60).'s';}
$summary = $analytics['summary'] ?? array();
$trend = $analytics['trend'] ?? array();
$topics = $analytics['topics'] ?? array();
?>
<style>
.student-metric{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:14px;height:100%}.student-metric h3{margin:2px 0 0}.trend-chart{height:220px;display:flex;align-items:flex-end;gap:8px;padding:20px 6px 0;border-bottom:1px solid #cbd5e1}.trend-column{flex:1;min-width:22px;max-width:54px;text-align:center}.trend-bar{background:linear-gradient(180deg,#6366f1,#4338ca);border-radius:8px 8px 0 0;min-height:3px}.trend-label{font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.topic-bar{height:8px;background:#e2e8f0;border-radius:999px;overflow:hidden}.topic-bar span{display:block;height:100%;border-radius:999px;background:#f59e0b}.delta-up{color:#15803d}.delta-down{color:#b91c1c}.attempt-table td,.attempt-table th{vertical-align:middle}
</style>
<div class="row"><div class="col-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-chart-line title_icon"></i> My Practice and Mock Test Progress</h4><p class="text-muted mb-0">Track results, improvement over time, accuracy, and topics that need more practice.</p></div></div></div></div>
<div class="row mb-3">
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Attempts</small><h3><?php echo (int)($summary['attempts'] ?? 0); ?></h3></div></div>
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Exams Practiced</small><h3><?php echo (int)($summary['exams_attempted'] ?? 0); ?></h3></div></div>
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Average Score</small><h3><?php echo (float)($summary['average_score'] ?? 0); ?>%</h3></div></div>
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Best Score</small><h3><?php echo (float)($summary['best_score'] ?? 0); ?>%</h3></div></div>
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Accuracy</small><h3><?php echo (float)($summary['average_accuracy'] ?? 0); ?>%</h3></div></div>
    <div class="col-lg-2 col-md-4 mb-2"><div class="student-metric"><small class="text-muted">Pass Rate</small><h3><?php echo (float)($summary['pass_rate'] ?? 0); ?>%</h3></div></div>
</div>
<div class="row">
    <div class="col-lg-8"><div class="card"><div class="card-body"><h4 class="header-title">Score Trend</h4><?php if(!empty($trend)): ?><div class="trend-chart"><?php foreach($trend as $item): $score=max(0,min(100,(float)$item['percentage'])); ?><div class="trend-column" title="<?php echo html_escape($item['exam_title'].' - '.$score.'%'); ?>"><div class="trend-bar" style="height:<?php echo max(3,$score*1.8); ?>px"></div><div class="trend-label"><?php echo $score; ?>%</div></div><?php endforeach; ?></div><p class="text-muted small mt-2 mb-0">Each bar is a completed attempt, ordered from oldest to newest.</p><?php else: ?><p class="text-muted">Complete a Practice or Mock Test to start your progress chart.</p><?php endif; ?></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h4 class="header-title">Topics to Improve</h4><?php foreach($topics as $topic): ?><div class="mb-3"><div class="d-flex justify-content-between"><b><?php echo html_escape($topic['topic']); ?></b><span><?php echo (float)$topic['accuracy']; ?>%</span></div><div class="topic-bar"><span style="width:<?php echo max(0,min(100,(float)$topic['accuracy'])); ?>%"></span></div><small class="text-muted"><?php echo (int)$topic['questions']; ?> questions reviewed</small></div><?php endforeach; ?><?php if(empty($topics)): ?><p class="text-muted">Weak-topic analysis appears after new snapshot-based attempts are submitted.</p><?php endif; ?></div></div></div>
</div>
<div class="row"><div class="col-12"><div class="card"><div class="card-body"><h4 class="header-title">Attempt History</h4><div class="table-responsive"><table class="table table-striped attempt-table"><thead><tr><th>Exam</th><th>Mode</th><th>Score</th><th>Accuracy</th><th>Change</th><th>Result</th><th>Weak Areas</th><th>Time</th><th>Action</th></tr></thead><tbody>
<?php foreach(($attempts ?? array()) as $attempt): $weak=json_decode((string)($attempt['weak_topics']??'[]'),true);if(!is_array($weak))$weak=array();$delta=$attempt['performance_delta']; ?>
<tr>
    <td><b><?php echo html_escape($attempt['exam_title']); ?></b><br><small class="text-muted"><?php echo html_escape($attempt['submitted_at'] ?: $attempt['started_at']); ?></small></td>
    <td><?php echo html_escape(ucfirst($attempt['attempt_mode'] ?? 'public')); ?></td>
    <td><?php echo $attempt['percentage']===null?'Pending':(float)$attempt['percentage'].'%'; ?><br><small><?php echo (float)$attempt['marks_obtained']; ?>/<?php echo (float)$attempt['total_marks']; ?></small></td>
    <td><?php echo $attempt['accuracy_percentage']===null?'—':(float)$attempt['accuracy_percentage'].'%'; ?></td>
    <td><?php if($delta===null): ?>—<?php else: ?><span class="<?php echo (float)$delta>=0?'delta-up':'delta-down'; ?>"><?php echo (float)$delta>=0?'+':''; ?><?php echo (float)$delta; ?>%</span><?php endif; ?></td>
    <td><span class="badge badge-<?php echo ($attempt['result_status']==='pass')?'success':($attempt['result_status']==='fail'?'danger':'secondary'); ?>"><?php echo html_escape(strtoupper($attempt['result_status'] ?: 'pending')); ?></span></td>
    <td><?php echo html_escape(!empty($weak)?implode(', ',$weak):($attempt['improvement_suggestions'] ?? '')); ?></td>
    <td><?php echo lv_student_time($attempt['time_taken_seconds'] ?? 0); ?></td>
    <td><?php if(!empty($attempt['submitted_at'])): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('exam/result/'.(int)$attempt['id']); ?>">Detailed Report</a><?php endif; ?> <?php if(!empty($attempt['slug'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('mock-tests/'.$attempt['slug']); ?>">Retest</a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php if(empty($attempts)): ?><tr><td colspan="9" class="text-center text-muted">No practice or mock test attempts yet.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div></div>
