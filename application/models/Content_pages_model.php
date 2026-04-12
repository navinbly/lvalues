<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Content_pages_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ============================================
    // Save or Update Content Page for a Node
    // ============================================
    public function save_page($node_id, $html, $meta = [], $user_id, $user_role)
    {
        // Check if page already exists for node
       $existing = $this->db->get_where('content_node_pages', [
			'node_id' => (int)$node_id
		])->row_array();

		// Preserve old OG image if not provided in this save
		if (!empty($existing) && (empty($meta['og_image']) || $meta['og_image'] === null)) {
			$meta['og_image'] = $existing['og_image'] ?? null;
		}

        // Status logic
        // Admin → published directly
        // Tutor → pending approval
        $status = ($user_role === 'admin') ? 'published' : 'pending';

        $data = [
            'node_id'          => (int)$node_id,
            'html'             => $html,
            'meta_title'       => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
            'meta_keywords'    => $meta['meta_keywords'] ?? null,
            'canonical_url'    => $meta['canonical_url'] ?? null,
            'og_image'         => $meta['og_image'] ?? null,
            'status'           => $status,
            'updated_by'       => (int)$user_id,
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        if ($status === 'published') {
            $data['approved_by'] = (int)$user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        if ($existing) {
            // Update
            $this->db->where('page_id', $existing['page_id']);
            $this->db->update('content_node_pages', $data);
            return ['ok' => true, 'message' => 'Content updated successfully'];
        } else {
            // Insert new
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('content_node_pages', $data);
            return ['ok' => true, 'message' => 'Content saved successfully'];
        }
    }

    // ============================================
    // Get page by node_id
    // ============================================
    public function get_page_by_node($node_id)
    {
        return $this->db
            ->get_where('content_node_pages', ['node_id' => (int)$node_id])
            ->row_array();
    }

    // ============================================
    // Admin Approve Content
    // ============================================
    public function approve_page($page_id, $admin_id)
    {
        $this->db->where('page_id', (int)$page_id);
        return $this->db->update('content_node_pages', [
            'status'      => 'published',
            'approved_by' => (int)$admin_id,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    // ============================================
    // Reject Content
    // ============================================
    public function reject_page($page_id)
    {
        $this->db->where('page_id', (int)$page_id);
        return $this->db->update('content_node_pages', [
            'status' => 'rejected'
        ]);
    }
	
	public function get_page_by_node_id($node_id)
{
    return $this->db->get_where('content_node_pages', ['node_id' => (int)$node_id])->row_array();
}

}
