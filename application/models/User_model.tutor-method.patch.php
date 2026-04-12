<?php
// Replace only the method upsert_tutor_profile_from_post() in User_model.php with the version below.

private function upsert_tutor_profile_from_post($user_id)
{
    if (!$this->db->table_exists('tutor_profiles')) {
        return;
    }

    $this->load->model('Tutor_category_model', 'tutor_category_model');

    $fee_type = $this->input->post('fee_type');
    if (!in_array($fee_type, ['per_hour', 'per_subject'], true)) {
        $fee_type = 'per_hour';
    }

    $subject_fees = [];
    $fee_names = $this->input->post('subject_fee_name');
    $fee_amounts = $this->input->post('subject_fee_amount');
    if (is_array($fee_names) && is_array($fee_amounts)) {
        $count = max(count($fee_names), count($fee_amounts));
        for ($i = 0; $i < $count; $i++) {
            $name = html_escape(trim((string)($fee_names[$i] ?? '')));
            $amount = trim((string)($fee_amounts[$i] ?? ''));
            if ($name !== '' && $amount !== '' && is_numeric($amount) && (float)$amount >= 0) {
                $subject_fees[] = [
                    'subject_name' => $name,
                    'fee' => (float)$amount,
                ];
            }
        }
    }

    $selected_category_ids = $this->tutor_category_model->normalize_ids($this->input->post('tutor_category_ids'));
    $selected_class_ids    = $this->tutor_category_model->normalize_ids($this->input->post('tutor_class_ids'));
    $subject_ids           = $this->tutor_category_model->normalize_ids($this->input->post('tutor_subject_ids'));

    $profile = [
        'user_id'          => (int)$user_id,
        'headline'         => html_escape((string)$this->input->post('tutor_headline')),
        'bio'              => $this->input->post('tutor_bio'),
        'qualification'    => html_escape((string)$this->input->post('tutor_qualification')),
        'experience_years' => is_numeric($this->input->post('tutor_experience_years')) ? (int)$this->input->post('tutor_experience_years') : null,
        'teaching_mode'    => in_array($this->input->post('tutor_teaching_mode'), ['online','offline','both'], true) ? $this->input->post('tutor_teaching_mode') : 'both',
        'city'             => html_escape((string)$this->input->post('tutor_city')),
        'state'            => html_escape((string)$this->input->post('tutor_state')),
        'country'          => html_escape((string)$this->input->post('tutor_country')),
        'pincode'          => html_escape((string)$this->input->post('tutor_pincode')),
        'hourly_fee'       => ($fee_type === 'per_hour' && is_numeric($this->input->post('tutor_hourly_fee'))) ? (float)$this->input->post('tutor_hourly_fee') : null,
        'lat'              => is_numeric($this->input->post('tutor_lat')) ? (float)$this->input->post('tutor_lat') : null,
        'lng'              => is_numeric($this->input->post('tutor_lng')) ? (float)$this->input->post('tutor_lng') : null,
        'status'           => 'pending',
    ];

    if ($this->db->field_exists('fee_type', 'tutor_profiles')) {
        $profile['fee_type'] = $fee_type;
    }
    if ($this->db->field_exists('subject_fees_json', 'tutor_profiles')) {
        $profile['subject_fees_json'] = !empty($subject_fees) ? json_encode($subject_fees) : null;
    }
    if ($this->db->field_exists('primary_category_id', 'tutor_profiles')) {
        $profile['primary_category_id'] = !empty($selected_category_ids) ? (int)$selected_category_ids[0] : null;
    }

    foreach ($profile as $k => $v) {
        if ($v === '') {
            unset($profile[$k]);
        }
    }

    $existing = $this->db->get_where('tutor_profiles', ['user_id' => (int)$user_id], 1);
    if ($existing->num_rows() > 0) {
        $this->db->where('user_id', (int)$user_id)->update('tutor_profiles', $profile);
        $tutor_profile_id = (int)$existing->row('id');
    } else {
        $this->db->insert('tutor_profiles', $profile);
        $tutor_profile_id = (int)$this->db->insert_id();
    }

    if ($this->db->table_exists('tutor_subjects')) {
        $this->db->where('tutor_profile_id', $tutor_profile_id)->delete('tutor_subjects');
        foreach ($subject_ids as $sid) {
            $this->db->insert('tutor_subjects', [
                'tutor_profile_id' => $tutor_profile_id,
                'subject_id'       => $sid,
                'level'            => !empty($selected_class_ids) ? implode(',', $selected_class_ids) : null,
            ]);
        }
    }
}
