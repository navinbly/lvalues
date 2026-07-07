<?php
$message_thread_details = $this->db->get_where('message_thread', array('message_thread_code' => $current_message_thread_code))->row_array();
$current_user = (int) $this->session->userdata('user_id');
if (empty($message_thread_details) || ((int)$message_thread_details['sender'] !== $current_user && (int)$message_thread_details['receiver'] !== $current_user)) {
    echo '<div class="tutor-empty-state">This conversation is not available.</div>';
    return;
}
$other_user_id = ((int)$message_thread_details['sender'] === $current_user) ? (int)$message_thread_details['receiver'] : (int)$message_thread_details['sender'];
$other_user = $this->user_model->get_all_user($other_user_id)->row_array();
$messages = $this->db->get_where('message', array('message_thread_code' => $current_message_thread_code))->result_array();
?>
<style>
    .tutor-chat-header { padding: 18px 20px; border-bottom: 1px solid #edf1f7; display: flex; align-items: center; gap: 12px; }
    .tutor-chat-header img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
    .tutor-chat-body { padding: 18px 20px; max-height: 480px; overflow: auto; background: #fbfcfe; }
    .tutor-chat-row { display: flex; margin-bottom: 14px; }
    .tutor-chat-row.is-mine { justify-content: flex-end; }
    .tutor-chat-bubble { max-width: 72%; padding: 12px 14px; border-radius: 8px; background: #fff; border: 1px solid #e7ecf3; color: #52677a; }
    .tutor-chat-row.is-mine .tutor-chat-bubble { background: #6c63ff; border-color: #6c63ff; color: #fff; }
    .tutor-chat-meta { display: block; margin-top: 6px; font-size: 11px; opacity: .72; }
    .tutor-chat-compose { padding: 16px 20px; border-top: 1px solid #edf1f7; background: #fff; }
</style>

<div class="tutor-chat-header">
    <img src="<?php echo $this->user_model->get_user_image_url($other_user['id']); ?>" alt="">
    <div>
        <h5 class="mb-1"><?php echo html_escape($other_user['first_name'] . ' ' . $other_user['last_name']); ?></h5>
        <p class="text-muted mb-0"><?php echo html_escape($other_user['email']); ?></p>
    </div>
</div>

<div class="tutor-chat-body">
    <?php foreach ($messages as $message): ?>
        <?php $is_mine = (int) $message['sender'] === $current_user; ?>
        <div class="tutor-chat-row <?php echo $is_mine ? 'is-mine' : ''; ?>">
            <div class="tutor-chat-bubble">
                <?php echo nl2br(html_escape($message['message'])); ?>
                <span class="tutor-chat-meta"><?php echo date('d M Y, h:i A', (int) $message['timestamp']); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="tutor-chat-compose">
    <form method="post" action="<?php echo site_url('user/message/send_reply/' . $current_message_thread_code); ?>" class="needs-validation" novalidate>
        <div class="input-group">
            <input type="text" name="message" class="form-control" placeholder="<?php echo get_phrase('type_your_message'); ?>" required>
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary"><?php echo get_phrase('sent_message'); ?></button>
            </div>
        </div>
    </form>
</div>