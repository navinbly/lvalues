<section class="lv-public-page">
    <style>
        .lv-public-page{background:#f7f9ff;padding:56px 0;color:#17213a}.lv-page-wrap{max-width:1120px;margin:0 auto;padding:0 15px}
        .lv-page-hero{background:#fff;border:1px solid #e6ebf5;border-radius:24px;padding:34px;box-shadow:0 18px 46px rgba(31,39,83,.08)}
        .lv-page-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;background:#eef2ff;color:#5f46e8;font-weight:900;font-size:12px;margin-bottom:12px}
        .lv-page-hero h1{font-size:38px;line-height:1.15;font-weight:900;margin:0 0 12px;color:#101828}.lv-page-hero p{font-size:16px;line-height:1.75;color:#5d6678;margin:0;max-width:820px}
        .lv-earn-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:22px}.lv-earn-card{background:#fff;border:1px solid #e6ebf5;border-radius:18px;padding:20px;box-shadow:0 12px 30px rgba(31,39,83,.06)}
        .lv-earn-card b{display:block;color:#101828;font-size:17px;margin-bottom:8px}.lv-earn-card span{display:block;color:#667085;line-height:1.65;font-size:14px}
        .lv-page-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.lv-page-actions a{border-radius:12px;padding:12px 18px;font-weight:900;text-decoration:none}.lv-primary{background:#6c4df6;color:#fff}.lv-secondary{background:#fff;color:#5f46e8;border:1px solid #dcd6ff}
        @media(max-width:900px){.lv-earn-grid{grid-template-columns:1fr}.lv-page-hero h1{font-size:30px}}
    </style>
    <div class="lv-page-wrap">
        <div class="lv-page-hero">
            <span class="lv-page-kicker">Tutor Earnings</span>
            <h1>Grow your teaching business on Lvalues</h1>
            <p>Tutors can create a profile, set fees, publish books and notes, create question banks, run mock tests, manage batches, and schedule online or offline learning.</p>
            <div class="lv-page-actions">
                <a class="lv-primary" href="<?php echo site_url('sign_up?instructor=yes'); ?>" data-lv-event="become_tutor" data-lv-label="earnings_page">Start as a tutor</a>
                <a class="lv-secondary" href="<?php echo site_url('tutor-verification'); ?>">See verification process</a>
            </div>
        </div>
        <div class="lv-earn-grid">
            <div class="lv-earn-card"><b>Set your offer</b><span>Choose subject, class level, teaching mode, city, fees, availability, and profile headline.</span></div>
            <div class="lv-earn-card"><b>Publish learning assets</b><span>Create books, notes, blogs, and structured content students can follow.</span></div>
            <div class="lv-earn-card"><b>Run batches</b><span>Create batches, invite students, schedule sessions, assignments, and tests.</span></div>
            <div class="lv-earn-card"><b>Track outcomes</b><span>Use reports and mock-test analytics to support students and improve teaching plans.</span></div>
        </div>
    </div>
</section>
