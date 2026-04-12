<?php
/**
 * content_sidebar.php (FINAL)
 *
 * Fix added:
 * - When user clicks a leaf node, the page reloads and tree must NOT collapse.
 * - We expand the active node's ancestor chain server-side (open path).
 * - JS root switch will NOT collapse if an active node exists inside that root.
 */

$nodes = $nodes ?? [];
$base_route = $base_route ?? 'blog';
$active_full_path = $active_full_path ?? '';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/** Build maps */
$children = [];
$roots = [];
$parent_by_id = [];
$id_by_path = [];

foreach ($nodes as $n) {
    $nodeId = (int)($n['node_id'] ?? 0);
    $parentIdRaw = $n['parent_id'] ?? 0;
    $pid = ($parentIdRaw === null || $parentIdRaw === '' || (int)$parentIdRaw === 0) ? 0 : (int)$parentIdRaw;

    $parent_by_id[$nodeId] = $pid;

    $full_path = (string)($n['full_path'] ?? '');
    if ($full_path !== '') {
        $id_by_path[rtrim($full_path, '/')] = $nodeId;
    }

    if (!isset($children[$pid])) $children[$pid] = [];
    $children[$pid][] = $n;

    if (($n['is_root'] ?? 0) == 1 || $pid === 0) {
        $roots[] = $n;
    }
}

/** Sort roots */
usort($roots, function($a, $b){
    $ak = $a['root_key'] ?? '';
    $bk = $b['root_key'] ?? '';
    if ($ak !== $bk) return strcmp($ak, $bk);

    $aso = (int)($a['sort_order'] ?? 0);
    $bso = (int)($b['sort_order'] ?? 0);
    if ($aso !== $bso) return $aso <=> $bso;

    return strcmp(($a['title'] ?? ''), ($b['title'] ?? ''));
});

/** Root meta for root list */
$root_meta = [];
foreach ($roots as $r) {
    $root_meta[] = [
        'id' => (int)($r['node_id'] ?? 0),
        'title' => (string)($r['title'] ?? ''),
        'path' => (string)($r['full_path'] ?? '')
    ];
}

/** Build open path set (node ids to keep expanded) */
$open_ids = [];
$active_id = 0;

$active_key = rtrim((string)$active_full_path, '/');
if ($active_key !== '' && isset($id_by_path[$active_key])) {
    $active_id = (int)$id_by_path[$active_key];
    $cur = $active_id;

    // Walk up to root
    $guard = 0;
    while ($cur > 0 && $guard < 1000) {
        $open_ids[$cur] = true;
        $cur = (int)($parent_by_id[$cur] ?? 0);
        $guard++;
    }
}

/** helpers for open state */
function is_open($nodeId, $open_ids) {
    return isset($open_ids[(int)$nodeId]);
}
function sort_kids(&$kids) {
    usort($kids, function($a, $b){
        $aso = (int)($a['sort_order'] ?? 0);
        $bso = (int)($b['sort_order'] ?? 0);
        if ($aso !== $bso) return $aso <=> $bso;
        return strcmp(($a['title'] ?? ''), ($b['title'] ?? ''));
    });
}

/** Recursive renderer with open-path support */
function render_tree_node($node, $children, $base_route, $active_full_path, $open_ids) {
    $nodeId = (int)($node['node_id'] ?? 0);
    $title = $node['title'] ?? 'Untitled';
    $full_path = (string)($node['full_path'] ?? '');
    $url = site_url($base_route . '/' . ltrim($full_path, '/'));

    $hasKids = !empty($children[$nodeId]);
    $isActive = ($active_full_path !== '' && rtrim($active_full_path, '/') === rtrim($full_path, '/'));

    $openHere = $hasKids && is_open($nodeId, $open_ids);

    echo '<li data-node-id="'.$nodeId.'">';

    if ($hasKids) {
        echo '<span class="toggle" role="button" aria-label="Toggle" aria-expanded="'.($openHere?'true':'false').'">'
           . ($openHere ? '-' : '+')
           . '</span>';
    } else {
        echo '<span class="toggle spacer" aria-hidden="true"></span>';
    }

    echo '<a class="node-link' . ($isActive ? ' active-node' : '') . '" href="' . h($url) . '">' . h($title) . '</a>';

    if ($hasKids) {
        $style = $openHere ? ' style="display:block"' : '';
        echo '<ul class="nested"'.$style.'>';

        $kids = $children[$nodeId];
        sort_kids($kids);

        foreach ($kids as $child) {
            render_tree_node($child, $children, $base_route, $active_full_path, $open_ids);
        }
        echo '</ul>';
    }

    echo '</li>';
}
?>

<!-- Root list -->
<div class="root-menu" id="rootMenu">
  <?php if (empty($root_meta)): ?>
    <div class="text-muted small px-2 py-2">No root topics found.</div>
  <?php else: ?>
    <ul class="list-group root-menu-list">
      <?php foreach ($root_meta as $i => $rm): ?>
        <li class="list-group-item root-menu-item">
          <button type="button"
                  class="root-btn <?= $i === 0 ? 'active' : '' ?>"
                  data-root-id="<?= (int)$rm['id'] ?>">
            <?= h($rm['title']) ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<ul class="list-group course-tree" id="courseTree">
<?php if (empty($roots)): ?>
  <li class="list-group-item">
    <p class="mb-0 text-muted">No published content yet.</p>
  </li>
<?php else: ?>
  <?php foreach ($roots as $r): ?>
    <?php
      $rootId = (int)($r['node_id'] ?? 0);
      $rootTitle = $r['title'] ?? 'Untitled';
      $rootPath = (string)($r['full_path'] ?? '');
      $rootUrl = site_url($base_route . '/' . ltrim($rootPath, '/'));

      $rootHasKids = !empty($children[$rootId]);
      $rootIsActive = ($active_full_path !== '' && rtrim($active_full_path, '/') === rtrim($rootPath, '/'));
      $rootOpen = $rootHasKids && is_open($rootId, $open_ids);
    ?>

    <li class="list-group-item root-item"
        data-root-id="<?= $rootId ?>"
        data-root-path="<?= h($rootPath) ?>">

      <?php if ($rootHasKids): ?>
        <span class="toggle" role="button" aria-label="Toggle" aria-expanded="<?= $rootOpen ? 'true' : 'false' ?>">
          <?= $rootOpen ? '-' : '+' ?>
        </span>
      <?php else: ?>
        <span class="toggle spacer" aria-hidden="true"></span>
      <?php endif; ?>

      <a class="node-link<?= $rootIsActive ? ' active-node' : '' ?>" href="<?= h($rootUrl) ?>">
        <?= h($rootTitle) ?>
      </a>

      <?php if ($rootHasKids): ?>
        <?php $style = $rootOpen ? ' style="display:block"' : ''; ?>
        <ul class="nested"<?= $style ?>>
          <?php
            $kids = $children[$rootId];
            sort_kids($kids);
            foreach ($kids as $child) {
                render_tree_node($child, $children, $base_route, $active_full_path, $open_ids);
            }
          ?>
        </ul>
      <?php endif; ?>

    </li>
  <?php endforeach; ?>
<?php endif; ?>
</ul>

<style>
/* Root menu */
.root-menu { padding: 6px 10px 10px 10px; }
.root-menu-list { border-radius: 12px; overflow: hidden; }
.root-menu-item { padding: 0; border: 0; border-bottom: 1px solid #f1f1f1; }
.root-menu-item:last-child { border-bottom: 0; }
.root-btn{
  width: 100%;
  text-align: left;
  border: 1px solid rgba(148,163,184,.45);
  border-left: 0; border-right: 0; border-top: 0;
  background:#fff;
  padding: 12px 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border-radius: 0;
}
.root-btn.active{ background:#eaf2ff; color:#0d6efd; }
.root-btn:focus{ outline: none; box-shadow: 0 0 0 3px rgba(13,110,253,.12); }

/* Tree */
#courseTree.course-tree{
  font-size: 14px;
  line-height: 1.35;
  margin-top: 6px;
}
#courseTree.course-tree .list-group-item{
  border: 0;
  padding: 10px 10px;
  background: transparent;
}

#courseTree .nested{
  display: none;              /* collapsed by default */
  list-style: none;
  padding-left: 12px;
  margin: 2px 0 0 0;
  border-left: 1px solid rgba(148,163,184,0.35);
  padding-left: 14px;
}

#courseTree li{ margin: 0; padding: 2px 0; }

#courseTree .toggle{
  display: inline-flex;
  width: 18px;
  height: 18px;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  cursor: pointer;
  user-select: none;
  font-weight: 700;
  font-size: 13px;
  line-height: 1;
  color: #64748b;
  background: rgba(100,116,139,0.10);
  margin-right: 8px;
  vertical-align: middle;
}
#courseTree .toggle:hover{
  color: #0d6efd;
  background: rgba(13,110,253,0.12);
}
#courseTree .toggle.spacer{
  visibility: hidden;
  cursor: default;
}
#courseTree a.node-link{
  display: inline-block;
  padding: 4px 6px;
  border-radius: 6px;
  color: #0f172a;
  text-decoration: none;
  vertical-align: middle;
}
#courseTree a.node-link:hover{
  background: rgba(13,110,253,0.08);
  color: #0d6efd;
  text-decoration: none;
}
#courseTree a.active-node{
  font-weight: 700;
  color: #0d6efd;
  background: rgba(13,110,253,0.10);
}

@media (max-width: 576px){
  .root-btn{ padding: 12px 10px; }
  #courseTree.course-tree .list-group-item{ padding: 10px 6px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const rootsMeta = <?php echo json_encode($root_meta); ?>;
  const rootItems = Array.from(document.querySelectorAll('#courseTree .root-item'));
  const rootMenu = document.getElementById('rootMenu');
  const treeEl = document.getElementById('courseTree');

  function getDirectNestedUl(li){
    if (!li) return null;
    for (const ch of li.children) {
      if (ch && ch.tagName === 'UL' && ch.classList.contains('nested')) return ch;
    }
    return null;
  }

  function setToggle(el, open){
    if (!el) return;
    el.textContent = open ? '-' : '+';
    el.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function collapseAllUnder(li){
    if (!li) return;
    const uls = li.querySelectorAll('ul.nested');
    uls.forEach(ul => ul.style.display = 'none');
    const toggles = li.querySelectorAll('.toggle:not(.spacer)');
    toggles.forEach(t => setToggle(t, false));
  }

  function openFirstLevel(li){
    // Used only when there is NO active node inside the root
    const nested = getDirectNestedUl(li);
    const toggle = li.querySelector('.toggle:not(.spacer)');
    if (nested) nested.style.display = 'block';
    if (toggle && nested) setToggle(toggle, true);
  }

  function showOnlyRoot(rootId){
    rootItems.forEach(li => {
      li.style.display = (Number(li.dataset.rootId) === Number(rootId)) ? '' : 'none';
    });

    // active button
    const btns = rootMenu ? rootMenu.querySelectorAll('.root-btn') : [];
    btns.forEach(b => b.classList.toggle('active', Number(b.dataset.rootId) === Number(rootId)));

    // IMPORTANT FIX:
    // If this root already contains an active leaf (server-side expanded path),
    // DO NOT collapse anything. Otherwise, collapse and open first level.
    const li = document.querySelector('#courseTree .root-item[data-root-id="' + rootId + '"]');
    if (!li) return;

    const hasActiveInside = !!li.querySelector('a.active-node');
    if (!hasActiveInside) {
      collapseAllUnder(li);
      openFirstLevel(li);
    }
    localStorage.setItem('selectedRootId', String(rootId));
  }

  // Root menu click
  if (rootMenu) {
    rootMenu.addEventListener('click', function(e){
      const btn = e.target.closest('.root-btn');
      if (!btn) return;
      showOnlyRoot(btn.dataset.rootId);
    });
  }

  // Toggle expand/collapse (event delegation)
  if (treeEl) {
    treeEl.addEventListener('click', function(e){
      const t = e.target;
      if (!t || !t.classList.contains('toggle') || t.classList.contains('spacer')) return;

      const li = t.closest('li');
      if (!li) return;

      const nested = getDirectNestedUl(li);
      if (!nested) return;

      const open = (nested.style.display === 'block');
      nested.style.display = open ? 'none' : 'block';
      setToggle(t, !open);
    });
  }

  // Initial root selection:
  // Prefer root that contains the active node (so leaf click stays focused).
  let rootToShow = null;

  // Find which root contains active node
  const activeLink = document.querySelector('#courseTree a.active-node');
  if (activeLink) {
    const rootLi = activeLink.closest('.root-item');
    if (rootLi) rootToShow = rootLi.dataset.rootId;
  }

  // Fallbacks
  if (!rootToShow) {
    const saved = localStorage.getItem('selectedRootId');
    if (saved && rootsMeta.find(r => String(r.id) === String(saved))) {
      rootToShow = saved;
    } else if (rootsMeta.length > 0) {
      rootToShow = rootsMeta[0].id;
    }
  }

  if (rootToShow) showOnlyRoot(rootToShow);
});
</script>
