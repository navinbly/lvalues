<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="page-title mb-3"><i class="mdi mdi-file-tree title_icon"></i> Pending Nodes</h4>
        <p class="text-muted">Review tutor-created Content (Docs) nodes and tutor blog submissions from one place.</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="header-title mb-3">Pending Content Nodes</h4>
        <div class="table-responsive-sm mt-3">
          <table class="table table-striped table-centered mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Owner User ID</th>
                <th>Parent ID</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($pending_nodes)): ?>
                <?php foreach ($pending_nodes as $key => $node): ?>
                  <tr>
                    <td><?php echo $key + 1; ?></td>
                    <td><?php echo html_escape($node['title']); ?></td>
                    <td><?php echo (int)$node['owner_user_id']; ?></td>
                    <td><?php echo (int)$node['parent_id']; ?></td>
                    <td><?php echo !empty($node['created_at']) ? html_escape($node['created_at']) : 'N/A'; ?></td>
                    <td><span class="badge badge-warning"><?php echo html_escape($node['status']); ?></span></td>
                    <td>
                      <div class="dropright dropright">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="mdi mdi-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('admin/content_nodes_pending/approve/' . (int)$node['node_id']); ?>');">Approve</a></li>
                          <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('admin/content_nodes_pending/reject/' . (int)$node['node_id']); ?>');">Reject</a></li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">No pending content nodes found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="header-title mb-3">Pending Tutor Blog Requests</h4>
        <div class="table-responsive-sm mt-3">
          <table class="table table-striped table-centered mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Creator</th>
                <th>Title</th>
                <th>Preview</th>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($pending_blogs) && $pending_blogs->num_rows() > 0): ?>
                <?php foreach ($pending_blogs->result_array() as $key => $blog): ?>
                  <?php
                    $user_details = $this->user_model->get_all_user($blog['user_id'])->row_array();
                    $category_row = $this->crud_model->get_blog_categories($blog['blog_category_id'])->row_array();
                    $preview_text = ellipsis(strip_tags(htmlspecialchars_decode_($blog['description'])), 180);
                  ?>
                  <tr>
                    <td><?php echo $key + 1; ?></td>
                    <td>
                      <?php if (is_array($user_details) && !empty($user_details)): ?>
                        <div class="d-flex align-items-center">
                          <div>
                            <img src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>" alt="" height="45" width="45" class="img-fluid rounded-circle img-thumbnail">
                          </div>
                          <div class="pl-2 pt-1">
                            <div><?php echo html_escape(trim(($user_details['first_name'] ?? '') . ' ' . ($user_details['last_name'] ?? ''))); ?></div>
                            <small class="text-muted"><?php echo html_escape($user_details['email'] ?? ''); ?></small>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">User not found</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <strong><?php echo html_escape($blog['title']); ?></strong><br>
                      <small class="text-muted"><?php echo !empty($blog['added_date']) ? date('d M Y', $blog['added_date']) : ''; ?></small>
                    </td>
                    <td style="max-width: 320px; white-space: normal;">
                      <?php echo html_escape($preview_text); ?>
                    </td>
                    <td><?php echo html_escape($category_row['title'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-warning"><?php echo html_escape($blog['status']); ?></span></td>
                    <td>
                      <div class="dropright dropright">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="mdi mdi-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('admin/content_nodes_pending/approve_blog/' . (int)$blog['blog_id']); ?>');">Approve</a></li>
                          <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('admin/content_nodes_pending/reject_blog/' . (int)$blog['blog_id']); ?>');">Reject</a></li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">No pending tutor blog requests found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
