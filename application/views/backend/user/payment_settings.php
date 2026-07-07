<?php
$user_data = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
$payment_keys = json_decode($user_data['payment_keys'] ?? '', true);
if (!is_array($payment_keys)) {
    $payment_keys = [];
}
$payout_details = $payment_keys['payout_details'] ?? [];
?>
<div class="row ">
  <div class="col-xl-12">
    <div class="card">
      <div class="card-body">
        <h4 class="page-title"> <i class="mdi mdi-bank title_icon"></i> <?php echo get_phrase('payout_settings'); ?></h4>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-8" style="padding: 0;">
    <div class="col-md-12">
      <div class="card">
        <div class="card-body">
          <h4 class="header-title"><p><?php echo get_phrase('tutor_payout_details'); ?></p></h4>
          <form action="<?php echo site_url('user/payout_settings'); ?>" method="post">
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="account_holder_name"><?php echo get_phrase('account_holder_name'); ?></label>
              <div class="col-md-9">
                <input type="text" id="account_holder_name" name="account_holder_name" value="<?php echo html_escape($payout_details['account_holder_name'] ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="bank_name"><?php echo get_phrase('bank_name'); ?></label>
              <div class="col-md-9">
                <input type="text" id="bank_name" name="bank_name" value="<?php echo html_escape($payout_details['bank_name'] ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="account_number"><?php echo get_phrase('account_number'); ?></label>
              <div class="col-md-9">
                <input type="text" id="account_number" name="account_number" value="<?php echo html_escape($payout_details['account_number'] ?? ''); ?>" class="form-control" inputmode="numeric" autocomplete="off">
              </div>
            </div>
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="ifsc_code"><?php echo get_phrase('ifsc_code'); ?></label>
              <div class="col-md-9">
                <input type="text" id="ifsc_code" name="ifsc_code" value="<?php echo html_escape($payout_details['ifsc_code'] ?? ''); ?>" class="form-control" maxlength="20">
              </div>
            </div>
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="upi_id"><?php echo get_phrase('upi_id'); ?></label>
              <div class="col-md-9">
                <input type="text" id="upi_id" name="upi_id" value="<?php echo html_escape($payout_details['upi_id'] ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group row mb-3">
              <label class="col-md-3 col-form-label" for="payout_notes"><?php echo get_phrase('notes'); ?></label>
              <div class="col-md-9">
                <textarea id="payout_notes" name="payout_notes" class="form-control" rows="3"><?php echo html_escape($payout_details['payout_notes'] ?? ''); ?></textarea>
              </div>
            </div>
            <div class="form-group w-100">
              <button class="btn btn-primary float-right" type="submit"><?php echo get_phrase('save_changes'); ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="alert alert-info" role="alert">
      <h4 class="alert-heading"><?php echo get_phrase('payout_settings'); ?></h4>
      <p>These details are used by the admin team to pay tutor earnings. Payment gateway credentials are managed only from the admin payment settings.</p>
    </div>
  </div>
</div>