<?php
$CI =& get_instance();
$CI->load->config('oauth', true);
$providers = $CI->config->item('oauth_providers', 'oauth');
$external_auth_context = isset($external_auth_context) ? $external_auth_context : 'login';
$external_auth_role = isset($external_auth_role) && in_array($external_auth_role, ['student', 'tutor'], true) ? $external_auth_role : 'student';
$component_id = 'external-auth-' . substr(md5(uniqid('', true)), 0, 8);

$entra_ready = !empty($providers['entra']['client_id'])
    && !empty($providers['entra']['client_secret'])
    && (!empty($providers['entra']['tenant_id']) || !empty($providers['entra']['authority']));
$google_ready = !empty($providers['google']['client_id']) && !empty($providers['google']['client_secret']);
$is_signup_context = $external_auth_context === 'signup';
$google_url = site_url('login/oauth/google') . '?' . http_build_query(['registration_type' => $external_auth_role]);
?>
<style>
.lvalues-external-auth {
    border: 1px solid #e7eaf3;
    border-radius: 12px;
    padding: 16px;
    margin: 22px 0 18px;
    background: #fff;
    box-shadow: 0 10px 28px rgba(21, 31, 56, .06);
}
.lvalues-external-auth__title {
    margin: 0 0 5px;
    font-size: 16px;
    line-height: 1.35;
    font-weight: 700;
    color: #26324d;
}
.lvalues-external-auth__copy {
    margin: 0 0 14px;
    font-size: 13px;
    line-height: 1.55;
    color: #667085;
}
.lvalues-external-auth__grid {
    display: grid;
    gap: 10px;
}
.lvalues-external-auth .form-control,
.lvalues-external-auth .form-select {
    height: 46px;
    border: 1px solid #dfe4ee;
    border-radius: 9px;
    background-color: #f8f9fc;
    color: #26324d;
}
.lvalues-external-auth__button {
    width: 100%;
    min-height: 46px;
    border-radius: 9px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-weight: 700;
    white-space: normal;
    text-align: center;
}
.lvalues-external-auth__button.microsoft {
    border-color: #5b6ee1;
    color: #3647b3;
    background: #fff;
}
.lvalues-external-auth__button.microsoft:hover {
    background: #f2f4ff;
    color: #27389b;
}
.lvalues-external-auth__button.google {
    border-color: #ea4335;
    color: #d93025;
    background: #fff;
}
.lvalues-external-auth__button.google:hover {
    background: #fff5f4;
    color: #b3261e;
}
.lvalues-external-auth__button[disabled],
.lvalues-external-auth__button.is-disabled {
    border-color: #d7dce8 !important;
    color: #8a94a8 !important;
    background: #f2f4f7 !important;
    cursor: not-allowed;
    box-shadow: none;
}
.lvalues-external-auth__setup {
    margin: 0;
    border: 1px solid #f6d08a;
    border-radius: 10px;
    padding: 10px 12px;
    background: #fff8e8;
    font-size: 12px;
    line-height: 1.45;
    color: #8a5a00;
}
.lvalues-external-auth__role {
    display: grid;
    grid-template-columns: 110px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.lvalues-external-auth__role label {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #344054;
}
@media (max-width: 575px) {
    .lvalues-external-auth { padding: 14px; }
    .lvalues-external-auth__role { grid-template-columns: 1fr; gap: 6px; }
}
</style>

<div class="lvalues-external-auth" id="<?php echo html_escape($component_id); ?>">
    <h5 class="lvalues-external-auth__title">
        <?php echo $is_signup_context ? 'Register without creating a password' : get_phrase('Continue without password'); ?>
    </h5>
    <p class="lvalues-external-auth__copy">
        <?php echo $is_signup_context
            ? 'Use Microsoft Authenticator, a Microsoft account, or Gmail to create your Lvalues account.'
            : 'Use Microsoft Authenticator, a Microsoft account, or Gmail to sign in securely.'; ?>
    </p>

    <div class="lvalues-external-auth__grid">
        <?php if ($entra_ready): ?>
        <form action="<?php echo site_url('login/oauth/entra'); ?>" method="post" class="external-login-form">
            <input type="hidden" name="registration_type" value="<?php echo html_escape($external_auth_role); ?>" data-external-role-input>
            <input
                type="email"
                class="form-control mb-2"
                name="login_hint"
                placeholder="<?php echo get_phrase('Enter your email'); ?>"
                >
            <button type="submit" class="btn lvalues-external-auth__button microsoft">
                <i class="fa-brands fa-microsoft"></i>
                Microsoft Authenticator
            </button>
        </form>
        <?php endif; ?>

        <?php if ($google_ready): ?>
            <a class="btn lvalues-external-auth__button google" href="<?php echo html_escape($google_url); ?>" data-google-base="<?php echo site_url('login/oauth/google'); ?>">
                <i class="fa-brands fa-google"></i>
                Continue with Google / Gmail
            </a>
        <?php endif; ?>

        <?php if (!$entra_ready && !$google_ready): ?>
            <div class="lvalues-external-auth__setup">
                Social sign-in is prepared, but Google/Microsoft OAuth keys are not configured on this server yet. Continue with email and password for now.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var root = document.getElementById('<?php echo $component_id; ?>');
    if (!root) return;
    var roleSelect = root.querySelector('[data-external-role]');
    var roleInputs = root.querySelectorAll('[data-external-role-input]');
    var googleLink = root.querySelector('[data-google-base]');
    function setExternalRole(role) {
        role = role === 'tutor' ? 'tutor' : 'student';
        if (roleSelect) roleSelect.value = role;
        roleInputs.forEach(function(input) { input.value = role; });
        if (googleLink) {
            googleLink.href = googleLink.getAttribute('data-google-base') + '?registration_type=' + encodeURIComponent(role);
        }
    }
    if (roleSelect) roleSelect.addEventListener('change', function() { setExternalRole(this.value); });
    setExternalRole(roleSelect ? roleSelect.value : '<?php echo html_escape($external_auth_role); ?>');
    document.addEventListener('click', function(event) {
        var button = event.target.closest('.registration-toggle');
        if (button) setExternalRole(button.getAttribute('data-type'));
    });
})();
</script>
