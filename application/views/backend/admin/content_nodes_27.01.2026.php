<?php
// Build parent->children map
$children = [];
foreach ($nodes as $n) {
    $pid = $n['parent_id'] ? (int)$n['parent_id'] : 0;
    if (!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $n;
}

$role = $this->session->userdata('role'); // Admin / Instructor / Tutor (your DB shows "Admin")

function render_tree($parent_id, $children)
{
    if (!isset($children[$parent_id])) return;

    // NOTE: your template seems Bootstrap-4 based; keep classes simple
    echo '<ul class="list-unstyled ml-3 mt-2 content-tree">';
    foreach ($children[$parent_id] as $node) {

        $status = $node['status'];
        $badge = '';
        if ($status === 'published') $badge = '<span class="badge badge-success ml-2">Published</span>';
        elseif ($status === 'pending') $badge = '<span class="badge badge-warning ml-2">Pending</span>';
        elseif ($status === 'draft') $badge = '<span class="badge badge-secondary ml-2">Draft</span>';
        else $badge = '<span class="badge badge-danger ml-2">'.htmlspecialchars($status).'</span>';

        $hasKids = isset($children[(int)$node['node_id']]);

        // Build safe JS strings
        $jsTitle  = htmlspecialchars($node['title'], ENT_QUOTES);
        $jsPath   = htmlspecialchars($node['full_path'], ENT_QUOTES);
        $jsStatus = htmlspecialchars($node['status'], ENT_QUOTES);

        $jsNodeId = (int)$node['node_id'];
        $jsLevel  = (int)$node['level'];
        $jsIsRoot = (int)$node['is_root'];

        echo '<li class="mb-2">';

        echo '<div class="d-flex align-items-start">';

        // toggle button
        if ($hasKids) {
            echo '<button type="button" class="btn btn-sm btn-light mr-2 tree-toggle" data-target="kids-'.$jsNodeId.'">+</button>';
        } else {
            echo '<span class="mr-2" style="width:34px; display:inline-block;"></span>';
        }

        // clickable node area
        echo '<div class="flex-grow-1">';
        echo '<a href="javascript:void(0);" class="node-click text-decoration-none" 
                 onclick="selectNode(\''.$jsNodeId.'\', \''.$jsTitle.'\', \''.$jsPath.'\', \''.$jsLevel.'\', \''.$jsStatus.'\', \''.$jsIsRoot.'\')">';

        echo '<div class="font-weight-semibold">'.htmlspecialchars($node['title']).$badge.'</div>';
        echo '<div class="text-muted small">'.htmlspecialchars($node['full_path']).' (Level '.$node['level'].')</div>';
        echo '</a>';
        echo '</div>';

        echo '</div>'; // d-flex

        // children box
        if ($hasKids) {
            echo '<div id="kids-'.$jsNodeId.'" class="tree-kids d-none">';
            render_tree((int)$node['node_id'], $children);
            echo '</div>';
        }

        echo '</li>';
    }
    echo '</ul>';
}
?>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
			<h4 class="header-title mb-1">Content Structure Builder</h4>
			<p class="text-muted mb-0">
			Build your course or documentation structure here.  
			Click a node to select it, then use <b>+ Add Node</b> to create topics, sub-topics, or lessons under it.
			</p>
          </div>

          <div class="d-flex" style="gap:10px;">
            <?php if (strtolower($role) == 'admin'): ?>
              <!-- Bootstrap 4 attributes -->
              <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#rootModal">
                + Add Root Tree
              </button>
            <?php endif; ?>

            <!-- Bootstrap 4 attributes -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addNodeModal">
              + Add Node
            </button>
          </div>
        </div>
		
		

<div class="alert alert-light border">
  <div class="d-flex align-items-center mb-1">
    <i class="dripicons-document mr-2 text-primary"></i>
    <strong>Course Structure Guidelines</strong>
  </div>

  <small class="text-muted">
    Each content tree follows predefined depth rules and approval flow.
    Please refer to the documentation before creating or extending nodes.
  </small>

  <div class="mt-2">
    <a href="<?php echo site_url('admin/content_docs_readme'); ?>" class="btn btn-sm btn-outline-primary">
      View Documentation
    </a>
  </div>
</div>
		
		
		

        <!-- Selected Node Panel -->
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="mb-2">Edit/Delete-Course</h5>

            <div id="selectedNodeInfo" class="text-muted">
              Select the course and click on Edit/Delete.
            </div>

            <div id="selectedNodeActions" style="display:none;" class="mt-3 d-flex" style="gap:10px;">
              <button class="btn btn-sm btn-warning" type="button" data-toggle="modal" data-target="#editNodeModal">
                Edit
              </button>

              <a id="deleteNodeBtn" href="#" class="btn btn-sm btn-danger"
                 onclick="return confirm('Delete this node? This cannot be undone.');">
                Delete
              </a>
            </div>
          </div>
        </div>

        <div class="mt-3">
          <h5 class="mb-2">Tree</h5>
          <?php render_tree(0, $children); ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Add Node Modal (Bootstrap 4) -->
<div class="modal fade" id="addNodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/add'); ?>" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add Node</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <!-- Parent is auto-filled from selected node -->
        <input type="hidden" name="parent_id" id="parent_id" value="">

        <div class="alert alert-light">
          <b>Parent:</b> <span id="parentPreview" class="text-muted">No node selected</span>
          <div class="text-muted small mt-1">Tip: click a node in Tree first (AWS/GCP/DWH or any published node).</div>
        </div>

        <div class="form-group">
          <label>Node Title</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Data Engineering / BigQuery / Introduction" required>
        </div>

        <div class="alert alert-warning mb-0">
          <b>Publish rules:</b> If Admin creates → <b>Published</b>. If Tutor/Instructor creates → <b>Pending</b> (Admin must approve in Pending Nodes page).
        </div>

      </div>

      <div class="modal-footer">
        <!-- FIXED: Cancel button should be a normal button with data-dismiss -->
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" onclick="return validateParentSelected();">Create Node</button>
      </div>

    </form>
  </div>
</div>

<!-- Edit Node Modal (Bootstrap 4) -->
<div class="modal fade" id="editNodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/update'); ?>" class="modal-content">


      <div class="modal-header">
        <h5 class="modal-title">Edit Node</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="node_id" id="edit_node_id" value="">

        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" id="edit_title" class="form-control" required>
        </div>

        <div class="text-muted small">
          Note: slug/full_path update logic will be handled in backend in next step.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Update</button>
      </div>

    </form>
  </div>
</div>

<!-- Add Root Tree Modal (Admin only) (Bootstrap 4) -->
<?php if (strtolower($role) == 'admin'): ?>
<div class="modal fade" id="rootModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/add_root'); ?>" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add Root Tree</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label>Root Key (unique)</label>
          <input type="text" name="root_key" class="form-control" placeholder="e.g. programming" required>
          <small class="text-muted">Lowercase recommended. Used in full_path.</small>
        </div>

        <div class="form-group">
          <label>Root Title</label>
          <input type="text" name="root_title" class="form-control" placeholder="e.g. Programming" required>
        </div>

        <div class="form-group">
          <label>Max Depth</label>
          <input type="number" name="max_depth" class="form-control" min="1" value="3" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Root</button>
      </div>

    </form>
  </div>
</div>
<?php endif; ?>

<script>
  // Expand/Collapse tree nodes
  document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('tree-toggle')) return;

    const targetId = e.target.getAttribute('data-target');
    const box = document.getElementById(targetId);
    if (!box) return;

    box.classList.toggle('d-none');
    e.target.textContent = box.classList.contains('d-none') ? '+' : '-';
  });

  // Selected node state
  let selectedNodeId = null;
  let selectedIsRoot = 0;

  function selectNode(nodeId, title, fullPath, level, status, isRoot) {
    selectedNodeId = nodeId;
    selectedIsRoot = parseInt(isRoot);

    document.getElementById('selectedNodeInfo').innerHTML =
      `<b>${title}</b><br><span class="text-muted">${fullPath} (Level ${level}) - ${status}</span>`;

    // Fill parent for Add Node
    document.getElementById('parent_id').value = nodeId;
    document.getElementById('parentPreview').innerHTML = `<b>${title}</b> <span class="text-muted">(${fullPath})</span>`;

    // Fill edit fields
    document.getElementById('edit_node_id').value = nodeId;
    document.getElementById('edit_title').value = title;

    // Enable actions only if not root
    const actions = document.getElementById('selectedNodeActions');
    if (selectedIsRoot === 1) {
      actions.style.display = 'none';
    } else {
      actions.style.display = 'flex';
      document.getElementById('deleteNodeBtn').href = "<?php echo site_url('admin/content_nodes_delete/'); ?>" + nodeId;
    }
  }

  function validateParentSelected() {
    if (!document.getElementById('parent_id').value) {
      alert("Please select a parent node from the tree first (click a node in the Tree).");
      return false;
    }
    return true;
  }
</script>
