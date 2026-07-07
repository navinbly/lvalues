<?php
$CI =& get_instance();
$CI->load->config('oauth', true);
$oauth_providers = $CI->config->item('oauth_providers', 'oauth');
$provider_icons = [
    'google' => 'fa-brands fa-google',
    'entra' => 'fa-brands fa-microsoft',
    'linkedin' => 'fa-brands fa-linkedin-in',
    'github' => 'fa-brands fa-github',
    'apple' => 'fa-brands fa-apple',
];
$provider_names = [
    'google' => 'Google / Gmail',
    'entra' => 'Microsoft',
    'linkedin' => 'LinkedIn',
    'github' => 'GitHub',
    'apple' => 'Apple',
];
function lvalues_auth_provider_ready($provider_key, $provider_config) {
    if (empty($provider_config['client_id']) || empty($provider_config['client_secret'])) {
        return false;
    }
    if ($provider_key === 'entra' && empty($provider_config['tenant_id']) && empty($provider_config['authority'])) {
        return false;
    }
    return true;
}
$ready_oauth_providers = [];
foreach ($oauth_providers as $provider_key => $provider_config) {
    if (lvalues_auth_provider_ready($provider_key, $provider_config)) {
        $ready_oauth_providers[$provider_key] = $provider_config;
    }
}
?>
<style>
#lvaluesAuthModal { z-index:20050; }
body.modal-open .modal-backdrop { z-index:20040; }
.lvalues-auth-modal .modal-dialog { max-width: 520px; }
.lvalues-auth-modal .modal-content { border:0; border-radius:18px; background:#fff !important; color:#26324d; box-shadow:0 24px 70px rgba(20,28,48,.22); overflow:hidden; }
.lvalues-auth-modal .modal-header { border:0; padding:22px 24px 6px; background:#fff; }
.lvalues-auth-modal .modal-body { padding:8px 24px 24px; background:#fff; }
.lvalues-auth-title { margin:0; font-size:24px; font-weight:800; color:#24324a !important; }
.lvalues-auth-subtitle { margin:6px 0 20px; color:#667085; font-size:14px; }
.lvalues-auth-tabs { display:none; }
.lvalues-auth-panel { display:none; }
.lvalues-auth-panel.is-active { display:block; }
.lvalues-auth-role { display:grid; grid-template-columns:110px minmax(0,1fr); gap:10px; align-items:center; margin-bottom:12px; }
.lvalues-auth-role label { margin:0; font-size:13px; font-weight:800; color:#344054; }
.lvalues-auth-role select,
.lvalues-auth-email input { height:44px; border-radius:10px; border:1px solid #dfe4ee; background:#f8f9fc; }
.lvalues-provider-list { display:grid; gap:10px; }
.lvalues-provider-btn { width:100%; min-height:46px; display:flex; align-items:center; justify-content:center; gap:10px; border:1px solid #dfe4ee; border-radius:11px; background:#fff; color:#26324d; font-weight:800; text-decoration:none; transition:all .18s ease; }
.lvalues-provider-btn:hover { border-color:#6f42f5; color:#5632c8; background:#faf8ff; text-decoration:none; }
.lvalues-provider-btn.is-disabled,
.lvalues-provider-btn[disabled] { color:#8f98aa; background:#f4f6fa; border-color:#e3e7ef; cursor:not-allowed; }
.lvalues-auth-divider { display:flex; align-items:center; gap:10px; margin:18px 0; color:#8a94a8; font-size:12px; font-weight:800; text-transform:lowercase; }
.lvalues-auth-divider:before,
.lvalues-auth-divider:after { content:""; flex:1; height:1px; background:#e6e9f2; }
.lvalues-auth-email,
.lvalues-auth-signup-direct { border:1px solid #e6e9f2; border-radius:14px; padding:14px; background:#fbfcff; }
.lvalues-auth-email .btn,
.lvalues-auth-signup-direct .btn { min-height:44px; border-radius:10px; font-weight:800; }
.lvalues-auth-footer { margin-top:14px; color:#667085; font-size:12px; line-height:1.55; text-align:center; }
.lvalues-auth-status { display:none; align-items:center; justify-content:center; gap:8px; margin:12px 0 0; color:#5632c8; font-weight:800; font-size:13px; }
.lvalues-auth-status.is-active { display:flex; }
.lvalues-auth-setup { border:1px solid #f6d08a; background:#fff8e8; color:#805500; border-radius:12px; padding:12px 14px; font-size:13px; line-height:1.55; }
.lvalues-auth-mode-link { border:0; background:transparent; color:#5632c8; font-weight:800; padding:0; }
@media (max-width:575px){
    .lvalues-auth-modal .modal-dialog { margin:12px; }
    .lvalues-auth-role { grid-template-columns:1fr; gap:6px; }
}
</style>

<div class="modal fade lvalues-auth-modal" id="lvaluesAuthModal" tabindex="-1" aria-labelledby="lvaluesAuthModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="lvalues-auth-title" id="lvaluesAuthModalTitle">Login to Lvalues</h5>
                    <p class="lvalues-auth-subtitle">Choose your preferred sign-in method</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="lvalues-auth-panel is-active" data-auth-panel="login">
                    <div class="lvalues-provider-list">
                        <?php if (!empty($ready_oauth_providers)): ?>
                            <?php foreach ($ready_oauth_providers as $provider_key => $provider_config): ?>
                                <a class="lvalues-provider-btn" href="<?php echo site_url('login/oauth/' . $provider_key); ?>" data-auth-loading>
                                    <i class="<?php echo html_escape($provider_icons[$provider_key] ?? 'fa-solid fa-user-lock'); ?>"></i>
                                    Continue with <?php echo html_escape($provider_names[$provider_key] ?? $provider_config['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="lvalues-auth-setup">
                                Social login is ready in the interface. Add Google or Microsoft OAuth keys on the server to activate these buttons. Email login works now.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="lvalues-auth-divider">or continue with email</div>
                    <div class="lvalues-auth-email">
                        <form action="<?php echo site_url('login/validate_login'); ?>" method="post">
                            <div class="mb-3">
                                <input type="email" name="email" class="form-control" placeholder="<?php echo get_phrase('Your email'); ?>" required>
                            </div>
                            <div class="mb-2">
                                <input type="password" name="password" class="form-control" placeholder="<?php echo get_phrase('Password'); ?>" required>
                            </div>
                            <div class="text-end mb-3">
                                <a href="<?php echo site_url('login/forgot_password_request'); ?>" class="text-muted small"><?php echo get_phrase('Forgot password?'); ?></a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo get_phrase('Log in'); ?></button>
                        </form>
                    </div>
                    <p class="lvalues-auth-footer">
                        New to Lvalues?
                        <button type="button" class="lvalues-auth-mode-link" data-switch-auth="signup">Create an account</button>
                    </p>
                </div>

                <div class="lvalues-auth-panel" data-auth-panel="signup">
                    <div class="lvalues-auth-role">
                        <label for="lvaluesAuthRole">Register as</label>
                        <select class="form-select" id="lvaluesAuthRole">
                            <option value="student">Student</option>
                            <option value="tutor">Tutor</option>
                        </select>
                    </div>

                    <div class="lvalues-provider-list">
                        <?php if (!empty($ready_oauth_providers)): ?>
                            <?php foreach ($ready_oauth_providers as $provider_key => $provider_config): ?>
                                <a class="lvalues-provider-btn" href="<?php echo site_url('login/oauth/' . $provider_key); ?>?registration_type=student" data-signup-provider="<?php echo html_escape($provider_key); ?>" data-auth-loading>
                                    <i class="<?php echo html_escape($provider_icons[$provider_key] ?? 'fa-solid fa-user-plus'); ?>"></i>
                                    Continue with <?php echo html_escape($provider_names[$provider_key] ?? $provider_config['label']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="lvalues-auth-setup">
                                Social signup will activate after OAuth keys are configured. Email signup is available now for students and tutors.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="lvalues-auth-divider">or continue with email</div>
                    <div class="lvalues-auth-signup-direct">
                        <a class="btn btn-primary w-100" href="<?php echo site_url('sign_up'); ?>" data-email-signup-link>Continue with Email</a>
                    </div>
                    <p class="lvalues-auth-footer">
                        Already registered?
                        <button type="button" class="lvalues-auth-mode-link" data-switch-auth="login">Log in</button>
                        <br>By continuing, you agree to Lvalues Terms &amp; Conditions and Privacy Policy.
                    </p>
                </div>

                <div class="lvalues-auth-status" data-auth-status>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    Redirecting securely...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('lvaluesAuthModal');
    if (!modal) return;
    const title = document.getElementById('lvaluesAuthModalTitle');
    const panels = modal.querySelectorAll('[data-auth-panel]');
    const status = modal.querySelector('[data-auth-status]');
    const role = document.getElementById('lvaluesAuthRole');
    const signupLinks = modal.querySelectorAll('[data-signup-provider]');
    const emailSignupLink = modal.querySelector('[data-email-signup-link]');

    function setMode(mode) {
        mode = mode === 'signup' ? 'signup' : 'login';
        panels.forEach(panel => panel.classList.toggle('is-active', panel.getAttribute('data-auth-panel') === mode));
        title.textContent = mode === 'signup' ? 'Create your Lvalues account' : 'Login to Lvalues';
        if (status) status.classList.remove('is-active');
        updateSignupLinks();
    }

    function updateSignupLinks() {
        const selectedRole = role && role.value === 'tutor' ? 'tutor' : 'student';
        signupLinks.forEach(link => {
            const provider = link.getAttribute('data-signup-provider');
            link.href = '<?php echo site_url('login/oauth'); ?>/' + encodeURIComponent(provider) + '?registration_type=' + encodeURIComponent(selectedRole);
        });
        if (emailSignupLink) {
            emailSignupLink.href = '<?php echo site_url('sign_up'); ?>' + (selectedRole === 'tutor' ? '?tutor=1' : '');
        }
    }

    if (role) role.addEventListener('change', updateSignupLinks);
    modal.querySelectorAll('[data-switch-auth]').forEach(button => {
        button.addEventListener('click', () => setMode(button.getAttribute('data-switch-auth')));
    });
    modal.querySelectorAll('[data-auth-loading]').forEach(link => {
        link.addEventListener('click', () => {
            if (status) status.classList.add('is-active');
        });
    });

    document.querySelectorAll('[data-lvalues-auth]').forEach(link => {
        link.addEventListener('click', function(event) {
            if (this.getAttribute('data-lvalues-auth') === 'signup' || this.getAttribute('data-lvalues-auth') === 'login') return;
            if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
            event.preventDefault();
            setMode(this.getAttribute('data-lvalues-auth'));
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    setMode('login');
})();
</script>
