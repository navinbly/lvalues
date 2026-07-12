<section class="lv-public-page">
    <style>
        .lv-public-page{background:#f7f9ff;padding:56px 0;color:#17213a}.lv-page-wrap{max-width:1120px;margin:0 auto;padding:0 15px}
        .lv-page-hero{background:#fff;border:1px solid #e6ebf5;border-radius:24px;padding:34px;box-shadow:0 18px 46px rgba(31,39,83,.08)}
        .lv-page-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;background:#eef2ff;color:#5f46e8;font-weight:900;font-size:12px;margin-bottom:12px}
        .lv-page-hero h1{font-size:38px;line-height:1.15;font-weight:900;margin:0 0 12px;color:#101828}.lv-page-hero p{font-size:16px;line-height:1.75;color:#5d6678;margin:0;max-width:820px}
        .lv-parent-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:22px}.lv-parent-card{background:#fff;border:1px solid #e6ebf5;border-radius:18px;padding:22px;box-shadow:0 12px 30px rgba(31,39,83,.06)}
        .lv-parent-card i{width:42px;height:42px;border-radius:13px;background:#edf8ff;color:#0b75bd;display:flex;align-items:center;justify-content:center;margin-bottom:14px}.lv-parent-card b{display:block;color:#101828;font-size:18px;margin-bottom:8px}.lv-parent-card span{display:block;color:#667085;line-height:1.65;font-size:14px}
        .lv-page-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.lv-page-actions a{border-radius:12px;padding:12px 18px;font-weight:900;text-decoration:none}.lv-primary{background:#6c4df6;color:#fff}.lv-secondary{background:#fff;color:#5f46e8;border:1px solid #dcd6ff}
        @media(max-width:900px){.lv-parent-grid{grid-template-columns:1fr}.lv-page-hero h1{font-size:30px}}
    </style>
    <div class="lv-page-wrap">
        <div class="lv-page-hero">
            <span class="lv-page-kicker">For Parents</span>
            <h1>Find learning support with more confidence</h1>
            <p>Lvalues helps parents compare tutors, learning modes, subjects, fees, batches, mock tests, and student progress signals before choosing support.</p>
            <div class="lv-page-actions">
                <a class="lv-primary" href="<?php echo site_url('home/search?search_for=tutor'); ?>" data-lv-event="tutor_search" data-lv-label="parents_page">Find a tutor</a>
                <a class="lv-secondary" href="<?php echo site_url('mock-tests'); ?>" data-lv-event="start_mock_test" data-lv-label="parents_page">Try a mock test</a>
            </div>
        </div>
        <div class="lv-parent-grid">
            <div class="lv-parent-card"><i class="fa-solid fa-magnifying-glass"></i><b>Search by need</b><span>Filter by subject, class level, online/offline mode, city, and learning goal.</span></div>
            <div class="lv-parent-card"><i class="fa-solid fa-user-check"></i><b>Review tutor fit</b><span>Check tutor profile, qualification, experience, fees, availability, and verification status.</span></div>
            <div class="lv-parent-card"><i class="fa-solid fa-chart-line"></i><b>Track progress</b><span>Use batches, sessions, assignments, tests, notifications, and reports to stay informed.</span></div>
        </div>
    </div>
</section>
