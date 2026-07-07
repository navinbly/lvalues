<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = trim((string)($exam['title'] ?? 'Mock Test'));
$description = trim((string)($exam['description'] ?? ''));
$time = (int)($exam['time_limit_minutes'] ?? 0);
$questions = (int)($exam['question_limit'] ?: ($exam['question_count'] ?? 0));
$passing = $exam['passing_marks'] ?? '';
$difficulty = $exam['difficulty'] ?? 'Beginner';
$instructions = trim((string)(($exam['instructions'] ?? '') ?: ($exam['policy'] ?? '')));
?>
<style>
    .lv-exam-detail{background:#f6f8fc;padding:34px 0 64px;color:#152238;}
    .lv-exam-container{max-width:1180px;margin:0 auto;padding:0 15px;}
    .lv-exam-breadcrumb{font-size:13px;margin-bottom:16px;color:#64748b;}
    .lv-exam-breadcrumb a{color:#2563eb;font-weight:700;text-decoration:none;}
    .lv-exam-paper{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 18px 46px rgba(15,23,42,.08);overflow:hidden;}
    .lv-exam-paper-head{background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);border-bottom:1px solid #e2e8f0;padding:28px 32px;}
    .lv-tag{display:inline-flex;align-items:center;gap:7px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:800;margin-bottom:14px;}
    .lv-exam-paper h1{font-size:34px;line-height:1.18;margin:0 0 10px;font-weight:900;color:#0f172a;letter-spacing:-.4px;}
    .lv-exam-subtitle{font-size:15px;line-height:1.8;color:#475569;margin:0;max-width:760px;}
    .lv-stat-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:22px;}
    .lv-stat-box{border:1px solid #dbe4f0;background:#fff;border-radius:14px;padding:14px 16px;}
    .lv-stat-box b{display:block;font-size:22px;line-height:1;color:#0f172a;font-weight:900;}
    .lv-stat-box span{display:block;margin-top:7px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:800;}
    .lv-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:22px;margin-top:22px;}
    .lv-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 10px 28px rgba(15,23,42,.05);padding:24px;height:100%;}
    .lv-card h2,.lv-card h3{font-weight:900;color:#0f172a;letter-spacing:-.25px;}
    .lv-card-title{font-size:20px;margin:0 0 14px;}
    .lv-instruction-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;white-space:pre-line;color:#334155;line-height:1.75;}
    .lv-instruction-list{padding-left:20px;margin:0;color:#334155;line-height:1.85;}
    .lv-start-card{position:sticky;top:88px;}
    .lv-start-card .lv-exam-checklist{border:1px solid #dbe4f0;background:#f8fafc;border-radius:14px;padding:13px 14px;margin-bottom:16px;color:#334155;font-size:13px;line-height:1.7;}
    .lv-start-card .lv-exam-checklist b{display:block;color:#0f172a;margin-bottom:4px;}
    .lv-start-card .form-group{margin:0 0 12px;}
    .lv-form-control{height:46px;border:1px solid #cfd9e7;border-radius:10px;padding:10px 13px;width:100%;background:#fff;color:#0f172a;}
    .lv-form-control:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.10);}
    .lv-mode-card{display:block;border:1px solid #dbe4f0;border-radius:14px;padding:13px;background:#fff;cursor:pointer;height:100%;transition:.15s;}
    .lv-mode-card:hover{border-color:#93c5fd;background:#f8fbff;}
    .lv-mode-card input{margin-right:8px;}
    .lv-mode-card strong{color:#0f172a;}
    .lv-mode-card small{display:block;margin-top:5px;color:#64748b;line-height:1.45;}
    .lv-mode-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
    .lv-start-btn{border:0;width:100%;border-radius:12px;background:#2563eb;color:#fff;padding:14px 18px;font-weight:900;font-size:15px;box-shadow:0 10px 22px rgba(37,99,235,.20);}
    .lv-start-btn:hover{background:#1d4ed8;}
    .lv-back-link{display:block;text-align:center;margin-top:12px;color:#2563eb;font-weight:800;text-decoration:none;}
    .lv-features{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:18px;}
    .lv-feature{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px;}
    .lv-feature b{display:block;color:#0f172a;margin-bottom:6px;font-weight:900;}
    .lv-feature span{font-size:13px;color:#64748b;line-height:1.55;}
    .lv-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;color:#7c2d12;padding:13px 15px;font-size:13px;line-height:1.6;margin-top:16px;}
    @media(max-width:991px){.lv-layout{grid-template-columns:1fr}.lv-start-card{position:static}.lv-stat-row{grid-template-columns:repeat(2,1fr)}.lv-features{grid-template-columns:1fr}.lv-exam-paper-head{padding:24px 20px}.lv-exam-paper h1{font-size:29px}}
    @media(max-width:575px){.lv-stat-row,.lv-mode-grid{grid-template-columns:1fr}.lv-card{padding:18px}.lv-exam-detail{padding-top:24px}}
</style>

<section class="lv-exam-detail">
    <div class="lv-exam-container">
        <div class="lv-exam-breadcrumb">
            <a href="<?php echo site_url(); ?>">Home</a> <span>/</span>
            <a href="<?php echo site_url('mock-tests'); ?>">Mock Tests</a> <span>/</span>
            <span><?php echo html_escape($title); ?></span>
        </div>

        <div class="lv-exam-paper">
            <div class="lv-exam-paper-head">
                <span class="lv-tag">Free Mock Test</span>
                <h1><?php echo html_escape($title); ?></h1>
                <p class="lv-exam-subtitle"><?php echo nl2br(html_escape($description ?: 'Practice this test in a clean exam-paper format with timer, navigation, final submission and result report.')); ?></p>
                <div class="lv-stat-row">
                    <div class="lv-stat-box"><b><?php echo $time; ?></b><span>Minutes</span></div>
                    <div class="lv-stat-box"><b><?php echo $questions; ?></b><span>Questions</span></div>
                    <div class="lv-stat-box"><b><?php echo html_escape($passing); ?></b><span>Passing Marks</span></div>
                    <div class="lv-stat-box"><b><?php echo html_escape($difficulty); ?></b><span>Difficulty</span></div>
                </div>
            </div>
        </div>

        <div class="lv-features">
            <div class="lv-feature"><b>Real exam layout</b><span>Clean question paper, fixed timer and proper question navigator.</span></div>
            <div class="lv-feature"><b>Section-wise test flow</b><span>Supports section timer and automatic movement to the next section.</span></div>
            <div class="lv-feature"><b>Result report</b><span>Marks, percentage, correct, wrong, skipped and weak areas after submission.</span></div>
        </div>

        <div class="lv-layout">
            <div class="lv-card">
                <h2 class="lv-card-title">Exam Instructions</h2>
                <?php if($instructions !== ''): ?>
                    <div class="lv-instruction-box"><?php echo html_escape($instructions); ?></div>
                <?php else: ?>
                    <ol class="lv-instruction-list">
                        <li>This test contains multiple-choice questions.</li>
                        <li>The timer starts immediately after you click Start Test.</li>
                        <li>Use Save &amp; Next to save your answer and move ahead.</li>
                        <li>You can mark a question for review and come back before submission.</li>
                        <li>If section timing is enabled, the next section opens automatically when section time ends.</li>
                        <li>Once submitted, answers cannot be changed.</li>
                    </ol>
                <?php endif; ?>
                <div class="lv-note">Keep your device connected to the internet during the test. Do not refresh or close the browser while the attempt is in progress.</div>
            </div>

            <div class="lv-card lv-start-card">
                <h3 class="lv-card-title">Start Your Test</h3>
                <div class="lv-exam-checklist">
                    <b>Before you begin</b>
                    Sit in a quiet place, keep your connection stable, and submit only when you are ready to finish the attempt.
                </div>
                <?php if($this->session->flashdata('error_message')): ?>
                    <div class="alert alert-danger"><?php echo html_escape($this->session->flashdata('error_message')); ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo site_url('exam/start/'.$exam['slug']); ?>" id="startForm">
                    <div class="form-group"><input name="participant_name" class="lv-form-control" placeholder="Full name" value="<?php echo html_escape($this->session->userdata('first_name') ?: ''); ?>" required></div>
                    <div class="form-group"><input type="email" name="participant_email" class="lv-form-control" placeholder="Email address" value="<?php echo html_escape($this->session->userdata('email') ?: ''); ?>" required></div>
                    <div class="form-group"><input name="participant_mobile" class="lv-form-control" placeholder="Mobile optional"></div>
                    <div class="lv-mode-grid">
                        <label class="lv-mode-card"><input type="radio" name="attempt_mode" value="practice"> <strong>Practice</strong><small>Learning mode without strict pressure.</small></label>
                        <label class="lv-mode-card"><input type="radio" name="attempt_mode" value="mock" checked> <strong>Mock Test</strong><small>Timed real exam experience.</small></label>
                    </div>
                    <button class="lv-start-btn" id="startButton">Start Mock Test</button>
                    <a class="lv-back-link" href="<?php echo site_url('mock-tests'); ?>">View all mock tests</a>
                </form>
            </div>
        </div>
    </div>
</section>
<script>
(function(){
    var form=document.getElementById('startForm'), button=document.getElementById('startButton');
    if(!form || !button) return;
    function sync(){var selected=form.querySelector('input[name="attempt_mode"]:checked'); var mode=selected?selected.value:'mock'; button.textContent=mode==='practice'?'Start Practice':'Start Mock Test';}
    form.querySelectorAll('input[name="attempt_mode"]').forEach(function(input){input.addEventListener('change',sync);});
    sync();
})();
</script>
