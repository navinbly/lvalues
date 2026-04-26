<?php if(get_frontend_settings('recaptcha_status')): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$registration_type = $this->input->get('tutor') ? 'tutor' : 'student';
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
?>
<style>
.registration-switch { display:flex; gap:10px; }
.registration-toggle { flex:1 1 0; border-radius:10px !important; font-weight:600; transition:all .2s ease; }
.registration-toggle.active { background:#6f42f5; border-color:#6f42f5; color:#fff; box-shadow:0 8px 18px rgba(111,66,245,.18); }
.registration-toggle:not(.active){ background:#fff; color:#6f42f5; }
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
</style>
<section class="sign-up my-5 pt-5">
<div class="container"><div class="row">
<div class="col-lg-7 col-md-6 col-sm-12 col-12 text-center"><img loading="lazy" width="65%" src="<?php echo site_url('assets/frontend/default-new/image/login-security.gif') ?>"></div>
<div class="col-lg-5 col-md-6 col-sm-12 col-12"><div class="sing-up-right">
<h3><?php echo get_phrase('Sign Up'); ?><span>!</span></h3>
<p><?php echo get_phrase('Explore, learn, and grow with us. Enjoy a seamless and enriching educational journey. Lets begin!') ?></p>
<form id="signup-form" action="<?php echo site_url('login/register') ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="registration_type" id="registration_type" value="<?php echo html_escape($registration_type); ?>">
<input type="hidden" name="tutor_lat" id="tutor_lat" value="<?php echo set_value('tutor_lat'); ?>">
<input type="hidden" name="tutor_lng" id="tutor_lng" value="<?php echo set_value('tutor_lng'); ?>">
<input type="hidden" name="tutor_location" id="tutor_location" value="<?php echo set_value('tutor_location'); ?>">
<div class="mb-4"><div class="registration-switch" role="group" aria-label="Registration type"><button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'student' ? 'active' : ''; ?>" data-type="student">Student</button><button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'tutor' ? 'active' : ''; ?>" data-type="tutor">Tutor</button></div></div>
<div class="mb-4"><h5><?php echo get_phrase('First Name'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('first_name') ? 'is-invalid' : ''; ?>" id="first_name" type="text" name="first_name" placeholder="Enter your first name" value="<?php echo set_value('first_name'); ?>" required><?php if(form_error('first_name')): ?><div class="invalid-feedback d-block"><?php echo form_error('first_name'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Last Name'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('last_name') ? 'is-invalid' : ''; ?>" type="text" name="last_name" placeholder="Enter your last name" value="<?php echo set_value('last_name'); ?>" required><?php if(form_error('last_name')): ?><div class="invalid-feedback d-block"><?php echo form_error('last_name'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Your email'); ?></h5><div class="position-relative"><i class="fa-solid fa-user"></i><input class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>" type="email" name="email" placeholder="Enter your email" value="<?php echo set_value('email'); ?>" required><?php if(form_error('email')): ?><div class="invalid-feedback d-block"><?php echo form_error('email'); ?></div><?php endif; ?></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Password'); ?></h5><div class="position-relative"><i class="fa-solid fa-key"></i><i class="fa-solid fas fa-eye cursor-pointer" onclick="if($('#password').attr('type')=='text'){$('#password').attr('type','password');}else{$('#password').attr('type','text');} $(this).toggleClass('fa-eye'); $(this).toggleClass('fa-eye-slash');" style="right:20px; left:unset;"></i><input class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>" id="password" type="password" name="password" placeholder="Enter password" required><?php if(form_error('password')): ?><div class="invalid-feedback d-block"><?php echo form_error('password'); ?></div><?php endif; ?><small class="text-muted d-block mt-2">Use at least 8 characters with uppercase, lowercase, number, and special character.New password must be different from your previous password.</small</div></div>
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
            <input class="form-control <?php echo form_error('phone_number') ? 'is-invalid' : ''; ?>" id="phone_number" type="text" name="phone_number" placeholder="<?php echo get_phrase('Enter your phone number'); ?>" value="<?php echo set_value('phone_number'); ?>" required>
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
<label class="taxonomy-label" for="student_class_id">Current Class / Level</label>
<select class="form-control taxonomy-select student-taxonomy-select <?php echo form_error('student_class_id') ? 'is-invalid' : ''; ?>" name="student_class_id" id="student_class_id">
<option value="">Select class / level</option>
<?php foreach ($tutor_registration_tree as $category_item): ?>
    <?php foreach (($category_item['classes'] ?? []) as $class_item): ?>
        <option value="<?php echo html_escape($class_item['id']); ?>" data-category="<?php echo html_escape($category_item['id']); ?>" <?php echo $selected_student_class_id === (string)$class_item['id'] ? 'selected' : ''; ?>><?php echo html_escape($category_item['name'].' / '.$class_item['name']); ?></option>
    <?php endforeach; ?>
<?php endforeach; ?>
</select>
<?php if(form_error('student_class_id')): ?><div class="invalid-feedback d-block"><?php echo form_error('student_class_id'); ?></div><?php endif; ?>
</div>
<div class="taxonomy-block">
<label class="taxonomy-label" for="student_subject_interest_id">Subject Interest <small class="text-muted">Optional</small></label>
<select class="form-control taxonomy-select student-taxonomy-select" name="student_subject_interest_id" id="student_subject_interest_id">
<option value="">Any / Not sure</option>
<?php foreach ($tutor_registration_tree as $category_item): ?>
    <?php foreach (($category_item['classes'] ?? []) as $class_item): ?>
        <?php foreach (($class_item['subjects'] ?? []) as $subject_item): ?>
            <option value="<?php echo html_escape($subject_item['id']); ?>" data-class="<?php echo html_escape($class_item['id']); ?>" <?php echo $selected_student_subject_id === (string)$subject_item['id'] ? 'selected' : ''; ?>><?php echo html_escape($class_item['name'].' / '.$subject_item['name']); ?></option>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endforeach; ?>
</select>
</div>
<div class="row g-2">
<div class="col-md-6 mb-2"><label class="taxonomy-label">Current Level Label</label><input type="text" class="form-control" name="student_current_level_label" placeholder="Example: Class 2, B.Tech 1st Year" value="<?php echo set_value('student_current_level_label'); ?>"></div>
<div class="col-md-6 mb-2"><label class="taxonomy-label">Academic Year</label><input type="text" class="form-control" name="student_academic_year" placeholder="Example: 2026-27" value="<?php echo set_value('student_academic_year', date('Y')); ?>"></div>
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
<div class="mb-4"><h5>Mode</h5><select class="form-control <?php echo form_error('tutor_teaching_mode') ? 'is-invalid' : ''; ?>" name="tutor_teaching_mode" id="tutor_teaching_mode"><option value="">Select mode</option><option value="online" <?php echo set_select('tutor_teaching_mode', 'online'); ?>>Online</option><option value="offline" <?php echo set_select('tutor_teaching_mode', 'offline'); ?>>Offline</option><option value="both" <?php echo set_select('tutor_teaching_mode', 'both'); ?>>Both</option></select><?php if(form_error('tutor_teaching_mode')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_teaching_mode'); ?></div><?php endif; ?></div>
<div class="mb-4"><h5>Address Details</h5><div class="browser-location-box mb-3"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2"><div><div class="status-badge" id="browser-location-badge"><i class="fa-solid fa-location-dot"></i> Location verification pending</div><small id="location-help" class="text-muted d-block mt-2">To complete tutor registration, please verify your current device location and then enter your address details below.</small></div><button type="button" class="btn btn-outline-primary btn-sm" id="refresh-location">Verify current location</button></div></div><div class="location-grid"><div class="full-width"><div class="position-relative"><i class="fa-solid fa-location-dot"></i><input class="form-control <?php echo form_error('tutor_address_line1') ? 'is-invalid' : ''; ?>" id="tutor_address_line1" type="text" name="tutor_address_line1" placeholder="House no, street, area" value="<?php echo set_value('tutor_address_line1'); ?>"></div><?php if(form_error('tutor_address_line1')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_address_line1'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_city') ? 'is-invalid' : ''; ?>" id="tutor_city" type="text" name="tutor_city" placeholder="City" value="<?php echo set_value('tutor_city'); ?>"><?php if(form_error('tutor_city')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_city'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_state') ? 'is-invalid' : ''; ?>" id="tutor_state" type="text" name="tutor_state" placeholder="State" value="<?php echo set_value('tutor_state'); ?>"><?php if(form_error('tutor_state')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_state'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_country') ? 'is-invalid' : ''; ?>" id="tutor_country" type="text" name="tutor_country" placeholder="Country" value="<?php echo set_value('tutor_country'); ?>"><?php if(form_error('tutor_country')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_country'); ?></div><?php endif; ?></div><div><input class="form-control <?php echo form_error('tutor_pincode') ? 'is-invalid' : ''; ?>" id="tutor_pincode" type="text" name="tutor_pincode" placeholder="Pincode" value="<?php echo set_value('tutor_pincode'); ?>"><?php if(form_error('tutor_pincode')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_pincode'); ?></div><?php endif; ?></div></div><?php if(form_error('tutor_lat')): ?><div class="invalid-feedback d-block mt-2"><?php echo form_error('tutor_lat'); ?></div><?php endif; ?><?php if(form_error('tutor_lng')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_lng'); ?></div><?php endif; ?></div>
<div class="mb-4"><h5>Fees</h5><div class="mb-3"><div class="form-check"><input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_hour" value="per_hour" <?php echo set_radio('fee_type', 'per_hour', set_value('fee_type') !== 'per_subject'); ?>><label class="form-check-label" for="fee_type_per_hour">Per Hour</label></div><div class="form-check"><input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_subject" value="per_subject" <?php echo set_radio('fee_type', 'per_subject'); ?>><label class="form-check-label" for="fee_type_per_subject">Per Subject</label></div><?php if(form_error('fee_type')): ?><div class="invalid-feedback d-block"><?php echo form_error('fee_type'); ?></div><?php endif; ?></div><div id="per-hour-box" class="fee-box"><input class="form-control <?php echo form_error('tutor_hourly_fee') ? 'is-invalid' : ''; ?>" id="tutor_hourly_fee" type="number" min="0" step="0.01" name="tutor_hourly_fee" placeholder="Enter fee per hour" value="<?php echo set_value('tutor_hourly_fee'); ?>"><?php if(form_error('tutor_hourly_fee')): ?><div class="invalid-feedback d-block"><?php echo form_error('tutor_hourly_fee'); ?></div><?php endif; ?></div><div id="per-subject-box" class="fee-box"><div id="subject-fees-wrapper"></div><?php if(form_error('subject_fee_amount[]')): ?><div class="invalid-feedback d-block"><?php echo form_error('subject_fee_amount[]'); ?></div><?php endif; ?><button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-subject-fee-row">Add Subject Fee</button></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Document'); ?> <small>(doc, docs, pdf, txt, png, jpg, jpeg)</small></h5><div class="position-relative"><input class="form-control" id="document" type="file" name="document"><small><?php echo get_phrase('Provide some documents about your qualifications'); ?></small></div></div>
<div class="mb-4"><h5><?php echo get_phrase('Message'); ?></h5><div class="position-relative"><textarea class="form-control" name="message" rows="4"><?php echo set_value('message'); ?></textarea></div></div>
</div>
<?php endif; ?>
<?php if(get_frontend_settings('recaptcha_status')): ?><div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div><?php endif; ?>
<div class="log-in"><button type="submit" class="btn btn-primary" id="signup-submit-btn"><?php echo get_phrase('Sign Up') ?></button></div>
</form>
<div class="another text-center"><p><?php echo get_phrase('Already you have an account?') ?> <a href="<?php echo site_url('login') ?>"><?php echo get_phrase('Log In') ?></a></p><h5><?php echo get_phrase('Or') ?></h5></div>
<div class="social-media"><div class="row"><div class="col-md-12 text-center"><?php if(get_settings('fb_social_login')) include "facebook_login.php"; ?></div></div></div>
</div></div></div></div></section>
<script>
(function() {
const registrationInput = document.getElementById('registration_type');
const tutorFields = document.getElementById('tutor-fields');
const studentFields = document.getElementById('student-fields');
const toggles = document.querySelectorAll('.registration-toggle');
const locationHelp = document.getElementById('location-help');
const locationBadge = document.getElementById('browser-location-badge');
const tutorLocation = document.getElementById('tutor_location');
const tutorLat = document.getElementById('tutor_lat');
const tutorLng = document.getElementById('tutor_lng');
const refreshLocationBtn = document.getElementById('refresh-location');
const submitBtn = document.getElementById('signup-submit-btn');
const subjectFeesWrapper = document.getElementById('subject-fees-wrapper');
const addSubjectFeeBtn = document.getElementById('add-subject-fee-row');
const feeTypeRadios = document.querySelectorAll('.fee-type-radio');
const perHourBox = document.getElementById('per-hour-box');
const perSubjectBox = document.getElementById('per-subject-box');
const categorySelector = document.getElementById('category_selector');
const classSelector = document.getElementById('class_selector');
const subjectSelector = document.getElementById('subject_selector');
const studentCategorySelector = document.getElementById('student_category_id');
const studentClassSelector = document.getElementById('student_class_id');
const studentSubjectSelector = document.getElementById('student_subject_interest_id');
const registrationTree = <?php echo json_encode($tutor_registration_tree); ?>;
let oldSelectedClassIds = <?php echo json_encode(array_values($selected_class_ids)); ?>;
let oldSelectedSubjectIds = <?php echo json_encode(array_values($selected_subject_ids)); ?>;
const addressInputs = ['tutor_address_line1','tutor_city','tutor_state','tutor_country','tutor_pincode'].map(id => document.getElementById(id)).filter(Boolean);
const oldFeeNames = <?php echo json_encode((array)$this->input->post('subject_fee_name')); ?>;
const oldFeeAmounts = <?php echo json_encode((array)$this->input->post('subject_fee_amount')); ?>;
function setRegistrationType(type){ registrationInput.value = type; toggles.forEach(btn => btn.classList.toggle('active', btn.dataset.type === type)); if (tutorFields){ tutorFields.classList.toggle('d-none', type !== 'tutor'); toggleTutorRequired(type === 'tutor'); } if (studentFields){ studentFields.classList.toggle('d-none', type !== 'student'); toggleStudentRequired(type === 'student'); } if (type === 'tutor') { syncSelectors(); updateCombinedLocation(); } if (type === 'student') { syncStudentSelectors(); } updateSubmitState(); }

function toggleTutorRequired(isTutor){
    ['tutor_teaching_mode','document','tutor_address_line1','tutor_city','tutor_country'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (isTutor) el.setAttribute('required','required');
        else el.removeAttribute('required');
    });

    if (tutorLat && tutorLng){
        if (isTutor){
            tutorLat.setAttribute('required','required');
            tutorLng.setAttribute('required','required');
        } else {
            tutorLat.removeAttribute('required');
            tutorLng.removeAttribute('required');
        }
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
function syncStudentSelectors(){
    if(!studentCategorySelector || !studentClassSelector || !studentSubjectSelector) return;
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
function updateSubmitState(){ if(!submitBtn) return; if(registrationInput.value === 'tutor'){ submitBtn.disabled = !hasVerifiedLocation(); } else { submitBtn.disabled = false; } }
function setLocationStatus(type, text, helpText){ if(locationBadge){ locationBadge.classList.remove('is-success','is-warning'); if(type==='success') locationBadge.classList.add('is-success'); if(type==='warning') locationBadge.classList.add('is-warning'); locationBadge.innerHTML = '<i class="fa-solid fa-location-dot"></i> ' + text; } if(locationHelp && helpText){ locationHelp.textContent = helpText; } updateSubmitState(); }
function detectLocation(){ if(!tutorFields || registrationInput.value !== 'tutor') return; if(!navigator.geolocation){ setLocationStatus('warning','Browser location not supported','This browser does not support location access. Please use a supported browser for tutor signup.'); return; } setLocationStatus('','Location verification in progress','Please allow the browser permission prompt so we can verify your current device location.'); navigator.geolocation.getCurrentPosition(function(position){ tutorLat.value = position.coords.latitude; tutorLng.value = position.coords.longitude; setLocationStatus('success','Location verified successfully','Your current device location has been verified. Please complete your address details below.'); }, function(){ tutorLat.value=''; tutorLng.value=''; setLocationStatus('warning','Location verification required','Please use the Verify current location button and allow browser access to continue tutor registration.'); }, { enableHighAccuracy:true, timeout:15000, maximumAge:0 }); }
function setPlaceholder(selectEl, text){ selectEl.innerHTML = ''; const opt = document.createElement('option'); opt.textContent = text; opt.disabled = true; selectEl.appendChild(opt); }
function syncSelectors(){ if(!categorySelector || !classSelector || !subjectSelector) return; const selectedCategoryIds = Array.from(categorySelector.selectedOptions).map(option => option.value); const currentClassIds = new Set(Array.from(classSelector.selectedOptions).map(o => o.value).concat(oldSelectedClassIds)); const currentSubjectIds = new Set(Array.from(subjectSelector.selectedOptions).map(o => o.value).concat(oldSelectedSubjectIds)); classSelector.innerHTML=''; subjectSelector.innerHTML=''; if (!selectedCategoryIds.length){ setPlaceholder(classSelector, 'Select category first'); setPlaceholder(subjectSelector, 'Select class/course group first'); oldSelectedClassIds = []; oldSelectedSubjectIds = []; return; }
let classCount = 0; registrationTree.forEach(categoryItem => { if (!selectedCategoryIds.includes(categoryItem.id)) return; (categoryItem.classes || []).forEach(classItem => { const option = document.createElement('option'); option.value = classItem.id; option.textContent = categoryItem.name + ' / ' + classItem.name; if (currentClassIds.has(classItem.id)) option.selected = true; classSelector.appendChild(option); classCount++; }); }); if (!classCount){ setPlaceholder(classSelector, 'No class/course group found'); setPlaceholder(subjectSelector, 'No subjects found'); oldSelectedClassIds = []; oldSelectedSubjectIds = []; return; }
const effectiveClassIds = Array.from(classSelector.selectedOptions).map(option => option.value); let subjectCount = 0; registrationTree.forEach(categoryItem => { if (!selectedCategoryIds.includes(categoryItem.id)) return; (categoryItem.classes || []).forEach(classItem => { if (!effectiveClassIds.includes(classItem.id)) return; (classItem.subjects || []).forEach(subjectItem => { const option = document.createElement('option'); option.value = subjectItem.id; option.textContent = classItem.name + ' / ' + subjectItem.name; if (currentSubjectIds.has(subjectItem.id)) option.selected = true; subjectSelector.appendChild(option); subjectCount++; }); }); }); if (!subjectCount){ setPlaceholder(subjectSelector, 'No subject found for selected class'); }
oldSelectedClassIds = []; oldSelectedSubjectIds = []; }
function toggleFeeBox(){ if(!perHourBox || !perSubjectBox || !subjectFeesWrapper) return; const feeType = document.querySelector('.fee-type-radio:checked')?.value || 'per_hour'; const isTutor = registrationInput.value === 'tutor'; perHourBox.style.display = feeType === 'per_hour' && isTutor ? 'block' : 'none'; perSubjectBox.style.display = feeType === 'per_subject' && isTutor ? 'block' : 'none'; const hourlyInput = document.getElementById('tutor_hourly_fee'); if (hourlyInput){ if(feeType === 'per_hour' && isTutor) hourlyInput.setAttribute('required','required'); else hourlyInput.removeAttribute('required'); }
subjectFeesWrapper.querySelectorAll('input').forEach(el => { if(feeType === 'per_subject' && isTutor) el.setAttribute('required','required'); else el.removeAttribute('required'); }); }
function createSubjectFeeRow(name='', amount=''){ if(!subjectFeesWrapper) return; const row=document.createElement('div'); row.className='row g-2 align-items-center mb-2 subject-fee-row'; row.innerHTML=`<div class="col-6"><input type="text" class="form-control" name="subject_fee_name[]" placeholder="Subject name" value="${escapeHtml(name)}"></div><div class="col-4"><input type="number" min="0" step="0.01" class="form-control" name="subject_fee_amount[]" placeholder="Fee" value="${escapeHtml(amount)}"></div><div class="col-2"><button type="button" class="btn btn-outline-danger w-100 delete-subject-fee">Delete</button></div>`; subjectFeesWrapper.appendChild(row); row.querySelector('.delete-subject-fee').addEventListener('click', function(){ row.remove(); if(!subjectFeesWrapper.querySelector('.subject-fee-row')) createSubjectFeeRow(); toggleFeeBox(); }); toggleFeeBox(); }
function escapeHtml(value){ return String(value ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
toggles.forEach(btn => btn.addEventListener('click', function(){ setRegistrationType(this.dataset.type); })); if(categorySelector){ categorySelector.addEventListener('change', syncSelectors); } if(classSelector){ classSelector.addEventListener('change', syncSelectors); } if(studentCategorySelector){ studentCategorySelector.addEventListener('change', syncStudentSelectors); } if(studentClassSelector){ studentClassSelector.addEventListener('change', syncStudentSelectors); } addressInputs.forEach(input => input.addEventListener('input', updateCombinedLocation)); if(refreshLocationBtn){ refreshLocationBtn.addEventListener('click', detectLocation); } if(addSubjectFeeBtn){ addSubjectFeeBtn.addEventListener('click', function(){ createSubjectFeeRow(); }); } feeTypeRadios.forEach(radio => radio.addEventListener('change', toggleFeeBox)); if(subjectFeesWrapper){ if(oldFeeNames.length || oldFeeAmounts.length){ const total = Math.max(oldFeeNames.length, oldFeeAmounts.length); for(let i=0;i<total;i++) createSubjectFeeRow(oldFeeNames[i] || '', oldFeeAmounts[i] || ''); } else { createSubjectFeeRow(); } }
const signupForm = document.getElementById('signup-form'); if(signupForm){ signupForm.addEventListener('submit', function(event){ if(registrationInput.value === 'tutor' && !hasVerifiedLocation()){ event.preventDefault(); setLocationStatus('warning','Location verification required','Please click Verify current location and allow browser access before submitting tutor registration.'); } }); }
updateCombinedLocation(); syncSelectors(); setRegistrationType(registrationInput.value || 'student'); toggleFeeBox(); updateSubmitState();
})();
</script>
