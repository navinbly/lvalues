<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo $page_title; ?>
                    <a href="<?php echo site_url('admin/user_form/add_user_form'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle"><i class="mdi mdi-plus"></i><?php echo get_phrase('add_student'); ?></a>
                </h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
          <div>
            <h4 class="mb-1 header-title">Student success controls</h4>
            <p class="text-muted mb-0">Use search and actions to verify students today. Phase 1 adds the operational checklist for progress, engagement, and support follow-up.</p>
          </div>
          <span class="badge badge-info-lighten mt-2 mt-md-0">Immediate win</span>
        </div>
        <div class="table-workflow-toolbar mt-3">
          <div>
            <strong class="d-block">Saved views and bulk actions</strong>
            <span class="text-muted">Recommended views: unverified, inactive, recently added, support-linked, export-ready.</span>
          </div>
          <div class="btn-group mt-2 mt-md-0" role="group" aria-label="Student table workflow actions">
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>Save view</button>
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>Bulk action</button>
            <button type="button" class="btn btn-outline-primary btn-sm" disabled>Async export</button>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Verification</strong>
              <p class="text-muted mb-0 small">Prioritize unverified accounts and invalid emails before onboarding campaigns.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Enrollment health</strong>
              <p class="text-muted mb-0 small">Check enrolled course count to identify inactive or manually added students.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Support context</strong>
              <p class="text-muted mb-0 small">Use email/phone search before responding to contact or refund requests.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Bulk actions next</strong>
              <p class="text-muted mb-0 small">Bulk verify, disable, email, export, and tag students should run with audit logging.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-12">
      <div class="card">
        <div class="card-body" data-collapsed="0">
          <h4 class="mb-3 header-title"><?php echo get_phrase('student'); ?></h4>
          <table class="table table-striped table-centered w-100" id="server_side_users_data">
            <thead>
              <tr>
                <th>#</th>
                <th><?php echo get_phrase('photo'); ?></th>
                <th><?php echo get_phrase('name'); ?></th>
                <th><?php echo get_phrase('email'); ?></th>
                <th><?php echo get_phrase('Phone'); ?></th>
                <th><?php echo get_phrase('enrolled_courses'); ?></th>
                <th><?php echo get_phrase('actions'); ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
      </div>
    </div>
  </div><!-- end col-->
</div>

<script>
  $(document).ready(function () {
     var table = $('#server_side_users_data').DataTable({
      responsive: true,
      "processing": true,
      "serverSide": true,
      "ajax":{
        "url": "<?php echo base_url('admin/server_side_users_data') ?>",
        "dataType": "json",
        "type": "POST",
        "data":{  '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>' }
      },
      "columns": [
        { "data": "key" },
        { "data": "photo" },
        { "data": "name" },
        { "data": "email" },
        { "data": "phone" },
        { "data": "enrolled_courses" },
        { "data": "action" }
      ]   
    });
   });

  function refreshServersideTable(tableId){
    $('#'+tableId).DataTable().ajax.reload();
  }
</script>
