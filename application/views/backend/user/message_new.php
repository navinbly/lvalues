<?php
$current_user = (int) $this->session->userdata('user_id');
$user_list = $this->db->where('id !=', $current_user)->order_by('first_name', 'ASC')->get('users')->result_array();
?>
<div class="p-4">
    <div class="mb-4">
        <h4 class="mb-1"><?php echo get_phrase('write_new_messages'); ?></h4>
        <p class="text-muted mb-0">Start a direct conversation with a student, tutor, or admin user.</p>
    </div>

    <form method="post" action="<?php echo site_url('user/message/send_new'); ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label><?php echo get_phrase('Recipient'); ?></label>
            <select class="form-control select2" data-toggle="select2" name="receiver" required>
                <option value=""><?php echo get_phrase('select_a_user'); ?></option>
                <?php foreach ($user_list as $user): ?>
                    <option value="<?php echo (int) $user['id']; ?>">
                        <?php echo html_escape(trim($user['first_name'] . ' ' . $user['last_name']) . ' - ' . $user['email']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><?php echo get_phrase('message'); ?></label>
            <textarea class="form-control" rows="7" name="message" placeholder="<?php echo get_phrase('type_your_message'); ?>" required></textarea>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo site_url('user/message'); ?>" class="btn btn-outline-secondary"><?php echo get_phrase('cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><?php echo get_phrase('sent_message'); ?></button>
        </div>
    </form>
</div>