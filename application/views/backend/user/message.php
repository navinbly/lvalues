<?php
$current_user = (int) $this->session->userdata('user_id');
$this->db->group_start();
$this->db->where('sender', $current_user);
$this->db->or_where('receiver', $current_user);
$this->db->group_end();
$message_threads = $this->db->get('message_thread')->result_array();
?>

<style>
    .tutor-message-shell { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 18px; }
    .tutor-panel { background: #fff; border: 1px solid #e7ecf3; border-radius: 8px; box-shadow: 0 14px 34px rgba(31, 45, 61, .05); }
    .tutor-panel__header { padding: 18px 20px; border-bottom: 1px solid #edf1f7; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .tutor-panel__title { margin: 0; color: #40556b; font-size: 16px; font-weight: 700; }
    .tutor-panel__sub { margin: 4px 0 0; color: #8b9aaa; font-size: 12px; }
    .tutor-thread-list { max-height: 640px; overflow: auto; padding: 12px; }
    .tutor-thread { display: flex; align-items: center; gap: 12px; width: 100%; padding: 12px; margin-bottom: 8px; border: 1px solid transparent; border-radius: 8px; color: #52677a; background: #f9fbfd; text-align: left; }
    .tutor-thread:hover, .tutor-thread.active { border-color: #6c63ff; background: #f3f2ff; color: #3d36c9; text-decoration: none; }
    .tutor-thread img { width: 38px; height: 38px; object-fit: cover; border-radius: 50%; }
    .tutor-thread__name { display: block; font-weight: 700; color: inherit; }
    .tutor-thread__email { display: block; color: #8493a5; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 190px; }
    .tutor-empty-state { padding: 58px 24px; text-align: center; color: #7f8da0; }
    @media (max-width: 991px) { .tutor-message-shell { grid-template-columns: 1fr; } }
</style>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h4 class="page-title mb-1"><i class="dripicons-mail title_icon"></i> <?php echo get_phrase('private_message'); ?></h4>
                    <p class="text-muted mb-0">Read and reply to student conversations without leaving your tutor workspace.</p>
                </div>
                <a href="<?php echo site_url('user/message/message_new'); ?>" class="btn btn-primary mt-2 mt-sm-0">
                    <i class="mdi mdi-pencil mr-1"></i><?php echo get_phrase('new_message'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="tutor-message-shell">
    <aside class="tutor-panel">
        <div class="tutor-panel__header">
            <div>
                <h5 class="tutor-panel__title">Inbox</h5>
                <p class="tutor-panel__sub"><?php echo count($message_threads); ?> conversation(s)</p>
            </div>
        </div>
        <div class="tutor-thread-list">
            <?php if (empty($message_threads)): ?>
                <div class="tutor-empty-state">No messages yet. Start a new conversation when you need to contact a student.</div>
            <?php endif; ?>
            <?php foreach ($message_threads as $row): ?>
                <?php
                $user_to_show_id = ((int) $row['sender'] === $current_user) ? (int) $row['receiver'] : (int) $row['sender'];
                $conversation_user = $this->user_model->get_all_user($user_to_show_id)->row_array();
                if (empty($conversation_user)) {
                    continue;
                }
                $unread_message_number = $this->crud_model->count_unread_message_of_thread($row['message_thread_code']);
                $is_active = isset($current_message_thread_code) && $current_message_thread_code == $row['message_thread_code'];
                ?>
                <a class="tutor-thread <?php echo $is_active ? 'active' : ''; ?>" href="<?php echo site_url('user/message/message_read/' . $row['message_thread_code']); ?>">
                    <img src="<?php echo $this->user_model->get_user_image_url($conversation_user['id']); ?>" alt="">
                    <span class="min-w-0">
                        <span class="tutor-thread__name"><?php echo html_escape($conversation_user['first_name'] . ' ' . $conversation_user['last_name']); ?></span>
                        <span class="tutor-thread__email"><?php echo html_escape($conversation_user['email']); ?></span>
                    </span>
                    <?php if ($unread_message_number > 0): ?>
                        <span class="badge badge-danger-lighten ml-auto"><?php echo $unread_message_number; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="tutor-panel">
        <?php include 'message_' . str_replace('message_', '', $message_inner_page_name) . '.php'; ?>
    </section>
</div>