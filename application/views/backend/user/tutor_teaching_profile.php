<?php
$tutor_registration_tree = isset($tutor_registration_tree) && is_array($tutor_registration_tree) ? $tutor_registration_tree : [];
$tutor_profile = isset($tutor_profile) && is_array($tutor_profile) ? $tutor_profile : [];
$selected_category_ids = array_map('strval', isset($selected_category_ids) ? (array)$selected_category_ids : []);
$selected_class_ids = array_map('strval', isset($selected_class_ids) ? (array)$selected_class_ids : []);
$selected_subject_ids = array_map('strval', isset($selected_subject_ids) ? (array)$selected_subject_ids : []);
$tutor_application = isset($tutor_application) && is_array($tutor_application) ? $tutor_application : [];
$has_verification_document = !empty($tutor_application['document']);
$is_admin_approved = (int)($tutor_application['status'] ?? 0) === 1;

$profile_quality_checks = [
    'headline' => !empty($tutor_profile['headline']),
    'qualification' => !empty($tutor_profile['qualification']),
    'experience' => isset($tutor_profile['experience_years']) && $tutor_profile['experience_years'] !== '',
    'mode_fee' => !empty($tutor_profile['teaching_mode']) && $tutor_profile['hourly_fee'] !== null && $tutor_profile['hourly_fee'] !== '',
    'location' => !empty($tutor_profile['city']) && !empty($tutor_profile['country']),
    'bio' => !empty($tutor_profile['bio']) && strlen(strip_tags((string)$tutor_profile['bio'])) >= 40,
    'subjects' => !empty($selected_subject_ids),
    'document' => $has_verification_document,
];
$profile_quality_percent = (int) round((count(array_filter($profile_quality_checks)) / max(1, count($profile_quality_checks))) * 100);
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap">
                    <div>
                        <h4 class="page-title"><i class="mdi mdi-account-edit title_icon"></i> <?php echo get_phrase('teaching_profile'); ?></h4>
                        <p class="text-muted mb-0">Update the subjects you teach. Tutor search will use this teaching profile, not course manager.</p>
                    </div>
                    <div class="text-right" aria-label="Teaching profile quality <?php echo $profile_quality_percent; ?> percent">
                        <div class="h4 mb-0"><?php echo $profile_quality_percent; ?>%</div>
                        <small class="text-muted">Profile quality</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="progress mb-3" style="height: 8px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $profile_quality_percent; ?>">
                    <div class="progress-bar" style="width: <?php echo $profile_quality_percent; ?>%;"></div>
                </div>
                <div class="row mb-3">
                    <?php
                        $quality_items = [
                            ['done' => $profile_quality_checks['headline'], 'label' => 'Headline'],
                            ['done' => $profile_quality_checks['qualification'], 'label' => 'Qualification'],
                            ['done' => $profile_quality_checks['experience'], 'label' => 'Experience'],
                            ['done' => $profile_quality_checks['mode_fee'], 'label' => 'Mode and fee'],
                            ['done' => $profile_quality_checks['location'], 'label' => 'Location'],
                            ['done' => $profile_quality_checks['bio'], 'label' => 'Bio'],
                            ['done' => $profile_quality_checks['subjects'], 'label' => 'Subjects'],
                            ['done' => $profile_quality_checks['document'], 'label' => 'Verification document'],
                        ];
                    ?>
                    <?php foreach ($quality_items as $item): ?>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <span class="badge badge-<?php echo $item['done'] ? 'success' : 'warning'; ?>-lighten mr-1"><?php echo $item['done'] ? 'Done' : 'Missing'; ?></span>
                            <?php echo html_escape($item['label']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$has_verification_document): ?>
                    <div class="alert alert-warning">
                        <strong>Verification document pending.</strong>
                        You can use the tutor dashboard, but your profile will not show as verified or appear publicly until a document is uploaded. Accepted documents: Government ID proof, qualification certificate, marksheet, professional certification, teaching experience proof, or other supporting document.
                    </div>
                <?php elseif ($is_admin_approved): ?>
                    <div class="alert alert-success">
                        <strong>Verification document uploaded.</strong>
                        Your admin-approved tutor profile can be verified and publicly listed.
                    </div>
                <?php endif; ?>

                <form action="<?php echo site_url('user/update_tutor_teaching_profile'); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="verification_document">Verification document</label>
                        <input type="file" class="form-control" id="verification_document" name="verification_document" accept=".doc,.docx,.pdf,.txt,.png,.jpg,.jpeg">
                        <small class="text-muted d-block mt-1">
                            Optional while editing, but required for verified badge and public listing. Upload Government ID proof, qualification certificate, marksheet, professional certification, teaching experience proof, or another supporting document.
                        </small>
                        <?php if ($has_verification_document): ?>
                            <small class="d-block mt-1">
                                Current document:
                                <a href="<?php echo base_url('uploads/document/' . $tutor_application['document']); ?>" download>Download uploaded document</a>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tutor_headline">Headline</label>
                                <input type="text" class="form-control" id="tutor_headline" name="tutor_headline" value="<?php echo html_escape($tutor_profile['headline'] ?? ''); ?>" placeholder="e.g., AWS + DevOps Trainer" maxlength="120" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tutor_qualification">Qualification</label>
                                <input type="text" class="form-control" id="tutor_qualification" name="tutor_qualification" value="<?php echo html_escape($tutor_profile['qualification'] ?? ''); ?>" placeholder="e.g., B.Tech / AWS Certified" maxlength="160" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group mb-3">
                                <label for="tutor_experience_years">Experience (years)</label>
                                <input type="number" min="0" max="60" class="form-control" id="tutor_experience_years" name="tutor_experience_years" value="<?php echo html_escape((string)($tutor_profile['experience_years'] ?? '')); ?>" required>
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
                                <input type="number" min="0" step="1" class="form-control" id="tutor_hourly_fee" name="tutor_hourly_fee" value="<?php echo html_escape((string)($tutor_profile['hourly_fee'] ?? '')); ?>" required>
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
                                <input type="text" class="form-control" id="tutor_city" name="tutor_city" value="<?php echo html_escape($tutor_profile['city'] ?? ''); ?>" required>
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
                                <input type="text" class="form-control" id="tutor_country" name="tutor_country" value="<?php echo html_escape($tutor_profile['country'] ?? 'India'); ?>" required>
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
                                <button type="button" class="btn btn-outline-primary btn-block d-block" id="get_current_location_btn" aria-describedby="location_fetch_status">Use current location</button>
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
                                <textarea class="form-control" id="tutor_bio" name="tutor_bio" rows="4" minlength="40" required><?php echo html_escape($tutor_profile['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Subjects you teach</h5>
                    <p class="text-muted">Select categories, then classes, then subjects. Your tutor profile becomes searchable immediately after save.</p>

                    <style>
                        .teaching-checkbox-panel {
                            border: 1px solid #d9e0ea;
                            border-radius: 6px;
                            min-height: 180px;
                            max-height: 300px;
                            overflow-y: auto;
                            padding: 10px 12px;
                            background: #fff;
                        }
                        .teaching-checkbox-panel.is-empty {
                            display: flex;
                            align-items: center;
                            color: #98a4b3;
                            font-size: 13px;
                        }
                        .teaching-checkbox-item {
                            display: flex;
                            align-items: flex-start;
                            gap: 8px;
                            padding: 6px 0;
                            margin: 0;
                            color: #4d5b6a;
                            line-height: 1.35;
                        }
                        .teaching-checkbox-item input {
                            margin-top: 3px;
                        }
                        .teaching-checkbox-item span {
                            word-break: break-word;
                        }
                    </style>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Teaching Categories</label>
                                <div class="teaching-checkbox-panel" id="category_selector" aria-describedby="category_selector_help"></div>
                                <small id="category_selector_help" class="text-muted">Select one or more teaching categories.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Class / Course Group</label>
                                <div class="teaching-checkbox-panel is-empty" id="class_selector" aria-describedby="class_selector_help">Select a teaching category first.</div>
                                <small id="class_selector_help" class="text-muted">Course groups appear after category selection.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Subjects</label>
                                <div class="teaching-checkbox-panel is-empty" id="subject_selector" aria-describedby="subject_selector_help">Select a course group first.</div>
                                <small id="subject_selector_help" class="text-muted">Select at least one subject.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" aria-label="Save teaching profile">Save teaching profile</button>
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

function renderCheckboxGroup(container, rows, selectedIds, inputName, emptyText, onChange) {
    if (!container) return;
    const selected = new Set((selectedIds || []).map(String));
    container.innerHTML = '';
    container.classList.toggle('is-empty', rows.length === 0);
    if (!rows.length) {
        container.textContent = emptyText;
        return;
    }

    rows.forEach(function(item) {
        const id = inputName.replace(/\W+/g, '_') + '_' + String(item.id);
        const label = document.createElement('label');
        label.className = 'teaching-checkbox-item';
        label.setAttribute('for', id);

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.id = id;
        input.name = inputName;
        input.value = String(item.id);
        input.checked = selected.has(String(item.id));

        const text = document.createElement('span');
        text.textContent = item.name;

        label.appendChild(input);
        label.appendChild(text);
        container.appendChild(label);

        input.addEventListener('change', onChange);
    });
}

function getCheckedValues(container) {
    return Array.from(container.querySelectorAll('input[type="checkbox"]:checked')).map(input => String(input.value));
}

function refreshCategorySelector() {
    const categorySelector = document.getElementById('category_selector');
    renderCheckboxGroup(
        categorySelector,
        tutorRegistrationTree.map(function(cat) { return { id: cat.id, name: cat.name }; }),
        selectedCategoryIds,
        'tutor_category_ids[]',
        'No teaching categories found.',
        function() {
            selectedCategoryIds = getCheckedValues(categorySelector);
            refreshClassSelector();
            refreshSubjectSelector();
        }
    );
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
    renderCheckboxGroup(
        classSelector,
        rows,
        selectedClassIds,
        'tutor_class_ids[]',
        'Select a teaching category first.',
        function() {
            selectedClassIds = getCheckedValues(classSelector);
            refreshSubjectSelector();
        }
    );
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
    renderCheckboxGroup(
        subjectSelector,
        rows,
        selectedSubjectIds,
        'tutor_subject_ids[]',
        'Select a course group first.',
        function() {
            selectedSubjectIds = getCheckedValues(subjectSelector);
        }
    );
}

function bindTeachingProfileSelectors() {
    refreshCategorySelector();
    refreshClassSelector();
    refreshSubjectSelector();
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
