<?php
// Sidebar badge counts come from a single cached COUNT(*) helper (60s TTL)
// instead of full-table queries on every admin page load.
$nav_counts = $this->crud_model->get_admin_nav_counts();
$pending_courses = (int)($nav_counts['pending_courses'] ?? 0);
$pending_tutor_applications = (int)($nav_counts['pending_tutor_applications'] ?? 0);
$pending_content_items = (int)($nav_counts['pending_content_items'] ?? 0);
$navigation_pending_payout_count = (int)($nav_counts['pending_payouts'] ?? 0);

if (!function_exists('lv_admin_nav_active')) {
    function lv_admin_nav_active($page_name, $pages) {
        return in_array($page_name, $pages, true) ? 'active' : '';
    }
}
if (!function_exists('lv_admin_nav_group_active')) {
    function lv_admin_nav_group_active($page_name, $pages) {
        return in_array($page_name, $pages, true) ? 'active' : '';
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
            <?php $users_pages = array('users', 'user_add', 'user_edit', 'instructors', 'instructor_add', 'instructor_edit', 'users_verification_queue', 'admins', 'admin_add', 'admin_edit', 'admin_permission', 'users_admin_roles'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $users_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $users_pages); ?>" aria-label="Users menu">
                    <i class="dripicons-user-group"></i>
                    <span>Users</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('student')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('users', 'user_add', 'user_edit')); ?>"><a href="<?php echo site_url('admin/users'); ?>">Students</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('instructors', 'instructor_add', 'instructor_edit')); ?>"><a href="<?php echo site_url('admin/instructors'); ?>">Tutors</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('admins', 'admin_add', 'admin_edit', 'admin_permission', 'users_admin_roles')); ?>"><a href="<?php echo site_url('admin/navigation_alias/users_admin_roles'); ?>">Admins & Roles</a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('users_verification_queue')); ?>"><a href="<?php echo site_url('admin/navigation_alias/users_verification_queue'); ?>">Verification Queue <span class="badge badge-danger-lighten"><?php echo $pending_tutor_applications; ?></span></a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('course') || has_permission('category') || has_permission('coupon')) : ?>
            <?php $course_pages = array('courses', 'courses-server-side', 'course_add', 'course_edit', 'course_quality', 'pending_courses', 'sponsored_courses', 'categories', 'category_add', 'category_edit', 'coupons', 'coupon_add', 'coupon_edit'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $course_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $course_pages); ?>" aria-label="Video courses menu">
                    <i class="dripicons-archive"></i>
                    <span>Video Courses</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('courses', 'courses-server-side', 'course_edit')); ?>"><a href="<?php echo site_url('admin/courses'); ?>">Video Course Catalog</a></li><?php endif; ?>
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('course_quality', 'pending_courses')); ?>"><a href="<?php echo site_url('admin/course_quality'); ?>">Video Course Review <span class="badge badge-warning-lighten"><?php echo $pending_courses; ?></span></a></li><?php endif; ?>
                    <?php if (has_permission('course')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('sponsored_courses')); ?>"><a href="<?php echo site_url('admin/sponsored_courses'); ?>">Sponsored Video Courses</a></li><?php endif; ?>
                    <?php if (has_permission('category')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('categories', 'category_add', 'category_edit')); ?>"><a href="<?php echo site_url('admin/categories'); ?>">Categories & Taxonomy</a></li><?php endif; ?>
                    <?php if (has_permission('coupon')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('coupons', 'coupon_add', 'coupon_edit')); ?>"><a href="<?php echo site_url('admin/coupons'); ?>">Coupons & Promotions</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('blog')) : ?>
            <?php $content_studio_pages = array('content_nodes', 'content_nodes_pending', 'content_pages'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $content_studio_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $content_studio_pages); ?>" aria-label="Content studio menu">
                    <i class="mdi mdi-book-open-page-variant"></i>
                    <span>Content Studio</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php $content_review_filter = $this->input->get('review_filter') ?: 'book'; ?>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_nodes')); ?> lv-course-content-nav <?php echo ($page_name === 'content_nodes') ? 'lv-expanded' : ''; ?>">
                        <a href="javascript:void(0);" class="lv-course-content-toggle"><i class="mdi mdi-book-edit-outline mr-1"></i>Book Studio <span class="menu-arrow"></span></a>
                        <ul class="side-nav-third-level lv-course-content-children" aria-expanded="<?php echo ($page_name === 'content_nodes') ? 'true' : 'false'; ?>" style="padding-left:18px; list-style:none; display:<?php echo ($page_name === 'content_nodes') ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo site_url('admin/content_nodes?action=create_notes'); ?>">Create Book / Notes</a></li>
                            <li><a href="<?php echo site_url('admin/content_nodes?action=upload_notes'); ?>">Upload Book / Notes</a></li>
                            <li><a href="<?php echo site_url('admin/content_nodes?action=create_blog'); ?>">Create Blog / Article</a></li>
                        </ul>
                    </li>
                    <li class="<?php echo ($page_name === 'content_nodes_pending' && $content_review_filter === 'book') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/content_nodes_pending?review_filter=book'); ?>">Books Review <span class="badge badge-warning-lighten"><?php echo $pending_content_items; ?></span></a></li>
                    <li class="<?php echo ($page_name === 'content_nodes_pending' && $content_review_filter === 'article') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/content_nodes_pending?review_filter=article'); ?>">Articles Review</a></li>
                    <li class="<?php echo ($page_name === 'content_nodes_pending' && $content_review_filter === 'published') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/content_nodes_pending?review_filter=published'); ?>">Published Content</a></li>
                    <li class="<?php echo ($page_name === 'content_nodes_pending' && $content_review_filter === 'recycle') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/content_nodes_pending?review_filter=recycle'); ?>">Recycle Bin</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_pages')); ?>"><a href="<?php echo site_url('admin/content_pages'); ?>">Content Governance</a></li>
                </ul>
            </li>

            <?php $exam_pages = array('question_bank', 'content_public_exams', 'content_exam_builder', 'exam_pattern_builder', 'assessment_workflow', 'exam_analytics', 'moderation_center'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $exam_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $exam_pages); ?>" aria-label="Exams and mock tests menu">
                    <i class="mdi mdi-clipboard-text-outline"></i>
                    <span>Exams & Mock Tests</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php $qb_tab = $this->input->get('qb_tab') ?: 'create_exam'; ?>
                    <li class="<?php echo ($page_name === 'question_bank') ? 'active lv-expanded' : ''; ?> lv-question-bank-nav">
                        <a href="javascript:void(0);" class="lv-question-bank-toggle"><i class="mdi mdi-database-search mr-1"></i>Question Bank <span class="menu-arrow"></span></a>
                        <ul class="side-nav-third-level lv-question-bank-children" aria-expanded="<?php echo ($page_name === 'question_bank') ? 'true' : 'false'; ?>" style="padding-left:18px; list-style:none; display:<?php echo ($page_name === 'question_bank') ? 'block' : 'none'; ?>;">
                            <li class="<?php echo ($page_name === 'question_bank' && $qb_tab === 'create_exam') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/question_bank?qb_tab=create_exam'); ?>">1. Setup Exams &amp; Sections</a></li>
                            <li class="<?php echo ($page_name === 'question_bank' && $qb_tab === 'upload') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/question_bank?qb_tab=upload'); ?>">2. Add Questions (Single &amp; Bulk)</a></li>
                            <li class="<?php echo ($page_name === 'question_bank' && $qb_tab === 'search') ? 'active' : ''; ?>"><a href="<?php echo site_url('admin/question_bank?qb_tab=search'); ?>">3. Manage Questions</a></li>
                        </ul>
                    </li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('content_public_exams', 'content_exam_builder', 'exam_pattern_builder')); ?>"><a href="<?php echo site_url('admin/content_public_exams'); ?>">Exam Pattern Builder</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('assessment_workflow')); ?>"><a href="<?php echo site_url('assessment-center'); ?>">Assessment Center</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('moderation_center')); ?>"><a href="<?php echo site_url('admin/moderation_center'); ?>">Admin Review Center</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('exam_analytics')); ?>"><a href="<?php echo site_url('admin/exam_analytics'); ?>">Exam Analytics</a></li>
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
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('instructor') || has_permission('contact')) : ?>
            <?php $marketplace_pages = array('application_list', 'tutor_performance'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $marketplace_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $marketplace_pages); ?>" aria-label="Marketplace menu">
                    <i class="dripicons-store"></i>
                    <span>Marketplace</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('application_list')); ?>"><a href="<?php echo site_url('admin/instructor_application'); ?>">Tutor Applications <span class="badge badge-danger-lighten"><?php echo $pending_tutor_applications; ?></span></a></li><?php endif; ?>
                    <?php if (has_permission('instructor')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('tutor_performance')); ?>"><a href="<?php echo site_url('admin/tutor_performance'); ?>">Tutor Performance</a></li><?php endif; ?>
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
                    <li class="<?php echo lv_admin_nav_active($page_name, array('instructor_payout')); ?>"><a href="<?php echo site_url('admin/instructor_payout'); ?>">Payouts <span class="badge badge-warning-lighten"><?php echo $navigation_pending_payout_count; ?></span></a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('contact') || has_permission('user')) : ?>
            <?php $support_pages = array('contact', 'support_tickets', 'communication_center'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $support_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $support_pages); ?>" aria-label="Support menu">
                    <i class="dripicons-help"></i>
                    <span>Support</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <li class="<?php echo lv_admin_nav_active($page_name, array('communication_center')); ?>"><a href="<?php echo site_url('admin/communication-center'); ?>">Communication Center</a></li>
                    <?php if (has_permission('contact')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('support_tickets')); ?>"><a href="<?php echo site_url('admin/support_tickets'); ?>">Tickets</a></li><?php endif; ?>
                    <?php if (has_permission('contact')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('contact')); ?>"><a href="<?php echo site_url('admin/contact'); ?>">Knowledge Base</a></li><?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('admin')) : ?>
            <?php $analytics_pages = array('analytics_operations', 'operational_quality', 'ai_readiness', 'scalability_review', 'implementation_roadmap'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $analytics_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $analytics_pages); ?>" aria-label="Analytics menu">
                    <i class="dripicons-graph-bar"></i>
                    <span>Analytics</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <li class="<?php echo lv_admin_nav_active($page_name, array('analytics_operations','operational_quality')); ?>"><a href="<?php echo site_url('admin/operational_quality'); ?>">Operational Quality</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('implementation_roadmap')); ?>"><a href="<?php echo site_url('admin/implementation_roadmap'); ?>">Roadmap</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('ai_readiness')); ?>"><a href="<?php echo site_url('admin/ai_readiness'); ?>">AI Readiness</a></li>
                    <li class="<?php echo lv_admin_nav_active($page_name, array('scalability_review')); ?>"><a href="<?php echo site_url('admin/scalability_review'); ?>">Scalability Review</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_permission('settings') || has_permission('admin')) : ?>
            <?php $settings_pages = array('system_settings', 'payment_settings', 'social_login', 'audit_activity', 'notification_settings', 'security_login_alerts'); ?>
            <li class="side-nav-item <?php echo lv_admin_nav_group_active($page_name, $settings_pages); ?>">
                <a href="javascript:void(0);" class="side-nav-link <?php echo lv_admin_nav_group_active($page_name, $settings_pages); ?>" aria-label="Settings menu">
                    <i class="dripicons-gear"></i>
                    <span>Settings</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="side-nav-second-level" aria-expanded="false">
                    <?php if (has_permission('settings')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('system_settings')); ?>"><a href="<?php echo site_url('admin/system_settings'); ?>">Platform</a></li><?php endif; ?>
                    <?php if (has_permission('settings')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('payment_settings', 'social_login')); ?>"><a href="<?php echo site_url('admin/payment_settings'); ?>">Integrations</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('security_login_alerts')); ?>"><a href="<?php echo site_url('admin/security_login_alerts'); ?>">Login Alerts</a></li><?php endif; ?>
                    <?php if (has_permission('admin')) : ?><li class="<?php echo lv_admin_nav_active($page_name, array('audit_activity')); ?>"><a href="<?php echo site_url('admin/audit_activity'); ?>">Audit Logs</a></li><?php endif; ?>
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

<style>
.lv-course-content-nav.lv-expanded > .lv-course-content-children{display:block!important;}
.lv-question-bank-nav.lv-expanded > .lv-question-bank-children{display:block!important;}
.lv-course-content-toggle,.lv-question-bank-toggle{cursor:pointer;}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
  function bindNestedMenu(toggleSelector, navSelector, childrenSelector){
    document.querySelectorAll(toggleSelector).forEach(function(toggle){
    toggle.addEventListener('click', function(e){
      e.preventDefault();
      var li = toggle.closest(navSelector);
      if (!li) return;
      var expanded = !li.classList.contains('lv-expanded');
      li.classList.toggle('lv-expanded', expanded);
      var child = li.querySelector(childrenSelector);
      if (child) {
        child.style.display = expanded ? 'block' : 'none';
        child.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      }
    });
    });
  }
  bindNestedMenu('.lv-course-content-toggle', '.lv-course-content-nav', '.lv-course-content-children');
  bindNestedMenu('.lv-question-bank-toggle', '.lv-question-bank-nav', '.lv-question-bank-children');
});
</script>
