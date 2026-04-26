<?php include 'breadcrumb.php'; ?>
<section class="grid-view courses-list-view pb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Update Profile</h4>
                        <p class="text-muted">Update your learning profile every academic year. Tutors will invite you only for batches matching your category and class/degree/professional level.</p>

                        <form method="post" action="<?php echo site_url('student_batch/update_learning_profile'); ?>">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label>Category</label>
                                    <select name="category_id" id="student_profile_category_id" class="form-control" required>
                                        <option value="">Select category</option>
                                        <?php foreach (($categories ?? []) as $category): ?>
                                            <option value="<?php echo (int)$category['id']; ?>" <?php echo ((int)($student_profile['category_id'] ?? 0) === (int)$category['id']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label>Class / Degree / Level</label>
                                    <select name="class_id" id="student_profile_class_id" class="form-control" required>
                                        <option value="">Select class/level</option>
                                        <?php foreach (($classes ?? []) as $class): ?>
                                            <option value="<?php echo (int)$class['id']; ?>" data-category="<?php echo (int)$class['category_id']; ?>" <?php echo ((int)($student_profile['class_id'] ?? 0) === (int)$class['id']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-2">
                                    <label>Subject Interest</label>
                                    <select name="subject_interest_id" id="student_profile_subject_id" class="form-control">
                                        <option value="">Any / Not sure</option>
                                        <?php foreach (($subjects ?? []) as $subject): ?>
                                            <option value="<?php echo (int)$subject['id']; ?>" data-class="<?php echo (int)$subject['class_id']; ?>" <?php echo ((int)($student_profile['subject_interest_id'] ?? 0) === (int)$subject['id']) ? 'selected' : ''; ?>>
                                                <?php echo html_escape($subject['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label>Current Level Label</label>
                                    <input type="text" name="current_level_label" class="form-control" value="<?php echo html_escape($student_profile['current_level_label'] ?? ''); ?>" placeholder="Example: Class 3 / B.Tech 2nd Year / Working Professional">
                                </div>

                                <div class="col-md-3 mb-2">
                                    <label>Academic Year</label>
                                    <input type="text" name="academic_year" class="form-control" value="<?php echo html_escape($student_profile['academic_year'] ?? date('Y')); ?>" placeholder="Example: 2026-27">
                                </div>

                                <div class="col-md-3 mb-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                                </div>
                            </div>
                        </form>

                        <?php if (!empty($student_profile)): ?>
                            <small class="text-success d-block mt-3">
                                Current profile:
                                <?php echo html_escape(trim(($student_profile['category_name'] ?? '') . ' / ' . ($student_profile['class_name'] ?? '') . ' / ' . ($student_profile['subject_name'] ?? ''), ' /')); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    const category = document.getElementById('student_profile_category_id');
    const classSelect = document.getElementById('student_profile_class_id');
    const subjectSelect = document.getElementById('student_profile_subject_id');
    if (!category || !classSelect || !subjectSelect) return;

    function filterClasses(){
        const categoryId = category.value;
        Array.from(classSelect.options).forEach(function(opt){
            if (!opt.value) return;
            opt.hidden = categoryId && opt.getAttribute('data-category') !== categoryId;
        });
        if (classSelect.selectedOptions.length && classSelect.selectedOptions[0].hidden) {
            classSelect.value = '';
        }
        filterSubjects();
    }

    function filterSubjects(){
        const classId = classSelect.value;
        Array.from(subjectSelect.options).forEach(function(opt){
            if (!opt.value) return;
            opt.hidden = classId && opt.getAttribute('data-class') !== classId;
        });
        if (subjectSelect.selectedOptions.length && subjectSelect.selectedOptions[0].hidden) {
            subjectSelect.value = '';
        }
    }

    category.addEventListener('change', filterClasses);
    classSelect.addEventListener('change', filterSubjects);
    filterClasses();
})();
</script>
