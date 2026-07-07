<?php
$activities = array();
if ($this->db->table_exists('users')) {
    $this->db->select('id, first_name, last_name, email, date_added');
    $this->db->order_by('date_added', 'DESC');
    $this->db->limit(8);
    foreach ($this->db->get('users')->result_array() as $user) {
        $activities[] = array(
            'type' => 'User created',
            'subject' => trim($user['first_name'] . ' ' . $user['last_name']),
            'detail' => $user['email'],
            'time' => !empty($user['date_added']) ? date('d M Y h:i A', (int)$user['date_added']) : 'Unknown'
        );
    }
}
if ($this->db->table_exists('course')) {
    $this->db->select('id, title, status, date_added');
    $this->db->order_by('date_added', 'DESC');
    $this->db->limit(8);
    foreach ($this->db->get('course')->result_array() as $course) {
        $activities[] = array(
            'type' => 'Course activity',
            'subject' => $course['title'],
            'detail' => 'Status: ' . $course['status'],
            'time' => !empty($course['date_added']) ? date('d M Y h:i A', (int)$course['date_added']) : 'Unknown'
        );
    }
}
if ($this->db->table_exists('payment')) {
    $this->db->select('id, course_id, user_id, amount, date_added');
    $this->db->order_by('date_added', 'DESC');
    $this->db->limit(8);
    foreach ($this->db->get('payment')->result_array() as $payment) {
        $activities[] = array(
            'type' => 'Payment recorded',
            'subject' => 'Payment #' . $payment['id'],
            'detail' => currency($payment['amount']),
            'time' => !empty($payment['date_added']) ? date('d M Y h:i A', (int)$payment['date_added']) : 'Unknown'
        );
    }
}
usort($activities, function($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});
$activities = array_slice($activities, 0, 18);
?>
<div class="row">
    <div class="col-xl-12">
        <div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-history title_icon"></i> Audit activity</h4><p class="text-muted mb-0">Phase 5 audit readiness view. Current records are assembled from existing tables; production hardening needs immutable event storage.</p></div></div>
    </div>
</div>
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Recent sensitive activity</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-centered mb-0">
                        <thead><tr><th>Event</th><th>Subject</th><th>Detail</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><span class="badge badge-info-lighten"><?php echo html_escape($activity['type']); ?></span></td>
                                    <td><?php echo html_escape($activity['subject']); ?></td>
                                    <td><?php echo html_escape($activity['detail']); ?></td>
                                    <td><?php echo html_escape($activity['time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($activities) == 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No activity records available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3">Immutable audit log backlog</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">Login success/failure, logout, session revocation.</li>
                    <li class="list-group-item px-0">User create/edit/delete, verification, role changes.</li>
                    <li class="list-group-item px-0">Course publish, unpublish, delete, pricing changes.</li>
                    <li class="list-group-item px-0">Payout, refund, payment gateway, tax/settings changes.</li>
                    <li class="list-group-item px-0">Content approval, rejection, version rollback.</li>
                </ul>
                <div class="alert alert-info mt-3 mb-0">Store actor, IP/device, before/after values, approval state, reviewer decision, and override reason for every sensitive action.</div>
            </div>
        </div>
    </div>
</div>
