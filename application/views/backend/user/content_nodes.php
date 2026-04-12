<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$CI =& get_instance();
$CI->load->model('content_docs_model');
$user_id = (int) $CI->session->userdata('user_id');
$CI->content_docs_model->ensure_root_nodes_exist($user_id);
$root_rules = $CI->content_docs_model->get_root_rules();
$nodes      = $CI->content_docs_model->get_all_nodes_for_admin();
$role       = strtolower((string)$CI->session->userdata('role')) ?: 'tutor';

ob_start();
include APPPATH . 'views/backend/admin/content_nodes.php';
$html = ob_get_clean();

$replacements = [
    site_url('admin/content_docs_readme')       => site_url('user/content_docs_readme'),
    site_url('admin/content_nodes/add_root')    => site_url('user/content_nodes/add_root'),
    site_url('admin/content_nodes/add')         => site_url('user/content_nodes/add'),
    site_url('admin/content_nodes/update')      => site_url('user/content_nodes/update'),
    site_url('admin/content_nodes_delete/')     => site_url('user/content_nodes/delete/'),
    site_url('admin/content_nodes')             => site_url('user/content_nodes'),
    site_url('admin/content_page_save')         => site_url('user/content_page_save'),
    site_url('admin/content_page_get/')         => site_url('user/content_page_get/'),
    site_url('admin/content_page_upload')       => site_url('user/content_page_upload'),
    site_url('admin/content_page_pdf_upload')   => site_url('user/content_page_pdf_upload'),
];
$html = str_replace(array_keys($replacements), array_values($replacements), $html);
echo $html;
