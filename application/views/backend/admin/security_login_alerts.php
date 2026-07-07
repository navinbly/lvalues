<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title">Security Login Alerts</h4>
                <p class="text-muted font-14 mb-4">
                    List of all suspicious logins reported by the system. You can filter by role or user response.
                </p>

                <div class="table-responsive-sm mt-4">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Device & IP</th>
                                <th>Location</th>
                                <th>Email Status</th>
                                <th>User Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $this->load->model('Security_login_model');
                            $alerts = $this->Security_login_model->get_all_security_alerts(200); 
                            foreach ($alerts as $key => $alert): 
                                $device = trim($alert['browser_name'] . ' ' . $alert['os_name']);
                                if(empty($device)) $device = 'Unknown';
                                
                                $location = array_filter([$alert['city'], $alert['country']]);
                                $loc_str = !empty($location) ? implode(', ', $location) : 'Unknown';
                            ?>
                                <tr>
                                    <td><?php echo $key + 1; ?></td>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($alert['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($alert['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($alert['first_name'] . ' ' . $alert['last_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($alert['email']); ?></small>
                                        <span class="badge badge-light-secondary"><?php echo ucfirst($alert['role']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($device); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($alert['ip_address']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($loc_str); ?></td>
                                    <td>
                                        <?php if ($alert['email_sent_status'] == 'sent'): ?>
                                            <span class="badge badge-success-lighten">Sent</span>
                                        <?php elseif ($alert['email_sent_status'] == 'failed'): ?>
                                            <span class="badge badge-danger-lighten" title="<?php echo htmlspecialchars($alert['email_error']); ?>">Failed</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary-lighten">Skipped</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($alert['user_response'] == 'pending'): ?>
                                            <span class="badge badge-warning-lighten">Pending</span>
                                        <?php elseif ($alert['user_response'] == 'confirmed'): ?>
                                            <span class="badge badge-success-lighten">Confirmed (Yes)</span>
                                        <?php elseif ($alert['user_response'] == 'not_me'): ?>
                                            <span class="badge badge-danger-lighten">Not Me (Suspicious)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alerts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No security alerts found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>
