<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ops extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) show_404();
        $this->config->load('operations');
    }

    public function backup()
    {
        $directory = rtrim((string)$this->config->item('operations_backup_directory'), '/\\');
        if ($directory === '' || (!is_dir($directory) && !mkdir($directory, 0700, true))) return $this->fail('Backup directory could not be created: ' . ($directory ?: '[empty]'));
        $file = $directory . DIRECTORY_SEPARATOR . 'lvalues-' . ENVIRONMENT . '-' . date('Ymd-His') . '.sql';
        $run_id = $this->start_run('backup', $file);
        $credentials = $this->credential_file();
        $binary = (string)$this->config->item('operations_mysqldump_binary');
        $command = escapeshellarg($binary) . ' --defaults-extra-file=' . escapeshellarg($credentials) . ' --single-transaction --routines --triggers --events --hex-blob --result-file=' . escapeshellarg($file) . ' ' . escapeshellarg($this->db->database);
        exec($command, $output, $code);
        @unlink($credentials);
        if ($code !== 0 || !is_file($file) || filesize($file) === 0) return $this->finish_run($run_id, false, array('exit_code' => $code), 'Database backup failed.');
        $manifest = array('environment' => ENVIRONMENT, 'database' => $this->db->database, 'created_at' => date(DATE_ATOM), 'file' => basename($file), 'size_bytes' => filesize($file), 'sha256' => hash_file('sha256', $file));
        file_put_contents($file . '.manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $this->prune($directory);
        return $this->finish_run($run_id, true, $manifest, 'Backup created: ' . $file);
    }

    public function restore_drill($file = '')
    {
        $directory = rtrim((string)$this->config->item('operations_backup_directory'), '/\\');
        if ($file === '') {
            $files = glob($directory . DIRECTORY_SEPARATOR . '*.sql');
            rsort($files);
            $file = $files[0] ?? '';
        } elseif (basename($file) === $file) {
            $file = $directory . DIRECTORY_SEPARATOR . $file;
        }
        if (!is_file($file)) return $this->fail('Backup file was not found.');
        $run_id = $this->start_run('restore_drill', $file);
        $drill_db = preg_replace('/[^a-zA-Z0-9_]/', '_', $this->db->database) . '_restore_drill_' . date('YmdHis');
        $credentials = $this->credential_file();
        $binary = (string)$this->config->item('operations_mysql_binary');
        $create = escapeshellarg($binary) . ' --defaults-extra-file=' . escapeshellarg($credentials) . ' -e ' . escapeshellarg('CREATE DATABASE `' . $drill_db . '` CHARACTER SET utf8mb4');
        exec($create, $out, $create_code);
        if ($create_code !== 0) {
            @unlink($credentials);
            return $this->finish_run($run_id, false, array('stage' => 'create_database'), 'Restore drill could not create an isolated database.');
        }
        $restore_sql = 'SOURCE ' . str_replace('\\', '/', realpath($file));
        $restore = escapeshellarg($binary) . ' --defaults-extra-file=' . escapeshellarg($credentials) . ' ' . escapeshellarg($drill_db) . ' -e ' . escapeshellarg($restore_sql);
        exec($restore, $out, $restore_code);
        $count_command = escapeshellarg($binary) . ' --defaults-extra-file=' . escapeshellarg($credentials) . ' -N -B ' . escapeshellarg($drill_db) . ' -e ' . escapeshellarg('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()');
        exec($count_command, $count_output, $count_code);
        $drop = escapeshellarg($binary) . ' --defaults-extra-file=' . escapeshellarg($credentials) . ' -e ' . escapeshellarg('DROP DATABASE IF EXISTS `' . $drill_db . '`');
        exec($drop);
        @unlink($credentials);
        $table_count = (int)($count_output[0] ?? 0);
        $passed = $restore_code === 0 && $count_code === 0 && $table_count > 0;
        return $this->finish_run($run_id, $passed, array('file' => $file, 'table_count' => $table_count, 'checksum' => hash_file('sha256', $file)), $passed ? 'Restore drill passed.' : 'Restore drill failed.');
    }

    public function monitor()
    {
        $run_id=$this->start_job('operations_monitor');
        $this->load->library('operations_monitor');
        $readiness = $this->operations_monitor->readiness();
        if (!$readiness['ok']) $this->operations_monitor->alert('application.readiness', 'critical', 'Application readiness check failed.', $readiness);
        $result=array('readiness' => $readiness, 'providers' => $this->operations_monitor->check_providers());
        $failures=empty($readiness['ok'])?1:count(array_filter($result['providers'],function($provider){return ($provider['status']??'')==='down';}));
        $processed=count($result['providers'])+1;
        $this->finish_job($run_id,$failures?'failed':'passed',$processed,max(0,$processed-$failures),$failures,$result);
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public function verify_audit()
    {
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $result = $this->immutable_audit->verify_chain();
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
        return !empty($result['ok']);
    }

    public function audit_self_test()
    {
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $event_id = $this->immutable_audit->record('system', 'audit_self_test', 'operations', 'audit_chain', array(), array('result' => 'written'), array('actor_role' => 'system'));
        $result = $this->immutable_audit->verify_chain();
        $result['event_id'] = $event_id;
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
        return $event_id > 0 && !empty($result['ok']);
    }

    public function idempotency_self_test()
    {
        $this->load->model('Idempotency_model', 'idempotency');
        $payload = array('self_test' => true, 'nonce' => date('YmdHis') . '-' . bin2hex(random_bytes(4)));
        $key = $this->idempotency->key('system.self_test', $payload, 'self-test-' . hash('sha256', json_encode($payload)));
        $this->db->trans_begin();
        $first = $this->idempotency->claim('system.self_test', $key, 0, $payload);
        if (!empty($first['proceed'])) $this->idempotency->complete((int)$first['id'], array('status' => true, 'result' => 'original'), 'system', 'self_test');
        $this->db->trans_commit();

        $this->db->trans_begin();
        $duplicate = $this->idempotency->claim('system.self_test', $key, 0, $payload);
        $this->db->trans_rollback();
        $ok = !empty($first['proceed']) && empty($duplicate['proceed']) && !empty($duplicate['response']['status']);
        echo json_encode(array('ok' => $ok, 'first' => $first, 'duplicate' => $duplicate), JSON_PRETTY_PRINT) . PHP_EOL;
        return $ok;
    }

    public function teacher_reminders()
    {
        $run_id=$this->start_job('teacher_reminders');
        $this->load->model('Teacher_workflow_model', 'teacher_workflow');
        $result=$this->teacher_workflow->process_due_reminders(200);
        $this->finish_job($run_id,!empty($result['failed'])?'partial':'passed',(int)$result['processed'],(int)$result['sent'],(int)$result['failed'],$result);
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    public function scheduled_courses()
    {
        $run_id=$this->start_job('scheduled_courses');
        $this->load->model('Course_workflow_model','course_workflow');
        $result=$this->course_workflow->publish_due(200);
        $failed=max(0,(int)$result['processed']-(int)$result['published']);
        $this->finish_job($run_id,$failed?'partial':'passed',(int)$result['processed'],(int)$result['published'],$failed,$result);
        echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;
    }

    public function communication_retries()
    {
        $run_id=$this->start_job('communication_retries');
        $this->load->library('Communication_service');
        $result=$this->communication_service->process_due_retries(200);
        $this->finish_job($run_id,!empty($result['failed'])?'partial':'passed',(int)$result['processed'],(int)$result['sent'],(int)$result['failed'],$result);
        echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;
    }

    public function teacher_quality_snapshots()
    {
        $run_id=$this->start_job('teacher_quality_snapshots');
        $this->load->model('Analytics_quality_model','analytics_quality');
        $teachers=$this->db->select('id')->where('is_instructor',1)->where('status',1)->get('users')->result_array();
        $saved=0;$failed=0;
        foreach($teachers as $teacher){
            try{$this->analytics_quality->teacher_quality((int)$teacher['id'],true);$saved++;}catch(Throwable $e){$failed++;log_message('error','Teacher quality snapshot failed: '.$e->getMessage());}
        }
        $result=array('processed'=>count($teachers),'saved'=>$saved,'failed'=>$failed);
        $this->finish_job($run_id,$failed?'partial':'passed',count($teachers),$saved,$failed,$result);
        echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;
    }

    public function analytics_quality_self_test()
    {
        $this->load->model('Analytics_quality_model','analytics_quality');
        $batch=$this->db->select('b.*,u.id teacher_id')->from('tutor_batches b')->join('users u','u.id=b.tutor_user_id')->where('u.is_instructor',1)->order_by('b.id')->get()->row_array();
        $student=$this->db->where('is_instructor',0)->where('status',1)->order_by('id')->get('users',1)->row_array();
        if(!$batch||!$student){echo json_encode(array('ok'=>false,'message'=>'A teacher batch and active student are required.')).PHP_EOL;return false;}
        $teacher_id=(int)$batch['teacher_id'];$batch_id=(int)$batch['id'];$student_id=(int)$student['id'];
        $this->db->trans_begin();
        $membership=$this->db->get_where('tutor_batch_students',array('batch_id'=>$batch_id,'student_user_id'=>$student_id),1)->row_array();
        if(!$membership)$this->db->insert('tutor_batch_students',array('batch_id'=>$batch_id,'student_user_id'=>$student_id,'membership_status'=>'active','joined_at'=>date('Y-m-d H:i:s'),'created_at'=>date('Y-m-d H:i:s')));
        $dashboard=$this->analytics_quality->teacher_dashboard($teacher_id,$batch_id);
        $intervention=$this->analytics_quality->save_intervention($teacher_id,array('batch_id'=>$batch_id,'student_user_id'=>$student_id,'intervention_type'=>'contact','reason_code'=>'self_test','reason_text'=>'Analytics quality self-test','action_note'=>'No external communication sent.'));
        $report=$this->analytics_quality->student_report($teacher_id,$student_id,$batch_id);
        $quality=$this->analytics_quality->teacher_quality($teacher_id,false);
        $admin_students=$this->analytics_quality->admin_student_success();
        $content=$this->analytics_quality->content_quality();
        $operations=$this->analytics_quality->operations_dashboard();
        $this->db->trans_rollback();
        $ok=!empty($dashboard['students'])&&!empty($intervention['status'])&&!empty($report['student'])&&!empty($admin_students)&&isset($quality['score'],$content['moderation'],$operations['delivery']);
        echo json_encode(array('ok'=>$ok,'teacher_id'=>$teacher_id,'batch_id'=>$batch_id,'student_id'=>$student_id,'student_rows'=>count($dashboard['students']),'admin_student_rows'=>count($admin_students),'comparison_rows'=>count($dashboard['batch_comparisons']),'risk_reasons'=>count($dashboard['students'][0]['risk']??array()),'intervention'=>$intervention,'quality_score'=>$quality['score'],'content_courses'=>count($content['courses']),'job_runs'=>count($operations['job_runs'])),JSON_PRETTY_PRINT).PHP_EOL;
        return $ok;
    }

    public function course_assessment_self_test()
    {
        $this->load->model('Course_workflow_model','course_workflow');
        $this->load->model('Assessment_improvement_model','assessment');
        $this->load->model('Communication_model','communication_model');
        $course=$this->db->order_by('id')->get('course',1)->row_array();
        $tutor=$this->db->where('is_instructor',1)->order_by('id')->get('users',1)->row_array();
        $this->db->trans_begin();
        $snapshot=$course?$this->course_workflow->snapshot((int)$course['id'],(int)($tutor['id']??0),'system','Course improvement self-test'):array('status'=>false,'message'=>'No course found.');
        $accessibility=$course?$this->course_workflow->accessibility_check((int)$course['id'],(int)($tutor['id']??0)):array('status'=>false,'message'=>'No course found.');
        $tutor_dashboard=$this->assessment->dashboard((int)($tutor['id']??0),'tutor');
        $admin_dashboard=$this->assessment->dashboard(0,'admin');
        $preference=$this->communication_model->delivery_decision((int)($tutor['id']??0),'in_app','transactional');
        $this->db->trans_rollback();
        $ok=!empty($snapshot['status'])&&!empty($accessibility['status'])&&isset($tutor_dashboard['rubrics'],$tutor_dashboard['mastery'],$tutor_dashboard['submissions'])&&isset($admin_dashboard['pending_attempts'])&&array_key_exists('allowed',$preference);
        echo json_encode(array('ok'=>$ok,'course_id'=>(int)($course['id']??0),'tutor_id'=>(int)($tutor['id']??0),'snapshot'=>$snapshot,'accessibility'=>$accessibility,'tutor_dashboard'=>array_map('count',array_intersect_key($tutor_dashboard,array_flip(array('rubrics','feedback','mastery','submissions')))),'admin_pending_attempts'=>count($admin_dashboard['pending_attempts']),'communication_decision'=>$preference),JSON_PRETTY_PRINT).PHP_EOL;
        return $ok;
    }

    public function teacher_workflow_self_test()
    {
        $teacher = $this->db->select('u.*')->from('users u')->join('tutor_batches b','b.tutor_user_id=u.id','inner')->where('u.is_instructor',1)->where('u.status',1)->order_by('u.id')->limit(1)->get()->row_array();
        if(!$teacher)$teacher=$this->db->where('is_instructor',1)->where('status',1)->order_by('id')->get('users',1)->row_array();
        if(!$teacher){echo json_encode(array('ok'=>false,'message'=>'No active teacher account found.')).PHP_EOL;return false;}
        $teacher_id=(int)$teacher['id'];
        $this->load->model('Teacher_workflow_model','teacher_workflow');
        $this->load->library('live_learning_service');
        $this->db->trans_begin();
        $autosave=$this->teacher_workflow->autosave($teacher_id,'self_test','teacher_workflow','default',array('value'=>'draft '.date(DATE_ATOM)));
        $versions=$this->teacher_workflow->get_versions($teacher_id,'self_test','teacher_workflow');
        $agenda=$this->teacher_workflow->get_daily_workspace($teacher_id);
        $calendar=$this->teacher_workflow->get_calendar($teacher_id,date('Y-m-d',strtotime('-1 day')),date('Y-m-d',strtotime('+30 days')));
        $providers=$this->live_learning_service->providers($teacher_id);
        $batch=$this->db->get_where('tutor_batches',array('tutor_user_id'=>$teacher_id),1)->row_array();
        $health=$batch?$this->teacher_workflow->calculate_batch_health((int)$batch['id'],$teacher_id):array('score'=>0,'label'=>'No batch');
        $template=$batch?$this->teacher_workflow->create_template_from_batch((int)$batch['id'],$teacher_id,'Self-test template'):array('status'=>false);
        $template_batch=!empty($template['template_id'])?$this->teacher_workflow->create_batch_from_template((int)$template['template_id'],$teacher_id,'Self-test batch',date('Y-m-d',strtotime('+30 days'))):array('status'=>false);
        $template_batch_exists=!empty($template_batch['batch_id'])&&$this->db->where('id',(int)$template_batch['batch_id'])->where('tutor_user_id',$teacher_id)->count_all_results('tutor_batches')===1;
        $conflict=!empty($calendar)?$this->teacher_workflow->check_session_conflict($teacher_id,$calendar[0]['session_date'],$calendar[0]['start_time'],$calendar[0]['end_time']):array('has_conflict'=>false);
        $this->db->trans_rollback();
        $ok=!empty($autosave['status'])&&!empty($versions)&&count($providers)>=4&&!empty($template['status'])&&!empty($template_batch['status'])&&$template_batch_exists;
        echo json_encode(array('ok'=>$ok,'teacher_id'=>$teacher_id,'autosave'=>$autosave,'version_count'=>count($versions),'agenda_count'=>count($agenda),'calendar_count'=>count($calendar),'provider_count'=>count($providers),'health'=>$health,'template'=>$template,'template_batch'=>$template_batch,'template_batch_exists'=>$template_batch_exists,'conflict_probe'=>$conflict),JSON_PRETTY_PRINT).PHP_EOL;
        return $ok;
    }

    private function credential_file(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'lvalues-db-');
        $content = "[client]\nhost={$this->db->hostname}\nport={$this->db->port}\nuser={$this->db->username}\npassword={$this->db->password}\n";
        file_put_contents($file, $content, LOCK_EX);
        @chmod($file, 0600);
        return $file;
    }

    private function start_job(string $key): int
    {
        if(!$this->db->table_exists('operational_job_runs'))return 0;
        $this->db->insert('operational_job_runs',array('job_key'=>$key));
        return (int)$this->db->insert_id();
    }

    private function finish_job(int $id,string $status,int $processed,int $success,int $failed,array $details): void
    {
        if($id<=0)return;
        $this->db->where('id',$id)->update('operational_job_runs',array('status'=>$status,'processed_count'=>max(0,$processed),'success_count'=>max(0,$success),'failure_count'=>max(0,$failed),'details_json'=>json_encode($details),'completed_at'=>date('Y-m-d H:i:s')));
    }

    private function start_run(string $type, string $file): int
    {
        $this->db->insert('backup_runs', array('run_type' => $type, 'file_path' => $file));
        return (int)$this->db->insert_id();
    }

    private function finish_run(int $id, bool $passed, array $details, string $message)
    {
        $file = $this->db->get_where('backup_runs', array('id' => $id), 1)->row('file_path');
        $this->db->where('id', $id)->update('backup_runs', array(
            'status' => $passed ? 'passed' : 'failed',
            'checksum_sha256' => is_file($file) ? hash_file('sha256', $file) : null,
            'size_bytes' => is_file($file) ? filesize($file) : null,
            'details_json' => json_encode($details),
            'completed_at' => date('Y-m-d H:i:s'),
        ));
        echo $message . PHP_EOL;
        return $passed;
    }

    private function prune(string $directory): void
    {
        $cutoff = time() - ((int)$this->config->item('operations_backup_retention_days') * 86400);
        foreach ((array)glob($directory . DIRECTORY_SEPARATOR . 'lvalues-*.sql*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) @unlink($file);
        }
    }

    private function fail(string $message)
    {
        fwrite(STDERR, $message . PHP_EOL);
        return false;
    }
}
