<?php if(get_frontend_settings('recaptcha_status')): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<?php
$login_role = $this->input->get('role') === 'tutor' ? 'tutor' : 'student';
?>
<style>
.lv-auth-page{padding:92px 0 58px;background:#f6f8fc}
.lv-auth-shell{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr);gap:28px;align-items:stretch}
.lv-auth-visual{position:relative;min-height:620px;border-radius:18px;overflow:hidden;background:#13213a;color:#fff}
.lv-auth-visual img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.58}
.lv-auth-visual:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(19,33,58,.18),rgba(19,33,58,.9))}
.lv-auth-visual-content{position:relative;z-index:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;padding:34px}
.lv-auth-visual h1{font-size:36px;line-height:1.15;margin:0 0 12px;font-weight:800;color:#fff;letter-spacing:0}
.lv-auth-visual p{font-size:16px;line-height:1.7;margin:0;color:#e6ebf5;max-width:620px}
.lv-auth-benefits{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:24px}
.lv-auth-benefit{border:1px solid rgba(255,255,255,.22);border-radius:12px;padding:12px;background:rgba(255,255,255,.08);backdrop-filter:blur(8px)}
.lv-auth-benefit strong{display:block;font-size:14px;color:#fff}
.lv-auth-benefit span{display:block;font-size:12px;color:#d5deef;line-height:1.45;margin-top:4px}
.lv-auth-card{background:#fff;border:1px solid #e6eaf2;border-radius:18px;padding:28px;box-shadow:0 18px 50px rgba(21,31,56,.08)}
.lv-auth-card h2{font-size:28px;line-height:1.2;margin:0 0 8px;font-weight:800;color:#1f2a44;letter-spacing:0}
.lv-auth-card>p{margin:0 0 18px;color:#667085;line-height:1.6}
.lv-role-tabs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0 18px}
.lv-role-tab{position:relative;border:1px solid #dce3ef;border-radius:14px;background:#fff;padding:14px 14px 14px 58px;text-align:left;color:#344054;transition:.18s ease;min-height:76px;box-shadow:0 8px 20px rgba(21,31,56,.04)}
.lv-role-tab:hover{border-color:#9aa8ff;transform:translateY(-1px);box-shadow:0 14px 28px rgba(21,31,56,.08)}
.lv-role-tab.active{border-color:#5b6ee1;background:linear-gradient(135deg,#f7f8ff,#eef2ff);box-shadow:0 14px 34px rgba(91,110,225,.16)}
.lv-role-tab__icon{position:absolute;left:14px;top:15px;width:34px;height:34px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;background:#f2f4f7;color:#5b6ee1}
.lv-role-tab.active .lv-role-tab__icon{background:#5b6ee1;color:#fff}
.lv-role-tab strong{display:block;font-size:15px;line-height:1.2;color:#1f2a44}
.lv-role-tab small{display:block;color:#667085;margin-top:5px;line-height:1.35}
.lv-auth-divider{display:flex;align-items:center;gap:12px;margin:18px 0;color:#8a94a8;font-size:13px;font-weight:700}
.lv-auth-divider:before,.lv-auth-divider:after{content:"";height:1px;background:#e5eaf2;flex:1}
.lv-auth-field{margin-bottom:16px}
.lv-auth-field label{display:block;margin-bottom:7px;font-weight:700;color:#344054}
.lv-auth-input{position:relative}
.lv-auth-input .field-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#98a2b3;z-index:1}
.lv-auth-input .toggle-password{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:#667085;z-index:2;background:transparent;border:0;padding:0}
.lv-auth-input input{height:48px;border:1px solid #dce3ef;border-radius:11px;padding-left:46px;padding-right:46px;background:#fbfcff}
.lv-auth-input input:focus{border-color:#5b6ee1;box-shadow:0 0 0 .2rem rgba(91,110,225,.12)}
.lv-auth-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 18px}
.lv-auth-actions a{font-size:13px;font-weight:700}
.lv-auth-submit{width:100%;height:48px;border-radius:11px;font-weight:800}
.lv-next-panel{border:1px solid #e6eaf2;border-radius:14px;padding:14px;background:#fbfcff;margin-top:18px}
.lv-next-panel h5{font-size:15px;font-weight:800;margin:0 0 8px;color:#1f2a44}
.lv-next-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-top:1px solid #edf1f7}
.lv-next-row:first-of-type{border-top:0}
.lv-next-row span{font-size:13px;color:#667085;line-height:1.4}
.lv-next-row a{white-space:nowrap;font-weight:800}
@media(max-width:991px){.lv-auth-shell{grid-template-columns:1fr}.lv-auth-visual{min-height:420px}.lv-auth-benefits{grid-template-columns:1fr}}
@media(max-width:575px){.lv-auth-page{padding-top:70px}.lv-auth-card{padding:20px}.lv-role-tabs{grid-template-columns:1fr}.lv-auth-actions{align-items:flex-start;flex-direction:column}.lv-next-row{align-items:flex-start;flex-direction:column}}
</style>

<section class="lv-auth-page">
    <div class="container">
        <div class="lv-auth-shell">
            <div class="lv-auth-visual">
                <img loading="eager" src="<?php echo site_url('assets/frontend/default-new/image/login-security.gif'); ?>" alt="Secure Lvalues login">
                <div class="lv-auth-visual-content">
                    <h1>One account for courses, tutors, batches, and progress.</h1>
                    <p>Students continue learning from their dashboard. Approved tutors go straight to teaching tools, batches, question banks, and publishing workflows.</p>
                    <div class="lv-auth-benefits">
                        <div class="lv-auth-benefit"><strong>Students</strong><span>Courses, mock tests, batches, tutor requests.</span></div>
                        <div class="lv-auth-benefit"><strong>Tutors</strong><span>Dashboard after approval, with authoring tools.</span></div>
                        <div class="lv-auth-benefit"><strong>Secure</strong><span>Email/password plus Google or Microsoft when configured.</span></div>
                    </div>
                </div>
            </div>

            <div class="lv-auth-card">
                <h2>Welcome back</h2>
                <p>Choose your path, then sign in. Existing accounts are routed by their approved role automatically.</p>

                <div class="lv-role-tabs" aria-label="Login role shortcut">
                    <button type="button" class="lv-role-tab <?php echo $login_role === 'student' ? 'active' : ''; ?>" data-login-role="student">
                        <span class="lv-role-tab__icon"><i class="fa-solid fa-user-graduate"></i></span>
                        <strong>Student</strong>
                        <small>Learn, attempt tests, track progress.</small>
                    </button>
                    <button type="button" class="lv-role-tab <?php echo $login_role === 'tutor' ? 'active' : ''; ?>" data-login-role="tutor">
                        <span class="lv-role-tab__icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <strong>Tutor</strong>
                        <small>Use this for approved tutor accounts.</small>
                    </button>
                </div>

                <div class="external-auth-top-login">
                    <?php $external_auth_context = 'login'; $external_auth_role = $login_role; include "external_login_buttons.php"; ?>
                </div>

                <div class="lv-auth-divider">or sign in with email</div>

                <form action="<?php echo site_url('login/validate_login'); ?>" method="post">
                    <div class="lv-auth-field">
                        <label for="email"><?php echo get_phrase('Your email'); ?></label>
                        <div class="lv-auth-input">
                            <i class="fa-solid fa-envelope field-icon"></i>
                            <input class="form-control" id="email" type="email" name="email" placeholder="<?php echo get_phrase('Enter your email'); ?>" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="lv-auth-field">
                        <label for="password"><?php echo get_phrase('Password'); ?></label>
                        <div class="lv-auth-input">
                            <i class="fa-solid fa-key field-icon"></i>
                            <button class="toggle-password" type="button" aria-label="Show password" data-toggle-password>
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <input class="form-control" id="password" type="password" name="password" placeholder="<?php echo get_phrase('Enter your password'); ?>" autocomplete="current-password" required>
                        </div>
                    </div>

                    <div class="lv-auth-actions">
                        <span class="text-muted small">Tutor login opens only after admin approval.</span>
                        <a href="<?php echo site_url('login/forgot_password_request'); ?>"><?php echo get_phrase('Forgot password?'); ?></a>
                    </div>

                    <?php if(get_frontend_settings('recaptcha_status')): ?>
                        <div class="g-recaptcha mb-3" data-sitekey="<?php echo get_frontend_settings('recaptcha_sitekey'); ?>"></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary lv-auth-submit"><?php echo get_phrase('Log in'); ?></button>
                </form>

                <div class="lv-next-panel">
                    <h5>New to Lvalues?</h5>
                    <div class="lv-next-row">
                        <span>Create a student account for courses, tutors, tests, and progress.</span>
                        <a href="<?php echo site_url('sign_up'); ?>" data-student-signup>Student signup</a>
                    </div>
                    <div class="lv-next-row">
                        <span>Apply as a tutor. Admin approval is required before tutor dashboard access.</span>
                        <a href="<?php echo site_url('sign_up?tutor=1'); ?>" data-tutor-signup>Tutor signup</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var roleTabs = document.querySelectorAll('[data-login-role]');
    var roleSelect = document.querySelector('.external-auth-top-login [data-external-role]');
    var roleInputs = document.querySelectorAll('.external-auth-top-login [data-external-role-input]');
    var googleLink = document.querySelector('.external-auth-top-login [data-google-base]');
    function setRole(role) {
        role = role === 'tutor' ? 'tutor' : 'student';
        roleTabs.forEach(function(tab) { tab.classList.toggle('active', tab.getAttribute('data-login-role') === role); });
        roleInputs.forEach(function(input) { input.value = role; });
        if (googleLink) {
            googleLink.href = googleLink.getAttribute('data-google-base') + '?registration_type=' + encodeURIComponent(role);
        }
        if (roleSelect) {
            roleSelect.value = role;
            roleSelect.dispatchEvent(new Event('change'));
        }
    }
    roleTabs.forEach(function(tab) {
        tab.addEventListener('click', function() { setRole(tab.getAttribute('data-login-role')); });
    });
    document.querySelectorAll('[data-toggle-password]').forEach(function(button) {
        button.addEventListener('click', function() {
            var input = document.getElementById('password');
            var icon = button.querySelector('i');
            if (!input || !icon) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });
})();
</script>
