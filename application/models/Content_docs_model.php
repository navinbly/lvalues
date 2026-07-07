<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Content_docs_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // -------- ROOT RULES + ROOT NODES --------
    public function get_root_rules()
    {
        return $this->db->get('content_node_rules')->result_array();
    }

    // Auto-create root nodes only if missing (safe). Call this from Admin page load.
    public function ensure_root_nodes_exist($created_by)
    {
        $rules = $this->get_root_rules();
        foreach ($rules as $r) {
            $root_key = $r['root_key'];
            $max_depth = (int)$r['max_depth'];
            $title = $r['root_title'];

            $existing = $this->db->get_where('content_nodes', ['is_root' => 1, 'root_key' => $root_key])->row_array();
            if ($existing) continue;

            $slug = $this->slugify($title);
            // full_path for root should be root_key (gcp/aws/dwh)
            $full_path = $root_key;

            $data = [
                'parent_id'   => null,
                'title'       => $title,
                'slug'        => $slug,
                'full_path'   => $full_path,
                'level'       => 1,
                'sort_order'  => 0,
                'is_root'     => 1,
                'root_key'    => $root_key,
                'max_depth'   => $max_depth,
                'status'      => 'published',
                'created_by'  => (int)$created_by,
                'approved_by' => (int)$created_by,
                'approved_at' => date('Y-m-d H:i:s'),
                'created_at'  => date('Y-m-d H:i:s')
            ];

            $this->db->insert('content_nodes', $data);
        }
    }

    // -------- TREE FETCH --------
    public function get_all_nodes_for_admin()
    {
        $this->ensure_review_columns();
        // Admin needs every active workflow state to track draft, review, rejected,
        // changes requested, and published content from one studio.
        $this->db->where('COALESCE(is_deleted,0)', 0, false);
        $this->db->where_in('status', ['published', 'pending', 'draft', 'rejected', 'on_hold', 'update_required']);
        $this->db->order_by('root_key', 'ASC');
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('title', 'ASC');
        return $this->db->get('content_nodes')->result_array();
    }

    // -------- ADD NODE --------
    public function add_node($user_id, $user_role, $parent_id, $title)
    {
        $title = trim($title);
        if ($title === '') {
            return ['ok' => false, 'message' => 'Title is required'];
        }

        // If parent_id is empty => not allowed (we always attach under a root)
        if (!$parent_id) {
            return ['ok' => false, 'message' => 'Parent node is required'];
        }

        $parent = $this->db->get_where('content_nodes', ['node_id' => (int)$parent_id])->row_array();
        if (!$parent) {
            return ['ok' => false, 'message' => 'Parent node not found'];
        }
        $is_admin = $this->is_admin_role($user_role);
        if (!$is_admin && (int)$parent['is_root'] !== 1 && (int)($parent['created_by'] ?? 0) !== (int)$user_id) {
            return ['ok' => false, 'message' => 'You can add content only inside your own tree.'];
        }

        if (!in_array($parent['status'], ['published']) && !($is_admin && $parent['status'] === 'draft')) {
            return ['ok' => false, 'message' => 'You can add child only under published nodes (for now).'];
        }

        $root_key = $parent['is_root'] == 1 ? $parent['root_key'] : $parent['root_key'];

        // Fetch max depth from rules table
        $rule = $this->db->get_where('content_node_rules', ['root_key' => $root_key])->row_array();
        if (!$rule) {
            return ['ok' => false, 'message' => 'Root rule not configured for this tree.'];
        }
        $max_depth = (int)$rule['max_depth'];

        $new_level = (int)$parent['level'] + 1;

        // Enforce max depth (level includes root level=1)
        if ($new_level > $max_depth) {
            return ['ok' => false, 'message' => "Max depth reached for {$rule['root_title']} (max level = {$max_depth})."];
        }

        $slug = $this->slugify($title);

        // Build full_path: parent.full_path + '/' + slug
        $full_path = rtrim($parent['full_path'], '/') . '/' . $slug;

        // Ensure unique full_path
        $full_path = $this->make_unique_full_path($full_path);

        // Status rules:
        // - admin creates => published
        // - tutor/instructor creates => pending (admin will approve later)
        $is_modern_book = strpos((string)$root_key, 'book_') === 0;
        $status = ($is_admin && !$is_modern_book) ? 'published' : ($is_admin ? 'draft' : 'pending');
        $content_type = 'legacy';
        if ($is_modern_book) {
            $content_type = ($parent['content_type'] ?? '') === 'book' ? 'chapter' : 'page';
        }

        $data = [
            'parent_id'   => (int)$parent_id,
            'title'       => $title,
            'slug'        => $slug,
            'full_path'   => $full_path,
            'level'       => $new_level,
            'sort_order'  => 0,
            'is_root'     => 0,
            'root_key'    => $root_key,
            'max_depth'   => $max_depth,
            'content_type'=> $content_type,
            'status'      => $status,
            'created_by'  => (int)$user_id,
            'created_at'  => date('Y-m-d H:i:s')
        ];

        if ($status === 'published') {
            $data['approved_by'] = (int)$user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert('content_nodes', $data);

        $message = $status === 'published'
            ? 'Node created and published.'
            : ($status === 'draft' ? 'Draft book item created.' : 'Node created and sent for admin approval.');
        return ['ok' => true, 'message' => $message];
    }

    // -------- HELPERS --------
    private function make_unique_full_path($full_path)
    {
        $base = $full_path;
        $i = 2;
        while (true) {
            $exists = $this->db->get_where('content_nodes', ['full_path' => $full_path])->row_array();
            if (!$exists) break;
            $full_path = $base . '-' . $i;
            $i++;
        }
        return $full_path;
    }

    public function slugify($text)
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'node';
    }
	
	// -------- PHASE 1 SCHEMA + REVIEW METADATA HELPERS --------
public function ensure_review_columns()
{
    // Safe schema upgrader for old local/prod databases.
    if (!$this->db->field_exists('content_type', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN content_type ENUM('legacy','book','chapter','page','article') NOT NULL DEFAULT 'legacy' AFTER max_depth");
    }
    if (!$this->db->field_exists('review_note', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN review_note TEXT NULL AFTER status");
    }
    if (!$this->db->field_exists('admin_remark', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN admin_remark TEXT NULL AFTER review_note");
    }
    if (!$this->db->field_exists('review_status', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN review_status ENUM('draft','in_review','approved','rejected','on_hold','update_required','published','deleted') NOT NULL DEFAULT 'draft' AFTER admin_remark");
    }
    if (!$this->db->field_exists('reviewed_by', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN reviewed_by INT NULL AFTER approved_by");
    }
    if (!$this->db->field_exists('reviewed_at', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN reviewed_at DATETIME NULL AFTER approved_at");
    }
    if (!$this->db->field_exists('updated_by', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN updated_by INT NULL AFTER created_by");
    }
    if (!$this->db->field_exists('deleted_by', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN deleted_by INT NULL AFTER updated_at");
    }
    if (!$this->db->field_exists('deleted_at', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN deleted_at DATETIME NULL AFTER deleted_by");
    }
    if (!$this->db->field_exists('is_deleted', 'content_nodes')) {
        @$this->db->query("ALTER TABLE content_nodes ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at");
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
    if (!$this->db->field_exists('created_by', 'content_node_pages')) {
        @$this->db->query("ALTER TABLE content_node_pages ADD COLUMN created_by INT NULL AFTER node_id");
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

    @$this->db->query("ALTER TABLE content_nodes MODIFY status ENUM('draft','pending','published','rejected','on_hold','update_required','deleted') NOT NULL DEFAULT 'draft'");
    @$this->db->query("ALTER TABLE content_node_pages MODIFY status ENUM('draft','pending','published','rejected','on_hold','update_required','deleted') NOT NULL DEFAULT 'draft'");

    @$this->db->query("UPDATE content_nodes SET content_type = 'book' WHERE root_key LIKE 'book\\_%' AND parent_id IS NULL");
    @$this->db->query("UPDATE content_nodes SET content_type = 'chapter' WHERE root_key LIKE 'book\\_%' AND parent_id IS NOT NULL AND level = 3");
    @$this->db->query("UPDATE content_nodes SET content_type = 'page' WHERE root_key LIKE 'book\\_%' AND parent_id IS NOT NULL AND level >= 4");
    @$this->db->query("UPDATE content_nodes SET content_type = 'article' WHERE root_key LIKE 'article\\_%' AND parent_id IS NULL");
    @$this->db->query("UPDATE content_nodes SET review_status = CASE status WHEN 'pending' THEN 'in_review' WHEN 'published' THEN 'published' WHEN 'rejected' THEN 'rejected' WHEN 'on_hold' THEN 'on_hold' WHEN 'update_required' THEN 'update_required' WHEN 'deleted' THEN 'deleted' ELSE 'draft' END WHERE review_status IS NULL OR review_status = ''");
    @$this->db->query("UPDATE content_node_pages SET review_status = CASE status WHEN 'pending' THEN 'in_review' WHEN 'published' THEN 'published' WHEN 'rejected' THEN 'rejected' WHEN 'on_hold' THEN 'on_hold' WHEN 'update_required' THEN 'update_required' WHEN 'deleted' THEN 'deleted' ELSE 'draft' END WHERE review_status IS NULL OR review_status = ''");
}

// -------- PENDING LIST --------
public function get_pending_nodes()
{
    $this->ensure_review_columns();
    $this->db->where('COALESCE(is_deleted,0)', 0, false);
    $this->db->where_in('status', ['pending', 'on_hold', 'update_required', 'rejected']);
    $this->db->group_start();
    $this->db->where('parent_id IS NULL', null, false);
    $this->db->or_group_start();
    $this->db->not_like('root_key', 'book_', 'after');
    $this->db->not_like('root_key', 'article_', 'after');
    $this->db->group_end();
    $this->db->group_end();
    $this->db->order_by('updated_at', 'DESC');
    $this->db->order_by('created_at', 'DESC');
    return $this->db->get('content_nodes')->result_array();
}

public function get_deleted_content()
{
    $this->ensure_review_columns();
    $this->db->where('COALESCE(is_deleted,0)', 1, false);
    $this->db->group_start();
    $this->db->where('parent_id IS NULL', null, false);
    $this->db->or_where('content_type', 'article');
    $this->db->group_end();
    $this->db->order_by('deleted_at', 'DESC');
    return $this->db->get('content_nodes')->result_array();
}

public function get_published_content()
{
    $this->ensure_review_columns();
    $this->db->where('COALESCE(is_deleted,0)', 0, false);
    $this->db->where('status', 'published');
    $this->db->group_start();
    $this->db->where('parent_id IS NULL', null, false);
    $this->db->or_where('content_type', 'article');
    $this->db->group_end();
    $this->db->order_by('approved_at', 'DESC');
    return $this->db->get('content_nodes')->result_array();
}

// -------- APPROVE / REJECT --------
public function approve_node($node_id, $admin_id, $note = '')
{
    return $this->review_node($node_id, 'published', $admin_id, $note);
}

public function reject_node($node_id, $admin_id, $note = '')
{
    return $this->review_node($node_id, 'rejected', $admin_id, $note);
}

public function review_node($node_id, $status, $admin_id, $note = '')
{
    $this->ensure_review_columns();
    $allowed = ['published', 'rejected', 'on_hold', 'update_required'];
    if (!in_array($status, $allowed, true)) return ['ok'=>false, 'message'=>'Invalid review action'];
    $node = $this->db->get_where('content_nodes', ['node_id'=>(int)$node_id])->row_array();
    if (!$node) return ['ok'=>false, 'message'=>'Content not found'];
    $now = date('Y-m-d H:i:s');
    $root_key = (string)$node['root_key'];
    $isModern = (strpos($root_key, 'book_') === 0 || strpos($root_key, 'article_') === 0);
    $reviewStatus = ($status === 'published') ? 'published' : $status;
    $data = [
        'status'=>$status,
        'review_status'=>$reviewStatus,
        'review_note'=>(string)$note,
        'admin_remark'=>(string)$note,
        'reviewed_by'=>(int)$admin_id,
        'reviewed_at'=>$now,
        'updated_by'=>(int)$admin_id,
        'updated_at'=>$now
    ];
    if ($status === 'published') { $data['approved_by']=(int)$admin_id; $data['approved_at']=$now; }
    $this->db->trans_start();
    if ($isModern) {
        $this->db->where('root_key', $root_key)->update('content_nodes', $data);
        $ids = $this->db->select('node_id')->where('root_key', $root_key)->get('content_nodes')->result_array();
        foreach ($ids as $r) {
            $pd = ['status'=>$status, 'review_status'=>$reviewStatus, 'review_note'=>(string)$note, 'admin_remark'=>(string)$note, 'updated_at'=>$now];
            if ($status === 'published') { $pd['approved_by']=(int)$admin_id; $pd['approved_at']=$now; }
            $this->db->where('node_id', (int)$r['node_id'])->update('content_node_pages', $pd);
        }
    } else {
        $this->db->where('node_id', (int)$node_id)->update('content_nodes', $data);
    }
    $this->load->model('Immutable_audit_model', 'immutable_audit');
    $this->immutable_audit->record('publishing', $status === 'published' ? 'content_published' : 'content_reviewed', 'content_node', (int)$node_id, $node, array_merge($node, $data), [
        'actor_user_id' => (int)$admin_id,
        'actor_role' => 'admin',
        'root_key' => $root_key,
        'review_note' => (string)$note,
    ]);
    $this->db->trans_complete();
    if (!$this->db->trans_status()) return ['ok'=>false, 'message'=>'Review update failed'];
    $label = ['published'=>'approved and published', 'rejected'=>'rejected', 'on_hold'=>'put on hold', 'update_required'=>'sent back for update'][$status];
    return ['ok'=>true, 'message'=>'Content '.$label.'.'];
}

public function restore_content($node_id, $admin_id)
{
    $this->ensure_review_columns();
    $node = $this->db->get_where('content_nodes', ['node_id'=>(int)$node_id])->row_array();
    if (!$node) return ['ok'=>false, 'message'=>'Content not found'];
    $root_key = (string)$node['root_key'];
    $now = date('Y-m-d H:i:s');
    $this->db->trans_start();
    if (strpos($root_key, 'book_') === 0 || strpos($root_key, 'article_') === 0) {
        $this->db->where('root_key', $root_key)->update('content_nodes', [
            'is_deleted'=>0, 'deleted_by'=>null, 'deleted_at'=>null, 'status'=>'draft', 'review_status'=>'draft', 'updated_by'=>(int)$admin_id, 'updated_at'=>$now
        ]);
        $ids = $this->db->select('node_id')->where('root_key', $root_key)->get('content_nodes')->result_array();
        foreach ($ids as $r) {
            $this->db->where('node_id', (int)$r['node_id'])->update('content_node_pages', [
                'is_deleted'=>0, 'deleted_by'=>null, 'deleted_at'=>null, 'status'=>'draft', 'review_status'=>'draft', 'updated_at'=>$now
            ]);
        }
    } else {
        $this->db->where('node_id', (int)$node_id)->update('content_nodes', [
            'is_deleted'=>0, 'deleted_by'=>null, 'deleted_at'=>null, 'status'=>'draft', 'review_status'=>'draft', 'updated_by'=>(int)$admin_id, 'updated_at'=>$now
        ]);
        $this->db->where('node_id', (int)$node_id)->update('content_node_pages', [
            'is_deleted'=>0, 'deleted_by'=>null, 'deleted_at'=>null, 'status'=>'draft', 'review_status'=>'draft', 'updated_at'=>$now
        ]);
    }
    $this->db->trans_complete();
    return $this->db->trans_status() ? ['ok'=>true,'message'=>'Content restored as draft.'] : ['ok'=>false,'message'=>'Restore failed.'];
}

public function permanently_delete_content($node_id)
{
    $this->ensure_review_columns();
    $node = $this->db->get_where('content_nodes', ['node_id'=>(int)$node_id])->row_array();
    if (!$node) return ['ok'=>false, 'message'=>'Content not found'];
    $root_key = (string)$node['root_key'];
    $this->db->trans_start();
    if (strpos($root_key, 'book_') === 0 || strpos($root_key, 'article_') === 0) {
        $ids = $this->db->select('node_id')->where('root_key', $root_key)->get('content_nodes')->result_array();
        foreach ($ids as $r) $this->db->where('node_id', (int)$r['node_id'])->delete('content_node_pages');
        $this->db->where('root_key', $root_key)->delete('content_nodes');
    } else {
        $this->db->where('node_id', (int)$node_id)->delete('content_node_pages');
        $this->db->where('node_id', (int)$node_id)->delete('content_nodes');
    }
    $this->db->trans_complete();
    return $this->db->trans_status() ? ['ok'=>true,'message'=>'Content permanently deleted.'] : ['ok'=>false,'message'=>'Permanent delete failed.'];
}

    // -------- ADD ROOT RULE (ADMIN ONLY) --------
		// -------- ADD ROOT RULE (Admin only UI will call this) --------
		public function add_root_rule($root_key, $root_title, $max_depth)
		{
			$root_key   = strtolower(trim($root_key));
			$root_key   = preg_replace('/[^a-z0-9_-]/', '', $root_key);  // keep safe
			$root_title = trim($root_title);
			$max_depth  = (int)$max_depth;

			if ($root_key === '' || $root_title === '' || $max_depth < 1) {
				return ['ok' => false, 'message' => 'Invalid root rule input.'];
			}

			// Insert or ignore (needs UNIQUE KEY on root_key)
			$sql = "INSERT IGNORE INTO content_node_rules (root_key, root_title, max_depth)
					VALUES (?, ?, ?)";
			$this->db->query($sql, [$root_key, $root_title, $max_depth]);

			// If already exists, tell user
			if ($this->db->affected_rows() == 0) {
				return ['ok' => false, 'message' => 'Root tree already exists (root_key is duplicate).'];
			}

			return ['ok' => true, 'message' => 'Root tree rule added successfully.'];
		}


public function update_node_basic($node_id, $title, $sort_order, $updated_by)
{
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) return ['ok' => false, 'message' => 'Node not found.'];

    // Prevent editing root key/structure here
    $title = trim($title);
    if ($title === '') return ['ok' => false, 'message' => 'Title required.'];

    $this->db->where('node_id', (int)$node_id)->update('content_nodes', [
        'title'      => $title,
        'sort_order' => (int)$sort_order,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    return ['ok' => true, 'message' => 'Node updated (title only).'];
}

public function soft_delete_node_recursive($node_id, $updated_by)
{
    $this->ensure_review_columns();
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) return ['ok' => false, 'message' => 'Node not found.'];
    $now = date('Y-m-d H:i:s');
    $this->db->where('node_id', (int)$node_id)->update('content_nodes', [
        'status'=>'deleted', 'review_status'=>'deleted', 'is_deleted'=>1, 'deleted_by'=>(int)$updated_by, 'deleted_at'=>$now, 'updated_by'=>(int)$updated_by, 'updated_at'=>$now
    ]);
    $this->db->where('node_id', (int)$node_id)->update('content_node_pages', [
        'status'=>'deleted', 'review_status'=>'deleted', 'is_deleted'=>1, 'deleted_by'=>(int)$updated_by, 'deleted_at'=>$now, 'updated_at'=>$now
    ]);
    $children = $this->db->get_where('content_nodes', ['parent_id' => (int)$node_id])->result_array();
    foreach ($children as $c) $this->soft_delete_node_recursive((int)$c['node_id'], (int)$updated_by);
    return ['ok' => true, 'message' => 'Content moved to recycle bin.'];
}

public function delete_node($user_id, $user_role, $node_id)
{
    $this->ensure_review_columns();
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) return ['ok' => false, 'message' => 'Node not found.'];
    $isAdmin = (strtolower((string)$user_role) === 'admin' || (int)$this->session->userdata('role_id') === 1 || $this->session->userdata('admin_login') == true);
    $isOwner = ((int)($node['created_by'] ?? 0) === (int)$user_id);
    $isDefaultRoot = ((int)($node['is_root'] ?? 0) === 1 && strpos((string)$node['root_key'], 'book_') !== 0 && strpos((string)$node['root_key'], 'article_') !== 0);
    if (!$isAdmin && !$isOwner) return ['ok'=>false, 'message'=>'You can delete only your own content.'];
    if (!$isAdmin && $isDefaultRoot) return ['ok'=>false, 'message'=>'Root nodes can be deleted only by admin.'];
    return $this->soft_delete_node_recursive((int)$node_id, (int)$user_id);
}

public function delete_node_safe($node_id, $force = false)
{
    $admin_id = (int)$this->session->userdata('user_id');
    return $this->delete_node($admin_id, 'admin', (int)$node_id);
}

// ================================
// Content Page (Whiteboard) Logic
// ================================

public function get_by_node($node_id) {
    return $this->db->get_where('content_node_pages', ['node_id' => $node_id])->row_array();
}

public function save_page($node_id, $data, $user_id, $role) {
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node || (!$this->is_admin_role($role) && (int)($node['created_by'] ?? 0) !== (int)$user_id)) {
        return ['ok' => false, 'message' => 'Content not found or access denied.'];
    }

    $existing = $this->get_by_node($node_id);

    $status = ($role == 'admin') ? 'published' : 'pending';

    $save = [
        'node_id' => $node_id,
        'html' => $data['html'],
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'meta_keywords' => $data['meta_keywords'],
        'canonical_url' => $data['canonical_url'],
        'og_image' => $data['og_image'],
        'status' => $status,
        'updated_by' => $user_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($status == 'published') {
        $save['approved_by'] = $user_id;
        $save['approved_at'] = date('Y-m-d H:i:s');
    }

    if ($existing) {
        $this->db->where('node_id', $node_id)->update('content_node_pages', $save);
    } else {
        $save['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('content_node_pages', $save);
    }
    return ['ok' => true, 'message' => 'Content saved.'];
}

// Frontend: only published nodes
public function get_published_nodes() {
    $this->db->where('status', 'published');
    $this->db->order_by('root_key', 'ASC');
    $this->db->order_by('sort_order', 'ASC');
    $this->db->order_by('title', 'ASC');
    return $this->db->get('content_nodes')->result_array();
}

// Find node by full_path (e.g. gcp/data-engineer/bigquery/introduction)
public function get_node_by_full_path($full_path) {
    return $this->db->get_where('content_nodes', [
        'full_path' => $full_path,
        'status' => 'published'
    ])->row_array();
}

// Fetch published page content for node
public function get_published_page_by_node($node_id) {
    return $this->db->get_where('content_node_pages', [
        'node_id' => $node_id,
        'status' => 'published'
    ])->row_array();
}


public function update_node_title($user_id, $user_role, $node_id, $title)
{
    $node_id = (int)$node_id;
    $title   = trim((string)$title);

    if ($node_id <= 0 || $title === '') {
        return ['ok' => false, 'message' => 'Invalid node id or title'];
    }

    $node = $this->db->get_where('content_nodes', ['node_id' => $node_id])->row_array();
    if (!$node) {
        return ['ok' => false, 'message' => 'Node not found'];
    }
    if (!$this->is_admin_role($user_role) && (int)($node['created_by'] ?? 0) !== (int)$user_id) {
        return ['ok' => false, 'message' => 'You can edit only your own content.'];
    }
    if (!$this->is_admin_role($user_role) && (int)$node['is_root'] === 1) {
        return ['ok' => false, 'message' => 'Root nodes can be edited only by admin.'];
    }

    // Root / modern book / article node: parent_id can be NULL, so bypass parent validation.
    if ((int)$node['is_root'] === 1 || empty($node['parent_id'])) {
        $slug = $this->slugify($title);
        $this->db->where('node_id', $node_id)->update('content_nodes', [
            'title'      => $title,
            'slug'       => $slug,
            'updated_by' => (int)$user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'message' => 'Name updated'];
    }

    // ---- For non-root: update title + slug + full_path + descendant full_paths ----
    $slug = $this->slugify($title);

    // parent full_path
    $parent = $this->db->get_where('content_nodes', ['node_id' => (int)$node['parent_id']])->row_array();
    if (!$parent) {
        return ['ok' => false, 'message' => 'Parent node not found'];
    }

    $old_full_path = (string)$node['full_path'];
    $new_full_path = rtrim($parent['full_path'], '/') . '/' . $slug;

    // ensure unique full_path
    $new_full_path = $this->make_unique_full_path($new_full_path);

    $this->db->trans_start();

    // Update this node
    $this->db->where('node_id', $node_id)->update('content_nodes', [
        'title'      => $title,
        'slug'       => $slug,
        'full_path'  => $new_full_path,
        'updated_by' => (int)$user_id,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    // Update descendant paths: replace old prefix with new prefix
    $old_prefix = rtrim($old_full_path, '/');
    $new_prefix = rtrim($new_full_path, '/');

    $desc = $this->db->select('node_id, full_path')
                     ->like('full_path', $old_prefix . '/', 'after')
                     ->get('content_nodes')
                     ->result_array();

    foreach ($desc as $d) {
        $child_old = $d['full_path'];
        $child_new = $new_prefix . substr($child_old, strlen($old_prefix));
        $this->db->where('node_id', (int)$d['node_id'])->update('content_nodes', [
            'full_path'  => $child_new,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
        return ['ok' => false, 'message' => 'Update failed'];
    }

    return ['ok' => true, 'message' => 'Node updated'];
}



	
public function update_node_order($parent_id, $ordered_ids, $user_id = 0, $user_role = 'admin')
{
    $parent_id = (int)$parent_id;

    if (!is_array($ordered_ids) || empty($ordered_ids)) {
        return ['ok' => false, 'message' => 'Empty order'];
    }
    $is_admin = $this->is_admin_role($user_role);
    $parent = $this->db->get_where('content_nodes', ['node_id' => $parent_id])->row_array();
    if (!$is_admin && (!$parent || (int)($parent['created_by'] ?? 0) !== (int)$user_id)) {
        return ['ok' => false, 'message' => 'You can reorder only your own content.'];
    }
    if (!$is_admin) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ordered_ids))));
        $owned_count = empty($ids) ? 0 : (int)$this->db->where('parent_id', $parent_id)
            ->where('created_by', (int)$user_id)
            ->where_in('node_id', $ids)
            ->count_all_results('content_nodes');
        if ($owned_count !== count($ids)) {
            return ['ok' => false, 'message' => 'One or more content items are not owned by you.'];
        }
    }

    $this->db->trans_start();

    $pos = 1;
    foreach ($ordered_ids as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;

        // Update only siblings under this parent
        $this->db->where('node_id', $id)
                 ->where('parent_id', $parent_id)
                 ->update('content_nodes', ['sort_order' => $pos]);

        $pos++;
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
        return ['ok' => false, 'message' => 'DB update failed'];
    }

    return ['ok' => true];
}


    /**
     * Home page: Fetch latest published content pages (docs/blog posts).
     * Returns rows with node + page + author + root title.
     *
     * NOTE:
     * - Uses page.status='published' and node.status='published'
     * - Orders by published time (approved_at/updated_at/created_at fallback)
     */
    public function get_latest_published_pages($limit = 3)
    {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 3;
        }

        // Join:
        // content_nodes (n) -> content_node_pages (p) -> users (u) -> content_node_rules (r)
        $this->db->select("
            n.node_id,
            n.title AS node_title,
            n.full_path,
            n.root_key,
            n.created_by,
            n.created_at,
            n.approved_by AS node_approved_by,
            n.approved_at AS node_approved_at,

            p.meta_title,
            p.meta_description,
            p.og_image,
            p.updated_at,
            p.approved_by AS page_approved_by,
            p.approved_at AS page_approved_at,
            p.html,

            r.root_title,

            u.id AS author_id,
            u.first_name AS author_first_name,
            u.last_name AS author_last_name
        ", false);

        $this->db->from('content_nodes n');
        $this->db->join('content_node_pages p', 'p.node_id = n.node_id AND p.status = "published"', 'inner');
        $this->db->join('content_node_rules r', 'r.root_key = n.root_key', 'left');

        // Pick author from page approval if possible, otherwise node approval/creation
        $this->db->join('users u', 'u.id = COALESCE(p.approved_by, n.approved_by, n.created_by)', 'left', false);

        $this->db->where('n.status', 'published');
        $this->db->order_by('COALESCE(p.approved_at, p.updated_at, n.approved_at, n.created_at)', 'DESC', false);
        $this->db->limit($limit);

        $rows = $this->db->get()->result_array();

        // Add derived fields: excerpt + published_at
        foreach ($rows as &$row) {
            $row['published_at'] = $row['page_approved_at'] ?: ($row['updated_at'] ?: ($row['node_approved_at'] ?: $row['created_at']));
            $row['excerpt'] = $this->make_excerpt($row['meta_description'] ?: $row['html'], 120);

            if (empty($row['root_title'])) {
                $row['root_title'] = strtoupper((string)$row['root_key']);
            }
            $row['author_name'] = trim(($row['author_first_name'] ?? '') . ' ' . ($row['author_last_name'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    /**
     * Create a short excerpt from HTML/text.
     */
    private function make_excerpt($html_or_text, $max_chars = 140)
    {
        //$text = strip_tags((string)$html_or_text);
		$text = html_entity_decode(strip_tags((string)$html_or_text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $max_chars = (int)$max_chars;
        if ($max_chars <= 0) $max_chars = 140;

        if (mb_strlen($text) <= $max_chars) {
            return $text;
        }
        return mb_substr($text, 0, $max_chars - 1) . '…';
    }




    // -------- MODERN TUTOR BOOK / ARTICLE WORKFLOW --------
    public function get_tutor_books($user_id)
    {
        $this->ensure_review_columns();
        $this->db->where('created_by', (int)$user_id);
        $this->db->where('parent_id IS NULL', null, false);
        $this->db->where('COALESCE(is_deleted,0)', 0, false);
        $this->db->like('root_key', 'book_' . (int)$user_id . '_', 'after');
        $this->db->order_by('updated_at', 'DESC');
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('content_nodes')->result_array();
    }

    public function get_tutor_articles($user_id)
    {
        $this->ensure_review_columns();

        // Root cause fix: articles are owned and approved at the content_nodes level.
        // The editor row in content_node_pages can be missing for a newly-created
        // article or can temporarily have an older status. Therefore listing must
        // use n.status as the authoritative workflow status, while page fields are
        // loaded only for editor/metadata display.
        $this->db->select('n.*, p.page_id, p.updated_at AS page_updated_at, p.status AS page_status, p.meta_description, p.meta_title, p.html');
        $this->db->from('content_nodes n');
        $this->db->join('content_node_pages p', 'p.node_id = n.node_id AND COALESCE(p.is_deleted,0) = 0', 'left', false);
        $this->db->where('n.created_by', (int)$user_id);
        $this->db->where('n.parent_id IS NULL', null, false);
        $this->db->where('COALESCE(n.is_deleted,0)', 0, false);
        $this->db->like('n.root_key', 'article_' . (int)$user_id . '_', 'after');
        $this->db->order_by('COALESCE(p.updated_at, n.updated_at, n.created_at)', 'DESC', false);
        return $this->db->get()->result_array();
    }

    public function get_tutor_book_tree($user_id)
    {
        $this->ensure_review_columns();
        $this->db->where('created_by', (int)$user_id);
        $this->db->where('COALESCE(is_deleted,0)', 0, false);
        $this->db->like('root_key', 'book_' . (int)$user_id . '_', 'after');
        $this->db->order_by('root_key', 'ASC');
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('node_id', 'ASC');
        return $this->db->get('content_nodes')->result_array();
    }

    public function create_book_wizard($user_id, $book_title, $chapter_title = 'Chapter 1', $page_title = 'Introduction')
    {
        $this->ensure_review_columns();
        $book_title = trim((string)$book_title);
        if ($book_title === '') return ['ok' => false, 'message' => 'Book title is required'];
        $chapter_title = trim((string)$chapter_title) ?: 'Chapter 1';
        $page_title = trim((string)$page_title) ?: 'Introduction';
        $now = date('Y-m-d H:i:s');
        $book_slug = $this->slugify($book_title);
        $root_key = 'book_' . (int)$user_id . '_' . $book_slug;
        $i = 2;
        while ($this->db->get_where('content_nodes', ['root_key' => $root_key])->row_array()) {
            $root_key = 'book_' . (int)$user_id . '_' . $book_slug . '_' . $i++;
        }
        $this->db->trans_start();
        $book_path = $this->make_unique_full_path('books/' . $book_slug);
        $this->db->insert('content_nodes', [
            'parent_id' => null, 'title' => $book_title, 'slug' => $book_slug,
            'full_path' => $book_path, 'level' => 2, 'sort_order' => 0,
            'is_root' => 0, 'root_key' => $root_key, 'max_depth' => 5, 'content_type' => 'book',
            'status' => 'draft', 'review_status' => 'draft', 'created_by' => (int)$user_id, 'updated_by' => (int)$user_id, 'created_at' => $now, 'updated_at' => $now
        ]);
        $book_id = (int)$this->db->insert_id();
        $chapter_slug = $this->slugify($chapter_title);
        $chapter_path = $this->make_unique_full_path($book_path . '/' . $chapter_slug);
        $this->db->insert('content_nodes', [
            'parent_id' => $book_id, 'title' => $chapter_title, 'slug' => $chapter_slug,
            'full_path' => $chapter_path, 'level' => 3, 'sort_order' => 1,
            'is_root' => 0, 'root_key' => $root_key, 'max_depth' => 5, 'content_type' => 'chapter',
            'status' => 'draft', 'review_status' => 'draft', 'created_by' => (int)$user_id, 'updated_by' => (int)$user_id, 'created_at' => $now, 'updated_at' => $now
        ]);
        $chapter_id = (int)$this->db->insert_id();
        $page_slug = $this->slugify($page_title);
        $page_path = $this->make_unique_full_path($chapter_path . '/' . $page_slug);
        $this->db->insert('content_nodes', [
            'parent_id' => $chapter_id, 'title' => $page_title, 'slug' => $page_slug,
            'full_path' => $page_path, 'level' => 4, 'sort_order' => 1,
            'is_root' => 0, 'root_key' => $root_key, 'max_depth' => 5, 'content_type' => 'page',
            'status' => 'draft', 'review_status' => 'draft', 'created_by' => (int)$user_id, 'updated_by' => (int)$user_id, 'created_at' => $now, 'updated_at' => $now
        ]);
        $page_id = (int)$this->db->insert_id();
        $this->db->insert('content_node_pages', [
            'node_id' => $page_id,
            'created_by' => (int)$user_id,
            'updated_by' => (int)$user_id,
            'html' => '',
            'meta_title' => $page_title,
            'status' => 'draft',
            'review_status' => 'draft',
            'is_deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            $error = $this->db->error();
            $message = !empty($error['message']) ? $error['message'] : 'Book creation failed';
            log_message('error', 'Tutor book creation failed user_id=' . (int)$user_id . ' error=' . $message);
            return ['ok'=>false,'message'=>$message];
        }
        return ['ok'=>true,'message'=>'Book, chapter, and first page created. You can now write the first page.'];
    }

    public function create_article($user_id, $article_title)
    {
        $this->ensure_review_columns();
        $article_title = trim((string)$article_title);
        if ($article_title === '') return ['ok' => false, 'message' => 'Article title is required'];
        $now = date('Y-m-d H:i:s');
        $slug = $this->slugify($article_title);
        $root_key = 'article_' . (int)$user_id . '_' . $slug;
        $i = 2;
        while ($this->db->get_where('content_nodes', ['root_key' => $root_key])->row_array()) {
            $root_key = 'article_' . (int)$user_id . '_' . $slug . '_' . $i++;
        }
        $this->db->trans_start();
        $this->db->insert('content_nodes', [
            'parent_id' => null, 'title' => $article_title, 'slug' => $slug,
            'full_path' => $this->make_unique_full_path('articles/' . $slug), 'level' => 4, 'sort_order' => 0,
            'is_root' => 0, 'root_key' => $root_key, 'max_depth' => 4, 'content_type' => 'article',
            'status' => 'draft', 'review_status' => 'draft', 'created_by' => (int)$user_id, 'updated_by' => (int)$user_id, 'created_at' => $now, 'updated_at' => $now
        ]);
        $article_id = (int)$this->db->insert_id();

        // Create the draft editor row immediately. This fixes the issue where a
        // created article did not reopen/list consistently because the node existed
        // but the page/editor row was not created until the first save.
        $this->db->insert('content_node_pages', [
            'node_id' => $article_id,
            'created_by' => (int)$user_id,
            'updated_by' => (int)$user_id,
            'html' => '',
            'meta_title' => $article_title,
            'status' => 'draft',
            'review_status' => 'draft',
            'is_deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        $this->db->trans_complete();

        return $this->db->trans_status() ? ['ok' => true, 'message' => 'Article created. Open it and start writing.'] : ['ok' => false, 'message' => 'Article creation failed.'];
    }

    public function import_book($user_id, $title, array $chapters, $reader_mode = 'interactive', $source_file = null, $source_mime = null)
    {
        $this->ensure_review_columns();
        $title = trim((string)$title);
        if ($title === '') return ['ok'=>false, 'message'=>'Book title is required.'];
        if (empty($chapters) && $reader_mode !== 'pdf') return ['ok'=>false, 'message'=>'No chapters or pages were detected.'];
        $now = date('Y-m-d H:i:s');
        $slug = $this->slugify($title);
        $rootKey = 'book_' . (int)$user_id . '_' . $slug;
        $suffix = 2;
        while ($this->db->get_where('content_nodes', ['root_key'=>$rootKey])->row_array()) $rootKey = 'book_' . (int)$user_id . '_' . $slug . '_' . $suffix++;

        $this->db->trans_start();
        $book = [
            'parent_id'=>null, 'title'=>$title, 'slug'=>$slug, 'full_path'=>$this->make_unique_full_path('books/'.$slug),
            'level'=>2, 'sort_order'=>0, 'is_root'=>0, 'root_key'=>$rootKey, 'max_depth'=>5, 'content_type'=>'book',
            'reader_mode'=>$reader_mode, 'source_file'=>$source_file, 'source_mime'=>$source_mime,
            'status'=>'draft', 'review_status'=>'draft', 'created_by'=>(int)$user_id, 'updated_by'=>(int)$user_id,
            'created_at'=>$now, 'updated_at'=>$now
        ];
        $this->db->insert('content_nodes', $book);
        $bookId = (int)$this->db->insert_id();

        foreach ($chapters as $chapterIndex=>$chapter) {
            $chapterTitle = trim((string)($chapter['title'] ?? 'Chapter '.($chapterIndex+1))) ?: 'Chapter '.($chapterIndex+1);
            $chapterSlug = $this->slugify($chapterTitle);
            $chapterPath = $book['full_path'].'/'.$chapterSlug;
            $this->db->insert('content_nodes', [
                'parent_id'=>$bookId, 'title'=>$chapterTitle, 'slug'=>$chapterSlug, 'full_path'=>$this->make_unique_full_path($chapterPath),
                'level'=>3, 'sort_order'=>$chapterIndex+1, 'is_root'=>0, 'root_key'=>$rootKey, 'max_depth'=>5,
                'content_type'=>'chapter', 'status'=>'draft', 'review_status'=>'draft', 'created_by'=>(int)$user_id,
                'updated_by'=>(int)$user_id, 'created_at'=>$now, 'updated_at'=>$now
            ]);
            $chapterId = (int)$this->db->insert_id();
            foreach (($chapter['pages'] ?? []) as $pageIndex=>$page) {
                $pageTitle = trim((string)($page['title'] ?? 'Page '.($pageIndex+1))) ?: 'Page '.($pageIndex+1);
                $pageSlug = $this->slugify($pageTitle);
                $this->db->insert('content_nodes', [
                    'parent_id'=>$chapterId, 'title'=>$pageTitle, 'slug'=>$pageSlug,
                    'full_path'=>$this->make_unique_full_path($chapterPath.'/'.$pageSlug), 'level'=>4, 'sort_order'=>$pageIndex+1,
                    'is_root'=>0, 'root_key'=>$rootKey, 'max_depth'=>5, 'content_type'=>'page', 'status'=>'draft',
                    'review_status'=>'draft', 'created_by'=>(int)$user_id, 'updated_by'=>(int)$user_id, 'created_at'=>$now, 'updated_at'=>$now
                ]);
                $pageId = (int)$this->db->insert_id();
                $html = (string)($page['html'] ?? '');
                $this->db->insert('content_node_pages', [
                    'node_id'=>$pageId, 'created_by'=>(int)$user_id, 'updated_by'=>(int)$user_id, 'html'=>$html,
                    'meta_title'=>$pageTitle, 'meta_description'=>$this->make_excerpt($html, 155), 'status'=>'draft',
                    'review_status'=>'draft', 'is_deleted'=>0, 'created_at'=>$now, 'updated_at'=>$now
                ]);
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status()
            ? ['ok'=>true, 'message'=>'Book imported as a draft. Review it, then publish.', 'book_id'=>$bookId]
            : ['ok'=>false, 'message'=>'Book import failed.'];
    }

    public function set_book_status($bookId, $adminId, $status)
    {
        $this->ensure_review_columns();
        if (!in_array($status, ['published','pending','draft','deleted'], true)) return ['ok'=>false,'message'=>'Invalid publishing status.'];
        $content = $this->db->where_in('content_type', ['book', 'article'])
            ->get_where('content_nodes', ['node_id'=>(int)$bookId], 1)
            ->row_array();
        if (!$content) return ['ok'=>false,'message'=>'Book or article not found.'];
        $rootKey = $content['root_key'];
        $now = date('Y-m-d H:i:s');
        $reviewStatus = $status === 'published' ? 'published' : ($status === 'pending' ? 'in_review' : ($status === 'deleted' ? 'deleted' : 'draft'));
        $nodeData = ['status'=>$status, 'review_status'=>$reviewStatus, 'updated_by'=>(int)$adminId, 'updated_at'=>$now];
        if ($status === 'published') { $nodeData['approved_by']=(int)$adminId; $nodeData['approved_at']=$now; }
        if ($status === 'deleted') { $nodeData['is_deleted']=1; $nodeData['deleted_by']=(int)$adminId; $nodeData['deleted_at']=$now; }
        $this->db->trans_start();
        $this->db->where('root_key', $rootKey)->update('content_nodes', $nodeData);
        $ids = $this->db->select('node_id')->where('root_key', $rootKey)->get('content_nodes')->result_array();
        foreach ($ids as $row) {
            $pageData = ['status'=>$status, 'review_status'=>$nodeData['review_status'], 'updated_by'=>(int)$adminId, 'updated_at'=>$now];
            if ($status === 'published') { $pageData['approved_by']=(int)$adminId; $pageData['approved_at']=$now; }
            if ($status === 'deleted') { $pageData['is_deleted']=1; $pageData['deleted_by']=(int)$adminId; $pageData['deleted_at']=$now; }
            $this->db->where('node_id', (int)$row['node_id'])->update('content_node_pages', $pageData);
        }
        $this->db->trans_complete();
        $label = ($content['content_type'] === 'article') ? 'Article' : 'Book';
        return $this->db->trans_status() ? ['ok'=>true,'message'=>$label.' status updated.'] : ['ok'=>false,'message'=>$label.' status update failed.'];
    }

    public function get_public_books($search = '')
    {
        $this->db->select('n.*, p.meta_description, p.og_image')->from('content_nodes n')
            ->join('content_node_pages p', 'p.node_id=n.node_id AND p.status="published" AND COALESCE(p.is_deleted,0)=0', 'left', false)
            ->where('n.content_type','book')->where('n.status','published')->where('COALESCE(n.is_deleted,0)',0,false);
        if (trim((string)$search) !== '') $this->db->like('n.title', trim((string)$search));
        return $this->db->order_by('n.approved_at','DESC')->get()->result_array();
    }

    public function get_public_book($slug, $includeDraft = false)
    {
        $this->db->where('content_type','book')->where('slug',(string)$slug)->where('COALESCE(is_deleted,0)',0,false);
        if (!$includeDraft) $this->db->where('status','published');
        return $this->db->get('content_nodes',1)->row_array();
    }

    public function get_public_book_tree($rootKey, $search = '', $includeDraft = false)
    {
        $this->db->select('n.*, p.html, p.meta_title, p.meta_description, p.meta_keywords')->from('content_nodes n')
            ->join('content_node_pages p', 'p.node_id=n.node_id AND COALESCE(p.is_deleted,0)=0'.($includeDraft?'':' AND p.status="published"'), 'left', false)
            ->where('n.root_key',(string)$rootKey)->where('COALESCE(n.is_deleted,0)',0,false);
        if (!$includeDraft) $this->db->where('n.status','published');
        if (trim((string)$search) !== '') {
            $term = trim((string)$search);
            $this->db->group_start()->like('n.title',$term)->or_like('p.meta_keywords',$term)->or_like('p.html',$term)->group_end();
        }
        return $this->db->order_by('n.level','ASC')->order_by('n.parent_id','ASC')->order_by('n.sort_order','ASC')->get()->result_array();
    }

    public function save_reading_state($userId, $bookId, $pageId, $percent = 0, $bookmark = null)
    {
        if ((int)$userId <= 0) return ['ok'=>false,'message'=>'Login required.'];
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->get_where('content_reading_progress', ['user_id'=>(int)$userId,'book_node_id'=>(int)$bookId], 1)->row_array();
        $data = ['user_id'=>(int)$userId,'book_node_id'=>(int)$bookId,'page_node_id'=>(int)$pageId,'progress_percent'=>max(0,min(100,(float)$percent)),'last_read_at'=>$now];
        if ($existing) $this->db->where('id',(int)$existing['id'])->update('content_reading_progress',$data); else $this->db->insert('content_reading_progress',$data);
        if ($bookmark !== null) {
            if ($bookmark) $this->db->replace('content_bookmarks',['user_id'=>(int)$userId,'book_node_id'=>(int)$bookId,'page_node_id'=>(int)$pageId,'created_at'=>$now]);
            else $this->db->where(['user_id'=>(int)$userId,'page_node_id'=>(int)$pageId])->delete('content_bookmarks');
        }
        return ['ok'=>true];
    }

    public function add_book_child($user_id, $parent_id, $title, $type = 'page')
    {
        $this->ensure_review_columns();
        $parent = $this->db->get_where('content_nodes', ['node_id'=>(int)$parent_id, 'created_by'=>(int)$user_id])->row_array();
        if (!$parent) return ['ok'=>false,'message'=>'Parent not found'];
        if (strpos($parent['root_key'], 'book_' . (int)$user_id . '_') !== 0) return ['ok'=>false,'message'=>'Only book items can be extended'];
        $title = trim((string)$title);
        if ($title === '') return ['ok'=>false,'message'=>'Title is required'];
        $level = ((string)$type === 'chapter') ? 3 : 4;
        if ((string)$type === 'chapter' && (int)$parent['level'] !== 2) return ['ok'=>false,'message'=>'Chapter can be added only under a book'];
        if ((string)$type !== 'chapter' && (int)$parent['level'] < 3) return ['ok'=>false,'message'=>'Page can be added only under a chapter'];
        $slug = $this->slugify($title);
        $count = $this->db->where('parent_id', (int)$parent_id)->count_all_results('content_nodes');
        $now = date('Y-m-d H:i:s');
        $this->db->insert('content_nodes', [
            'parent_id'=>(int)$parent_id, 'title'=>$title, 'slug'=>$slug,
            'full_path'=>$this->make_unique_full_path(rtrim($parent['full_path'], '/') . '/' . $slug),
            'level'=>$level, 'sort_order'=>$count + 1, 'is_root'=>0, 'root_key'=>$parent['root_key'], 'max_depth'=>5, 'content_type'=>((string)$type === 'chapter' ? 'chapter' : 'page'),
            'status'=>'draft', 'created_by'=>(int)$user_id, 'created_at'=>$now, 'updated_at'=>$now
        ]);
        $this->mark_book_draft_by_root($parent['root_key']);
        return ['ok'=>true,'message'=>(((string)$type === 'chapter') ? 'Chapter' : 'Page') . ' added'];
    }

    public function save_page_draft($node_id, $html, $meta, $user_id, $status = 'draft')
    {
        $this->ensure_review_columns();
        $node = $this->db->get_where('content_nodes', ['node_id'=>(int)$node_id, 'created_by'=>(int)$user_id])->row_array();
        if (!$node) return ['ok'=>false,'message'=>'Content item not found'];
        if ($node['status'] === 'published') return ['ok'=>false,'message'=>'Published content cannot be edited directly'];
        $existing = $this->db->get_where('content_node_pages', ['node_id'=>(int)$node_id])->row_array();
        $now = date('Y-m-d H:i:s');
        $data = [
            'node_id'=>(int)$node_id, 'html'=>$html,
            'meta_title'=>$meta['meta_title'] ?? null, 'meta_description'=>$meta['meta_description'] ?? null,
            'meta_keywords'=>$meta['meta_keywords'] ?? null, 'canonical_url'=>$meta['canonical_url'] ?? null,
            'content_category'=>trim((string)($meta['content_category'] ?? '')),
            'content_tags'=>trim((string)($meta['content_tags'] ?? '')),
            'og_image'=>$meta['og_image'] ?? ($existing['og_image'] ?? null),
            'status'=>'draft', 'review_status'=>'draft', 'updated_by'=>(int)$user_id, 'updated_at'=>$now
        ];
        if ($existing) { $this->db->where('page_id', (int)$existing['page_id'])->update('content_node_pages', $data); }
        else { $data['created_by'] = (int)$user_id; $data['created_at'] = $now; $this->db->insert('content_node_pages', $data); }
        $this->db->where('node_id', (int)$node_id)->update('content_nodes', ['status'=>'draft', 'review_status'=>'draft', 'updated_by'=>(int)$user_id, 'updated_at'=>$now]);
        if (strpos($node['root_key'], 'book_' . (int)$user_id . '_') === 0) $this->mark_book_draft_by_root($node['root_key']);
        return ['ok'=>true,'message'=>'Draft saved'];
    }

    public function get_owned_page($node_id, $user_id)
    {
        $this->ensure_review_columns();
        $node_id = (int)$node_id;
        $user_id = (int)$user_id;
        $node = $this->db
            ->where('node_id', $node_id)
            ->where('created_by', $user_id)
            ->where('COALESCE(is_deleted,0)', 0, false)
            ->get('content_nodes')
            ->row_array();
        if (!$node) {
            return null;
        }

        $page = $this->db->select('p.*, n.title AS node_title, n.content_type, n.status AS node_status, n.review_status AS node_review_status')
            ->from('content_node_pages p')
            ->join('content_nodes n', 'n.node_id = p.node_id')
            ->where('p.node_id', $node_id)
            ->where('n.created_by', $user_id)
            ->where('COALESCE(n.is_deleted,0)', 0, false)
            ->where('COALESCE(p.is_deleted,0)', 0, false)
            ->get()->row_array();
        if ($page) {
            return $page;
        }

        if (in_array((string)($node['content_type'] ?? ''), ['article', 'page'], true)) {
            $now = date('Y-m-d H:i:s');
            $this->db->insert('content_node_pages', [
                'node_id' => $node_id,
                'created_by' => $user_id,
                'updated_by' => $user_id,
                'html' => '',
                'meta_title' => $node['title'] ?? '',
                'status' => 'draft',
                'review_status' => 'draft',
                'is_deleted' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return $this->db->select('p.*, n.title AS node_title, n.content_type, n.status AS node_status, n.review_status AS node_review_status')
                ->from('content_node_pages p')
                ->join('content_nodes n', 'n.node_id = p.node_id')
                ->where('p.node_id', $node_id)
                ->where('n.created_by', $user_id)
                ->get()->row_array();
        }

        return null;
    }

    public function duplicate_page_node($user_id, $node_id)
    {
        $node = $this->db->get_where('content_nodes', ['node_id'=>(int)$node_id, 'created_by'=>(int)$user_id])->row_array();
        if (!$node) return ['ok'=>false,'message'=>'Page not found'];
        $title = $node['title'] . ' Copy';
        $slug = $this->slugify($title);
        $now = date('Y-m-d H:i:s');
        $this->db->insert('content_nodes', [
            'parent_id'=>(int)$node['parent_id'], 'title'=>$title, 'slug'=>$slug,
            'full_path'=>$this->make_unique_full_path(rtrim(dirname($node['full_path']), '.') . '/' . $slug),
            'level'=>(int)$node['level'], 'sort_order'=>((int)$node['sort_order']) + 1, 'is_root'=>0, 'root_key'=>$node['root_key'], 'max_depth'=>(int)$node['max_depth'], 'content_type'=>'page',
            'status'=>'draft', 'created_by'=>(int)$user_id, 'created_at'=>$now, 'updated_at'=>$now
        ]);
        $new_id = (int)$this->db->insert_id();
        $page = $this->db->get_where('content_node_pages', ['node_id'=>(int)$node_id])->row_array();
        if ($page) {
            unset($page['page_id']); $page['node_id'] = $new_id; $page['status'] = 'draft'; $page['created_at'] = $now; $page['updated_at'] = $now; $page['approved_by'] = null; $page['approved_at'] = null;
            $this->db->insert('content_node_pages', $page);
        }
        $this->mark_book_draft_by_root($node['root_key']);
        return ['ok'=>true,'message'=>'Page duplicated'];
    }

    public function submit_book_for_review($user_id, $book_id)
    {
        $this->ensure_review_columns();
        $book = $this->db->get_where('content_nodes', ['node_id'=>(int)$book_id, 'created_by'=>(int)$user_id])->row_array();
        if (!$book || strpos($book['root_key'], 'book_' . (int)$user_id . '_') !== 0) return ['ok'=>false,'message'=>'Book not found'];
        $now = date('Y-m-d H:i:s');
        $this->db->where('root_key', $book['root_key'])->update('content_nodes', ['status'=>'pending', 'review_status'=>'in_review', 'updated_at'=>$now]);
        $ids = $this->db->select('node_id')->where('root_key', $book['root_key'])->get('content_nodes')->result_array();
        foreach ($ids as $r) {
            $this->db->where('node_id', (int)$r['node_id'])->update('content_node_pages', ['status'=>'pending', 'review_status'=>'in_review', 'updated_at'=>$now, 'updated_by'=>(int)$user_id]);
        }
        return ['ok'=>true,'message'=>'Book sent to admin for approval'];
    }

    public function submit_article_for_review($user_id, $article_id)
    {
        $this->ensure_review_columns();
        $article = $this->db->get_where('content_nodes', ['node_id'=>(int)$article_id, 'created_by'=>(int)$user_id])->row_array();
        if (!$article || strpos($article['root_key'], 'article_' . (int)$user_id . '_') !== 0) return ['ok'=>false,'message'=>'Article not found'];
        $page = $this->db->get_where('content_node_pages', ['node_id'=>(int)$article_id])->row_array();
        if (!$page || trim(strip_tags((string)$page['html'])) === '') return ['ok'=>false,'message'=>'Please write and save article content before publishing'];
        $now = date('Y-m-d H:i:s');
        $this->db->where('node_id', (int)$article_id)->update('content_nodes', ['status'=>'pending', 'review_status'=>'in_review', 'updated_at'=>$now]);
        $this->db->where('node_id', (int)$article_id)->update('content_node_pages', ['status'=>'pending', 'review_status'=>'in_review', 'updated_at'=>$now, 'updated_by'=>(int)$user_id]);
        return ['ok'=>true,'message'=>'Article sent to admin for approval'];
    }

    private function mark_book_draft_by_root($root_key)
    {
        $this->db->where('root_key', $root_key)->where('parent_id IS NULL', null, false)->update('content_nodes', ['status'=>'draft', 'updated_at'=>date('Y-m-d H:i:s')]);
    }

    private function is_admin_role($role)
    {
        return strtolower((string)$role) === 'admin'
            || (int)$this->session->userdata('role_id') === 1
            || $this->session->userdata('admin_login') == true;
    }

}
