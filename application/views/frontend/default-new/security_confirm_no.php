<section class="category-course-list-area mt-5 pt-5 mb-5 pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-lg text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 80px;"></i>
                    </div>
                    <h2 class="mb-3">Account Security Alert</h2>
                    <p class="text-muted mb-4" style="font-size: 16px;">
                        You have reported this login as suspicious. We have revoked all trusted devices for your account to protect your data. 
                        <strong>Please change your password immediately.</strong>
                    </p>
                    <div>
                        <a href="<?php echo site_url('login/forgot_password_request'); ?>" class="btn btn-danger px-4 py-2 rounded-pill">
                            Reset Password Now
                        </a>
                        <a href="<?php echo site_url('login'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill ms-2">
                            Return to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
