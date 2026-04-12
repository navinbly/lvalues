<?php include "breadcrumb.php"; ?>

<section class="contact-page">
    <div class="container">
    	<div class="row">
    		<div class="col-md-8">
				<form class="form-section" action="<?php echo site_url('home/become_an_instructor'); ?>" method="post" enctype="multipart/form-data">

				<div class="mb-4">
					<label class="mb-2" for="address"><?php echo get_phrase('address'); ?></label>
					<textarea class="form-control bg-white" id="address" name="address" rows="2" placeholder="City, State, Country" required></textarea>
				</div>

				<h5 class="mb-3">Tutor public profile (for students to find you)</h5>

				<div class="mb-4">
					<label class="mb-2" for="tutor_headline">Headline</label>
					<input class="form-control bg-white" id="tutor_headline" type="text" name="tutor_headline" placeholder="e.g., AWS + DevOps Trainer" required>
				</div>

				<div class="mb-4">
					<label class="mb-2" for="tutor_qualification">Qualification</label>
					<input class="form-control bg-white" id="tutor_qualification" type="text" name="tutor_qualification" placeholder="e.g., B.Tech / AWS Certified" required>
				</div>

				<div class="mb-4">
					<label class="mb-2" for="tutor_experience_years">Experience (years)</label>
					<input class="form-control bg-white" id="tutor_experience_years" type="number" min="0" max="60" name="tutor_experience_years" placeholder="e.g., 6" required>
				</div>

				<div class="mb-4">
					<label class="mb-2" for="tutor_teaching_mode">Teaching mode</label>
					<select class="form-control bg-white" id="tutor_teaching_mode" name="tutor_teaching_mode" required>
						<option value="both">Both (Online + Offline)</option>
						<option value="online">Online</option>
						<option value="offline">Offline</option>
					</select>
				</div>

				<div class="mb-4">
					<label class="mb-2" for="tutor_hourly_fee">Fee (per hour)</label>
					<input class="form-control bg-white" id="tutor_hourly_fee" type="number" min="0" step="1" name="tutor_hourly_fee" placeholder="e.g., 800" required>
				</div>

				<div class="mb-4">
					<label class="mb-2">Subjects you teach</label>
					<select class="form-control bg-white" name="tutor_subject_ids[]" multiple required>
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
					<small>Tip: Hold Ctrl to select multiple subjects.</small>
				</div>

				<div class="mb-4">
					<label class="mb-2">Location</label>
					<div class="row">
						<div class="col-md-6 mb-2">
							<input class="form-control bg-white" type="text" name="tutor_city" placeholder="City" required>
						</div>
						<div class="col-md-6 mb-2">
							<input class="form-control bg-white" type="text" name="tutor_pincode" placeholder="Pincode" required>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6 mb-2">
							<input class="form-control bg-white" type="text" name="tutor_state" placeholder="State">
						</div>
						<div class="col-md-6 mb-2">
							<input class="form-control bg-white" type="text" name="tutor_country" placeholder="Country" value="India">
						</div>
					</div>
				</div>
                	<div class="mb-4">
                        <label class="mb-2" for="phone"><?php echo get_phrase('Your Phone'); ?></label>
						<input class="form-control bg-white" id="phone" type="phone" name="phone" placeholder="<?php echo get_phrase('Enter your phone number'); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2" for="document"><?php echo get_phrase('Document'); ?> <small>(doc, docs, pdf, txt, png, jpg, jpeg)</small></label>
					    <input class="form-control bg-white mb-0" id="document" type="file" name="document" required>
					    <small><?php echo get_phrase('Provide some documents about your qualifications'); ?></small>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2" for="message"><?php echo get_phrase('message'); ?></label>
						<textarea class="form-control bg-white" id="message" name="message" rows="4"></textarea>
                    </div>

					<div class="mb-4">
						<label class="mb-2" for="tutor_bio">Bio (optional)</label>
						<textarea class="form-control bg-white" id="tutor_bio" name="tutor_bio" rows="3"></textarea>
					</div>

                    <div class="mb-4">
                    	<button class="btn btn-primary"><?php echo get_phrase('Submit'); ?></button>
                    </div>
		                
		        </form>
		    </div>
		</div>
	</div>
</section>