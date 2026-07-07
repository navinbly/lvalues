<?php
$edit = !empty($edit_sponsored_course) ? $edit_sponsored_course : [];
$is_edit = !empty($edit);
?>
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                    <div>
                        <h4 class="page-title mb-1"><i class="mdi mdi-bullhorn title_icon"></i> Sponsored Courses</h4>
                        <p class="text-muted mb-0">Publish third-party learning programs on the homepage without changing the core course catalog.</p>
                    </div>
                    <?php if ($is_edit): ?>
                        <a href="<?php echo site_url('admin/sponsored_courses'); ?>" class="btn btn-outline-secondary btn-sm">Cancel edit</a>
                    <?php endif; ?>
                </div>

                <form action="<?php echo site_url('admin/sponsored_courses/save'); ?>" method="post" enctype="multipart/form-data" class="border rounded p-3 mb-4">
                    <input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Course title <span class="text-danger">*</span></label>
                            <input type="text" name="course_title" class="form-control" required maxlength="191" value="<?php echo html_escape($edit['course_title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Provider / Institute <span class="text-danger">*</span></label>
                            <input type="text" name="provider_name" class="form-control" required maxlength="191" value="<?php echo html_escape($edit['provider_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <textarea name="course_description" class="form-control" rows="3"><?php echo html_escape($edit['course_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Start date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo html_escape($edit['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>End date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo html_escape($edit['end_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Class timing</label>
                            <input type="text" name="class_timing" class="form-control" maxlength="191" placeholder="Weekends, 7-9 PM" value="<?php echo html_escape($edit['class_timing'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Fees</label>
                            <input type="number" min="0" step="0.01" name="fees" class="form-control" value="<?php echo html_escape($edit['fees'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Mode</label>
                            <?php $mode = $edit['mode'] ?? 'online'; ?>
                            <select name="mode" class="form-control">
                                <option value="online" <?php if ($mode === 'online') echo 'selected'; ?>>Online</option>
                                <option value="offline" <?php if ($mode === 'offline') echo 'selected'; ?>>Offline</option>
                                <option value="hybrid" <?php if ($mode === 'hybrid') echo 'selected'; ?>>Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-5 form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" maxlength="255" value="<?php echo html_escape($edit['location'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Registration link</label>
                            <input type="url" name="registration_link" class="form-control" maxlength="500" value="<?php echo html_escape($edit['registration_link'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Contact email</label>
                            <input type="email" name="contact_email" class="form-control" maxlength="191" value="<?php echo html_escape($edit['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Contact phone</label>
                            <input type="text" name="contact_phone" class="form-control" maxlength="60" value="<?php echo html_escape($edit['contact_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Status</label>
                            <?php $status = $edit['status'] ?? 'draft'; ?>
                            <select name="status" class="form-control">
                                <option value="draft" <?php if ($status === 'draft') echo 'selected'; ?>>Draft</option>
                                <option value="published" <?php if ($status === 'published') echo 'selected'; ?>>Published</option>
                                <option value="unpublished" <?php if ($status === 'unpublished') echo 'selected'; ?>>Unpublished</option>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Display order</label>
                            <input type="number" name="display_order" class="form-control" value="<?php echo html_escape($edit['display_order'] ?? 0); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Banner image</label>
                            <input type="file" name="banner_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <small class="text-muted">Allowed: JPG, PNG, WEBP. Max 2MB.</small>
                            <?php if (!empty($edit['banner_image'])): ?>
                                <div class="mt-2"><img src="<?php echo base_url($edit['banner_image']); ?>" alt="Sponsored course banner preview" style="max-width:180px;border-radius:6px;"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update sponsored course' : 'Add sponsored course'; ?></button>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Course</th>
                                <th>Schedule</th>
                                <th>Fee / Mode</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sponsored_courses)): ?>
                                <?php foreach ($sponsored_courses as $course): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($course['banner_image'])): ?>
                                                <img src="<?php echo base_url($course['banner_image']); ?>" alt="<?php echo html_escape($course['course_title']); ?> banner" style="width:86px;height:54px;object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                <span class="badge badge-light">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo html_escape($course['course_title']); ?></strong>
                                            <div class="text-muted small"><?php echo html_escape($course['provider_name']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo html_escape($course['class_timing'] ?: 'Timing not set'); ?></div>
                                            <small class="text-muted"><?php echo html_escape(trim(($course['start_date'] ?: '') . ' - ' . ($course['end_date'] ?: ''), ' -')); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo $course['fees'] !== null ? '₹' . number_format((float)$course['fees'], 2) : 'Fee not set'; ?></div>
                                            <small class="text-muted"><?php echo ucfirst(html_escape($course['mode'])); ?></small>
                                        </td>
                                        <td><span class="badge badge-<?php echo $course['status'] === 'published' ? 'success' : ($course['status'] === 'archived' ? 'dark' : 'warning'); ?>-lighten"><?php echo ucfirst(html_escape($course['status'])); ?></span></td>
                                        <td><?php echo (int)$course['display_order']; ?></td>
                                        <td>
                                            <a href="<?php echo site_url('admin/sponsored_courses/edit/' . (int)$course['id']); ?>" class="btn btn-outline-primary btn-sm mb-1">Edit</a>
                                            <?php if ($course['status'] !== 'published'): ?>
                                                <a href="<?php echo site_url('admin/sponsored_courses/publish/' . (int)$course['id']); ?>" class="btn btn-outline-success btn-sm mb-1">Publish</a>
                                            <?php else: ?>
                                                <a href="<?php echo site_url('admin/sponsored_courses/unpublish/' . (int)$course['id']); ?>" class="btn btn-outline-warning btn-sm mb-1">Unpublish</a>
                                            <?php endif; ?>
                                            <a href="<?php echo site_url('admin/sponsored_courses/archive/' . (int)$course['id']); ?>" class="btn btn-outline-danger btn-sm mb-1">Archive</a>
                                            <?php if (!empty($course['registration_link'])): ?>
                                                <a href="<?php echo html_escape($course['registration_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm mb-1">Preview</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted">No sponsored courses created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
