<?php
$status_wise_courses = $this->crud_model->get_status_wise_courses();
?>
<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu left-side-menu-detached">
	<div class="leftbar-user">
		<a href="javascript: void(0);">
			<img src="<?php echo $this->user_model->get_user_image_url($this->session->userdata('user_id')); ?>" alt="user-image" height="42" class="rounded-circle shadow-sm">
			<?php
			$admin_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
			?>
			<span class="leftbar-user-name"><?php echo $admin_details['first_name'] . ' ' . $admin_details['last_name']; ?></span>
		</a>
	</div>

	<!--- Sidemenu -->
	<ul class="metismenu side-nav side-nav-light">

		<li class="side-nav-title side-nav-item"><?php echo get_phrase('navigation'); ?></li>

		<li class="side-nav-item <?php if ($page_name == 'dashboard') echo 'active'; ?>">
			<a href="<?php echo site_url('admin/dashboard'); ?>" class="side-nav-link">
				<i class="dripicons-view-apps"></i>
				<span><?php echo get_phrase('dashboard'); ?></span>
			</a>
		</li>

		<?php if (has_permission('course')) : ?>
			<li class="side-nav-item <?php if ($page_name == 'courses' || $page_name == 'course_add' || $page_name == 'course_edit' || $page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit' || $page_name == 'coupons' || $page_name == 'coupon_add' || $page_name == 'coupon_edit' || $page_name == 'add_bundle' || $page_name == 'manage_course_bundle' || $page_name == 'edit_bundle' || $page_name == 'active_bundle_subscription_report' || $page_name == 'expire_bundle_subscription_report' || $page_name == 'bundle_invoice') echo 'active'; ?>">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'courses' || $page_name == 'course_add' || $page_name == 'course_edit' || $page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit' || $page_name == 'coupons' || $page_name == 'coupon_add' || $page_name == 'coupon_edit') : ?> active <?php endif; ?>">
					<i class="dripicons-archive"></i>
					<span> <?php echo get_phrase('courses'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">
					<?php if (has_permission('course')) : ?>
						<li class="<?php if ($page_name == 'courses' || $page_name == 'course_edit') echo 'active'; ?>">
							<a href="<?php echo site_url('admin/courses'); ?>"><?php echo get_phrase('manage_courses'); ?></a>
						</li>
					<?php endif; ?>

					<?php if (has_permission('course')) : ?>
						<li class="<?php if ($page_name == 'course_add') echo 'active'; ?>">
							<a href="<?php echo site_url('admin/course_form/add_course'); ?>"><?php echo get_phrase('add_new_course'); ?></a>
						</li>
					<?php endif; ?>

					<?php if (has_permission('category')) : ?>
						<li class="<?php if ($page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit') echo 'active'; ?>">
							<a href="<?php echo site_url('admin/categories'); ?>"><?php echo get_phrase('course_category'); ?></a>
						</li>
					<?php endif; ?>
					<?php if (has_permission('coupon')) : ?>
						<li class="<?php if ($page_name == 'coupons' || $page_name == 'coupon_add' || $page_name == 'coupon_edit') echo 'active'; ?>">
							<a href="<?php echo site_url('admin/coupons'); ?>">
								<?php echo get_phrase('coupons'); ?>
							</a>
						</li>
					<?php endif; ?>

					<?php if (addon_status('course_bundle')) : ?>
						<li class="side-nav-item">
							<a href="javascript: void(0);" aria-expanded="false"><?php echo get_phrase('course_bundle'); ?>
								<span class="menu-arrow"></span>
							</a>
							<ul class="side-nav-third-level" aria-expanded="false">
								<li class="<?php if ($page_name == 'add_bundle') echo 'active'; ?>">
									<a href="<?php echo site_url('addons/bundle/add_bundle_form'); ?>"><?php echo get_phrase('add_new_bundle'); ?></a>
								</li>
								<li class="<?php if ($page_name == 'manage_course_bundle') echo 'active'; ?>">
									<a href="<?php echo site_url('addons/bundle/manage_bundle'); ?>"><?php echo get_phrase('manage_bundle'); ?></a>
								</li>
								<li class="<?php if ($page_name == 'active_bundle_subscription_report' || $page_name == 'expire_bundle_subscription_report' || $page_name == 'bundle_invoice') echo 'active'; ?>">
									<a href="<?php echo site_url('addons/bundle/subscription_report/active'); ?>"><?php echo get_phrase('subscription_report'); ?></a>
								</li>
							</ul>
						</li>
					<?php endif; ?>
				</ul>
			</li>
		<?php endif; ?>

		<!-- bootcamp addon -->
		<?php if (addon_status('bootcamp')) : ?>
			<li class="side-nav-item <?php if ($page_name == 'bootcamp_list' || $page_name == 'bootcamp_form' || $page_name == 'bootcamp_payment_invoice') : ?> active <?php endif; ?>">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'bootcamp_form' || $page_name == 'bootcamp_list' || $page_name == 'bootcamp_payment_invoice') : ?> active <?php endif; ?>">
					<i class="dripicons-user-group"></i>
					<span> <?php echo get_phrase('bootcamp'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">

					<!-- live class -->
					<li class="<?php if ($page_name == 'bootcamp_live_classes') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/bootcamp/list'); ?>"><?php echo get_phrase('bootcamp_list'); ?></a>
					</li>

					<!-- add bootcamp -->
					<li class="<?php if ($page_name == 'bootcamp_bootcamp_form') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/bootcamp/action/form'); ?>"><?php echo get_phrase('add_bootcamp'); ?></a>
					</li>

					<!-- category -->
					<li class="<?php if ($page_name == 'category') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/bootcamp/category'); ?>"><?php echo get_phrase('category'); ?></a>
					</li>

					<!-- payment -->
					<li class="<?php if ($page_name == 'bootcamp_payment_report' || $page_name == 'bootcamp_payment_invoice') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/bootcamp/payment_report'); ?>"><?php echo get_phrase('payment'); ?></a>
					</li>
				</ul>
			</li>
		<?php endif; ?>

		<!-- team training start -->
		<?php if (addon_status('team_training')) : ?>
			<li class="side-nav-item">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'team_packages' || $page_name == 'team_package_add' || $page_name == 'team_package_edit'  || $page_name == 'team_package_purchase_history' || $page_name == 'teams-server-side' || $page_name == 'team-details-page') : ?> active <?php endif; ?>">
					<i class="dripicons-document"></i>
					<span> <?php echo get_phrase('team_training'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level <?php if ($page_name == 'team_packages' || $page_name == 'team_package_add' || $page_name == 'team_package_edit'  || $page_name == 'team_package_purchase_history' || $page_name == 'teams-server-side' || $page_name == 'team-details-page') echo 'in'; ?>" aria-expanded="false">
					<li class="<?php if ($page_name == 'team_packages') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/team_training/team_packages'); ?>"><?php echo get_phrase('manage_packages'); ?></a>
					</li>
					<li class="<?php if ($page_name == 'team_package_add') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/team_training/team_package_form/add_team_package_form'); ?>"><?php echo get_phrase('add_new_package'); ?></a>
					</li>


					<li class="<?php if ($page_name == 'team_package_purchase_history') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/team_training/purchase_history'); ?>"><?php echo get_phrase('purchase_history'); ?></a>
					</li>

					<li class="<?php if ($page_name == 'teams-server-side' || $page_name == 'team-details-page') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/team_training/teams_list'); ?>"><?php echo get_phrase('teams'); ?></a>
					</li>

				</ul>
			</li>
		<?php endif; ?>
		<!-- team training end -->

		<?php if (addon_status('tutor_booking')) : ?>
			<li class="side-nav-item">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'tutor_inactive_booking_list' || $page_name == 'tutor_schedule_list' || $page_name == 'tutor_inactive_schedule_list'  || $page_name == 'tutor_live_class_settings' || $page_name == 'booked_schedule_details' || $page_name == 'tutor_caregories' || $page_name == 'add_schedule' || $page_name == 'tutor_booking_list') : ?> active <?php endif; ?>">
					<i class="dripicons-document"></i>
					<span> <?php echo get_phrase('tutor_booking'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level <?php if ($page_name == 'tutor_inactive_booking_list' || $page_name == 'tutor_schedule_list' || $page_name == 'tutor_inactive_schedule_list'  || $page_name == 'tutor_live_class_settings' || $page_name == 'booked_schedule_details' || $page_name == 'tutor_caregories' || $page_name == 'add_schedule' || $page_name == 'tutor_booking_list') echo 'in'; ?>" aria-expanded="false">
					<li class="<?php if ($page_name == 'tutor_caregories') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/tutor_booking/tutor_categories'); ?>"><?php echo get_phrase('subject_category'); ?></a>
					</li>
					<li class="<?php if ($page_name == 'add_schedule') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/tutor_booking/schedule'); ?>"><?php echo get_phrase('add_booking'); ?></a>
					</li>

					<li class="<?php if ($page_name == 'tutor_booking_list' || $page_name == 'tutor_inactive_booking_list' || $page_name == 'tutor_schedule_list' || $page_name == 'tutor_inactive_schedule_list') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/tutor_booking/tutor_booking_list'); ?>"><?php echo get_phrase('all bookings'); ?></a>
					</li>

					<li class="<?php if ($page_name == 'booked_schedule_details') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/tutor_booking/booked_schedules'); ?>"><?php echo get_phrase('Booked Schedules'); ?></a>
					</li>
				</ul>
			</li>
		<?php endif; ?>

		<?php if (addon_status('ebook')) : ?>
			<li class="side-nav-item">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'all_ebooks' || $page_name == 'add_ebook' || $page_name == 'ebook_edit') : ?> active <?php endif; ?>">
					<i class="dripicons-document"></i>
					<span> <?php echo get_phrase('ebook'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level <?php if ($page_name == 'ebook_edit') echo 'in'; ?>" aria-expanded="false">
					<li class="<?php if ($page_name == 'all_ebooks' || $page_name == 'ebook_edit') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/ebook_manager/ebook'); ?>"><?php echo get_phrase('all_ebooks'); ?></a>
					</li>
					<li class="<?php if ($page_name == 'add_ebook') echo 'active'; ?>">
						<a href="<?php echo site_url('ebook_manager/add_ebook'); ?>"><?php echo get_phrase('add_ebook'); ?></a>
					</li>
					<li class="<?php if ($page_name == 'ebook_payment_history') echo 'active'; ?>">
						<a href="javascript: void(0);" class="<?php if ($page_name == 'admin_revenue' || $page_name == 'instructor_revenue') : ?> active <?php endif; ?>" aria-expanded="false"><?php echo get_phrase('payment_history'); ?>
							<span class="menu-arrow"></span>
						</a>

						<ul class="side-nav-third-level" aria-expanded="false">
							<li class="<?php if ($page_name == 'admin_revenue') : ?> active <?php endif; ?>">
								<a href="<?php echo site_url('addons/ebook_manager/payment_history/admin_revenue'); ?>"><?php echo get_phrase('admin_revenue'); ?></a>
							</li>
							<li class="<?php if ($page_name == 'instructor_revenue') echo 'active'; ?>">
								<a href="<?php echo site_url('addons/ebook_manager/payment_history/instructor_revenue'); ?>"><?php echo get_phrase('instructor_revenue'); ?></a>
							</li>
						</ul>
					</li>

					<li class="<?php if ($page_name == 'ebook_category') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/ebook_manager/ebook_category'); ?>"><?php echo get_phrase('category'); ?></a>
					</li>
				</ul>
			</li>
		<?php endif; ?>

		<?php if (has_permission('enrolment')) : ?>
			<li class="side-nav-item <?php if ($page_name == 'enrol_history' || $page_name == 'enrol_student') : ?> active <?php endif; ?>">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'enrol_history' || $page_name == 'enrol_student') : ?> active <?php endif; ?>">
					<i class="dripicons-network-3"></i>
					<span> <?php echo get_phrase('Enrollments'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">
					<li class="<?php if ($page_name == 'enrol_student') echo 'active'; ?>">
						<a href="<?php echo site_url('admin/enrol_student'); ?>"><?php echo get_phrase('course_enrollment'); ?></a>
					</li>
					<li class="<?php if ($page_name == 'enrol_history') echo 'active'; ?>">
						<a href="<?php echo site_url('admin/enrol_history'); ?>"><?php echo get_phrase('enrol_history'); ?></a>
					</li>
				</ul>
			</li>
		<?php endif; ?>

		<?php if (has_permission('revenue')) : ?>
			<li class="side-nav-item">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'admin_revenue' || $page_name == 'instructor_revenue' || $page_name == 'purchase_history' || $page_name == 'invoice') : ?> active <?php endif; ?>">
					<i class="dripicons-box"></i>
					<span> <?php echo get_phrase('report'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">
					<li class="<?php if ($page_name == 'admin_revenue') echo 'active'; ?>"> <a href="<?php echo site_url('admin/admin_revenue'); ?>"><?php echo get_phrase('admin_revenue'); ?></a> </li>
					<?php if (get_settings('allow_instructor') == 1) : ?>
						<li class="<?php if ($page_name == 'instructor_revenue') echo 'active'; ?>">
							<a href="<?php echo site_url('admin/instructor_revenue'); ?>">
								<?php echo get_phrase('instructor_revenue'); ?>
							</a>
						</li>
					<?php endif; ?>
					<li class="<?php if ($page_name == 'purchase_history') echo 'active'; ?>"> <a href="<?php echo site_url('admin/purchase_history'); ?>"><?php echo get_phrase('purchase_history'); ?></a> </li>
				</ul>
			</li>
		<?php endif; ?>

		<?php if (addon_status('affiliate_course')) :
			$CI    = &get_instance();
			$CI->load->model('addons/affiliate_course_model');
		?>
			<li class="side-nav-item <?php if ($page_name == 'active_affiliator' || $page_name == 'suspend_affiliator' || $page_name == 'pending_affiliator' || $page_name == 'course_affiliation_history' || $page_name == 'affiliation_course_payouts' || $page_name == 'affiliator_add' || $page_name == 'affiliate_addon_settings') : ?> active <?php endif; ?>">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'active_affiliator' || $page_name == 'suspend_affiliator' || $page_name == 'pending_affiliator' || $page_name == 'course_affiliation_history' || $page_name == 'affiliation_course_payouts' || $page_name == 'affiliator_add' || $page_name == 'affiliate_addon_settings') : ?> active <?php endif; ?>">
					<i class="dripicons-box"></i>
					<span> <?php echo get_phrase('Affiliate'); ?> </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">
					<li class="<?php if ($page_name == 'active_affiliator' || $page_name == 'suspend_affiliator' || $page_name == 'pending_affiliator') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/affiliate_course/active_affiliator'); ?>">
							<?php echo get_phrase('affliliator_list'); ?>
							<span class="badge badge-danger-lighten">
								<?php echo $CI->affiliate_course_model->get_pending_affiliator_application()->num_rows(); ?>
							</span>
						</a>
					</li>

					<li class="<?php if ($page_name == 'course_affiliation_history') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/affiliate_course/course_affiliation_history'); ?>">
							<?php echo get_phrase('affiliation_history'); ?>
						</a>
					</li>

					<li class="<?php if ($page_name == 'affiliation_course_payouts') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/affiliate_course/affiliation_course_payouts'); ?>">
							<?php echo get_phrase('Payouts'); ?>
							<span class="badge badge-danger-lighten">
								<?php echo $CI->affiliate_course_model->get_table_pending_course_amount_info_from_course_affiliation_payouts()->num_rows(); ?>
							</span>
						</a>
					</li>

					<li class="<?php if ($page_name == 'affiliator_add') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/affiliate_course/affiliator_form'); ?>">
							<?php echo get_phrase('Create_affiliator'); ?>
						</a>
					</li>

					<li class="<?php if ($page_name == 'affiliate_addon_settings') echo 'active'; ?>">
						<a href="<?php echo site_url('addons/affiliate_course/affiliate_addon_settings'); ?>">
							<?php echo get_phrase('affiliation_settings'); ?>
						</a>
					</li>
				</ul>
			</li>
		<?php endif; ?>

		<?php if (has_permission('user')) : ?>
            <!-- ... your existing Users menu continues ... -->
		<?php endif; ?>

		<?php if (has_permission('contact')) : ?>
            <!-- ... your existing Contact menu continues ... -->
		<?php endif; ?>

		<?php if (false && has_permission('blog')) : ?>
		<?php endif; ?>

		<!-- ✅ NEW: Content (Docs) menu -->
		<?php if (has_permission('blog')) : // reuse blog permission for now (later we can add content permission) ?>
			<li class="side-nav-item <?php if ($page_name == 'content_nodes' || $page_name == 'content_pages' || $page_name == 'content_nodes_pending') : ?> active <?php endif; ?>">
				<a href="javascript: void(0);" class="side-nav-link <?php if ($page_name == 'content_nodes' || $page_name == 'content_pages' || $page_name == 'content_nodes_pending') : ?> active <?php endif; ?>">
					<i class="dripicons-network-3"></i>
					<span> Content (Docs) </span>
					<span class="menu-arrow"></span>
				</a>
				<ul class="side-nav-second-level" aria-expanded="false">
					<li class="<?php if ($page_name == 'content_nodes') echo 'active'; ?>">
						<a href="<?php echo site_url('admin/content_nodes'); ?>">Nodes (Tree)</a>
					</li>
					<li class="<?php if ($page_name == 'content_nodes_pending') echo 'active'; ?>">
						<a href="<?php echo site_url('admin/content_nodes_pending'); ?>">Pending Nodes <span class="badge badge-danger-lighten"><?php echo count($this->content_docs_model->get_pending_nodes()) + $this->crud_model->get_instructors_pending_blog()->num_rows(); ?></span></a>
					</li>
					<li class="<?php if ($page_name == 'content_pages') echo 'active'; ?>">
						<a href="<?php echo site_url('admin/content_pages'); ?>">Pages</a>
					</li>
				</ul>
			</li>
		<?php endif; ?>
		<!-- ✅ END: Content (Docs) menu -->

		<!-- ... rest of your existing menus (customer_support, addons, themes, settings, manage_profile) ... -->

		<li class="side-nav-item <?php if ($page_name == 'manage_profile') echo 'active'; ?>">
			<a href="<?php echo site_url(strtolower($this->session->userdata('role')) . '/manage_profile'); ?>" class="side-nav-link">
				<i class="dripicons-user"></i>
				<span><?php echo get_phrase('manage_profile'); ?></span>
			</a>
		</li>
	</ul>
</div>