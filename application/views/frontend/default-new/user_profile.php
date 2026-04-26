<?php $user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array(); ?>
<?php $social_links = json_decode($user_details['social_links'], true); ?>
<?php
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];
$student_learning_profile = isset($student_learning_profile) && is_array($student_learning_profile) ? $student_learning_profile : [];
$is_student_profile = isset($user_details['is_instructor']) && (int)$user_details['is_instructor'] !== 1;
$selected_student_category_id = (string)($student_learning_profile['category_id'] ?? '');
$selected_student_class_id = (string)($student_learning_profile['class_id'] ?? '');
$selected_student_subject_id = (string)($student_learning_profile['subject_interest_id'] ?? '');
?>


<?php include "breadcrumb.php"; ?>

<!--------  Wish List body section start------>
<section class="wish-list-body message">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <?php include "profile_menus.php"; ?>
            </div>
            <div class="col-lg-9 col-md-8">
                <div class="profile">
                    <div class="profile-bg">
                        <!-- <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/img/profile-bg-2.jpg') ?>"> -->
                    </div>
                    <div class="profile-ful-body common-card">
                        <div class="profile-parrent mt-5">
                            <div class="profile-child">
                               <a href="#"><img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>"></a> 
                                <div class="child-text">
                                    <a href="#"><h5><?php echo get_phrase('Profile Photo') ?></h5></a>
                                    <p><?php echo get_phrase('Update your profile photo and personal details'); ?></p>  
                                </div>
                            </div>

                            <div class="profile-child-btn">
                                <form action="<?php echo site_url('home/update_profile/update_photo/true') ?>" method="post" enctype="multipart/form-data" class="d-flex align-items-center">
                                    <input type="file" id="profile-photo-input" name="user_image" onchange="
                                        $('.photo-upload-btn').toggleClass('d-hidden');
                                        $('[for=profile-photo-input]').toggleClass('d-hidden');
                                    " class="d-none">
                                    <label for="profile-photo-input" class="btn btn-light float-end" type="button" style="background-color: var(--bs-gray-200);"><i class="fas fa-upload"></i> <?php echo get_phrase('Upload photo') ?></label>
                                    <div class="photo-upload-btn d-hidden">
                                        <button type="submit" class="purchase-btn ms-1 float-end"><?php echo get_phrase('Save') ?></button>
                                        <button type="reset" onclick="
                                            $('.photo-upload-btn').toggleClass('d-hidden');
                                            $('[for=profile-photo-input]').toggleClass('d-hidden');
                                        " class="purchase-btn float-end"><?php echo get_phrase('Cancel') ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="profile-input-section">
                            <form class="" action="<?php echo site_url('home/update_profile/update_basics'); ?>" method="post">
                                <div class="row">
                                    <div class="col-12 border-bottom mb-3 pb-3">
                                        <h4 class="text-black"><?php echo site_phrase('Profile Info'); ?></h4>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="text-dark fw-600" for="FristName"><?php echo site_phrase('first_name'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control bg-white-2 text-14px" name="first_name" id="FristName" placeholder="<?php echo site_phrase('first_name'); ?>" value="<?php echo $user_details['first_name']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-dark fw-600" for="FristName"><?php echo site_phrase('last_name'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control bg-white-2 text-14px" name="last_name" placeholder="<?php echo site_phrase('last_name'); ?>" value="<?php echo $user_details['last_name']; ?>">
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <?php if ($user_details['is_instructor'] > 0) : ?>
                                            <div class="form-group mb-3">
                                                <label class="text-dark fw-600" for="Biography"><?php echo site_phrase('title'); ?></label>
                                                <textarea class="form-control bg-white-2 text-14px" name="title" placeholder="<?php echo site_phrase('short_title_about_yourself'); ?>"><?php echo $user_details['title']; ?></textarea>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="text-dark fw-600" for="skills"><?php echo get_phrase('your_skills'); ?></label>
                                                <input type="text" class=" tagify" id="skills" name="skills" data-role="tagsinput" style="width: 100%;" value="<?php echo $user_details['skills'];  ?>" />
                                                <small class="text-muted"><?php echo get_phrase('write_your_skill_and_click_the_enter_button'); ?></small>
                                            </div>

                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label class="text-dark fw-600" for="Biography"><?php echo site_phrase('biography'); ?></label>
                                            <textarea class="form-control bg-white-2 text-14px text_editor" name="biography" id="Biography"><?php echo $user_details['biography']; ?></textarea>
                                        </div>

                                        <?php if ($is_student_profile): ?>
                                            <hr class="my-5 bg-secondary">

                                            <div class="student-learning-profile-section">
                                                <h4 class="text-black mb-2">Learning Profile</h4>
                                                <p class="text-muted mb-4">Update your current class/level so tutors can send you relevant batch invitations.</p>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="text-dark fw-600" for="student_category_id">Learning Category</label>
                                                        <select class="form-control bg-white-2 text-14px" name="student_category_id" id="student_category_id" required>
                                                            <option value="">Select category</option>
                                                            <?php foreach ($tutor_registration_tree as $category_item): ?>
                                                                <option value="<?php echo html_escape($category_item['id']); ?>" <?php echo $selected_student_category_id === (string)$category_item['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo html_escape($category_item['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="text-dark fw-600" for="student_class_id">Current Class / Level</label>
                                                        <select class="form-control bg-white-2 text-14px" name="student_class_id" id="student_class_id" required>
                                                            <option value="">Select class / level</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="text-dark fw-600" for="student_subject_interest_id">Subject Interest</label>
                                                        <select class="form-control bg-white-2 text-14px" name="student_subject_interest_id" id="student_subject_interest_id">
                                                            <option value="">Any / Not sure</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <label class="text-dark fw-600" for="student_academic_year">Academic Year</label>
                                                        <input type="text" class="form-control bg-white-2 text-14px" name="student_academic_year" id="student_academic_year" placeholder="Example: 2026-27" value="<?php echo html_escape($student_learning_profile['academic_year'] ?? date('Y')); ?>">
                                                    </div>

                                                    <div class="col-md-12 mb-3">
                                                        <label class="text-dark fw-600" for="student_current_level_label">Current Level Label</label>
                                                        <input type="text" class="form-control bg-white-2 text-14px" name="student_current_level_label" id="student_current_level_label" placeholder="Example: Class 3, B.Tech 2nd Year" value="<?php echo html_escape($student_learning_profile['current_level_label'] ?? ''); ?>">
                                                        <small class="text-muted">Example: if you were in Class 2 last year, update it to Class 3 for the current academic year.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <hr class="my-5 bg-secondary">

                                        <label class="text-dark fw-600"><?php echo site_phrase('add_your_twitter_link'); ?></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                            <input type="text" class="form-control bg-white-2 text-14px" maxlength="60" name="twitter_link" placeholder="<?php echo site_phrase('twitter_link'); ?>" value="<?php echo $social_links['twitter']; ?>">
                                        </div>


                                        <label class="text-dark fw-600"><?php echo site_phrase('add_your_facebook_link'); ?></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                            <input type="text" class="form-control bg-white-2 text-14px" maxlength="60" name="facebook_link" placeholder="<?php echo site_phrase('facebook_link'); ?>" value="<?php echo $social_links['facebook']; ?>">
                                        </div>


                                        <label class="text-dark fw-600"><?php echo site_phrase('add_your_linkedin_link'); ?></label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                            <input type="text" class="form-control bg-white-2 text-14px" maxlength="60" name="linkedin_link" placeholder="<?php echo site_phrase('linkedin_link'); ?>" value="<?php echo $social_links['linkedin']; ?>">
                                        </div>
                                    </div>

                                    <div class="col-12 pt-4">
                                        <button class="btn btn-primary px-5"><?php echo site_phrase('save'); ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-------- wish list bosy section end ------->

<?php if ($is_student_profile): ?>
<script>
(function () {
    const registrationTree = <?php echo json_encode($tutor_registration_tree); ?>;
    const selectedCategoryId = <?php echo json_encode($selected_student_category_id); ?>;
    const selectedClassId = <?php echo json_encode($selected_student_class_id); ?>;
    const selectedSubjectId = <?php echo json_encode($selected_student_subject_id); ?>;

    const categorySelect = document.getElementById('student_category_id');
    const classSelect = document.getElementById('student_class_id');
    const subjectSelect = document.getElementById('student_subject_interest_id');

    if (!categorySelect || !classSelect || !subjectSelect) {
        return;
    }

    function resetSelect(select, firstText) {
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = firstText;
        select.appendChild(option);
    }

    function loadClasses() {
        const categoryId = categorySelect.value;
        const currentClassValue = classSelect.value || selectedClassId;

        resetSelect(classSelect, 'Select class / level');
        resetSelect(subjectSelect, 'Any / Not sure');

        if (!categoryId) {
            return;
        }

        registrationTree.forEach(function (category) {
            if (String(category.id) !== String(categoryId)) {
                return;
            }

            (category.classes || []).forEach(function (classItem) {
                const option = document.createElement('option');
                option.value = classItem.id;
                option.textContent = classItem.name;
                if (String(currentClassValue) === String(classItem.id)) {
                    option.selected = true;
                }
                classSelect.appendChild(option);
            });
        });

        loadSubjects();
    }

    function loadSubjects() {
        const categoryId = categorySelect.value;
        const classId = classSelect.value;
        const currentSubjectValue = subjectSelect.value || selectedSubjectId;

        resetSelect(subjectSelect, 'Any / Not sure');

        if (!categoryId || !classId) {
            return;
        }

        registrationTree.forEach(function (category) {
            if (String(category.id) !== String(categoryId)) {
                return;
            }

            (category.classes || []).forEach(function (classItem) {
                if (String(classItem.id) !== String(classId)) {
                    return;
                }

                (classItem.subjects || []).forEach(function (subjectItem) {
                    const option = document.createElement('option');
                    option.value = subjectItem.id;
                    option.textContent = subjectItem.name;
                    if (String(currentSubjectValue) === String(subjectItem.id)) {
                        option.selected = true;
                    }
                    subjectSelect.appendChild(option);
                });
            });
        });
    }

    categorySelect.addEventListener('change', function () {
        classSelect.value = '';
        subjectSelect.value = '';
        loadClasses();
    });

    classSelect.addEventListener('change', function () {
        subjectSelect.value = '';
        loadSubjects();
    });

    if (!categorySelect.value && selectedCategoryId) {
        categorySelect.value = selectedCategoryId;
    }

    loadClasses();
})();
</script>
<?php endif; ?>
