<?php
$student_count = $this->db->table_exists('users') ? (int)$this->db->where('role_id', 2)->count_all_results('users') : 0;
$tutor_count = $this->db->table_exists('users') ? (int)$this->db->where('is_instructor', 1)->count_all_results('users') : 0;
$course_count = $this->db->table_exists('course') ? (int)$this->db->count_all_results('course') : 0;
$payment_count = $this->db->table_exists('payment') ? (int)$this->db->count_all_results('payment') : 0;
$support_count = $this->db->table_exists('contact') ? (int)$this->db->count_all_results('contact') : 0;
$foundation_tables = array(
    array('name' => 'Central audit trail', 'table' => 'admin_audit_logs', 'now' => 'Stores actor, action, entity, before/after values, approval state, reviewer, IP/device context.'),
    array('name' => 'Workflow tasks', 'table' => 'admin_workflow_tasks', 'now' => 'Supports owner, priority, due date, SLA escalation, entity linkage, resolution notes.'),
    array('name' => 'Async export jobs', 'table' => 'admin_export_jobs', 'now' => 'Supports queued exports for revenue, purchases, users, courses, and future report downloads.'),
    array('name' => 'KPI snapshots', 'table' => 'admin_kpi_snapshots', 'now' => 'Supports scheduled dashboard aggregates instead of expensive live table scans.')
);
$future_students = 100000;
$future_tutors = 10000;
$future_courses = 50000;
$scale_cards = array(
    array('label' => 'Students', 'current' => $student_count, 'future' => $future_students, 'note' => 'Learner accounts and engagement records'),
    array('label' => 'Tutors', 'current' => $tutor_count, 'future' => $future_tutors, 'note' => 'Applications, profiles, availability, payouts'),
    array('label' => 'Courses', 'current' => $course_count, 'future' => $future_courses, 'note' => 'Catalog, lessons, taxonomy, media, search'),
    array('label' => 'Transactions/support', 'current' => $payment_count + $support_count, 'future' => 250000, 'note' => 'Payments, refunds, tickets, reconciliation')
);
$bottlenecks = array(
    array('area' => 'Admin tables', 'risk' => 'Slow loading, pagination/search limitations.', 'recommendation' => 'Server-side indexed search, saved filters, async export, cursor pagination.'),
    array('area' => 'Course catalog', 'risk' => '50,000 courses require strong taxonomy/search.', 'recommendation' => 'Use Elasticsearch/OpenSearch/Meilisearch, structured taxonomy, synonyms.'),
    array('area' => 'Reporting', 'risk' => 'Real-time table queries become expensive.', 'recommendation' => 'Add analytics warehouse, scheduled aggregates, BI layer.'),
    array('area' => 'Finance', 'risk' => 'Payout/refund/reconciliation complexity rises sharply.', 'recommendation' => 'Event ledger, payment gateway reconciliation jobs, finance approval workflow.'),
    array('area' => 'Notifications', 'risk' => 'Manual admin review will not scale.', 'recommendation' => 'Queue-based notifications, task assignment, SLA escalation.'),
    array('area' => 'Tutor/course approval', 'risk' => 'Review queues will overload.', 'recommendation' => 'Workflow engine, AI pre-screening, batch review, reviewer assignment.'),
    array('area' => 'Audit/security', 'risk' => 'More admins require governance.', 'recommendation' => 'Central audit trail, SIEM export, privileged action approval.'),
    array('area' => 'Content', 'risk' => 'Versioning and publishing conflicts increase.', 'recommendation' => 'Content versioning, draft/published separation, rollback.')
);
$architecture = array(
    'Now: central audit log schema for privileged admin, settings, finance, content, and publish events.',
    'Now: workflow task schema for ownership, due dates, priorities, SLAs, and reviewer assignment.',
    'Now: async export job schema for users, courses, revenue, purchases, and report downloads.',
    'Now: KPI snapshot schema and database index migration for cheaper dashboard/report queries.',
    'Later: separate operational database from analytics warehouse when reporting volume demands it.',
    'Later: queue workers for email, notifications, report exports, AI reviews, and reconciliation.',
    'Later: dedicated search infrastructure such as Elasticsearch/OpenSearch/Meilisearch.',
    'Later: object storage/CDN governance and observability stack for logs, metrics, traces, uptime.'
);
$index_plan = array(
    array('table' => 'users', 'indexes' => 'role_id, status, date_added, is_instructor, email', 'reason' => 'Fast admin user/tutor search and verification queues.'),
    array('table' => 'course', 'indexes' => 'category_id, status, user_id/creator, date_added, price', 'reason' => 'Catalog filtering, publish queues, instructor course lookup.'),
    array('table' => 'payment', 'indexes' => 'date_added, payment_type, user_id, course_id, amount/status', 'reason' => 'Finance reports, reconciliation, refunds, exports.'),
    array('table' => 'contact/support', 'indexes' => 'status, priority, assigned_to, due_at/SLA, user_id', 'reason' => 'Ticket queues, SLA breach handling, escalations.')
);
?>
<div class="row"><div class="col-xl-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-server-network title_icon"></i> Scalability review</h4><p class="text-muted mb-0">Phase 6 scale-readiness plan for 100,000 students, 10,000 tutors, and 50,000 courses.</p></div></div></div></div>

<div class="row">
    <?php foreach ($scale_cards as $card): ?>
        <?php $ratio = $card['future'] > 0 ? min(100, round(($card['current'] / $card['future']) * 100, 2)) : 0; ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card h-100 scale-readiness-card"><div class="card-body">
                <p class="text-muted mb-1"><?php echo $card['label']; ?></p>
                <h3><?php echo number_format($card['current']); ?></h3>
                <small>Future target: <?php echo number_format($card['future']); ?></small>
                <div class="progress mt-3" style="height: 7px;"><div class="progress-bar bg-info" style="width: <?php echo $ratio; ?>%;"></div></div>
                <small class="text-muted d-block mt-2"><?php echo $card['note']; ?></small>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div><h4 class="header-title mb-1">Foundation implementation status</h4><p class="text-muted mb-0">These are the Phase 6 changes implemented now. Run the migration or SQL script on Hostinger to create the tables/indexes there.</p></div>
            <span class="badge badge-success-lighten mt-2 mt-md-0">Foundation now</span>
        </div>
        <div class="table-responsive"><table class="table table-striped table-centered mb-0">
            <thead><tr><th>Foundation</th><th>Database object</th><th>Status on this install</th><th>Purpose</th></tr></thead>
            <tbody>
                <?php foreach ($foundation_tables as $foundation): ?>
                    <?php $exists = $this->db->table_exists($foundation['table']); ?>
                    <tr>
                        <td><strong><?php echo $foundation['name']; ?></strong></td>
                        <td><?php echo $foundation['table']; ?></td>
                        <td><span class="badge badge-<?php echo $exists ? 'success' : 'warning'; ?>-lighten"><?php echo $exists ? 'Created' : 'Migration pending'; ?></span></td>
                        <td><?php echo $foundation['now']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Database indexes</strong></td>
                    <td>users, course, payment, contact</td>
                    <td><span class="badge badge-info-lighten">Migration-managed</span></td>
                    <td>Indexes for role/status/date, instructor/course lookups, finance reports, and support SLA queues.</td>
                </tr>
                <tr>
                    <td><strong>Support workflow fields</strong></td>
                    <td>contact.status, priority, assigned_to, due_at</td>
                    <td><span class="badge badge-info-lighten">Migration-managed</span></td>
                    <td>Turns contact intake into a scalable support queue with ownership and SLA tracking.</td>
                </tr>
            </tbody>
        </table></div>
        <div class="alert alert-info mt-3 mb-0">Hostinger deployment: run CodeIgniter migration target 20260604120000 or apply docs/phase6-scale-foundations.sql from phpMyAdmin after backup.</div>
    </div></div></div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div><h4 class="header-title mb-1">Scale bottleneck map</h4><p class="text-muted mb-0">Operational risks that will become expensive at 100k students, 10k tutors, and 50k courses.</p></div>
                <span class="badge badge-warning-lighten mt-2 mt-md-0">Phase 6</span>
            </div>
            <div class="table-responsive"><table class="table table-striped table-centered mb-0">
                <thead><tr><th>Area</th><th>Risk at scale</th><th>Recommendation</th></tr></thead>
                <tbody>
                    <?php foreach ($bottlenecks as $item): ?>
                        <tr><td><strong><?php echo $item['area']; ?></strong></td><td><?php echo $item['risk']; ?></td><td><?php echo $item['recommendation']; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">Architecture improvements</h4>
            <ul class="list-group list-group-flush">
                <?php foreach ($architecture as $item): ?>
                    <li class="list-group-item px-0"><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4"><div class="card scale-readiness-card"><div class="card-body">
        <h4 class="header-title mb-3">Admin table scalability</h4>
        <p class="text-muted">Replace large client-side tables with server-side indexed search, saved filters, async export jobs, and cursor pagination.</p>
        <a href="<?php echo site_url('admin/users'); ?>" class="btn btn-outline-primary btn-sm">Review user tables</a>
    </div></div></div>
    <div class="col-xl-4"><div class="card scale-readiness-card"><div class="card-body">
        <h4 class="header-title mb-3">Async export foundation</h4>
        <p class="text-muted">Export jobs now have a database queue schema. Queue workers for processing files can be added when export volume grows.</p>
        <a href="<?php echo site_url('admin/support_tickets'); ?>" class="btn btn-outline-primary btn-sm">Review support queues</a>
    </div></div></div>
    <div class="col-xl-4"><div class="card scale-readiness-card"><div class="card-body">
        <h4 class="header-title mb-3">Search infrastructure</h4>
        <p class="text-muted">Database indexes are the current foundation. Dedicated search engines remain a future step when catalog search outgrows SQL.</p>
        <a href="<?php echo site_url('admin/course_quality'); ?>" class="btn btn-outline-primary btn-sm">Review catalog quality</a>
    </div></div></div>
</div>

<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Database index and reporting plan</h4>
        <div class="table-responsive"><table class="table table-striped table-centered mb-0">
            <thead><tr><th>Table/domain</th><th>Recommended indexes</th><th>Why it matters</th></tr></thead>
            <tbody>
                <?php foreach ($index_plan as $plan): ?>
                    <tr><td><strong><?php echo $plan['table']; ?></strong></td><td><?php echo $plan['indexes']; ?></td><td><?php echo $plan['reason']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <div class="alert alert-info mt-3 mb-0">Reporting should move toward scheduled aggregates and an analytics warehouse so admin dashboards do not run expensive real-time table scans.</div>
    </div></div></div>
</div>

<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Event-driven scale plan</h4>
        <div class="row">
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Enrollments</strong><p class="text-muted mb-0 small">Emit events for progress, cohorts, certificates, and student success.</p></div></div>
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Payments</strong><p class="text-muted mb-0 small">Ledger events for payment, refund, payout, reconciliation.</p></div></div>
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Publishing</strong><p class="text-muted mb-0 small">Course/content status, version, reviewer, rollback events.</p></div></div>
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Support</strong><p class="text-muted mb-0 small">Ticket assignment, SLA breach, escalation, resolution events.</p></div></div>
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>AI reviews</strong><p class="text-muted mb-0 small">Async pre-screening, prompt/output audit, human override events.</p></div></div>
            <div class="col-md-2 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Observability</strong><p class="text-muted mb-0 small">Logs, metrics, traces, uptime, errors, worker lag.</p></div></div>
        </div>
    </div></div></div>
</div>
