<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> Video Courses
                    <a href="<?php echo site_url('user/course_form/add_course'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle"><i class="mdi mdi-plus"></i>Add New Video Course</a>
                </h4>
                <p class="text-muted mb-0">Use this area for structured video/LMS courses. Use <strong>Publish Books</strong> for books, notes, uploaded notes, and articles.</p>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>
<div class="row">
    <div class="col-12">
        <div class="card widget-inline">
            <div class="card-body p-0">
                <?php
                    $course_manager_counts = isset($course_manager_counts) && is_array($course_manager_counts) ? $course_manager_counts : [];
                    $active_count = (int)($course_manager_counts['active'] ?? 0);
                    $pending_count = (int)($course_manager_counts['pending'] ?? 0);
                    $draft_count = (int)($course_manager_counts['draft'] ?? 0);
                    $free_count = (int)($course_manager_counts['free'] ?? 0);
                    $paid_count = (int)($course_manager_counts['paid'] ?? 0);
                    $counter_category_query = isset($selected_category_id) ? $selected_category_id : 'all';
                ?>
                <div class="row no-gutters">
                    <div class="col">
                        <a href="<?php echo site_url('user/courses?category_id=' . rawurlencode($counter_category_query) . '&status=active&price=all&button='); ?>" class="text-secondary">
                            <div class="card shadow-none m-0">
                                <div class="card-body text-center">
                                    <i class="dripicons-link text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $active_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('active_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="<?php echo site_url('user/courses?category_id=' . rawurlencode($counter_category_query) . '&status=pending&price=all&button='); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-link-broken text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $pending_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('pending_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="<?php echo site_url('user/courses?category_id=' . rawurlencode($counter_category_query) . '&status=draft&price=all&button='); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-bookmark text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $draft_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('draft_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="<?php echo site_url('user/courses?category_id=' . rawurlencode($counter_category_query) . '&status=all&price=free&button='); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-star text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $free_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('free_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="<?php echo site_url('user/courses?category_id=' . rawurlencode($counter_category_query) . '&status=all&price=paid&button='); ?>" class="text-secondary">
                            <div class="card shadow-none m-0 border-left">
                                <div class="card-body text-center">
                                    <i class="dripicons-tags text-muted" style="font-size: 24px;"></i>
                                    <h3><span><?php echo $paid_count; ?></span></h3>
                                    <p class="text-muted font-15 mb-0"><?php echo get_phrase('paid_courses'); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>

                </div> <!-- end row -->
            </div>
        </div> <!-- end card-box-->
    </div> <!-- end col-->
</div>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('course_list'); ?></h4>
                <form class="row justify-content-center" action="<?php echo site_url('user/courses'); ?>" method="get">
                    <!-- Course Categories -->
                    <div class="col-xl-3">
                        <div class="form-group">
                            <label for="category_id"><?php echo get_phrase('categories'); ?></label>
                            <select class="form-control select2" data-toggle="select2" name="category_id" id="category_id">
                                <option value="<?php echo 'all'; ?>" <?php if($selected_category_id == 'all') echo 'selected'; ?>><?php echo get_phrase('all'); ?></option>
                                <?php foreach ($categories->result_array() as $category): ?>
                                    <optgroup label="<?php echo $category['name']; ?>">
                                        <?php $sub_categories = $this->crud_model->get_sub_categories($category['id']);
                                        foreach ($sub_categories as $sub_category): ?>
                                        <option value="<?php echo $sub_category['id']; ?>" <?php if($selected_category_id == $sub_category['id']) echo 'selected'; ?>><?php echo $sub_category['name']; ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Course Status -->
                <div class="col-xl-3">
                    <div class="form-group">
                        <label for="status"><?php echo get_phrase('status'); ?></label>
                        <select class="form-control select2" data-toggle="select2" name="status" id = 'status'>
                            <option value="all" <?php if($selected_status == 'all') echo 'selected'; ?>><?php echo get_phrase('all'); ?></option>
                            <option value="active" <?php if($selected_status == 'active') echo 'selected'; ?>><?php echo get_phrase('active'); ?></option>
                            <option value="pending" <?php if($selected_status == 'pending') echo 'selected'; ?>><?php echo get_phrase('pending'); ?></option>
                            <option value="draft" <?php if($selected_status == 'draft') echo 'selected'; ?>><?php echo get_phrase('draft'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Course Price -->
                <div class="col-xl-3">
                    <div class="form-group">
                        <label for="price"><?php echo get_phrase('price'); ?></label>
                        <select class="form-control select2" data-toggle="select2" name="price" id = 'price'>
                            <option value="all"  <?php if($selected_price == 'all' ) echo 'selected'; ?>><?php echo get_phrase('all'); ?></option>
                            <option value="free" <?php if($selected_price == 'free') echo 'selected'; ?>><?php echo get_phrase('free'); ?></option>
                            <option value="paid" <?php if($selected_price == 'paid') echo 'selected'; ?>><?php echo get_phrase('paid'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="col-xl-3">
                    <label for=".." class="text-white">..</label>
                    <button type="submit" class="btn btn-primary btn-block" name="button"><?php echo get_phrase('filter'); ?></button>
                </div>
            </form>

            <div class="table-responsive-sm mt-4">
                <?php if (count($courses) > 0): ?>
                    <table id="course-datatable-server-side" class="table table-striped dt-responsive nowrap" width="100%" data-page-length='25'>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('title'); ?></th>
                                <th><?php echo get_phrase('category'); ?></th>
                                <th><?php echo get_phrase('lesson_and_section'); ?></th>
                                <th><?php echo get_phrase('enrolled_student'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('price'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                    </table>
                <?php endif; ?>
                <?php if (count($courses) == 0): ?>
                    <div class="img-fluid w-100 text-center">
                      <img style="opacity: 1; width: 100px;" src="<?php echo base_url('assets/backend/images/file-search.svg'); ?>"><br>
                      <?php echo get_phrase('no_data_found'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
