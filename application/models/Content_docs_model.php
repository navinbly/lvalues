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
        // Admin can see published + pending (not rejected)
        $this->db->where_in('status', ['published', 'pending', 'draft']);
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

        if (!in_array($parent['status'], ['published'])) {
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
        $status = ($user_role === 'admin') ? 'published' : 'pending';

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
            'status'      => $status,
            'created_by'  => (int)$user_id,
            'created_at'  => date('Y-m-d H:i:s')
        ];

        if ($status === 'published') {
            $data['approved_by'] = (int)$user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert('content_nodes', $data);

        return ['ok' => true, 'message' => ($status === 'published') ? 'Node created & published.' : 'Node created and sent for admin approval.'];
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
	
	// -------- PENDING LIST --------
public function get_pending_nodes()
{
    $this->db->where('status', 'pending');
    $this->db->order_by('created_at', 'DESC');
    return $this->db->get('content_nodes')->result_array();
}

// -------- APPROVE / REJECT --------
public function approve_node($node_id, $admin_id)
{
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) {
        return ['ok' => false, 'message' => 'Node not found'];
    }
    if ($node['status'] !== 'pending') {
        return ['ok' => false, 'message' => 'Only pending nodes can be approved'];
    }

    $this->db->where('node_id', (int)$node_id);
    $this->db->update('content_nodes', [
        'status'      => 'published',
        'approved_by' => (int)$admin_id,
        'approved_at' => date('Y-m-d H:i:s'),
        'updated_at'  => date('Y-m-d H:i:s')
    ]);

    return ['ok' => true, 'message' => 'Node approved & published'];
}

public function reject_node($node_id, $admin_id)
{
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) {
        return ['ok' => false, 'message' => 'Node not found'];
    }
    if ($node['status'] !== 'pending') {
        return ['ok' => false, 'message' => 'Only pending nodes can be rejected'];
    }

    $this->db->where('node_id', (int)$node_id);
    $this->db->update('content_nodes', [
        'status'     => 'rejected',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    return ['ok' => true, 'message' => 'Node rejected'];
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
    $node = $this->db->get_where('content_nodes', ['node_id' => (int)$node_id])->row_array();
    if (!$node) return ['ok' => false, 'message' => 'Node not found.'];

    // Do not allow deleting root nodes
    //if ((int)$node['is_root'] === 1) {
    //    return ['ok' => false, 'message' => 'Root nodes cannot be deleted.'];
    //}
	
	// Allow deleting root nodes only if they are empty (no children) and (if published content exists) user confirms force delete.
    // If you want to NEVER allow root deletion for AWS/GCP default roots, keep the old block.


    $this->_reject_node_and_children((int)$node_id);

    return ['ok' => true, 'message' => 'Node deleted (soft) with children.'];
}

private function _reject_node_and_children($node_id)
{
    // reject this node
    $this->db->where('node_id', $node_id)->update('content_nodes', [
        'status'     => 'rejected',
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    // find children
    $children = $this->db->get_where('content_nodes', ['parent_id' => $node_id])->result_array();
    foreach ($children as $c) {
        $this->_reject_node_and_children((int)$c['node_id']);
    }
}

public function delete_node_safe($node_id, $force = false)
{
    $node_id = (int)$node_id;

    $node = $this->db->get_where('content_nodes', ['node_id' => $node_id])->row_array();
    if (!$node) {
        return ['ok' => false, 'needs_force' => false, 'message' => 'Node not found.'];
    }

    // 1) children check
    $children = $this->db->where('parent_id', $node_id)->count_all_results('content_nodes');
    if ($children > 0) {
        return ['ok' => false, 'needs_force' => false, 'message' => 'Cannot delete: node has child nodes. Delete leaf nodes first.'];
    }

    // 2) page check
    $page = $this->db->select('page_id, status, html')
                     ->where('node_id', $node_id)
                     ->get('content_node_pages')
                     ->row_array();

    if ($page) {
        $plain = trim(strip_tags($page['html'] ?? ''));
        $has_content  = ($plain !== '');
        $is_published = (strtolower($page['status'] ?? '') === 'published');

        if ($is_published && $has_content) {
            return [
                'ok' => false,
                'needs_force' => false,
                'message' => 'This node has published content. First delete the content and then try to delete the node!'
            ];
        }
    }

    // ✅ START TRANSACTION
    $this->db->trans_start();

    // delete page row (if exists)
    if ($page) {
        $this->db->where('page_id', (int)$page['page_id'])->delete('content_node_pages');
    }

    // delete rule if root
    if ((int)$node['is_root'] === 1) {
        $this->db->where('root_key', $node['root_key'])->delete('content_node_rules');
    }

    // delete node
    $this->db->where('node_id', $node_id)->delete('content_nodes');

    $this->db->trans_complete();
    // ✅ END TRANSACTION

    if ($this->db->trans_status() === FALSE) {
        return ['ok' => false, 'needs_force' => false, 'message' => 'Delete failed.'];
    }

    return ['ok' => true, 'needs_force' => false, 'message' => 'Node deleted successfully.'];
}



// ================================
// Content Page (Whiteboard) Logic
// ================================

public function get_by_node($node_id) {
    return $this->db->get_where('content_node_pages', ['node_id' => $node_id])->row_array();
}

public function save_page($node_id, $data, $user_id, $role) {

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

    // Root node: change only title (do NOT change full_path which is root_key)
    if ((int)$node['is_root'] === 1) {
        $this->db->where('node_id', $node_id)->update('content_nodes', [
            'title'      => $title,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'message' => 'Node updated'];
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



	
public function update_node_order($parent_id, $ordered_ids)
{
    $parent_id = (int)$parent_id;

    if (!is_array($ordered_ids) || empty($ordered_ids)) {
        return ['ok' => false, 'message' => 'Empty order'];
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



}


