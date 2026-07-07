<?php
$permission_modules = array('course', 'category', 'coupon', 'enrolment', 'revenue', 'user', 'student', 'instructor', 'admin', 'settings', 'contact', 'blog');
$permission_rows = $this->db->table_exists('permissions') ? $this->db->get('permissions')->result_array() : array();
$permission_map = array();
foreach ($permission_rows as $permission_row) {
    $permission_map[(int)$permission_row['admin_id']] = json_decode($permission_row['permissions'], true) ?: array();
}
$root_admin_count = 0;
$restricted_admin_count = 0;
$finance_admin_count = 0;
$settings_admin_count = 0;
foreach ($admins as $admin) {
    if (is_root_admin($admin['id'])) {
        $root_admin_count++;
        $finance_admin_count++;
        $settings_admin_count++;
    } else {
        $restricted_admin_count++;
        $admin_permissions = isset($permission_map[(int)$admin['id']]) ? $permission_map[(int)$admin['id']] : array();
        if (in_array('revenue', $admin_permissions)) $finance_admin_count++;
        if (in_array('settings', $admin_permissions)) $settings_admin_count++;
    }
}
$security_controls = array(
    array('control' => 'Admin MFA', 'state' => 'Not visible', 'risk' => 'Admin takeover risk', 'next' => 'Require MFA for admins and finance users before sensitive workflows.'),
    array('control' => 'Password policy', 'state' => 'Needs hardening', 'risk' => 'Credential stuffing and brute force', 'next' => 'Minimum 12 characters, mixed character types, breached-password check, lockout/rate limit.'),
    array('control' => 'Session management', 'state' => 'Not visible', 'risk' => 'Compromised sessions are hard to revoke', 'next' => 'Show active sessions, authorized devices, revoke action, idle timeout, suspicious login alert.'),
    array('control' => 'Dual approval', 'state' => 'Workflow needed', 'risk' => 'Misconfiguration and finance risk', 'next' => 'Second reviewer for payout, tax, gateway, role, and system-setting changes.')
);
?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"><i class="mdi mdi-shield-account title_icon"></i> Security review</h4>
                <p class="text-muted mb-0">Phase 5 control center for MFA readiness, least-privilege RBAC, audit logging, session management, password policy, and dual approval.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Admins</p><h3><?php echo count($admins); ?></h3><small>Total privileged users</small></div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Root admins</p><h3><?php echo $root_admin_count; ?></h3><small>Full access accounts</small></div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Scoped admins</p><h3><?php echo $restricted_admin_count; ?></h3><small>Permission controlled</small></div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card h-100 security-risk-card"><div class="card-body"><p class="text-muted mb-1">MFA enforcement</p><h3>0%</h3><small>Visible MFA controls required</small></div></div>
    </div>
</div>

<div class="row">
    <?php foreach ($security_controls as $control): ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 security-risk-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h4 class="header-title mb-0"><?php echo $control['control']; ?></h4>
                        <span class="badge badge-warning-lighten"><?php echo $control['state']; ?></span>
                    </div>
                    <p class="text-muted mb-2"><?php echo $control['risk']; ?></p>
                    <small><?php echo $control['next']; ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 class="header-title mb-1">Admin permission matrix</h4>
                        <p class="text-muted mb-0">Review who can access operational, finance, content, settings, and privileged admin modules.</p>
                    </div>
                    <a href="<?php echo site_url('admin/admins'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Manage admins</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Email</th>
                                <th>Access model</th>
                                <th>Permission coverage</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <?php
                                $admin_permissions = isset($permission_map[(int)$admin['id']]) ? $permission_map[(int)$admin['id']] : array();
                                $coverage = is_root_admin($admin['id']) ? count($permission_modules) : count(array_intersect($permission_modules, $admin_permissions));
                                ?>
                                <tr>
                                    <td><?php echo html_escape($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                                    <td><?php echo html_escape($admin['email']); ?></td>
                                    <td>
                                        <?php if (is_root_admin($admin['id'])): ?>
                                            <span class="badge badge-danger-lighten">Root admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-info-lighten">Scoped admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $coverage . ' / ' . count($permission_modules); ?> modules</td>
                                    <td>
                                        <?php if (!is_root_admin($admin['id'])): ?>
                                            <a href="<?php echo site_url('admin/permissions?permission_assing_to=' . $admin['id']); ?>" class="btn btn-outline-primary btn-sm">Assign permissions</a>
                                        <?php else: ?>
                                            <span class="text-muted">Root protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Sensitive-action policy</h4>
                <div class="alert alert-warning">Use dual review for payouts, refunds, settings, role changes, gateway changes, public course accessibility, and course takedowns.</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0"><strong>MFA:</strong> require admins and finance users to verify with a second factor.</li>
                    <li class="list-group-item px-0"><strong>Session review:</strong> show devices, last IP, idle age, and revoke action.</li>
                    <li class="list-group-item px-0"><strong>Audit trail:</strong> log login, role, settings, finance, content, and course publish actions.</li>
                    <li class="list-group-item px-0"><strong>Approvals:</strong> require a second reviewer for finance/settings changes.</li>
                </ul>
                <a href="<?php echo site_url('admin/audit_activity'); ?>" class="btn btn-primary btn-block mt-3">Open audit activity</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">Privileged access review</h4>
            <div class="row">
                <div class="col-md-6 mb-2"><div class="border rounded p-3 h-100"><p class="text-muted mb-1">Finance-capable admins</p><h3><?php echo (int)$finance_admin_count; ?></h3><small>Require MFA and dual approval for payouts/refunds.</small></div></div>
                <div class="col-md-6 mb-2"><div class="border rounded p-3 h-100"><p class="text-muted mb-1">Settings-capable admins</p><h3><?php echo (int)$settings_admin_count; ?></h3><small>Require change confirmation and rollback plan.</small></div></div>
            </div>
            <div class="alert alert-info mb-0 mt-2">Least privilege target: keep root admins minimal, assign scoped modules, and review permissions monthly.</div>
        </div></div>
    </div>
    <div class="col-xl-6">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">Session and device management design</h4>
            <div class="table-responsive">
                <table class="table table-striped table-centered mb-0">
                    <thead><tr><th>Control</th><th>Required behavior</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>Active sessions</td><td>List device, browser, IP, last activity, login time.</td><td><span class="badge badge-warning-lighten">Design ready</span></td></tr>
                        <tr><td>Device revoke</td><td>Admin can revoke a compromised device/session.</td><td><span class="badge badge-warning-lighten">Design ready</span></td></tr>
                        <tr><td>Idle timeout</td><td>Shorter timeout for admin and finance roles.</td><td><span class="badge badge-warning-lighten">Policy needed</span></td></tr>
                        <tr><td>Suspicious login</td><td>Alert on unusual IP, impossible travel, repeated failures.</td><td><span class="badge badge-warning-lighten">Policy needed</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">Dual approval workflow for sensitive changes</h4>
            <div class="table-responsive">
                <table class="table table-striped table-centered mb-0">
                    <thead><tr><th>Change type</th><th>First reviewer</th><th>Second approval</th><th>Audit requirement</th></tr></thead>
                    <tbody>
                        <tr><td>Payout, refund, tax, gateway changes</td><td>Finance admin</td><td>Root admin or finance lead</td><td>Before/after value, reason, reviewer IDs.</td></tr>
                        <tr><td>Role and permission changes</td><td>Admin manager</td><td>Root admin</td><td>Permission diff, affected admin, justification.</td></tr>
                        <tr><td>System settings and public course accessibility</td><td>Settings admin</td><td>Root admin</td><td>Confirmation text, rollback value, business impact.</td></tr>
                        <tr><td>Course takedown and content publish/reject</td><td>Course/content reviewer</td><td>Catalog owner</td><td>Decision reason, version, reviewer notes.</td></tr>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
