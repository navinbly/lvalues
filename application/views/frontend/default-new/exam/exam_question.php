<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$multi = ($question['question_type'] ?? 'single') === 'multiple';
$answered = array_flip(array_map('intval', $answered_question_ids ?? []));
$review = array_flip(array_map('intval', $review_question_ids ?? []));
$sections = [];
$currentSectionKey = '';
foreach (($questions ?? []) as $position => $item) {
    $key = (string)($item['section_id'] ?? 0) . ':' . (string)($item['section_title'] ?? 'General');
    if (!isset($sections[$key])) {
        $sections[$key] = [
            'title' => (string)($item['section_title'] ?? 'General'),
            'time' => (int)($item['section_time_minutes'] ?? 0),
            'questions' => [],
            'start_position' => $position,
            'end_position' => $position,
        ];
    }
    $sections[$key]['questions'][] = ['position' => $position, 'id' => (int)$item['id']];
    $sections[$key]['end_position'] = $position;
    if ((int)$position === (int)$index) $currentSectionKey = $key;
}
$currentSection = $sections[$currentSectionKey] ?? reset($sections);
$nextSectionStart = null;
$currentFound = false;
foreach ($sections as $key => $section) {
    if ($currentFound) { $nextSectionStart = (int)$section['start_position']; break; }
    if ($key === $currentSectionKey) $currentFound = true;
}
$sectionSecondsLeft = 0;
if (!empty($attempt['started_at']) && !empty($currentSection['time'])) {
    $elapsedBefore = 0;
    foreach ($sections as $key => $section) {
        if ($key === $currentSectionKey) break;
        $elapsedBefore += max(0, (int)($section['time'] ?? 0)) * 60;
    }
    $sectionEnd = strtotime($attempt['started_at']) + $elapsedBefore + ((int)$currentSection['time'] * 60);
    $sectionSecondsLeft = max(0, $sectionEnd - time());
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
        body{margin:0;background:#eaf0f7;color:#162033;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55}
        a{text-decoration:none}
        .exam-shell{max-width:1240px;margin:0 auto;padding:20px 16px 34px}
        .top-panel{background:#fff;border:1px solid #d9e4ef;border-radius:18px;box-shadow:0 16px 42px rgba(15,23,42,.08);overflow:hidden;margin-bottom:16px}
        .top-main{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:start;padding:20px 22px;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%)}
        h1{margin:0 0 8px;color:#0f172a;font-size:24px;line-height:1.2;font-weight:950}
        .mode-badge{display:inline-flex;margin-left:8px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:900;vertical-align:middle}
        .meta{color:#64748b;font-size:14px}
        .timer-wrap{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .timer-card{min-width:150px;border:1px solid #fecaca;background:#fff7f7;border-radius:14px;padding:11px 14px}
        .timer-card.section{border-color:#bbf7d0;background:#f0fdf4}
        .timer-label{display:block;color:#64748b;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em}
        .timer{display:block;margin-top:4px;color:#b91c1c;font-size:25px;font-weight:950;line-height:1}
        .timer-card.section .timer{color:#15803d}
        .section-strip{display:flex;gap:8px;flex-wrap:wrap;padding:12px 22px;border-top:1px solid #e6edf5;background:#fbfdff}
        .section-pill{border:1px solid #d4deea;border-radius:999px;background:#fff;color:#475569;padding:7px 11px;font-size:12px;font-weight:900}
        .section-pill.active{border-color:#93c5fd;background:#eff6ff;color:#1d4ed8}
        .progress-track{height:8px;background:#dfe8f3}
        .progress-fill{height:100%;background:#2563eb}
        .exam-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:18px;align-items:start}
        .paper,.navigator{background:#fff;border:1px solid #d9e4ef;border-radius:18px;box-shadow:0 16px 42px rgba(15,23,42,.08);overflow:hidden}
        .paper-head{display:flex;justify-content:space-between;gap:14px;align-items:center;padding:17px 20px;border-bottom:1px solid #e6edf5;background:#fbfdff}
        .paper-head b{color:#0f172a;font-size:16px}
        .question-meta{color:#64748b;font-size:13px;text-align:right}
        .paper-body{padding:26px 26px 18px}
        .question-box{border:1px solid #e3ebf4;background:#fbfdff;border-radius:16px;padding:20px;margin-bottom:18px}
        .question-label{display:block;color:#1d4ed8;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px}
        .question-text{color:#0f172a;font-size:18px;line-height:1.7;font-weight:900}
        .option-list{display:grid;gap:11px}
        .option-item{display:grid;grid-template-columns:auto 1fr;gap:13px;align-items:start;border:1px solid #d8e2ee;border-radius:14px;padding:14px 15px;background:#fff;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s}
        .option-item:hover{border-color:#93c5fd;background:#f8fbff;box-shadow:0 8px 18px rgba(37,99,235,.08)}
        .option-item input{margin-top:5px}
        .option-item span{color:#111827;line-height:1.6}
        .option-correct{border-color:#22c55e;background:#f0fdf4}
        .option-wrong{border-color:#ef4444;background:#fff1f2}
        .feedback{margin-top:16px;border-radius:14px;padding:13px 15px;border:1px solid #fde68a;background:#fffbeb;color:#7c2d12}
        .feedback.ok{border-color:#bbf7d0;background:#f0fdf4;color:#166534}
        .save-state{font-size:12px;color:#64748b;margin-top:12px}
        .action-bar{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:17px 20px;border-top:1px solid #e6edf5;background:#fbfdff}
        .action-right{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}
        .btn{appearance:none;border:1px solid transparent;border-radius:10px;padding:10px 14px;font-weight:900;cursor:pointer;font-size:14px}
        .btn:disabled{opacity:.45;cursor:not-allowed}
        .btn-light{background:#fff;color:#334155;border-color:#cbd5e1}.btn-light:hover{background:#f8fafc}
        .btn-primary{background:#2563eb;color:#fff}.btn-primary:hover{background:#1d4ed8}
        .btn-success{background:#16a34a;color:#fff}.btn-success:hover{background:#15803d}
        .btn-warning{background:#f59e0b;color:#111827}.btn-warning:hover{background:#d97706}
        .btn-outline{background:#fff;color:#334155;border-color:#cbd5e1}.btn-outline:hover{border-color:#2563eb;color:#2563eb}
        .navigator{position:sticky;top:18px}
        .navigator-head{padding:18px;border-bottom:1px solid #e6edf5;background:#fbfdff}
        .navigator-head h2{margin:0 0 10px;color:#0f172a;font-size:17px;font-weight:950}
        .legend{display:flex;gap:10px;flex-wrap:wrap;color:#64748b;font-size:12px}
        .legend span{display:inline-flex;align-items:center;gap:5px}
        .dot{width:12px;height:12px;border-radius:4px;border:1px solid #cbd5e1;background:#fff}.dot.ans{background:#dcfce7;border-color:#86efac}.dot.rev{background:#ffedd5;border-color:#fdba74}
        .navigator-body{padding:18px}
        .nav-section-title{margin:13px 0 8px;color:#475569;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.06em}
        .nav-section-title:first-child{margin-top:0}
        .question-grid{display:flex;flex-wrap:wrap;gap:8px}
        .question-link{width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #cbd5e1;color:#475569;background:#fff;font-weight:950}
        .question-link:hover{border-color:#2563eb;color:#2563eb}
        .question-link.answered{background:#dcfce7;border-color:#86efac;color:#166534}
        .question-link.review{background:#ffedd5;border-color:#fdba74;color:#9a3412}
        .question-link.current{background:#2563eb;border-color:#2563eb;color:#fff}
        .answered-count{border-top:1px solid #e6edf5;margin-top:16px;padding-top:14px;color:#475569;font-size:13px;font-weight:900}
        @media(max-width:980px){.top-main,.exam-grid{grid-template-columns:1fr}.timer-wrap{justify-content:flex-start}.navigator{position:static}.question-meta{text-align:left}.paper-head{align-items:flex-start}.action-right{justify-content:flex-start}}
        @media(max-width:560px){.exam-shell{padding:12px 8px 26px}.top-main,.paper-body,.action-bar,.navigator-body{padding:16px}.paper-head{display:block}.question-text{font-size:16px}.timer-card{min-width:132px}.btn{width:100%}.action-right{width:100%}}
    </style>
</head>
<body>
<main class="exam-shell">
    <section class="top-panel">
        <div class="top-main">
            <div>
                <h1><?php echo html_escape($exam['title']); ?><span class="mode-badge"><?php echo html_escape($is_practice ? 'Practice Mode' : ucfirst($attempt['attempt_mode'] ?? 'Mock') . ' Test'); ?></span></h1>
                <div class="meta">Question <?php echo (int)$index + 1; ?> of <?php echo (int)$total; ?> · Section: <strong><?php echo html_escape($currentSection['title'] ?? 'General'); ?></strong></div>
            </div>
            <div class="timer-wrap">
                <?php if($is_timed): ?><div class="timer-card"><span class="timer-label">Total time left</span><span class="timer" id="timer"></span></div><?php endif; ?>
                <?php if(!empty($currentSection['time'])): ?><div class="timer-card section"><span class="timer-label">Section time left</span><span class="timer" id="sectionTimer"></span></div><?php endif; ?>
                <?php if(!$is_timed): ?><div class="timer-card section"><span class="timer-label">Mode</span><span class="timer">Untimed</span></div><?php endif; ?>
            </div>
        </div>
        <div class="section-strip">
            <?php foreach($sections as $key=>$section): ?>
                <span class="section-pill <?php echo $key === $currentSectionKey ? 'active' : ''; ?>"><?php echo html_escape($section['title']); ?><?php if(!empty($section['time'])): ?> · <?php echo (int)$section['time']; ?> min<?php endif; ?></span>
            <?php endforeach; ?>
        </div>
        <div class="progress-track"><div class="progress-fill" style="width:<?php echo round((($index + 1) / max(1, $total)) * 100); ?>%"></div></div>
    </section>

    <div class="exam-grid">
        <section class="paper">
            <div class="paper-head">
                <b>Question <?php echo (int)$index + 1; ?></b>
                <div class="question-meta">Marks: <?php echo html_escape($question['marks']); ?><?php if((float)($question['negative_marks'] ?? 0) > 0): ?> · Negative: <?php echo html_escape($question['negative_marks']); ?><?php endif; ?> · <?php echo $multi ? 'Multiple choice' : 'Single choice'; ?></div>
            </div>
            <form method="post" id="examForm">
                <div class="paper-body">
                    <input type="hidden" name="question_id" value="<?php echo (int)$question['id']; ?>">
                    <input type="hidden" name="question_index" value="<?php echo (int)$index; ?>">
                    <div class="question-box">
                        <span class="question-label">Read carefully</span>
                        <div class="question-text"><?php echo nl2br(html_escape($question['question_text'])); ?></div>
                    </div>
                    <div class="option-list">
                        <?php foreach(($question['options'] ?? []) as $opt): ?>
                            <?php
                                $optionId = (int)$opt['id'];
                                $feedbackClass = '';
                                if(!empty($feedback['ok']) && $is_practice) {
                                    if(in_array($optionId, array_map('intval', $feedback['correct_option_ids'] ?? []), true)) $feedbackClass = ' option-correct';
                                    elseif(in_array($optionId, array_map('intval', $picked_option_ids ?? []), true)) $feedbackClass = ' option-wrong';
                                }
                            ?>
                            <label class="option-item<?php echo $feedbackClass; ?>">
                                <input type="<?php echo $multi ? 'checkbox' : 'radio'; ?>" name="option_ids[]" value="<?php echo $optionId; ?>" <?php echo in_array($optionId, $picked_option_ids ?? [], true) ? 'checked' : ''; ?>>
                                <span><?php echo html_escape($opt['option_text']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if(!empty($feedback['ok']) && $is_practice): ?>
                        <div class="feedback <?php echo !empty($feedback['is_correct']) ? 'ok' : ''; ?>">
                            <strong><?php echo !empty($feedback['is_correct']) ? 'Correct.' : 'Review this answer.'; ?></strong>
                            <?php if(!empty($feedback['explanation'])): ?><div><?php echo nl2br(html_escape($feedback['explanation'])); ?></div><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="save-state" id="saveState"><?php echo !empty($attempt['autosaved_at']) ? 'Progress autosaved' : 'Autosave ready'; ?></div>
                </div>
                <div class="action-bar">
                    <button class="btn btn-light" name="nav_action" value="back" <?php echo $index <= 0 ? 'disabled' : ''; ?>>Back</button>
                    <div class="action-right">
                        <?php if($is_practice): ?><button class="btn btn-outline" name="nav_action" value="check">Check Answer</button><?php endif; ?>
                        <button class="btn btn-outline" name="nav_action" value="clear">Clear</button>
                        <button class="btn btn-warning" name="nav_action" value="review">Mark for Review</button>
                        <?php if($index < $total - 1): ?><button class="btn btn-primary" name="nav_action" value="next">Save &amp; Next</button><?php endif; ?>
                        <button class="btn btn-success" name="nav_action" value="submit" onclick="return confirm('Submit this attempt now?');">Submit Test</button>
                    </div>
                </div>
            </form>
        </section>

        <aside class="navigator">
            <div class="navigator-head">
                <h2>Question Navigator</h2>
                <div class="legend"><span><i class="dot"></i>Not answered</span><span><i class="dot ans"></i>Answered</span><span><i class="dot rev"></i>Review</span></div>
            </div>
            <div class="navigator-body">
                <?php foreach($sections as $section): ?>
                    <div class="nav-section-title"><?php echo html_escape($section['title']); ?></div>
                    <div class="question-grid">
                        <?php foreach($section['questions'] as $nav): ?>
                            <?php
                                $classes = 'question-link';
                                if(isset($answered[$nav['id']])) $classes .= ' answered';
                                if(isset($review[$nav['id']])) $classes .= ' review';
                                if((int)$nav['position'] === (int)$index) $classes .= ' current';
                            ?>
                            <a class="<?php echo $classes; ?>" href="<?php echo html_escape($question_url_base . (int)$nav['position']); ?>"><?php echo (int)$nav['position'] + 1; ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="answered-count"><?php echo count($answered); ?> of <?php echo (int)$total; ?> answered</div>
            </div>
        </aside>
    </div>
</main>
<script>
(function(){
    var form = document.getElementById('examForm'), saveState = document.getElementById('saveState');
    var autosaveUrl = <?php echo json_encode((string)$autosave_url); ?>, submitting = false;
    function autosave(){
        if(!autosaveUrl || submitting) return;
        saveState.textContent = 'Saving...';
        fetch(autosaveUrl,{method:'POST',body:new FormData(form),credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(r){saveState.textContent = r.ok ? 'Saved at ' + r.saved_at : 'Save failed';})
            .catch(function(){saveState.textContent = 'Save failed';});
    }
    form.querySelectorAll('input[name="option_ids[]"]').forEach(function(input){input.addEventListener('change', autosave);});
    form.addEventListener('submit', function(){submitting = true; window.onbeforeunload = null;});
    window.onbeforeunload = function(){if(!submitting) return 'Your attempt is still in progress.';};
    function fmt(left){var m = Math.floor(left / 60), s = left % 60; return m + ':' + String(s).padStart(2,'0');}
    <?php if($is_timed): ?>var left = <?php echo (int)$seconds_left; ?>, timer = document.getElementById('timer'); function tick(){timer.textContent = fmt(left); if(left <= 0){submitting = true; window.onbeforeunload = null; var a = document.createElement('input'); a.type = 'hidden'; a.name = 'nav_action'; a.value = 'submit'; form.appendChild(a); form.submit(); return;} left--;} setInterval(tick,1000); tick();<?php endif; ?>
    <?php if(!empty($currentSection['time'])): ?>var sectionLeft = <?php echo (int)$sectionSecondsLeft; ?>, sectionTimer = document.getElementById('sectionTimer'), nextUrl = <?php echo json_encode($nextSectionStart === null ? '' : $question_url_base . (int)$nextSectionStart); ?>; function tickSection(){sectionTimer.textContent = fmt(sectionLeft); if(sectionLeft <= 0){submitting = true; window.onbeforeunload = null; if(nextUrl){window.location.href = nextUrl;} else {var a = document.createElement('input'); a.type = 'hidden'; a.name = 'nav_action'; a.value = 'submit'; form.appendChild(a); form.submit();} return;} sectionLeft--;} setInterval(tickSection,1000); tickSection();<?php endif; ?>
})();
</script>
</body>
</html>
