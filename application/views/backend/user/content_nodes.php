<?php defined('BASEPATH') OR exit('No direct script access allowed');
$CI =& get_instance();
$CI->load->model('content_docs_model');
$user_id = (int)$CI->session->userdata('user_id');
$section = $CI->input->get('section');
if (!in_array($section, array('book', 'article', 'published'), true)) $section = 'book';

$books = $CI->content_docs_model->get_tutor_books($user_id);
$articles = $CI->content_docs_model->get_tutor_articles($user_id);
$nodes = $CI->content_docs_model->get_tutor_book_tree($user_id);

$children = array();
foreach ($nodes as $n) {
    $pid = (int)($n['parent_id'] ?: 0);
    if (!isset($children[$pid])) $children[$pid] = array();
    $children[$pid][] = $n;
}
foreach ($children as &$list) {
    usort($list, function($a, $b){
        $ao = (int)($a['sort_order'] ?? 0);
        $bo = (int)($b['sort_order'] ?? 0);
        if ($ao !== $bo) return $ao <=> $bo;
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    });
}
unset($list);

if (!function_exists('lv_cp_status_label')) {
    function lv_cp_status_label($status) {
        if ($status === 'pending' || $status === 'in_review') return 'In Review';
        if ($status === 'published') return 'Published';
        if ($status === 'rejected') return 'Rejected';
        if ($status === 'on_hold') return 'On Hold';
        if ($status === 'update_required') return 'Update Required';
        if ($status === 'deleted') return 'Deleted';
        return 'Draft';
    }
}
if (!function_exists('lv_cp_status_badge')) {
    function lv_cp_status_badge($status) {
        $class = 'secondary';
        if ($status === 'pending' || $status === 'in_review') $class = 'warning';
        elseif ($status === 'published') $class = 'success';
        elseif ($status === 'rejected') $class = 'danger';
        elseif ($status === 'on_hold' || $status === 'update_required') $class = 'warning';
        elseif ($status === 'deleted') $class = 'dark';
        return '<span class="badge badge-'.$class.' lv-status-badge">'.htmlspecialchars(lv_cp_status_label($status)).'</span>';
    }
}
if (!function_exists('lv_cp_admin_note')) {
    function lv_cp_admin_note($row) {
        $note = !empty($row['admin_remark']) ? $row['admin_remark'] : ($row['review_note'] ?? '');
        if (trim((string)$note) === '') return '';
        return '<div class="lv-admin-note"><i class="mdi mdi-comment-alert-outline mr-1"></i><b>Admin reason:</b> '.htmlspecialchars($note).'</div>';
    }
}

function lv_tutor_tree_json_rows($books, $articles, $nodes) {
    $rows = array();
    foreach ($books as $b) {
        $rows[] = array('node_id'=>(int)$b['node_id'], 'parent_id'=>(int)($b['parent_id'] ?? 0), 'title'=>(string)($b['title'] ?? ''), 'level'=>(int)($b['level'] ?? 0), 'status'=>(string)($b['status'] ?? 'draft'), 'kind'=>'book');
    }
    foreach ($nodes as $n) {
        $rows[] = array('node_id'=>(int)$n['node_id'], 'parent_id'=>(int)($n['parent_id'] ?? 0), 'title'=>(string)($n['title'] ?? ''), 'level'=>(int)($n['level'] ?? 0), 'status'=>(string)($n['status'] ?? 'draft'), 'kind'=>((int)($n['level'] ?? 0) <= 3 ? 'chapter' : 'page'));
    }
    foreach ($articles as $a) {
        $rows[] = array('node_id'=>(int)$a['node_id'], 'parent_id'=>0, 'title'=>(string)($a['title'] ?? ''), 'level'=>0, 'status'=>(string)($a['status'] ?? 'draft'), 'kind'=>'article');
    }
    return $rows;
}

if (!function_exists('lv_tutor_render_book_children')) {
    function lv_tutor_render_book_children($parentId, $children) {
        if (empty($children[$parentId])) return;
        echo '<ul class="list-unstyled tutor-content-tree" data-parent-id="'.(int)$parentId.'">';
        foreach ($children[$parentId] as $node) {
            $id = (int)$node['node_id'];
            $level = (int)($node['level'] ?? 0);
            $hasChildren = !empty($children[$id]);
            $isPage = !$hasChildren && $level > 1;
            $kind = $isPage ? 'page' : 'chapter';
            $icon = $isPage ? 'mdi-file-document-outline text-primary' : 'mdi-folder-outline text-warning';
            $title = htmlspecialchars($node['title'] ?? 'Untitled');
            $titleJs = htmlspecialchars($node['title'] ?? '', ENT_QUOTES);
            $statusJs = htmlspecialchars($node['status'] ?? 'draft', ENT_QUOTES);
            echo '<li class="tutor-tree-item" data-node-id="'.$id.'" data-search="'.strtolower($title).'">';
            echo '<div class="tutor-tree-row">';
            if ($hasChildren) echo '<button type="button" class="tree-toggle-btn" data-target="kids-'.$id.'">▾</button>';
            else echo '<span class="tree-toggle-space"></span>';
            if ($isPage) {
                echo '<a href="javascript:void(0)" class="tree-main select-page" data-node-id="'.$id.'" data-title="'.$titleJs.'" data-status="'.$statusJs.'"><i class="mdi '.$icon.' mr-1"></i><span>'.$title.'</span></a>';
            } else {
                echo '<span class="tree-main"><i class="mdi '.$icon.' mr-1"></i><span>'.$title.'</span></span>';
            }
            echo '<span class="tree-actions">';
            if (!$isPage) echo '<button type="button" class="tree-action-btn add-child" title="Add Page" data-parent="'.$id.'" data-type="page">＋</button>';
            else echo '<form method="post" class="d-inline" action="'.site_url('user/content_page_duplicate/'.$id).'"><button class="tree-action-btn" title="Duplicate">⧉</button></form>';
            echo '<button type="button" class="tree-action-btn edit-node" title="Rename" data-id="'.$id.'" data-title="'.$titleJs.'">✎</button>';
            echo '<form method="post" class="d-inline" action="'.site_url('user/content_nodes/delete/'.$id).'"><button class="tree-action-btn tree-action-danger" title="Delete" onclick="return confirm(\'Move this item to recycle bin? Admin can restore it.\');">−</button></form>';
            echo '</span></div>';
            if ($hasChildren) { echo '<div id="kids-'.$id.'" class="tree-kids-modern">'; lv_tutor_render_book_children($id, $children); echo '</div>'; }
            echo '</li>';
        }
        echo '</ul>';
    }
}
$js_nodes = lv_tutor_tree_json_rows($books, $articles, $nodes);
$published_books = array_values(array_filter($books, function($b){ return ($b['status'] ?? '') === 'published'; }));
$published_articles = array_values(array_filter($articles, function($a){ return ($a['status'] ?? '') === 'published'; }));
$upload_node_map = array();
foreach (array_merge($books, $nodes) as $row) {
    $upload_node_map[(int)$row['node_id']] = $row;
}
$upload_chapters_by_book = array();
$upload_pages_by_chapter = array();
foreach ($nodes as $row) {
    $node_id = (int)$row['node_id'];
    $level = (int)($row['level'] ?? 0);
    if ($level === 3) {
        $book_id = (int)($row['parent_id'] ?? 0);
        if (!isset($upload_chapters_by_book[$book_id])) $upload_chapters_by_book[$book_id] = array();
        $upload_chapters_by_book[$book_id][] = $row;
        continue;
    }
    if ($level > 3) {
        $chapter_id = (int)($row['parent_id'] ?? 0);
        while (!empty($upload_node_map[$chapter_id]) && (int)($upload_node_map[$chapter_id]['level'] ?? 0) > 3) {
            $chapter_id = (int)($upload_node_map[$chapter_id]['parent_id'] ?? 0);
        }
        if ($chapter_id > 0) {
            if (!isset($upload_pages_by_chapter[$chapter_id])) $upload_pages_by_chapter[$chapter_id] = array();
            $upload_pages_by_chapter[$chapter_id][] = $row;
        }
    }
}
$upload_picker = array(
    'chaptersByBook' => $upload_chapters_by_book,
    'pagesByChapter' => $upload_pages_by_chapter,
);
?>
<style>
.tutor-publish-shell{display:grid;grid-template-columns:360px minmax(0,1fr);gap:18px;align-items:start}.tutor-left-panel{background:#f8fafc;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 14px 36px rgba(15,23,42,.06);position:sticky;top:88px;max-height:calc(100vh - 110px);display:flex;flex-direction:column;overflow:hidden}.tutor-left-header{padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#fff}.content-search-box{position:relative;margin-top:0}.content-search-box input{border-radius:999px;padding-left:36px;background:#fff}.content-search-box:before{content:'🔍';position:absolute;left:13px;top:8px;z-index:2;font-size:13px}.tutor-tree-scroll{padding:12px 10px 18px;overflow:auto}.tutor-right-panel{min-width:0;background:#fff;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 14px 36px rgba(15,23,42,.06);overflow:hidden}.tutor-editor-header{padding:18px 20px;border-bottom:1px solid #e5e7eb;background:linear-gradient(135deg,#fff,#f8fafc);display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.tutor-editor-body{padding:18px 20px}.tutor-content-tree{margin:0;padding-left:0}.tutor-content-tree .tutor-content-tree{padding-left:18px;margin-top:6px;border-left:1px dashed #dbe3ef}.tutor-tree-item{margin-bottom:6px}.tutor-tree-row{display:flex;align-items:center;gap:6px;border:1px solid transparent;border-radius:12px;padding:5px 6px;transition:.15s ease;background:transparent}.tutor-tree-row:hover{background:#fff;border-color:#dbeafe;box-shadow:0 8px 22px rgba(59,130,246,.08)}.tree-toggle-btn,.tree-toggle-space{width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 24px;border:0;background:#eef2ff;color:#4f46e5;border-radius:8px;font-size:12px}.tree-toggle-btn.collapsed{transform:rotate(-90deg)}.tree-main{display:flex;align-items:center;gap:5px;min-width:0;flex:1;color:#0f172a;font-weight:700;font-size:13px;text-decoration:none!important}.tree-main span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.tree-actions{display:flex;gap:4px;opacity:0;transition:.15s ease}.tutor-tree-row:hover .tree-actions{opacity:1}.tree-action-btn{width:24px;height:24px;border:0;border-radius:8px;background:#ecfeff;color:#0891b2;font-weight:800;line-height:1;cursor:pointer}.tree-action-danger{background:#fff1f2;color:#e11d48}.tree-action-btn:hover{filter:brightness(.96)}.node-row.active,.select-page.active{color:#4f46e5}.lv-status-badge{font-size:11px;padding:5px 8px}.lv-admin-note{font-size:12px;color:#64748b;margin-top:5px;line-height:1.35;clear:both}.autosave-dot{width:8px;height:8px;border-radius:50%;display:inline-block;background:#94a3b8}.autosave-dot.saving{background:#f59e0b}.autosave-dot.saved{background:#22c55e}.lv-editor-shell{border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}.lv-editor-header{background:#f8fafc;padding:14px 16px;border-bottom:1px solid #e5e7eb}.lv-sticky-save{position:sticky;bottom:0;background:#fff;border-top:1px solid #e5e7eb;padding:12px;z-index:5;box-shadow:0 -8px 18px rgba(15,23,42,.05)}.publish-panel{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:12px}.content-empty-state{min-height:360px;display:flex;align-items:center;justify-content:center;text-align:center;color:#64748b;background:linear-gradient(135deg,#f8fafc,#fff);border:1px dashed #cbd5e1;border-radius:16px}.article-row{border:1px solid #e5e7eb;border-radius:12px;padding:8px 10px;margin-bottom:8px;background:#fff}.article-row:hover{border-color:#c7d2fe;background:#fafbff}.sticky-review-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:10px}@media(max-width:992px){.tutor-publish-shell{grid-template-columns:1fr}.tutor-left-panel{position:relative;top:0;max-height:none}.tree-actions{opacity:1}}
</style>

<div class="row"><div class="col-12">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:12px;">
    <div><h4 class="header-title mb-1">Publish Books</h4><p class="text-muted mb-0">Tutor book, notes, and article workspace. Drafts stay with you; submitted books/blogs go to admin for approval.</p></div>
  </div>

  <div class="tutor-publish-shell">
    <aside class="tutor-left-panel">
      <div class="tutor-left-header">
        <div class="content-search-box"><input type="text" id="contentTreeSearch" class="form-control form-control-sm" placeholder="Search..."></div>
      </div>
      <div class="tutor-tree-scroll">
        <?php if ($section === 'book'): ?>
          <div class="d-flex justify-content-between align-items-center mb-2"><strong>Notes / Books</strong><button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newBookModal">＋ Book</button></div>
          <?php if (empty($books)): ?><div class="alert alert-info mb-0">No notes yet. Click <b>＋ Book</b> to create your first notes/book.</div><?php endif; ?>
          <?php foreach($books as $book): $bid=(int)$book['node_id']; $btitle=htmlspecialchars($book['title'] ?? 'Untitled'); $btitleJs=htmlspecialchars($book['title'] ?? '', ENT_QUOTES); ?>
            <div class="tutor-tree-item root-book-item" data-node-id="<?php echo $bid; ?>">
              <div class="tutor-tree-row">
                <?php if (!empty($children[$bid])): ?><button type="button" class="tree-toggle-btn" data-target="kids-<?php echo $bid; ?>">▾</button><?php else: ?><span class="tree-toggle-space"></span><?php endif; ?>
                <span class="tree-main"><i class="mdi mdi-book-open-page-variant text-primary mr-1"></i><span><?php echo $btitle; ?></span></span>
                <span class="tree-actions"><button type="button" class="tree-action-btn add-child" title="Add Chapter" data-parent="<?php echo $bid; ?>" data-type="chapter">＋</button><button type="button" class="tree-action-btn edit-node" title="Rename" data-id="<?php echo $bid; ?>" data-title="<?php echo $btitleJs; ?>">✎</button><form method="post" class="d-inline" action="<?php echo site_url('user/content_nodes/delete/'.$bid); ?>"><button class="tree-action-btn tree-action-danger" title="Delete" onclick="return confirm('Move this book to recycle bin? Admin can restore it.');">−</button></form></span>
              </div>
              <?php echo lv_cp_admin_note($book); ?>
              <?php if (!empty($children[$bid])): ?><div id="kids-<?php echo $bid; ?>" class="tree-kids-modern"><?php lv_tutor_render_book_children($bid, $children); ?></div><?php endif; ?>
              <?php if(($book['status'] ?? '') !== 'pending' && ($book['status'] ?? '') !== 'published'): ?><form method="post" class="sticky-review-actions" action="<?php echo site_url('user/content_book_submit/'.$bid); ?>"><button class="btn btn-sm btn-success" onclick="return confirm('Submit this book to admin for approval?');">Submit Book for Review</button></form><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php elseif ($section === 'article'): ?>
          <div class="d-flex justify-content-between align-items-center mb-2"><strong>Blogs / Articles</strong><button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#newArticleModal">＋ Blog</button></div>
          <?php if (empty($articles)): ?><div class="alert alert-info mb-0">No blogs yet. Click <b>＋ Blog</b> to create your first blog.</div><?php endif; ?>
          <?php foreach($articles as $article): $aid=(int)$article['node_id']; $atitle=htmlspecialchars($article['title'] ?? 'Untitled'); $atitleJs=htmlspecialchars($article['title'] ?? '', ENT_QUOTES); $astatus=htmlspecialchars($article['status'] ?? 'draft', ENT_QUOTES); ?>
            <div class="article-row" data-search="<?php echo strtolower($atitle); ?>">
              <div class="d-flex justify-content-between align-items-start" style="gap:8px;">
                <a href="javascript:void(0)" class="tree-main select-page" data-node-id="<?php echo $aid; ?>" data-title="<?php echo $atitleJs; ?>" data-status="<?php echo $astatus; ?>"><i class="mdi mdi-file-document-edit-outline text-info mr-1"></i><span><?php echo $atitle; ?></span></a>
                <span class="tree-actions" style="opacity:1"><button type="button" class="tree-action-btn edit-node" title="Rename" data-id="<?php echo $aid; ?>" data-title="<?php echo $atitleJs; ?>">✎</button><form method="post" class="d-inline" action="<?php echo site_url('user/content_nodes/delete/'.$aid); ?>"><button class="tree-action-btn tree-action-danger" title="Delete" onclick="return confirm('Move this blog to recycle bin? Admin can restore it.');">−</button></form></span>
              </div>
              <?php echo lv_cp_admin_note($article); ?>
              <?php if(($article['status'] ?? '') !== 'pending' && ($article['status'] ?? '') !== 'published'): ?><form method="post" class="sticky-review-actions" action="<?php echo site_url('user/content_article_submit/'.$aid); ?>"><button class="btn btn-sm btn-success" onclick="return confirm('Submit this blog/article to admin for approval?');">Submit Blog for Review</button></form><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <strong>Published Content</strong>
          <?php $published_all = array_merge($published_books, $published_articles); ?>
          <?php if(empty($published_all)): ?><div class="alert alert-light border mt-2">No published content yet.</div><?php endif; ?>
          <?php foreach($published_all as $item): ?><div class="article-row"><strong><?php echo htmlspecialchars($item['title'] ?? 'Untitled'); ?></strong><div class="mt-1"><?php echo lv_cp_status_badge($item['status']); ?></div></div><?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <section class="tutor-right-panel">
      <div class="tutor-editor-header"><div><h5 class="mb-1">Right Editor Workspace</h5><p class="text-muted small mb-0">Select a page/blog from the left pane. CKEditor and approval flow are unchanged.</p></div></div>
      <div class="tutor-editor-body">
        <?php if ($section === 'book' || $section === 'article'): ?>
          <?php include APPPATH.'views/backend/user/content_nodes_editor_partial.php'; ?>
        <?php else: ?>
          <div class="content-empty-state"><div><h5>Published content</h5><p class="mb-0">Approved notes and blogs are shown on the left.</p></div></div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div></div>

<?php if ($section === 'book' || $section === 'article'): ?>
<div class="modal fade" id="renameNodeModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><form method="post" action="<?php echo site_url('user/content_nodes/update'); ?>" class="modal-content"><input type="hidden" name="node_id" id="rename_node_id"><div class="modal-header"><h5 class="modal-title">Rename</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>New name</label><input type="text" name="title" id="rename_node_title" class="form-control" required></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Update Name</button></div></form></div></div>
<div class="modal fade" id="newBookModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><form method="post" action="<?php echo site_url('user/content_book_create'); ?>" class="modal-content"><div class="modal-header"><h5 class="modal-title">Create Book / Notes</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>Book / Notes Title</label><input type="text" name="book_title" class="form-control" required placeholder="Example: Cloud Computing"><label class="mt-2">First Chapter</label><input type="text" name="chapter_title" class="form-control" value="Introduction"><label class="mt-2">First Page</label><input type="text" name="page_title" class="form-control" value="Overview"></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Create Book</button></div></form></div></div>
<div class="modal fade" id="addChildModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><form method="post" action="<?php echo site_url('user/content_book_add_child'); ?>" class="modal-content"><input type="hidden" name="parent_id" id="child_parent_id"><input type="hidden" name="type" id="child_type"><div class="modal-header"><h5 class="modal-title" id="childModalTitle">Add</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>Title</label><input type="text" name="title" id="child_title" class="form-control" required></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Add</button></div></form></div></div>
<div class="modal fade" id="newArticleModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><form method="post" action="<?php echo site_url('user/content_article_create'); ?>" class="modal-content"><div class="modal-header"><h5 class="modal-title">Create Blog</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>Blog Title</label><input type="text" name="article_title" class="form-control" required placeholder="Example: How to Start Learning Cloud"><small class="text-muted d-block mt-1">This creates a standalone blog/article in Draft state.</small></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Create Blog</button></div></form></div></div>
<div class="modal fade" id="uploadNotesModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Upload Book / Notes</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><label>Book</label><select id="uploadTargetBook" class="form-control"><option value="">Select book...</option><?php foreach($books as $book): ?><option value="<?php echo (int)$book['node_id']; ?>"><?php echo htmlspecialchars($book['title'] ?? 'Untitled'); ?></option><?php endforeach; ?></select><label class="mt-3">Chapter</label><select id="uploadTargetChapter" class="form-control" disabled><option value="">Select chapter...</option></select><label class="mt-3">Page</label><select id="uploadTargetPage" class="form-control" disabled><option value="">Select page...</option></select><label class="mt-3">Choose file</label><input type="file" id="uploadNotesFile" class="form-control" accept=".docx,.doc,.pdf"><small class="text-muted d-block mt-1">DOCX imports into CKEditor. PDF/DOC uploads are inserted as a page attachment link; PDFs also get an embedded viewer.</small><div id="uploadNotesStatus" class="small text-muted mt-2"></div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Cancel</button><button class="btn btn-primary" type="button" id="btnImportUploadNotes">Upload Book / Notes</button></div></div></div></div>
<?php endif; ?>

<?php if ($section === 'book' || $section === 'article'): ?>
<script src="<?php echo base_url('assets/backend/ckeditor/ckeditor.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
let selectedNodeId = 0, autosaveTimer = null;
const contentStudioNodes = <?php echo json_encode($js_nodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
const uploadPicker = <?php echo json_encode($upload_picker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || {chaptersByBook:{}, pagesByChapter:{}};
const statusLabels = {draft:'Draft', pending:'In Review', in_review:'In Review', published:'Published', rejected:'Rejected', on_hold:'On Hold', update_required:'Update Required', deleted:'Deleted'};
const statusClasses = {draft:'secondary', pending:'warning', in_review:'warning', published:'success', rejected:'danger', on_hold:'warning', update_required:'warning', deleted:'dark'};
function statusBadge(status){ status=status||'draft'; return '<span class="badge badge-'+(statusClasses[status]||'secondary')+' lv-status-badge">'+(statusLabels[status]||'Draft')+'</span>'; }
function setSaveState(state, text){ const dot=document.getElementById('saveDot'), t=document.getElementById('saveText'); if(dot){ dot.className='autosave-dot '+state; } if(t){ t.textContent=text; } }
function parseJsonResponse(response){ return response.text().then(function(text){ if(!text.trim()){ throw new Error('Server returned an empty response. Please refresh and try again.'); } try { return JSON.parse(text); } catch(e){ throw new Error('Server returned an invalid response. Please refresh and try again.'); } }); }
function editorData(){ return CKEDITOR.instances.editor ? CKEDITOR.instances.editor.getData() : (document.getElementById('editor') ? document.getElementById('editor').value : ''); }
function setEditorData(html){ if(CKEDITOR.instances.editor){ CKEDITOR.instances.editor.setData(html||''); } else if(document.getElementById('editor')) { document.getElementById('editor').value=html||''; } }
function ensureEditor(){ if(!CKEDITOR.instances.editor){ CKEDITOR.replace('editor',{height:430, allowedContent:true, extraAllowedContent:'*(*);*{*}', versionCheck:false, removePlugins:'easyimage,cloudservices', filebrowserUploadUrl:'<?php echo site_url('user/content_page_upload'); ?>', uploadUrl:'<?php echo site_url('user/content_page_upload'); ?>', filebrowserUploadMethod:'form', toolbar:[{ name:'document', items:['Source','Preview','Print'] },{ name:'clipboard', items:['Undo','Redo','Cut','Copy','Paste','PasteText','PasteFromWord'] },{ name:'editing', items:['Find','Replace','SelectAll'] },'/',{ name:'styles', items:['Format','Font','FontSize'] },{ name:'basicstyles', items:['Bold','Italic','Underline','Strike','Subscript','Superscript','RemoveFormat'] },{ name:'colors', items:['TextColor','BGColor'] },{ name:'paragraph', items:['NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },'/',{ name:'links', items:['Link','Unlink','Anchor'] },{ name:'insert', items:['Image','Table','HorizontalRule','SpecialChar','PageBreak','Iframe'] },{ name:'tools', items:['Maximize','ShowBlocks'] }]}); CKEDITOR.on('instanceReady', function(){ if(CKEDITOR.instances.editor){ CKEDITOR.instances.editor.on('change', scheduleAutosave); } }); } }
function scheduleAutosave(){ clearTimeout(autosaveTimer); autosaveTimer=setTimeout(autosave, 1200); }
function autosave(){ if(!selectedNodeId || !document.getElementById('pageForm')) return; setSaveState('saving','Saving draft...'); const fd=new FormData(document.getElementById('pageForm')); fd.set('html', editorData()); fetch('<?php echo site_url('user/content_page_autosave'); ?>',{method:'POST', body:fd, credentials:'same-origin'}).then(r=>r.json()).then(j=>{ setSaveState(j.ok?'saved':'', j.ok?'Draft saved':'Save failed'); }).catch(()=>setSaveState('', 'Save failed')); }
function previewContent(){ const w=window.open('', '_blank'); if(!w){ alert('Please allow popups for preview.'); return; } w.document.open(); w.document.write('<!doctype html><html><head><title>Preview<\/title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:Arial,sans-serif;max-width:900px;margin:30px auto;line-height:1.6;padding:0 15px}img,iframe,video{max-width:100%;height:auto}table{border-collapse:collapse}td,th{border:1px solid #ddd;padding:8px}<\/style><\/head><body>'+editorData()+'<\/body><\/html>'); w.document.close(); }
function openEditorByNode(nodeId, title, status, previewAfter){ selectedNodeId=parseInt(nodeId,10)||0; document.querySelectorAll('.select-page').forEach(r=>r.classList.remove('active')); const el=document.querySelector('.select-page[data-node-id="'+selectedNodeId+'"]'); if(el){ el.classList.add('active'); } document.getElementById('pageForm').style.display='block'; document.getElementById('emptyEditor').style.display='none'; document.getElementById('content_node_id').value=selectedNodeId; document.getElementById('selectedPageTitle').textContent=title||'Content Editor'; document.getElementById('selectedPageLabel').textContent='Editing: '+(title||'')+' · '+(statusLabels[status]||status||'Draft'); document.getElementById('selectedPageStatus').innerHTML=statusBadge(status||'draft'); ensureEditor(); fetch('<?php echo site_url('user/content_page_get/'); ?>'+selectedNodeId,{credentials:'same-origin'}).then(parseJsonResponse).then(j=>{ if(!j.ok){ throw new Error(j.message||'Unable to load content'); } const d=j.data||{}; setEditorData(d.html||''); document.getElementById('content_meta_title').value=d.meta_title||''; document.getElementById('content_meta_description').value=d.meta_description||''; document.getElementById('content_meta_keywords').value=d.meta_keywords||''; document.getElementById('content_canonical_url').value=d.canonical_url||''; if(document.getElementById('content_category')) document.getElementById('content_category').value=d.content_category||''; if(document.getElementById('content_tags')) document.getElementById('content_tags').value=d.content_tags||''; setSaveState('', 'Loaded'); if(previewAfter){ setTimeout(previewContent, 700); } }).catch(e=>{ setSaveState('', 'Load failed'); alert(e.message); }); }
function openEditor(el, previewAfter){ openEditorByNode(el.dataset.nodeId, el.dataset.title||'Content Editor', el.dataset.status||'draft', previewAfter); }
document.querySelectorAll('.select-page').forEach(a=>a.addEventListener('click', function(){ openEditor(this, this.classList.contains('preview-after-open')); }));
document.querySelectorAll('.edit-node').forEach(btn=>btn.addEventListener('click', function(){ document.getElementById('rename_node_id').value=this.dataset.id; document.getElementById('rename_node_title').value=this.dataset.title||''; $('#renameNodeModal').modal('show'); }));
document.querySelectorAll('.add-child').forEach(btn=>btn.addEventListener('click', function(){ document.getElementById('child_parent_id').value=this.dataset.parent; document.getElementById('child_type').value=this.dataset.type; document.getElementById('childModalTitle').textContent='Add '+(this.dataset.type==='chapter'?'Chapter':'Page'); document.getElementById('child_title').value=this.dataset.type==='chapter'?'New Chapter':'New Page'; $('#addChildModal').modal('show'); }));
document.querySelectorAll('.tree-toggle-btn').forEach(btn=>btn.addEventListener('click', function(){ const box=document.getElementById(this.dataset.target); if(!box) return; box.classList.toggle('d-none'); this.classList.toggle('collapsed', box.classList.contains('d-none')); }));
if(document.getElementById('insertTemplate')){ document.getElementById('insertTemplate').addEventListener('click', function(){ setEditorData(editorData()+'<h2>Topic Title</h2><p>Explain the concept in simple words.</p><h3>Example</h3><p>Add one practical example here.</p><h3>Summary</h3><ul><li>Key point 1</li><li>Key point 2</li></ul>'); scheduleAutosave(); }); }
function importDocxFile(file, append){ if(!file) return; if(!/\.docx$/i.test(file.name)){ alert('Please select a .docx file only.'); return; } const fd=new FormData(); fd.append('docx_file', file); setSaveState('saving','Importing DOCX...'); return fetch('<?php echo site_url('AdminDocImport/upload_docx'); ?>',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(j=>{ var html=(j.html || (j.data && j.data.html) || ''); if(j.ok && html){ setEditorData(append ? editorData()+html : html); scheduleAutosave(); setSaveState('saved','DOCX imported. Click Save Draft.'); return true; } alert(j.error||'DOCX import failed'); return false; }).catch(function(e){ alert('DOCX import failed: '+e.message); setSaveState('', 'Import failed'); return false; }); }
if(document.getElementById('docxFile')){ document.getElementById('docxFile').addEventListener('change', function(){ if(!this.files.length) return; importDocxFile(this.files[0], true); }); }
if(document.getElementById('openUploadNotes')){ document.getElementById('openUploadNotes').addEventListener('click', function(){ $('#uploadNotesModal').modal('show'); }); }
<?php if ($CI->input->get('action') === 'upload'): ?>
setTimeout(function(){ if (window.jQuery) $('#uploadNotesModal').modal('show'); }, 500);
<?php endif; ?>
function resetSelect(select, placeholder){ select.innerHTML=''; const opt=document.createElement('option'); opt.value=''; opt.textContent=placeholder; select.appendChild(opt); }
function nodeTitle(row){ return (row && row.title) ? row.title : 'Untitled'; }
function fillSelect(select, rows, placeholder){ resetSelect(select, placeholder); rows.forEach(function(row){ const opt=document.createElement('option'); opt.value=row.node_id; opt.textContent=nodeTitle(row); opt.setAttribute('data-title', nodeTitle(row)); opt.setAttribute('data-status', row.status || 'draft'); select.appendChild(opt); }); select.disabled = rows.length === 0; }
function htmlEscape(text){ return String(text || '').replace(/[&<>"']/g, function(ch){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]; }); }
const uploadBook = document.getElementById('uploadTargetBook');
const uploadChapter = document.getElementById('uploadTargetChapter');
const uploadPage = document.getElementById('uploadTargetPage');
if(uploadBook && uploadChapter && uploadPage){
  uploadBook.addEventListener('change', function(){ fillSelect(uploadChapter, uploadPicker.chaptersByBook[this.value] || [], 'Select chapter...'); resetSelect(uploadPage, 'Select page...'); uploadPage.disabled = true; });
  uploadChapter.addEventListener('change', function(){ fillSelect(uploadPage, uploadPicker.pagesByChapter[this.value] || [], 'Select page...'); });
}
function uploadAttachmentFile(file){
  const fd = new FormData();
  fd.append('document', file);
  return fetch('<?php echo site_url('user/content_page_document_upload'); ?>', {method:'POST', body:fd, credentials:'same-origin'})
    .then(r=>r.json())
    .then(j=>{ if(!j.ok) throw new Error(j.message || 'Document upload failed.'); return j; });
}
function insertUploadedAttachment(file, upload){
  const safeName = htmlEscape(file.name);
  const safeUrl = htmlEscape(upload.url);
  const ext = (file.name.split('.').pop() || '').toLowerCase();
  let html = '<p><a href="'+safeUrl+'" target="_blank" rel="noopener">'+safeName+'</a></p>';
  if (ext === 'pdf') {
    html += '<iframe src="'+safeUrl+'" style="width:100%;min-height:520px;border:1px solid #ddd;" title="'+safeName+'"></iframe>';
  }
  setEditorData(editorData() + html);
  scheduleAutosave();
}
if(document.getElementById('btnImportUploadNotes')){ document.getElementById('btnImportUploadNotes').addEventListener('click', function(){ const sel=document.getElementById('uploadTargetPage'); const fileInput=document.getElementById('uploadNotesFile'); const statusBox=document.getElementById('uploadNotesStatus'); const pageId=sel.value; if(!uploadBook.value){ alert('Please select target book.'); return; } if(!uploadChapter.value){ alert('Please select target chapter.'); return; } if(!pageId){ alert('Please select target page.'); return; } if(!fileInput.files.length){ alert('Please choose DOCX, DOC, or PDF file.'); return; } const file=fileInput.files[0]; const ext=(file.name.split('.').pop() || '').toLowerCase(); if(['docx','doc','pdf'].indexOf(ext) === -1){ alert('Only DOCX, DOC, or PDF files are allowed.'); return; } const opt=sel.options[sel.selectedIndex]; if(statusBox) statusBox.textContent='Opening selected page...'; openEditorByNode(pageId, opt.getAttribute('data-title') || opt.textContent, opt.getAttribute('data-status') || 'draft', false); setTimeout(function(){ if(ext === 'docx'){ if(statusBox) statusBox.textContent='Importing DOCX into CKEditor...'; importDocxFile(file, false).then(function(ok){ if(ok){ if(statusBox) statusBox.textContent='DOCX imported. Save Draft keeps it as tutor draft.'; $('#uploadNotesModal').modal('hide'); } }); return; } if(statusBox) statusBox.textContent='Uploading attachment...'; uploadAttachmentFile(file).then(function(upload){ insertUploadedAttachment(file, upload); if(statusBox) statusBox.textContent='Attachment inserted. Save Draft keeps it as tutor draft.'; $('#uploadNotesModal').modal('hide'); }).catch(function(e){ if(statusBox) statusBox.textContent='Upload failed.'; alert(e.message); }); }, 700); }); }
if(document.getElementById('previewBtn')){ document.getElementById('previewBtn').addEventListener('click', previewContent); }
if(document.getElementById('saveDraftBtn')){ document.getElementById('saveDraftBtn').addEventListener('click', function(){ autosave(); }); }
const pageForm=document.getElementById('pageForm'); if(pageForm){ pageForm.addEventListener('submit', function(){ if(CKEDITOR.instances.editor) CKEDITOR.instances.editor.updateElement(); }); }
document.querySelectorAll('.tutor-content-tree').forEach(ul=>{ new Sortable(ul,{handle:'.tree-main',animation:150,onEnd:function(evt){ const ids=[...evt.to.children].map(li=>li.dataset.nodeId).filter(Boolean); const fd=new FormData(); fd.append('parent_id', evt.to.dataset.parentId); fd.append('ordered_ids', JSON.stringify(ids)); fetch('<?php echo site_url('user/update_content_node_order'); ?>',{method:'POST',body:fd,credentials:'same-origin'}); }}); });
var search=document.getElementById('contentTreeSearch'); if(search){ search.addEventListener('input', function(){ var q=(search.value||'').toLowerCase().trim(); document.querySelectorAll('.tutor-tree-item,.root-book-item,.article-row').forEach(function(item){ if(!q){ item.style.display=''; return; } var hay=(item.textContent||'').toLowerCase(); item.style.display=hay.indexOf(q)!==-1?'':'none'; }); }); }
</script>
<?php endif; ?>
