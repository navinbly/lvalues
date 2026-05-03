<?php
$logged_user_id = (int)$this->session->userdata('user_id');
$notifications = [];
$number_of_unread_notification = 0;
$use_lms_notifications = $this->db->table_exists('lms_notifications');

if ($logged_user_id > 0) {
    if ($use_lms_notifications) {
        $notifications = $this->db
            ->where('user_id', $logged_user_id)
            ->order_by('is_read', 'ASC')
            ->order_by('id', 'DESC')
            ->limit(8)
            ->get('lms_notifications')
            ->result_array();

        $number_of_unread_notification = (int)$this->db
            ->where('user_id', $logged_user_id)
            ->where('is_read', 0)
            ->count_all_results('lms_notifications');
    } else {
        $notifications = $this->db
            ->where('to_user', $logged_user_id)
            ->order_by('status', 'ASC')
            ->order_by('id', 'DESC')
            ->limit(8)
            ->get('notifications')
            ->result_array();

        $number_of_unread_notification = (int)$this->db
            ->where('to_user', $logged_user_id)
            ->where('status', 0)
            ->count_all_results('notifications');
    }
}
?>

<a class="menu_wisth_tgl mt-1">
    <i class="far fa-bell"></i>
    <?php if ($number_of_unread_notification > 0): ?>
        <p class="menu_number"><?php echo (int)$number_of_unread_notification; ?></p>
    <?php endif; ?>
</a>

<div class="menu_pro_wish lvalues-notification-dropdown" style="width: 320px;">
    <div class="d-flex align-items-center px-3 pt-3 pb-2">
        <strong><?php echo get_phrase('Notifications'); ?></strong>

        <?php if ($use_lms_notifications && $number_of_unread_notification > 0): ?>
            <a href="<?php echo site_url('notifications/mark_all_read'); ?>" class="text-secondary ms-auto">
                <small><?php echo get_phrase('Mark all as read'); ?></small>
            </a>
        <?php endif; ?>
    </div>

    <div class="overflow-control" id="notifications">
        <?php foreach ($notifications as $notification): ?>
            <?php
                if ($use_lms_notifications) {
                    $is_unread = ((int)($notification['is_read'] ?? 0) === 0);
                    $open_url = site_url('notifications/read/' . (int)$notification['id']);
                    $title = $notification['title'] ?? '';
                    $description = $notification['message'] ?? '';
                    $type = $notification['reference_type'] ?? 'general';
                    $created_at = !empty($notification['created_at']) ? strtotime($notification['created_at']) : time();
                } else {
                    $is_unread = ((int)($notification['status'] ?? 1) === 0);
                    $open_url = site_url('home/get_my_notification/open/' . (int)$notification['id']);
                    $title = $notification['title'] ?? '';
                    $description = $notification['description'] ?? '';
                    $type = $notification['type'] ?? 'general';
                    $created_at = $notification['created_at'] ?? time();
                }

                $icon = 'far fa-bell';
                if ($type === 'signup') $icon = 'fas fa-user-plus';
                if ($type === 'assignment') $icon = 'fas fa-tasks';
                if ($type === 'test') $icon = 'fas fa-clipboard-check';
                if ($type === 'session') $icon = 'fas fa-video';
                if ($type === 'recording') $icon = 'fas fa-play-circle';
            ?>

            <a href="<?php echo $open_url; ?>"
               class="notify-item cursor-pointer d-flex py-2 px-3 <?php echo $is_unread ? 'unread' : ''; ?>"
               style="width: 320px; text-decoration:none; color:inherit;">
                <div class="notify-icon">
                    <i class="<?php echo html_escape($icon); ?>"></i>
                </div>
                <div class="ps-2 w-100">
                    <p class="notify-details text-13px mb-1">
                        <?php echo html_escape($title); ?>
                        <small class="text-muted float-end">
                            <?php echo function_exists('get_past_time') ? get_past_time($created_at) : date('d M Y', (int)$created_at); ?>
                        </small>
                    </p>
                    <div class="text-muted mb-0 user-msg text-13px">
                        <?php echo html_escape($description); ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (empty($notifications)): ?>
            <div class="text-center px-4 py-4">
                <img loading="lazy" width="80" src="<?php echo site_url('assets/global/image/empty-notification.png'); ?>" alt="">
                <h6 class="mt-2 mb-1"><?php echo get_phrase('No notification'); ?></h6>
                <p class="text-muted text-13px mb-0">
                    <?php echo get_phrase('Notifications about your activity will show up here.'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="menu_pro_btn px-3 pb-3">
        <a href="<?php echo site_url('notifications'); ?>" class="btn btn-primary text-white w-100">
            <?php echo get_phrase('View all'); ?>
        </a>
    </div>
</div>
