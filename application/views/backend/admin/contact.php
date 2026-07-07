<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo $page_title; ?>
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
            <h4 class="mb-1 header-title">Support triage queue</h4>
            <p class="text-muted mb-0">Treat contact messages as the Phase 1 support inbox until a full ticketing module is built.</p>
          </div>
          <span class="badge badge-warning-lighten mt-2 mt-md-0">SLA workflow needed</span>
        </div>
        <div class="row mt-3">
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>New requests</strong>
              <p class="text-muted mb-0 small">Review latest messages first and identify payment, tutor, and course-quality issues.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Escalations</strong>
              <p class="text-muted mb-0 small">Manually escalate refund, complaint, and safety issues until formal statuses exist.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Student lookup</strong>
              <p class="text-muted mb-0 small">Search students by email before replying so support has account context.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-2">
            <div class="border rounded p-3 h-100">
              <strong>Next build</strong>
              <p class="text-muted mb-0 small">Add ticket status, owner, priority, SLA timer, internal notes, and resolution reason.</p>
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
          <h4 class="mb-3 header-title"><?php echo get_phrase('Contact Users'); ?></h4>
          <table class="table table-striped table-centered w-100" id="server_side_users_data">
            <thead>
              <tr>
                <th>#</th>
                <th><?php echo get_phrase('Name'); ?></th>
                <th><?php echo get_phrase('Contact'); ?></th>
                <th><?php echo get_phrase('Message'); ?></th>
                <th><?php echo get_phrase('Action'); ?></th>
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
        "url": "<?php echo base_url('admin/contact/data-table') ?>",
        "dataType": "json",
        "type": "GET",
        "data":{  '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>' }
      },
      order: [[0, 'desc']],
      "columns": [
        { "data": "key" },
        { "data": "name" },
        { "data": "contact" },
        { "data": "message" },
        { "data": "action" }
      ]   
    });
   });

  function refreshServersideTable(tableId){
    $('#'+tableId).DataTable().ajax.reload();
  }
</script>
