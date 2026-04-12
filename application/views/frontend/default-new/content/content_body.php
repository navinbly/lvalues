<?php
/**
 * content_body.php (FINAL - DB driven, CKEditor content)
 *
 * Expected variables from controller:
 *   $selected_node   : array|null   (content_nodes row)
 *   $selected_page   : array|null   (content_node_pages row with html)
 *
 * Optional (for header/breadcrumb display):
 *   $active_full_path : string
 */

$selected_node   = $selected_node ?? null;
$selected_page   = $selected_page ?? null;
$active_full_path = $active_full_path ?? '';

//function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// ---------- Breadcrumb from full_path ----------
$breadcrumb = [];
if (!empty($active_full_path)) {
    $parts = array_filter(explode('/', trim($active_full_path, '/')));
    foreach ($parts as $p) {
        $breadcrumb[] = ucwords(str_replace('-', ' ', $p));
    }
}
?>

<?php if (!empty($breadcrumb)): ?>
    <h4><?= h(implode(' → ', $breadcrumb)) ?></h4>
    <hr>
<?php endif; ?>

<?php if (!empty($selected_node)): ?>

    <h3 class="mb-2"><?= h($selected_node['title'] ?? 'Untitled') ?></h3>

    <?php
        // If meta_title/meta_description needed later, we can use $selected_page fields.
        $html = $selected_page['html'] ?? '';
    ?>

    <?php if (trim($html) !== ''): ?>
        <!-- CKEditor HTML (admin-authored). We do not escape on purpose. -->
        <div class="content-page-html">
            <?= $html ?>
        </div>
    <?php else: ?>
        <p class="text-muted">No content published yet for this node.</p>
        <p>Select another topic from the sidebar.</p>
    <?php endif; ?>

<?php else: ?>

    <p>Select a topic from the sidebar.</p>

<?php endif; ?>
