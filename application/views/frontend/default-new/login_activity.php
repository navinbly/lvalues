<?php $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array(); ?>
<?php $this->load->view('frontend/default-new/profile_menus'); ?>

<section class="grid-view">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="profile-content">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4 class="mb-0">Recent Login Activity</h4>
                            <p class="text-muted">Review where your account has been accessed recently. If you see unrecognized activity, change your password immediately.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Date & Time</th>
                                    <th scope="col">Device / Browser</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">IP Address</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $this->load->model('Security_login_model');
                                $history = $this->Security_login_model->get_login_history_for_user($this->session->userdata('user_id'), 30);
                                if (!empty($history)): 
                                    foreach ($history as $row): 
                                        $device = trim($row['browser_name'] . ' ' . $row['browser_version']);
                                        if (empty($device)) $device = 'Unknown Browser';
                                        $os = $row['os_name'] ? $row['os_name'] : 'Unknown OS';
                                        
                                        $location = array_filter([$row['city'], $row['state'], $row['country']]);
                                        $loc_str = !empty($location) ? implode(', ', $location) : 'Unknown Location';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo date('d M Y', strtotime($row['created_at'])); ?></div>
                                            <div class="text-muted small"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php if($row['device_type'] == 'mobile'): ?>
                                                    <i class="fas fa-mobile-alt me-1 text-muted"></i>
                                                <?php elseif($row['device_type'] == 'tablet'): ?>
                                                    <i class="fas fa-tablet-alt me-1 text-muted"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-desktop me-1 text-muted"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($device); ?>
                                            </div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($os); ?></div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($loc_str); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['ip_address']); ?>
                                        </td>
                                        <td>
                                            <?php if ($row['is_suspicious']): ?>
                                                <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded">Suspicious / New</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success px-2 py-1 rounded">Trusted</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach; 
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No login history found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-muted small text-center">
                        Showing last 30 login events.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
