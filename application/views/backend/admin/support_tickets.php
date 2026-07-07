<?php
$contacts = $this->db->table_exists('contact') ? $this->crud_model->get_contacts()->result_array() : array();
$new_requests = count($contacts);
$high_priority = 0;
$refund_like = 0;
foreach ($contacts as $contact) {
    $message = strtolower(($contact['message'] ?? '') . ' ' . ($contact['subject'] ?? ''));
    if (strpos($message, 'refund') !== false || strpos($message, 'payment') !== false || strpos($message, 'complaint') !== false) {
        $high_priority++;
    }
    if (strpos($message, 'refund') !== false || strpos($message, 'money') !== false || strpos($message, 'payment') !== false) {
        $refund_like++;
    }
}
?>
<div class="row">
    <div class="col-xl-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-lifebuoy title_icon"></i> Support tickets</h4><p class="text-muted mb-0">Phase 2 support console using current contact messages as the ticket intake queue.</p></div></div></div>
</div>
<div class="row">
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Open intake</p><h3><?php echo $new_requests; ?></h3><small>Contact messages</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Priority signals</p><h3><?php echo $high_priority; ?></h3><small>Payment/refund/complaint terms</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Finance-linked</p><h3><?php echo $refund_like; ?></h3><small>Needs finance review</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">SLA policy</p><h3>24h</h3><small>Recommended first response</small></div></div></div>
</div>
<div class="row">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div><h4 class="header-title mb-1">Ticket intake queue</h4><p class="text-muted mb-0">Use status/owner/SLA fields as the next database-backed enhancement.</p></div>
                <a href="<?php echo site_url('admin/contact'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Open contact table</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-centered mb-0">
                    <thead><tr><th>Requester</th><th>Priority</th><th>Message preview</th><th>Recommended owner</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($contacts, 0, 12) as $contact): ?>
                            <?php
                            $message = $contact['message'] ?? '';
                            $signal = strtolower($message);
                            $priority = (strpos($signal, 'refund') !== false || strpos($signal, 'payment') !== false || strpos($signal, 'complaint') !== false) ? 'High' : 'Normal';
                            $owner = $priority == 'High' ? 'Support + Finance' : 'Support';
                            ?>
                            <tr>
                                <td><?php echo html_escape($contact['name'] ?? 'Visitor'); ?><br><small class="text-muted"><?php echo html_escape($contact['email'] ?? ''); ?></small></td>
                                <td><span class="badge badge-<?php echo $priority == 'High' ? 'danger' : 'info'; ?>-lighten"><?php echo $priority; ?></span></td>
                                <td><?php echo html_escape(ellipsis(strip_tags($message), 120)); ?></td>
                                <td><?php echo $owner; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($contacts) == 0): ?><tr><td colspan="4" class="text-center text-muted">No support intake messages yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-body">
            <h4 class="header-title mb-3">Ticket workflow backlog</h4>
            <ul class="list-group list-group-flush">
                <li class="list-group-item px-0">Status: new, assigned, waiting, escalated, resolved.</li>
                <li class="list-group-item px-0">Owner, priority, due date, SLA breach badge.</li>
                <li class="list-group-item px-0">Link ticket to user, course, tutor, payment, refund.</li>
                <li class="list-group-item px-0">AI support triage with suggested response and escalation intent.</li>
            </ul>
        </div></div>
    </div>
</div>
<div class="row">
    <div class="col-xl-12"><div class="card ai-review-card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
            <div><h4 class="header-title mb-1">AI support triage assistant</h4><p class="text-muted mb-0">Classifies support messages, drafts a response, and detects refund or escalation intent from current ticket text.</p></div>
            <span class="badge badge-info-lighten mt-2 mt-md-0">Draft response only</span>
        </div>
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-2"><strong>Classification</strong><p class="text-muted mb-0 small">Payment, refund, complaint, course access, tutor issue, or general support.</p></div>
            <div class="col-md-3 col-sm-6 mb-2"><strong>Confidence</strong><p class="text-muted mb-0 small">Show confidence and ticket text that triggered the classification.</p></div>
            <div class="col-md-3 col-sm-6 mb-2"><strong>Escalation</strong><p class="text-muted mb-0 small">Refund denials and disputes require finance/support manager approval.</p></div>
            <div class="col-md-3 col-sm-6 mb-2"><strong>Audit</strong><p class="text-muted mb-0 small">Store prompt, suggested reply, final reply, and reviewer override.</p></div>
        </div>
    </div></div></div>
</div>
