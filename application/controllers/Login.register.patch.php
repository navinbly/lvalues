<?php
// Apply these changes inside Login::register() and add the callback method below.

// 1) Near the other tutor validation rules, ADD:
$this->load->model('Tutor_category_model', 'tutor_category_model');
$this->form_validation->set_rules('tutor_category_ids[]', 'Category', 'required');
$this->form_validation->set_rules('tutor_class_ids[]', 'Class / Course Group', 'required');
$this->form_validation->set_rules('tutor_subject_ids[]', 'Subject', 'required');

// 2) After validation succeeds but before file upload / duplication checks, ADD:
$selected_category_ids = $this->tutor_category_model->normalize_ids($this->input->post('tutor_category_ids'));
$selected_class_ids    = $this->tutor_category_model->normalize_ids($this->input->post('tutor_class_ids'));
$selected_subject_ids  = $this->tutor_category_model->normalize_ids($this->input->post('tutor_subject_ids'));

if (!$this->tutor_category_model->class_ids_belong_to_categories($selected_class_ids, $selected_category_ids)) {
    $this->session->set_flashdata('error_message', 'Selected classes do not belong to the selected category.');
    redirect(site_url('sign_up?tutor=1'), 'refresh');
}

if (!$this->tutor_category_model->subject_ids_belong_to_classes($selected_subject_ids, $selected_class_ids)) {
    $this->session->set_flashdata('error_message', 'Selected subjects do not belong to the selected class list.');
    redirect(site_url('sign_up?tutor=1'), 'refresh');
}
