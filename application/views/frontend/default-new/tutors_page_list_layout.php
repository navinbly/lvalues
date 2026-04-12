<?php
// Tutor list layout - mirrors course list style so UI feels consistent.
$tutors = isset($tutors) && is_array($tutors) ? $tutors : [];
?>

<div class="grid-view-body courses courses-list-view-body">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <strong>
                <?php echo get_phrase('Showing'); ?>
                <?php echo count($tutors); ?>
                <?php echo get_phrase('of'); ?>
                <?php echo (int)($total_result ?? 0); ?>
                <?php echo get_phrase('Results'); ?>
            </strong>
        </div>
        <div class="text-muted small">
            <?php echo get_phrase('Search Tutors'); ?>
        </div>
    </div>

    <div class="courses-card courses-list-view-card">
        <?php foreach ($tutors as $t): ?>
            <?php
                $full_name = trim(($t['first_name'] ?? '').' '.($t['last_name'] ?? ''));
                $mode = $t['teaching_mode'] ?? 'both';
                $location = trim(($t['city'] ?? '').(!empty($t['state']) ? ', '.$t['state'] : '').(!empty($t['country']) ? ', '.$t['country'] : ''));
                $rating = (float)($t['avg_rating'] ?? 0);
                $rating_count = (int)($t['rating_count'] ?? 0);
            ?>

            <div class="courses-list-view-card-body courses-card-body" style="cursor: default;">
                <div class="courses-card-image">
                    <?php
                        $photo = !empty($t['profile_photo']) ? $t['profile_photo'] : '';
                        $photo_path = $photo ? ('uploads/tutors/'.$photo) : '';
                        $photo_url = (!empty($photo_path) && file_exists($photo_path)) ? base_url($photo_path) : base_url('assets/global/image/user.png');
                    ?>
                    <img loading="lazy" src="<?php echo $photo_url; ?>" alt="Tutor" />

                    <div class="courses-card-image-text">
                        <h3><?php echo strtoupper(html_escape($mode)); ?></h3>
                    </div>
                </div>

                <div class="courses-text w-100">
                    <div class="courses-d-flex-text">
                        <h5><?php echo html_escape($full_name ?: 'Tutor'); ?></h5>
                        <span class="compare-img">
                            <?php if (!empty($t['hourly_fee'])): ?>
                                <strong><?php echo currency($t['hourly_fee']); ?></strong>
                            <?php else: ?>
                                <strong><?php echo get_phrase('Contact'); ?></strong>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="review-icon">
                        <p><?php echo number_format($rating, 1); ?></p>
                        <p><i class="fa-solid fa-star <?php echo ($rating > 0 ? 'filled' : ''); ?>"></i></p>
                        <p>(<?php echo $rating_count; ?> <?php echo get_phrase('Reviews'); ?>)</p>
                        <?php if (!empty($t['distance_km'])): ?>
                            <p><i class="fa-solid fa-location-dot"></i> <?php echo number_format((float)$t['distance_km'], 1); ?> km</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($t['headline'])): ?>
                        <p class="ellipsis-line-2"><?php echo html_escape($t['headline']); ?></p>
                    <?php endif; ?>

                    <div class="courses-price-border">
                        <div class="courses-price">
                            <div class="courses-price-left">
                                <p class="mb-0"><i class="fa-regular fa-id-badge"></i>
                                    <?php echo html_escape($t['qualification'] ?? ''); ?>
                                    <?php if (!empty($t['experience_years'])): ?>
                                        · <?php echo (int)$t['experience_years']; ?> yrs
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($location)): ?>
                                    <p class="mb-0 text-muted"><i class="fa-solid fa-location-dot"></i> <?php echo html_escape($location); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="courses-price-right">
                                <!-- Future: request/enroll buttons go here -->
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-primary" onclick="alert('Next step: open tutor profile + send request');">
                                    <?php echo get_phrase('Send Request'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="pagenation-items mb-0 mt-3">
            <?php echo $this->pagination->create_links(); ?>
        </div>
    </div>
</div>
