<?php
$tutors = isset($tutors) && is_array($tutors) ? $tutors : [];
$selected_subject_id = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
$selected_category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$selected_class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$current_query = isset($search_string) ? $search_string : (isset($_GET['query']) ? $_GET['query'] : '');
$current_url = current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
?>

<div class="grid-view-body courses courses-list-view-body">

    <div class="tutor-marketplace-hero mb-4">
        <div>
            <span class="tutor-marketplace-kicker">Tutor marketplace</span>
            <h2>Find tutors by subject, mode, fee, rating, and location</h2>
            <p>Compare verified tutor profiles, teaching mode, experience, subjects, pricing, reviews, and demo request options before contacting a tutor.</p>
        </div>
        <div class="tutor-marketplace-actions">
            <a href="<?php echo site_url('sign_up'); ?>" class="btn btn-primary btn-sm">Create student account</a>
            <a href="<?php echo site_url('sign_up?instructor=yes'); ?>" class="btn btn-outline-primary btn-sm">Become a tutor</a>
        </div>
    </div>

    <div class="tutor-discovery-trust mb-3">
        <span><i class="fa-solid fa-circle-check"></i> Verified tutor badge</span>
        <span><i class="fa-solid fa-star"></i> Ratings and reviews</span>
        <span><i class="fa-solid fa-wallet"></i> Fee transparency</span>
        <span><i class="fa-solid fa-video"></i> Demo request flow</span>
        <span><i class="fa-solid fa-location-dot"></i> Location and mode filters</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
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
            <?php echo get_phrase('Search Tutors'); ?> · <?php echo get_phrase('Best match'); ?>
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
                $request_modal_id = 'tutorRequestModal_' . (int) $t['tutor_profile_id'];
                $profile_modal_id = 'tutorProfileModal_' . (int) $t['tutor_profile_id'];
                // phase4_verified_tutor_badge
                $is_verified_tutor = strtolower((string)($t['tutor_profile_status'] ?? 'active')) === 'active';

                $headline = trim((string)($t['headline'] ?? ''));
                $qualification = trim((string)($t['qualification'] ?? ''));
                $experience_years = (int)($t['experience_years'] ?? 0);
                $bio = trim((string)($t['bio'] ?? ''));
                $subject_names = trim((string)($t['subject_names'] ?? ''));
                $pincode = trim((string)($t['pincode'] ?? ''));
                $hourly_fee = $t['hourly_fee'] ?? '';
                $city = trim((string)($t['city'] ?? ''));
                $state = trim((string)($t['state'] ?? ''));
                $country = trim((string)($t['country'] ?? ''));
                $profile_location_line = trim($city . ($state !== '' ? ', ' . $state : '') . ($country !== '' ? ', ' . $country : '') . ($pincode !== '' ? ' - ' . $pincode : ''));

                $photo = !empty($t['profile_photo']) ? $t['profile_photo'] : '';
                $photo_path = $photo ? ('uploads/tutors/'.$photo) : '';
                $photo_url = (!empty($photo_path) && file_exists($photo_path)) ? base_url($photo_path) : base_url('assets/global/image/user.png');
                $availability_label = 'Demo request available';
                $response_label = 'Response after request';
                $fee_label = !empty($hourly_fee) ? currency($hourly_fee) . ' / hour' : 'Fee on request';
            ?>

            <div class="courses-list-view-card-body courses-card-body" style="cursor: default;">
                <div class="courses-card-image">
                    <img loading="lazy" src="<?php echo $photo_url; ?>" alt="Tutor" />

                    <div class="courses-card-image-text">
                        <h3><?php echo strtoupper(html_escape($mode)); ?></h3>
                    </div>
                </div>

                <div class="courses-text w-100">
                    <div class="courses-d-flex-text">
                        <h5 class="mb-0">
                            <a href="javascript:void(0);" class="tutor-name-link" data-bs-toggle="modal" data-bs-target="#<?php echo $profile_modal_id; ?>">
                                <?php echo html_escape($full_name ?: 'Tutor'); ?>
                            </a>
                        </h5>
                        <?php if ($is_verified_tutor): ?><span class="tutor-verified-badge"><i class="fa-solid fa-circle-check"></i> Verified tutor</span><?php endif; ?>
                        <span class="compare-img">
                            <?php if (!empty($hourly_fee)): ?>
                                <strong><?php echo currency($hourly_fee); ?> / hour</strong>
                            <?php else: ?>
                                <strong><?php echo get_phrase('Contact'); ?></strong>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="tutor-card-chips">
                        <span><i class="fa-solid fa-video"></i> <?php echo html_escape($availability_label); ?></span>
                        <span><i class="fa-solid fa-reply"></i> <?php echo html_escape($response_label); ?></span>
                        <span><i class="fa-solid fa-wallet"></i> <?php echo html_escape($fee_label); ?></span>
                        <span><i class="fa-solid fa-location-dot"></i> <?php echo html_escape($mode === 'both' ? 'Online + offline' : ucfirst($mode)); ?></span>
                    </div>

                    <div class="review-icon">
                        <p><?php echo number_format($rating, 1); ?></p>
                        <p><i class="fa-solid fa-star <?php echo ($rating > 0 ? 'filled' : ''); ?>"></i></p>
                        <p>(<?php echo $rating_count; ?> <?php echo get_phrase('Reviews'); ?>)</p>
                        <?php if (!empty($t['distance_km'])): ?>
                            <p><i class="fa-solid fa-location-dot"></i> <?php echo number_format((float)$t['distance_km'], 1); ?> km</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($subject_names)): ?>
                        <p class="mb-2"><strong><?php echo get_phrase('Subjects'); ?>:</strong> <?php echo html_escape($subject_names); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($headline)): ?>
                        <p class="ellipsis-line-2"><?php echo html_escape($headline); ?></p>
                    <?php endif; ?>

                    <div class="courses-price-border">
                        <div class="courses-price">
                            <div class="courses-price-left">
                                <p class="mb-0"><i class="fa-regular fa-id-badge"></i>
                                    <?php echo html_escape($qualification); ?>
                                    <?php if (!empty($experience_years)): ?>
                                        · <?php echo (int)$experience_years; ?> yrs
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($location)): ?>
                                    <p class="mb-0 text-muted"><i class="fa-solid fa-location-dot"></i> <?php echo html_escape($location); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="courses-price-right">
                                <?php if ($this->session->userdata('user_login')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $request_modal_id; ?>">
                                        <?php echo get_phrase('Book Demo / Send Request'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo site_url('login'); ?>" class="btn btn-sm btn-outline-primary">
                                        <?php echo get_phrase('Login to Request'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="<?php echo $profile_modal_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content tutor-profile-modal-content">
                        <div class="modal-header tutor-profile-modal-header">
                            <h5 class="modal-title tutor-profile-modal-title"><?php echo get_phrase('Tutor Profile'); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo get_phrase('Close'); ?>"></button>
                        </div>
                        <div class="modal-body tutor-profile-modal-body">
                            <div class="row align-items-start">
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <img src="<?php echo $photo_url; ?>" alt="Tutor" class="tutor-profile-avatar">
                                    <h4 class="tutor-profile-name"><?php echo html_escape($full_name ?: 'Tutor'); ?></h4>
                                    <?php if ($headline !== ''): ?>
                                        <p class="tutor-profile-headline"><?php echo html_escape($headline); ?></p>
                                    <?php endif; ?>

                                    <div class="tutor-profile-badges">
                                        <span class="tutor-pill tutor-pill-light"><?php echo strtoupper(html_escape($mode)); ?></span>
                                        <?php if ($is_verified_tutor): ?><span class="tutor-pill tutor-pill-success"><i class="fa-solid fa-circle-check"></i> Verified</span><?php endif; ?>
                                        <?php if (!empty($hourly_fee)): ?>
                                            <span class="tutor-pill tutor-pill-primary"><?php echo currency($hourly_fee); ?> / hour</span>
                                        <?php endif; ?>
                                        <span class="tutor-pill tutor-pill-light">Demo request</span>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Subjects'); ?></div>
                                                <div class="tutor-profile-value"><?php echo html_escape($subject_names !== '' ? $subject_names : 'NA'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Qualification'); ?></div>
                                                <div class="tutor-profile-value"><?php echo html_escape($qualification !== '' ? $qualification : 'NA'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Experience'); ?></div>
                                                <div class="tutor-profile-value"><?php echo $experience_years > 0 ? (int)$experience_years . ' yrs' : 'NA'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Rating'); ?></div>
                                                <div class="tutor-profile-value"><?php echo number_format($rating, 1); ?> (<?php echo $rating_count; ?> <?php echo get_phrase('Reviews'); ?>)</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Location'); ?></div>
                                                <div class="tutor-profile-value"><?php echo html_escape($profile_location_line !== '' ? $profile_location_line : 'NA'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label">Availability</div>
                                                <div class="tutor-profile-value">Demo request available. Confirm schedule after sending a request.</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label">Verification</div>
                                                <div class="tutor-profile-value"><?php echo $is_verified_tutor ? 'Profile active and qualification details provided.' : 'Verification details pending.'; ?></div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="tutor-profile-field">
                                                <div class="tutor-profile-label">What to ask before booking</div>
                                                <div class="tutor-profile-value">Ask about weekly availability, demo class timing, teaching plan, homework support, assessment style, and parent progress updates.</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="tutor-profile-field mb-0">
                                                <div class="tutor-profile-label"><?php echo get_phrase('Bio'); ?></div>
                                                <div class="tutor-profile-bio-box"><?php echo nl2br(html_escape($bio !== '' ? $bio : 'No bio added yet.')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer tutor-profile-modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php echo get_phrase('Close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($this->session->userdata('user_login')): ?>
            <div class="modal fade" id="<?php echo $request_modal_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="<?php echo site_url('home/send_tutor_request'); ?>" method="post" data-lv-event="send_request" data-lv-label="tutor_list_request">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo get_phrase('Book Demo / Send Request'); ?> - <?php echo html_escape($full_name ?: 'Tutor'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo get_phrase('Close'); ?>"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="tutor_user_id" value="<?php echo (int) $t['tutor_user_id']; ?>">
                                <input type="hidden" name="tutor_profile_id" value="<?php echo (int) $t['tutor_profile_id']; ?>">
                                <input type="hidden" name="category_id" value="<?php echo (int) $selected_category_id; ?>">
                                <input type="hidden" name="class_id" value="<?php echo (int) $selected_class_id; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo (int) $selected_subject_id; ?>">
                                <input type="hidden" name="query_text" value="<?php echo html_escape($current_query); ?>">
                                <input type="hidden" name="redirect_url" value="<?php echo html_escape($current_url); ?>">

                                <div class="form-group">
                                    <label><?php echo get_phrase('Tutor'); ?></label>
                                    <input type="text" class="form-control" value="<?php echo html_escape($full_name ?: 'Tutor'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label><?php echo get_phrase('Subjects'); ?></label>
                                    <input type="text" class="form-control" value="<?php echo html_escape($subject_names); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label><?php echo get_phrase('Preferred Mode'); ?></label>
                                    <select name="preferred_mode" class="form-control" required>
                                        <option value="both"><?php echo get_phrase('Both'); ?></option>
                                        <option value="online"><?php echo get_phrase('Online'); ?></option>
                                        <option value="offline"><?php echo get_phrase('Offline'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?php echo get_phrase('Location'); ?></label>
                                    <input type="text" class="form-control" name="student_location" placeholder="City / Area / Pincode">
                                </div>
                                <div class="form-group mb-0">
                                    <label><?php echo get_phrase('Message'); ?></label>
                                    <textarea class="form-control" name="message" rows="4" placeholder="Briefly explain what help you need" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php echo get_phrase('Cancel'); ?></button>
                                <button type="submit" class="btn btn-primary"><?php echo get_phrase('Submit Request'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="pagenation-items mb-0 mt-3">
            <?php echo $this->pagination->create_links(); ?>
        </div>
    </div>
</div>

<style>
.tutor-marketplace-hero{
    display:flex;
    justify-content:space-between;
    gap:18px;
    align-items:flex-start;
    background:#ffffff;
    border:1px solid #e8ecf5;
    border-radius:14px;
    padding:20px;
    box-shadow:0 12px 30px rgba(29,39,70,.06);
}
.tutor-marketplace-kicker{
    display:inline-flex;
    align-items:center;
    margin-bottom:8px;
    padding:5px 10px;
    border-radius:999px;
    background:#eef7ff;
    color:#0b75bd;
    font-size:12px;
    font-weight:800;
}
.tutor-marketplace-hero h2{
    margin:0 0 8px;
    color:#1d2746;
    font-size:26px;
    font-weight:800;
    line-height:1.25;
}
.tutor-marketplace-hero p{
    margin:0;
    color:#65708a;
    line-height:1.65;
}
.tutor-marketplace-actions{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    justify-content:flex-end;
}
.tutor-discovery-trust,
.tutor-card-chips{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}
.tutor-discovery-trust span,
.tutor-card-chips span{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    border:1px solid #e3e8f2;
    background:#f8fafc;
    color:#374151;
    font-size:12px;
    font-weight:700;
    padding:7px 10px;
}
.tutor-card-chips{
    margin:10px 0 12px;
}
.tutor-card-chips span{
    background:#ffffff;
}
.tutor-name-link{
    color: inherit;
    text-decoration: none;
    cursor: pointer;
}
.tutor-name-link:hover{
    color: #6c4df6;
    text-decoration: underline;
}

.tutor-profile-modal-content{
    border-radius: 14px;
    border: 1px solid #ececf5;
    overflow: hidden;
}
.tutor-profile-modal-header{
    background: #ffffff;
    border-bottom: 1px solid #ececf5;
    padding: 16px 20px;
}
.tutor-profile-modal-title{
    font-weight: 700;
    color: #1d2746;
}
.tutor-profile-modal-body{
    background: #ffffff;
    padding: 20px;
}
.tutor-profile-modal-footer{
    background: #ffffff;
    border-top: 1px solid #ececf5;
    padding: 14px 20px;
}
.tutor-profile-avatar{
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #edf0f7;
    box-shadow: 0 6px 20px rgba(29, 39, 70, 0.08);
}
.tutor-profile-name{
    margin-top: 16px;
    margin-bottom: 6px;
    font-size: 24px;
    font-weight: 700;
    color: #1d2746;
}
.tutor-profile-headline{
    margin-bottom: 14px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}
.tutor-profile-badges{
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}
.tutor-pill{
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.tutor-pill-light{
    background: #f4f6fb;
    color: #374151;
    border: 1px solid #dfe4ee;
}
.tutor-pill-primary{
    background: #6c4df6;
    color: #ffffff;
}
.tutor-pill-success,
.tutor-verified-badge{
    background: #e9f8ef;
    color: #13753a;
    border: 1px solid #bce8cc;
}
.tutor-verified-badge{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    margin-left: 8px;
    white-space: nowrap;
}
.tutor-profile-field{
    margin-bottom: 16px;
}
.tutor-profile-label{
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 6px;
}
.tutor-profile-value{
    color: #1f2937;
    font-size: 15px;
    line-height: 1.6;
}
.tutor-profile-bio-box{
    background: #f8f9fc;
    border: 1px solid #ececf5;
    border-radius: 10px;
    padding: 12px 14px;
    min-height: 90px;
    color: #1f2937;
    line-height: 1.7;
    white-space: normal;
    word-break: break-word;
}
.modal .btn-close{
    opacity: 1;
}
@media (max-width: 767px){
    .tutor-marketplace-hero{
        display:block;
    }
    .tutor-marketplace-actions{
        justify-content:flex-start;
        margin-top:14px;
    }
}
</style>
