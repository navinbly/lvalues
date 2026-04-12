<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Content (Docs) - Pending Nodes</h5>
        <small class="text-muted">Approve or reject tutor-created nodes.</small>
    </div>

    <div class="card-body">
        <?php if (empty($pending_nodes)): ?>
            <div class="alert alert-success mb-0">No pending nodes 🎉</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Path</th>
                            <th>Root</th>
                            <th>Level</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th style="width:180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach($pending_nodes as $n): ?>
                            <tr>
                                <td><?= $i++; ?></td>
                                <td><?= htmlspecialchars($n['title']); ?></td>
                                <td><code><?= htmlspecialchars($n['full_path']); ?></code></td>
                                <td><?= htmlspecialchars($n['root_key']); ?></td>
                                <td><?= (int)$n['level']; ?></td>
                                <td><?= (int)$n['created_by']; ?></td>
                                <td><?= htmlspecialchars($n['created_at']); ?></td>
                                <td>
                                    <a class="btn btn-success btn-sm"
                                       href="<?= site_url('admin/content_nodes_pending/approve/'.$n['node_id']); ?>"
                                       onclick="return confirm('Approve this node?');">
                                        Approve
                                    </a>

                                    <a class="btn btn-danger btn-sm"
                                       href="<?= site_url('admin/content_nodes_pending/reject/'.$n['node_id']); ?>"
                                       onclick="return confirm('Reject this node?');">
                                        Reject
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <hr>
        <a class="btn btn-outline-primary" href="<?= site_url('admin/content_nodes'); ?>">
            ← Back to Tree
        </a>
    </div>
</div>
