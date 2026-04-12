<?php
// Lightweight standalone page (keeps your current theme minimal).
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo html_escape($page_title); ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h3 class="mb-2"><?php echo html_escape($exam['title']); ?></h3>
          <?php if (!empty($exam['description'])): ?>
            <p class="text-muted"><?php echo nl2br(html_escape($exam['description'])); ?></p>
          <?php endif; ?>

          <?php if (!empty($exam['policy'])): ?>
            <div class="alert alert-info">
              <b>Exam policy / instructions</b><br>
              <?php echo nl2br(html_escape($exam['policy'])); ?>
            </div>
          <?php endif; ?>

          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="small text-muted">
              Passing marks: <b><?php echo html_escape($exam['passing_marks']); ?></b>
            </div>
            <a class="btn btn-primary" href="<?php echo site_url('exam/start/'.$node_id); ?>">Start Exam</a>
          </div>
        </div>
      </div>

      <div class="text-center mt-3">
        <a class="small" href="<?php echo site_url('blog'); ?>">Back to learning</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
