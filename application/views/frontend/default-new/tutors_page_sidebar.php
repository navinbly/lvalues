<?php
$CI =& get_instance();
$CI->load->model('Tutor_master_model', 'tutor_master_model');
$taxonomy_tree = $CI->tutor_master_model->get_registration_tree();
$selected_category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$selected_class_id    = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$selected_subject_id  = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
?>
<form action="<?php echo site_url('home/search'); ?>" method="get" id="tutor_filter_form">
    <input type="hidden" name="search_for" value="tutor">
    <?php if(isset($_GET['query']) && !empty($_GET['query'])): ?>
        <input type="hidden" name="query" value="<?php echo html_escape($_GET['query']); ?>">
    <?php endif; ?>
    <div class="course-all-category">
        <div class="course-category">
            <h3><?php echo get_phrase('Category'); ?></h3>
            <select class="form-control" name="category_id" id="tutor_filter_category" onchange="handleTutorCategoryChange();">
                <option value="0">All categories</option>
                <?php foreach ($taxonomy_tree as $category): ?>
                    <option value="<?php echo (int)$category['id']; ?>" <?php if($selected_category_id === (int)$category['id']) echo 'selected'; ?>><?php echo html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="course-category mt-3">
            <h3><?php echo get_phrase('Class / Course Group'); ?></h3>
            <select class="form-control" name="class_id" id="tutor_filter_class" onchange="handleTutorClassChange();"></select>
        </div>
        <div class="course-category mt-3">
            <h3><?php echo get_phrase('Subject'); ?></h3>
            <select class="form-control" name="subject_id" id="tutor_filter_subject" onchange="handleTutorSubjectChange();"></select>
        </div>
        <div class="course-price course-category mt-3">
            <h3><?php echo get_phrase('Mode'); ?></h3>
            <?php $selected_mode = isset($_GET['mode']) ? $_GET['mode'] : 'all'; $modes = ['all'=>'All','online'=>'Online','offline'=>'Offline','both'=>'Both']; foreach($modes as $val => $label): ?>
                <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="<?php echo $val; ?>" id="mode_<?php echo $val; ?>" onchange="filterTutor()" <?php if($selected_mode === $val) echo 'checked'; ?>><label class="form-check-label" for="mode_<?php echo $val; ?>"><span class="text-13px"><?php echo $label; ?></span></label></div>
            <?php endforeach; ?>
        </div>
        <div class="course-price course-category"><h3><?php echo get_phrase('Location'); ?></h3><input class="form-control" type="text" name="location" placeholder="City / Pincode" value="<?php echo html_escape(isset($_GET['location']) ? $_GET['location'] : ''); ?>" onblur="filterTutor()"></div>
        <div class="course-price course-category"><h3><?php echo get_phrase('Distance'); ?></h3><select class="form-control" name="distance_km" onchange="filterTutor()"><?php $selected_distance_km = isset($_GET['distance_km']) ? (int)$_GET['distance_km'] : 0; $distances=[0=>'Any',2=>'2 km',5=>'5 km',10=>'10 km',25=>'25 km',50=>'50 km']; foreach($distances as $km=>$label): ?><option value="<?php echo $km; ?>" <?php if($selected_distance_km == $km) echo 'selected'; ?>><?php echo $label; ?></option><?php endforeach; ?></select><input type="hidden" name="lat" id="tutor_search_lat" value="<?php echo isset($_GET['lat']) ? html_escape($_GET['lat']) : ''; ?>"><input type="hidden" name="lng" id="tutor_search_lng" value="<?php echo isset($_GET['lng']) ? html_escape($_GET['lng']) : ''; ?>"></div>
        <div class="course-price course-category"><h3><?php echo get_phrase('Fee Range'); ?></h3><div class="row g-2"><div class="col-6"><input class="form-control" type="number" name="fee_min" placeholder="Min" value="<?php echo html_escape(isset($_GET['fee_min']) ? $_GET['fee_min'] : ''); ?>" onblur="filterTutor()"></div><div class="col-6"><input class="form-control" type="number" name="fee_max" placeholder="Max" value="<?php echo html_escape(isset($_GET['fee_max']) ? $_GET['fee_max'] : ''); ?>" onblur="filterTutor()"></div></div></div>
        <div class="course-price course-category"><h3><?php echo get_phrase('Minimum Rating'); ?></h3><select class="form-control" name="min_rating" onchange="filterTutor()"><?php $selected_min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0; ?><option value="0" <?php if($selected_min_rating==0) echo 'selected'; ?>>All</option><option value="3" <?php if($selected_min_rating==3) echo 'selected'; ?>>3+ stars</option><option value="4" <?php if($selected_min_rating==4) echo 'selected'; ?>>4+ stars</option><option value="5" <?php if($selected_min_rating==5) echo 'selected'; ?>>5 stars</option></select></div>
        <div class="course-price course-category"><h3><?php echo get_phrase('Sort By'); ?></h3><?php $selected_sort = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'best_match'; ?><select class="form-control" name="sort_by" onchange="filterTutor()"><option value="best_match" <?php if($selected_sort==='best_match') echo 'selected'; ?>>Best match</option><option value="rating" <?php if($selected_sort==='rating') echo 'selected'; ?>>Highest rated</option><option value="fee_low" <?php if($selected_sort==='fee_low') echo 'selected'; ?>>Fee: low to high</option><option value="fee_high" <?php if($selected_sort==='fee_high') echo 'selected'; ?>>Fee: high to low</option></select></div>
    </div>
</form>
<script>
window.tutorFilterTree = <?php echo json_encode($taxonomy_tree); ?>;
window.selectedTutorCategoryId = <?php echo (int)$selected_category_id; ?>;
window.selectedTutorClassId = <?php echo (int)$selected_class_id; ?>;
window.selectedTutorSubjectId = <?php echo (int)$selected_subject_id; ?>;
function getTutorFilterCategory(categoryId) {
    return (window.tutorFilterTree || []).find(function(item) {
        return parseInt(item.id, 10) === categoryId;
    }) || null;
}

function populateTutorClasses(categoryId, selectedClassId) {
    const classEl = document.getElementById('tutor_filter_class');
    if (!classEl) return;
    classEl.innerHTML = '<option value="0">All classes / groups</option>';
    const category = getTutorFilterCategory(categoryId);
    if (!category) return;
    (category.classes || []).forEach(function(item) {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        option.selected = parseInt(item.id, 10) === selectedClassId;
        classEl.appendChild(option);
    });
}

function populateTutorSubjects(categoryId, classId, selectedSubjectId) {
    const subjectEl = document.getElementById('tutor_filter_subject');
    if (!subjectEl) return;
    subjectEl.innerHTML = '<option value="0">All subjects</option>';
    const category = getTutorFilterCategory(categoryId);
    if (!category || classId <= 0) return;
    const selectedClass = (category.classes || []).find(function(item) {
        return parseInt(item.id, 10) === classId;
    });
    if (!selectedClass) return;
    (selectedClass.subjects || []).forEach(function(item) {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        option.selected = parseInt(item.id, 10) === selectedSubjectId;
        subjectEl.appendChild(option);
    });
}

function syncTutorFilterTaxonomy(){
    const categoryEl = document.getElementById('tutor_filter_category');
    const classEl = document.getElementById('tutor_filter_class');
    const subjectEl = document.getElementById('tutor_filter_subject');
    if(!categoryEl || !classEl || !subjectEl) return;
    const categoryId = parseInt(categoryEl.value || '0', 10);
    populateTutorClasses(categoryId, window.selectedTutorClassId);
    const classId = parseInt(classEl.value || '0', 10);
    populateTutorSubjects(categoryId, classId, window.selectedTutorSubjectId);
    window.selectedTutorClassId = parseInt(classEl.value || '0', 10);
    window.selectedTutorSubjectId = parseInt(subjectEl.value || '0', 10);
}

function handleTutorCategoryChange() {
    const categoryId = parseInt(document.getElementById('tutor_filter_category').value || '0', 10);
    window.selectedTutorCategoryId = categoryId;
    window.selectedTutorClassId = 0;
    window.selectedTutorSubjectId = 0;
    populateTutorClasses(categoryId, 0);
    populateTutorSubjects(categoryId, 0, 0);
    filterTutor();
}

function handleTutorClassChange() {
    const categoryId = parseInt(document.getElementById('tutor_filter_category').value || '0', 10);
    const classId = parseInt(document.getElementById('tutor_filter_class').value || '0', 10);
    window.selectedTutorClassId = classId;
    window.selectedTutorSubjectId = 0;
    populateTutorSubjects(categoryId, classId, 0);
    filterTutor();
}

function handleTutorSubjectChange() {
    window.selectedTutorSubjectId = parseInt(document.getElementById('tutor_filter_subject').value || '0', 10);
    filterTutor();
}
document.addEventListener('DOMContentLoaded', syncTutorFilterTaxonomy);
</script>
