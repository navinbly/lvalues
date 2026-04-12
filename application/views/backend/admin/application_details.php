<?php
$application_details = $this->user_model->get_applications($param2, 'application')->row_array();
$application_details = is_array($application_details) ? $application_details : array();

$applicant_details = array();
if (isset($application_details['user_id']) && !empty($application_details['user_id'])) {
    $applicant_details = $this->user_model->get_all_user($application_details['user_id'])->row_array();
}
$applicant_details = is_array($applicant_details) ? $applicant_details : array();

$applicant_id = isset($applicant_details['id']) ? $applicant_details['id'] : 0;
$first_name   = isset($applicant_details['first_name']) ? $applicant_details['first_name'] : '';
$last_name    = isset($applicant_details['last_name']) ? $applicant_details['last_name'] : '';
$email        = isset($applicant_details['email']) ? $applicant_details['email'] : 'N/A';

$phone        = isset($application_details['phone']) ? $application_details['phone'] : 'N/A';
$address      = isset($application_details['address']) ? $application_details['address'] : 'N/A';
$message      = isset($application_details['message']) ? $application_details['message'] : 'N/A';
$status       = isset($application_details['status']) ? (int)$application_details['status'] : 0;

$full_name    = trim($first_name . ' ' . $last_name);
$image_url    = $applicant_id > 0 ? $this->user_model->get_user_image_url($applicant_id) : base_url('uploads/user_image/placeholder.png');
?>

<div class="text-center mb-2">
    <img class="mr-2 rounded-circle" src="<?php echo $image_url; ?>" alt="" height="80">
</div>

<div class="table-responsive-sm">
    <table class="table table-bordered table-centered mb-0">
        <tbody>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('applicant'); ?></strong></td>
                <td><?php echo $full_name !== '' ? html_escape($full_name) : 'N/A'; ?></td>
            </tr>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('email'); ?></strong></td>
                <td><?php echo html_escape($email); ?></td>
            </tr>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('phone_number'); ?></strong></td>
                <td><?php echo !empty($phone) ? html_escape($phone) : 'N/A'; ?></td>
            </tr>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('address'); ?></strong></td>
                <td><?php echo !empty($address) ? html_escape($address) : 'N/A'; ?></td>
            </tr>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('message'); ?></strong></td>
                <td><?php echo !empty($message) ? html_escape($message) : 'N/A'; ?></td>
            </tr>
            <tr class="text-center">
                <td><strong><?php echo get_phrase('status'); ?></strong></td>
                <td>
                    <?php if ($status === 1): ?>
                        <span class="badge badge-success"><?php echo get_phrase('approved'); ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger"><?php echo get_phrase('pending'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>