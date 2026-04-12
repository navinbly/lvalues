<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">

        <h4 class="header-title mb-3">Pending Comments</h4>

        <?php if (empty($comments)): ?>
          <div class="alert alert-light">No pending comments.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Node ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Comment</th>
                  <th>Created</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($comments as $i => $c): ?>
                  <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><?php echo (int)$c['node_id']; ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($c['comment_text'])); ?></td>
                    <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                    <td style="white-space:nowrap;">
                      <a class="btn btn-success btn-sm"
                         href="<?php echo site_url('admin/content_comment_action/approve/'.(int)$c['comment_id']); ?>">
                        Approve
                      </a>
                      <a class="btn btn-danger btn-sm"
                         href="<?php echo site_url('admin/content_comment_action/reject/'.(int)$c['comment_id']); ?>"
                         onclick="return confirm('Reject this comment?');">
                        Reject
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
