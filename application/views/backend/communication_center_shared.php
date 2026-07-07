<?php
$role = $communication_role === 'admin' ? 'admin' : 'tutor';
$options = $communication_options;
$action = site_url('communication/send/' . $role);
$preview_url = site_url('communication/preview/' . $role);
$templates = $communication_templates ?? array();
?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title"><?php echo html_escape($page_title); ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <form id="communication-form" method="post" action="<?php echo $action; ?>" class="card">
            <div class="card-body">
                <input type="hidden" name="communication_csrf_token" value="<?php echo html_escape($communication_csrf_token); ?>">
                <h5 class="mb-3">Audience</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">All courses</option>
                                <?php foreach ($options['categories'] as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>"><?php echo html_escape($item['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="class_id">Class / Course Group</label>
                            <select class="form-control" id="class_id" name="class_id">
                                <option value="">All</option>
                                <?php foreach ($options['classes'] as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>" data-category-id="<?php echo (int)($item['category_id'] ?? 0); ?>"><?php echo html_escape($item['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="subject_id">Subject</label>
                            <select class="form-control" id="subject_id" name="subject_id">
                                <option value="">All</option>
                                <?php foreach ($options['subjects'] as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>" data-category-id="<?php echo (int)($item['category_id'] ?? 0); ?>" data-class-id="<?php echo (int)($item['class_id'] ?? 0); ?>"><?php echo html_escape($item['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="course_id">Course interest</label>
                            <select class="form-control" id="course_id" name="course_id">
                                <option value="">All</option>
                                <?php foreach ($options['courses'] as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>"><?php echo html_escape($item['title'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="batch_id">Batch</label>
                            <select class="form-control" id="batch_id" name="batch_id">
                                <option value="">All</option>
                                <?php foreach ($options['batches'] as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>"><?php echo html_escape($item['title'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($role === 'admin'): ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tutor_id">Tutor</label>
                            <select class="form-control" id="tutor_id" name="tutor_id">
                                <option value="">All</option>
                                <?php foreach ($options['tutors'] as $item): ?>
                                    <?php $label = trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')); ?>
                                    <option value="<?php echo (int)$item['id']; ?>"><?php echo html_escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4"><div class="form-group"><label for="city">City/location</label><input class="form-control" id="city" name="city" maxlength="120"></div></div>
                    <div class="col-md-4"><div class="form-group"><label for="payment_status">Payment status</label><select class="form-control" id="payment_status" name="payment_status"><option value="">All</option><option value="paid">Paid</option><option value="unpaid">No payment recorded</option></select></div></div>
                    <div class="col-md-4"><div class="form-group"><label for="student_status">Student status</label><select class="form-control" id="student_status" name="student_status"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div></div>
                    <?php if ($role === 'tutor'): ?><div class="col-md-4"><div class="form-group"><label for="relationship">Relationship</label><select class="form-control" id="relationship" name="relationship"><option value="">Any authorized</option><option value="requested">Requested tutor</option><option value="accepted">Accepted by tutor</option></select></div></div><?php endif; ?>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <button type="button" id="preview-recipients" class="btn btn-outline-primary">Preview recipients</button>
                    <strong id="recipient-count" class="ml-3" aria-live="polite">Not calculated</strong>
                </div>

                <h5 class="mb-3">Message</h5>
                <div class="row"><div class="col-md-8"><div class="form-group"><label for="template_key">Template</label><select class="form-control" id="template_key" name="template_key"><option value="">Write custom message</option><?php foreach($templates as $template):?><option value="<?php echo html_escape($template['template_key']);?>" data-category="<?php echo html_escape($template['message_category']);?>" data-subject="<?php echo html_escape($template['subject_template']);?>" data-email="<?php echo html_escape($template['email_template']);?>" data-whatsapp="<?php echo html_escape($template['whatsapp_template']);?>"><?php echo html_escape($template['name']);?></option><?php endforeach;?></select></div></div><div class="col-md-4"><div class="form-group"><label for="message_category">Message type</label><select class="form-control" id="message_category" name="message_category"><option value="transactional">Transactional</option><option value="promotional">Promotional</option></select></div></div></div>
                <div class="form-group"><label for="title">Message title/subject</label><input class="form-control" id="title" name="title" maxlength="255" required></div>
                <div class="form-group"><label for="email_body">Email body</label><textarea class="form-control" id="email_body" name="email_body" rows="7"></textarea></div>
                <div class="form-group"><label for="whatsapp_body">WhatsApp body</label><textarea class="form-control" id="whatsapp_body" name="whatsapp_body" rows="4"></textarea></div>
                <p class="text-muted small">Placeholders: {{student_name}}, {{course_name}}, {{tutor_name}}, {{class_name}}, {{subject_name}}, {{batch_name}}, {{start_date}}, {{fee}}, {{login_link}}</p>
                <div class="form-group">
                    <div class="custom-control custom-checkbox custom-control-inline"><input type="checkbox" class="custom-control-input" id="send_email" name="send_email" value="1" checked><label class="custom-control-label" for="send_email">Email</label></div>
                    <div class="custom-control custom-checkbox custom-control-inline"><input type="checkbox" class="custom-control-input" id="send_whatsapp" name="send_whatsapp" value="1"><label class="custom-control-label" for="send_whatsapp">WhatsApp</label></div>
                    <div class="custom-control custom-checkbox custom-control-inline"><input type="checkbox" class="custom-control-input" id="send_in_app" name="send_in_app" value="1" checked><label class="custom-control-label" for="send_in_app">In-app</label></div>
                </div>
                <div class="alert alert-warning py-2">WhatsApp sends are logged as skipped until a provider is configured. Maximum recipients per campaign: <?php echo (int)$max_recipients; ?>.</div>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Send this message to the previewed authorized audience?');">Send now</button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card"><div class="card-body"><h5>Message preview</h5><h6 id="preview-title" class="mt-3 text-muted">Subject preview</h6><div id="preview-body" class="border p-3 mt-2" style="min-height:160px;white-space:pre-wrap">Your message preview appears here.</div><div id="recipient-sample" class="mt-3 small text-muted"></div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">Campaign history</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Date</th><th>Title</th><th>Type</th><th>Recipients</th><th>Sent</th><th>Failed</th><th>Skipped</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($campaign_history)): ?><tr><td colspan="8" class="text-center text-muted">No campaigns yet.</td></tr><?php endif; ?>
                <?php foreach ($campaign_history as $campaign): ?><tr>
                    <td><?php echo html_escape($campaign['created_at']); ?></td>
                    <td><?php echo html_escape($campaign['title']); ?></td>
                    <td><?php echo html_escape(ucfirst($campaign['campaign_type'])); ?></td>
                    <td><?php echo (int)$campaign['total_recipients']; ?></td>
                    <td><?php echo (int)$campaign['sent_count']; ?></td>
                    <td><?php echo (int)$campaign['failed_count']; ?></td>
                    <td><?php echo (int)$campaign['skipped_count']; ?></td>
                    <td><span class="badge badge-secondary"><?php echo html_escape(ucfirst($campaign['status'])); ?></span></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><?php echo !empty($communication_timeline_student_id) ? 'Student communication timeline' : 'Recent delivery logs'; ?></h5>
            <?php if(!empty($communication_timeline_student_id)):?><a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url($role==='admin'?'admin/communication-center':'user/student-communication');?>">Show all students</a><?php endif;?>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>Date</th><th>Campaign</th><th>Student</th><th>Channel</th><th>Status</th><th>Attempts</th><th>Delivery note</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($delivery_logs)): ?><tr><td colspan="8" class="text-center text-muted">No delivery logs yet.</td></tr><?php endif; ?>
                <?php foreach ($delivery_logs as $log): ?><tr>
                    <td><?php echo html_escape($log['sent_at'] ?: $log['created_at']); ?></td>
                    <td><?php echo html_escape($log['title']); ?></td>
                    <td><a href="<?php echo site_url(($role==='admin'?'admin/communication-center':'user/student-communication').'?student_id='.(int)$log['student_user_id']); ?>"><?php echo html_escape(trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''))); ?></a></td>
                    <td><?php echo html_escape(ucfirst(str_replace('_', ' ', $log['channel']))); ?></td>
                    <td><span class="badge badge-<?php echo $log['status'] === 'sent' ? 'success' : ($log['status'] === 'failed' ? 'danger' : 'warning'); ?>"><?php echo html_escape(ucfirst($log['status'])); ?></span></td>
                    <td><?php echo (int)($log['attempt_count']??0);?></td>
                    <td><?php echo html_escape($log['error_message'] ?: 'Delivered'); ?></td>
                    <td><?php if(in_array($log['status'],array('failed','pending'),true)&&(int)($log['attempt_count']??0)<5):?><form method="post" action="<?php echo site_url('communication/retry/'.$log['id']);?>"><button class="btn btn-outline-primary btn-xs">Retry</button></form><?php endif;?></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('communication-form');
    var title = document.getElementById('title');
    var emailBody = document.getElementById('email_body');
    var whatsappBody = document.getElementById('whatsapp_body');
    var categorySelect = document.getElementById('category_id');
    var classSelect = document.getElementById('class_id');
    var subjectSelect = document.getElementById('subject_id');
    var templateSelect = document.getElementById('template_key');
    var categorySelectMessage = document.getElementById('message_category');
    var classOptions = Array.prototype.slice.call(classSelect.querySelectorAll('option')).map(function (option) { return option.cloneNode(true); });
    var subjectOptions = Array.prototype.slice.call(subjectSelect.querySelectorAll('option')).map(function (option) { return option.cloneNode(true); });

    function rebuildSelect(select, sourceOptions, predicate) {
        var currentValue = select.value;
        select.innerHTML = '';
        sourceOptions.forEach(function (option) {
            if (option.value === '' || predicate(option)) {
                select.appendChild(option.cloneNode(true));
            }
        });
        var hasCurrent = Array.prototype.some.call(select.options, function (option) { return option.value === currentValue; });
        select.value = hasCurrent ? currentValue : '';
    }

    function cascadeAudienceFilters() {
        var categoryId = categorySelect.value;
        var classId = classSelect.value;

        rebuildSelect(classSelect, classOptions, function (option) {
            return !categoryId || option.getAttribute('data-category-id') === categoryId;
        });

        classId = classSelect.value;
        rebuildSelect(subjectSelect, subjectOptions, function (option) {
            var categoryMatches = !categoryId || option.getAttribute('data-category-id') === categoryId;
            var classMatches = !classId || option.getAttribute('data-class-id') === classId;
            return categoryMatches && classMatches;
        });
    }

    categorySelect.addEventListener('change', function () {
        classSelect.value = '';
        subjectSelect.value = '';
        cascadeAudienceFilters();
    });
    classSelect.addEventListener('change', function () {
        subjectSelect.value = '';
        cascadeAudienceFilters();
    });
    cascadeAudienceFilters();

    function updatePreview() {
        document.getElementById('preview-title').textContent = title.value || 'Subject preview';
        document.getElementById('preview-body').textContent = emailBody.value || whatsappBody.value || 'Your message preview appears here.';
    }
    title.addEventListener('input', updatePreview);
    emailBody.addEventListener('input', updatePreview);
    whatsappBody.addEventListener('input', updatePreview);
    templateSelect.addEventListener('change', function () {
        var option = templateSelect.options[templateSelect.selectedIndex];
        if (!option || !option.value) return;
        title.value = option.dataset.subject || '';
        emailBody.value = option.dataset.email || '';
        whatsappBody.value = option.dataset.whatsapp || '';
        categorySelectMessage.value = option.dataset.category || 'transactional';
        updatePreview();
    });
    document.getElementById('preview-recipients').addEventListener('click', function () {
        var button = this;
        button.disabled = true;
        document.getElementById('recipient-count').textContent = 'Calculating...';
        fetch('<?php echo $preview_url; ?>', {method: 'POST', body: new FormData(form), credentials: 'same-origin'})
            .then(function (response) { if (!response.ok) throw new Error('Preview failed'); return response.json(); })
            .then(function (data) {
                document.getElementById('recipient-count').textContent = data.count + ' authorized recipient(s)';
                document.getElementById('recipient-sample').textContent = data.sample.map(function (row) { return row.name + (row.class_name ? ' - ' + row.class_name : ''); }).join(', ');
            })
            .catch(function () { document.getElementById('recipient-count').textContent = 'Unable to calculate recipients'; })
            .finally(function () { button.disabled = false; });
    });
}());
</script>
