<?php
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];
$tutor_profile = isset($tutor_profile) && is_array($tutor_profile) ? $tutor_profile : [];
$selected_category_ids = array_map('strval', isset($selected_category_ids) ? (array)$selected_category_ids : []);
$selected_class_ids = array_map('strval', isset($selected_class_ids) ? (array)$selected_class_ids : []);
$selected_subject_ids = array_map('strval', isset($selected_subject_ids) ? (array)$selected_subject_ids : []);
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"><i class="mdi mdi-account-edit title_icon"></i> <?php echo get_phrase('teaching_profile'); ?></h4>
                <p class="text-muted mb-0">Update the subjects you teach. Tutor search will use this teaching profile, not course manager.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <form action="<?php echo site_url('user/update_tutor_teaching_profile'); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tutor_headline">Headline</label>
                                <input type="text" class="form-control" id="tutor_headline" name="tutor_headline" value="<?php echo html_escape($tutor_profile['headline'] ?? ''); ?>" placeholder="e.g., AWS + DevOps Trainer">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tutor_qualification">Qualification</label>
                                <input type="text" class="form-control" id="tutor_qualification" name="tutor_qualification" value="<?php echo html_escape($tutor_profile['qualification'] ?? ''); ?>" placeholder="e.g., B.Tech / AWS Certified">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_experience_years">Experience (years)</label>
                                <input type="number" min="0" max="60" class="form-control" id="tutor_experience_years" name="tutor_experience_years" value="<?php echo html_escape((string)($tutor_profile['experience_years'] ?? '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_teaching_mode">Teaching mode</label>
                                <?php $mode = strtolower((string)($tutor_profile['teaching_mode'] ?? 'both')); ?>
                                <select class="form-control" id="tutor_teaching_mode" name="tutor_teaching_mode">
                                    <option value="online" <?php echo $mode === 'online' ? 'selected' : ''; ?>>Online</option>
                                    <option value="offline" <?php echo $mode === 'offline' ? 'selected' : ''; ?>>Offline</option>
                                    <option value="both" <?php echo $mode === 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_hourly_fee">Fee (per hour)</label>
                                <input type="number" min="0" step="1" class="form-control" id="tutor_hourly_fee" name="tutor_hourly_fee" value="<?php echo html_escape((string)($tutor_profile['hourly_fee'] ?? '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_pincode">Pincode</label>
                                <input type="text" class="form-control" id="tutor_pincode" name="tutor_pincode" value="<?php echo html_escape($tutor_profile['pincode'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_city">City</label>
                                <input type="text" class="form-control" id="tutor_city" name="tutor_city" value="<?php echo html_escape($tutor_profile['city'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_state">State</label>
                                <input type="text" class="form-control" id="tutor_state" name="tutor_state" value="<?php echo html_escape($tutor_profile['state'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_country">Country</label>
                                <input type="text" class="form-control" id="tutor_country" name="tutor_country" value="<?php echo html_escape($tutor_profile['country'] ?? 'India'); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_lat">Latitude</label>
                                <input type="text" class="form-control" id="tutor_lat" name="tutor_lat" value="<?php echo html_escape((string)($tutor_profile['lat'] ?? '')); ?>" readonly>
                                <small class="text-muted">Auto-filled from your current location.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_lng">Longitude</label>
                                <input type="text" class="form-control" id="tutor_lng" name="tutor_lng" value="<?php echo html_escape((string)($tutor_profile['lng'] ?? '')); ?>" readonly>
                                <small class="text-muted">Auto-filled from your current location.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-outline-primary btn-block d-block" id="get_current_location_btn">Use current location</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label>&nbsp;</label>
                                <div id="location_fetch_status" class="small text-muted pt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group mb-3">
                                <label for="tutor_bio">Bio</label>
                                <textarea class="form-control" id="tutor_bio" name="tutor_bio" rows="3"><?php echo html_escape($tutor_profile['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Subjects you teach</h5>
                    <p class="text-muted">Select categories, then classes, then subjects. Your tutor profile becomes searchable immediately after save.</p>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Teaching Categories</label>
                                <select class="form-control" id="category_selector" name="tutor_category_ids[]" multiple size="8"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Class / Course Group</label>
                                <select class="form-control" id="class_selector" name="tutor_class_ids[]" multiple size="8"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Subjects</label>
                                <select class="form-control" id="subject_selector" name="tutor_subject_ids[]" multiple size="8"></select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save teaching profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const tutorRegistrationTree = <?php echo json_encode($tutor_registration_tree); ?>;
let selectedCategoryIds = <?php echo json_encode(array_values($selected_category_ids)); ?>;
let selectedClassIds = <?php echo json_encode(array_values($selected_class_ids)); ?>;
let selectedSubjectIds = <?php echo json_encode(array_values($selected_subject_ids)); ?>;

function setSelectOptions(selectEl, rows, selectedIds) {
    if (!selectEl) return;
    const selected = new Set((selectedIds || []).map(String));
    selectEl.innerHTML = '';
    rows.forEach(function(item) {
        const option = document.createElement('option');
        option.value = String(item.id);
        option.textContent = item.name;
        if (selected.has(String(item.id))) {
            option.selected = true;
        }
        selectEl.appendChild(option);
    });
}

function getSelectedValues(selectEl) {
    return Array.from(selectEl.options).filter(opt => opt.selected).map(opt => String(opt.value));
}

function refreshCategorySelector() {
    const categorySelector = document.getElementById('category_selector');
    setSelectOptions(categorySelector, tutorRegistrationTree.map(function(cat) {
        return { id: cat.id, name: cat.name };
    }), selectedCategoryIds);
}

function refreshClassSelector() {
    const classSelector = document.getElementById('class_selector');
    const categories = tutorRegistrationTree.filter(cat => selectedCategoryIds.includes(String(cat.id)));
    const rows = [];
    categories.forEach(function(cat) {
        (cat.classes || []).forEach(function(cls) {
            rows.push({ id: cls.id, name: cat.name + ' / ' + cls.name });
        });
    });
    selectedClassIds = selectedClassIds.filter(id => rows.some(r => String(r.id) === String(id)));
    setSelectOptions(classSelector, rows, selectedClassIds);
}

function refreshSubjectSelector() {
    const subjectSelector = document.getElementById('subject_selector');
    const rows = [];
    tutorRegistrationTree.forEach(function(cat) {
        (cat.classes || []).forEach(function(cls) {
            if (!selectedClassIds.includes(String(cls.id))) return;
            (cls.subjects || []).forEach(function(sub) {
                rows.push({ id: sub.id, name: cls.name + ' / ' + sub.name });
            });
        });
    });
    selectedSubjectIds = selectedSubjectIds.filter(id => rows.some(r => String(r.id) === String(id)));
    setSelectOptions(subjectSelector, rows, selectedSubjectIds);
}

function bindTeachingProfileSelectors() {
    const categorySelector = document.getElementById('category_selector');
    const classSelector = document.getElementById('class_selector');
    const subjectSelector = document.getElementById('subject_selector');

    refreshCategorySelector();
    refreshClassSelector();
    refreshSubjectSelector();

    categorySelector.addEventListener('change', function() {
        selectedCategoryIds = getSelectedValues(categorySelector);
        refreshClassSelector();
        refreshSubjectSelector();
    });

    classSelector.addEventListener('change', function() {
        selectedClassIds = getSelectedValues(classSelector);
        refreshSubjectSelector();
    });

    subjectSelector.addEventListener('change', function() {
        selectedSubjectIds = getSelectedValues(subjectSelector);
    });
}

function bindCurrentLocationButton() {
    const button = document.getElementById('get_current_location_btn');
    const latEl = document.getElementById('tutor_lat');
    const lngEl = document.getElementById('tutor_lng');
    const statusEl = document.getElementById('location_fetch_status');

    if (!button || !latEl || !lngEl || !statusEl) return;

    button.addEventListener('click', function() {
        if (!navigator.geolocation) {
            statusEl.textContent = 'Geolocation is not supported in this browser.';
            statusEl.className = 'small text-danger pt-2';
            return;
        }

        statusEl.textContent = 'Fetching current location...';
        statusEl.className = 'small text-muted pt-2';

        navigator.geolocation.getCurrentPosition(function(position) {
            latEl.value = position.coords.latitude.toFixed(8);
            lngEl.value = position.coords.longitude.toFixed(8);
            statusEl.textContent = 'Location captured successfully.';
            statusEl.className = 'small text-success pt-2';
        }, function(error) {
            let message = 'Unable to fetch current location.';
            if (error && error.code === 1) {
                message = 'Location access denied. Please allow location permission and try again.';
            } else if (error && error.code === 2) {
                message = 'Location unavailable. Please try again.';
            } else if (error && error.code === 3) {
                message = 'Location request timed out. Please try again.';
            }
            statusEl.textContent = message;
            statusEl.className = 'small text-danger pt-2';
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    bindTeachingProfileSelectors();
    bindCurrentLocationButton();
});
</script>
