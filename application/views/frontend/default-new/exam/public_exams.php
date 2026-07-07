<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if (!function_exists('lv_exam_limit')) {
    function lv_exam_limit($text, $limit = 120) {
        $text = trim(strip_tags((string)$text));
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }
}
$exam_count = is_array($exams ?? null) ? count($exams) : 0;
?>
<style>
    .lv-mock-page{background:#f6f8ff;padding:48px 0 64px;}
    .lv-mock-breadcrumb{font-size:13px;margin-bottom:18px;color:#64748b;}
    .lv-mock-breadcrumb a{color:#5b4df5;font-weight:700;text-decoration:none;}
    .lv-mock-hero{position:relative;overflow:hidden;border-radius:30px;background:linear-gradient(135deg,#20124d 0%,#4f46e5 45%,#06b6d4 100%);padding:44px 38px;box-shadow:0 24px 70px rgba(31,41,111,.22);color:#fff;}
    .lv-mock-hero:before{content:"";position:absolute;right:-80px;top:-80px;width:260px;height:260px;border-radius:999px;background:rgba(255,255,255,.16);}
    .lv-mock-hero:after{content:"";position:absolute;right:120px;bottom:-120px;width:220px;height:220px;border-radius:999px;background:rgba(255,255,255,.10);}
    .lv-mock-hero-content{position:relative;z-index:2;}
    .lv-mock-kicker{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:8px 13px;font-size:13px;font-weight:900;color:#fff;}
    .lv-mock-hero h1{font-size:42px;line-height:1.15;margin:18px 0 12px;font-weight:950;letter-spacing:-.8px;color:#fff;}
    .lv-mock-hero p{max-width:760px;font-size:17px;line-height:1.8;color:rgba(255,255,255,.86);margin:0;}
    .lv-mock-hero-stat{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);border-radius:22px;padding:18px;backdrop-filter:blur(10px);text-align:center;}
    .lv-mock-hero-stat b{display:block;font-size:34px;line-height:1;color:#fff;}
    .lv-mock-hero-stat span{display:block;margin-top:7px;color:rgba(255,255,255,.78);font-weight:700;}
    .lv-mock-filter{margin-top:28px;background:#fff;border:1px solid #e7ebf4;border-radius:22px;padding:18px;box-shadow:0 12px 36px rgba(15,23,42,.08);}
    .lv-mock-filter .form-control{height:46px;border-radius:13px;border-color:#dbe3ef;}
    .lv-mock-section-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin:34px 0 18px;}
    .lv-mock-section-head h2{font-size:28px;font-weight:950;color:#17213a;margin:0;}
    .lv-mock-section-head p{margin:8px 0 0;color:#64748b;}
    .lv-exam-card{height:100%;position:relative;background:#fff;border:1px solid #e7ebf4;border-radius:24px;padding:22px;box-shadow:0 12px 34px rgba(15,23,42,.07);transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;}
    .lv-exam-card:hover{transform:translateY(-4px);border-color:#c7d2fe;box-shadow:0 18px 48px rgba(79,70,229,.15);}
    .lv-exam-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:12px;}
    .lv-exam-icon{width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:16px;background:#eef2ff;color:#4f46e5;font-size:22px;}
    .lv-free-badge{display:inline-block;background:#ecfdf5;color:#047857;border:1px solid #bbf7d0;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:950;white-space:nowrap;}
    .lv-exam-card h3{font-size:20px;line-height:1.3;color:#17213a;font-weight:950;margin:0 0 9px;}
    .lv-exam-desc{min-height:48px;color:#64748b;line-height:1.65;margin-bottom:14px;font-size:14px;}
    .lv-exam-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:14px 0 18px;}
    .lv-exam-meta-item{border:1px solid #eef1f6;background:#f8fafc;border-radius:14px;padding:10px;}
    .lv-exam-meta-item b{display:block;font-size:15px;color:#17213a;line-height:1.15;}
    .lv-exam-meta-item span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-top:4px;font-weight:800;}
    .lv-start-btn{display:flex;align-items:center;justify-content:center;width:100%;border:0;border-radius:15px;background:#6c4df6;color:#fff!important;padding:13px 16px;font-weight:950;text-decoration:none;box-shadow:0 10px 22px rgba(108,77,246,.22);}
    .lv-start-btn:hover{background:#5639de;text-decoration:none;}
    .lv-empty-state{background:#fff;border:1px dashed #cbd5e1;border-radius:22px;padding:34px;text-align:center;color:#64748b;}
    @media(max-width:991px){.lv-mock-hero h1{font-size:34px}.lv-mock-hero{padding:34px 24px}.lv-mock-section-head{display:block}.lv-mock-hero-stat{margin-top:20px}}
    @media(max-width:575px){.lv-mock-page{padding-top:26px}.lv-mock-hero h1{font-size:28px}.lv-mock-hero p{font-size:15px}.lv-exam-meta{grid-template-columns:1fr}.lv-mock-filter{display:none}}
</style>

<section class="lv-mock-page">
    <div class="container">
        <div class="lv-mock-breadcrumb">
            <a href="<?php echo site_url(); ?>">Home</a> <span>/</span> <span>Mock Tests</span>
        </div>

        <div class="lv-mock-hero">
            <div class="row align-items-center lv-mock-hero-content">
                <div class="col-lg-8">
                    <span class="lv-mock-kicker"><i class="fa fa-bolt"></i> Free Practice + Final Exam Feel</span>
                    <h1>Practice Mock Tests & Competitive Exams</h1>
                    <p>Attempt public Lvalues mock tests in a modern exam interface with timer, instant results, feedback and weak area reports.</p>
                </div>
                <div class="col-lg-4">
                    <div class="lv-mock-hero-stat">
                        <b><?php echo (int)$exam_count; ?>+</b>
                        <span>published mock tests</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if(!empty($exams)): ?>
            <div class="lv-mock-filter">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-2 mb-lg-0">
                        <input type="text" class="form-control" id="lvMockSearch" placeholder="Search mock test, exam name, topic or difficulty">
                    </div>
                    <div class="col-lg-5 text-lg-right text-muted small">
                        Showing published public exams only. Draft and unpublished exams are hidden.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="lv-mock-section-head">
            <div>
                <h2>Available Mock Tests</h2>
                <p>Choose a test and start practicing like the final exam.</p>
            </div>
        </div>

        <div class="row" id="lvMockGrid">
            <?php foreach(($exams ?? []) as $exam): ?>
                <?php
                    $title = (string)($exam['title'] ?? 'Mock Test');
                    $description = lv_exam_limit($exam['description'] ?? '', 118);
                    if ($description === '') $description = 'Practice this exam with timer, instant result and weak area analysis.';
                    $question_count = (int)($exam['question_count'] ?: ($exam['question_limit'] ?? 0));
                    $time = (int)($exam['time_limit_minutes'] ?? 0);
                    $difficulty = (string)($exam['difficulty'] ?? 'Beginner');
                    $attempts = (int)($exam['attempt_count'] ?? 0);
                    $search_text = strtolower($title.' '.$description.' '.$difficulty);
                ?>
                <div class="col-lg-4 col-md-6 mb-4 lv-mock-item" data-search="<?php echo html_escape($search_text); ?>">
                    <article class="lv-exam-card">
                        <div class="lv-exam-card-top">
                            <div class="lv-exam-icon"><i class="fa fa-clipboard-check"></i></div>
                            <span class="lv-free-badge">Free</span>
                        </div>
                        <h3><?php echo html_escape($title); ?></h3>
                        <p class="lv-exam-desc"><?php echo html_escape($description); ?></p>
                        <div class="lv-exam-meta">
                            <div class="lv-exam-meta-item"><b><?php echo $question_count; ?></b><span>Questions</span></div>
                            <div class="lv-exam-meta-item"><b><?php echo $time; ?> min</b><span>Duration</span></div>
                            <div class="lv-exam-meta-item"><b><?php echo html_escape($difficulty); ?></b><span>Difficulty</span></div>
                            <div class="lv-exam-meta-item"><b><?php echo $attempts; ?></b><span>Attempts</span></div>
                        </div>
                        <a class="lv-start-btn" href="<?php echo site_url('mock-tests/'.($exam['slug'] ?? '')); ?>">Start Test</a>
                    </article>
                </div>
            <?php endforeach; ?>
            <?php if(empty($exams)): ?>
                <div class="col-12"><div class="lv-empty-state"><h4>No public mock tests are available right now.</h4><p class="mb-0">Please check again later.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<script>
(function(){
    var search = document.getElementById('lvMockSearch');
    if(!search) return;
    search.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.lv-mock-item').forEach(function(card){
            card.style.display = !q || (card.getAttribute('data-search') || '').indexOf(q) !== -1 ? '' : 'none';
        });
    });
})();
</script>
