<?php
$payments = $this->db->table_exists('payment') ? $this->db->get('payment')->result_array() : array();
$pending_payouts = $this->crud_model->get_pending_payouts()->result_array();
$total_amount = 0; $admin_revenue = 0; $instructor_revenue = 0; $unpaid_instructor_revenue = 0;
foreach ($payments as $payment) {
    $total_amount += (float)($payment['amount'] ?? 0);
    $admin_revenue += (float)($payment['admin_revenue'] ?? 0);
    $instructor_revenue += (float)($payment['instructor_revenue'] ?? 0);
    if (isset($payment['instructor_payment_status']) && (int)$payment['instructor_payment_status'] == 0) {
        $unpaid_instructor_revenue += (float)($payment['instructor_revenue'] ?? 0);
    }
}
$pending_payout_total = 0;
foreach ($pending_payouts as $payout) $pending_payout_total += (float)$payout['amount'];
$total_amount_label = $total_amount > 0 ? currency($total_amount) : '0';
$admin_revenue_label = $admin_revenue > 0 ? currency($admin_revenue) : '0';
$instructor_revenue_label = $instructor_revenue > 0 ? currency($instructor_revenue) : '0';
$pending_payout_total_label = $pending_payout_total > 0 ? currency($pending_payout_total) : '0';
?>
<div class="row"><div class="col-xl-12"><div class="card"><div class="card-body"><h4 class="page-title"><i class="mdi mdi-finance title_icon"></i> Finance operations</h4><p class="text-muted mb-0">Revenue, payout, reconciliation, refund, and commission readiness console.</p></div></div></div></div>
<div class="row">
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Total collected</p><h3><?php echo $total_amount_label; ?></h3><small>Payment table</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Admin revenue</p><h3><?php echo $admin_revenue_label; ?></h3><small>Platform share</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Instructor revenue</p><h3><?php echo $instructor_revenue_label; ?></h3><small>Tutor share</small></div></div></div>
    <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Pending payouts</p><h3><?php echo $pending_payout_total_label; ?></h3><small><?php echo count($pending_payouts); ?> requests</small></div></div></div>
</div>
<div class="row">
    <div class="col-xl-8"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Finance action center</h4>
        <div class="row">
            <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Revenue reports</h5><p class="text-muted">Review admin, instructor, and purchase history tables.</p><a href="<?php echo site_url('admin/admin_revenue'); ?>" class="btn btn-outline-primary btn-sm">Admin revenue</a> <a href="<?php echo site_url('admin/purchase_history'); ?>" class="btn btn-outline-primary btn-sm">Purchases</a></div></div>
            <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Payout workflow</h5><p class="text-muted">Pending payout total: <?php echo $pending_payout_total_label; ?></p><a href="<?php echo site_url('admin/instructor_payout'); ?>" class="btn btn-outline-primary btn-sm">Open payouts</a></div></div>
            <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Refund queue</h5><p class="text-muted">Next build: refund requests, approval levels, reason codes, gateway sync.</p><span class="badge badge-warning-lighten">Workflow missing</span></div></div>
            <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Reconciliation</h5><p class="text-muted">Next build: settlement status, gateway reference matching, tax/export reports.</p><span class="badge badge-warning-lighten">Workflow missing</span></div></div>
        </div>
    </div></div></div>
    <div class="col-xl-4"><div class="card"><div class="card-body">
        <h4 class="header-title mb-3">Finance controls backlog</h4>
        <ul class="list-group list-group-flush">
            <li class="list-group-item px-0">Refund request table with approval workflow.</li>
            <li class="list-group-item px-0">Commission rules by category, tutor tier, and campaign.</li>
            <li class="list-group-item px-0">Gateway settlement reconciliation and failed-payment report.</li>
            <li class="list-group-item px-0">Invoice/tax export and accounting integration.</li>
        </ul>
    </div></div></div>
</div>
