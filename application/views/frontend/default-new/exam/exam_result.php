<?php
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo html_escape($page_title); ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .ans-ok{ background:#ecfdf5; border:1px solid #34d399; }
    .ans-bad{ background:#fef2f2; border:1px solid #f87171; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0"><?php echo html_escape($exam['title']); ?> - Result</h4>
          <div class="small text-muted">Submitted: <?php echo html_escape($attempt['submitted_at']); ?></div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('blog'); ?>">Back to learning</a>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4"><div class="text-muted small">Score</div><div class="h3 mb-0"><?php echo (float)$attempt['score']; ?></div></div>
            <div class="col-md-4"><div class="text-muted small">Passing marks</div><div class="h3 mb-0"><?php echo (float)$exam['passing_marks']; ?></div></div>
            <div class="col-md-4"><div class="text-muted small">Status</div>
              <?php if ((int)$attempt['passed'] === 1): ?>
                <div class="h3 mb-0 text-success">Passed</div>
              <?php else: ?>
                <div class="h3 mb-0 text-danger">Not Passed</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <?php foreach (($report['items'] ?? []) as $idx => $item): ?>
        <div class="card mb-3 <?php echo $item['is_correct'] ? 'ans-ok' : 'ans-bad'; ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="font-weight-bold">Q<?php echo $idx+1; ?>. <?php echo nl2br(html_escape($item['question'])); ?></div>
              <div class="text-muted small">Marks: <?php echo html_escape($item['marks_awarded']); ?>/<?php echo html_escape($item['marks']); ?></div>
            </div>
            <hr>
            <div class="mb-1"><b>Your answer:</b> <?php echo html_escape($item['your_answer'] ?: 'Not answered'); ?></div>
            <div><b>Correct answer:</b> <?php echo html_escape($item['correct_answer']); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</body>
</html>
