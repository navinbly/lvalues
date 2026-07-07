<?php
$status_wise_courses = $this->crud_model->get_status_wise_courses();
$pending_courses = isset($status_wise_courses['pending']) ? $status_wise_courses['pending']->num_rows() : 0;
$pending_tutor_applications = $this->user_model->get_pending_applications()->num_rows();
$pending_content_items = 0;
if (isset($this->content_docs_model)) {
    $pending_content_items += count($this->content_docs_model->get_pending_nodes());
}
$pending_content_items += $this->crud_model->get_instructors_pending_blog()->num_rows();
$pending_payouts = $this->crud_model->get_pending_payouts()->num_rows();

if (!function_exists('lv_admin_nav_active')) {
    function lv_admin_nav_active($page_name, $pages) {
        return in_array($page_name, $pages) ? 'active' : '';
    }
}
if (!function_exists('lv_admin_nav_group_active')) {
    function lv_admin_nav_group_active($page_name, $pages) {
        return in_array($page_name, $pages) ? 'active' : '';
    }
}
?>
<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu left-side-menu-detached">
    <div class="leftbar-user">
        <a href="javascript: void(0);">
            <img src="<?php echo $this->user_model->get_user_image_url($this->session->userdata('user_id')); ?>" alt="Admin profile image" height="42" class="rounded-circle shadow-sm">
            <?php $admin_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array(); ?>
            <span class="leftbar-user-name"><?php echo $admin_details['first_name'] . ' ' . $admin_details['last_name']; ?></span>
        </a>
    </div>

    <ul class="metismenu side-nav side-nav-light admin-modern-nav">
        <li class="side-nav-title side-nav-item"><?php echo get_phrase('navigation'); ?></li>

        <li class="side-nav-item <?php echo lv_admin_nav_active($page_name, array('dashboard')); ?>">
            <a href="<?php echo site_url('admin/dashboard'); ?>" class="side-nav-link" aria-label="Dashboard">
                <i class="dripicons-view-apps"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <?php if (has_permission('user') || has_permission('student') || has_permission('instructor') || has_permission('admin')) : ?>
            <?php $users_pages = array('users', 'user_add', 'user_edit', 'instructors', 'instructor_add', 'instructor_edit', 'application_list', 'admins', 'admin_add', 'admin_edit', 'admin_permission', 'phase2_security'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $users_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $users_pages); ?>" aria-label="Users menu">
                    <i class="dripicons-user-group"></i>
                    <span>Users</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('student')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('users', 'user_add', 'user_edit')); ?>"><a href="<?php echo site_url('admin/users'); ?>">Students</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('instructors', 'instructor_add', 'instructor_edit')); ?>"><a href="<?php echo site_url('admin/instructors'); ?>">Tutors</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('admins', 'admin_add', 'admin_edit', 'admin_permission', 'phase2_security')); ?>"><a href="<?php echo site_url('admin/phase2_security'); ?>">Admins & Roles</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('application_list')); ?>"><a href="<?php echo site_url('admin/instructor_application'); ?>">Verification Queue <span class="badge badge-danger-lighten"><?php echo $pending_tutor_applications; ?></span></a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('course') || has_permission('category') || has_permission('coupon')) : ?>
            <?php $course_pages = array('courses', 'courses-server-side', 'course_add', 'course_edit', 'course_quality', 'pending_courses', 'categories', 'category_add', 'category_edit', 'coupons', 'coupon_add', 'coupon_edit'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $course_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $course_pages); ?>" aria-label="Courses menu">
                    <i class="dripicons-archive"></i>
                    <span>Courses</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('courses', 'courses-server-side', 'course_edit')); ?>"><a href="<?php echo site_url('admin/courses'); ?>">Course Catalog</a></li><?php endif; ?>
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('course_quality', 'pending_courses')); ?>"><a href="<?php echo site_url('admin/course_quality'); ?>">Course Review Queue <span class="badge badge-warning-lighten"><?php echo $pending_courses; ?></span></a></li><?php endif; ?>
                    <?php if (has_permission('category')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('categories', 'category_add', 'category_edit')); ?>"><a href="<?php echo site_url('admin/categories'); ?>">Categories & Taxonomy</a></li><?php endif; ?>
                    <?php if (has_permission('coupon')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('coupons', 'coupon_add', 'coupon_edit')); ?>"><a href="<?php echo site_url('admin/coupons'); ?>">Coupons & Promotions</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('enrolment') || has_permission('student')) : ?>
            <?php $learning_pages = array('enrol_student', 'enrol_history', 'student_success', 'student_academic_progress', 'student_academic_quiz_result'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $learning_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $learning_pages); ?>" aria-label="Learning operations menu">
                    <i class="dripicons-graduation"></i>
                    <span>Learning Operations</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('enrolment')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('enrol_student', 'enrol_history')); ?>"><a href="<?php echo site_url('admin/enrol_history'); ?>">Enrollments</a></li><?php endif; ?>
                    <?php if (has_permission('student')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('student_success')); ?>"><a href="<?php echo site_url('admin/student_success'); ?>">Progress</a></li><?php endif; ?>
                    <li><a href="<?php echo site_url('admin/student_success'); ?>">Certificates</a></li>
                    <li><a href="<?php echo site_url('admin/student_success'); ?>">Cohorts/Batches</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('instructor') || has_permission('contact')) : ?>
            <?php $marketplace_pages = array('application_list', 'tutor_performance', 'instructors', 'support_tickets'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $marketplace_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $marketplace_pages); ?>" aria-label="Marketplace menu">
                    <i class="dripicons-store"></i>
                    <span>Marketplace</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('application_list')); ?>"><a href="<?php echo site_url('admin/instructor_application'); ?>">Tutor Applications <span class="badge badge-danger-lighten"><?php echo $pending_tutor_applications; ?></span></a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('tutor_performance')); ?>"><a href="<?php echo site_url('admin/tutor_performance'); ?>">Tutor Performance</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li><a href="<?php echo site_url('admin/tutor_performance'); ?>">Availability</a></li><?php endif; ?>
                    <?php if (has_permission('contact')) : ?><li><a href="<?php echo site_url('admin/support_tickets'); ?>">Reviews & Complaints</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('revenue')) : ?>
            <?php $finance_pages = array('admin_revenue', 'instructor_revenue', 'purchase_history', 'finance_ops', 'instructor_payout'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $finance_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $finance_pages); ?>" aria-label="Finance menu">
                    <i class="dripicons-wallet"></i>
                    <span>Finance</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <li class="<?php echo lv_admin_nav_active($page_name, array('admin_revenue', 'instructor_revenue')); ?>"><a href="<?php echo site_url('admin/admin_revenue'); ?>">Revenue</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('purchase_history')); ?>"><a href="<?php echo site_url('admin/purchase_history'); ?>">Payments</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('finance_ops')); ?>"><a href="<?php echo site_url('admin/finance_ops'); ?>">Refunds</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('instructor_payout')); ?>"><a href="<?php echo site_url('admin/instructor_payout'); ?>">Payouts <span class="badge badge-warning-lighten"><?php echo $pending_payouts; ?></span></a></li>
                    <li><a href="<?php echo site_url('admin/finance_ops'); ?>">Commission Rules</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('contact')) : ?>
            <?php $support_pages = array('contact', 'support_tickets'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $support_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $support_pages); ?>" aria-label="Support menu">
                    <i class="dripicons-help"></i>
                    <span>Support</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <li class="<?php echo lv_admin_nav_active($page_name, array('support_tickets')); ?>"><a href="<?php echo site_url('admin/support_tickets'); ?>">Tickets</a></li>
                    <li><a href="<?php echo site_url('admin/support_tickets'); ?>">Complaints</a></li>
                    <li><a href="<?php echo site_url('admin/support_tickets'); ?>">Escalations</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('contact')); ?>"><a href="<?php echo site_url('admin/contact'); ?>">Knowledge Base</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('blog')) : ?>
            <?php $content_pages_nav = array('content_nodes', 'content_nodes_pending', 'content_pages', 'blog', 'blog_add', 'blog_edit', 'blog_category', 'custom_page'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $content_pages_nav); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $content_pages_nav); ?>" aria-label="Content menu">
                    <i class="dripicons-network-3"></i>
                    <span>Content</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_nodes')); ?>"><a href="<?php echo site_url('admin/content_nodes'); ?>">Docs Tree</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_pages')); ?>"><a href="<?php echo site_url('admin/content_pages'); ?>">Pages <span class="badge badge-warning-lighten"><?php echo $pending_content_items; ?></span></a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('blog', 'blog_add', 'blog_edit')); ?>"><a href="<?php echo site_url('admin/blog'); ?>">Blogs</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_nodes_pending')); ?>"><a href="<?php echo site_url('admin/content_nodes_pending'); ?>">FAQs</a></li>
                    <li><a href="<?php echo site_url('admin/content_pages'); ?>">SEO</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('revenue') || has_permission('course') || has_permission('student') || has_permission('instructor') || has_permission('admin')) : ?>
            <?php $analytics_pages = array('finance_ops', 'course_quality', 'student_success', 'tutor_performance', 'audit_activity', 'ai_readiness', 'scalability_review', 'implementation_roadmap'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $analytics_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $analytics_pages); ?>" aria-label="Analytics menu">
                    <i class="dripicons-graph-bar"></i>
                    <span>Analytics</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('revenue')) : ?><li><a href="<?php echo site_url('admin/finance_ops'); ?>">Growth</a></li><?php endif; ?>
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('course_quality')); ?>"><a href="<?php echo site_url('admin/course_quality'); ?>">Course Analytics</a></li><?php endif; ?>
                    <?php if (has_permission('student')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('student_success')); ?>"><a href="<?php echo site_url('admin/student_success'); ?>">Student Success</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('tutor_performance')); ?>"><a href="<?php echo site_url('admin/tutor_performance'); ?>">Tutor Analytics</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('implementation_roadmap')); ?>"><a href="<?php echo site_url('admin/implementation_roadmap'); ?>">Roadmap</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('ai_readiness')); ?>"><a href="<?php echo site_url('admin/ai_readiness'); ?>">AI Readiness</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('scalability_review')); ?>"><a href="<?php echo site_url('admin/scalability_review'); ?>">Scalability Review</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('settings') || has_permission('admin')) : ?>
            <?php $settings_pages = array('system_settings', 'payment_settings', 'social_login', 'phase2_security', 'audit_activity', 'notification_settings', 'ai_readiness', 'scalability_review', 'implementation_roadmap'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $settings_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $settings_pages); ?>" aria-label="Settings menu">
                    <i class="dripicons-gear"></i>
                    <span>Settings</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('settings')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('system_settings')); ?>"><a href="<?php echo site_url('admin/system_settings'); ?>">Platform</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('phase2_security')); ?>"><a href="<?php echo site_url('admin/phase2_security'); ?>">Security Review</a></li><?php endif; ?>
                    <?php if (has_permission('settings')) : ?><li><a href="<?php echo site_url('admin/payment_settings'); ?>">Integrations</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('audit_activity')); ?>"><a href="<?php echo site_url('admin/audit_activity'); ?>">Audit Logs</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('implementation_roadmap')); ?>"><a href="<?php echo site_url('admin/implementation_roadmap'); ?>">Roadmap</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('ai_readiness')); ?>"><a href="<?php echo site_url('admin/ai_readiness'); ?>">AI Guardrails</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('scalability_review')); ?>"><a href="<?php echo site_url('admin/scalability_review'); ?>">Scale Readiness</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <li class="side-nav-title side-nav-item">Account</li>
        <li class="side-nav-item <?php echo lv_admin_nav_active($page_name, array('manage_profile')); ?>">
            <a href="<?php echo site_url(strtolower($this->session->userdata('role')) . '/manage_profile'); ?>" class="side-nav-link" aria-label="Manage profile">
                <i class="dripicons-user"></i>
                <span><?php echo get_phrase('manage_profile'); ?></span>
            </a>
        </li>
    </ul>
</div>
