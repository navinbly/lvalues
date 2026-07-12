<section class="lv-public-page">
    <style>
        .lv-public-page{background:#f7f9ff;padding:56px 0;color:#17213a}
        .lv-page-wrap{max-width:1120px;margin:0 auto;padding:0 15px}
        .lv-page-hero{background:#fff;border:1px solid #e6ebf5;border-radius:24px;padding:34px;box-shadow:0 18px 46px rgba(31,39,83,.08)}
        .lv-page-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;background:#eef2ff;color:#5f46e8;font-weight:900;font-size:12px;margin-bottom:12px}
        .lv-page-hero h1{font-size:38px;line-height:1.15;font-weight:900;margin:0 0 12px;color:#101828}
        .lv-page-hero p{font-size:16px;line-height:1.75;color:#5d6678;margin:0;max-width:820px}
        .lv-step-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:22px}
        .lv-step-card,.lv-info-card{background:#fff;border:1px solid #e6ebf5;border-radius:18px;padding:20px;box-shadow:0 12px 30px rgba(31,39,83,.06)}
        .lv-step-card b,.lv-info-card b{display:block;color:#101828;font-size:17px;margin-bottom:8px}
        .lv-step-card span,.lv-info-card span{display:block;color:#667085;line-height:1.65;font-size:14px}
        .lv-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px}
        .lv-page-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
        .lv-page-actions a{border-radius:12px;padding:12px 18px;font-weight:900;text-decoration:none}
        .lv-primary{background:#6c4df6;color:#fff}.lv-secondary{background:#fff;color:#5f46e8;border:1px solid #dcd6ff}
        @media(max-width:900px){.lv-step-grid,.lv-info-grid{grid-template-columns:1fr}.lv-page-hero h1{font-size:30px}}
    </style>
    <div class="lv-page-wrap">
        <div class="lv-page-hero">
            <span class="lv-page-kicker">Tutor Trust</span>
            <h1>How Lvalues verifies tutors</h1>
            <p>Lvalues separates tutor registration, admin approval, and verified badges so students and parents understand where a tutor is in the trust process.</p>
            <div class="lv-page-actions">
                <a class="lv-primary" href="<?php echo site_url('sign_up?instructor=yes'); ?>" data-lv-event="become_tutor" data-lv-label="verification_page">Become a tutor</a>
                <a class="lv-secondary" href="<?php echo site_url('home/search?search_for=tutor'); ?>" data-lv-event="tutor_search" data-lv-label="verification_page">Find tutors</a>
            </div>
        </div>
        <div class="lv-step-grid">
            <div class="lv-step-card"><b>1. Profile checks</b><span>Tutors submit profile, teaching category, class level, subjects, qualification, experience, fees, mode, and availability.</span></div>
            <div class="lv-step-card"><b>2. Document review</b><span>Documents may include ID proof, qualification certificate, marksheet, certification, and experience proof.</span></div>
            <div class="lv-step-card"><b>3. Admin approval</b><span>Admin reviews the tutor application and decides whether the tutor can be approved for platform use.</span></div>
            <div class="lv-step-card"><b>4. Verified badge</b><span>A verified badge should be shown only after required documents are uploaded and admin approval is complete.</span></div>
        </div>
        <div class="lv-info-grid">
            <div class="lv-info-card"><b>Documents are optional at registration</b><span>A tutor profile can be created first, but public listing and verification depend on document completion and admin approval.</span></div>
            <div class="lv-info-card"><b>Parents should review fit</b><span>Verification helps trust, but parents should still compare teaching mode, subject fit, fees, schedule, and student goals.</span></div>
        </div>
    </div>
</section>
