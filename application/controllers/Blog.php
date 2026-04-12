<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Blog extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        date_default_timezone_set(get_settings('timezone'));
        
        // Your own constructor code
        $this->load->database();
        $this->load->library('session');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');


        $this->user_model->check_session_data();       
    }

    function index(){
        $page_data['popular_blogs'] = $this->crud_model->get_popular_blogs(6);
        $page_data['latest_blogs'] = $this->crud_model->get_latest_blogs(6);
        $page_data['included_page'] = 'blog_latest_and_popular.php';
        $page_data['page_title'] = site_phrase('blog');
        $page_data['page_name'] = 'blogs';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    //all blogs
    function blogs($param1 = ''){

        $uri_segment = $param1;

        if(isset($_GET['search']) && !empty($_GET['search'])){
            $config = array();
            $this->db->like('title', $_GET['search']);
            $this->db->or_like('description', $_GET['search']);
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['reuse_query_string'] = TRUE;
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->like('title', $_GET['search']);
            $this->db->or_like('description', $_GET['search']);
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['search_string'] = $_GET['search'];
            $page_data['page_title'] = site_phrase('search_result');
        }elseif(isset($_GET['category']) && !empty($_GET['category'])){
            $config = array();
            
            $blog_category_id = $this->crud_model->get_blog_category_by_slug($_GET['category'])->row('blog_category_id');
            $this->db->where('blog_category_id', $blog_category_id);
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['reuse_query_string'] = TRUE;
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->where('blog_category_id', $blog_category_id);
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['page_title'] = site_phrase('search_result');
        }else{
            $config = array();
            $this->db->where('status', 1);
            $total_rows = $this->db->get('blogs')->num_rows();
            $config = pagintaion($total_rows, 9);
            $config['base_url']  = site_url('blogs/');
            $this->pagination->initialize($config);

            $this->db->order_by('added_date', 'asc');
            $this->db->where('status', 1);
            $page_data['blogs'] = $this->db->get('blogs', $config['per_page'], $uri_segment);
            $page_data['total_rows'] = $total_rows;
            $page_data['page_title'] = site_phrase('blogs');
        }
        $page_data['included_page'] = 'blogs_all.php';
        $page_data['page_name'] = 'blogs';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    function categories(){
        $page_data['included_page'] = 'blog_categories.php';
        $page_data['page_name'] = 'blogs';
        $page_data['page_title'] = site_phrase('categories');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    //blog details page
    function details($blog_slug = "", $blog_id = ""){
        $page_data['blog_details'] = $this->crud_model->get_all_blogs($blog_id)->row_array();
        $page_data['page_name'] = 'blog_details';
        $page_data['page_title'] = site_phrase('blog_details');
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    function add_blog_comment($blog_id = ""){
        $user_id = $this->session->userdata('user_id');
        if($blog_id > 0 && $user_id > 0){
            $this->crud_model->add_blog_comment($blog_id, $user_id);
            $this->session->set_flashdata('flash_message', site_phrase('your_reply_has_been_successfully_published'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }else{
            $this->session->set_flashdata('error_message', site_phrase('make_sure_you_have_logged_in'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }
    }

    function update_blog_comment($blog_comment_id = ""){
        $user_id = $this->session->userdata('user_id');
        if($blog_comment_id > 0 && $user_id > 0){
            $this->crud_model->update_blog_comment($blog_comment_id, $user_id);
            $this->session->set_flashdata('flash_message', site_phrase('your_reply_has_been_successfully_published'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }else{
            $this->session->set_flashdata('error_message', site_phrase('make_sure_you_have_logged_in'));
            redirect($_SERVER['HTTP_REFERER'], 'refresh');
        }
    }

    function delete_comment($blog_comment_id = "", $blog_id = ""){
        $blog_details = $this->crud_model->get_blogs($blog_id)->row_array();
        $user_id = $this->session->userdata('user_id');
        $this->crud_model->delete_comment($blog_comment_id, $user_id);
        $this->session->set_flashdata('flash_message', site_phrase('your_comment_has_been_deleted_successfully'));
        redirect(site_url('blog/details/'.slugify($blog_details['title']).'/'.$blog_id), 'refresh');
    }
    /**
     * Content Book Page
     * Purpose : Book-style hierarchical content (Course → Topic → Subtopic)
     * URL     : /blog/content
     * Author  : Custom Enhancement
     */
    /*public function content()
    {
        // Optional: restrict access to logged-in users
        // if (!$this->session->userdata('user_login')) {
        //     redirect(site_url('login'), 'refresh');
        // }

        $page_data['page_name']  = 'content_page';
        $page_data['page_title'] = 'Content';

        $this->load->view(
            'frontend/' . get_frontend_settings('theme') . '/index',
            $page_data
        );
    }*/
	
public function content($a=null, $b=null, $c=null, $d=null)
{
    $this->load->model('content_docs_model');

    // 1) Build full_path from URL segments
    $parts = array_filter([$a,$b,$c,$d]);
    $full_path = implode('/', $parts);

    // 2) Load tree
    $nodes = $this->content_docs_model->get_published_nodes();

    // 3) Find selected node + page
    $selected_node = null;
    $selected_page = null;

    if ($full_path) {
        $selected_node = $this->content_docs_model->get_node_by_full_path($full_path);
        if ($selected_node) {
            $selected_page = $this->content_docs_model->get_published_page_by_node($selected_node['node_id']);
        }
    }

    $page_data['page_name']  = 'content_page';
    $page_data['page_title'] = 'Docs';

    // data for views
    $page_data['nodes'] = $nodes;
    $page_data['active_full_path'] = $full_path;

    $page_data['selected_node'] = $selected_node;
    $page_data['selected_page'] = $selected_page;

    // base route so links become /blog/...
    $page_data['base_route'] = 'blog';

    $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
}


}