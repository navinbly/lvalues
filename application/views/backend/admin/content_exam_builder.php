<?php
// Variables: $node, $exam, $questions
$node_id = (int)($node['node_id'] ?? 0);
?>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
          <div>
            <h4 class="header-title mb-1">MCQ Exam Builder</h4>
            <div class="text-muted small">For: <b><?php echo html_escape($node['full_path'] ?? $node['title']); ?></b></div>
          </div>
          <div class="mt-2 mt-md-0">
            <a href="<?php echo site_url('admin/content_nodes'); ?>" class="btn btn-sm btn-light">Back to Content Nodes</a>
            <a href="<?php echo site_url('exam/'. $node_id); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview (published only)">Preview Exam</a>
          </div>
        </div>

        <hr>

        <?php if ($this->session->flashdata('error_message')): ?>
          <div class="alert alert-danger"><?php echo $this->session->flashdata('error_message'); ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('flash_message')): ?>
          <div class="alert alert-success"><?php echo $this->session->flashdata('flash_message'); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('admin/content_exam_save/'.$node_id); ?>" id="examBuilderForm">
          <div class="form-row">
            <div class="form-group col-md-8">
              <label>Exam title</label>
              <input type="text" class="form-control" name="title" value="<?php echo html_escape($exam['title'] ?? ($node['title'].' Exam')); ?>" required>
            </div>
            <div class="form-group col-md-2">
              <label>Passing marks</label>
              <input type="number" step="0.5" class="form-control" name="passing_marks" value="<?php echo html_escape($exam['passing_marks'] ?? 0); ?>">
            </div>
            <div class="form-group col-md-2">
              <label>Publish</label>
              <div class="custom-control custom-switch mt-2">
                <input type="checkbox" class="custom-control-input" id="isPublished" name="is_published" value="1" <?php echo (!empty($exam) && (int)$exam['is_published']===1) ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="isPublished"><?php echo (!empty($exam) && (int)$exam['is_published']===1) ? 'Published' : 'Draft'; ?></label>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Exam description (shown on first page)</label>
            <textarea class="form-control" name="description" rows="3"><?php echo html_escape($exam['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label>Exam policy / instructions</label>
            <textarea class="form-control" name="policy" rows="3"><?php echo html_escape($exam['policy'] ?? ''); ?></textarea>
          </div>

          <hr>

          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Questions</h5>
            <button type="button" class="btn btn-sm btn-primary" id="btnAddQuestion">+ Add question</button>
          </div>

          <div id="questionsWrap" class="mt-3"></div>

          <div class="mt-4">
            <button type="submit" class="btn btn-success">Save Exam</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var wrap = document.getElementById('questionsWrap');
  var addBtn = document.getElementById('btnAddQuestion');
  var pub = document.getElementById('isPublished');
  if (pub) {
    pub.addEventListener('change', function(){
      var lbl = document.querySelector('label[for="isPublished"]');
      if (lbl) lbl.textContent = pub.checked ? 'Published' : 'Draft';
    });
  }

  function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function renderQuestion(qIndex, data){
    data = data || {};
    var qText = data.question_text || '';
    var marks = (data.marks != null) ? data.marks : 1;
    var options = (data.options && data.options.length) ? data.options : [{option_text:''},{option_text:''},{option_text:''},{option_text:''}];
    var correctIndex = 0;
    // try detect correct index from options[].is_correct
    for (var i=0;i<options.length;i++) {
      if (String(options[i].is_correct) === '1') { correctIndex = i; break; }
    }

    var html = '';
    html += '<div class="card mb-3 question-card" data-q="'+qIndex+'">';
    html += '  <div class="card-body">';
    html += '    <div class="d-flex justify-content-between align-items-center">';
    html += '      <h6 class="mb-2">Question ' + (qIndex+1) + '</h6>';
    html += '      <button type="button" class="btn btn-sm btn-outline-danger btnRemoveQuestion">Remove</button>';
    html += '    </div>';
    html += '    <div class="form-group">';
    html += '      <label>Question</label>';
    html += '      <textarea class="form-control" name="questions['+qIndex+'][text]" rows="2" required>'+esc(qText)+'</textarea>';
    html += '    </div>';
    html += '    <div class="form-row">';
    html += '      <div class="form-group col-md-3">';
    html += '        <label>Marks</label>';
    html += '        <input type="number" step="0.5" class="form-control" name="questions['+qIndex+'][marks]" value="'+esc(String(marks))+'">';
    html += '      </div>';
    html += '      <div class="form-group col-md-9">';
    html += '        <label>Correct answer</label>';
    html += '        <select class="form-control" name="questions['+qIndex+'][correct_index]" data-correct-select>'; 
    for (var k=0;k<options.length;k++) {
      html += '          <option value="'+k+'" '+(k===correctIndex?'selected':'')+'>Option '+(k+1)+'</option>';
    }
    html += '        </select>';
    html += '      </div>';
    html += '    </div>';

    html += '    <label class="mb-2">Options</label>';
    html += '    <div class="options-wrap">';
    for (var j=0;j<options.length;j++) {
      html += '      <div class="form-group d-flex align-items-center">';
      html += '        <span class="mr-2 text-muted" style="min-width:70px;">Option '+(j+1)+'</span>';
      html += '        <input type="text" class="form-control" name="questions['+qIndex+'][options]['+j+']" value="'+esc(options[j].option_text||'')+'" required>'; 
      html += '      </div>';
    }
    html += '    </div>';

    html += '  </div>';
    html += '</div>';
    return html;
  }

  function renumber(){
    var cards = wrap.querySelectorAll('.question-card');
    cards.forEach(function(card, idx){
      card.dataset.q = idx;
      var h = card.querySelector('h6');
      if (h) h.textContent = 'Question ' + (idx+1);

      // update name attributes inside
      card.querySelectorAll('[name]').forEach(function(el){
        el.name = el.name.replace(/questions\[\d+\]/, 'questions['+idx+']');
      });
    });
  }

  addBtn.addEventListener('click', function(){
    var idx = wrap.querySelectorAll('.question-card').length;
    wrap.insertAdjacentHTML('beforeend', renderQuestion(idx, null));
  });

  wrap.addEventListener('click', function(e){
    if (e.target && e.target.classList.contains('btnRemoveQuestion')) {
      var card = e.target.closest('.question-card');
      if (card) card.remove();
      renumber();
    }
  });

  // Initial render from PHP
  var initial = <?php echo json_encode($questions ?: [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  if (initial && initial.length) {
    for (var i=0;i<initial.length;i++) {
      wrap.insertAdjacentHTML('beforeend', renderQuestion(i, initial[i]));
    }
  } else {
    wrap.insertAdjacentHTML('beforeend', renderQuestion(0, null));
  }
})();
</script>
