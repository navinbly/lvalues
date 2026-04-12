<h4 class="header-title mb-3"><?php echo get_phrase('instructor_application_form'); ?></h4>
<div class="alert alert-info" role="alert">
    <h4 class="alert-heading"><?php echo get_phrase('heads_up'); ?>!</h4>
    <p><?php echo get_settings('instructor_application_note'); ?></p>
</div>
<form class="required-form" action="<?php echo site_url('user/become_an_instructor'); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $this->session->userdata('user_id'); ?>">
    <div class="form-group">
        <label for="name"><?php echo get_phrase('name'); ?></label>
        <input type="text" class="form-control" name="name" id="name" aria-describedby="name-help" placeholder="<?php echo get_phrase('your_name_will_go_here'); ?>" value="<?php echo $user_details['first_name'].' '.$user_details['last_name']; ?>" readonly required>
        <small id="name-help" class="form-text text-muted"><?php echo get_phrase('your_name_is_required'); ?></small>
    </div>
    <div class="form-group">
        <label for="email"><?php echo get_phrase('email_address'); ?></label>
        <input type="email" class="form-control" name="email" id="email" aria-describedby="email-help" placeholder="<?php echo get_phrase('your_email_will_go_here'); ?>" value="<?php echo $user_details['email']; ?>" readonly required>
        <small id="email-help" class="form-text text-muted"><?php echo get_phrase('your_email_is_required'); ?></small>
    </div>
    <div class="form-group">
        <label for="address"><?php echo get_phrase('address'); ?></label>
        <textarea name="address" id = "address" class="form-control" required></textarea>
        <small id="address-help" class="form-text text-muted"><?php echo get_phrase('your_address_is_required'); ?></small>
    </div>

    <!-- Tutor marketplace profile fields (used for Tutor Search filters) -->
    <hr>
    <h5 class="mb-3">Tutor public profile (for students to find you)</h5>

    <div class="form-group">
        <label for="tutor_headline">Headline</label>
        <input type="text" class="form-control" name="tutor_headline" id="tutor_headline" placeholder="e.g., AWS + DevOps Trainer" required>
        <small class="form-text text-muted">Students will see this in tutor search results.</small>
    </div>

    <div class="form-group">
        <label for="tutor_qualification">Qualification</label>
        <input type="text" class="form-control" name="tutor_qualification" id="tutor_qualification" placeholder="e.g., B.Tech / AWS Certified" required>
    </div>

    <div class="form-group">
        <label for="tutor_experience_years">Experience (years)</label>
        <input type="number" min="0" max="60" class="form-control" name="tutor_experience_years" id="tutor_experience_years" placeholder="e.g., 6" required>
    </div>

    <div class="form-group">
        <label for="tutor_teaching_mode">Teaching mode</label>
        <select class="form-control" name="tutor_teaching_mode" id="tutor_teaching_mode" required>
            <option value="both">Both (Online + Offline)</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
        </select>
    </div>

    <div class="form-group">
        <label for="tutor_hourly_fee">Fee (per hour)</label>
        <input type="number" min="0" step="1" class="form-control" name="tutor_hourly_fee" id="tutor_hourly_fee" placeholder="e.g., 800" required>
    </div>

    <div class="form-group">
        <label>Subjects you teach</label>
        <select class="form-control" name="tutor_subject_ids[]" multiple required>
            <?php $categories = $this->crud_model->get_categories()->result_array(); ?>
            <?php foreach ($categories as $cat): ?>
                <optgroup label="<?php echo html_escape($cat['name']); ?>">
                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo html_escape($cat['name']); ?></option>
                    <?php foreach ($this->crud_model->get_sub_categories($cat['id']) as $sub): ?>
                        <option value="<?php echo (int)$sub['id']; ?>">&nbsp;&nbsp;— <?php echo html_escape($sub['name']); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Select one or more categories/sub-categories. These power the “Subjects” filter.</small>
    </div>

    <div class="form-group">
        <label>Location (for offline search)</label>
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_city" placeholder="City" required>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_pincode" placeholder="Pincode" required>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_state" placeholder="State">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_country" placeholder="Country" value="India">
            </div>
        </div>
        <small class="form-text text-muted">Distance filter needs lat/lng (optional below). Later we can auto-fill using browser GPS.</small>
    </div>

    <div class="form-group">
        <label>Coordinates (optional)</label>
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_lat" placeholder="Latitude (optional)">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="tutor_lng" placeholder="Longitude (optional)">
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="tutor_bio">Bio (optional)</label>
        <textarea name="tutor_bio" id="tutor_bio" class="form-control" rows="3" placeholder="Tell students about your teaching style, batch history, etc."></textarea>
    </div>
    <div class="form-group">
        <label for="phone"><?php echo get_phrase('phone_number'); ?></label>
        <input type="text" class="form-control" name="phone" id="phone" aria-describedby="phone-help" placeholder="<?php echo get_phrase('your_phone_number_will_go_here'); ?>" required>
        <small id="phone-help" class="form-text text-muted"><?php echo get_phrase('your_phone_number_is_required'); ?></small>
    </div>
    <div class="form-group">
        <label for="message"><?php echo get_phrase('any_message'); ?></label>
        <textarea name="message" id = "message" class="form-control"></textarea>
        <small id="message-help" class="form-text text-muted"><?php echo get_phrase('if_any_message_you_want_to_share'); ?></small>
    </div>
    <div class="form-group">
        <label> <?php echo get_phrase('document'); ?></label>
        <div class="input-group">
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="document" name="document" onchange="changeTitleOfImageUploader(this)">
                <label class="custom-file-label" for="document"><?php echo get_phrase('document'); ?></label>
            </div>
        </div>
        <small id="attachment-help" class="form-text text-muted"><?php echo get_phrase('if_any_document_you_want_to_share'); ?> ( .doc, .docs, .pdf, .txt, .png, .jpg, jpeg ) <?php echo get_phrase('are_accepted'); ?></small>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="text-center">
                <div class="mb-3 mt-3">
                    <button type="button" class="btn btn-primary text-center" onclick="checkRequiredFields()"><?php echo get_phrase('apply'); ?></button>
                </div>
            </div>
        </div> <!-- end col -->
    </div>
</form>
