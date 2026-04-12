<?php
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo html_escape($page_title); ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    .option-item{ border:1px solid #e2e8f0; border-radius:10px; padding:12px; }
    .option-item:hover{ background:#f8fafc; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="h5 mb-0"><?php echo html_escape($exam['title']); ?></div>
          <div class="small text-muted">Question <?php echo (int)$index + 1; ?> of <?php echo (int)$total; ?></div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('exam/'.$exam['node_id']); ?>">Exit</a>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="mb-2 text-muted small">Marks: <?php echo html_escape($question['marks']); ?></div>
          <h5 class="mb-3"><?php echo nl2br(html_escape($question['question_text'])); ?></h5>

          <form method="post">
            <input type="hidden" name="question_id" value="<?php echo (int)$question['id']; ?>">
            <input type="hidden" name="index" value="<?php echo (int)$index; ?>">

            <div class="d-grid" style="display:grid; gap:10px;">
              <?php foreach (($question['options'] ?? []) as $opt): ?>
                <label class="option-item d-flex align-items-center" style="gap:10px; cursor:pointer;">
                  <input type="radio" name="option_id" value="<?php echo (int)$opt['id']; ?>" <?php echo ((int)$picked_option_id === (int)$opt['id']) ? 'checked' : ''; ?>>
                  <span><?php echo html_escape($opt['option_text']); ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
              <button class="btn btn-light" type="submit" name="nav_action" value="back" <?php echo ($index <= 0) ? 'disabled' : ''; ?>>Back</button>

              <?php if ($index < $total - 1): ?>
                <button class="btn btn-primary" type="submit" name="nav_action" value="next">Next</button>
              <?php else: ?>
                <button class="btn btn-success" type="submit" name="nav_action" value="submit">Submit Exam</button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>