<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('lv_student_practice_time')) {
    function lv_student_practice_time($seconds) {
        $seconds = (int)$seconds;
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    }
}

$user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$summary = isset($analytics['summary']) && is_array($analytics['summary']) ? $analytics['summary'] : [];
$trend = isset($analytics['trend']) && is_array($analytics['trend']) ? $analytics['trend'] : [];
$topics = isset($analytics['topics']) && is_array($analytics['topics']) ? $analytics['topics'] : [];
$attempts = isset($attempts) && is_array($attempts) ? $attempts : [];
?>

<?php include "breadcrumb.php"; ?>

<style>
.student-practice-dashboard .sp-card{background:#fff;border:1px solid #e7ecf4;border-radius:8px;box-shadow:0 8px 22px rgba(31,45,61,.04);}
.student-practice-dashboard .sp-hero{padding:22px 24px;}
.student-practice-dashboard .sp-kicker{color:#727cf5;font-size:12px;font-weight:700;text-transform:uppercase;}
.student-practice-dashboard .sp-title{color:#263238;font-size:24px;font-weight:700;line-height:1.25;margin:4px 0;}
.student-practice-dashboard .sp-metric{padding:16px;}
.student-practice-dashboard .sp-metric small{color:#6c757d;font-weight:600;}
.student-practice-dashboard .sp-metric h3{color:#263238;font-size:24px;font-weight:700;margin:4px 0 0;}
.student-practice-dashboard .sp-trend{align-items:flex-end;border-bottom:1px solid #dbe3ef;display:flex;gap:8px;height:220px;padding:20px 6px 0;}
.student-practice-dashboard .sp-trend-column{flex:1;max-width:54px;min-width:22px;text-align:center;}
.student-practice-dashboard .sp-trend-bar{background:#5369f8;border-radius:8px 8px 0 0;min-height:3px;}
.student-practice-dashboard .sp-trend-label{color:#64748b;font-size:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.student-practice-dashboard .sp-topic-bar{background:#e9edf3;border-radius:999px;height:8px;overflow:hidden;}
.student-practice-dashboard .sp-topic-bar span{background:#f59e0b;border-radius:999px;display:block;height:100%;}
.student-practice-dashboard .sp-empty{background:#f8fafc;border:1px dashed #dbe3ef;border-radius:8px;padding:28px;text-align:center;}
.student-practice-dashboard .sp-delta-up{color:#15803d;}
.student-practice-dashboard .sp-delta-down{color:#b91c1c;}
.student-practice-dashboard .table th{border-top:0;color:#53627c;font-size:12px;font-weight:700;text-transform:uppercase;}
.student-practice-dashboard .table td{color:#53627c;vertical-align:middle;}
</style>

<section class="wish-list-body student-practice-dashboard">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-4 col-sm-12">
                <?php include "profile_menus.php"; ?>
            </div>
            <div class="col-lg-9 col-md-8 col-sm-12">
                <div class="sp-card sp-hero mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start">
                        <div class="mb-2">
                            <div class="sp-kicker">Mock tests & progress</div>
                            <h1 class="sp-title">Practice dashboard</h1>
                            <p class="text-muted mb-0">Track attempts, score trend, weak topics, accuracy, and detailed reports from one place.</p>
                        </div>
                        <a href="<?php echo site_url('mock-tests'); ?>" class="btn btn-primary btn-sm mt-2">Find Mock Tests</a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Attempts</small><h3><?php echo (int)($summary['attempts'] ?? 0); ?></h3></div></div>
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Exams</small><h3><?php echo (int)($summary['exams_attempted'] ?? 0); ?></h3></div></div>
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Average</small><h3><?php echo (float)($summary['average_score'] ?? 0); ?>%</h3></div></div>
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Best</small><h3><?php echo (float)($summary['best_score'] ?? 0); ?>%</h3></div></div>
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Accuracy</small><h3><?php echo (float)($summary['average_accuracy'] ?? 0); ?>%</h3></div></div>
                    <div class="col-lg-2 col-md-4 col-6 mb-2"><div class="sp-card sp-metric h-100"><small>Pass rate</small><h3><?php echo (float)($summary['pass_rate'] ?? 0); ?>%</h3></div></div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="sp-card p-4 h-100">
                            <h5 class="mb-3">Score trend</h5>
                            <?php if (!empty($trend)): ?>
                                <div class="sp-trend">
                                    <?php foreach ($trend as $item): ?>
                                        <?php $score = max(0, min(100, (float)$item['percentage'])); ?>
                                        <div class="sp-trend-column" title="<?php echo html_escape(($item['exam_title'] ?? 'Exam') . ' - ' . $score . '%'); ?>">
                                            <div class="sp-trend-bar" style="height:<?php echo max(3, $score * 1.8); ?>px"></div>
                                            <div class="sp-trend-label"><?php echo $score; ?>%</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-muted small mt-2 mb-0">Each bar is a completed attempt, ordered from oldest to newest.</p>
                            <?php else: ?>
                                <div class="sp-empty text-muted">
                                    <h6 class="mb-1">No score trend yet</h6>
                                    <p class="mb-3">Complete a mock test to start seeing your improvement chart.</p>
                                    <a href="<?php echo site_url('mock-tests'); ?>" class="btn btn-outline-primary btn-sm">Start Practice</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="sp-card p-4 h-100">
                            <h5 class="mb-3">Topics to improve</h5>
                            <?php foreach ($topics as $topic): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between"><strong><?php echo html_escape($topic['topic'] ?? 'Topic'); ?></strong><span><?php echo (float)($topic['accuracy'] ?? 0); ?>%</span></div>
                                    <div class="sp-topic-bar"><span style="width:<?php echo max(0, min(100, (float)($topic['accuracy'] ?? 0))); ?>%"></span></div>
                                    <small class="text-muted"><?php echo (int)($topic['questions'] ?? 0); ?> questions reviewed</small>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($topics)): ?>
                                <p class="text-muted mb-0">Weak-topic analysis appears after completed attempts.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="sp-card p-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Attempt history</h5>
                        <a href="<?php echo site_url('mock-tests'); ?>" class="btn btn-outline-primary btn-sm">Take Another Test</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Exam</th><th>Mode</th><th>Score</th><th>Accuracy</th><th>Change</th><th>Result</th><th>Weak Areas</th><th>Time</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($attempts as $attempt): ?>
                                    <?php
                                        $weak = json_decode((string)($attempt['weak_topics'] ?? '[]'), true);
                                        $weak = is_array($weak) ? $weak : [];
                                        $delta = $attempt['performance_delta'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo html_escape($attempt['exam_title'] ?? 'Exam'); ?></strong><br><small class="text-muted"><?php echo html_escape($attempt['submitted_at'] ?: ($attempt['started_at'] ?? '')); ?></small></td>
                                        <td><?php echo html_escape(ucfirst($attempt['attempt_mode'] ?? 'public')); ?></td>
                                        <td><?php echo $attempt['percentage'] === null ? 'Pending' : (float)$attempt['percentage'] . '%'; ?><br><small><?php echo (float)($attempt['marks_obtained'] ?? 0); ?>/<?php echo (float)($attempt['total_marks'] ?? 0); ?></small></td>
                                        <td><?php echo $attempt['accuracy_percentage'] === null ? '-' : (float)$attempt['accuracy_percentage'] . '%'; ?></td>
                                        <td><?php if ($delta === null): ?>-<?php else: ?><span class="<?php echo (float)$delta >= 0 ? 'sp-delta-up' : 'sp-delta-down'; ?>"><?php echo (float)$delta >= 0 ? '+' : ''; ?><?php echo (float)$delta; ?>%</span><?php endif; ?></td>
                                        <td><span class="badge bg-<?php echo ($attempt['result_status'] === 'pass') ? 'success' : (($attempt['result_status'] === 'fail') ? 'danger' : 'secondary'); ?>"><?php echo html_escape(strtoupper($attempt['result_status'] ?: 'pending')); ?></span></td>
                                        <td><?php echo html_escape(!empty($weak) ? implode(', ', $weak) : ($attempt['improvement_suggestions'] ?? '')); ?></td>
                                        <td><?php echo lv_student_practice_time($attempt['time_taken_seconds'] ?? 0); ?></td>
                                        <td>
                                            <?php if (!empty($attempt['submitted_at'])): ?><a class="btn btn-sm btn-outline-primary mb-1" href="<?php echo site_url('exam/result/' . (int)$attempt['id']); ?>">Report</a><?php endif; ?>
                                            <?php if (!empty($attempt['slug'])): ?><a class="btn btn-sm btn-outline-secondary mb-1" href="<?php echo site_url('mock-tests/' . $attempt['slug']); ?>">Retest</a><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($attempts)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">No practice or mock test attempts yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
