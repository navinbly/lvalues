<?php
$CI =& get_instance();
$CI->load->model('content_docs_model');
$pending_nodes = $CI->content_docs_model->get_pending_nodes();
$pending_blogs = $CI->crud_model->get_instructors_pending_blog();
$custom_pages = $this->db->table_exists('custom_page') ? $this->db->get('custom_page')->result_array() : array();
$blogs = $this->db->table_exists('blog') ? $this->db->get('blog')->result_array() : array();
$published_blogs = 0;
foreach ($blogs as $blog) {
    if (($blog['status'] ?? '') == '1' || ($blog['status'] ?? '') == 'active' || ($blog['status'] ?? '') == 'published') {
        $published_blogs++;
    }
}
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="page-title"><i class="mdi mdi-file-document-edit title_icon"></i> Content governance</h4>
        <p class="text-muted mb-0">Phase 2 governance layer for docs pages, blogs, SEO, publishing workflow, and versioning readiness.</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Pending docs nodes</p><h3><?php echo count($pending_nodes); ?></h3><small>Needs review</small></div></div></div>
  <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Pending tutor blogs</p><h3><?php echo $pending_blogs->num_rows(); ?></h3><small>Needs editorial action</small></div></div></div>
  <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Custom pages</p><h3><?php echo count($custom_pages); ?></h3><small>Static page inventory</small></div></div></div>
  <div class="col-md-3 col-sm-6 mb-3"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Published blogs</p><h3><?php echo $published_blogs; ?></h3><small>Content marketing</small></div></div></div>
</div>

<div class="row">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
          <div>
            <h4 class="header-title mb-1">Publishing workflow</h4>
            <p class="text-muted mb-0">Move content through draft, review, approved, published, archived, and rollback states.</p>
          </div>
          <a href="<?php echo site_url('admin/content_nodes_pending'); ?>" class="btn btn-outline-primary btn-sm mt-2 mt-md-0">Review pending content</a>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Docs tree</h5><p class="text-muted">Manage course/documentation hierarchy and submit nodes for approval.</p><a href="<?php echo site_url('admin/content_nodes'); ?>" class="btn btn-outline-primary btn-sm">Open nodes</a></div></div>
          <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Static pages</h5><p class="text-muted">Use existing custom page tools while docs-page editor is completed.</p><a href="<?php echo site_url('admin/custom_page'); ?>" class="btn btn-outline-primary btn-sm">Open custom pages</a></div></div>
          <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>Blogs</h5><p class="text-muted">Govern tutor-created and admin-created blog content.</p><a href="<?php echo site_url('admin/blog'); ?>" class="btn btn-outline-primary btn-sm">Open blogs</a></div></div>
          <div class="col-md-6 mb-3"><div class="border rounded p-3 h-100"><h5>SEO readiness</h5><p class="text-muted">Next build: meta score, indexing status, canonical URL, OG image checklist.</p><span class="badge badge-warning-lighten">Workflow missing</span></div></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card">
      <div class="card-body">
        <h4 class="header-title mb-3">Content workflow backlog</h4>
        <ul class="list-group list-group-flush">
          <li class="list-group-item px-0">Add docs page editor with title, slug, body, SEO fields, OG image.</li>
          <li class="list-group-item px-0">Version history, diff preview, rollback, reviewer comments.</li>
          <li class="list-group-item px-0">Publishing states: draft, review, approved, published, archived.</li>
          <li class="list-group-item px-0">Media governance: asset owner, license, alt text, image dimensions.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
