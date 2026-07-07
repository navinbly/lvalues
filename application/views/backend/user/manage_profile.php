<?php
$row = isset($edit_data[0]) ? $edit_data[0] : $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
$social_links = json_decode($row['social_links'] ?? '', true);
if (!is_array($social_links)) {
    $social_links = array('facebook' => '', 'twitter' => '', 'linkedin' => '');
}
?>

<style>
    .profile-shell { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 20px; }
    .profile-card { background: #fff; border: 1px solid #e7ecf3; border-radius: 8px; box-shadow: 0 14px 34px rgba(31, 45, 61, .05); }
    .profile-summary { padding: 24px; text-align: center; }
    .profile-summary img { width: 96px; height: 96px; object-fit: cover; border-radius: 50%; margin-bottom: 14px; box-shadow: 0 12px 24px rgba(108, 99, 255, .18); }
    .profile-summary h4 { margin-bottom: 4px; color: #40556b; }
    .profile-summary p { color: #8493a5; margin-bottom: 14px; }
    .profile-badge { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #eefcf6; color: #0f9f6e; font-weight: 700; font-size: 12px; }
    .profile-form-section { padding: 22px; border-bottom: 1px solid #edf1f7; }
    .profile-form-section:last-child { border-bottom: 0; }
    .profile-form-section h5 { color: #40556b; font-weight: 700; margin-bottom: 16px; }
    @media (max-width: 991px) { .profile-shell { grid-template-columns: 1fr; } }
</style>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title mb-1"><i class="dripicons-user title_icon"></i> <?php echo get_phrase('manage_profile'); ?></h4>
                <p class="text-muted mb-0">Keep your tutor identity, public bio, social links, and password updated from this dashboard.</p>
            </div>
        </div>
    </div>
</div>

<div class="profile-shell">
    <aside class="profile-card profile-summary">
        <img src="<?php echo $this->user_model->get_user_image_url($row['id']); ?>" alt="">
        <h4><?php echo html_escape(trim($row['first_name'] . ' ' . $row['last_name'])); ?></h4>
        <p><?php echo html_escape($row['email']); ?></p>
        <?php if ((int)($row['is_instructor'] ?? 0) === 1): ?>
            <span class="profile-badge"><i class="mdi mdi-check-circle mr-1"></i><?php echo get_phrase('instructor'); ?></span>
        <?php endif; ?>
    </aside>

    <section class="profile-card">
        <?php echo form_open(site_url('user/manage_profile/update_profile_info/' . $row['id']), array('class' => 'validate', 'target' => '_top', 'enctype' => 'multipart/form-data')); ?>
            <div class="profile-form-section">
                <h5>Profile details</h5>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label><?php echo get_phrase('first_name'); ?></label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo html_escape($row['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><?php echo get_phrase('last_name'); ?></label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo html_escape($row['last_name']); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><?php echo get_phrase('email'); ?></label>
                        <input type="email" class="form-control" value="<?php echo html_escape($row['email']); ?>" disabled>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><?php echo get_phrase('update_user_photo'); ?></label>
                        <input type="file" class="form-control" name="user_image" accept="image/*">
                    </div>
                    <div class="col-md-12 form-group">
                        <label><?php echo get_phrase('title'); ?></label>
                        <input type="text" class="form-control" name="title" value="<?php echo html_escape($row['title']); ?>" placeholder="Example: Maths and Science Tutor">
                    </div>
                    <div class="col-md-12 form-group">
                        <label><?php echo get_phrase('your_skills'); ?></label>
                        <input type="text" class="form-control" name="skills" value="<?php echo html_escape($row['skills']); ?>" placeholder="Mathematics, English, Physics">
                    </div>
                    <div class="col-md-12 form-group mb-0">
                        <label><?php echo get_phrase('biography'); ?></label>
                        <textarea class="form-control" name="biography" rows="6" placeholder="Write a clear, student-friendly introduction."><?php echo html_escape($row['biography']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="profile-form-section">
                <h5>Social links</h5>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('facebook'); ?></label>
                        <input type="text" class="form-control" name="facebook_link" value="<?php echo html_escape($social_links['facebook'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('twitter'); ?></label>
                        <input type="text" class="form-control" name="twitter_link" value="<?php echo html_escape($social_links['twitter'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('linkedin'); ?></label>
                        <input type="text" class="form-control" name="linkedin_link" value="<?php echo html_escape($social_links['linkedin'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo get_phrase('save'); ?></button>
            </div>
        <?php echo form_close(); ?>

        <?php echo form_open(site_url('user/manage_profile/change_password/' . $row['id']), array('class' => 'validate', 'target' => '_top')); ?>
            <div class="profile-form-section">
                <h5><?php echo get_phrase('change_password'); ?></h5>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('current_password'); ?></label>
                        <input type="password" class="form-control" name="current_password" autocomplete="current-password">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('new_password'); ?></label>
                        <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?php echo get_phrase('confirm_password'); ?></label>
                        <input type="password" class="form-control" name="confirm_password" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary"><?php echo get_phrase('save_changes'); ?></button>
            </div>
        <?php echo form_close(); ?>
    </section>
</div>