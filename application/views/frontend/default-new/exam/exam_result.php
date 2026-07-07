<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$totalQuestions = (int)($attempt['total_questions'] ?? 0);
$attemptedQuestions = (int)($attempt['attempted_questions'] ?? 0);
$correctAnswers = (int)($attempt['correct_answers'] ?? 0);
$skipped = (int)($attempt['skipped_questions'] ?? max(0, $totalQuestions - $attemptedQuestions));
$wrongAnswers = max(0, $attemptedQuestions - $correctAnswers);
$score = (float)($attempt['percentage'] ?? 0);
$accuracy = (float)($attempt['accuracy_percentage'] ?? 0);
$marksObtained = (float)($attempt['marks_obtained'] ?? 0);
$totalMarks = (float)($attempt['total_marks'] ?? 0);
$weakTopics = json_decode((string)($attempt['weak_topics'] ?? '[]'), true);
if (!is_array($weakTopics)) $weakTopics = array();
$insights = $report['insights'] ?? array();
$comparison = $report['comparison'] ?? array();
$hideDetailedResult = (($attempt['attempt_mode'] ?? '') === 'real' && empty($exam['show_result_immediately']));
$passed = strtolower((string)($attempt['result_status'] ?? '')) === 'pass';
$statusLabel = $attempt['result_status'] ? strtoupper((string)$attempt['result_status']) : 'COMPLETED';

if (!function_exists('lv_exam_time_label')) {
    function lv_exam_time_label($seconds) {
        $seconds = max(0, (int)$seconds);
        $minutes = floor($seconds / 60);
        return $minutes . 'm ' . ($seconds % 60) . 's';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo html_escape($page_title); ?></title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef3f8;color:#162033;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55}
        a{text-decoration:none}
        .result-page{max-width:1180px;margin:0 auto;padding:30px 16px 46px}
        .hero{background:#fff;border:1px solid #dce5f0;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.08);overflow:hidden}
        .hero-main{display:grid;grid-template-columns:minmax(0,1fr) 240px;gap:22px;padding:28px;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%)}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;border:1px solid #c7d8ff;background:#eef5ff;color:#1d4ed8;border-radius:999px;padding:6px 11px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
        h1{margin:14px 0 8px;color:#0f172a;font-size:32px;line-height:1.18;font-weight:900}
        .meta{color:#617188;font-size:14px}
        .status-card{border-radius:16px;padding:18px;border:1px solid <?php echo $passed ? '#bbf7d0' : '#fecaca'; ?>;background:<?php echo $passed ? '#f0fdf4' : '#fff5f5'; ?>;text-align:center;align-self:center}
        .status-label{font-size:13px;color:#617188;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
        .status-value{font-size:34px;line-height:1.1;font-weight:950;color:<?php echo $passed ? '#15803d' : '#b91c1c'; ?>;margin-top:7px}
        .score-ring{width:118px;height:118px;margin:14px auto 0;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#2563eb <?php echo max(0,min(100,$score)); ?>%,#dbe6f3 0)}
        .score-ring-inner{width:88px;height:88px;border-radius:50%;display:grid;place-items:center;background:#fff;color:#0f172a;font-size:24px;font-weight:950}
        .summary-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1px;background:#dce5f0;border-top:1px solid #dce5f0}
        .summary-cell{background:#fff;padding:17px 18px}
        .summary-cell span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
        .summary-cell b{display:block;margin-top:5px;color:#0f172a;font-size:23px;line-height:1;font-weight:950}
        .section{margin-top:18px;background:#fff;border:1px solid #dce5f0;border-radius:18px;box-shadow:0 12px 32px rgba(15,23,42,.06)}
        .section-head{padding:20px 22px;border-bottom:1px solid #e6edf5;display:flex;justify-content:space-between;gap:12px;align-items:center}
        .section-head h2{margin:0;color:#0f172a;font-size:20px;font-weight:900}
        .section-body{padding:22px}
        .insight-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
        .mini-card{border:1px solid #e1e9f2;border-radius:14px;padding:15px;background:#fbfdff}
        .mini-card span{display:block;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
        .mini-card b{display:block;margin-top:6px;color:#0f172a;font-size:22px;font-weight:950}
        .trend-up{color:#15803d!important}.trend-down{color:#b91c1c!important}
        .next-step{margin-top:16px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;border-radius:14px;padding:14px 16px}
        .weak-pill{display:inline-flex;border:1px solid #fdba74;background:#fff7ed;color:#9a3412;border-radius:999px;padding:6px 11px;margin:5px 5px 0 0;font-size:13px;font-weight:800}
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px}
        .performance-row{margin-bottom:16px}
        .performance-top{display:flex;justify-content:space-between;gap:12px;font-weight:900;color:#0f172a}
        .performance-meta{color:#64748b;font-size:13px;margin-top:5px}
        .bar{height:10px;background:#e4ebf4;border-radius:999px;overflow:hidden;margin-top:8px}
        .bar span{display:block;height:100%;border-radius:999px;background:#2563eb}
        .bar.topic span{background:#16a34a}
        .answer-card{border:1px solid #dce5f0;border-left:6px solid #94a3b8;border-radius:14px;background:#fff;margin-bottom:14px;overflow:hidden}
        .answer-card.ok{border-left-color:#22c55e}.answer-card.bad{border-left-color:#ef4444}
        .answer-head{padding:16px 18px;background:#fbfdff;border-bottom:1px solid #e6edf5;display:flex;justify-content:space-between;gap:14px}
        .answer-head b{color:#0f172a}.answer-body{padding:16px 18px}
        .question-meta{color:#64748b;font-size:12px;margin-top:5px}
        .qa-line{margin:8px 0}.qa-line strong{color:#0f172a}
        .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:11px 16px;font-weight:900;border:1px solid transparent}
        .btn-primary{background:#2563eb;color:#fff}.btn-primary:hover{background:#1d4ed8;color:#fff}
        .btn-secondary{background:#fff;color:#334155;border-color:#cbd5e1}.btn-secondary:hover{background:#f8fafc;color:#0f172a}
        .notice{border:1px solid #bae6fd;background:#f0f9ff;color:#075985;border-radius:14px;padding:16px}
        @media(max-width:900px){.hero-main,.two-col{grid-template-columns:1fr}.summary-strip,.insight-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.status-card{text-align:left}.score-ring{margin-left:0}}
        @media(max-width:560px){.result-page{padding:20px 10px}.hero-main,.section-body{padding:18px}.summary-strip,.insight-grid{grid-template-columns:1fr}h1{font-size:26px}.answer-head{display:block}}
    </style>
</head>
<body>
<main class="result-page">
    <section class="hero">
        <div class="hero-main">
            <div>
                <span class="eyebrow"><?php echo html_escape(ucfirst($attempt['attempt_mode'] ?? 'public')); ?> result</span>
                <h1><?php echo html_escape($exam['title']); ?></h1>
                <div class="meta">
                    Submitted <?php echo html_escape($attempt['submitted_at'] ?? ''); ?> · Time taken <?php echo lv_exam_time_label($attempt['time_taken_seconds'] ?? 0); ?>
                </div>
            </div>
            <div class="status-card">
                <div class="status-label">Final Status</div>
                <div class="status-value"><?php echo html_escape($statusLabel); ?></div>
                <?php if(!$hideDetailedResult): ?>
                    <div class="score-ring"><div class="score-ring-inner"><?php echo round($score, 2); ?>%</div></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!$hideDetailedResult): ?>
            <div class="summary-strip">
                <div class="summary-cell"><span>Total</span><b><?php echo $totalQuestions; ?></b></div>
                <div class="summary-cell"><span>Attempted</span><b><?php echo $attemptedQuestions; ?></b></div>
                <div class="summary-cell"><span>Correct</span><b><?php echo $correctAnswers; ?></b></div>
                <div class="summary-cell"><span>Wrong</span><b><?php echo $wrongAnswers; ?></b></div>
                <div class="summary-cell"><span>Skipped</span><b><?php echo $skipped; ?></b></div>
                <div class="summary-cell"><span>Accuracy</span><b><?php echo round($accuracy, 2); ?>%</b></div>
                <div class="summary-cell"><span>Marks</span><b><?php echo $marksObtained; ?>/<?php echo $totalMarks; ?></b></div>
                <div class="summary-cell"><span>Exam Average</span><b><?php echo round((float)($comparison['cohort_average'] ?? 0), 2); ?>%</b></div>
            </div>
        <?php endif; ?>
    </section>

    <?php if($hideDetailedResult): ?>
        <section class="section"><div class="section-body"><div class="notice"><strong>Test submitted successfully.</strong><br>This is a real test. Detailed marks, answers and explanation will be visible after the result is published.</div></div></section>
    <?php else: ?>
        <section class="section">
            <div class="section-head"><h2>Performance Summary</h2></div>
            <div class="section-body">
                <div class="insight-grid">
                    <div class="mini-card"><span>Score</span><b><?php echo round($score, 2); ?>%</b></div>
                    <div class="mini-card"><span>Accuracy</span><b><?php echo round($accuracy, 2); ?>%</b></div>
                    <div class="mini-card"><span>Change from Previous</span><b class="<?php echo ($comparison['delta'] ?? 0) >= 0 ? 'trend-up' : 'trend-down'; ?>"><?php echo ($comparison['delta'] ?? null) === null ? 'First attempt' : (((float)$comparison['delta'] >= 0 ? '+' : '') . round((float)$comparison['delta'], 2) . '%'); ?></b></div>
                </div>
                <div class="next-step"><strong>Next step:</strong> <?php echo html_escape($attempt['improvement_suggestions'] ?: 'Keep practicing to improve speed and accuracy.'); ?></div>
                <?php if(!empty($weakTopics)): ?>
                    <div style="margin-top:14px"><strong>Priority topics:</strong><br><?php foreach($weakTopics as $topic): ?><span class="weak-pill"><?php echo html_escape($topic); ?></span><?php endforeach; ?></div>
                <?php endif; ?>
            </div>
        </section>

        <?php if(!empty($insights['section']) || !empty($insights['topic'])): ?>
            <div class="two-col">
                <section class="section" style="margin-top:0">
                    <div class="section-head"><h2>Section Performance</h2></div>
                    <div class="section-body">
                        <?php foreach(($insights['section'] ?? array()) as $row): ?>
                            <div class="performance-row">
                                <div class="performance-top"><span><?php echo html_escape($row['dimension_label']); ?></span><span><?php echo round((float)$row['score_percentage'], 2); ?>%</span></div>
                                <div class="bar"><span style="width:<?php echo max(0,min(100,(float)$row['score_percentage'])); ?>%"></span></div>
                                <div class="performance-meta"><?php echo (int)$row['correct_answers']; ?>/<?php echo (int)$row['attempted_questions']; ?> correct · <?php echo (int)$row['skipped_questions']; ?> skipped</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="section" style="margin-top:0">
                    <div class="section-head"><h2>Topic Performance</h2></div>
                    <div class="section-body">
                        <?php foreach(($insights['topic'] ?? array()) as $row): ?>
                            <div class="performance-row">
                                <div class="performance-top"><span><?php echo html_escape($row['dimension_label']); ?></span><span><?php echo round((float)$row['accuracy_percentage'], 2); ?>%</span></div>
                                <div class="bar topic"><span style="width:<?php echo max(0,min(100,(float)$row['accuracy_percentage'])); ?>%;background:<?php echo (float)$row['accuracy_percentage'] < 50 ? '#f59e0b' : '#16a34a'; ?>"></span></div>
                                <div class="performance-meta">Score <?php echo round((float)$row['score_percentage'], 2); ?>% · <?php echo (int)$row['total_questions']; ?> questions</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>

        <?php if(!empty($exam['show_correct_answers']) || ($attempt['attempt_mode'] ?? '') === 'practice'): ?>
            <section class="section">
                <div class="section-head"><h2>Answer Review</h2></div>
                <div class="section-body">
                    <?php foreach(($report['items'] ?? array()) as $index=>$item): ?>
                        <article class="answer-card <?php echo !empty($item['is_correct']) ? 'ok' : 'bad'; ?>">
                            <div class="answer-head">
                                <div>
                                    <b>Q<?php echo $index + 1; ?>. <?php echo nl2br(html_escape($item['question'])); ?></b>
                                    <div class="question-meta"><?php echo html_escape($item['section']); ?> · <?php echo html_escape($item['topic'] ?: 'General'); ?> · <?php echo html_escape($item['difficulty']); ?></div>
                                </div>
                                <strong><?php echo (float)$item['marks_awarded']; ?>/<?php echo (float)$item['marks']; ?> marks</strong>
                            </div>
                            <div class="answer-body">
                                <div class="qa-line"><strong>Your answer:</strong> <?php echo html_escape($item['your_answer'] ?: 'Not answered'); ?></div>
                                <div class="qa-line"><strong>Correct answer:</strong> <?php echo html_escape($item['correct_answer']); ?></div>
                                <?php if((!empty($exam['show_explanations']) || ($attempt['attempt_mode'] ?? '') === 'practice') && !empty($item['explanation'])): ?>
                                    <div class="qa-line"><strong>Explanation:</strong> <?php echo nl2br(html_escape($item['explanation'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <div class="actions">
        <a href="<?php echo site_url('mock-tests'); ?>" class="btn btn-primary">Find Another Test</a>
        <?php if($this->session->userdata('user_login')): ?>
            <a href="<?php echo site_url('home/my_practice_tests'); ?>" class="btn btn-secondary">My Progress Dashboard</a>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
