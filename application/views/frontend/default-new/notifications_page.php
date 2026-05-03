<section class="lvalues-notifications-page py-5">
    <style>
        .lvalues-notifications-page{
            background:#f7f9ff;
            min-height:520px;
        }
        .lvalues-notifications-wrap{
            max-width:980px;
            margin:0 auto;
        }
        .lvalues-notifications-head{
            background:linear-gradient(135deg,#6c4df6,#7b5cff);
            color:#fff;
            border-radius:22px;
            padding:30px;
            box-shadow:0 18px 45px rgba(108,77,246,.22);
            margin-bottom:24px;
        }
        .lvalues-notifications-head h2{
            margin:0 0 8px;
            font-weight:800;
            color:#fff;
        }
        .lvalues-notifications-head p{
            margin:0;
            color:rgba(255,255,255,.88);
        }
        .lvalues-notification-card{
            background:#fff;
            border:1px solid #e9ecf5;
            border-radius:20px;
            box-shadow:0 18px 45px rgba(34,41,87,.08);
            overflow:hidden;
        }
        .lvalues-empty-notifications{
            text-align:center;
            padding:55px 25px;
        }
        .lvalues-empty-icon{
            width:82px;
            height:82px;
            line-height:82px;
            border-radius:50%;
            background:#f4f1ff;
            color:#6c4df6;
            font-size:30px;
            margin:0 auto 18px;
        }
        .lvalues-empty-notifications h3{
            margin:0 0 10px;
            font-size:24px;
            font-weight:800;
            color:#17213a;
        }
        .lvalues-empty-notifications p{
            max-width:560px;
            margin:0 auto 22px;
            color:#68738c;
            line-height:1.7;
        }
        .lvalues-status-table{
            max-width:680px;
            margin:22px auto 0;
            border:1px solid #eef0f7;
            border-radius:16px;
            overflow:hidden;
            text-align:left;
        }
        .lvalues-status-row{
            display:flex;
            justify-content:space-between;
            gap:16px;
            padding:14px 18px;
            border-bottom:1px solid #eef0f7;
            color:#566179;
        }
        .lvalues-status-row:last-child{border-bottom:0;}
        .lvalues-status-row strong{color:#17213a;}
        .lvalues-notification-list-item{
            display:flex;
            gap:14px;
            padding:18px 22px;
            border-bottom:1px solid #eef0f7;
            color:inherit;
            text-decoration:none;
        }
        .lvalues-notification-list-item:hover{
            background:#f9f7ff;
            text-decoration:none;
            color:inherit;
        }
        .lvalues-notification-list-item.unread{
            background:#fbfaff;
        }
        .lvalues-list-icon{
            width:44px;
            height:44px;
            min-width:44px;
            line-height:44px;
            text-align:center;
            border-radius:14px;
            background:#f4f1ff;
            color:#6c4df6;
        }
        .lvalues-list-content h5{
            margin:0 0 6px;
            color:#17213a;
            font-size:16px;
            font-weight:800;
        }
        .lvalues-list-content p{
            margin:0;
            color:#68738c;
            line-height:1.6;
        }
        @media(max-width:575px){
            .lvalues-notifications-head{padding:22px;border-radius:18px;}
            .lvalues-status-row{display:block;}
        }
    </style>

    <div class="container">
        <div class="lvalues-notifications-wrap">
            <div class="lvalues-notifications-head">
                <h2><?php echo get_phrase('Notifications'); ?></h2>
                <p><?php echo get_phrase('Stay updated with batches, sessions, assignments, tests, invites, and learning activity.'); ?></p>
            </div>

            <div class="lvalues-notification-card">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $title = $notification['title'] ?? 'Notification';
                            $description = $notification['message'] ?? ($notification['description'] ?? '');
                            $is_unread = isset($notification['is_read']) ? ((int)$notification['is_read'] === 0) : ((int)($notification['status'] ?? 1) === 0);
                            $id = (int)($notification['id'] ?? 0);
                            $url = site_url('notifications/read/'.$id);
                            $type = $notification['reference_type'] ?? ($notification['type'] ?? 'general');
                            $icon = 'far fa-bell';
                            if ($type === 'assignment') $icon = 'fas fa-tasks';
                            if ($type === 'test') $icon = 'fas fa-clipboard-check';
                            if ($type === 'session') $icon = 'fas fa-video';
                            if ($type === 'recording') $icon = 'fas fa-play-circle';
                            if ($type === 'batch_invite') $icon = 'fas fa-users';
                        ?>
                        <a href="<?php echo $url; ?>" class="lvalues-notification-list-item <?php echo $is_unread ? 'unread' : ''; ?>">
                            <div class="lvalues-list-icon"><i class="<?php echo html_escape($icon); ?>"></i></div>
                            <div class="lvalues-list-content">
                                <h5><?php echo html_escape($title); ?></h5>
                                <p><?php echo html_escape($description); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="lvalues-empty-notifications">
                        <div class="lvalues-empty-icon">
                            <i class="far fa-bell-slash"></i>
                        </div>
                        <h3><?php echo get_phrase('No notifications yet'); ?></h3>
                        <p>
                            <?php echo get_phrase('You do not have any notifications right now. When you receive batch invites, session updates, assignments, tests, approvals, or learning activity, they will appear here.'); ?>
                        </p>
                        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:20px;">

							<!-- Back to Home -->
							<a href="<?php echo site_url(); ?>" class="btn btn-primary" style="min-width:160px;">
								Back to home
							</a>

							<!-- Back to Dashboard -->
							<a href="<?php echo site_url('home/my_courses'); ?>" class="btn btn-outline-primary" style="min-width:160px;">
								Back to Dashboard
							</a>

						</div>

                        <div class="lvalues-status-table">
                            <div class="lvalues-status-row">
                                <strong><?php echo get_phrase('Batch invite'); ?></strong>
                                <span><?php echo get_phrase('No update'); ?></span>
                            </div>
                            <div class="lvalues-status-row">
                                <strong><?php echo get_phrase('Live session'); ?></strong>
                                <span><?php echo get_phrase('No update'); ?></span>
                            </div>
                            <div class="lvalues-status-row">
                                <strong><?php echo get_phrase('Assignment / Test'); ?></strong>
                                <span><?php echo get_phrase('No update'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
