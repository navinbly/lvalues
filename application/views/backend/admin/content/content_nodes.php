<?php
// expects: $nodes (flat array), $root_rules (rules array)
function build_tree($nodes) {
    $by_parent = [];
    foreach ($nodes as $n) {
        $pid = $n['parent_id'] ? (int)$n['parent_id'] : 0;
        if (!isset($by_parent[$pid])) $by_parent[$pid] = [];
        $by_parent[$pid][] = $n;
    }
    return $by_parent;
}

$by_parent = build_tree($nodes);

// Render recursive UL
function render_nodes($by_parent, $parent_id = 0) {
    if (!isset($by_parent[$parent_id])) return;
    echo '<ul class="list-unstyled ms-2 mb-1">';
    foreach ($by_parent[$parent_id] as $n) {
        $node_id = (int)$n['node_id'];
        $has_children = isset($by_parent[$node_id]);

        $badge = ($n['status'] === 'published') ? 'success' : (($n['status'] === 'pending') ? 'warning' : 'secondary');

        echo '<li class="mb-1">';
        echo '  <div class="d-flex align-items-center gap-2">';
        if ($has_children) {
            echo '    <span class="toggle text-muted" data-node="'.$node_id.'">+</span>';
        } else {
            echo '    <span class="toggle-placeholder"></span>';
        }

        echo '    <a href="javascript:void(0);" class="node-link" 
                    data-node-id="'.$node_id.'" 
                    data-title="'.htmlspecialchars($n['title']).'" 
                    data-level="'.(int)$n['level'].'"
                    data-root-key="'.htmlspecialchars($n['root_key']).'"
                >'.htmlspecialchars($n['title']).'</a>';

        echo '    <span class="badge bg-'.$badge.'">'.htmlspecialchars($n['status']).'</span>';
        echo '    <small class="text-muted">('.htmlspecialchars($n['full_path']).' | L'.$n['level'].')</small>';
        echo '  </div>';

        echo '  <div class="children ms-3 mt-1" id="children-'.$node_id.'" style="display:none;">';
        render_nodes($by_parent, $node_id);
        echo '  </div>';

        echo '</li>';
    }
    echo '</ul>';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Content (Docs) - Node Tree</h5>
                <small class="text-muted">Click <b>+</b> to expand/collapse. Click node name to select.</small>
            </div>
            <div class="card-body" id="treeContainer">

                <div class="mb-3">
                    <b>Rules:</b>
                    <?php foreach ($root_rules as $r): ?>
                        <span class="badge bg-info text-dark me-1">
                            <?= htmlspecialchars($r['root_title']) ?> max = <?= (int)$r['max_depth'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <?php render_nodes($by_parent, 0); ?>

            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Selected Node</h5>
                <small class="text-muted">Select any node on left to add child under it.</small>
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <div><b>Selected:</b> <span id="selectedTitle">None</span></div>
                    <div><b>Level:</b> <span id="selectedLevel">-</span></div>
                    <div><b>Root:</b> <span id="selectedRootKey">-</span></div>
                </div>

                <form method="post" action="<?= site_url('admin/content_nodes/add'); ?>">
                    <input type="hidden" name="parent_id" id="parent_id" value="">

                    <div class="mb-3">
                        <label class="form-label">New Child Node Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Example: BigQuery" required>
                        <small class="text-muted">This will create a node under the selected node.</small>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnAddNode" disabled>
                        + Add Node Under Selected
                    </button>
                </form>

                <hr>

                <div class="mt-2">
                    <a class="btn btn-outline-warning" href="<?= site_url('admin/content_nodes_pending'); ?>">
                        View Pending Nodes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.toggle { cursor:pointer; font-weight:700; width:16px; display:inline-block; }
.toggle-placeholder { width:16px; display:inline-block; }
.node-link { text-decoration:none; }
.node-link:hover { text-decoration:underline; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Expand/Collapse only on +
    document.querySelectorAll('.toggle').forEach(function(t) {
        t.addEventListener('click', function(e) {
            const nodeId = this.getAttribute('data-node');
            const children = document.getElementById('children-' + nodeId);
            if (!children) return;

            if (children.style.display === 'none') {
                children.style.display = 'block';
                this.textContent = '-';
            } else {
                children.style.display = 'none';
                this.textContent = '+';
            }
            e.stopPropagation();
        });
    });

    // Select node on clicking name
    document.querySelectorAll('.node-link').forEach(function(a) {
        a.addEventListener('click', function() {
            const nodeId = this.getAttribute('data-node-id');
            const title  = this.getAttribute('data-title');
            const level  = this.getAttribute('data-level');
            const root   = this.getAttribute('data-root-key');

            document.getElementById('parent_id').value = nodeId;
            document.getElementById('selectedTitle').textContent = title;
            document.getElementById('selectedLevel').textContent = level;
            document.getElementById('selectedRootKey').textContent = root;

            document.getElementById('btnAddNode').disabled = false;
        });
    });
});
</script>
