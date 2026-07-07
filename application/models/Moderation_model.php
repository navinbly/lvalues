<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Moderation_model extends CI_Model
{
    public function record($entityType, $entityId, $action, $fromStatus, $toStatus, $reason, $actorId, $creatorId = 0, $metadata = array())
    {
        $this->db->insert('content_moderation_events', array(
            'entity_type'=>(string)$entityType,
            'entity_id'=>(int)$entityId,
            'action'=>(string)$action,
            'from_status'=>(string)$fromStatus,
            'to_status'=>(string)$toStatus,
            'reason'=>trim((string)$reason),
            'actor_id'=>(int)$actorId,
            'creator_id'=>(int)$creatorId ?: null,
            'metadata_json'=>empty($metadata) ? null : json_encode($metadata),
            'created_at'=>date('Y-m-d H:i:s'),
        ));
    }

    public function get_history($entityType, $entityId, $limit = 20)
    {
        return $this->db->select('m.*,u.first_name,u.last_name')
            ->from('content_moderation_events m')->join('users u','u.id=m.actor_id','left')
            ->where('m.entity_type',(string)$entityType)->where('m.entity_id',(int)$entityId)
            ->order_by('m.created_at','DESC')->limit((int)$limit)->get()->result_array();
    }

    public function get_review_queue()
    {
        $exams = $this->db->select("e.id,e.title,e.status,e.review_status,e.admin_remark,e.updated_at,e.created_by,
                'exam' entity_type,COUNT(DISTINCT s.id) section_count,COUNT(DISTINCT m.question_bank_id) question_count", false)
            ->from('content_exams e')->join('content_exam_sections s','s.exam_id=e.id','left')
            ->join('content_exam_question_bank_map m','m.exam_id=e.id','left')
            ->where_in('e.review_status',array('in_review','on_hold','update_required','rejected'))
            ->group_by('e.id')->order_by('e.updated_at','DESC')->get()->result_array();
        $questions = $this->db->select("q.id,q.question_text title,q.status,q.review_status,q.admin_remark,q.updated_at,q.created_by,
                'question' entity_type,q.question_code,q.topic,q.difficulty", false)
            ->from('question_bank_questions q')
            ->where_in('q.review_status',array('in_review','on_hold','update_required','rejected'))
            ->order_by('q.updated_at','DESC')->limit(200)->get()->result_array();
        return array('exams'=>$exams,'questions'=>$questions);
    }

    public function get_recent_events($limit = 30)
    {
        return $this->db->select('m.*,u.first_name,u.last_name')
            ->from('content_moderation_events m')->join('users u','u.id=m.actor_id','left')
            ->order_by('m.created_at','DESC')->limit((int)$limit)->get()->result_array();
    }

    public function review_exam($examId, $action, $reason, $adminId)
    {
        $exam = $this->db->get_where('content_exams',array('id'=>(int)$examId))->row_array();
        if (!$exam) return array('ok'=>false,'message'=>'Exam not found.');
        $map = array(
            'approve'=>array('status'=>'published','review_status'=>'published'),
            'reject'=>array('status'=>'draft','review_status'=>'rejected'),
            'hold'=>array('status'=>'draft','review_status'=>'on_hold'),
            'ask_update'=>array('status'=>'draft','review_status'=>'update_required'),
        );
        if (!isset($map[$action])) return array('ok'=>false,'message'=>'Invalid moderation action.');
        if ($action !== 'approve' && trim((string)$reason) === '') return array('ok'=>false,'message'=>'A reason is required for this action.');
        if ($action === 'approve') {
            $sections = $this->db->where('exam_id',(int)$examId)->count_all_results('content_exam_sections');
            $questions = $this->db->where('exam_id',(int)$examId)->count_all_results('content_exam_question_bank_map');
            if ($sections < 1 || $questions < 1) return array('ok'=>false,'message'=>'Exam must contain at least one section and question before approval.');
        }
        $now=date('Y-m-d H:i:s'); $target=$map[$action];
        $data=array(
            'status'=>$target['status'],'review_status'=>$target['review_status'],'admin_remark'=>trim((string)$reason),
            'is_published'=>$action==='approve'?1:0,'reviewed_by'=>(int)$adminId,'reviewed_at'=>$now,
            'updated_by'=>(int)$adminId,'updated_at'=>$now
        );
        if ($action==='approve') $data['published_at']=$now;
        $this->db->trans_start();
        $this->db->where('id',(int)$examId)->update('content_exams',$data);
        if ($action==='approve') {
            $this->load->model('Exam_pattern_model','exam_pattern');
            $this->exam_pattern->materialize_exam((int)$examId,(int)$adminId);
        }
        $this->record('exam',(int)$examId,$action,$exam['review_status'] ?: $exam['status'],$target['review_status'],$reason,$adminId,(int)$exam['created_by']);
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $this->immutable_audit->record('publishing', $action, 'exam', (int)$examId, $exam, array_merge($exam, $data), array(
            'actor_user_id' => (int)$adminId,
            'actor_role' => 'admin',
            'reason' => trim((string)$reason),
        ));
        $this->db->trans_complete();
        return $this->db->trans_status()
            ? array('ok'=>true,'message'=>'Exam review action completed.')
            : array('ok'=>false,'message'=>'Exam review action failed.');
    }

    public function review_question($questionId, $action, $reason, $adminId)
    {
        $question=$this->db->get_where('question_bank_questions',array('id'=>(int)$questionId))->row_array();
        if (!$question) return array('ok'=>false,'message'=>'Question not found.');
        $map=array(
            'approve'=>array('status'=>'active','review_status'=>'published'),
            'reject'=>array('status'=>'rejected','review_status'=>'rejected'),
            'hold'=>array('status'=>'on_hold','review_status'=>'on_hold'),
            'ask_update'=>array('status'=>'update_required','review_status'=>'update_required'),
        );
        if (!isset($map[$action])) return array('ok'=>false,'message'=>'Invalid moderation action.');
        if ($action!=='approve' && trim((string)$reason)==='') return array('ok'=>false,'message'=>'A reason is required for this action.');
        $now=date('Y-m-d H:i:s'); $target=$map[$action];
        $data=array(
            'status'=>$target['status'],'review_status'=>$target['review_status'],'admin_remark'=>trim((string)$reason),
            'reviewed_by'=>(int)$adminId,'reviewed_at'=>$now,'updated_by'=>(int)$adminId,'updated_at'=>$now
        );
        $this->db->trans_start();
        $this->db->where('id',(int)$questionId)->update('question_bank_questions',$data);
        $this->record('question',(int)$questionId,$action,$question['review_status'] ?: $question['status'],$target['review_status'],$reason,$adminId,(int)$question['created_by']);
        $this->load->model('Immutable_audit_model', 'immutable_audit');
        $this->immutable_audit->record('publishing', $action, 'question', (int)$questionId, $question, array_merge($question, $data), array(
            'actor_user_id' => (int)$adminId,
            'actor_role' => 'admin',
            'reason' => trim((string)$reason),
        ));
        $this->db->trans_complete();
        return $this->db->trans_status()
            ? array('ok'=>true,'message'=>'Question review action completed.')
            : array('ok'=>false,'message'=>'Question review action failed.');
    }
}
