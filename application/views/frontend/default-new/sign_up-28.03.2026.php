<?php if(get_frontend_settings('recaptcha_status')): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<?php
$registration_type = $this->input->get('tutor') ? 'tutor' : 'student';
if (set_value('registration_type')) {
    $registration_type = set_value('registration_type');
}
$categories = $this->crud_model->get_categories()->result_array();
$selected_subject_ids = (array)$this->input->post('tutor_subject_ids');
?>

<section class="sign-up my-5 pt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 col-md-6 col-sm-12 col-12 text-center">
                <img loading="lazy" width="65%" src="<?php echo site_url('assets/frontend/default-new/image/login-security.gif') ?>">
            </div>
            <div class="col-lg-5 col-md-6 col-sm-12 col-12">
                <div class="sing-up-right">
                    <h3><?php echo get_phrase('Sign Up'); ?><span>!</span></h3>
                    <p><?php echo get_phrase('Explore, learn, and grow with us. Enjoy a seamless and enriching educational journey. Lets begin!') ?></p>

                    <form id="signup-form" action="<?php echo site_url('login/register') ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="registration_type" id="registration_type" value="<?php echo html_escape($registration_type); ?>">
                        <input type="hidden" name="tutor_lat" id="tutor_lat" value="<?php echo set_value('tutor_lat'); ?>">
                        <input type="hidden" name="tutor_lng" id="tutor_lng" value="<?php echo set_value('tutor_lng'); ?>">

                        <div class="mb-4">
                            <div class="btn-group w-100 registration-switch" role="group" aria-label="Registration type">
                                <button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'student' ? 'active' : ''; ?>" data-type="student">Student</button>
                                <button type="button" class="btn btn-outline-primary registration-toggle <?php echo $registration_type === 'tutor' ? 'active' : ''; ?>" data-type="tutor">Tutor</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5><?php echo get_phrase('First Name'); ?></h5>
                            <div class="position-relative">
                                <i class="fa-solid fa-user"></i>
                                <input class="form-control <?php echo form_error('first_name') ? 'is-invalid' : ''; ?>"
                                    id="first_name" type="text" name="first_name"
                                    placeholder="Enter your first name"
                                    value="<?php echo set_value('first_name'); ?>" required>
                                <?php if(form_error('first_name')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('first_name'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5><?php echo get_phrase('Last Name'); ?></h5>
                            <div class="position-relative">
                                <i class="fa-solid fa-user"></i>
                                <input class="form-control <?php echo form_error('last_name') ? 'is-invalid' : ''; ?>"
                                    type="text" name="last_name"
                                    placeholder="Enter your last name"
                                    value="<?php echo set_value('last_name'); ?>" required>
                                <?php if(form_error('last_name')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('last_name'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5><?php echo get_phrase('Your email'); ?></h5>
                            <div class="position-relative">
                                <i class="fa-solid fa-user"></i>
                                <input class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                                    type="email" name="email"
                                    placeholder="Enter your email"
                                    value="<?php echo set_value('email'); ?>" required>
                                <?php if(form_error('email')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('email'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5><?php echo get_phrase('Password'); ?></h5>
                            <div class="position-relative">
                                <i class="fa-solid fa-key"></i>
                                <i class="fa-solid fas fa-eye cursor-pointer"
                                   onclick="if($('#password').attr('type')=='text'){$('#password').attr('type','password');}else{$('#password').attr('type','text');} $(this).toggleClass('fa-eye'); $(this).toggleClass('fa-eye-slash');"
                                   style="right: 20px; left: unset;"></i>
                                <input class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>"
                                   id="password"
                                   type="password"
                                   name="password"
                                   placeholder="Enter password"
                                   required>
                                <?php if(form_error('password')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('password'); ?></div>
                                <?php endif; ?>
                                <small class="text-muted d-block mt-2">Use at least 8 characters with uppercase, lowercase, number, and special character.</small>
                            </div>
                        </div>

                        <?php if(get_settings('allow_instructor')): ?>
                        <div id="tutor-fields" class="<?php echo $registration_type === 'tutor' ? '' : 'd-none'; ?>">
                            <div class="mb-4">
                                <h5>Subject(s)</h5>
                                <select class="form-control <?php echo form_error('tutor_subject_ids[]') ? 'is-invalid' : ''; ?>" name="tutor_subject_ids[]" id="tutor_subject_ids" multiple>
                                    <?php foreach ($categories as $cat): ?>
                                        <optgroup label="<?php echo html_escape($cat['name']); ?>">
                                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo in_array((string)$cat['id'], $selected_subject_ids, true) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($cat['name']); ?>
                                            </option>
                                            <?php foreach ($this->crud_model->get_sub_categories($cat['id']) as $sub): ?>
                                                <option value="<?php echo (int)$sub['id']; ?>" <?php echo in_array((string)$sub['id'], $selected_subject_ids, true) ? 'selected' : ''; ?>>
                                                    <?php echo html_escape($sub['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <?php if(form_error('tutor_subject_ids[]')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('tutor_subject_ids[]'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <h5>Mode</h5>
                                <select class="form-control <?php echo form_error('tutor_teaching_mode') ? 'is-invalid' : ''; ?>" name="tutor_teaching_mode" id="tutor_teaching_mode">
                                    <option value="">Select mode</option>
                                    <option value="online" <?php echo set_select('tutor_teaching_mode', 'online'); ?>>Online</option>
                                    <option value="offline" <?php echo set_select('tutor_teaching_mode', 'offline'); ?>>Offline</option>
                                    <option value="both" <?php echo set_select('tutor_teaching_mode', 'both'); ?>>Both</option>
                                </select>
                                <?php if(form_error('tutor_teaching_mode')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('tutor_teaching_mode'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <h5>Location</h5>
                                <div class="position-relative">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <input class="form-control <?php echo form_error('tutor_location') ? 'is-invalid' : ''; ?>"
                                           id="tutor_location"
                                           type="text"
                                           name="tutor_location"
                                           placeholder="Current location"
                                           value="<?php echo set_value('tutor_location'); ?>"
                                           readonly>
                                </div>
                                <small id="location-help" class="text-muted d-block mt-2">We will detect your current device location for tutor registration.</small>
                                <?php if(form_error('tutor_location')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('tutor_location'); ?></div>
                                <?php endif; ?>
                                <?php if(form_error('tutor_lat')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('tutor_lat'); ?></div>
                                <?php endif; ?>
                                <?php if(form_error('tutor_lng')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('tutor_lng'); ?></div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-link p-0 mt-2" id="refresh-location">Detect location again</button>
                            </div>

                            <div class="mb-4">
                                <h5>Fees</h5>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_hour" value="per_hour" <?php echo set_radio('fee_type', 'per_hour', set_value('fee_type') !== 'per_subject'); ?>>
                                        <label class="form-check-label" for="fee_type_per_hour">Per Hour</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input fee-type-radio" type="radio" name="fee_type" id="fee_type_per_subject" value="per_subject" <?php echo set_radio('fee_type', 'per_subject'); ?>>
                                        <label class="form-check-label" for="fee_type_per_subject">Per Subject</label>
                                    </div>
                                    <?php if(form_error('fee_type')): ?>
                                        <div class="invalid-feedback d-block"><?php echo form_error('fee_type'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div id="per-hour-box" class="fee-box">
                                    <input class="form-control <?php echo form_error('tutor_hourly_fee') ? 'is-invalid' : ''; ?>"
                                           id="tutor_hourly_fee"
                                           type="number"
                                           min="0"
                                           step="0.01"
                                           name="tutor_hourly_fee"
                                           placeholder="Enter fee per hour"
                                           value="<?php echo set_value('tutor_hourly_fee'); ?>">
                                    <?php if(form_error('tutor_hourly_fee')): ?>
                                        <div class="invalid-feedback d-block"><?php echo form_error('tutor_hourly_fee'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div id="per-subject-box" class="fee-box">
                                    <div id="subject-fees-wrapper"></div>
                                    <?php if(form_error('subject_fee_amount[]')): ?>
                                        <div class="invalid-feedback d-block"><?php echo form_error('subject_fee_amount[]'); ?></div>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-subject-fee-row">Add Subject Fee</button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5><?php echo get_phrase('Phone'); ?></h5>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <select class="form-control <?php echo form_error('phone_country_code') ? 'is-invalid' : ''; ?>" name="phone_country_code" id="phone_country_code">
                                            <option value="+91" <?php echo set_select('phone_country_code', '+91', set_value('phone_country_code') === '' || set_value('phone_country_code') === '+91'); ?>>+91 (IN)</option>
                                            <option value="+1" <?php echo set_select('phone_country_code', '+1'); ?>>+1 (US)</option>
                                            <option value="+44" <?php echo set_select('phone_country_code', '+44'); ?>>+44 (UK)</option>
                                            <option value="+61" <?php echo set_select('phone_country_code', '+61'); ?>>+61 (AU)</option>
                                            <option value="+971" <?php echo set_select('phone_country_code', '+971'); ?>>+971 (UAE)</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <input class="form-control <?php echo form_error('phone_number') ? 'is-invalid' : ''; ?>"
                                               id="phone_number"
                                               type="text"
                                               name="phone_number"
                                               placeholder="<?php echo get_phrase('Enter your phone number'); ?>"
                                               value="<?php echo set_value('phone_number'); ?>">
                                    </div>
                                </div>
                                <?php if(form_error('phone_number')): ?>
                                    <div class="invalid-feedback d-block"><?php echo form_error('phone_number'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <h5><?php echo get_phrase('Document'); ?> <small>(doc, docs, pdf, txt, png, jpg, jpeg)</small></h5>
                                <div class="position-relative">
                                    <input class="form-control" id="document" type="file" name="document">
                                    <small><?php echo get_phrase('Provide some documents about your qualifications'); ?></small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5><?php echo get_phrase('Message'); ?></h5>
                                <div class="position-relative">
                                    <textarea class="form-control" name="message" rows="4"><?php echo set_value('message'); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(get_frontend_settings('recaptcha_status')): ?>
                            <div class="g-recaptcha" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                        <?php endif; ?>

                        <div class="log-in">
                            <button type="submit" class="btn btn-primary"><?php echo get_phrase('Sign Up') ?></button>
                        </div>
                    </form>

                    <div class="another text-center">
                        <p>
                            <?php echo get_phrase('Already you have an account?') ?>
                            <a href="<?php echo site_url('login') ?>"><?php echo get_phrase('Log In') ?></a>
                        </p>
                        <h5><?php echo get_phrase('Or') ?></h5>
                    </div>

                    <div class="social-media">
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <?php if(get_settings('fb_social_login')) include "facebook_login.php"; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    const registrationInput = document.getElementById('registration_type');
    const tutorFields = document.getElementById('tutor-fields');
    const toggles = document.querySelectorAll('.registration-toggle');
    const locationHelp = document.getElementById('location-help');
    const tutorLocation = document.getElementById('tutor_location');
    const tutorLat = document.getElementById('tutor_lat');
    const tutorLng = document.getElementById('tutor_lng');
    const refreshLocationBtn = document.getElementById('refresh-location');
    const subjectFeesWrapper = document.getElementById('subject-fees-wrapper');
    const addSubjectFeeBtn = document.getElementById('add-subject-fee-row');
    const feeTypeRadios = document.querySelectorAll('.fee-type-radio');
    const perHourBox = document.getElementById('per-hour-box');
    const perSubjectBox = document.getElementById('per-subject-box');

    const oldFeeNames = <?php echo json_encode((array)$this->input->post('subject_fee_name')); ?>;
    const oldFeeAmounts = <?php echo json_encode((array)$this->input->post('subject_fee_amount')); ?>;

    function setRegistrationType(type) {
        registrationInput.value = type;
        toggles.forEach(btn => btn.classList.toggle('active', btn.dataset.type === type));
        if (tutorFields) {
            tutorFields.classList.toggle('d-none', type !== 'tutor');
            toggleTutorRequired(type === 'tutor');
        }
        if (type === 'tutor') {
            detectLocation();
        }
    }

    function toggleTutorRequired(isTutor) {
        const ids = ['tutor_subject_ids', 'tutor_teaching_mode', 'phone_country_code', 'phone_number', 'document'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (isTutor) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });

        if (tutorLocation && tutorLat && tutorLng) {
            if (isTutor) {
                tutorLocation.setAttribute('required', 'required');
                tutorLat.setAttribute('required', 'required');
                tutorLng.setAttribute('required', 'required');
            } else {
                tutorLocation.removeAttribute('required');
                tutorLat.removeAttribute('required');
                tutorLng.removeAttribute('required');
            }
        }
        toggleFeeBox();
    }

    function detectLocation() {
        if (!tutorFields || registrationInput.value !== 'tutor') return;
        if (!navigator.geolocation) {
            if (locationHelp) locationHelp.textContent = 'Location is not supported in this browser. Please use a supported browser for tutor signup.';
            return;
        }
        if (locationHelp) locationHelp.textContent = 'Detecting current device location...';
        navigator.geolocation.getCurrentPosition(function(position) {
            tutorLat.value = position.coords.latitude;
            tutorLng.value = position.coords.longitude;
            tutorLocation.value = 'Lat: ' + position.coords.latitude.toFixed(6) + ', Lng: ' + position.coords.longitude.toFixed(6);
            if (locationHelp) locationHelp.textContent = 'Current device location captured successfully.';
        }, function() {
            tutorLat.value = '';
            tutorLng.value = '';
            tutorLocation.value = '';
            if (locationHelp) locationHelp.textContent = 'Please allow browser location access to complete tutor registration.';
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    }

    function toggleFeeBox() {
        if (!perHourBox || !perSubjectBox || !subjectFeesWrapper) return;
        const feeType = document.querySelector('.fee-type-radio:checked')?.value || 'per_hour';
        const isTutor = registrationInput.value === 'tutor';
        perHourBox.style.display = feeType === 'per_hour' && isTutor ? 'block' : 'none';
        perSubjectBox.style.display = feeType === 'per_subject' && isTutor ? 'block' : 'none';

        const hourlyInput = document.getElementById('tutor_hourly_fee');
        if (hourlyInput) {
            if (feeType === 'per_hour' && isTutor) {
                hourlyInput.setAttribute('required', 'required');
            } else {
                hourlyInput.removeAttribute('required');
            }
        }

        subjectFeesWrapper.querySelectorAll('input').forEach(el => {
            if (feeType === 'per_subject' && isTutor) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    function createSubjectFeeRow(name = '', amount = '') {
        if (!subjectFeesWrapper) return;
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-center mb-2 subject-fee-row';
        row.innerHTML = `
            <div class="col-6">
                <input type="text" class="form-control" name="subject_fee_name[]" placeholder="Subject name" value="${escapeHtml(name)}">
            </div>
            <div class="col-4">
                <input type="number" min="0" step="0.01" class="form-control" name="subject_fee_amount[]" placeholder="Fee" value="${escapeHtml(amount)}">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger w-100 delete-subject-fee">Delete</button>
            </div>
        `;
        subjectFeesWrapper.appendChild(row);
        row.querySelector('.delete-subject-fee').addEventListener('click', function() {
            row.remove();
            if (!subjectFeesWrapper.querySelector('.subject-fee-row')) {
                createSubjectFeeRow();
            }
            toggleFeeBox();
        });
        toggleFeeBox();
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    toggles.forEach(btn => {
        btn.addEventListener('click', function() {
            setRegistrationType(this.dataset.type);
        });
    });

    if (refreshLocationBtn) {
        refreshLocationBtn.addEventListener('click', function() {
            detectLocation();
        });
    }

    if (addSubjectFeeBtn) {
        addSubjectFeeBtn.addEventListener('click', function() {
            createSubjectFeeRow();
        });
    }

    feeTypeRadios.forEach(radio => radio.addEventListener('change', toggleFeeBox));

    if (subjectFeesWrapper) {
        if (oldFeeNames.length || oldFeeAmounts.length) {
            const total = Math.max(oldFeeNames.length, oldFeeAmounts.length);
            for (let i = 0; i < total; i++) {
                createSubjectFeeRow(oldFeeNames[i] || '', oldFeeAmounts[i] || '');
            }
        } else {
            createSubjectFeeRow();
        }
    }

    setRegistrationType(registrationInput.value || 'student');
    toggleFeeBox();
})();
</script>
