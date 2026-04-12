<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tutor_api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tutor_master_model', 'tutor_master_model');
        $this->output->set_content_type('application/json');
    }

    public function registration_tree()
    {
        echo json_encode(['status' => 'success', 'data' => $this->tutor_master_model->get_registration_tree()]);
    }

    public function classes_by_category()
    {
        $ids = $this->input->get('category_ids');
        $ids = is_array($ids) ? $ids : explode(',', (string)$ids);
        echo json_encode(['status' => 'success', 'data' => $this->tutor_master_model->get_classes_by_category_ids($ids)]);
    }

    public function subjects_by_class()
    {
        $ids = $this->input->get('class_ids');
        $ids = is_array($ids) ? $ids : explode(',', (string)$ids);
        echo json_encode(['status' => 'success', 'data' => $this->tutor_master_model->get_subjects_by_class_ids($ids)]);
    }
}
