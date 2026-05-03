<?php
$test = $test_data['test'];
$attempt = $test_data['attempt'];
$questions = $test_data['questions'];

$duration_seconds = ((int)$test['duration_minutes']) * 60;
?>

<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4><?php echo html_escape($test['title']); ?></h4>
                    <p class="text-muted"><?php echo html_escape($test['description'] ?? ''); ?></p>
                </div>

                <div>
                    <h5>
                        Time Left:
                        <span id="timer"><?php echo (int)$test['duration_minutes']; ?>:00</span>
                    </h5>
                </div>
            </div>

            <hr>

            <form id="testForm" method="post" action="<?php echo site_url('student_batch/submit_test/' . (int)$attempt['id']); ?>">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="border rounded p-3 mb-3">
                        <strong>
                            Q<?php echo $index + 1; ?>.
                            <?php echo html_escape($q['question_text']); ?>
                        </strong>

                        <div class="mt-2">
                            <label>
                                <input type="radio" name="answers[<?php echo (int)$q['id']; ?>]" value="A">
                                A. <?php echo html_escape($q['option_a']); ?>
                            </label>
                        </div>

                        <div>
                            <label>
                                <input type="radio" name="answers[<?php echo (int)$q['id']; ?>]" value="B">
                                B. <?php echo html_escape($q['option_b']); ?>
                            </label>
                        </div>

                        <div>
                            <label>
                                <input type="radio" name="answers[<?php echo (int)$q['id']; ?>]" value="C">
                                C. <?php echo html_escape($q['option_c']); ?>
                            </label>
                        </div>

                        <div>
                            <label>
                                <input type="radio" name="answers[<?php echo (int)$q['id']; ?>]" value="D">
                                D. <?php echo html_escape($q['option_d']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-success" onclick="return confirm('Submit test now?');">
                    Submit Test
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let timeLeft = <?php echo (int)$duration_seconds; ?>;
const timerEl = document.getElementById('timer');
const testForm = document.getElementById('testForm');

function updateTimer() {
    let minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;

    timerEl.innerHTML = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

    if (timeLeft <= 0) {
        testForm.submit();
        return;
    }

    timeLeft--;
}

setInterval(updateTimer, 1000);
updateTimer();
</script>