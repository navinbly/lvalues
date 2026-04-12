<?php
$sessions = isset($upcoming_tutor_sessions) ? $upcoming_tutor_sessions : [];
?>

<div class="container my-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Upcoming Live Sessions</h3>
    <a href="<?= site_url('tutors'); ?>" class="btn btn-outline-primary btn-sm">Find Tutors</a>
  </div>

  <?php if (empty($sessions)): ?>
    <div class="alert alert-light border">
      No upcoming sessions published yet.
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 180px;">Date & Time</th>
            <th>Session</th>
            <th style="width: 160px;">Tutor</th>
            <th style="width: 120px;">Mode</th>
            <th>Location</th>
            <th style="width: 120px;">Capacity</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td>
                <div><strong><?= date('d M Y', strtotime($s['start_at'])); ?></strong></div>
                <div class="text-muted small">
                  <?= date('h:i A', strtotime($s['start_at'])); ?> - <?= date('h:i A', strtotime($s['end_at'])); ?>
                </div>
              </td>
              <td>
                <div><strong><?= html_escape($s['title']); ?></strong></div>
                <div class="text-muted small">Session ID: <?= (int)$s['id']; ?></div>
              </td>
              <td>
                <?= html_escape(trim(($s['tutor_first_name'] ?? '').' '.($s['tutor_last_name'] ?? ''))); ?>
              </td>
              <td>
                <span class="badge bg-<?=
                    ($s['mode'] === 'offline' ? 'warning' : 'success');
                ?>">
                  <?= strtoupper(html_escape($s['mode'])); ?>
                </span>
              </td>
              <td><?= html_escape($s['location_text'] ?? ($s['mode'] === 'online' ? 'Online' : '')); ?></td>
              <td><?= (int)$s['capacity']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
