<?php
$risk_counts=array('high'=>0,'medium'=>0,'low'=>0);
foreach($students as $student)$risk_counts[$student['risk_level']]++;
?>
<div class="row"><div class="col-12"><div class="card"><div class="card-body d-flex justify-content-between align-items-center flex-wrap">
    <div><h4 class="page-title mb-1"><i class="mdi mdi-chart-line title_icon"></i> Teaching Analytics & Quality</h4><p class="text-muted mb-0">Fast, explainable signals for the next teaching decision.</p></div>
    <form method="get" action="<?php echo site_url('teacher-analytics');?>" class="form-inline"><select name="batch_id" class="form-control form-control-sm mr-2"><option value="0">All my batches</option><?php foreach($batches as $batch):?><option value="<?php echo (int)$batch['id'];?>" <?php echo (int)$selected_batch_id===(int)$batch['id']?'selected':'';?>><?php echo html_escape($batch['title']);?></option><?php endforeach;?></select><button class="btn btn-primary btn-sm">Apply</button></form>
</div></div></div></div>

<div class="row">
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small class="text-muted">High risk</small><h3 class="text-danger"><?php echo $risk_counts['high'];?></h3><span>Act before the next class</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small class="text-muted">Watch</small><h3 class="text-warning"><?php echo $risk_counts['medium'];?></h3><span>Review this week</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small class="text-muted">On track</small><h3 class="text-success"><?php echo $risk_counts['low'];?></h3><span>No current indicators</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="card"><div class="card-body"><small class="text-muted">Teacher quality</small><h3><?php echo number_format((float)$quality['score'],1);?></h3><a href="#quality-explanation">See calculation</a></div></div></div>
</div>

<div class="row"><div class="col-xl-8"><div class="card"><div class="card-body">
    <h5>Students Needing a Decision</h5><p class="text-muted">Indicators use visible thresholds. They do not make decisions or contact students automatically.</p>
    <div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Student</th><th>Batch</th><th>Signals</th><th>Next action</th><th>Reports</th></tr></thead><tbody>
    <?php foreach($students as $student):?><tr>
        <td><strong><?php echo html_escape(trim($student['first_name'].' '.$student['last_name']));?></strong><br><span class="badge badge-<?php echo $student['risk_level']==='high'?'danger':($student['risk_level']==='medium'?'warning':'success');?>"><?php echo ucfirst($student['risk_level']);?></span></td>
        <td><?php echo html_escape($student['batch_title']);?><br><small>A <?php echo $student['metrics']['attendance'];?>% · Work <?php echo $student['metrics']['assignment_completion'];?>% · Score <?php echo $student['metrics']['score'];?>%</small></td>
        <td><?php if(!$student['risk']):?><span class="text-success">No current watch indicators.</span><?php endif;?><?php foreach($student['risk'] as $reason):?><div class="mb-1"><strong><?php echo html_escape($reason['reason']);?></strong><br><small class="text-muted"><?php echo html_escape($reason['action']);?></small></div><?php endforeach;?></td>
        <td>
            <form method="post" action="<?php echo site_url('teacher-analytics/intervention');?>" style="min-width:220px">
                <input type="hidden" name="batch_id" value="<?php echo (int)$student['batch_id'];?>"><input type="hidden" name="student_user_id" value="<?php echo (int)$student['student_user_id'];?>">
                <input type="hidden" name="reason_code" value="<?php echo html_escape($student['risk'][0]['code']??'teacher_review');?>"><input type="hidden" name="reason_text" value="<?php echo html_escape($student['risk'][0]['reason']??'Teacher follow-up');?> ">
                <select class="form-control form-control-sm mb-1" name="intervention_type"><option value="contact">Contact student</option><option value="attendance_plan">Attendance plan</option><option value="assignment_plan">Assignment plan</option><option value="mastery_plan">Topic mastery plan</option><option value="parent_update">Parent update</option><option value="support_referral">Support referral</option></select>
                <input class="form-control form-control-sm mb-1" name="action_note" placeholder="Short action note"><button class="btn btn-outline-primary btn-sm btn-block">Add intervention</button>
            </form>
        </td>
        <td><a class="btn btn-outline-secondary btn-sm mb-1" href="<?php echo site_url('teacher-analytics/progress/'.$student['batch_id'].'/'.$student['student_user_id']);?>">Progress PDF</a><br><a class="btn btn-outline-info btn-sm" href="<?php echo site_url('teacher-analytics/parent/'.$student['batch_id'].'/'.$student['student_user_id']);?>">Parent report</a></td>
    </tr><?php endforeach;?><?php if(!$students):?><tr><td colspan="5" class="text-center text-muted">No students in the selected teaching scope.</td></tr><?php endif;?>
    </tbody></table></div>
</div></div></div>
<div class="col-xl-4"><div class="card"><div class="card-body"><h5>Open Interventions</h5>
    <?php foreach($interventions as $item):?><div class="border-bottom py-2"><strong><?php echo html_escape(trim($item['first_name'].' '.$item['last_name']));?></strong><br><?php echo html_escape($item['reason_text']);?><br><small class="text-muted"><?php echo html_escape(ucwords(str_replace('_',' ',$item['intervention_type'])));?> · <?php echo html_escape($item['batch_title']);?></small><div class="mt-1"><?php if($item['status']!=='completed'):?><form method="post" action="<?php echo site_url('teacher-analytics/intervention/'.$item['id'].'/completed');?>"><button class="btn btn-outline-success btn-xs">Mark complete</button></form><?php endif;?></div></div><?php endforeach;?>
    <?php if(!$interventions):?><p class="text-muted">No open intervention plans.</p><?php endif;?>
</div></div></div></div>

<div class="row"><div class="col-xl-7"><div class="card"><div class="card-body"><h5>My Batch Comparisons</h5><p class="text-muted">Only your own batches are included.</p>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Batch</th><th>Students</th><th>Attendance</th><th>Work</th><th>Scores</th><th>Engagement</th></tr></thead><tbody><?php foreach($batch_comparisons as $row):?><tr><td><?php echo html_escape($row['title']);?></td><td><?php echo (int)$row['students'];?></td><td><?php echo $row['attendance'];?>%</td><td><?php echo $row['assignment_completion'];?>%</td><td><?php echo $row['score'];?>%</td><td><?php echo $row['engagement'];?>%</td></tr><?php endforeach;?></tbody></table></div>
</div></div></div>
<div class="col-xl-5"><div class="card"><div class="card-body"><h5>Lowest Topic Mastery</h5><?php foreach($topic_mastery as $row):?><div class="mb-2"><div class="d-flex justify-content-between"><span><?php echo html_escape($row['dimension_label']);?></span><strong><?php echo $row['score'];?>%</strong></div><div class="progress" style="height:6px"><div class="progress-bar <?php echo (float)$row['score']<50?'bg-danger':'bg-warning';?>" style="width:<?php echo min(100,(float)$row['score']);?>%"></div></div><small><?php echo (int)$row['students'];?> student(s)</small></div><?php endforeach;?><?php if(!$topic_mastery):?><p class="text-muted">No topic mastery evidence yet.</p><?php endif;?></div></div></div></div>

<div class="row"><div class="col-md-6"><div class="card"><div class="card-body"><h5>Attendance Trend</h5><?php foreach($attendance_trend as $point):?><div class="d-flex justify-content-between border-bottom py-2"><span><?php echo html_escape($point['period']);?></span><strong><?php echo (float)$point['value'];?>%</strong></div><?php endforeach;?><?php if(!$attendance_trend):?><p class="text-muted">Attendance trend will appear after attendance is marked.</p><?php endif;?></div></div></div>
<div class="col-md-6"><div class="card"><div class="card-body"><h5>Score Trend</h5><?php foreach($score_trend as $point):?><div class="d-flex justify-content-between border-bottom py-2"><span><?php echo html_escape($point['period']);?></span><strong><?php echo (float)$point['value'];?>%</strong></div><?php endforeach;?><?php if(!$score_trend):?><p class="text-muted">Score trend will appear after tests are submitted.</p><?php endif;?></div></div></div></div>

<div class="row" id="quality-explanation"><div class="col-12"><div class="card"><div class="card-body"><h5>How the Teacher Quality Score Works</h5><p class="text-muted">The score is explanatory, not disciplinary. It highlights improvement actions and should always be reviewed with context.</p>
<div class="row"><?php foreach($quality['components'] as $key=>$value):?><div class="col-md"><div class="border rounded p-3 mb-2"><small><?php echo html_escape(ucwords(str_replace('_',' ',$key)));?> · <?php echo (int)$quality['weights'][$key];?>%</small><h4><?php echo number_format((float)$value,1);?></h4></div></div><?php endforeach;?></div>
<p><strong>Inputs:</strong> <?php echo (int)$quality['inputs']['courses'];?> courses, <?php echo (int)$quality['inputs']['review_items'];?> moderated items, <?php echo (int)$quality['inputs']['feedback_count'];?> feedback responses, <?php echo (int)$quality['inputs']['past_sessions'];?> past classes, and <?php echo (int)$quality['inputs']['batches'];?> batches.</p>
<?php foreach($quality['actions'] as $action):?><div class="alert alert-light border py-2 mb-2"><?php echo html_escape($action);?></div><?php endforeach;?><?php if(!$quality['actions']):?><div class="alert alert-success">Current quality inputs are healthy. Keep the same teaching routines.</div><?php endif;?>
</div></div></div></div>
