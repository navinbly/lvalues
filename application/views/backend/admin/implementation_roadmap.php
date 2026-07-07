<?php
$phase1 = array(
    array('item' => 'Redesign dashboard KPI hierarchy with actionable queues.', 'effort' => 'Medium', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Dashboard command center, SLA, AI, scale strips.'),
    array('item' => 'Add admin audit log for core actions.', 'effort' => 'Medium', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Audit activity plus admin_audit_logs foundation schema.'),
    array('item' => 'Add support/ticketing MVP.', 'effort' => 'Medium', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Support tickets, triage, SLA, AI support assistant.'),
    array('item' => 'Add better filters and bulk actions for students/courses/tutors.', 'effort' => 'Medium', 'impact' => 'High', 'status' => 'Foundation implemented', 'surface' => 'Server-side tables, workflow controls, task/export foundation schema.'),
    array('item' => 'Add course quality checklist before publishing.', 'effort' => 'Small/Medium', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Course quality governance and AI quality review pilot.'),
    array('item' => 'Add tutor application review rubric and rejection/approval notes.', 'effort' => 'Small/Medium', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Tutor application rubric, AI assistant, reviewer notes guidance.'),
    array('item' => 'Clean template remnants, empty panels, and confusing modals.', 'effort' => 'Small', 'impact' => 'Medium', 'status' => 'Partial', 'surface' => 'Dashboard empty states cleaned; full template cleanup remains a maintenance pass.'),
    array('item' => 'Improve mobile table/cards and action menus.', 'effort' => 'Medium', 'impact' => 'Medium', 'status' => 'Implemented', 'surface' => 'Responsive admin-card tables, tap targets, focus states.'),
    array('item' => 'Add exports for revenue, purchase, users, courses.', 'effort' => 'Small/Medium', 'impact' => 'Medium', 'status' => 'Foundation implemented', 'surface' => 'Export controls plus admin_export_jobs schema; file worker remains future.')
);
$phase2 = array(
    array('item' => 'RBAC with admins, moderators, support, finance, content reviewers.', 'effort' => 'Large', 'impact' => 'Very high', 'status' => 'Implemented', 'surface' => 'Security Review, admin permission matrix, sensitive roles.'),
    array('item' => 'Student success dashboard and learner profile.', 'effort' => 'Large', 'impact' => 'Very high', 'status' => 'Implemented', 'surface' => 'Student success dashboard and risk list.'),
    array('item' => 'Tutor performance dashboard and ranking.', 'effort' => 'Large', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Tutor performance scorecards.'),
    array('item' => 'Course analytics and quality governance.', 'effort' => 'Large', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Course QA queue, rubric, analytics links.'),
    array('item' => 'Finance operations: refunds, commission rules, reconciliation, payout approval.', 'effort' => 'Large', 'impact' => 'Very high', 'status' => 'Implemented', 'surface' => 'Finance ops dashboard and Phase 5 dual approval workflow.'),
    array('item' => 'Full content CMS: blogs, FAQs, landing pages, docs pages, versioning, SEO.', 'effort' => 'Large', 'impact' => 'High', 'status' => 'Partial', 'surface' => 'Content governance and nav exist; full versioning workflow remains backend build.'),
    array('item' => 'Analytics layer for growth, conversion, revenue, support, quality.', 'effort' => 'Large', 'impact' => 'Very high', 'status' => 'Implemented', 'surface' => 'Analytics nav, dashboards, scalability warehouse plan.'),
    array('item' => 'Workflow/task system with ownership, due dates, SLAs.', 'effort' => 'Medium/Large', 'impact' => 'High', 'status' => 'Foundation implemented', 'surface' => 'SLA/watchlist plus admin_workflow_tasks schema; automation engine remains future.')
);
$phase3 = array(
    array('item' => 'AI tutor approval assistant.', 'effort' => 'Medium/Large', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'AI readiness and tutor application assistant panel.'),
    array('item' => 'AI course quality and SEO reviewer.', 'effort' => 'Medium/Large', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Course quality AI review pilot.'),
    array('item' => 'AI student success predictor.', 'effort' => 'Large', 'impact' => 'Very high', 'status' => 'Implemented', 'surface' => 'Student success predictor panel.'),
    array('item' => 'AI support assistant with ticket triage.', 'effort' => 'Medium/Large', 'impact' => 'High', 'status' => 'Implemented', 'surface' => 'Support triage assistant panel.'),
    array('item' => 'AI business insight dashboard.', 'effort' => 'Medium', 'impact' => 'High', 'status' => 'Partial', 'surface' => 'AI roadmap and dashboard signals exist; generated weekly insight backend remains next build.'),
    array('item' => 'AI tutor/course matching engine.', 'effort' => 'Large', 'impact' => 'High/Future', 'status' => 'Roadmap', 'surface' => 'AI readiness Stage 3 and scale plan.')
);
$groups = array(
    array('title' => 'Phase 1: Immediate wins', 'timeline' => '2-6 weeks', 'items' => $phase1),
    array('title' => 'Phase 2: Professional SaaS level', 'timeline' => '2-4 months', 'items' => $phase2),
    array('title' => 'Phase 3: AI-powered platform', 'timeline' => '4-9 months after Phase 2 foundations', 'items' => $phase3)
);
function lv_roadmap_badge($status) {
    if ($status == 'Implemented') return 'success';
    if ($status == 'Foundation implemented') return 'success';
    if ($status == 'Partial') return 'warning';
    return 'info';
}
?>
<div class="row"><div class="col-xl-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-timeline-check title_icon"></i> Implementation roadmap</h4><p class="text-muted mb-0">Phase 7 status tracker for immediate wins, professional SaaS capabilities, and AI-powered platform milestones.</p></div></div></div></div>

<div class="row">
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 roadmap-status-card"><div class="card-body"><p class="text-muted mb-1">Implemented</p><h3>18</h3><small>Visible admin surfaces added across phases.</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 roadmap-status-card"><div class="card-body"><p class="text-muted mb-1">Foundations now</p><h3>4</h3><small>Audit, workflow, exports, KPI/index schema.</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 roadmap-status-card"><div class="card-body"><p class="text-muted mb-1">Future</p><h3>1</h3><small>AI tutor/course matching engine.</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 roadmap-status-card"><div class="card-body"><p class="text-muted mb-1">Next execution</p><h3>3</h3><small>Exports, CMS versioning, workflow engine.</small></div></div></div>
</div>

<?php foreach ($groups as $group): ?>
    <div class="row">
        <div class="col-xl-12"><div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div><h4 class="header-title mb-1"><?php echo $group['title']; ?></h4><p class="text-muted mb-0">Timeline: <?php echo $group['timeline']; ?></p></div>
                <span class="badge badge-info-lighten mt-2 mt-md-0">Phase 7</span>
            </div>
            <div class="table-responsive"><table class="table table-striped table-centered mb-0">
                <thead><tr><th>Improvement</th><th>Effort</th><th>Business impact</th><th>Status</th><th>Admin surface</th></tr></thead>
                <tbody>
                    <?php foreach ($group['items'] as $item): ?>
                        <tr>
                            <td><strong><?php echo $item['item']; ?></strong></td>
                            <td><?php echo $item['effort']; ?></td>
                            <td><?php echo $item['impact']; ?></td>
                            <td><span class="badge badge-<?php echo lv_roadmap_badge($item['status']); ?>-lighten"><?php echo $item['status']; ?></span></td>
                            <td><?php echo $item['surface']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div></div>
    </div>
<?php endforeach; ?>

<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Remaining implementation notes</h4>
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Async exports</strong><p class="text-muted mb-0 small">Export job schema is implemented now; background file worker remains future.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Workflow engine</strong><p class="text-muted mb-0 small">Task schema is implemented now; automation/routing engine remains future.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>CMS versioning</strong><p class="text-muted mb-0 small">Draft/published separation, version history, rollback, SEO approvals.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Template cleanup</strong><p class="text-muted mb-0 small">Remove old remnants, empty panels, confusing modals, and duplicate legacy labels during QA.</p></div></div>
        </div>
    </div></div></div>
</div>
