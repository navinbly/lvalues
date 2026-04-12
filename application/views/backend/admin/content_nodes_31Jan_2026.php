<?php
// Build parent->children map
$children = [];
foreach ($nodes as $n) {
    $pid = $n['parent_id'] ? (int)$n['parent_id'] : 0;
    if (!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $n;
}

$role = $this->session->userdata('role'); // Admin / Instructor / Tutor (your DB shows "Admin")

// ---------------- ROOT dropdown data (Published roots only) ----------------
//======================================Navin===================================== -->
$root_nodes = [];
foreach ($nodes as $n) {
    $pid = $n['parent_id'] ? (int)$n['parent_id'] : 0;
    if ($pid === 0 && $n['status'] === 'published') {
        $root_nodes[] = $n;
    }
}

// Sort roots A->Z by title (AWS first etc.)
usort($root_nodes, function($a, $b){
    return strcasecmp($a['title'], $b['title']);
});

// Default root = first root (if exists)
$default_root_id = !empty($root_nodes) ? (int)$root_nodes[0]['node_id'] : 0;
//======================================Navin=====================================

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

              <a id="deleteNodeBtn" href="javascript:void(0)" class="btn btn-sm btn-danger"
					onclick="return deleteSelectedNode(0);">
				   Delete
				</a>
            </div>
          </div>
        </div>
<!-- ===========================================Navin================================= -->
<div class="mt-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Course</h5>

    <div style="min-width:280px;">
      <select id="root_selector" class="form-control form-control-sm">
        <?php foreach ($root_nodes as $r): ?>
          <option value="<?php echo (int)$r['node_id']; ?>" <?php echo ((int)$r['node_id'] === $default_root_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($r['title']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small class="text-muted">Switch root to view/manage its structure.</small>
    </div>
  </div>

  <?php if (empty($root_nodes)): ?>
    <div class="alert alert-warning mb-0">
      No published root trees found yet. Admin can create one using <b>+ Add Root Tree</b>.
    </div>
  <?php else: ?>

    <?php foreach ($root_nodes as $r): ?>
      <?php $rid = (int)$r['node_id']; ?>
      <div class="rootTreeBox" id="rootTree-<?php echo $rid; ?>" style="<?php echo ($rid === $default_root_id) ? '' : 'display:none;'; ?>">
        <!-- Show the ROOT node clickable (so you can add child under it) -->
        <div class="mb-2 p-2 border rounded bg-white">
          <a href="javascript:void(0);" class="node-click text-decoration-none"
             onclick="selectNode('<?php echo $rid; ?>',
                                '<?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?>',
                                '<?php echo htmlspecialchars($r['full_path'], ENT_QUOTES); ?>',
                                '<?php echo (int)$r['level']; ?>',
                                '<?php echo htmlspecialchars($r['status'], ENT_QUOTES); ?>',
                                '<?php echo (int)$r['is_root']; ?>')">
            <div class="font-weight-bold">
              <?php echo htmlspecialchars($r['title']); ?>
              <span class="badge badge-success ml-2">Published</span>
            </div>
            <div class="text-muted small">
              <?php echo htmlspecialchars($r['full_path']); ?> (Level <?php echo (int)$r['level']; ?>)
            </div>
          </a>
        </div>

        <?php
          // Render only this root's children tree
          render_tree($rid, $children);
        ?>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>
<!-- ===========================================Navin================================= -->

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

<!-- Whiteboard UI Below Tree -->
<div class="card mt-4">
  <div class="card-body">
    <h5 class="mb-2">Content Editor (Whiteboard)</h5>
    <div id="editorPanel" style="display:none;">
      <form method="post" action="<?php echo site_url('admin/content_page_save'); ?>" enctype="multipart/form-data">

        <input type="hidden" name="node_id" id="content_node_id">

        <div class="form-group">
          <label>Content</label>
          <textarea name="html" id="editor" class="form-control" rows="10"></textarea>
        </div>

        <div class="row">
          <div class="col-md-4">
            <label>Meta Title</label>
            <input type="text" name="meta_title" class="form-control">
          </div>
          <div class="col-md-4">
            <label>Meta Description</label>
            <input type="text" name="meta_description" class="form-control">
          </div>
          <div class="col-md-4">
            <label>Meta Keywords</label>
            <input type="text" name="meta_keywords" class="form-control">
          </div>
        </div>

        <div class="form-group mt-3">
          <label>OG Image</label>
          
		  
		  <input type="file" name="og_image" id="og_image" class="form-control" accept="image/*">
			<small class="text-muted">Recommended: 1200×630 (OG). Max 2MB.</small>

			<div class="mt-2" id="ogPreviewBox" style="display:none;">
			  <img id="ogPreview" src="" style="max-width:220px; border:1px solid #ddd; padding:4px; border-radius:4px;">
			  <div class="small text-muted mt-1" id="ogMeta"></div>
			</div>
		  		  
		  
        </div>

        <div class="mt-3">
          <button type="submit" class="btn btn-success">Save / Submit for Approval</button>
        </div>
      </form>
    </div>

    <div id="editorPlaceholder" class="text-muted">
      Select a node to start adding content.
    </div>
  </div>
</div>

<!-- Whiteboard UI Below Tree -->

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
	  
	nodeId = parseInt(nodeId, 10);           // ✅ force numeric
    if (isNaN(nodeId) || nodeId <= 0) {
        alert("Invalid nodeId received: " + nodeId);
        return;
    }
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
	
	document.getElementById('editorPanel').style.display = 'block';
	// Init CKEditor once when editor becomes visible
	if (window.CKEDITOR && document.getElementById('editor')) {
	  if (!CKEDITOR.instances.editor) {
		CKEDITOR.replace('editor', {
		  height: 260,
		  filebrowserUploadUrl: "<?php echo site_url('admin/content_page_upload'); ?>",
		  filebrowserUploadMethod: "form"
		});
	  }
	}

	
    document.getElementById('editorPlaceholder').style.display = 'none';
    document.getElementById('content_node_id').value = nodeId;
	
	// Load saved content into editor
	var url = "<?php echo site_url('admin/content_page_get/'); ?>" + nodeId;

	fetch(url)
	  .then(r => r.json())
	  .then(resp => {
		if (!resp || !resp.ok) return;

		var html = '';
		if (resp.data && resp.data.html) {
		  html = resp.data.html;
		}

		if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
		  CKEDITOR.instances.editor.setData(html);
		} else {
		  // fallback if textarea exists
		  var ta = document.getElementById('editor');
		  if (ta) ta.value = html;
		}
	  })
	 // .catch(err => console.log(err));
	console.log("selectNode source:", selectNode.toString());

    // Enable actions only if not root
    //const actions = document.getElementById('selectedNodeActions');
    //if (selectedIsRoot === 1) {
    //  actions.style.display = 'none';
    //} else {
    //  actions.style.display = 'flex';
    //  document.getElementById('deleteNodeBtn').href = "<?php echo site_url('admin/content_nodes_delete/'); ?>" + nodeId;
    //}
	
	// Enable actions for BOTH root + non-root (backend will decide if delete is allowed)
	const actions = document.getElementById('selectedNodeActions');
	actions.style.display = 'flex';
  }

  function validateParentSelected() {
    if (!document.getElementById('parent_id').value) {
      alert("Please select a parent node from the tree first (click a node in the Tree).");
      return false;
    }
    return true;
  }
  
  <!-- ===============================Navin========================== -->
    // Root dropdown filter (does NOT affect Add/Edit/Delete logic)
  document.addEventListener('change', function(e){
    if (e.target && e.target.id === 'root_selector') {
      var rootId = e.target.value;

      var boxes = document.querySelectorAll('.rootTreeBox');
      boxes.forEach(function(b){ b.style.display = 'none'; });

      var active = document.getElementById('rootTree-' + rootId);
      if (active) active.style.display = 'block';

      // Reset selection panel to avoid confusion when switching root
      document.getElementById('selectedNodeActions').style.display = 'none';
      document.getElementById('selectedNodeInfo').innerHTML =
        'Click a node in the tree to manage it.';
      document.getElementById('parent_id').value = '';
      document.getElementById('parentPreview').innerHTML = 'No node selected';
    }
  });
  
  <!-- ===============================Navin========================== -->
  
</script>

<!-- CKEditor (Full build includes image2 for resizing) -->
<script src="https://cdn.ckeditor.com/4.21.0/full-all/ckeditor.js"></script>

<script>
(function () {
  var el = document.getElementById('editor');
  if (!el) return;

  // Prevent double-init if page is reloaded via partial render
  if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
    return;
  }

  // Small overlay (Bootstrap-4 compatible)
  var overlay = document.createElement('div');
  overlay.id = 'ckUploadOverlay';
  overlay.style.cssText =
    'display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.35); z-index:9999;';
  overlay.innerHTML =
    '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:15px 20px;border-radius:6px;box-shadow:0 5px 30px rgba(0,0,0,.25);font-weight:600;">Uploading... Please wait</div>';
  document.body.appendChild(overlay);

  function showOverlay(){ overlay.style.display = 'block'; }
  function hideOverlay(){ overlay.style.display = 'none'; }

  // ✅ CKEditor init (with resizing support)
  CKEDITOR.replace('editor', {
    height: 320,

    // Enable image resizing handles
    extraPlugins: 'image2',
    removePlugins: 'image',

    // ✅ Only images: use your working endpoint
    filebrowserImageUploadUrl: "<?php echo site_url('admin/content_page_upload'); ?>",

    // (Optional) makes pasted content cleaner
    allowedContent: true
  });

  CKEDITOR.on('instanceReady', function (evt) {
    var editor = evt.editor;

    // Upload begin/end events
    editor.on('fileUploadRequest', function () {
      showOverlay();
    });

    editor.on('fileUploadResponse', function () {
      hideOverlay();
    });

    editor.on('fileUploadError', function () {
      hideOverlay();
      alert('Upload failed. Please try again with a JPG/PNG under the size limit.');
    });
  });
})();
</script>

<script>
(function () {
  const MAX_IMG_BYTES = 2 * 1024 * 1024; // 2MB

  document.addEventListener('change', function (e) {
    if (!e.target || e.target.id !== 'og_image') return;

    const f = e.target.files && e.target.files[0];
    const box  = document.getElementById('ogPreviewBox');
    const img  = document.getElementById('ogPreview');
    const meta = document.getElementById('ogMeta');

    if (!f) {
      if (box) box.style.display = 'none';
      return;
    }

    // ✅ size check
    if (f.size > MAX_IMG_BYTES) {
      alert('OG Image too large. Max 2MB allowed.');
      e.target.value = '';
      if (box) box.style.display = 'none';
      return;
    }

    // ✅ preview
    if (img) img.src = URL.createObjectURL(f);
    if (meta) meta.textContent = f.name + ' • ' + Math.round(f.size / 1024) + ' KB';
    if (box) box.style.display = 'block';
  });
})();
</script>

<script>
function deleteSelectedNode(force = 0) {
    // You must already have selected node id stored somewhere.
    // In your tree screen, it is usually in a hidden input or JS variable.
    // Try these common ones:
    //var nodeId = document.getElementById('content_node_id') ? document.getElementById('content_node_id').value : null;
	
	console.log("deleteSelectedNode called, force=", force);
	console.log("DELETE URL:", url);
	var nodeId = document.getElementById('content_node_id') ? parseInt(document.getElementById('content_node_id').value, 10)
  : null;
	//alert("Selected nodeId = " nodeId);
    //if (!nodeId || nodeId === '0') {
    //    alert('Please select a node first.');
    //    return false;
    //}
	
	if (!Number.isInteger(nodeId) || nodeId <= 0) {
    alert('Please select a node first.');
    return false;
	}

    if (force === 0) {
        if (!confirm('Delete this node? This cannot be undone.')) return false;
    }

    // ✅ IMPORTANT: update this URL to match your actual Admin route for delete_node_safe
    var url = "<?= site_url('admin/content_nodes_delete'); ?>/" + nodeId + "?force=" + force;


    fetch(url, { method: 'POST' })
      .then(r => r.json())
      .then(res => {
          if (res.ok) {
              location.reload();
              return;
          }
          if (res.needs_force) {
              if (confirm(res.message)) {
                  deleteSelectedNode(1); // retry with force=1
              }
              return;
          }
          alert(res.message || 'Delete failed');
      })
      .catch(() => alert('Delete failed (server error)'));

    return false;
}
</script>


