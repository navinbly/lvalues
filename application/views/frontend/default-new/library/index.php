<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section style="background:#f8fafc;padding:48px 0 72px"><div class="container">
  <div class="d-flex justify-content-between align-items-end flex-wrap mb-4"><div><h1>Books & Learning Library</h1><p class="text-muted">Search and continue learning from published Lvalues books.</p></div>
    <form class="form-inline" method="get"><input class="form-control mr-2" name="q" value="<?php echo html_escape($query); ?>" placeholder="Search books"><button class="btn btn-primary">Search</button></form>
  </div>
  <div class="row"><?php foreach($books as $book): ?><div class="col-lg-4 col-md-6 mb-3"><a href="<?php echo site_url('books/'.$book['slug']); ?>" class="card h-100 text-decoration-none" style="border:1px solid #e2e8f0;border-radius:18px"><div class="card-body">
    <span class="badge badge-primary"><?php echo ($book['reader_mode'] ?? 'interactive') === 'pdf' ? 'PDF Viewer' : 'Interactive Book'; ?></span>
    <h4 class="mt-3 text-dark"><?php echo html_escape($book['title']); ?></h4><p class="text-muted"><?php echo html_escape($book['meta_description'] ?? 'Open this book and start reading.'); ?></p>
  </div></a></div><?php endforeach; ?><?php if(empty($books)): ?><div class="col-12"><div class="alert alert-light border">No published books match your search.</div></div><?php endif; ?></div>
</div></section>
