<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Content_pages_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function ensure_review_columns()
    {
        if ($this->db->table_exists('content_nodes')) {
            if (!$this->db->field_exists('is_deleted', 'content_nodes')) {
                @$this->db->query("ALTER TABLE content_nodes ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0");
            }
        }
        if ($this->db->table_exists('content_node_pages')) {
            if (!$this->db->field_exists('created_by', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN created_by INT NULL AFTER node_id");
            }
            if (!$this->db->field_exists('review_note', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN review_note TEXT NULL AFTER status");
            }
            if (!$this->db->field_exists('admin_remark', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN admin_remark TEXT NULL AFTER review_note");
            }
            if (!$this->db->field_exists('review_status', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN review_status ENUM('draft','in_review','approved','rejected','on_hold','update_required','published','deleted') NOT NULL DEFAULT 'draft' AFTER admin_remark");
            }
            if (!$this->db->field_exists('deleted_by', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN deleted_by INT NULL AFTER updated_at");
            }
            if (!$this->db->field_exists('deleted_at', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN deleted_at DATETIME NULL AFTER deleted_by");
            }
            if (!$this->db->field_exists('is_deleted', 'content_node_pages')) {
                @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at");
            }
            @$this->db->query("ALTER TABLE content_node_pages MODIFY status ENUM('draft','pending','published','rejected','on_hold','update_required','deleted') NOT NULL DEFAULT 'draft'");
        }
    }

    public function save_page($node_id, $html, $meta = [], $user_id, $user_role, $save_status = 'draft')
    {
        $this->ensure_review_columns();
        $existing = $this->db->get_where('content_node_pages', ['node_id' => (int)$node_id])->row_array();
        if (!empty($existing) && (empty($meta['og_image']) || $meta['og_image'] === null)) {
            $meta['og_image'] = $existing['og_image'] ?? null;
        }

        $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
        if (!$node) {
            return ['ok' => false, 'message' => 'Please select a valid book page or article before saving.'];
        }

        $is_admin = strtolower((string)$user_role) === 'admin';
        $requested_status = in_array($save_status, ['draft', 'pending', 'published'], true) ? $save_status : 'draft';
        if (!$is_admin && $requested_status === 'published') {
            $requested_status = 'pending';
        }
        $status = $requested_status;
        $review_status = $status === 'pending' ? 'in_review' : ($status === 'published' ? 'published' : 'draft');
        $now = date('Y-m-d H:i:s');

        $data = [
            'node_id'          => (int)$node_id,
            'html'             => $html,
            'meta_title'       => $meta['meta_title'] ?? null,
            'meta_description' => $meta['meta_description'] ?? null,
            'meta_keywords'    => $meta['meta_keywords'] ?? null,
            'canonical_url'    => $meta['canonical_url'] ?? null,
            'content_category' => trim((string)($meta['content_category'] ?? '')),
            'content_tags'     => trim((string)($meta['content_tags'] ?? '')),
            'og_image'         => $meta['og_image'] ?? null,
            'status'           => $status,
            'review_status'    => $review_status,
            'updated_by'       => (int)$user_id,
            'updated_at'       => $now,
            'is_deleted'       => 0,
        ];

        if ($status === 'published') {
            $data['approved_by'] = (int)$user_id;
            $data['approved_at'] = $now;
        }

        $this->db->trans_start();
        if ($existing) {
            $this->db->where('page_id', (int)$existing['page_id'])->update('content_node_pages', $data);
        } else {
            $data['created_by'] = (int)$user_id;
            $data['created_at'] = $now;
            $this->db->insert('content_node_pages', $data);
        }

        $node_data = [
            'status' => $status,
            'review_status' => $review_status,
            'updated_by' => (int)$user_id,
            'updated_at' => $now,
            'is_deleted' => 0,
        ];
        if ($status === 'published') {
            $node_data['approved_by'] = (int)$user_id;
            $node_data['approved_at'] = $now;
        }

        if (strpos((string)$node['root_key'], 'book_') === 0) {
            $this->db->where('root_key', $node['root_key'])->update('content_nodes', $node_data);
            $book_nodes = $this->db->select('node_id')->where('root_key', $node['root_key'])->get('content_nodes')->result_array();
            foreach ($book_nodes as $book_node) {
                $book_page_data = [
                    'status' => $status,
                    'review_status' => $review_status,
                    'updated_by' => (int)$user_id,
                    'updated_at' => $now,
                ];
                if ($status === 'published') {
                    $book_page_data['approved_by'] = (int)$user_id;
                    $book_page_data['approved_at'] = $now;
                }
                $this->db->where('node_id', (int)$book_node['node_id'])->update('content_node_pages', $book_page_data);
            }
        } else {
            $this->db->where('node_id', (int)$node_id)->update('content_nodes', $node_data);
        }
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return ['ok' => false, 'message' => 'Unable to save content.'];
        }
        if ($status === 'draft') return ['ok' => true, 'message' => 'Draft saved successfully.'];
        if ($status === 'pending') return ['ok' => true, 'message' => 'Content submitted for review.'];
        return ['ok' => true, 'message' => 'Content published successfully.'];
    }

    public function get_page_by_node($node_id)
    {
        return $this->db->where('COALESCE(is_deleted,0)', 0, false)
            ->get_where('content_node_pages', ['node_id' => (int)$node_id])
            ->row_array();
    }

    public function get_page_by_node_id($node_id)
    {
        return $this->get_page_by_node($node_id);
    }

    public function approve_page($page_id, $admin_id)
    {
        $this->ensure_review_columns();
        $this->db->where('page_id', (int)$page_id);
        return $this->db->update('content_node_pages', [
            'status'        => 'published',
            'review_status' => 'published',
            'approved_by'   => (int)$admin_id,
            'approved_at'   => date('Y-m-d H:i:s')
        ]);
    }

    public function reject_page($page_id, $admin_id = 0, $reason = '')
    {
        $this->ensure_review_columns();
        $this->db->where('page_id', (int)$page_id);
        return $this->db->update('content_node_pages', [
            'status'        => 'rejected',
            'review_status' => 'rejected',
            'admin_remark'  => $reason,
            'review_note'   => $reason,
            'updated_at'    => date('Y-m-d H:i:s')
        ]);
    }
}
