<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Content_comments_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add_comment($node_id, $user_id, $name, $email, $comment_text)
    {
        $data = [
            'node_id' => (int)$node_id,
            'user_id' => $user_id ? (int)$user_id : null,
            'name' => $name ? trim($name) : null,
            'email' => $email ? trim($email) : null,
            'comment_text' => trim($comment_text),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('content_page_comments', $data);
    }

    public function get_approved_comments($node_id)
    {
        $this->db->where('node_id', (int)$node_id);
        $this->db->where('status', 'approved');
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('content_page_comments')->result_array();
    }

    public function get_pending_comments()
    {
        $this->db->where('status', 'pending');
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('content_page_comments')->result_array();
    }

    public function approve_comment($comment_id, $admin_id)
    {
        $this->db->where('comment_id', (int)$comment_id);
        return $this->db->update('content_page_comments', [
            'status' => 'approved',
            'approved_by' => (int)$admin_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function reject_comment($comment_id, $admin_id)
    {
        $this->db->where('comment_id', (int)$comment_id);
        return $this->db->update('content_page_comments', [
            'status' => 'rejected',
            'approved_by' => (int)$admin_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
}
