<div class="course-description">
    <h3 class="description-head"><?php echo get_phrase('Course Description') ?></h3>
    <?php echo $course_details['description']; ?>
</div>

<div class="course-description course-discovery-detail">
    <h3 class="description-head">Course at a glance</h3>
    <div class="course-detail-grid">
        <div>
            <span class="course-detail-label">Learning format</span>
            <strong>Self-paced lessons with curriculum preview</strong>
        </div>
        <div>
            <span class="course-detail-label">Difficulty</span>
            <strong><?php echo get_phrase($course_details['level']); ?></strong>
        </div>
        <div>
            <span class="course-detail-label">Certificate</span>
            <strong><?php echo addon_status('certificate') ? 'Certificate eligible' : 'Completion record available'; ?></strong>
        </div>
        <div>
            <span class="course-detail-label">Career relevance</span>
            <strong>Useful for skill-building, school support, or professional growth</strong>
        </div>
    </div>
</div>

<div class="course-description">
    <h3 class="description-head"><?php echo get_phrase('What will i learn?') ?></h3>
    <ul class="step-down">
        <?php foreach (json_decode($course_details['outcomes']) as $outcome) : ?>
            <?php if ($outcome != "") : ?>
                <li><?php echo $outcome; ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<div class="course-description course-discovery-detail">
    <h3 class="description-head">Projects, practice, and proof of learning</h3>
    <ul class="step-down">
        <li>Use the curriculum and lessons to preview how the course is structured before enrolling.</li>
        <li>Look for quizzes, assignments, downloadable materials, or practice activities inside the curriculum.</li>
        <li>Use reviews, instructor profile, outcomes, and related courses to compare career fit.</li>
        <li><?php echo addon_status('certificate') ? 'Complete the course to become eligible for a certificate where configured.' : 'Track completion through your learner dashboard after enrollment.'; ?></li>
    </ul>
</div>

<div class="course-description course-discovery-detail">
    <h3 class="description-head">Recommended before enrolling</h3>
    <ul>
        <li>Review prerequisites and confirm the difficulty level matches your current skill level.</li>
        <li>Check lesson count, course duration, reviews, pricing, and instructor credibility.</li>
        <li>Use Compare to evaluate this course against another course before buying.</li>
        <li>For school or professional goals, confirm whether you need tutor support or a structured batch.</li>
    </ul>
</div>

<div class="course-description requirements">
    <h3 class="description-head"><?php echo get_phrase('Requirements') ?></h3>
    <ul>
        <?php foreach (json_decode($course_details['requirements']) as $requirement) : ?>
            <?php if ($requirement != "") : ?>
                <li><?php echo $requirement; ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>

<?php $faqs = json_decode($course_details['faqs'], true);
    $counter = 0;
  if(is_array($faqs) && count($faqs) > 0): ?>
    <div class="course-description">
        <h3 class="description-head"><?php echo get_phrase('Frequently asked question') ?></h3>

        <div class="faq-accrodion m-0">
            <?php foreach($faqs as $faq_question => $faq): ?>
                <?php ++$counter; ?>
                <div class="accordion">
                    <div class="accordion-item radius-0">
                      <h2 class="accordion-header" id="faq<?php echo $counter; ?>">
                        <button class="faq accordion-button collapsed text-18px mt-20px" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-<?php echo $counter; ?>" aria-expanded="false" aria-controls="panelsStayOpen-<?php echo $counter; ?>">
                            <?php echo $faq_question; ?>
                        </button>
                      </h2>
                      <div id="panelsStayOpen-<?php echo $counter; ?>" class="accordion-collapse collapse" aria-labelledby="faq<?php echo $counter; ?>">
                        <div class="accordion-body pt-0">
                            <?php echo $faq; ?>
                        </div>
                      </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<style>
.course-detail-trust-chips{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin:0 0 18px;
}
.course-detail-trust-chips span{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:1px solid rgba(255,255,255,.28);
    background:rgba(255,255,255,.12);
    color:#ffffff;
    border-radius:999px;
    padding:7px 10px;
    font-size:12px;
    font-weight:800;
}
.course-discovery-detail{
    border:1px solid #e8ecf5;
    border-radius:14px;
    padding:18px;
    background:#ffffff;
}
.course-detail-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
}
.course-detail-grid > div{
    border:1px solid #e8ecf5;
    border-radius:12px;
    background:#f8fafc;
    padding:14px;
}
.course-detail-label{
    display:block;
    margin-bottom:6px;
    color:#64748b;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
}
@media (max-width: 575px){
    .course-detail-grid{
        grid-template-columns:1fr;
    }
}
</style>
