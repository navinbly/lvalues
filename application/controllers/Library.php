<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Library extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Content_docs_model','content_docs');
    }

    public function index()
    {
        $query = trim((string)$this->input->get('q', true));
        $data = ['page_name'=>'library/index','page_title'=>'Books & Learning Library','books'=>$this->content_docs->get_public_books($query),'query'=>$query];
        $this->load->view('frontend/'.get_frontend_settings('theme').'/index', $data);
    }

    public function book($slug = '')
    {
        $preview = $this->input->get('preview') === '1' && $this->session->userdata('admin_login') == true;
        $book = $this->content_docs->get_public_book($slug, $preview);
        if (!$book) { show_404(); return; }
        $query = trim((string)$this->input->get('q', true));
        $tree = $this->content_docs->get_public_book_tree($book['root_key'], $query, $preview);
        $pages = array_values(array_filter($tree, function($row){ return ($row['content_type'] ?? '') === 'page'; }));
        $requested = (int)$this->input->get('page');
        $selectedIndex = 0;
        foreach ($pages as $index=>$page) if ((int)$page['node_id'] === $requested) $selectedIndex = $index;
        $data = [
            'page_name'=>'library/reader','page_title'=>$book['title'],'book'=>$book,'tree'=>$tree,'pages'=>$pages,
            'selected_page'=>$pages[$selectedIndex] ?? null,'selected_index'=>$selectedIndex,'query'=>$query,
            'canonical_url'=>site_url('books/'.$book['slug']),'is_preview'=>$preview
        ];
        $this->load->view('frontend/'.get_frontend_settings('theme').'/index', $data);
    }

    public function state()
    {
        if ($this->input->method(true) !== 'POST') show_error('Method not allowed', 405);
        $result = $this->content_docs->save_reading_state(
            (int)$this->session->userdata('user_id'), (int)$this->input->post('book_id'),
            (int)$this->input->post('page_id'), (float)$this->input->post('progress'),
            $this->input->post('bookmark') === null ? null : (bool)$this->input->post('bookmark')
        );
        return $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }
}
