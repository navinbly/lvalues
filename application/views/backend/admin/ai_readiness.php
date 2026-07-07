<?php
$pending_applications = $this->user_model->get_pending_applications()->num_rows();
$status_wise_courses = $this->crud_model->get_status_wise_courses();
$pending_courses = isset($status_wise_courses['pending']) ? $status_wise_courses['pending']->num_rows() : 0;
$contacts = $this->db->table_exists('contact') ? $this->crud_model->get_contacts()->result_array() : array();
$students = $this->db->table_exists('users') ? $this->db->where('role_id', 2)->get('users')->result_array() : array();
$enrolments = $this->db->table_exists('enrol') ? $this->db->get('enrol')->result_array() : array();
$enrolled_users = array();
foreach ($enrolments as $enrolment) {
    $enrolled_users[(int)($enrolment['user_id'] ?? 0)] = true;
}
$at_risk_students = max(0, count($students) - count($enrolled_users));
$refund_signals = 0;
foreach ($contacts as $contact) {
    $message = strtolower(($contact['subject'] ?? '') . ' ' . ($contact['message'] ?? ''));
    if (strpos($message, 'refund') !== false || strpos($message, 'payment') !== false || strpos($message, 'complaint') !== false) {
        $refund_signals++;
    }
}
$ai_modules = array(
    array('stage' => 'Stage 1', 'name' => 'AI tutor approval assistant', 'priority' => 'High', 'status' => 'Ready for pilot', 'data' => 'Tutor profile, documents, application answers, history', 'output' => 'Summary, missing documents, suspicious profile flags, approve/review/reject recommendation.'),
    array('stage' => 'Stage 1', 'name' => 'AI course quality review', 'priority' => 'High', 'status' => 'Ready for pilot', 'data' => 'Course metadata, lessons, media, descriptions', 'output' => 'Title, description, outcomes, lesson structure, SEO, and policy score.'),
    array('stage' => 'Stage 1', 'name' => 'AI support triage', 'priority' => 'High', 'status' => 'Ready for pilot', 'data' => 'Support tickets, policy docs, user history', 'output' => 'Ticket class, suggested response, refund/escalation intent.'),
    array('stage' => 'Stage 2', 'name' => 'AI student success predictor', 'priority' => 'High', 'status' => 'Data expansion needed', 'data' => 'Login history, progress, watch time, quiz scores, support cases, payment state', 'output' => 'Risk score and intervention recommendation.'),
    array('stage' => 'Stage 2', 'name' => 'AI content generator', 'priority' => 'Medium', 'status' => 'Governance first', 'data' => 'Course/catalog data, brand guidelines', 'output' => 'Course descriptions, FAQs, SEO metadata, landing-page copy.'),
    array('stage' => 'Stage 2', 'name' => 'AI business insight assistant', 'priority' => 'Medium', 'status' => 'Data expansion needed', 'data' => 'Revenue, enrollment, support, tutor, course analytics', 'output' => 'Weekly admin summary, anomaly detection, growth recommendations.'),
    array('stage' => 'Stage 3', 'name' => 'AI marketplace matching', 'priority' => 'Future', 'status' => 'Future', 'data' => 'Profiles, preferences, outcomes, tutor performance', 'output' => 'Student-to-tutor/course matching by goals, availability, learning style.')
);
?>
<div class="row"><div class="col-xl-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-robot title_icon"></i> AI readiness</h4><p class="text-muted mb-0">Phase 4 AI roadmap, guardrails, and pilot surfaces. AI can recommend, but high-risk actions stay human-approved.</p></div></div></div></div>

<div class="row">
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 ai-signal-card"><div class="card-body"><p class="text-muted mb-1">Tutor approval signals</p><h3><?php echo (int)$pending_applications; ?></h3><small>Applications for AI summary pilot</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 ai-signal-card"><div class="card-body"><p class="text-muted mb-1">Course QA signals</p><h3><?php echo (int)$pending_courses; ?></h3><small>Pending courses for quality review</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 ai-signal-card"><div class="card-body"><p class="text-muted mb-1">Support triage signals</p><h3><?php echo (int)$refund_signals; ?></h3><small>Refund/payment/escalation intent</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100 ai-signal-card"><div class="card-body"><p class="text-muted mb-1">Student risk signals</p><h3><?php echo (int)$at_risk_students; ?></h3><small>No enrollment found</small></div></div></div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div><h4 class="header-title mb-1">AI implementation roadmap</h4><p class="text-muted mb-0">Prioritized modules with the data each one needs before production rollout.</p></div>
                <span class="badge badge-info-lighten mt-2 mt-md-0">Phase 4</span>
            </div>
            <div class="table-responsive"><table class="table table-striped table-centered mb-0">
                <thead><tr><th>Stage</th><th>Feature</th><th>Priority</th><th>Status</th><th>Data required</th></tr></thead>
                <tbody>
                    <?php foreach ($ai_modules as $module): ?>
                        <tr>
                            <td><?php echo $module['stage']; ?></td>
                            <td><strong><?php echo $module['name']; ?></strong><br><small class="text-muted"><?php echo $module['output']; ?></small></td>
                            <td><span class="badge badge-<?php echo $module['priority'] == 'High' ? 'danger' : ($module['priority'] == 'Medium' ? 'warning' : 'info'); ?>-lighten"><?php echo $module['priority']; ?></span></td>
                            <td><?php echo $module['status']; ?></td>
                            <td><?php echo $module['data']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">AI guardrails</h4>
            <ul class="list-group list-group-flush">
                <li class="list-group-item px-0"><strong>Recommend only:</strong> AI never silently approves high-risk actions.</li>
                <li class="list-group-item px-0"><strong>Explainability:</strong> every recommendation shows confidence, reasons, and missing data.</li>
                <li class="list-group-item px-0"><strong>Auditability:</strong> store prompt, output, reviewer decision, and override reason.</li>
                <li class="list-group-item px-0"><strong>Data minimization:</strong> send only the fields needed for the task.</li>
                <li class="list-group-item px-0"><strong>Human approval:</strong> required for rejection, takedown, refund denial, suspension.</li>
            </ul>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-4"><div class="card ai-review-card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h4 class="header-title mb-0">Tutor approval assistant</h4><span class="badge badge-warning-lighten">91% confidence</span></div>
        <p class="text-muted">Recommended action: human review before approval.</p>
        <p class="mb-2"><strong>Explanation:</strong> summarize profile quality, check missing documents, and flag duplicate or suspicious signals before admins decide.</p>
        <a href="<?php echo site_url('admin/instructor_application'); ?>" class="btn btn-outline-primary btn-sm">Open tutor queue</a>
    </div></div></div>
    <div class="col-xl-4"><div class="card ai-review-card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h4 class="header-title mb-0">Course quality review</h4><span class="badge badge-warning-lighten">88% confidence</span></div>
        <p class="text-muted">Recommended action: review weak metadata and empty lesson structures.</p>
        <p class="mb-2"><strong>Explanation:</strong> score title, outcomes, description, SEO, media, lessons, and policy compliance before publishing.</p>
        <a href="<?php echo site_url('admin/course_quality'); ?>" class="btn btn-outline-primary btn-sm">Open course QA</a>
    </div></div></div>
    <div class="col-xl-4"><div class="card ai-review-card"><div class="card-body">
        <div class="d-flex justify-content-between mb-2"><h4 class="header-title mb-0">Support triage assistant</h4><span class="badge badge-info-lighten">84% confidence</span></div>
        <p class="text-muted">Recommended action: classify refund, payment, complaint, or general support.</p>
        <p class="mb-2"><strong>Explanation:</strong> draft a response and route escalation intent, but refund denial remains human-approved.</p>
        <a href="<?php echo site_url('admin/support_tickets'); ?>" class="btn btn-outline-primary btn-sm">Open support triage</a>
    </div></div></div>
</div>

<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Audit log requirements for AI decisions</h4>
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Prompt snapshot</strong><p class="text-muted mb-0 small">Template, policy version, fields sent, and redaction status.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Model output</strong><p class="text-muted mb-0 small">Recommendation, explanation, confidence, and risk flags.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Reviewer decision</strong><p class="text-muted mb-0 small">Approve, review, reject, takedown, refund, or suspend decision.</p></div></div>
            <div class="col-md-3 col-sm-6 mb-2"><div class="border rounded p-3 h-100"><strong>Override reason</strong><p class="text-muted mb-0 small">Required whenever the admin disagrees with the AI recommendation.</p></div></div>
        </div>
    </div></div></div>
</div>
