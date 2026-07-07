<?php if(get_frontend_settings('recaptcha_status')): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$oauth_signup_profile = $this->session->userdata('oauth_signup_profile');
$is_oauth_signup = is_array($oauth_signup_profile) && !empty($oauth_signup_profile['email']);
$registration_type = ($this->input->get('tutor') || $this->input->get('instructor') === 'yes') ? 'tutor' : 'student';
if (set_value('registration_type')) {
    $registration_type = set_value('registration_type');
}
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];
$selected_category_ids = array_map('strval', (array)$this->input->post('tutor_category_ids'));
$selected_class_ids    = array_map('strval', (array)$this->input->post('tutor_class_ids'));
$selected_subject_ids  = array_map('strval', (array)$this->input->post('tutor_subject_ids'));
$selected_student_category_id = (string)set_value('student_category_id');
$selected_student_class_id = (string)set_value('student_class_id');
$selected_student_subject_id = (string)set_value('student_subject_interest_id');
$selected_student_board = (string)set_value('student_board');
$tutor_qualification_options = [
    '10th - Secondary School',
    '12th - Senior Secondary',
    'Diploma - Diploma',
    'PG Diploma - Post Graduate Diploma',
    'B.A. - Bachelor of Arts',
    'B.Com. - Bachelor of Commerce',
    'B.Sc. - Bachelor of Science',
    'B.B.A. - Bachelor of Business Administration',
    'B.C.A. - Bachelor of Computer Applications',
    'B.E. - Bachelor of Engineering',
    'B.Tech. - Bachelor of Technology',
    'B.Arch. - Bachelor of Architecture',
    'B.Plan. - Bachelor of Planning',
    'B.Des. - Bachelor of Design',
    'B.F.A. - Bachelor of Fine Arts',
    'B.P.A. - Bachelor of Performing Arts',
    'B.S.W. - Bachelor of Social Work',
    'B.Lib.I.Sc. - Bachelor of Library and Information Science',
    'B.J.M.C. - Bachelor of Journalism and Mass Communication',
    'B.H.M. - Bachelor of Hotel Management',
    'B.T.T.M. - Bachelor of Travel and Tourism Management',
    'B.Ed. - Bachelor of Education',
    'B.El.Ed. - Bachelor of Elementary Education',
    'B.P.Ed. - Bachelor of Physical Education',
    'LL.B. - Bachelor of Laws',
    'MBBS - Bachelor of Medicine and Bachelor of Surgery',
    'BDS - Bachelor of Dental Surgery',
    'BAMS - Bachelor of Ayurvedic Medicine and Surgery',
    'BHMS - Bachelor of Homeopathic Medicine and Surgery',
    'BUMS - Bachelor of Unani Medicine and Surgery',
    'BSMS - Bachelor of Siddha Medicine and Surgery',
    'BNYS - Bachelor of Naturopathy and Yogic Sciences',
    'BPT - Bachelor of Physiotherapy',
    'BOT - Bachelor of Occupational Therapy',
    'B.Optom. - Bachelor of Optometry',
    'BMLT - Bachelor of Medical Laboratory Technology',
    'B.Pharm. - Bachelor of Pharmacy',
    'Pharm.D. - Doctor of Pharmacy',
    'B.Sc. Nursing - Bachelor of Science in Nursing',
    'BASLP - Bachelor of Audiology and Speech Language Pathology',
    'B.Sc. Agriculture - Bachelor of Science in Agriculture',
    'B.Sc. Horticulture - Bachelor of Science in Horticulture',
    'B.Sc. Forestry - Bachelor of Science in Forestry',
    'B.F.Sc. - Bachelor of Fisheries Science',
    'B.V.Sc. & A.H. - Bachelor of Veterinary Science and Animal Husbandry',
    'B.F.Tech. - Bachelor of Fashion Technology',
    'B.V.A. - Bachelor of Visual Arts',
    'B.Sc. Nautical Science - Bachelor of Science in Nautical Science',
    'M.A. - Master of Arts',
    'M.Com. - Master of Commerce',
    'M.Sc. - Master of Science',
    'M.B.A. - Master of Business Administration',
    'M.C.A. - Master of Computer Applications',
    'M.E. - Master of Engineering',
    'M.Tech. - Master of Technology',
    'M.Arch. - Master of Architecture',
    'M.Plan. - Master of Planning',
    'M.Des. - Master of Design',
    'M.F.A. - Master of Fine Arts',
    'M.P.A. - Master of Performing Arts',
    'M.S.W. - Master of Social Work',
    'M.Lib.I.Sc. - Master of Library and Information Science',
    'M.J.M.C. - Master of Journalism and Mass Communication',
    'M.H.M. - Master of Hotel Management',
    'M.T.T.M. - Master of Travel and Tourism Management',
    'M.Ed. - Master of Education',
    'M.P.Ed. - Master of Physical Education',
    'LL.M. - Master of Laws',
    'MD - Doctor of Medicine',
    'MS - Master of Surgery',
    'MDS - Master of Dental Surgery',
    'MPT - Master of Physiotherapy',
    'MOT - Master of Occupational Therapy',
    'M.Pharm. - Master of Pharmacy',
    'M.Sc. Nursing - Master of Science in Nursing',
    'MHA - Master of Hospital Administration',
    'MPH - Master of Public Health',
    'MASLP - Master of Audiology and Speech Language Pathology',
    'M.Sc. Agriculture - Master of Science in Agriculture',
    'M.Sc. Horticulture - Master of Science in Horticulture',
    'M.Sc. Forestry - Master of Science in Forestry',
    'M.F.Sc. - Master of Fisheries Science',
    'M.V.Sc. - Master of Veterinary Science',
    'M.F.Tech. - Master of Fashion Technology',
    'M.V.A. - Master of Visual Arts',
    'Ph.D. - Doctor of Philosophy',
    'D.Sc. - Doctor of Science',
    'D.Litt. - Doctor of Literature',
    'Other',
];
?>
<style>
.registration-switch { display:flex; gap:10px; }
.registration-toggle { flex:1 1 0; border-radius:10px !important; font-weight:600; transition:all .2s ease; }
.registration-toggle.active { background:#6f42f5; border-color:#6f42f5; color:#fff; box-shadow:0 8px 18px rgba(111,66,245,.18); }
.registration-toggle:not(.active){ background:#fff; color:#6f42f5; }
.registration-entry { border:1px solid #e6e8ef; border-radius:14px; padding:16px; margin:18px 0; background:#fafbff; }
.registration-entry__label { margin:0 0 10px; font-size:14px; font-weight:800; color:#24324a; }
.registration-entry__help { margin:10px 0 0; font-size:12px; line-height:1.5; color:#667085; }
.taxonomy-section { border:1px solid #e6e8ef; border-radius:14px; padding:16px; background:#fff; }
.taxonomy-block { margin-bottom:18px; }
.taxonomy-label { font-size:14px; font-weight:700; color:#24324a; margin-bottom:6px; display:block; }
.taxonomy-help { font-size:12px; color:#6c757d; margin-bottom:8px; }
.taxonomy-select { width:100%; min-height:180px; border:1px solid #d9dff0; border-radius:10px; padding:10px; background:#fafbff; }
.student-taxonomy-select { min-height:48px; }
.taxonomy-select:focus { border-color:#6f42f5; box-shadow:0 0 0 .2rem rgba(111,66,245,.12); outline:0; }
.taxonomy-note { font-size:12px; color:#6c757d; }
.browser-location-box { border:1px solid #e6e8ef; border-radius:14px; padding:14px 16px; background:#fafbff; }
.browser-location-box .status-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; font-size:13px; font-weight:600; background:#eef2ff; color:#46516b; }
.browser-location-box .status-badge.is-success { background:#e9f8ef; color:#198754; }
.browser-location-box .status-badge.is-warning { background:#fff4e5; color:#c97a00; }
.location-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
.location-grid .full-width { grid-column:1 / -1; }
@media (max-width:767px){ .location-grid { grid-template-columns:1fr; } }
.registration-wizard { margin:18px 0 24px; }
.registration-wizard__steps { display:none; }
.registration-wizard__step { border:1px solid #e5e8f0; border-radius:10px; padding:10px 8px; background:#fff; color:#667085; font-size:12px; font-weight:700; text-align:center; line-height:1.25; }
.registration-wizard__step.is-active { border-color:#6f42f5; background:#f5f2ff; color:#5632c8; box-shadow:0 8px 18px rgba(111,66,245,.12); }
.registration-wizard__step.is-done { border-color:#29a36a; background:#eefaf4; color:#18724a; }
.registration-step-panel { display:none; border:1px solid #e6e8ef; border-radius:14px; padding:18px; background:#fff; box-shadow:0 10px 26px rgba(21,31,56,.05); }
.registration-step-panel.is-active { display:block; }
.registration-step-panel__title { margin:0 0 6px; font-size:18px; font-weight:800; color:#24324a; }
.registration-step-panel__help { margin:0 0 18px; color:#667085; font-size:13px; line-height:1.6; }
.registration-wizard__actions { display:flex; justify-content:space-between; gap:12px; margin-top:18px; }
.registration-wizard__actions .btn { min-height:44px; border-radius:9px; font-weight:700; }
.registration-review { border:1px solid #edf0f6; background:#fafbff; border-radius:12px; padding:14px; margin-bottom:14px; }
.registration-review h6 { margin:0 0 8px; font-weight:800; color:#24324a; }
.registration-review p { margin:0; color:#5d667b; font-size:13px; line-height:1.6; }
.registration-summary-table { display:grid; gap:8px; }
.registration-summary-row { display:grid; grid-template-columns:minmax(120px, 38%) minmax(0,1fr); gap:10px; padding:8px 0; border-bottom:1px solid #edf0f6; }
.registration-summary-row:last-child { border-bottom:0; }
.registration-summary-row strong { color:#344054; }
.registration-summary-row span { color:#5d667b; overflow-wrap:anywhere; }
.approval-choice { border:1px solid #e6e8ef; border-radius:12px; padding:14px; background:#fff; }
.approval-choice .form-check { margin-bottom:8px; }
.student-address-section { border:1px solid #e6e8ef; border-radius:14px; padding:16px; background:#fafbff; }
.signup-password-field { position:relative; }
.signup-password-field .signup-password-icon,
.signup-password-field .signup-password-toggle {
    position:absolute;
    top:24px;
    transform:translateY(-50%);
    z-index:3;
    color:#8b95a5;
    line-height:1;
    pointer-events:none;
}
.signup-password-field .signup-password-icon { left:18px; }
.signup-password-field .signup-password-toggle {
    right:18px;
    left:auto;
    pointer-events:auto;
}
.signup-password-field .form-control {
    padding-left:48px;
    padding-right:48px;
}
@media (max-width:767px){
    .registration-wizard__steps { grid-template-columns:1fr; }
    .registration-wizard__actions { flex-direction:column-reverse; }
    .registration-wizard__actions .btn { width:100%; }
}
</style>
<section class="sign-up my-5 pt-5">
<div class="container"><div class="row">
<div class="col-lg-7 col-md-6 col-sm-12 col-12 text-center"><img loading="lazy" width="65%" src="<?php echo site_url('assets/frontend/default-new/image/login-security.gif') ?>"></div>
<div class="col-lg-5 col-md-6 col-sm-12 col-12"><div class="sing-up-right">
<h3><?php echo get_phrase('Sign Up'); ?><span>!</span></h3>
<p><?php echo get_phrase("Explore, learn, and grow with us. Enjoy a seamless and enriching educational journey. Let's begin!") ?></p>
<?php $external_auth_context = 'signup'; $external_auth_role = $registration_type; include "external_login_buttons.php"; ?>
<form id="signup-form" action="<?php echo site_url('login/register') ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="registration_type" id="registration_type" value="<?php echo html_escape($registration_type); ?>">
<input type="hidden" name="tutor_lat" id="tutor_lat" value="<?php echo set_value('tutor_lat'); ?>">
<input type="hidden" name="tutor_lng" id="tutor_lng" value="<?php echo set_value('tutor_lng'); ?>">
<input type="hidden" name="tutor_location" id="tutor_location" value="<?php echo set_value('tutor_location'); ?>">
<div class="registration-entry">
    <p class="registration-entry__label">Register as</p>
    <div class="registration-switch" role="group" aria-label="Registration type">
        <button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'student' ? 'active' : ''; ?>" data-type="student">Student</button>
        <button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'tutor' ? 'active' : ''; ?>" data-type="tutor">Tutor</button>
    </div>
    <p class="registration-entry__help">Choose once before registration. This applies to email/password registration and future social sign-in options.</p>
</div>
<div class="mb-4"><h5><?php echo get_phrase('First Name'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('first_name') ? 'is-invalid' : ''; ?>" id="first_name" type="text" name="first_name" placeholder="Enter your first name" value="<?php echo html_escape(set_value('first_name', $is_oauth_signup ? ($oauth_signup_profile['first_name'] ?? '') : '')); ?>" <?php echo $is_oauth_signup ? 'readonly' : ''; ?> required><?php if(form_error('first_name')): ?><div class="invalid-feedback d-block"><?php echo form_error('first_name'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Last Name'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('last_name') ? 'is-invalid' : ''; ?>" type="text" name="last_name" placeholder="Enter your last name" value="<?php echo html_escape(set_value('last_name', $is_oauth_signup ? ($oauth_signup_profile['last_name'] ?? '') : '')); ?>" <?php echo $is_oauth_signup ? 'readonly' : ''; ?> required><?php if(form_error('last_name')): ?><div class="invalid-feedback d-block"><?php echo form_error('last_name'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Your email'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>" type="email" name="email" placeholder="Enter your email" value="<?php echo html_escape(set_value('email', $is_oauth_signup ? ($oauth_signup_profile['email'] ?? '') : '')); ?>" <?php echo $is_oauth_signup ? 'readonly' : ''; ?> required><?php if(form_error('email')): ?><div class="invalid-feedback d-block"><?php echo form_error('email'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Password'); ?></h5><div class="signup-password-field"><i class="fa-solid fa-key signup-password-icon"></i><i class="fa-solid fas fa-eye cursor-pointer signup-password-toggle" onclick="if($('#password').attr('type')=='text'){$('#password').attr('type','password');}else{$('#password').attr('type','text');} $(this).toggleClass('fa-eye'); $(this).toggleClass('fa-eye-slash');"></i><input class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>" id="password" type="password" name="password" placeholder="Enter password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, number, and special character." autocomplete="new-password" required><?php if(form_error('password')): ?><div class="invalid-feedback d-block"><?php echo form_error('password'); ?></div><?php endif; ?><small class="text-muted d-block mt-2">Use at least 8 characters with uppercase, lowercase, number, and special character. New password must be different from your previous password.</small></div></div>
<div class="mb-4">
    <h5><?php echo get_phrase('Phone'); ?></h5>
    <div class="row g-2">
        <div class="col-4">
            <select class="form-control <?php echo form_error('phone_country_code') ? 'is-invalid' : ''; ?>" name="phone_country_code" id="phone_country_code" required>
                <option value="+91" <?php echo set_select('phone_country_code', '+91', set_value('phone_country_code') === '' || set_value('phone_country_code') === '+91'); ?>>+91 (IN)</option>
                <option value="+1" <?php echo set_select('phone_country_code', '+1'); ?>>+1 (US)</option>
                <option value="+44" <?php echo set_select('phone_country_code', '+44'); ?>>+44 (UK)</option>
                <option value="+61" <?php echo set_select('phone_country_code', '+61'); ?>>+61 (AU)</option>
                <option value="+971" <?php echo set_select('phone_country_code', '+971'); ?>>+971 (UAE)</option>
            </select>
        </div>
        <div class="col-8">
            <input class="form-control <?php echo form_error('phone_number') ? 'is-invalid' : ''; ?>" id="phone_number" type="tel" inputmode="numeric" name="phone_number" placeholder="Mobile number without country code" value="<?php echo set_value('phone_number'); ?>" required>
        </div>
    </div>
    <?php if(form_error('phone_number')): ?>
        <div class="invalid-feedback d-block"><?php echo form_error('phone_number'); ?></div>
    <?php endif; ?>
</div>
<div id="student-fields" class="<?php echo $registration_type === 'student' ? '' : 'd-none'; ?>">
<div class="mb-4 taxonomy-section">
<h5 class="mb-2">Student Learning Profile</h5>
<p class="text-muted small mb-3">Select the class/level for which you want to learn. Tutors will use this to send relevant batch invitations.</p>
<div class="taxonomy-block <?php echo form_error('student_category_id') ? 'border border-danger rounded p-2' : ''; ?>">
<label class="taxonomy-label" for="student_category_id">Learning Category</label>
<select class="form-control taxonomy-select student-taxonomy-select <?php echo form_error('student_category_id') ? 'is-invalid' : ''; ?>" name="student_category_id" id="student_category_id">
<option value="">Select category</option>
<?php foreach ($tutor_registration_tree as $category_item): ?>
<option value="<?php echo html_escape($category_item['id']); ?>" <?php echo $selected_student_category_id === (string)$category_item['id'] ? 'selected' : ''; ?>><?php echo html_escape($category_item['name']); ?></option>
<?php endforeach; ?>
</select>
<?php if(form_error('student_category_id')): ?><div class="invalid-feedback d-block"><?php echo form_error('student_category_id'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-block <?php echo form_error('student_class_id') ? 'border border-danger rounded p-2' : ''; ?>">
<label class="taxonomy-label" for="student_class_id" id="student_class_label">Current Class & Level</label>
<select class="form-control taxonomy-select student-taxonomy-select <?php echo form_error('student_class_id') ? 'is-invalid' : ''; ?>" name="student_class_id" id="student_class_id">
<option value="">Select class / level</option>
<?php foreach ($tutor_registration_tree as $category_item): ?>
    <?php foreach (($category_item['classes'] ?? []) as $class_item): ?>
        <option value="<?php echo html_escape($class_item['id']); ?>" data-category="<?php echo html_escape($category_item['id']); ?>" <?php echo $selected_student_class_id === (string)$class_item['id'] ? 'selected' : ''; ?>><?php echo html_escape($class_item['name']); ?></option>
    <?php endforeach; ?>
<?php endforeach; ?>
</select>
<?php if(form_error('student_class_id')): ?><div class="invalid-feedback d-block"><?php echo form_error('student_class_id'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-block">
<label class="taxonomy-label" for="student_subject_interest_id" id="student_subject_label">Subject Interest <small class="text-muted">Optional</small></label>
<select class="form-control taxonomy-select student-taxonomy-select" name="student_subject_interest_id" id="student_subject_interest_id">
<option value="">Any / Not sure</option>
<?php foreach ($tutor_registration_tree as $category_item): ?>
    <?php foreach (($category_item['classes'] ?? []) as $class_item): ?>
        <?php foreach (($class_item['subjects'] ?? []) as $subject_item): ?>
            <option value="<?php echo html_escape($subject_item['id']); ?>" data-class="<?php echo html_escape($class_item['id']); ?>" <?php echo $selected_student_subject_id === (string)$subject_item['id'] ? 'selected' : ''; ?>><?php echo html_escape($subject_item['name']); ?></option>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endforeach; ?>
</select>
</div>
<div class="row g-2">
<div class="col-md-6 mb-2 d-none" id="student_board_box"><label class="taxonomy-label" for="student_board">Board</label><select class="form-control <?php echo form_error('student_board') ? 'is-invalid' : ''; ?>" name="student_board" id="student_board"><option value="">Select board</option><?php foreach (['CBSE','ICSE','State Board','Other'] as $board_option): ?><option value="<?php echo html_escape($board_option); ?>" <?php echo $selected_student_board === $board_option ? 'selected' : ''; ?>><?php echo html_escape($board_option); ?></option><?php endforeach; ?></select><?php if(form_error('student_board')): ?><div class="invalid-feedback d-block"><?php echo form_error('student_board'); ?></div><?php endif; ?></div>
<div class="col-md-6 mb-2"><label class="taxonomy-label">Academic Year</label><input type="text" class="form-control" name="student_academic_year" placeholder="Example: 2026" value="<?php echo html_escape(set_value('student_academic_year', date('Y') . '-' . substr((string)(date('Y') + 1), -2))); ?>"></div>
<div class="col-md-12 mb-2" id="student_learning_message_box"><label class="taxonomy-label" id="student_learning_message_label">Message / Learning Goal</label><textarea class="form-control" name="student_learning_message" rows="3" maxlength="1000" placeholder="Tell us what you want to learn or any requirement for the tutor."><?php echo html_escape(set_value('student_learning_message')); ?></textarea><small class="text-muted">Optional. For IT and Professional courses, write your learning goal, experience level, or preferred course requirement.</small></div>
</div>
</div>
</div>
<?php if(get_settings('allow_instructor')): ?>
<div id="tutor-fields" class="<?php echo $registration_type === 'tutor' ? '' : 'd-none'; ?>">
<div class="mb-4 taxonomy-section">
<h5 class="mb-2">Teaching Categories</h5>
<p class="text-muted small mb-3">Select category, then class/course group, then subject(s).</p>
<div class="taxonomy-block <?php echo form_error('tutor_category_ids[]') ? 'border border-danger rounded p-2' : ''; ?>">
<label class="taxonomy-label" for="category_selector">Category</label>
<div class="taxonomy-help">Choose one or more main teaching areas.</div>
<select class="form-control taxonomy-select" id="category_selector" name="tutor_category_ids[]" multiple size="7">
<?php foreach ($tutor_registration_tree as $category_item): ?>
<option value="<?php echo html_escape($category_item['id']); ?>" <?php echo in_array((string)$category_item['id'], $selected_category_ids, true) ? 'selected' : ''; ?>><?php echo html_escape($category_item['name']); ?></option>
<?php endforeach; ?>
</select>
<?php if(form_error('tutor_category_ids[]')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_category_ids[]'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-block <?php echo form_error('tutor_class_ids[]') ? 'border border-danger rounded p-2' : ''; ?>">
<label class="taxonomy-label" for="class_selector">Class / Course Group</label>
<div class="taxonomy-help">Available options depend on your selected category.</div>
<select class="form-control taxonomy-select" id="class_selector" name="tutor_class_ids[]" multiple size="7"></select>
<?php if(form_error('tutor_class_ids[]')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_class_ids[]'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-block <?php echo form_error('tutor_subject_ids[]') ? 'border border-danger rounded p-2' : ''; ?>">
<label class="taxonomy-label" for="subject_selector">Subject(s)</label>
<div class="taxonomy-help">Only subjects related to the selected class/course group will appear.</div>
<select class="form-control taxonomy-select" id="subject_selector" name="tutor_subject_ids[]" multiple size="8"></select>
<?php if(form_error('tutor_subject_ids[]')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_subject_ids[]'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-note">Use Ctrl + Click or Cmd + Click to select multiple items.</div>
</div>
<div class="mb-4 taxonomy-section">
<h5 class="mb-2">Tutor Profile</h5>
<p class="text-muted small mb-3">These details help admin verify your application and help students understand your teaching background after approval.</p>
<div class="mb-3"><label class="taxonomy-label" for="tutor_headline">Profile Headline <small class="text-muted">Optional</small></label><input class="form-control <?php echo form_error('tutor_headline') ? 'is-invalid' : ''; ?>" id="tutor_headline" type="text" name="tutor_headline" maxlength="160" placeholder="Example: Maths tutor for Classes 8-12 with JEE foundation focus" value="<?php echo html_escape(set_value('tutor_headline')); ?>"><?php if(form_error('tutor_headline')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_headline'); ?></div><?php endif; ?></div>
<div class="mb-3"><label class="taxonomy-label" for="tutor_qualification">Education / Qualification</label><select class="form-control <?php echo form_error('tutor_qualification') ? 'is-invalid' : ''; ?>" id="tutor_qualification" name="tutor_qualification"><option value="">Select education / qualification</option><?php foreach ($tutor_qualification_options as $qualification_option): ?><option value="<?php echo html_escape($qualification_option); ?>" <?php echo set_select('tutor_qualification', $qualification_option); ?>><?php echo html_escape($qualification_option); ?></option><?php endforeach; ?></select><?php if(form_error('tutor_qualification')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_qualification'); ?></div><?php endif; ?></div>
<div class="mb-3"><label class="taxonomy-label" for="tutor_experience_years">Teaching Experience</label><div class="row g-2"><div class="col-md-5" data-feedback-scope="tutor_experience_years"><input class="form-control <?php echo form_error('tutor_experience_years') ? 'is-invalid' : ''; ?>" id="tutor_experience_years" type="number" min="0" max="50" step="1" name="tutor_experience_years" placeholder="Years" title="Teaching Experience must be between 0 and 50 years." value="<?php echo html_escape(set_value('tutor_experience_years')); ?>"><?php if(form_error('tutor_experience_years')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_experience_years'); ?></div><?php endif; ?></div><div class="col-md-7"><input class="form-control <?php echo form_error('tutor_current_role') ? 'is-invalid' : ''; ?>" id="tutor_current_role" type="text" name="tutor_current_role" maxlength="160" placeholder="Current role, e.g. School teacher / Industry mentor" value="<?php echo html_escape(set_value('tutor_current_role')); ?>"><?php if(form_error('tutor_current_role')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_current_role'); ?></div><?php endif; ?></div></div></div>
<div class="mb-0"><label class="taxonomy-label" for="tutor_bio">Teaching Bio</label><textarea class="form-control <?php echo form_error('tutor_bio') ? 'is-invalid' : ''; ?>" id="tutor_bio" name="tutor_bio" rows="4" maxlength="1500" placeholder="Describe your teaching style, achievements, exam/course expertise, online class comfort, and student outcomes."><?php echo html_escape(set_value('tutor_bio')); ?></textarea><?php if(form_error('tutor_bio')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_bio'); ?></div><?php endif; ?></div>
</div>
<div class="mb-4"><h5>Mode</h5><select class="form-control <?php echo form_error('tutor_teaching_mode') ? 'is-invalid' : ''; ?>" name="tutor_teaching_mode" id="tutor_teaching_mode"><option value="">Select mode</option><option value="online" <?php echo set_select('tutor_teaching_mode', 'online'); ?>>Online</option><option value="offline" <?php echo set_select('tutor_teaching_mode', 'offline'); ?>>Offline</option><option value="both" <?php echo set_select('tutor_teaching_mode', 'both'); ?>>Both</option></select><?php if(form_error('tutor_teaching_mode')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_teaching_mode'); ?></div><?php endif; ?></div>
<div class="mb-4"><h5>Address Details</h5><div class="location-grid"><div class="full-width"><div class="position-relative"><i class="fa-solid fa-location-dot"></i><input class="form-control <?php echo form_error('tutor_address_line1') ? 'is-invalid' : ''; ?>" id="tutor_address_line1" type="text" name="tutor_address_line1" placeholder="House no, street, area" value="<?php echo set_value('tutor_address_line1'); ?>"></div><?php if(form_error('tutor_address_line1')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_address_line1'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_city') ? 'is-invalid' : ''; ?>" id="tutor_city" type="text" name="tutor_city" placeholder="City" value="<?php echo set_value('tutor_city'); ?>"><?php if(form_error('tutor_city')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_city'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_state') ? 'is-invalid' : ''; ?>" id="tutor_state" type="text" name="tutor_state" placeholder="State" value="<?php echo set_value('tutor_state'); ?>"><?php if(form_error('tutor_state')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_state'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_country') ? 'is-invalid' : ''; ?>" id="tutor_country" type="text" name="tutor_country" placeholder="Country" value="<?php echo set_value('tutor_country'); ?>"><?php if(form_error('tutor_country')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_country'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_pincode') ? 'is-invalid' : ''; ?>" id="tutor_pincode" type="text" name="tutor_pincode" placeholder="Pincode" value="<?php echo set_value('tutor_pincode'); ?>"><?php if(form_error('tutor_pincode')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_pincode'); ?></div><?php endif; ?></div></div><small id="location-submit-status" class="text-muted d-none d-block mt-2"></small><?php if(form_error('tutor_lat')): ?><div class="invalid-feedback d-block mt-2"><?php echo form_error('tutor_lat'); ?></div><?php endif; ?><?php if(form_error('tutor_lng')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_lng'); ?></div><?php endif; ?></div>
<div class="mb-4"><h5>Fees</h5><div class="row g-3 mb-3"><div class="col-md-5"><label class="taxonomy-label" for="fee_currency">Currency</label><select class="form-control <?php echo form_error('fee_currency') ? 'is-invalid' : ''; ?>" name="fee_currency" id="fee_currency"><option value="">Select currency</option><?php foreach (['INR','USD','GBP','AED','CAD','AUD'] as $currency): ?><option value="<?php echo $currency; ?>" <?php echo set_select('fee_currency', $currency, set_value('fee_currency', 'INR') === $currency); ?>><?php echo $currency; ?></option><?php endforeach; ?></select><?php if(form_error('fee_currency')): ?><div class="invalid-feedback d-block"><?php echo form_error('fee_currency'); ?></div><?php endif; ?></div><div class="col-md-7"><label class="taxonomy-label d-block">Fee Type</label><div class="form-check form-check-inline"><input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_hour" value="per_hour" <?php echo set_radio('fee_type', 'per_hour', set_value('fee_type') !== 'per_subject'); ?>><label class="form-check-label" for="fee_type_per_hour">Per Hour</label></div><div class="form-check form-check-inline"><input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_subject" value="per_subject" <?php echo set_radio('fee_type', 'per_subject'); ?>><label class="form-check-label" for="fee_type_per_subject">Per Subject</label></div><?php if(form_error('fee_type')): ?><div class="invalid-feedback d-block"><?php echo form_error('fee_type'); ?></div><?php endif; ?></div></div><div id="per-hour-box" class="fee-box"><input class="form-control <?php echo form_error('tutor_hourly_fee') ? 'is-invalid' : ''; ?>" id="tutor_hourly_fee" type="number" min="0" step="0.01" name="tutor_hourly_fee" placeholder="Enter fee per hour" value="<?php echo set_value('tutor_hourly_fee'); ?>"><?php if(form_error('tutor_hourly_fee')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_hourly_fee'); ?></div><?php endif; ?></div><div id="per-subject-box" class="fee-box"><div id="subject-fees-wrapper"></div><?php if(form_error('subject_fee_amount[]')): ?><div class="invalid-feedback d-block"><?php echo form_error('subject_fee_amount[]'); ?></div><?php endif; ?><button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-subject-fee-row">Add Subject Fee</button></div><small id="fee-preview" class="text-muted d-block mt-2"></small></div>
<div class="mb-4"><h5><?php echo get_phrase('Document'); ?> <small class="text-muted">Optional now</small></h5><div class="position-relative"><input class="form-control" id="document" type="file" name="document" accept=".doc,.docx,.pdf,.txt,.png,.jpg,.jpeg"><small class="text-muted d-block mt-2">You can skip this now and upload documents later from your dashboard before admin approval.</small><small class="text-muted d-block">Accepted documents: Government ID proof, qualification certificate, marksheet, professional certification, teaching experience proof, or other supporting document. Accepted file types: doc, docx, pdf, txt, png, jpg, jpeg.</small><small class="text-muted d-block">Registration: documents optional. Admin approval: documents required. Verified badge: documents required plus admin approval.</small><small class="text-muted d-block">Your tutor profile can be created without documents, but you cannot become approved, verified, or publicly listed until required documents are uploaded and approved by admin.</small></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Message'); ?></h5><div class="position-relative"><textarea class="form-control" name="message" rows="4"><?php echo set_value('message'); ?></textarea></div></div>
</div>
<?php endif; ?>
<?php if(get_frontend_settings('recaptcha_status')): ?><div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div><?php endif; ?>
<div class="log-in"><button type="submit" class="btn btn-primary" id="signup-submit-btn">Submit Registration</button></div>
</form>
<div class="another text-center"><p><?php echo get_phrase('Already you have an account?') ?> <a href="<?php echo site_url('login') ?>" data-lvalues-auth="login"><?php echo get_phrase('Log In') ?></a></p><h5><?php echo get_phrase('Or') ?></h5></div>
<div class="social-media"><div class="row"><div class="col-md-12 text-center"><?php if(get_settings('fb_social_login')) include "facebook_login.php"; ?></div></div></div>
</div></div></div></div></section>
<script>
(function() {
const registrationInput = document.getElementById('registration_type');
const tutorFields = document.getElementById('tutor-fields');
const studentFields = document.getElementById('student-fields');
const toggles = document.querySelectorAll('.registration-toggle');
const locationSubmitStatus = document.getElementById('location-submit-status');
const tutorLocation = document.getElementById('tutor_location');
const tutorLat = document.getElementById('tutor_lat');
const tutorLng = document.getElementById('tutor_lng');
const submitBtn = document.getElementById('signup-submit-btn');
const subjectFeesWrapper = document.getElementById('subject-fees-wrapper');
const addSubjectFeeBtn = document.getElementById('add-subject-fee-row');
const feeTypeRadios = document.querySelectorAll('.fee-type-radio');
const feeCurrency = document.getElementById('fee_currency');
const feePreview = document.getElementById('fee-preview');
const perHourBox = document.getElementById('per-hour-box');
const perSubjectBox = document.getElementById('per-subject-box');
const categorySelector = document.getElementById('category_selector');
const classSelector = document.getElementById('class_selector');
const subjectSelector = document.getElementById('subject_selector');
const studentCategorySelector = document.getElementById('student_category_id');
const studentClassSelector = document.getElementById('student_class_id');
const studentSubjectSelector = document.getElementById('student_subject_interest_id');
const studentClassLabel = document.getElementById('student_class_label');
const studentSubjectLabel = document.getElementById('student_subject_label');
const studentBoardBox = document.getElementById('student_board_box');
const studentBoard = document.getElementById('student_board');
const studentLearningMessageBox = document.getElementById('student_learning_message_box');
const studentLearningMessageLabel = document.getElementById('student_learning_message_label');
const registrationTree = <?php echo json_encode($tutor_registration_tree); ?>;
let oldSelectedClassIds = <?php echo json_encode(array_values($selected_class_ids)); ?>;
let oldSelectedSubjectIds = <?php echo json_encode(array_values($selected_subject_ids)); ?>;
const addressInputs = ['tutor_address_line1','tutor_city','tutor_state','tutor_country','tutor_pincode'].map(id => document.getElementById(id)).filter(Boolean);
const oldFeeNames = <?php echo json_encode((array)$this->input->post('subject_fee_name')); ?>;
const oldFeeAmounts = <?php echo json_encode((array)$this->input->post('subject_fee_amount')); ?>;
const phoneCountryCode = document.getElementById('phone_country_code');
const phoneNumber = document.getElementById('phone_number');
const phoneRules = {
    '+91': { pattern: '^[6-9][0-9]{9}$', maxLength: 10, title: 'Enter a valid 10-digit Indian mobile number without +91.' },
    '+1': { pattern: '^[2-9][0-9]{9}$', maxLength: 10, title: 'Enter a valid 10-digit US/Canada phone number without +1.' },
    '+44': { pattern: '^[0-9]{10}$', maxLength: 10, title: 'Enter a valid 10-digit UK phone number without +44.' },
    '+61': { pattern: '^[0-9]{9}$', maxLength: 9, title: 'Enter a valid 9-digit Australia phone number without +61.' },
    '+971': { pattern: '^[0-9]{9}$', maxLength: 9, title: 'Enter a valid 9-digit UAE phone number without +971.' }
};
function setRegistrationType(type){ registrationInput.value = type; toggles.forEach(btn => btn.classList.toggle('active', btn.dataset.type === type)); if (tutorFields){ tutorFields.classList.toggle('d-none', type !== 'tutor'); toggleTutorRequired(type === 'tutor'); } if (studentFields){ studentFields.classList.toggle('d-none', type !== 'student'); toggleStudentRequired(type === 'student'); } if (type === 'tutor') { syncSelectors(); updateCombinedLocation(); } if (type === 'student') { syncStudentSelectors(); } updateSubmitState(); }

function syncPhoneRule() {
    if (!phoneCountryCode || !phoneNumber) return;
    const rule = phoneRules[phoneCountryCode.value] || { pattern: '^[0-9]{6,15}$', maxLength: 15, title: 'Enter a valid phone number without country code.' };
    phoneNumber.setAttribute('pattern', rule.pattern);
    phoneNumber.setAttribute('maxlength', String(rule.maxLength));
    phoneNumber.setAttribute('title', rule.title);
    phoneNumber.placeholder = phoneCountryCode.value === '+91' ? '10-digit mobile number' : 'Mobile number without country code';
    validatePhoneField(false);
}

function validatePhoneField(showError) {
    if (!phoneCountryCode || !phoneNumber) return true;
    const selectedCode = phoneCountryCode.value || '';
    const selectedDigits = selectedCode.replace(/\D+/g, '');
    let value = phoneNumber.value || '';
    const digits = value.replace(/\D+/g, '');

    if (value.indexOf('+') !== -1 || (selectedDigits && digits.indexOf(selectedDigits) === 0 && digits.length > (phoneRules[selectedCode]?.maxLength || 15))) {
        phoneNumber.setCustomValidity('Do not type the country code here. Select ' + selectedCode + ' from the dropdown and enter only the mobile number.');
    } else {
        phoneNumber.setCustomValidity('');
    }

    if (showError && !phoneNumber.checkValidity() && typeof showControlError === 'function') showControlError(phoneNumber);
    if (phoneNumber.checkValidity() && typeof clearControlError === 'function') clearControlError(phoneNumber);
    return phoneNumber.checkValidity();
}

function toggleTutorRequired(isTutor){
    ['tutor_qualification','tutor_experience_years','tutor_teaching_mode','tutor_address_line1','tutor_city','tutor_country','fee_currency'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (isTutor) el.setAttribute('required','required');
        else el.removeAttribute('required');
    });

    if (tutorLat && tutorLng){
        tutorLat.removeAttribute('required');
        tutorLng.removeAttribute('required');
    }

    toggleFeeBox();
    updateSubmitState();
}

function toggleStudentRequired(isStudent){
    ['student_category_id','student_class_id'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (isStudent) el.setAttribute('required','required');
        else el.removeAttribute('required');
    });
}
function getSelectedStudentCategoryName(){
    if (!studentCategorySelector) return '';
    const selected = studentCategorySelector.options[studentCategorySelector.selectedIndex];
    return selected ? selected.textContent.trim() : '';
}
function isProfessionalStudentCategory(){
    const name = getSelectedStudentCategoryName().toLowerCase();
    return name.includes('it') || name.includes('professional') || name.includes('cloud') || name.includes('technology');
}
function isSchoolStudentCategory(){
    return getSelectedStudentCategoryName().trim().toLowerCase() === 'school courses';
}
function updateStudentFieldLabels(){
    const isProfessional = isProfessionalStudentCategory();
    const isSchool = isSchoolStudentCategory();
    if (studentClassLabel) studentClassLabel.textContent = isProfessional ? 'SubCategory' : 'Current Class & Level';
    if (studentSubjectLabel) studentSubjectLabel.innerHTML = isProfessional ? 'Course <small class="text-muted">Optional</small>' : 'Subject Interest <small class="text-muted">Optional</small>';
    if (studentBoardBox) studentBoardBox.classList.toggle('d-none', !isSchool);
    if (studentBoard) {
        if (isSchool) studentBoard.setAttribute('required', 'required');
        else {
            studentBoard.removeAttribute('required');
            studentBoard.value = '';
        }
    }
    if (studentLearningMessageLabel) studentLearningMessageLabel.textContent = isProfessional ? 'Message / Course Requirement' : 'Message / Learning Goal';
    if (studentLearningMessageBox) studentLearningMessageBox.classList.remove('d-none');
}
function syncStudentSelectors(){
    if(!studentCategorySelector || !studentClassSelector || !studentSubjectSelector) return;
    updateStudentFieldLabels();
    const selectedCategory = studentCategorySelector.value;
    Array.from(studentClassSelector.options).forEach(option => {
        if (!option.value) { option.hidden = false; return; }
        option.hidden = selectedCategory && option.dataset.category !== selectedCategory;
        if (option.hidden && option.selected) option.selected = false;
    });
    const selectedClass = studentClassSelector.value;
    Array.from(studentSubjectSelector.options).forEach(option => {
        if (!option.value) { option.hidden = false; return; }
        option.hidden = selectedClass && option.dataset.class !== selectedClass;
        if (option.hidden && option.selected) option.selected = false;
    });
}
function updateCombinedLocation(){ if(!tutorLocation) return; const parts = addressInputs.map(input => input && input.value ? input.value.trim() : '').filter(Boolean); tutorLocation.value = parts.join(', '); }
function hasVerifiedLocation(){ return !!(tutorLat && tutorLng && tutorLat.value && tutorLng.value); }
function updateSubmitState(){ if(!submitBtn) return; submitBtn.disabled = false; }
function setLocationStatus(type, text){ if(locationSubmitStatus){ locationSubmitStatus.classList.remove('d-none','text-success','text-warning'); if(type === 'success') locationSubmitStatus.classList.add('text-success'); if(type === 'warning') locationSubmitStatus.classList.add('text-warning'); locationSubmitStatus.textContent = text; } updateSubmitState(); }
function requestOptionalLocation(callback){ if(!tutorLat || !tutorLng || registrationInput.value !== 'tutor'){ callback(); return; } if(!navigator.geolocation){ tutorLat.value=''; tutorLng.value=''; setLocationStatus('warning','Location is optional. Your browser does not support location access, so address details will be used.'); setTimeout(callback, 450); return; } if(!window.isSecureContext && !['localhost','127.0.0.1'].includes(window.location.hostname)){ tutorLat.value=''; tutorLng.value=''; setLocationStatus('warning','Location is optional. Browser location requires a secure page, so address details will be used.'); setTimeout(callback, 450); return; } setLocationStatus('', 'Browser location is optional. Please allow it to verify your location, or deny it to continue with city and pincode.'); navigator.geolocation.getCurrentPosition(function(position){ tutorLat.value = position.coords.latitude; tutorLng.value = position.coords.longitude; setLocationStatus('success','Location verified and saved with your tutor profile.'); setTimeout(callback, 450); }, function(error){ tutorLat.value=''; tutorLng.value=''; const code = error && error.code ? error.code : 0; let help = 'Location is optional. We will continue with your city and pincode.'; if(code === 1) help = 'Location permission was not allowed. No problem, we will continue with your city and pincode.'; if(code === 2) help = 'Your browser could not detect location. We will continue with your city and pincode.'; if(code === 3) help = 'Location check timed out. We will continue with your city and pincode.'; setLocationStatus('warning', help); setTimeout(callback, 650); }, { enableHighAccuracy:false, timeout:10000, maximumAge:300000 }); }
function setPlaceholder(selectEl, text){ selectEl.innerHTML = ''; const opt = document.createElement('option'); opt.textContent = text; opt.disabled = true; selectEl.appendChild(opt); }
function syncSelectors(){ if(!categorySelector || !classSelector || !subjectSelector) return; const selectedCategoryIds = Array.from(categorySelector.selectedOptions).map(option => option.value); const currentClassIds = new Set(Array.from(classSelector.selectedOptions).map(o => o.value).concat(oldSelectedClassIds)); const currentSubjectIds = new Set(Array.from(subjectSelector.selectedOptions).map(o => o.value).concat(oldSelectedSubjectIds)); classSelector.innerHTML=''; subjectSelector.innerHTML=''; if (!selectedCategoryIds.length){ setPlaceholder(classSelector, 'Select category first'); setPlaceholder(subjectSelector, 'Select class/course group first'); oldSelectedClassIds = []; oldSelectedSubjectIds = []; return; }
let classCount = 0; registrationTree.forEach(categoryItem => { if (!selectedCategoryIds.includes(categoryItem.id)) return; (categoryItem.classes || []).forEach(classItem => { const option = document.createElement('option'); option.value = classItem.id; option.textContent =  classItem.name; if (currentClassIds.has(classItem.id)) option.selected = true; classSelector.appendChild(option); classCount++; }); }); if (!classCount){ setPlaceholder(classSelector, 'No class/course group found'); setPlaceholder(subjectSelector, 'No subjects found'); oldSelectedClassIds = []; oldSelectedSubjectIds = []; return; }
const effectiveClassIds = Array.from(classSelector.selectedOptions).map(option => option.value); let subjectCount = 0; registrationTree.forEach(categoryItem => { if (!selectedCategoryIds.includes(categoryItem.id)) return; (categoryItem.classes || []).forEach(classItem => { if (!effectiveClassIds.includes(classItem.id)) return; (classItem.subjects || []).forEach(subjectItem => { const option = document.createElement('option'); option.value = subjectItem.id; option.textContent =  subjectItem.name; if (currentSubjectIds.has(subjectItem.id)) option.selected = true; subjectSelector.appendChild(option); subjectCount++; }); }); }); if (!subjectCount){ setPlaceholder(subjectSelector, 'No subject found for selected class'); }
oldSelectedClassIds = []; oldSelectedSubjectIds = []; }
function currencySymbol(code){ return { INR:'₹', USD:'$', GBP:'£', AED:'د.إ', CAD:'C$', AUD:'A$' }[code] || ''; }
function formatFee(amount, type){ const code = feeCurrency ? feeCurrency.value : ''; if (!amount || !code) return ''; return currencySymbol(code) + amount + ' ' + code + ' ' + (type === 'per_subject' ? 'per subject' : 'per hour'); }
function updateFeePreview(){ if(!feePreview) return; const feeType = document.querySelector('.fee-type-radio:checked')?.value || 'per_hour'; if (feeType === 'per_hour') { feePreview.textContent = formatFee(document.getElementById('tutor_hourly_fee')?.value.trim() || '', 'per_hour'); return; } feePreview.textContent = Array.from(document.querySelectorAll('.subject-fee-row')).map(row => { const name = row.querySelector('[name="subject_fee_name[]"]')?.value.trim() || ''; const amount = row.querySelector('[name="subject_fee_amount[]"]')?.value.trim() || ''; return name && amount ? name + ': ' + formatFee(amount, 'per_subject') : ''; }).filter(Boolean).join(', '); }
function toggleFeeBox(){ if(!perHourBox || !perSubjectBox || !subjectFeesWrapper) return; const feeType = document.querySelector('.fee-type-radio:checked')?.value || 'per_hour'; const isTutor = registrationInput.value === 'tutor'; perHourBox.style.display = feeType === 'per_hour' && isTutor ? 'block' : 'none'; perSubjectBox.style.display = feeType === 'per_subject' && isTutor ? 'block' : 'none'; const hourlyInput = document.getElementById('tutor_hourly_fee'); if (hourlyInput){ if(feeType === 'per_hour' && isTutor) hourlyInput.setAttribute('required','required'); else hourlyInput.removeAttribute('required'); }
subjectFeesWrapper.querySelectorAll('input').forEach(el => { if(feeType === 'per_subject' && isTutor) el.setAttribute('required','required'); else el.removeAttribute('required'); }); updateFeePreview(); }
function createSubjectFeeRow(name='', amount=''){ if(!subjectFeesWrapper) return; const row=document.createElement('div'); row.className='row g-2 align-items-center mb-2 subject-fee-row'; row.innerHTML=`<div class="col-6"><input type="text" class="form-control" name="subject_fee_name[]" placeholder="Subject name" value="${escapeHtml(name)}"></div><div class="col-4"><input type="number" min="0" step="0.01" class="form-control" name="subject_fee_amount[]" placeholder="Fee" value="${escapeHtml(amount)}"></div><div class="col-2"><button type="button" class="btn btn-outline-danger w-100 delete-subject-fee">Delete</button></div>`; subjectFeesWrapper.appendChild(row); row.querySelector('.delete-subject-fee').addEventListener('click', function(){ row.remove(); if(!subjectFeesWrapper.querySelector('.subject-fee-row')) createSubjectFeeRow(); toggleFeeBox(); }); toggleFeeBox(); }
function escapeHtml(value){ return String(value ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
toggles.forEach(btn => btn.addEventListener('click', function(){ setRegistrationType(this.dataset.type); })); if(categorySelector){ categorySelector.addEventListener('change', syncSelectors); } if(classSelector){ classSelector.addEventListener('change', syncSelectors); } if(studentCategorySelector){ studentCategorySelector.addEventListener('change', syncStudentSelectors); } if(studentClassSelector){ studentClassSelector.addEventListener('change', syncStudentSelectors); } addressInputs.forEach(input => input.addEventListener('input', updateCombinedLocation)); if(addSubjectFeeBtn){ addSubjectFeeBtn.addEventListener('click', function(){ createSubjectFeeRow(); }); } feeTypeRadios.forEach(radio => radio.addEventListener('change', toggleFeeBox)); if(feeCurrency){ feeCurrency.addEventListener('change', updateFeePreview); } ['tutor_hourly_fee'].forEach(id => { const input = document.getElementById(id); if(input) input.addEventListener('input', updateFeePreview); }); if(subjectFeesWrapper){ subjectFeesWrapper.addEventListener('input', updateFeePreview); if(oldFeeNames.length || oldFeeAmounts.length){ const total = Math.max(oldFeeNames.length, oldFeeAmounts.length); for(let i=0;i<total;i++) createSubjectFeeRow(oldFeeNames[i] || '', oldFeeAmounts[i] || ''); } else { createSubjectFeeRow(); } }
if(phoneCountryCode){ phoneCountryCode.addEventListener('change', syncPhoneRule); }
if(phoneNumber){ phoneNumber.addEventListener('input', function(){ validatePhoneField(false); }); phoneNumber.addEventListener('blur', function(){ validatePhoneField(true); }); }
window.lvaluesRequestOptionalLocation = requestOptionalLocation;
window.lvaluesHasVerifiedLocation = hasVerifiedLocation;
updateCombinedLocation(); syncSelectors(); setRegistrationType(registrationInput.value || 'student'); syncPhoneRule(); toggleFeeBox(); updateSubmitState();
})();

(function initRegistrationWizard() {
const form = document.getElementById('signup-form');
const isOauthSignup = <?php echo $is_oauth_signup ? 'true' : 'false'; ?>;
if (!form || form.dataset.wizardReady === '1') return;
form.dataset.wizardReady = '1';

const registrationInput = document.getElementById('registration_type');
const studentFields = document.getElementById('student-fields');
const tutorFields = document.getElementById('tutor-fields');
const submitWrap = form.querySelector('.log-in');
const submitButton = document.getElementById('signup-submit-btn');
const passwordInput = document.getElementById('password');
const recaptcha = form.querySelector('.g-recaptcha');

const steps = [
    { key:'personal', title:'Personal information', help:'Add your basic contact details. This keeps the first step short and easy.' },
    { key:'course', title:'Course information', help:'Tell us what you want to learn or teach so Lvalues can match the right courses.' },
    { key:'address', title:'Address', help:'Add your address details. Tutors can optionally verify current location, but address-only registration is allowed.' },
    { key:'other', title:'Other information', help:'Add fees, documents, and optional notes where applicable.' },
    { key:'approval', title:'Review and approval', help:'Review your information and confirm before submitting registration.' }
];

const wizard = document.createElement('div');
wizard.className = 'registration-wizard';
wizard.innerHTML = '<div class="registration-wizard__steps" aria-label="Registration steps"></div><div class="registration-wizard__panels"></div><div class="registration-wizard__actions"><button type="button" class="btn btn-outline-primary" data-wizard-back>Back</button><button type="button" class="btn btn-primary" data-wizard-next>Next</button></div>';

const firstVisibleNode = Array.from(form.children).find(node => node.tagName !== 'INPUT' || node.type !== 'hidden');
form.insertBefore(wizard, firstVisibleNode || form.firstChild);
const stepList = wizard.querySelector('.registration-wizard__steps');
const panelWrap = wizard.querySelector('.registration-wizard__panels');

const panels = steps.map((step, index) => {
    const stepEl = document.createElement('div');
    stepEl.className = 'registration-wizard__step';
    stepEl.textContent = (index + 1) + '. ' + step.title;
    stepList.appendChild(stepEl);

    const panel = document.createElement('div');
    panel.className = 'registration-step-panel';
    panel.dataset.step = step.key;
    panel.innerHTML = '<h4 class="registration-step-panel__title">' + step.title + '</h4><p class="registration-step-panel__help">' + step.help + '</p>';
    panelWrap.appendChild(panel);
    return panel;
});

if (isOauthSignup && passwordInput) {
    passwordInput.removeAttribute('required');
    passwordInput.disabled = true;
    const passwordBlock = passwordInput.closest('.mb-4');
    if (passwordBlock) passwordBlock.classList.add('d-none');
}

const personalPanel = panels[0];
const coursePanel = panels[1];
const addressPanel = panels[2];
const otherPanel = panels[3];
const approvalPanel = panels[4];

function appendIfFound(panel, selectorOrNode) {
    const node = typeof selectorOrNode === 'string' ? form.querySelector(selectorOrNode) : selectorOrNode;
    if (node) panel.appendChild(node);
}

const registrationEntry = form.querySelector('.registration-entry');
appendIfFound(personalPanel, registrationEntry);
['first_name','last_name','email','password'].forEach(name => {
    appendIfFound(personalPanel, form.querySelector('[name="' + name + '"]')?.closest('.mb-4'));
});
appendIfFound(personalPanel, document.getElementById('phone_country_code')?.closest('.mb-4'));

const studentAddress = document.createElement('div');
studentAddress.id = 'student-address-fields';
studentAddress.className = 'student-address-section';
studentAddress.innerHTML = '<h5 class="mb-2">Student address</h5><p class="text-muted small mb-3">This helps tutors and support teams understand your learning location.</p><div class="location-grid"><div class="full-width"><input class="form-control" type="text" name="address_line1" id="address_line1" placeholder="House no, street, area"></div><div><input class="form-control" type="text" name="address_city" id="address_city" placeholder="City"></div><div><input class="form-control" type="text" name="address_state" id="address_state" placeholder="State"></div><div><input class="form-control" type="text" name="address_country" id="address_country" placeholder="Country"></div><div><input class="form-control" type="text" name="address_pincode" id="address_pincode" placeholder="Pincode"></div></div>';

if (studentFields) coursePanel.appendChild(studentFields);
addressPanel.appendChild(studentAddress);

if (tutorFields) {
    const tutorCourse = document.createElement('div');
    tutorCourse.id = 'tutor-course-step';
    tutorCourse.className = 'tutor-step-fields';
    const tutorAddress = document.createElement('div');
    tutorAddress.id = 'tutor-address-step';
    tutorAddress.className = 'tutor-step-fields';
    const tutorOther = document.createElement('div');
    tutorOther.id = 'tutor-other-step';
    tutorOther.className = 'tutor-step-fields';

    Array.from(tutorFields.children).forEach(child => {
        const text = child.textContent.trim().toLowerCase();
        if (text.indexOf('address details') !== -1) {
            tutorAddress.appendChild(child);
        } else if (text.indexOf('fees') !== -1 || text.indexOf('document') !== -1 || text.indexOf('message') !== -1) {
            tutorOther.appendChild(child);
        } else {
            tutorCourse.appendChild(child);
        }
    });

    coursePanel.appendChild(tutorCourse);
    addressPanel.appendChild(tutorAddress);
    otherPanel.appendChild(tutorOther);
    tutorFields.remove();
}

const studentOther = document.createElement('div');
studentOther.id = 'student-other-fields';
studentOther.className = 'taxonomy-section';
studentOther.innerHTML = '<h5 class="mb-2">Other information</h5><p class="text-muted small mb-3">You can add extra learning preferences after registration from your profile. Continue to review when ready.</p>';
otherPanel.appendChild(studentOther);

const reviewBox = document.createElement('div');
reviewBox.className = 'registration-review';
reviewBox.innerHTML = '<h6>Registration summary</h6><p data-registration-summary>Please review your details before submitting.</p>';
approvalPanel.appendChild(reviewBox);

const approvalChoice = document.createElement('div');
approvalChoice.className = 'approval-choice';
approvalChoice.innerHTML = '<h5 class="mb-2">Permission to submit</h5><p class="text-muted small">Please confirm that the information provided is correct and can be submitted for account creation and approval.</p><div class="form-check"><input class="form-check-input" type="radio" name="registration_permission" id="registration_permission_yes" value="yes"><label class="form-check-label" for="registration_permission_yes">Yes, submit my registration</label></div><div class="form-check"><input class="form-check-input" type="radio" name="registration_permission" id="registration_permission_no" value="no"><label class="form-check-label" for="registration_permission_no">No, I want to review again</label></div><div class="invalid-feedback d-block d-none" data-permission-error>Please choose Yes to submit or No to review again.</div>';
approvalPanel.appendChild(approvalChoice);
if (recaptcha) approvalPanel.appendChild(recaptcha);
if (submitWrap) {
    approvalPanel.appendChild(submitWrap);
    submitWrap.classList.add('d-none');
}

let currentStep = 0;
const backButton = wizard.querySelector('[data-wizard-back]');
const nextButton = wizard.querySelector('[data-wizard-next]');

function currentRole() {
    return registrationInput && registrationInput.value === 'tutor' ? 'tutor' : 'student';
}

function syncRoleFields() {
    const role = currentRole();
    document.querySelectorAll('.tutor-step-fields').forEach(el => el.classList.toggle('d-none', role !== 'tutor'));
    const studentAddressFields = document.getElementById('student-address-fields');
    const studentOtherFields = document.getElementById('student-other-fields');
    if (studentFields) studentFields.classList.toggle('d-none', role !== 'student');
    if (studentAddressFields) studentAddressFields.classList.toggle('d-none', role !== 'student');
    if (studentOtherFields) studentOtherFields.classList.toggle('d-none', role !== 'student');

    ['address_line1','address_city','address_country'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (role === 'student') el.setAttribute('required','required');
        else el.removeAttribute('required');
    });
}

function visibleControls(panel) {
    return Array.from(panel.querySelectorAll('input, select, textarea')).filter(el => {
        if (el.type === 'hidden' || el.disabled) return false;
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    });
}

function controlLabel(control) {
    const container = control.closest('.taxonomy-block, .mb-4, .col-md-6, .col-md-12, .full-width, .approval-choice') || control.parentElement;
    const label = container ? container.querySelector('label, h5') : null;
    if (label && label.textContent.trim()) return label.textContent.replace(/\s+/g, ' ').trim();
    if (control.placeholder) return control.placeholder;
    return (control.name || 'This field').replace(/\[\]/g, '').replace(/_/g, ' ');
}

function validationText(control) {
    const label = controlLabel(control);
    if (control.id === 'tutor_experience_years' && (control.validity.rangeUnderflow || control.validity.rangeOverflow || control.validity.stepMismatch || control.validity.badInput)) return 'Teaching Experience must be between 0 and 50 years.';
    if (control.validity.valueMissing) return label + ' is required.';
    if (control.validity.typeMismatch) return 'Enter a valid ' + label.toLowerCase() + '.';
    if (control.validity.patternMismatch) return control.title || 'Enter a valid ' + label.toLowerCase() + '.';
    if (control.validity.tooShort) return label + ' must be at least ' + control.minLength + ' characters.';
    if (control.validity.tooLong) return label + ' must be at most ' + control.maxLength + ' characters.';
    if (control.validity.rangeUnderflow) return label + ' must be at least ' + control.min + '.';
    if (control.validity.rangeOverflow) return label + ' must be at most ' + control.max + '.';
    return control.validationMessage || label + ' is invalid.';
}

function feedbackContainer(control) {
    if (control.id === 'tutor_experience_years') {
        return document.querySelector('[data-feedback-scope="tutor_experience_years"]') || control.parentElement || control;
    }
    return control.closest('.taxonomy-block, .mb-4, .col-md-6, .col-md-12, .full-width, .approval-choice') || control.parentElement || control;
}

function showControlError(control) {
    const container = feedbackContainer(control);
    let feedback = container.querySelector('[data-client-validation-for="' + control.name + '"]');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        feedback.setAttribute('data-client-validation-for', control.name || control.id || 'field');
        container.appendChild(feedback);
    }
    control.classList.add('is-invalid');
    feedback.textContent = validationText(control);
}

function clearControlError(control) {
    control.classList.remove('is-invalid');
    const container = feedbackContainer(control);
    const feedback = container.querySelector('[data-client-validation-for="' + control.name + '"]');
    if (feedback) feedback.remove();
}

form.querySelectorAll('input, select, textarea').forEach(control => {
    control.addEventListener('input', function() {
        if (this.checkValidity()) clearControlError(this);
    });
    control.addEventListener('change', function() {
        if (this.checkValidity()) clearControlError(this);
    });
});

function validateStep() {
    syncRoleFields();
    const panel = panels[currentStep];
    const controls = visibleControls(panel);
    let firstInvalid = null;
    for (const control of controls) {
        if (!control.checkValidity()) {
            showControlError(control);
            if (!firstInvalid) firstInvalid = control;
        } else {
            clearControlError(control);
        }
    }
    if (currentStep === 4) {
        const yes = document.getElementById('registration_permission_yes');
        const no = document.getElementById('registration_permission_no');
        const error = approvalPanel.querySelector('[data-permission-error]');
        if (no && no.checked) {
            if (error) {
                error.textContent = 'Click Back to review and edit your details, or choose Yes to submit your registration.';
                error.classList.remove('d-none');
            }
            if (!firstInvalid) firstInvalid = no;
            return false;
        }
        if (!yes || !yes.checked) {
            if (error) {
                error.textContent = 'Please choose Yes to submit or No to review again.';
                error.classList.remove('d-none');
            }
            if (!firstInvalid) firstInvalid = yes;
        } else if (error) {
            error.classList.add('d-none');
        }
    }
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function() { if (firstInvalid.focus) firstInvalid.focus({ preventScroll: true }); }, 250);
            return false;
        }
    return true;
}

function updateSummary() {
    const summary = approvalPanel.querySelector('[data-registration-summary]');
    if (!summary) return;
    try {
        const role = currentRole() === 'tutor' ? 'Tutor' : 'Student';
        const firstName = valueOf('first_name');
        const lastName = valueOf('last_name');
        const noSelected = document.getElementById('registration_permission_no')?.checked;
        const rows = [
            ['Role', role],
            ['Name', (firstName + ' ' + lastName).trim()],
            ['Email', valueOf('email')],
            ['Phone', (valueOf('phone_country_code') + ' ' + valueOf('phone_number')).trim()]
        ];

        if (role === 'Student') {
            rows.push(
                ['Learning Category', selectedText('student_category_id')],
                ['Current Class / Level', selectedText('student_class_id')],
                ['Subject Interest', selectedText('student_subject_interest_id') || 'Any / Not sure']
            );
            if (studentBoard && studentBoardBox && !studentBoardBox.classList.contains('d-none')) {
                rows.push(['Board', selectedText('student_board')]);
            }
            rows.push(
                ['Academic Year', valueOf('student_academic_year')],
                ['Learning Goal', valueOf('student_learning_message')],
                ['Address', compactValues(['address_line1','address_city','address_state','address_country','address_pincode']).join(', ')]
            );
        } else {
            rows.push(
                ['Teaching Categories', selectedTexts('tutor_category_ids[]').join(', ')],
                ['Class / Course Group', selectedTexts('tutor_class_ids[]').join(', ')],
                ['Subject(s)', selectedTexts('tutor_subject_ids[]').join(', ')],
                ['Profile Headline', valueOf('tutor_headline')],
                ['Education / Qualification', selectedText('tutor_qualification')],
                ['Teaching Experience', valueOf('tutor_experience_years') ? valueOf('tutor_experience_years') + ' year(s)' : ''],
                ['Current Role', valueOf('tutor_current_role')],
                ['Teaching Bio', valueOf('tutor_bio')],
                ['Mode', selectedText('tutor_teaching_mode')],
                ['Address', compactValues(['tutor_address_line1','tutor_city','tutor_state','tutor_country','tutor_pincode']).join(', ')],
                ['Location Verification', (typeof window.lvaluesHasVerifiedLocation === 'function' && window.lvaluesHasVerifiedLocation()) ? 'Verified' : 'Address-only registration'],
                ['Fee Type', feeTypeLabel()],
                ['Fees', feeSummary()],
                ['Document', documentName()],
                ['Message', valueOf('message')]
            );
        }

        renderSummaryRows(summary, rows, noSelected);
    } catch (error) {
        renderSummaryRows(summary, [
            ['Role', currentRole() === 'tutor' ? 'Tutor' : 'Student'],
            ['Name', compactValues(['first_name', 'last_name']).join(' ')],
            ['Email', valueOf('email')],
            ['Phone', compactValues(['phone_country_code', 'phone_number']).join(' ')]
        ], true);
    }
}

function valueOf(name) {
    const field = fieldByName(name);
    return field ? field.value.trim() : '';
}

function selectedText(nameOrId) {
    const field = fieldByName(nameOrId) || document.getElementById(nameOrId);
    if (!field || !field.options || field.selectedIndex < 0) return '';
    const option = field.options[field.selectedIndex];
    return option && option.value ? option.textContent.trim() : '';
}

function selectedTexts(name) {
    const field = fieldByName(name);
    if (!field || !field.options) return [];
    return Array.from(field.options)
        .filter(option => option.selected)
        .filter(option => option.value)
        .map(option => option.textContent.trim());
}

function fieldByName(name) {
    const field = form.elements[name];
    if (!field) return null;
    if (typeof RadioNodeList !== 'undefined' && field instanceof RadioNodeList) return field[0] || null;
    return field;
}

function renderSummaryRows(summary, rows, noSelected) {
    const filledRows = rows.filter(row => row[1] !== null && String(row[1]).trim() !== '');
    summary.innerHTML = (noSelected ? '<div class="alert alert-info py-2 mb-3">You selected review again. Click Back to edit any section, or select Yes when you are ready to submit.</div>' : '') +
        '<div class="registration-summary-table">' +
        filledRows.map(row => '<div class="registration-summary-row"><strong>' + escapeSummary(row[0]) + ':</strong><span>' + escapeSummary(row[1]) + '</span></div>').join('') +
        '</div>';
}

function compactValues(names) {
    return names.map(valueOf).filter(value => value !== '');
}

function feeTypeLabel() {
    const checked = form.querySelector('[name="fee_type"]:checked');
    if (!checked) return '';
    return checked.value === 'per_subject' ? 'Per Subject' : 'Per Hour';
}

function summaryCurrencySymbol(code) {
    return { INR:'₹', USD:'$', GBP:'£', AED:'د.إ', CAD:'C$', AUD:'A$' }[code] || '';
}

function summaryFeeAmount(amount, type) {
    const currency = valueOf('fee_currency');
    if (!amount || !currency) return '';
    return summaryCurrencySymbol(currency) + amount + ' ' + currency + ' ' + (type === 'per_subject' ? 'per subject' : 'per hour');
}

function feeSummary() {
    const feeType = form.querySelector('[name="fee_type"]:checked')?.value || 'per_hour';
    if (feeType === 'per_hour') return summaryFeeAmount(valueOf('tutor_hourly_fee'), 'per_hour');
    return Array.from(form.querySelectorAll('.subject-fee-row')).map(row => {
        const name = row.querySelector('[name="subject_fee_name[]"]')?.value.trim() || '';
        const amount = row.querySelector('[name="subject_fee_amount[]"]')?.value.trim() || '';
        return name && amount ? name + ': ' + summaryFeeAmount(amount, 'per_subject') : '';
    }).filter(Boolean).join(', ');
}

function documentName() {
    const field = document.getElementById('document');
    return field && field.files && field.files.length ? field.files[0].name : '';
}

function escapeSummary(value) {
    return String(value ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showStep(index) {
    currentStep = Math.max(0, Math.min(index, panels.length - 1));
    syncRoleFields();
    panels.forEach((panel, i) => panel.classList.toggle('is-active', i === currentStep));
    Array.from(stepList.children).forEach((step, i) => {
        step.classList.toggle('is-active', i === currentStep);
        step.classList.toggle('is-done', i < currentStep);
    });
    backButton.disabled = currentStep === 0;
    const isFinalStep = currentStep === panels.length - 1;
    nextButton.textContent = 'Next';
    nextButton.classList.toggle('d-none', isFinalStep);
    if (submitWrap) submitWrap.classList.toggle('d-none', !isFinalStep);
    if (currentStep === panels.length - 1) updateSummary();
    window.scrollTo({ top: Math.max(form.getBoundingClientRect().top + window.pageYOffset - 110, 0), behavior: 'smooth' });
}

backButton.addEventListener('click', function() {
    showStep(currentStep - 1);
});

nextButton.addEventListener('click', function() {
    if (!validateStep()) return;
    if (currentStep === panels.length - 1) {
        form.requestSubmit ? form.requestSubmit(submitButton) : submitButton.click();
        return;
    }
    showStep(currentStep + 1);
});

document.querySelectorAll('.registration-toggle').forEach(button => {
    button.addEventListener('click', function() {
        setTimeout(function() {
            syncRoleFields();
            showStep(currentStep);
        }, 0);
    });
});

const academicYear = form.querySelector('[name="student_academic_year"]');
if (academicYear) {
    academicYear.placeholder = 'Example: 2025-26';
    academicYear.setAttribute('required', 'required');
    academicYear.pattern = '^\\d{4}-\\d{2}$';
    academicYear.title = 'Use format YYYY-YY, for example 2025-26';
}

approvalPanel.querySelectorAll('[name="registration_permission"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateSummary();
        const error = approvalPanel.querySelector('[data-permission-error]');
        if (this.id === 'registration_permission_no' && this.checked) {
            if (error) {
                error.textContent = 'Click Back to review and edit your details, or choose Yes to submit your registration.';
                error.classList.remove('d-none');
            }
            return;
        }
        if (error) {
            error.textContent = 'Please choose Yes to submit or No to review again.';
            error.classList.add('d-none');
        }
    });
    radio.addEventListener('click', function() { setTimeout(updateSummary, 0); });
});

let optionalLocationSubmitPending = false;
let optionalLocationChecked = false;

form.addEventListener('submit', function(event) {
    if (currentStep !== panels.length - 1 || !validateStep()) {
        event.preventDefault();
        return;
    }

    if (!optionalLocationChecked && !optionalLocationSubmitPending && typeof window.lvaluesRequestOptionalLocation === 'function') {
        event.preventDefault();
        optionalLocationSubmitPending = true;
        window.lvaluesRequestOptionalLocation(function() {
            optionalLocationChecked = true;
            optionalLocationSubmitPending = false;
            if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {
                HTMLFormElement.prototype.submit.call(form);
            } else {
                form.submit();
            }
        });
    }
});

const firstServerInvalid = form.querySelector('.is-invalid');
const firstInvalidPanel = firstServerInvalid ? panels.findIndex(panel => panel.contains(firstServerInvalid)) : -1;
showStep(firstInvalidPanel >= 0 ? firstInvalidPanel : 0);
})();
</script>
