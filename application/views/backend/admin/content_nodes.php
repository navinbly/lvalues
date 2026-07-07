<?php
// Build parent->children map
$children = [];
foreach ($nodes as $n) {
    $pid = ($n['parent_id'] === null || $n['parent_id'] === '' || (int)$n['parent_id'] === 0)
        ? 0
        : (int)$n['parent_id'];
    if (!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $n;
}

// Sort children by sort_order then title (stable ordering)
foreach ($children as $pid => &$list) {
    usort($list, function($a, $b){
        $aso = (int)($a['sort_order'] ?? 0);
        $bso = (int)($b['sort_order'] ?? 0);
        if ($aso !== $bso) return $aso <=> $bso;
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    });
}
unset($list);

$role = $this->session->userdata('role'); // Admin / Instructor / Tutor (your DB shows "Admin")

// ---------------- ROOT dropdown data ----------------
//======================================Navin===================================== -->
$root_nodes = [];
foreach ($nodes as $n) {
    $pid = $n['parent_id'] ? (int)$n['parent_id'] : 0;
    $isModernRoot = $pid === 0 && in_array(($n['content_type'] ?? 'legacy'), ['book', 'article'], true);
    if (($pid === 0 && $n['status'] === 'published') || $isModernRoot) {
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

    echo '<ul class="list-unstyled content-tree-modern" data-parent-id="'.(int)$parent_id.'">';
    foreach ($children[$parent_id] as $node) {
        $status = $node['status'] ?? 'draft';
        $statusLabel = htmlspecialchars(ucwords(str_replace('_', ' ', $status)));
        $badgeClass = 'secondary';
        if ($status === 'published') $badgeClass = 'success';
        elseif ($status === 'pending' || $status === 'in_review') $badgeClass = 'info';
        elseif ($status === 'update_required' || $status === 'on_hold') $badgeClass = 'warning';
        elseif ($status === 'rejected' || $status === 'deleted') $badgeClass = 'danger';

        $hasKids = isset($children[(int)$node['node_id']]);
        $jsNodeId = (int)$node['node_id'];
        $jsLevel  = (int)($node['level'] ?? 0);
        $jsIsRoot = (int)($node['is_root'] ?? 0);
        $jsTitle  = htmlspecialchars($node['title'] ?? '', ENT_QUOTES);
        $jsPath   = htmlspecialchars($node['full_path'] ?? '', ENT_QUOTES);
        $jsStatus = htmlspecialchars($status, ENT_QUOTES);
        $titleText = htmlspecialchars($node['title'] ?? 'Untitled');
        $pathText = htmlspecialchars($node['full_path'] ?? '');

        $icon = '📄';
        $addLabel = 'Add Page';
        if ($jsIsRoot === 1 || $jsLevel === 0) { $icon = '📘'; $addLabel = 'Add Chapter'; }
        elseif ($jsLevel === 1) { $icon = '📂'; $addLabel = 'Add Page'; }

        echo '<li class="tree-item-modern" data-node-id="'.$jsNodeId.'" data-title="'.$jsTitle.'" data-path="'.$jsPath.'">';
        echo '<div class="tree-row-modern">';
        if ($hasKids) {
            echo '<button type="button" class="tree-toggle-modern tree-toggle" data-target="kids-'.$jsNodeId.'" title="Expand/Collapse">▾</button>';
        } else {
            echo '<span class="tree-toggle-spacer"></span>';
        }
        echo '<a href="javascript:void(0);" class="node-click tree-main-modern" onclick="selectNode(\''.$jsNodeId.'\', \''.$jsTitle.'\', \''.$jsPath.'\', \''.$jsLevel.'\', \''.$jsStatus.'\', \''.$jsIsRoot.'\')">';
        echo '<span class="tree-icon-modern">'.$icon.'</span>';
        echo '<span class="tree-text-modern"><span class="tree-title-modern">'.$titleText.'</span><span class="tree-path-modern">'.$pathText.'</span></span>';
        echo '</a>';
        echo '<div class="tree-actions-modern">';
        echo '<button type="button" class="tree-action-btn" title="'.$addLabel.'" onclick="openAddChildNode('.$jsNodeId.', \''.$jsTitle.'\', \''.$jsPath.'\'); return false;">＋</button>';
        echo '<button type="button" class="tree-action-btn tree-action-danger" title="Delete" onclick="openDeleteNode('.$jsNodeId.', \''.$jsTitle.'\', \''.$jsPath.'\', '.$jsLevel.', \''.$jsStatus.'\', '.$jsIsRoot.'); return false;">−</button>';
        echo '</div>';
        echo '</div>';
        if ($hasKids) {
            echo '<div id="kids-'.$jsNodeId.'" class="tree-kids-modern tree-kids">';
            render_tree((int)$node['node_id'], $children);
            echo '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>

<style>
/* Content Publishing Studio: non-technical author friendly UI */
.content-studio-hero{background:linear-gradient(135deg,#f8fbff 0%,#eef2ff 55%,#f0fdfa 100%);border:1px solid #e2e8f0;border-radius:18px;padding:22px;margin-bottom:18px;}
.content-choice-card{height:100%;border:1px solid #e5e7eb;border-radius:22px;padding:24px;background:#fff;box-shadow:0 14px 36px rgba(15,23,42,.08);cursor:pointer;transition:.18s ease;position:relative;overflow:hidden;}
.content-choice-card:before{content:"";position:absolute;inset:0;background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(16,185,129,.05));opacity:0;transition:.18s ease;}
.content-choice-card:hover:before,.content-choice-card.active:before{opacity:1;}
.content-choice-card>*{position:relative;z-index:1;}
.content-choice-card .choice-cta{display:inline-flex;align-items:center;gap:8px;margin-top:14px;border-radius:999px;padding:8px 14px;font-weight:700;font-size:12px;background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;}
.content-choice-card:hover,.content-choice-card.active{transform:translateY(-2px);border-color:#6366f1;box-shadow:0 14px 34px rgba(99,102,241,.15);}
.content-choice-icon{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#eef2ff,#ecfeff);color:#4f46e5;display:flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:14px;box-shadow:inset 0 0 0 1px rgba(99,102,241,.12);} 
.content-workflow-panel{display:none;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 8px 26px rgba(15,23,42,.05);margin-bottom:16px;}
.content-workflow-panel.active{display:block;}
.book-flow-step{display:none}.book-flow-step.active{display:block}.modal-wizard-stepper{display:flex;gap:8px;flex-wrap:wrap}.modal-wizard-stepper span{border:1px solid #e2e8f0;border-radius:999px;padding:6px 10px;background:#f8fafc;font-size:12px}.modal-wizard-stepper span.active{background:#eef2ff;border-color:#6366f1;color:#4338ca;font-weight:700}.content-modal-choice{border:1px solid #e5e7eb;border-radius:14px;padding:14px;cursor:pointer;background:#fff}.content-modal-choice.active{border-color:#6366f1;background:#eef2ff}
.wizard-steps{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0 16px;}
.wizard-steps .step{background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:6px 10px;font-size:12px;color:#334155;}
.wizard-steps .step strong{color:#4f46e5;}
.publish-status-bar{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
.publish-status-bar .badge{font-size:11px;padding:6px 8px;}
.studio-tree-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.05);}
.tree-item-row{border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:9px 10px;margin-bottom:8px;}
.tree-item-row:hover{border-color:#c7d2fe;background:#fafbff;}
.node-click.active-node .tree-item-row{border-color:#6366f1;background:#eef2ff;}
.node-type-icon{display:inline-flex;width:22px;height:22px;border-radius:7px;align-items:center;justify-content:center;background:#f1f5f9;margin-right:6px;}
.editor-toolbar-hint{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:10px;margin-bottom:10px;}
.drag-handle{cursor:grab;user-select:none;color:#94a3b8;font-weight:700;padding:2px 6px;line-height:1;}
.drag-handle:active{cursor:grabbing;}
.sortable-ghost{opacity:.4;}
@media(max-width:768px){.content-studio-hero{padding:16px}.wizard-steps .step{font-size:11px}.content-choice-card{margin-bottom:10px}}

/* Modern Content Publishing Workspace */
.content-publish-shell{display:grid;grid-template-columns:360px minmax(0,1fr);gap:18px;align-items:start;}
.content-left-panel{background:#f8fafc;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 14px 36px rgba(15,23,42,.06);position:sticky;top:88px;max-height:calc(100vh - 110px);display:flex;flex-direction:column;overflow:hidden;}
.content-left-header{padding:16px;border-bottom:1px solid #e5e7eb;background:linear-gradient(135deg,#ffffff,#eef2ff);}
.content-left-title{display:flex;align-items:center;justify-content:space-between;gap:10px;}
.content-search-box{position:relative;margin-top:12px;}
.content-search-box input{border-radius:999px;padding-left:36px;background:#fff;}
.content-search-box:before{content:'🔍';position:absolute;left:13px;top:8px;z-index:2;font-size:13px;}

.course-mode-tree{margin-top:12px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;overflow:hidden;}
.course-mode-parent{width:100%;border:0;background:#eef2ff;color:#0f172a;display:flex;align-items:center;gap:8px;padding:9px 10px;font-weight:700;text-align:left;cursor:pointer;}
.course-mode-parent:hover{background:#e0e7ff;}
.course-mode-arrow{width:20px;height:20px;border-radius:7px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:#4f46e5;transition:.15s ease;}
.course-mode-tree.collapsed .course-mode-arrow{transform:rotate(-90deg);}
.course-mode-children{display:block;padding:8px;border-top:1px solid #e5e7eb;}
.course-mode-tree.collapsed .course-mode-children{display:none;}

.course-mode-item{width:100%;border:0;background:#fff;border-radius:10px;padding:8px 9px;text-align:left;display:flex;align-items:center;gap:8px;color:#0f172a;cursor:pointer;margin-bottom:4px;}
.course-mode-item:hover,.course-mode-item.active{background:#f8fafc;color:#4f46e5;box-shadow:inset 0 0 0 1px #c7d2fe;}
.course-mode-item span:first-child{width:24px;height:24px;border-radius:8px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;}
.course-filter-title{font-size:12px;color:#64748b;margin:10px 2px 8px;display:flex;justify-content:space-between;gap:8px;}
.course-filter-title strong{color:#0f172a;}

.course-create-tree{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:10px;box-shadow:0 10px 24px rgba(15,23,42,.04);}
.course-tree-row{display:flex;align-items:center;gap:8px;padding:7px 6px;border-radius:10px;color:#0f172a;}
.course-root-row{background:#eef2ff;}
.course-section-row{background:#f8fafc;margin-top:6px;}
.course-tree-toggle{width:20px;height:20px;border-radius:7px;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:#4f46e5;font-size:11px;flex:0 0 20px;}
.course-tree-icon{width:26px;height:26px;border-radius:9px;background:#fff;border:1px solid #e5e7eb;display:inline-flex;align-items:center;justify-content:center;flex:0 0 26px;}
.course-tree-text{font-size:13px;}
.course-tree-children{margin-left:18px;border-left:1px dashed #cbd5e1;padding-left:10px;}
.course-action-children{display:flex;flex-direction:column;gap:8px;margin-top:8px;}
.left-action-card{width:100%;border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:10px 12px;text-align:left;display:flex;gap:10px;align-items:center;cursor:pointer;transition:.16s ease;color:#0f172a;}
.left-action-card:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(15,23,42,.08);border-color:#c7d2fe;}
.left-action-icon{width:34px;height:34px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:#eef2ff;flex:0 0 34px;}
.left-action-card strong{display:block;font-size:13px;}
.left-action-card small{display:block;color:#64748b;font-size:11px;line-height:1.2;margin-top:2px;}
.left-action-upload .left-action-icon{background:#ecfeff;}
.left-action-blog .left-action-icon{background:#fff7ed;}
.content-tree-scroll{padding:12px 10px 18px;overflow:auto;}
.content-right-panel{min-width:0;background:#fff;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 14px 36px rgba(15,23,42,.06);overflow:hidden;}
.content-editor-header{padding:18px 20px;border-bottom:1px solid #e5e7eb;background:linear-gradient(135deg,#ffffff,#f8fafc);display:flex;justify-content:space-between;gap:12px;align-items:flex-start;}
.content-editor-body{padding:18px 20px;}
.content-empty-state{min-height:360px;display:flex;align-items:center;justify-content:center;text-align:center;color:#64748b;background:linear-gradient(135deg,#f8fafc,#fff);border:1px dashed #cbd5e1;border-radius:16px;}
.content-tree-modern{margin:0;padding-left:0;}
.content-tree-modern .content-tree-modern{padding-left:18px;margin-top:6px;border-left:1px dashed #dbe3ef;}
.tree-item-modern{margin-bottom:6px;}
.tree-row-modern{display:flex;align-items:center;gap:6px;border:1px solid transparent;border-radius:12px;padding:5px 6px;transition:.15s ease;background:transparent;}
.tree-row-modern:hover{background:#fff;border-color:#dbeafe;box-shadow:0 8px 22px rgba(59,130,246,.08);}
.tree-toggle-modern,.tree-toggle-spacer{width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 24px;border:0;background:#eef2ff;color:#4f46e5;border-radius:8px;font-size:12px;}
.tree-toggle-modern.collapsed{transform:rotate(-90deg);}
.tree-main-modern{display:flex;align-items:center;gap:8px;min-width:0;flex:1;text-decoration:none!important;color:#0f172a;}
.tree-icon-modern{width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #e5e7eb;border-radius:9px;flex:0 0 26px;}
.tree-text-modern{min-width:0;display:flex;flex-direction:column;line-height:1.15;}
.tree-title-modern{font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tree-path-modern{font-size:10.5px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:185px;}
.tree-status-modern{font-size:9.5px;margin-left:auto;}
.tree-actions-modern{display:flex;gap:4px;opacity:0;transition:.15s ease;}
.tree-row-modern:hover .tree-actions-modern{opacity:1;}
.tree-action-btn{width:24px;height:24px;border:0;border-radius:8px;background:#ecfeff;color:#0891b2;font-weight:800;line-height:1;cursor:pointer;}
.tree-action-danger{background:#fff1f2;color:#e11d48;}
.tree-action-btn:hover{filter:brightness(.96);}
.node-click.active-node .tree-title-modern{color:#4f46e5;}
.content-status-pills .badge{padding:6px 8px;border-radius:999px;}
.sticky-editor-actions{position:sticky;bottom:0;background:rgba(255,255,255,.96);border-top:1px solid #e5e7eb;padding-top:12px;z-index:5;}
@media(max-width:992px){.content-publish-shell{grid-template-columns:1fr}.content-left-panel{position:relative;top:0;max-height:none}.tree-actions-modern{opacity:1}}

</style>


<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:12px;">
      <div>
        <h4 class="header-title mb-1">Publish Course</h4>
        <p class="text-muted mb-0">Create course content, upload notes, or write blogs from the left panel. The editor workspace remains on the right.</p>
      </div>
    </div>

    <div class="content-publish-shell">
      <aside class="content-left-panel">
        <div class="content-left-header">
          <div class="content-left-title">
            <div>
              <h5 class="mb-0">📚 Publish Course</h5>
              <small class="text-muted">Create and manage course content</small>
            </div>
          </div>
          <div class="course-mode-tree collapsed" id="courseModeTree">
            <button type="button" class="course-mode-parent" id="btnToggleCourseMode" aria-expanded="false">
              <span class="course-mode-arrow">▾</span>
              <span>🧩 Create Course</span>
            </button>
            <div class="course-mode-children">
              <button type="button" class="course-mode-item" data-content-mode="notes"><span>📝</span><strong>Create Notes</strong></button>
              <button type="button" class="course-mode-item" data-content-mode="upload"><span>📤</span><strong>Upload Notes</strong></button>
              <button type="button" class="course-mode-item" data-content-mode="blog"><span>✍️</span><strong>Create Blog</strong></button>
            </div>
          </div>
          <div class="content-search-box">
            <input type="text" id="contentTreeSearch" class="form-control form-control-sm" placeholder="Search visible content...">
          </div>
          <div class="course-filter-title"><strong id="courseFilterLabel">All Course Content</strong><span id="courseFilterHint">Select Create Notes or Create Blog to filter.</span></div>
        </div>
        <div class="content-tree-scroll">
          <?php if (empty($root_nodes)): ?>
            <div class="alert alert-warning mb-0">No content found. Use <b>Create Notes</b>, <b>Upload Notes</b>, or <b>Create Blog</b> to start.</div>
          <?php else: ?>
            <?php foreach ($root_nodes as $r): ?>
              <?php
                $rid = (int)$r['node_id'];
                $rTitle = htmlspecialchars($r['title'] ?? 'Untitled');
                $rTitleJs = htmlspecialchars($r['title'] ?? '', ENT_QUOTES);
                $rPathJs = htmlspecialchars($r['full_path'] ?? '', ENT_QUOTES);
                $rStatusJs = htmlspecialchars($r['status'] ?? 'draft', ENT_QUOTES);
                $rBadge = ($r['status'] ?? '') === 'published' ? 'success' : ((($r['status'] ?? '') === 'pending') ? 'info' : 'secondary');
              ?>
              <div class="rootTreeBox" id="rootTree-<?php echo $rid; ?>" data-content-kind="<?php echo (($r['content_type'] ?? '') === 'article') ? 'blog' : 'notes'; ?>">
                <div class="tree-row-modern root-row-modern">
                  <?php if (isset($children[$rid])): ?>
                    <button type="button" class="tree-toggle-modern tree-toggle" data-target="kids-<?php echo $rid; ?>" title="Expand/Collapse">▾</button>
                  <?php else: ?>
                    <span class="tree-toggle-spacer"></span>
                  <?php endif; ?>
                  <a href="javascript:void(0);" class="node-click tree-main-modern" onclick="selectNode('<?php echo $rid; ?>','<?php echo $rTitleJs; ?>','<?php echo $rPathJs; ?>','<?php echo (int)($r['level'] ?? 0); ?>','<?php echo $rStatusJs; ?>','<?php echo (int)($r['is_root'] ?? 1); ?>')">
                    <span class="tree-icon-modern">📘</span>
                    <span class="tree-text-modern"><span class="tree-title-modern"><?php echo $rTitle; ?></span><span class="tree-path-modern"><?php echo htmlspecialchars($r['full_path'] ?? ''); ?></span></span>
                  </a>
                  <div class="tree-actions-modern">
                    <button type="button" class="tree-action-btn" title="Add Chapter" onclick="openAddChildNode(<?php echo $rid; ?>, '<?php echo $rTitleJs; ?>', '<?php echo $rPathJs; ?>'); return false;">＋</button>
                    <button type="button" class="tree-action-btn tree-action-danger" title="Delete Book" onclick="openDeleteNode(<?php echo $rid; ?>, '<?php echo $rTitleJs; ?>', '<?php echo $rPathJs; ?>', <?php echo (int)($r['level'] ?? 0); ?>, '<?php echo $rStatusJs; ?>', <?php echo (int)($r['is_root'] ?? 1); ?>); return false;">−</button>
                  </div>
                </div>
                <?php if (isset($children[$rid])): ?>
                  <div id="kids-<?php echo $rid; ?>" class="tree-kids-modern tree-kids">
                    <?php render_tree($rid, $children); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </aside>
      <section>
        <div class="card mb-3 border-0 shadow-sm">
          <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
              <div>
                <h5 class="mb-1">Selected Item</h5>
                <div id="selectedNodeInfo" class="text-muted">Select a book, chapter, page, or article from the left tree.</div>
              </div>
              <div id="selectedNodeActions" class="d-flex" style="display:none;gap:8px;">
                <button class="btn btn-sm btn-warning" type="button" data-toggle="modal" data-target="#editNodeModal">Rename</button>
                <a id="deleteNodeBtn" href="javascript:void(0)" class="btn btn-sm btn-danger" onclick="return deleteSelectedNode(0);">Move to Recycle Bin</a>
              </div>
            </div>
          </div>
        </div>
<!-- Page / Article Editor -->
<div class="content-right-panel">
  <div class="content-editor-body">
    <div class="content-editor-header mb-3"><div><h5 class="mb-1">Right Editor Workspace</h5>
    <p class="text-muted small mb-0">Select a page from the left tree. Existing CKEditor, DOCX import, save draft, review and publish workflow are kept.</p></div><div class="content-status-pills"><span class="badge badge-light border">Book → Chapter → Page</span></div></div>
    <div id="editorPanel" style="display:none;">
      <form id="contentPageForm" method="post" action="<?php echo site_url('admin/content_page_save'); ?>" enctype="multipart/form-data">

        <?php if (isset($this->security)): ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        <?php endif; ?>
        <input type="hidden" name="node_id" id="content_node_id">
        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap" role="status">
          <div>
            <strong>Publishing Status</strong>
            <span class="badge badge-secondary-lighten ml-2" id="contentPageStateBadge">Draft</span>
            <span class="text-muted small ml-2">Use Save Draft while editing, Preview before publishing, then submit/publish when ready.</span>
          </div>
          <small id="contentDraftAutosaveStatus" class="text-muted">Autosave ready</small>
        </div>
        <div id="contentAdminCommentBox" class="alert alert-warning py-2" style="display:none;"></div>

        <div class="form-group">
	          <label for="editor">Write Content</label>
	          <!-- DOCX Import (as-is) -->
	          <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;margin-bottom:8px;">
	            <button type="button" class="btn btn-sm btn-outline-primary" id="btnUploadDocx">Import DOCX into Editor</button>
	            <input type="file" id="docxFileInput" accept=".docx" style="display:none;">
	            <small id="docxStatus" class="text-muted" style="display:none;"></small>
              <span class="small text-muted">Media: Insert Image • Table • Video • Code Block • PDF link from CKEditor toolbar</span>
	          </div>
          <textarea name="html" id="editor" class="form-control" rows="10"></textarea>
        </div>

        <div class="row">
          <div class="col-md-4">
            <label>Meta Title</label>
            <input type="text" name="meta_title" id="content_meta_title" class="form-control" maxlength="120">
          </div>
          <div class="col-md-4">
            <label>Meta Description</label>
            <input type="text" name="meta_description" id="content_meta_description" class="form-control" maxlength="180">
          </div>
          <div class="col-md-4">
            <label>Meta Keywords</label>
            <input type="text" name="meta_keywords" id="content_meta_keywords" class="form-control">
          </div>
        </div>
          <div class="col-md-12 mt-2">
            <label for="content_canonical_url">Canonical URL</label>
            <input type="url" name="canonical_url" id="content_canonical_url" class="form-control" placeholder="https://example.com/page-url">
          </div>
        </div>

        <div class="form-group mt-3">
          <label for="og_image">OG Image</label>
          
		  
		  <input type="file" name="og_image" id="og_image" class="form-control" accept="image/*">
			<small class="text-muted">Recommended: 1200×630 (OG). Max 2MB.</small>

			<div class="mt-2" id="ogPreviewBox" style="display:none;">
			  <img id="ogPreview" src="" style="max-width:220px; border:1px solid #ddd; padding:4px; border-radius:4px;">
			  <div class="small text-muted mt-1" id="ogMeta"></div>
			</div>
		  		  
		  
        </div>
        <div class="sticky-editor-actions d-flex align-items-center flex-wrap" style="gap: 8px;">
          <button type="button" class="btn btn-outline-secondary" id="btnPreviewContentPage">Preview</button>
          <button type="submit" class="btn btn-secondary" name="save_status" value="draft">Save Draft</button>
          <button type="submit" class="btn btn-info" name="save_status" value="pending">Submit for Review</button>
          <button type="submit" class="btn btn-success" name="save_status" value="published">Publish</button>
          <button type="button" class="btn btn-light" id="btnClearContentDraft">Clear Local Draft</button>
        </div>
      </form>
    </div>

    <div id="editorPlaceholder"><div class="content-empty-state"><div><h5>Select a page to start editing</h5><p class="mb-0">Use the left panel to open a book, chapter, or page. Click the ＋ icon to add new content.</p></div></div></div>
  </div>
</div>

<!-- Whiteboard UI Below Tree -->


      </section>
    </div>
  </div>
</div>

<!-- Quick Create Online Book Modal -->
<div class="modal fade" id="quickBookModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/create_book'); ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Online Book</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Book Name</label>
          <input class="form-control" name="book_title" placeholder="e.g. Cloud Computing" required>
        </div>
        <div class="form-row">
          <div class="col-md-6 form-group">
            <label>First Chapter</label>
            <input class="form-control" name="chapter_title" value="Introduction" required>
          </div>
          <div class="col-md-6 form-group">
            <label>First Page</label>
            <input class="form-control" name="page_title" value="Overview" required>
          </div>
        </div>
        <div class="alert alert-light border small mb-0">The book will appear in the left tree. Use ＋ to add chapters/pages and edit content on the right.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Book</button>
      </div>
    </form>
  </div>
</div>

<!-- Upload Notes Modal -->
<div class="modal fade" id="uploadNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" enctype="multipart/form-data" action="<?php echo site_url('admin/content_nodes/import_book'); ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload Notes</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Upload Into</label>
          <select class="form-control" name="target_node_id" id="upload_target_node_id" required>
            <option value="">Select book / chapter / page...</option>
            <?php foreach (($nodes ?? []) as $uploadNode): ?>
              <option value="<?php echo (int)($uploadNode['node_id'] ?? 0); ?>">
                <?php echo htmlspecialchars(($uploadNode['full_path'] ?? $uploadNode['title'] ?? 'Untitled')); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Choose the book, chapter, or page where this uploaded note should be added.</small>
        </div>
        <div class="form-group">
          <label>Upload Mode</label>
          <select class="form-control" name="upload_mode">
            <option value="append_to_page">Append content into selected page</option>
            <option value="create_page_under_chapter">Create new page under selected chapter</option>
            <option value="create_book">Create new notes/book from file</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notes Title</label>
          <input class="form-control" name="book_title" placeholder="e.g. Cloud Notes / AI Notes">
          <small class="text-muted">Optional. If empty, the file name can be used.</small>
        </div>
        <div class="form-group">
          <label>File Path / Choose File</label>
          <input class="form-control" type="file" name="book_file" accept=".pdf,.doc,.docx" required>
          <small class="text-muted">Supported: PDF, DOC, DOCX.</small>
        </div>
        <div class="alert alert-light border small mb-0">After upload, select the target page from the left tree and continue editing from the right CKEditor workspace.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload Notes</button>
      </div>
    </form>
  </div>
</div>

<!-- Create Blog Modal -->
<div class="modal fade" id="quickBlogModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/create_article'); ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Blog</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Blog Title</label>
          <input class="form-control" name="article_title" placeholder="e.g. Introduction to LangChain" required>
        </div>
        <div class="alert alert-light border small mb-0">After creating the blog, select it in the left tree and write/edit content in the right CKEditor workspace.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Blog</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Node Modal (Bootstrap 4) -->
<div class="modal fade" id="addNodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/add'); ?>" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add Chapter / Page</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <!-- Parent is auto-filled from selected node -->
        <input type="hidden" name="parent_id" id="parent_id" value="">

        <div class="alert alert-light">
          <b>Create under:</b> <span id="parentPreview" class="text-muted">No node selected</span>
          <div class="text-muted small mt-1">Tip: click a book or chapter first, then add a chapter/page under it.</div>
        </div>

        <div class="form-group">
          <label>Chapter / Page Title</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 2 / Practice Questions / Summary" required>
        </div>

        <div class="alert alert-warning mb-0">
          <b>Publishing:</b> New chapters and pages start as drafts. Use the editor buttons to save, submit for review, or publish.
        </div>

      </div>

      <div class="modal-footer">
        <!-- FIXED: Cancel button should be a normal button with data-dismiss -->
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" onclick="return validateParentSelected();">Create Chapter / Page</button>
      </div>

    </form>
  </div>
</div>

<!-- Edit Node Modal (Bootstrap 4) -->
<div class="modal fade" id="editNodeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="post" action="<?php echo site_url('admin/content_nodes/update'); ?>" class="modal-content">


      <div class="modal-header">
        <h5 class="modal-title">Rename Content Item</h5>
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
          This renames the selected book, chapter, page, or article while keeping the existing content.
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
        <h5 class="modal-title">Advanced: Add Content Area</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label>Content Area Key (advanced, unique)</label>
          <input type="text" name="root_key" class="form-control" placeholder="e.g. programming" required>
          <small class="text-muted">Lowercase recommended. Used in full_path.</small>
        </div>

        <div class="form-group">
          <label>Root Title / Content Area Name</label>
          <input type="text" name="root_title" class="form-control" placeholder="e.g. Programming" required>
        </div>

        <div class="form-group">
          <label>Max Depth</label>
          <input type="number" name="max_depth" class="form-control" min="1" value="3" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Root Area</button>
      </div>

    </form>
  </div>
</div>
<?php endif; ?>

<?php
// Safe lightweight JSON for JavaScript. Do NOT expose html/content fields here;
// saved HTML can contain </script> and break the page.
$js_nodes = [];
foreach (($nodes ?? []) as $n) {
    $js_nodes[] = [
        'node_id'      => (int)($n['node_id'] ?? 0),
        'parent_id'    => (int)($n['parent_id'] ?? 0),
        'title'        => (string)($n['title'] ?? ''),
        'full_path'    => (string)($n['full_path'] ?? ''),
        'level'        => (int)($n['level'] ?? 0),
        'status'       => (string)($n['status'] ?? 'draft'),
        'is_root'      => (int)($n['is_root'] ?? 0),
        'content_type' => (string)($n['content_type'] ?? ''),
        'admin_remark' => (string)($n['admin_remark'] ?? ''),
        'review_note'  => (string)($n['review_note'] ?? ''),
    ];
}
?>
<script>
(function () {
  'use strict';

  var contentStudioNodes = <?php echo json_encode($js_nodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
  window.contentStudioNodes = contentStudioNodes;

  var selectedNodeId = null;
  var selectedIsRoot = 0;
  window.selectedNodeId = null;
  window.selectedIsRoot = 0;

  function byId(id) { return document.getElementById(id); }
  function asInt(v) { var n = parseInt(v, 10); return isNaN(n) ? 0 : n; }
  function esc(v) {
    return String(v || '').replace(/[&<>"']/g, function (ch) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
    });
  }

  window.friendlyContentStatus = function (status) {
    var labels = {draft:'Draft',pending:'Submitted for Review',in_review:'In Review',approved:'Approved',published:'Published',rejected:'Rejected',update_required:'Changes Requested',on_hold:'In Review',deleted:'Archived'};
    return labels[status] || String(status || 'Draft').replace(/_/g, ' ');
  };

  window.contentStatusBadgeClass = function (status) {
    if (status === 'published') return 'badge-success';
    if (status === 'pending' || status === 'in_review') return 'badge-info';
    if (status === 'rejected') return 'badge-danger';
    if (status === 'update_required' || status === 'on_hold') return 'badge-warning';
    if (status === 'deleted') return 'badge-dark';
    return 'badge-secondary';
  };

  window.escapeStudioText = esc;

  function getNodeChildren(parentId) {
    parentId = asInt(parentId);
    return (contentStudioNodes || []).filter(function (n) { return asInt(n.parent_id || 0) === parentId; });
  }

  function fillSelect(el, items, emptyText) {
    if (!el) return;
    el.innerHTML = '';
    var empty = document.createElement('option');
    empty.value = '';
    empty.textContent = emptyText;
    el.appendChild(empty);
    items.forEach(function (n) {
      var opt = document.createElement('option');
      opt.value = n.node_id;
      opt.textContent = (n.title || '') + (n.status ? ' (' + n.status + ')' : '');
      el.appendChild(opt);
    });
  }

  function setWizardStep(stepNo) {
    document.querySelectorAll('.book-flow-step').forEach(function (s) { s.classList.remove('active'); });
    var el = byId('bookStep' + stepNo);
    if (el) el.classList.add('active');
    document.querySelectorAll('.modal-wizard-stepper span').forEach(function (s) { s.classList.remove('active'); });
    var lab = document.querySelector('.modal-wizard-stepper span[data-step-label="' + stepNo + '"]');
    if (lab) lab.classList.add('active');
  }

  window.contentDraftKey = function (nodeId) { return 'lvalues_content_page_draft_' + String(nodeId || ''); };

  window.getContentDraft = function (nodeId) {
    if (!nodeId) return null;
    try { var raw = localStorage.getItem(window.contentDraftKey(nodeId)); return raw ? JSON.parse(raw) : null; }
    catch (e) { return null; }
  };

  window.setContentDraftStatus = function (msg) {
    var el = byId('contentDraftAutosaveStatus');
    if (el) el.textContent = msg || 'Autosave ready';
  };

  window.populateContentMeta = function (data) {
    data = data || {};
    var map = {
      content_meta_title: data.meta_title || '',
      content_meta_description: data.meta_description || '',
      content_meta_keywords: data.meta_keywords || '',
      content_canonical_url: data.canonical_url || ''
    };
    Object.keys(map).forEach(function (id) { var el = byId(id); if (el) el.value = map[id]; });
  };

  window.collectContentMeta = function () {
    function v(id) { var el = byId(id); return el ? el.value : ''; }
    return {meta_title:v('content_meta_title'), meta_description:v('content_meta_description'), meta_keywords:v('content_meta_keywords'), canonical_url:v('content_canonical_url')};
  };

  window.saveContentDraft = function () {
    var nodeId = selectedNodeId || (byId('content_node_id') || {}).value;
    if (!nodeId) return;
    var draft = window.collectContentMeta();
    draft.html = window.getFullEditorData ? window.getFullEditorData() : ((byId('editor') || {}).value || '');
    draft.savedAt = new Date().toISOString();
    try {
      localStorage.setItem(window.contentDraftKey(nodeId), JSON.stringify(draft));
      window.setContentDraftStatus('Local draft saved ' + new Date().toLocaleTimeString());
    } catch (e) { window.setContentDraftStatus('Local draft not saved'); }
  };

  window.clearContentDraft = function (nodeId, eraseEditor) {
    nodeId = nodeId || selectedNodeId || (byId('content_node_id') || {}).value;
    if (!nodeId) return;

    // Remove browser/localStorage draft for this page/article.
    try { localStorage.removeItem(window.contentDraftKey(nodeId)); } catch (e) {}

    // When user clicks Clear Local Draft, also clear the visible CKEditor area.
    // This does NOT delete saved database content until user clicks Save/Publish.
    if (eraseEditor) {
      if (window.setFullEditorData) {
        window.setFullEditorData('');
      } else if (byId('editor')) {
        byId('editor').value = '';
      }

      // Clear SEO fields from screen/local draft too.
      ['content_meta_title','content_meta_description','content_meta_keywords','content_canonical_url'].forEach(function(id){
        if (byId(id)) byId(id).value = '';
      });

      if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
        try { CKEDITOR.instances.editor.updateElement(); } catch (e) {}
      }
    }

    window.setContentDraftStatus(eraseEditor ? 'Local draft cleared and editor emptied' : 'Local draft cleared');
  };

  window.buildPreviewHtml = function (bodyHtml) {
    var title = (byId('content_meta_title') || {}).value || 'Content Preview';
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' +
      '<title>' + esc(title) + '<\/title>' +
      '<style>body{font-family:Arial,sans-serif;max-width:960px;margin:32px auto;padding:0 20px;line-height:1.65;color:#111827}img,iframe,video{max-width:100%;height:auto}table{border-collapse:collapse;max-width:100%}td,th{border:1px solid #d1d5db;padding:8px}blockquote{border-left:4px solid #c7d2fe;margin-left:0;padding-left:16px;color:#475569}<\/style>' +
      '<\/head><body>' + (bodyHtml || '') + '<\/body><\/html>';
  };

  window.selectNode = function (nodeId, title, fullPath, level, status, isRoot) {
    nodeId = asInt(nodeId);
    if (!nodeId) { alert('Invalid nodeId received: ' + nodeId); return; }
    selectedNodeId = nodeId;
    selectedIsRoot = asInt(isRoot);
    window.selectedNodeId = selectedNodeId;
    window.selectedIsRoot = selectedIsRoot;

    title = String(title || '');
    fullPath = String(fullPath || '');
    status = String(status || 'draft');

    var selectedNode = (contentStudioNodes || []).find(function (n) { return asInt(n.node_id) === nodeId; }) || {};
    var note = selectedNode.admin_remark || selectedNode.review_note || '';

    var info = byId('selectedNodeInfo');
    if (info) {
      info.innerHTML = '<b>' + esc(title) + '</b><br><span class="text-muted small">' + esc(fullPath) + '</span>' +
        (note ? '<div class="alert alert-warning mt-2 mb-0 py-2"><strong>Admin comments:</strong> ' + esc(note) + '</div>' : '');
    }

    if (byId('parent_id')) byId('parent_id').value = nodeId;
    if (byId('parentPreview')) byId('parentPreview').innerHTML = '<b>' + esc(title) + '</b> <span class="text-muted">(' + esc(fullPath) + ')</span>';
    if (byId('edit_node_id')) byId('edit_node_id').value = nodeId;
    if (byId('edit_title')) byId('edit_title').value = title;
    if (byId('editorPanel')) byId('editorPanel').style.display = 'block';
    if (byId('editorPlaceholder')) byId('editorPlaceholder').style.display = 'none';
    if (byId('content_node_id')) byId('content_node_id').value = nodeId;

    fetch('<?php echo site_url('admin/content_page_get/'); ?>' + nodeId, {credentials:'same-origin'})
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        var html = '';
        if (resp && resp.ok && resp.data) html = resp.data.html || '';

        var badge = byId('contentPageStateBadge');
        if (badge) {
          var badgeStatus = (resp && resp.data && (resp.data.status || resp.data.review_status)) || status || 'draft';
          badge.className = 'badge ml-2 ' + window.contentStatusBadgeClass(badgeStatus);
          badge.textContent = window.friendlyContentStatus(badgeStatus);
        }

        var contentNote = (resp && resp.data && (resp.data.admin_remark || resp.data.review_note)) || note || '';
        var noteBox = byId('contentAdminCommentBox');
        if (noteBox) {
          noteBox.style.display = contentNote ? 'block' : 'none';
          noteBox.textContent = contentNote ? ('Admin comments: ' + contentNote) : '';
        }

        window.populateContentMeta((resp && resp.data) || {});

        // Do not show browser confirm popup on every tree/menu click.
        // If a browser local draft exists, keep it available in localStorage,
        // but load saved database content into CKEditor unless the user explicitly saves/clears later.
        var localDraft = window.getContentDraft(nodeId);
        if (localDraft && localDraft.savedAt) {
          window.setContentDraftStatus('Local draft exists from ' + new Date(localDraft.savedAt).toLocaleString());
        }

        if (window.setFullEditorData) window.setFullEditorData(html);
        else if (byId('editor')) byId('editor').value = html;
        if (window.ensureFullCkEditor) window.ensureFullCkEditor();
      })
      .catch(function () {
        if (window.setFullEditorData) window.setFullEditorData('');
        if (window.ensureFullCkEditor) window.ensureFullCkEditor();
      });

    var actions = byId('selectedNodeActions');
    if (actions) actions.style.display = 'flex';
  };

  window.validateParentSelected = function () {
    if (!byId('parent_id') || !byId('parent_id').value) {
      alert('Please select a parent node from the tree first (click a node in the Tree).');
      return false;
    }
    return true;
  };


  window.openAddChildNode = function (nodeId, title, fullPath) {
    nodeId = asInt(nodeId);
    if (!nodeId) return false;
    selectedNodeId = nodeId;
    window.selectedNodeId = nodeId;
    if (byId('parent_id')) byId('parent_id').value = nodeId;
    if (byId('parentPreview')) byId('parentPreview').innerHTML = '<b>' + esc(title) + '</b> <span class="text-muted">(' + esc(fullPath) + ')</span>';
    if (window.jQuery) jQuery('#addNodeModal').modal('show');
    return false;
  };

  window.openDeleteNode = function (nodeId, title, fullPath, level, status, isRoot) {
    if (window.selectNode) window.selectNode(nodeId, title, fullPath, level, status, isRoot);
    setTimeout(function(){ if (window.deleteSelectedNode) window.deleteSelectedNode(0); }, 50);
    return false;
  };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.content-choice-card').forEach(function (card) {
      card.addEventListener('click', function () {
        document.querySelectorAll('.content-choice-card').forEach(function (c) { c.classList.remove('active'); });
        document.querySelectorAll('.content-workflow-panel').forEach(function (p) { p.classList.remove('active'); });
        card.classList.add('active');
        var modalId = card.getAttribute('data-modal');
        if (modalId && window.jQuery) { jQuery('#' + modalId).modal('show'); return; }
        var target = byId(card.getAttribute('data-workflow'));
        if (target) { target.classList.add('active'); target.scrollIntoView({behavior:'smooth', block:'start'}); }
      });
    });
  });

  document.addEventListener('click', function (e) {
    var choice = e.target.closest && e.target.closest('.content-modal-choice');
    if (choice) {
      document.querySelectorAll('.content-modal-choice').forEach(function (c) { c.classList.remove('active'); });
      choice.classList.add('active');
      var mode = choice.getAttribute('data-mode');
      if (byId('newBookBox') && byId('existingBookBox')) {
        byId('newBookBox').style.display = mode === 'newBook' ? 'block' : 'none';
        byId('existingBookBox').style.display = mode === 'existingBook' ? 'block' : 'none';
      }
    }

    if (e.target && e.target.classList.contains('tree-toggle')) {
      var box = byId(e.target.getAttribute('data-target'));
      if (box) {
        box.classList.toggle('d-none');
        e.target.classList.toggle('collapsed', box.classList.contains('d-none')); e.target.textContent = '▾';
      }
    }

    if (e.target && e.target.id === 'btnBookNext') {
      var bookId = byId('wizardBookSelect').value;
      if (!bookId) { alert('Please select a book first.'); return; }
      byId('wizardChapterParentId').value = bookId;
      fillSelect(byId('wizardChapterSelect'), getNodeChildren(bookId), 'Select chapter...');
      setWizardStep(2);
    }
    if (e.target && e.target.id === 'btnChapterBack') setWizardStep(1);
    if (e.target && e.target.id === 'btnChapterNext') {
      var chId = byId('wizardChapterSelect').value;
      if (!chId) { alert('Please select a chapter first.'); return; }
      byId('wizardPageParentId').value = chId;
      fillSelect(byId('wizardPageSelect'), getNodeChildren(chId), 'Select page...');
      setWizardStep(3);
    }
    if (e.target && e.target.id === 'btnPageBack') setWizardStep(2);
    if (e.target && e.target.id === 'btnOpenPageEditor') {
      var pageId = byId('wizardPageSelect').value;
      if (!pageId) { alert('Please select a page first.'); return; }
      var node = (contentStudioNodes || []).find(function (n) { return asInt(n.node_id) === asInt(pageId); });
      if (!node) { alert('Selected page not found.'); return; }
      window.selectNode(node.node_id, node.title || '', node.full_path || '', node.level || 0, node.status || 'draft', node.is_root || 0);
      setWizardStep(4);
      if (window.jQuery) jQuery('#bookFlowModal').modal('hide');
      setTimeout(function () { var ep = byId('editorPanel'); if (ep) ep.scrollIntoView({behavior:'smooth', block:'start'}); }, 400);
    }
  });

  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'root_selector') {
      var rootId = e.target.value;
      document.querySelectorAll('.rootTreeBox').forEach(function (b) { b.style.display = 'none'; });
      var active = byId('rootTree-' + rootId);
      if (active) active.style.display = 'block';
      if (byId('selectedNodeActions')) byId('selectedNodeActions').style.display = 'none';
      if (byId('selectedNodeInfo')) byId('selectedNodeInfo').innerHTML = 'Click a node in the tree to manage it.';
      if (byId('parent_id')) byId('parent_id').value = '';
      if (byId('parentPreview')) byId('parentPreview').innerHTML = 'No node selected';
    }
  });
})();
</script>

<!-- CKEditor 4 loader for Book Page / Article Editor -->
<style>
  .cke_chrome{border-radius:10px!important;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.06)!important;border-color:#dbe3ef!important;}
  .cke_top{background:#fff!important;border-bottom:1px solid #e5e7eb!important;}
  .cke_contents{min-height:420px!important;}
  #ckUploadOverlay{font-family:Arial,sans-serif;}
</style>
<script>
(function(){
  var localCkeditorBasePath = "<?php echo base_url('assets/backend/ckeditor/'); ?>";
  var localCkeditorUrl = localCkeditorBasePath + 'ckeditor.js';
  var cdnCkeditorBasePath = 'https://cdn.ckeditor.com/4.25.1-lts/full-all/';
  var cdnCkeditorUrl = cdnCkeditorBasePath + 'ckeditor.js';
  var uploadUrl = "<?php echo site_url('admin/content_page_upload'); ?>";
  var docxUploadUrl = "<?php echo site_url('admin/doc-import/upload-docx'); ?>";

  var pendingEditorData = '';
  var editorReadyCallbacks = [];
  var ckeditorLoadPromise = null;
  var ckeditorLoadedFrom = '';

  function setDraftStatus(msg) {
    var el = document.getElementById('contentDraftAutosaveStatus');
    if (el && msg) el.textContent = msg;
  }

  function removeCkeditorScripts() {
    var scripts = document.querySelectorAll('script[data-lvalues-ckeditor-loader="1"]');
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i] && scripts[i].parentNode) scripts[i].parentNode.removeChild(scripts[i]);
    }
  }

  function loadScript(src, basePath, label) {
    return new Promise(function(resolve, reject) {
      if (window.CKEDITOR && typeof window.CKEDITOR.replace === 'function') {
        ckeditorLoadedFrom = ckeditorLoadedFrom || label;
        resolve();
        return;
      }
      window.CKEDITOR_BASEPATH = basePath;
      var script = document.createElement('script');
      script.src = src;
      script.async = false;
      script.setAttribute('data-lvalues-ckeditor-loader', '1');
      script.onload = function(){
        setTimeout(function(){
          if (window.CKEDITOR && typeof window.CKEDITOR.replace === 'function') {
            ckeditorLoadedFrom = label;
            resolve();
          } else {
            reject(new Error('CKEditor loaded but CKEDITOR.replace is unavailable: ' + label));
          }
        }, 50);
      };
      script.onerror = function(){ reject(new Error('CKEditor script failed: ' + src)); };
      document.head.appendChild(script);
    });
  }

  function ensureCkeditorLibrary() {
    if (window.CKEDITOR && typeof window.CKEDITOR.replace === 'function') return Promise.resolve();
    if (ckeditorLoadPromise) return ckeditorLoadPromise;

    // Use CDN full build first because the local folder often contains only ckeditor.js.
    // ckeditor.js returning HTTP 200 is not enough; CKEditor also needs config/skins/plugins.
    ckeditorLoadPromise = loadScript(cdnCkeditorUrl, cdnCkeditorBasePath, 'CDN full build')
      .catch(function(){
        removeCkeditorScripts();
        window.CKEDITOR = undefined;
        window.CKEDITOR_BASEPATH = localCkeditorBasePath;
        return loadScript(localCkeditorUrl, localCkeditorBasePath, 'local build');
      });
    return ckeditorLoadPromise;
  }

  function runReadyCallbacks(editor){
    while(editorReadyCallbacks.length){
      try { editorReadyCallbacks.shift()(editor); } catch(e) { console.error(e); }
    }
  }

  window.getFullEditorData = function(){
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
      try { return CKEDITOR.instances.editor.getData(); } catch(e) {}
    }
    var el = document.getElementById('editor');
    return el ? el.value : pendingEditorData;
  };

  window.setFullEditorData = function(html){
    pendingEditorData = html || '';
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
      try { CKEDITOR.instances.editor.setData(pendingEditorData); return; } catch(e) {}
    }
    var el = document.getElementById('editor');
    if (el) el.value = pendingEditorData;
  };

  window.destroyFullCkEditor = function(){
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
      try {
        CKEDITOR.instances.editor.updateElement();
        CKEDITOR.instances.editor.destroy(true);
      } catch (e) { console.error(e); }
    }
  };

  window.ensureFullCkEditor = function(){
    var el = document.getElementById('editor');
    if (!el) return Promise.resolve(null);

    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
      return Promise.resolve(CKEDITOR.instances.editor);
    }

    return ensureCkeditorLibrary().then(function(){
      if (!window.CKEDITOR || typeof CKEDITOR.replace !== 'function') {
        throw new Error('CKEditor 4 could not be loaded.');
      }
      if (CKEDITOR.instances && CKEDITOR.instances.editor) return CKEDITOR.instances.editor;

      CKEDITOR.disableAutoInline = true;
      CKEDITOR.replace('editor', {
        height: 430,
        allowedContent: true,
        extraAllowedContent: '*(*);*{*}',
        versionCheck: false,
        removePlugins: 'easyimage,cloudservices',
        filebrowserUploadUrl: uploadUrl,
        uploadUrl: uploadUrl,
        filebrowserUploadMethod: 'form',
        pasteFromWordRemoveFontStyles: false,
        pasteFromWordRemoveStyles: false,
        toolbar: [
          { name: 'document', items: ['Source','Preview','Print'] },
          { name: 'clipboard', items: ['Undo','Redo','Cut','Copy','Paste','PasteText','PasteFromWord'] },
          { name: 'editing', items: ['Find','Replace','SelectAll'] },
          '/',
          { name: 'styles', items: ['Format','Font','FontSize'] },
          { name: 'basicstyles', items: ['Bold','Italic','Underline','Strike','Subscript','Superscript','RemoveFormat'] },
          { name: 'colors', items: ['TextColor','BGColor'] },
          { name: 'paragraph', items: ['NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
          '/',
          { name: 'links', items: ['Link','Unlink','Anchor'] },
          { name: 'insert', items: ['Image','Table','HorizontalRule','SpecialChar','PageBreak','Iframe'] },
          { name: 'tools', items: ['Maximize','ShowBlocks'] }
        ]
      });

      var inst = CKEDITOR.instances.editor;
      inst.on('instanceReady', function(){
        if (pendingEditorData) inst.setData(pendingEditorData);
        initDocxImport(inst);
        runReadyCallbacks(inst);
        setDraftStatus('CKEditor ready (' + (ckeditorLoadedFrom || 'loaded') + ')');
      });
      inst.on('change', function(){
        el.value = inst.getData();
        try { el.dispatchEvent(new Event('input', {bubbles:true})); } catch(e) {}
      });

      var form = document.getElementById('contentPageForm');
      if (form && form.getAttribute('data-ckeditor-submit-bound') !== '1') {
        form.setAttribute('data-ckeditor-submit-bound','1');
        form.addEventListener('submit', function(){
          if (CKEDITOR.instances.editor) CKEDITOR.instances.editor.updateElement();
        });
      }
      return inst;
    }).catch(function(error) {
      console.error('CKEditor initialization failed:', error);
      setDraftStatus('CKEditor failed - check Console');
      return null;
    });
  };

  window.LvaluesEditorApi = window.LvaluesEditorApi || {};
  window.LvaluesEditorApi.getData = window.getFullEditorData;
  window.LvaluesEditorApi.setData = window.setFullEditorData;

  document.addEventListener('DOMContentLoaded', function(){
    if (!document.getElementById('ckUploadOverlay')) {
      var overlay = document.createElement('div');
      overlay.id = 'ckUploadOverlay';
      overlay.style.cssText = 'display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.35); z-index:9999;';
      overlay.innerHTML = '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:15px 20px;border-radius:6px;box-shadow:0 5px 30px rgba(0,0,0,.25);font-weight:600;">Uploading... Please wait</div>';
      document.body.appendChild(overlay);
    }

    // Preload CKEditor and also initialize immediately if the editor panel is already visible.
    ensureCkeditorLibrary().then(function(){
      var panel = document.getElementById('editorPanel');
      if (panel && panel.style.display !== 'none') window.ensureFullCkEditor();
    }).catch(function(e){ console.error(e); });

    // Safety retry: when AJAX later shows the panel, this initializes CKEditor even if the earlier call was missed.
    var retryCount = 0;
    var timer = setInterval(function(){
      retryCount++;
      var panel = document.getElementById('editorPanel');
      if (panel && panel.style.display !== 'none' && !(window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor)) {
        window.ensureFullCkEditor();
      }
      if (retryCount >= 15 || (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor)) clearInterval(timer);
    }, 800);
  });

  function initDocxImport(editor){
    var btn = document.getElementById('btnUploadDocx');
    var input = document.getElementById('docxFileInput');
    var statusEl = document.getElementById('docxStatus');
    if (!btn || !input || btn.getAttribute('data-docx-bound') === '1') return;
    btn.setAttribute('data-docx-bound','1');
    function setStatus(msg, show) {
      if (!statusEl) return;
      statusEl.textContent = msg || '';
      statusEl.style.display = show ? 'inline-block' : 'none';
    }
    btn.addEventListener('click', function(){ input.value = ''; input.click(); });
    input.addEventListener('change', function(){
      var f = input.files && input.files[0];
      if (!f) return;
      if (!/\.docx$/i.test(f.name)) { alert('Please select a .docx file only.'); return; }
      setStatus('Uploading & converting DOCX...', true);
      btn.disabled = true;
      var formData = new FormData();
      formData.append('docx_file', f);
      fetch(docxUploadUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function(r){ return r.text(); })
      .then(function(txt){
        try { return JSON.parse(txt); } catch(e) {
          var start = txt.indexOf('{'), end = txt.lastIndexOf('}');
          if (start !== -1 && end !== -1 && end > start) return JSON.parse(txt.substring(start, end + 1));
          throw new Error('Server returned non-JSON response');
        }
      })
      .then(function(resp){
        if (!resp || !resp.ok) {
          var msg = (resp && (resp.error || resp.message || resp.msg || resp.debug)) ? (resp.error || resp.message || resp.msg || resp.debug) : 'Server returned failure without details. Check application/logs.';
          throw new Error(msg);
        }
        var html = resp.html || (resp.data && resp.data.html) || '';
        if (!html) throw new Error('DOCX converted, but no HTML was returned.');
        if (editor && editor.setData) editor.setData(html);
        else window.setFullEditorData(html);
        setStatus('Imported ✅ Now click Save Draft / Publish.', true);
        setTimeout(function(){ setStatus('', false); }, 4000);
      })
      .catch(function(err){
        console.error('DOCX import failed', err);
        alert('DOCX import failed: ' + (err && err.message ? err.message : err));
        setStatus('', false);
      })
      .finally(function(){ btn.disabled = false; });
    });
  }

  function initCreateExamButton(){
    var btnExam = document.getElementById('btnCreateExam');
    if (!btnExam || btnExam.getAttribute('data-exam-bound') === '1') return;
    btnExam.setAttribute('data-exam-bound','1');
    btnExam.addEventListener('click', function(){
      var el = document.getElementById('content_node_id');
      var nodeId = el && el.value ? parseInt(el.value,10) : null;
      if (!nodeId || nodeId <= 0) { alert('Please select a node first, then click Create Exam.'); return; }
      window.location.href = "<?php echo site_url('admin/content-exam/'); ?>" + nodeId;
    });
  }
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  document.querySelectorAll('ul.content-tree[data-parent-id]').forEach(function(ul){
    new Sortable(ul, {
      animation: 150,
      handle: '.drag-handle',
      ghostClass: 'sortable-ghost',
      onEnd: function () {
        const parentId = Number(ul.getAttribute('data-parent-id')) || 0;
        const orderedIds = Array.from(ul.querySelectorAll(':scope > li.tree-item'))
          .map(li => Number(li.getAttribute('data-node-id')))
          .filter(n => Number.isInteger(n) && n > 0);

        if (!orderedIds.length) return;

        const orderData = new FormData();
        orderData.append('parent_id', parentId);
        orderData.append('ordered_ids', JSON.stringify(orderedIds));
        fetch(`<?php echo site_url('admin/update_node_order'); ?>`, {
          method: 'POST',
          body: orderData,
          credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(resp => {
          if (!resp || !resp.ok) {
            alert((resp && resp.message) ? resp.message : 'Failed to save order');
          }
        })
        .catch(() => alert('Network error while saving order'));
      }
    });
  });

});
</script>

<script>
function deleteSelectedNode(force = 0) {
  // Always read from the single source of truth:
  const nodeId = Number(window.selectedNodeId);

  if (!Number.isInteger(nodeId) || nodeId <= 0) {
    alert("No valid node selected. Please click a node first.");
    return;
  }

  if (!confirm("Move this content item to Recycle Bin? You can restore it later from the Recycle Bin.")) return;

 // fetch(`http://localhost/lvalues/admin/delete_node_safe/${nodeId}`, {
  const deleteData = new FormData();
  deleteData.append('force', force ? '1' : '0');
  fetch(`<?php echo site_url('admin/content_nodes_delete/'); ?>${nodeId}`, {
    method: "POST",
    body: deleteData,
    credentials: 'same-origin'
  })
    .then(r => r.json())
    .then(resp => {
      if (!resp.success) return alert(resp.message || "Delete failed");
      // refresh tree
      location.reload();
    })
    .catch(err => {
      alert("Network error. Check console.");
    });
}


</script>
<script>
// Preview, Save Draft safety, Submit/Publish support.
(function () {
  function currentNodeId() {
    var el = document.getElementById('content_node_id');
    return el && el.value ? parseInt(el.value, 10) : 0;
  }

  function updateTextareaFromEditor() {
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editor) {
      CKEDITOR.instances.editor.updateElement();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('contentPageForm');
    var previewBtn = document.getElementById('btnPreviewContentPage');
    var clearBtn = document.getElementById('btnClearContentDraft');

    if (previewBtn) {
      previewBtn.addEventListener('click', function () {
        updateTextareaFromEditor();
        var html = window.getFullEditorData ? window.getFullEditorData() : ((document.getElementById('editor') || {}).value || '');
        var win = window.open('', 'lvalues_content_preview', 'width=1100,height=750,scrollbars=yes,resizable=yes');
        if (!win) { alert('Popup blocked. Please allow popups for localhost/lvalues.'); return; }
        win.document.open();
        win.document.write(buildPreviewHtml(html));
        win.document.close();
        win.focus();
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        var nodeId = currentNodeId();
        if (!nodeId) { alert('Please select a page/article first.'); return; }
        if (!confirm('Clear the local draft and empty the editor on screen? Saved database content will not be deleted unless you click Save Draft/Publish after clearing.')) return;
        clearContentDraft(nodeId, true);
      });
    }

    if (form) {
      form.addEventListener('input', function(){ window.clearTimeout(window.__lvaluesDraftTimer); window.__lvaluesDraftTimer = window.setTimeout(saveContentDraft, 800); });
      form.addEventListener('change', function(){ window.clearTimeout(window.__lvaluesDraftTimer); window.__lvaluesDraftTimer = window.setTimeout(saveContentDraft, 800); });
      form.addEventListener('submit', function (e) {
        updateTextareaFromEditor();
        var nodeId = currentNodeId();
        if (!nodeId) {
          e.preventDefault();
          alert('Please select a page/article from the tree first.');
          return false;
        }
        try { localStorage.setItem('lvalues_last_content_node_id', String(nodeId)); } catch(err) {}
        saveContentDraft();
      });
    }

    // After Save Draft / Submit / Publish redirects back to this page, reopen the last selected page.
    setTimeout(function(){
      var lastId = 0;
      try { lastId = parseInt(localStorage.getItem('lvalues_last_content_node_id') || '0', 10); } catch(e) {}
      if (!lastId || typeof selectNode !== 'function') return;
      var node = (window.contentStudioNodes || contentStudioNodes || []).find(function(n){ return parseInt(n.node_id, 10) === lastId; });
      if (node) selectNode(node.node_id, node.title || '', node.full_path || '', node.level || 0, node.status || 'draft', node.is_root || 0);
    }, 700);
  });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var search = document.getElementById('contentTreeSearch');
  var modeTree = document.getElementById('courseModeTree');
  var toggleBtn = document.getElementById('btnToggleCourseMode');
  var filterLabel = document.getElementById('courseFilterLabel');
  var filterHint = document.getElementById('courseFilterHint');
  var currentMode = 'all';

  function setTreeExpanded(expanded) {
    if (!modeTree || !toggleBtn) return;
    modeTree.classList.toggle('collapsed', !expanded);
    toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(){
      setTreeExpanded(modeTree.classList.contains('collapsed'));
    });
  }
  function applyContentFilter(mode) {
    currentMode = mode || 'all';
    var q = search ? (search.value || '').toLowerCase().trim() : '';

    document.querySelectorAll('.course-mode-item').forEach(function(btn){
      btn.classList.toggle('active', btn.getAttribute('data-content-mode') === currentMode);
    });

    if (filterLabel) {
      filterLabel.textContent = currentMode === 'notes' ? 'Notes / Books' : (currentMode === 'blog' ? 'Blogs / Articles' : 'All Course Content');
    }
    if (filterHint) {
      filterHint.textContent = currentMode === 'notes' ? 'Only notes/books are visible.' : (currentMode === 'blog' ? 'Only blogs/articles are visible.' : 'Select Create Notes or Create Blog to filter.');
    }

    document.querySelectorAll('.rootTreeBox').forEach(function(item){
      var kind = item.getAttribute('data-content-kind') || 'notes';
      var modeOk = currentMode === 'all' || kind === currentMode;
      var hay = (item.textContent || '').toLowerCase();
      var searchOk = !q || hay.indexOf(q) !== -1;
      item.style.display = (modeOk && searchOk) ? '' : 'none';
    });
  }

  document.querySelectorAll('.course-mode-item').forEach(function(btn){
    btn.addEventListener('click', function(){
      var mode = btn.getAttribute('data-content-mode');
      if (mode === 'upload') {
        if (window.jQuery) jQuery('#uploadNotesModal').modal('show');
        return;
      }
      applyContentFilter(mode);
      setTreeExpanded(true);
    });
  });

  if (search) search.addEventListener('input', function(){ applyContentFilter(currentMode); });

  var params = new URLSearchParams(window.location.search || '');
  var action = params.get('action');
  if (action === 'create_notes') applyContentFilter('notes');
  else if (action === 'create_blog') applyContentFilter('blog');
  else applyContentFilter('all');
  if (action === 'upload_notes' && window.jQuery) { jQuery('#uploadNotesModal').modal('show'); }
});
</script>
