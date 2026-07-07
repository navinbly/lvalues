<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_bank_model extends CI_Model
{
    public function get_summary($owner_id = null)
    {
        $this->db->select(
            "COUNT(*) AS total_questions,
             SUM(status='active') AS active_questions,
             SUM(status='draft') AS draft_questions,
             SUM(status='on_hold') AS on_hold_questions",
            false
        );
        $this->apply_owner_filter('question_bank_questions', $owner_id);
        $row = $this->db->get('question_bank_questions')->row_array();
        return $row ?: array();
    }

    public function get_quality_summary($owner_id = null)
    {
        $this->db->select(
            "SUM(class_id=0 OR subject_id=0) AS unmapped_questions,
             SUM(chapter IS NULL OR chapter='') AS missing_chapter,
             SUM(learning_outcome IS NULL OR learning_outcome='') AS missing_outcome,
             SUM(explanation IS NULL OR explanation='') AS missing_explanation",
            false
        );
        $this->apply_owner_filter('question_bank_questions', $owner_id);
        $row = $this->db->get('question_bank_questions')->row_array();
        return $row ?: array();
    }

    public function get_filter_options($owner_id = null)
    {
        $exam_names = array();
        $sub_categories = array();

        if ($this->db->table_exists('question_bank_exam_masters')) {
            $this->db->select('exam_name AS name')->from('question_bank_exam_masters')->where('status', 'active');
            $this->apply_owner_filter('question_bank_exam_masters', $owner_id);
            $exam_names = $this->db->order_by('exam_name', 'ASC')->get()->result_array();
        }
        if (empty($exam_names)) {
            $this->db->select('exam_type AS name')->from('question_bank_questions')
                ->where('exam_type IS NOT NULL', null, false)
                ->where('exam_type !=', '');
            $this->apply_owner_filter('question_bank_questions', $owner_id);
            $exam_names = $this->db->group_by('exam_type')->order_by('exam_type', 'ASC')->get()->result_array();
        }

        if ($this->db->table_exists('question_bank_section_masters')) {
            $this->db->select('s.section_name AS name, e.exam_name')
                ->from('question_bank_section_masters s')
                ->join('question_bank_exam_masters e', 'e.id=s.exam_master_id', 'left')
                ->where('s.status', 'active');
            $this->apply_owner_filter('s', $owner_id, 'question_bank_section_masters');
            $this->apply_owner_filter('e', $owner_id, 'question_bank_exam_masters');
            $sub_categories = $this->db->order_by('e.exam_name', 'ASC')->order_by('s.section_name', 'ASC')->get()->result_array();
        }
        if (empty($sub_categories)) {
            $this->db->select("COALESCE(NULLIF(question_section,''), topic) AS name, exam_type AS exam_name", false)
                ->from('question_bank_questions')
                ->where("(question_section IS NOT NULL AND question_section != '' OR topic IS NOT NULL AND topic != '')", null, false);
            $this->apply_owner_filter('question_bank_questions', $owner_id);
            $sub_categories = $this->db->group_by(array('exam_type', 'question_section', 'topic'))->order_by('exam_type', 'ASC')->order_by('topic', 'ASC')->get()->result_array();
        }

        return array(
            'exam_names' => $exam_names,
            'sub_categories' => $sub_categories,
            'classes' => $this->db->table_exists('tutor_classes') ? $this->db->select('id,name,category_id')->where('status',1)->order_by('name')->get('tutor_classes')->result_array() : array(),
            'subjects' => $this->db->table_exists('tutor_subject_master') ? $this->db->select('id,name,class_id')->where('status',1)->order_by('name')->get('tutor_subject_master')->result_array() : array(),
        );
    }

    public function save_exam_master($admin_id, $exam_name, $description = '')
    {
        $exam_name = trim((string)$exam_name);
        if ($exam_name === '') return array('ok'=>false, 'message'=>'Exam Name is required.');
        if (!$this->db->table_exists('question_bank_exam_masters')) return array('ok'=>false, 'message'=>'Master table is missing. Please run database_changes.sql.');
        $this->db->where('exam_name', $exam_name);
        $this->apply_owner_filter('question_bank_exam_masters', (int)$admin_id);
        $exists = $this->db->get('question_bank_exam_masters')->row_array();
        if ($exists) return array('ok'=>false, 'message'=>'Exam Name already exists.');
        $now = date('Y-m-d H:i:s');
        $this->db->insert('question_bank_exam_masters', array(
            'exam_name'=>$exam_name,
            'description'=>trim((string)$description),
            'status'=>'active',
            'created_by'=>(int)$admin_id,
            'updated_by'=>(int)$admin_id,
            'created_at'=>$now,
            'updated_at'=>$now,
        ));
        return array('ok'=>$this->db->insert_id()>0, 'message'=>'Exam Name created.');
    }

    public function save_section_master($admin_id, $exam_name, $section_name, $description = '')
    {
        $exam_name = trim((string)$exam_name);
        $section_name = trim((string)$section_name);
        if ($exam_name === '' || $section_name === '') return array('ok'=>false, 'message'=>'Exam Name and Sub Category / Section are required.');
        if (!$this->db->table_exists('question_bank_exam_masters') || !$this->db->table_exists('question_bank_section_masters')) return array('ok'=>false, 'message'=>'Master tables are missing. Please run database_changes.sql.');
        $this->db->where(array('exam_name'=>$exam_name,'status'=>'active'));
        $this->apply_owner_filter('question_bank_exam_masters', (int)$admin_id);
        $exam = $this->db->get('question_bank_exam_masters')->row_array();
        if (!$exam) return array('ok'=>false, 'message'=>'Please create/select a valid Exam Name first.');
        $this->db->where(array('exam_master_id'=>(int)$exam['id'], 'section_name'=>$section_name));
        $this->apply_owner_filter('question_bank_section_masters', (int)$admin_id);
        $exists = $this->db->get('question_bank_section_masters')->row_array();
        if ($exists) return array('ok'=>false, 'message'=>'Sub Category / Section already exists for this exam.');
        $now = date('Y-m-d H:i:s');
        $this->db->insert('question_bank_section_masters', array(
            'exam_master_id'=>(int)$exam['id'],
            'section_name'=>$section_name,
            'description'=>trim((string)$description),
            'status'=>'active',
            'created_by'=>(int)$admin_id,
            'updated_by'=>(int)$admin_id,
            'created_at'=>$now,
            'updated_at'=>$now,
        ));
        return array('ok'=>$this->db->insert_id()>0, 'message'=>'Sub Category / Section created.');
    }

    private function is_valid_master_pair($exam_name, $section_name, $owner_id = null)
    {
        $exam_name = trim((string)$exam_name);
        $section_name = trim((string)$section_name);
        if ($exam_name === '' || $section_name === '') return false;
        if (!$this->db->table_exists('question_bank_exam_masters') || !$this->db->table_exists('question_bank_section_masters')) {
            return true; // backward compatible if SQL not run yet
        }
        $this->db->from('question_bank_section_masters s')
            ->join('question_bank_exam_masters e', 'e.id=s.exam_master_id')
            ->where('e.exam_name', $exam_name)
            ->where('e.status', 'active')
            ->where('s.section_name', $section_name)
            ->where('s.status', 'active');
        $this->apply_owner_filter('s', $owner_id, 'question_bank_section_masters');
        $this->apply_owner_filter('e', $owner_id, 'question_bank_exam_masters');
        return $this->db->count_all_results() > 0;
    }

    public function get_recent_imports($limit = 10, $owner_id = null)
    {
        $this->db->order_by('created_at', 'DESC')->limit((int)$limit);
        if ($owner_id !== null && $this->db->field_exists('requested_by', 'question_bank_import_jobs')) {
            $this->db->where('requested_by', (int)$owner_id);
        }
        return $this->db->get('question_bank_import_jobs')->result_array();
    }

    public function search($filters, $limit = 25, $offset = 0, $owner_id = null)
    {
        $this->apply_filters($filters, $owner_id);
        $total = (int)$this->db->count_all_results();

        $this->apply_filters($filters, $owner_id);
        $this->db->select('q.*');
        $this->db->order_by('q.updated_at', 'DESC');
        $this->db->order_by('q.id', 'DESC');
        $rows = $this->db->limit((int)$limit, (int)$offset)->get()->result_array();
        return array('rows' => $rows, 'total' => $total);
    }

    private function apply_filters($filters, $owner_id = null)
    {
        $this->db->from('question_bank_questions q');
        $this->apply_owner_filter('q', $owner_id, 'question_bank_questions');
        if (!empty($filters['search'])) {
            $term = trim((string)$filters['search']);
            $this->db->group_start()
                ->like('q.question_text', $term)
                ->or_like('q.question_code', $term)
                ->or_like('q.topic', $term)
                ->or_like('q.tags', $term)
                ->group_end();
        }
        foreach (array('status','difficulty','exam_type','topic','question_type','chapter','learning_outcome','cognitive_level') as $field) {
            if (!empty($filters[$field])) {
                $this->db->where('q.' . $field, $filters[$field]);
            }
        }
        if (!empty($filters['question_section'])) {
            $this->db->group_start()->where('q.question_section', $filters['question_section'])
                ->or_group_start()->where("(q.question_section IS NULL OR q.question_section='')", null, false)->where('q.topic', $filters['question_section'])->group_end()
                ->group_end();
        }
        foreach(array('class_id','subject_id') as $field)if(!empty($filters[$field]))$this->db->where('q.'.$field,(int)$filters[$field]);
    }


    public function export_rows($filters = array(), $limit = 50000, $owner_id = null)
    {
        $this->apply_filters(is_array($filters) ? $filters : array(), $owner_id);
        $this->db->select('q.*');
        $this->db->order_by('q.exam_type', 'ASC')->order_by('q.question_section', 'ASC')->order_by('q.id', 'ASC');
        $rows = $this->db->limit((int)$limit)->get()->result_array();
        if (empty($rows)) return array();
        $ids = array_map(function($row){ return (int)$row['id']; }, $rows);
        $options = array();
        foreach (array_chunk($ids, 500) as $chunk) {
            $optRows = $this->db->where_in('question_id', $chunk)->order_by('question_id', 'ASC')->order_by('sort_order', 'ASC')->get('question_bank_options')->result_array();
            foreach ($optRows as $opt) {
                $qid = (int)$opt['question_id'];
                if (!isset($options[$qid])) $options[$qid] = array();
                $options[$qid][] = $opt;
            }
        }
        foreach ($rows as &$row) {
            $qid = (int)$row['id'];
            $row['options'] = $options[$qid] ?? array();
        }
        unset($row);
        return $rows;
    }

    public function get_question($question_id)
    {
        $row = $this->db->get_where('question_bank_questions', array('id' => (int)$question_id))->row_array();
        if ($row) {
            $row['options'] = $this->db->order_by('sort_order', 'ASC')
                ->get_where('question_bank_options', array('question_id' => (int)$question_id))->result_array();
        }
        return $row;
    }

    public function save_question($admin_id, $post, $question_id = 0)
    {
        $validation = $this->normalize_question($post, (int)$admin_id);
        if (!$validation['ok']) {
            return $validation;
        }
        $data = $validation['data'];
        $options = $validation['options'];
        $data['updated_by'] = (int)$admin_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['review_status'] = $data['status'];
        if ($data['status'] === 'in_review') $data['submitted_for_review_at'] = date('Y-m-d H:i:s');

        if ((int)$question_id > 0) {
            if (!$this->db->get_where('question_bank_questions', array('id' => (int)$question_id))->row_array()) {
                return array('ok' => false, 'message' => 'Question not found.');
            }
        }

        $this->db->trans_start();
        if ((int)$question_id > 0) {
            $this->db->where('id', (int)$question_id)->update('question_bank_questions', $data);
            $this->db->where('question_id', (int)$question_id)->delete('question_bank_options');
        } else {
            $question_id = $this->insert_normalized_question($admin_id, $data, $options);
            $options = array();
        }
        $this->insert_options($question_id, $options);
        $this->db->trans_complete();
        return $this->db->trans_status()
            ? array('ok' => true, 'message' => 'Question saved successfully.', 'question_id' => (int)$question_id)
            : array('ok' => false, 'message' => 'Question could not be saved.');
    }

    public function change_status($admin_id, $question_id, $status, $remark = '')
    {
        if (!in_array($status, array('draft','in_review','active','rejected','on_hold','update_required','archived'), true)) {
            return array('ok' => false, 'message' => 'Invalid question status.');
        }
        $remark = trim((string)$remark);
        if (in_array($status,array('on_hold','rejected','update_required'),true) && $remark === '') {
            return array('ok' => false, 'message' => 'An admin remark is required for this status.');
        }
        $data = array(
            'status' => $status,
            'review_status' => $status === 'active' ? 'published' : $status,
            'admin_remark' => $remark,
            'updated_by' => (int)$admin_id,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        if ($status === 'active') {
            $data['reviewed_by'] = (int)$admin_id;
            $data['reviewed_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'in_review') {
            $data['submitted_for_review_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', (int)$question_id)->update('question_bank_questions', $data);
        return array('ok' => $this->db->affected_rows() >= 0, 'message' => 'Question status updated.');
    }

    public function duplicate_question($admin_id, $question_id)
    {
        $question = $this->get_question($question_id);
        if (!$question) return ['ok'=>false,'message'=>'Question not found.'];
        $options = $question['options']; unset($question['id'],$question['options'],$question['legacy_exam_question_id']);
        $question['question_code']=$this->next_code(); $question['status']='draft'; $question['review_status']='draft';
        $question['admin_remark']=''; $question['created_by']=(int)$admin_id; $question['updated_by']=(int)$admin_id;
        $question['created_at']=date('Y-m-d H:i:s'); $question['updated_at']=$question['created_at'];
        $question['submitted_for_review_at']=null; $question['reviewed_by']=null; $question['reviewed_at']=null;
        $this->db->trans_start();
        $this->db->insert('question_bank_questions',$question);
        $newId=(int)$this->db->insert_id();
        foreach($options as $option){ unset($option['id']); $option['question_id']=$newId; $option['created_at']=date('Y-m-d H:i:s'); $option['updated_at']=$option['created_at']; $this->db->insert('question_bank_options',$option); }
        $this->db->trans_complete();
        return $this->db->trans_status()?['ok'=>true,'message'=>'Question duplicated as draft.']:['ok'=>false,'message'=>'Question duplication failed.'];
    }

    public function delete_question($question_id)
    {
        $question=$this->get_question($question_id);
        if(!$question)return ['ok'=>false,'message'=>'Question not found.'];
        if($this->db->table_exists('content_exam_question_bank_map') && $this->db->where('question_bank_id',(int)$question_id)->count_all_results('content_exam_question_bank_map')>0){
            $this->db->where('id',(int)$question_id)->update('question_bank_questions',['status'=>'archived','review_status'=>'archived','updated_at'=>date('Y-m-d H:i:s')]);
            return ['ok'=>true,'message'=>'Question is used by an exam and was archived instead of deleted.'];
        }
        $this->db->where('id',(int)$question_id)->delete('question_bank_questions');
        return ['ok'=>true,'message'=>'Question permanently deleted.'];
    }

    public function import_rows($admin_id, $rows, $filename = '')
    {
        $job = array(
            'requested_by' => (int)$admin_id,
            'original_filename' => substr((string)$filename, 0, 255),
            'status' => 'processing',
            'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('question_bank_import_jobs', $job);
        $job_id = (int)$this->db->insert_id();
        $summary = array('total'=>0, 'imported'=>0, 'failed'=>0, 'duplicates'=>0, 'errors'=>array());

        foreach (array_chunk($rows, 100, true) as $chunk) {
            $this->db->trans_start();
            foreach ($chunk as $line => $row) {
                $summary['total']++;
                $normalized = $this->normalize_import_row($row, (int)$admin_id);
                if (!$normalized['ok']) {
                    $summary['failed']++;
                    if (count($summary['errors']) < 50) {
                        $summary['errors'][] = 'Row ' . $line . ': ' . $normalized['message'];
                    }
                    continue;
                }
                $this->db->where('question_text', $normalized['data']['question_text'])
                    ->where('exam_type', (string)$normalized['data']['exam_type'])
                    ->where('topic', (string)$normalized['data']['topic']);
                $this->apply_owner_filter('question_bank_questions', (int)$admin_id);
                $duplicate = $this->db->count_all_results('question_bank_questions');
                if ($duplicate > 0) {
                    $summary['duplicates']++;
                    continue;
                }
                $question_id = $this->insert_normalized_question(
                    $admin_id,
                    $normalized['normalized']['data'],
                    $normalized['normalized']['options']
                );
                if ($question_id > 0) {
                    $summary['imported']++;
                } else {
                    $summary['failed']++;
                    if (count($summary['errors']) < 50) {
                        $summary['errors'][] = 'Row ' . $line . ': Import failed';
                    }
                }
            }
            $this->db->trans_complete();
            if (!$this->db->trans_status()) {
                $summary['errors'][] = 'A 100-row import chunk failed and was rolled back.';
            }
        }

        $status = $summary['failed'] > 0 ? 'completed_with_errors' : 'completed';
        $this->db->where('id', $job_id)->update('question_bank_import_jobs', array(
            'status' => $status,
            'total_rows' => $summary['total'],
            'imported_rows' => $summary['imported'],
            'failed_rows' => $summary['failed'],
            'duplicate_rows' => $summary['duplicates'],
            'error_summary' => json_encode($summary['errors']),
            'completed_at' => date('Y-m-d H:i:s'),
        ));
        $summary['ok'] = true;
        $summary['job_id'] = $job_id;
        return $summary;
    }

    private function normalize_import_row($row, $owner_id = null)
    {
        $letters = array_filter(array_map('trim', explode(',', strtoupper((string)($row['correct_answers'] ?? '')))));
        $options = array();
        foreach (array('a','b','c','d','e') as $letter) {
            $options[] = trim((string)($row['option_' . $letter] ?? ''));
        }
        $correct = array();
        foreach ($letters as $letter) {
            if (!in_array($letter, array('A','B','C','D','E'), true)) {
                return array('ok'=>false, 'message'=>'Correct answers must use A-E.');
            }
            $correct[] = (string)(ord($letter) - 65);
        }
        $post = array(
            'question_text' => $row['question_text'] ?? '',
            'question_type' => $row['question_type'] ?? 'single',
            'category_id' => $row['category_id'] ?? 0,
            'class_id' => $row['class_id'] ?? 0,
            'subject_id' => $row['subject_id'] ?? 0,
            'question_section' => $row['sub_category'] ?? ($row['question_section'] ?? ''),
            'topic' => $row['topic'] ?? '',
            'chapter' => $row['chapter'] ?? '',
            'learning_outcome' => $row['learning_outcome'] ?? '',
            'cognitive_level' => $row['cognitive_level'] ?? 'understand',
            'difficulty' => ucfirst(strtolower((string)($row['difficulty'] ?? 'Beginner'))),
            'exam_type' => $row['exam_name'] ?? ($row['exam_type'] ?? ''),
            'tags' => $row['tags'] ?? '',
            'marks' => $row['marks'] ?? 1,
            'negative_marks' => $row['negative_marks'] ?? 0,
            'explanation' => $row['explanation'] ?? '',
            'status' => $row['status'] ?? 'draft',
            'options' => $options,
            'correct_options' => $correct,
        );
        $result = $this->normalize_question($post, $owner_id);
        if (!$result['ok']) {
            return $result;
        }
        return array('ok'=>true, 'post'=>$post, 'data'=>$result['data'], 'normalized'=>$result);
    }

    private function normalize_question($post, $owner_id = null)
    {
        $text = trim((string)($post['question_text'] ?? ''));
        if ($text === '') return array('ok'=>false, 'message'=>'Question text is required.');
        $type = strtolower(trim((string)($post['question_type'] ?? 'single')));
        if ($type === 'single_choice') $type = 'single';
        if ($type === 'multiple_choice') $type = 'multiple';
        if (!in_array($type, array('single','multiple'), true)) return array('ok'=>false, 'message'=>'Invalid question type.');
        $marks = (float)($post['marks'] ?? 0);
        $negative = (float)($post['negative_marks'] ?? 0);
        if ($marks <= 0 || $negative < 0 || $negative > $marks) return array('ok'=>false, 'message'=>'Marks or negative marks are invalid.');
        $difficulty = ucfirst(strtolower((string)($post['difficulty'] ?? 'Beginner')));
        if (!in_array($difficulty, array('Beginner','Intermediate','Advanced'), true)) return array('ok'=>false, 'message'=>'Invalid difficulty.');
        $exam_name = trim((string)($post['exam_type'] ?? ''));
        $section_name = trim((string)($post['question_section'] ?? ($post['topic'] ?? '')));
        if ($exam_name === '') return array('ok'=>false, 'message'=>'Please select Exam Name.');
        if ($section_name === '') return array('ok'=>false, 'message'=>'Please select Sub Category / Section.');
        if (!$this->is_valid_master_pair($exam_name, $section_name, $owner_id)) return array('ok'=>false, 'message'=>'Invalid Exam Name and Sub Category / Section combination. Please create it in your own question-bank master first.');
        $status = strtolower((string)($post['status'] ?? 'draft'));
        if (!in_array($status, array('draft','in_review','rejected','on_hold','update_required','archived'), true)) $status = 'draft';
        $raw_options = is_array($post['options'] ?? null) ? $post['options'] : array();
        $correct = is_array($post['correct_options'] ?? null) ? array_map('strval', $post['correct_options']) : array((string)($post['correct_options'] ?? ''));
        $options = array();
        foreach ($raw_options as $index => $option) {
            $option = trim((string)$option);
            if ($option === '') continue;
            $options[] = array('text'=>$option, 'is_correct'=>in_array((string)$index, $correct, true) ? 1 : 0);
        }
        if (count($options) < 2) return array('ok'=>false, 'message'=>'At least two options are required.');
        $correct_count = 0;
        foreach ($options as $option) $correct_count += $option['is_correct'];
        if ($correct_count === 0) return array('ok'=>false, 'message'=>'Select at least one correct answer.');
        if ($type === 'single' && $correct_count !== 1) return array('ok'=>false, 'message'=>'Single choice questions require exactly one correct answer.');
        $option_texts = array_map(function($option){ return strtolower(trim($option['text'])); }, $options);
        if (count($option_texts) !== count(array_unique($option_texts))) return array('ok'=>false, 'message'=>'Answer options must be unique.');
        $class_id = max(0,(int)($post['class_id']??0));
        $subject_id = max(0,(int)($post['subject_id']??0));
        if ($subject_id > 0 && $this->db->table_exists('tutor_subject_master')) {
            $subject = $this->db->get_where('tutor_subject_master', array('id'=>$subject_id), 1)->row_array();
            if (!$subject || ($class_id > 0 && (int)$subject['class_id'] !== $class_id)) {
                return array('ok'=>false, 'message'=>'Selected subject does not belong to the selected class.');
            }
        }
        if ($status === 'in_review') {
            if ($class_id <= 0 || $subject_id <= 0) return array('ok'=>false, 'message'=>'Class and subject are required before review.');
            if (trim((string)($post['chapter']??'')) === '') return array('ok'=>false, 'message'=>'Chapter is required before review.');
            if (trim((string)($post['learning_outcome']??'')) === '') return array('ok'=>false, 'message'=>'Learning outcome is required before review.');
            if (trim(strip_tags((string)($post['explanation']??''))) === '') return array('ok'=>false, 'message'=>'Answer explanation is required before review.');
        }
        $cognitive=strtolower(trim((string)($post['cognitive_level']??'understand')));
        if(!in_array($cognitive,array('remember','understand','apply','analyze','evaluate','create'),true))$cognitive='understand';
        return array(
            'ok'=>true,
            'data'=>array(
                'question_text'=>$text,
                'question_type'=>$type,
                'category_id'=>max(0,(int)($post['category_id']??0)),
                'class_id'=>$class_id,
                'subject_id'=>$subject_id,
                'question_section'=>$section_name,
                'topic'=>substr(trim((string)($post['topic']??'')),0,255),
                'chapter'=>substr(trim((string)($post['chapter']??'')),0,191),
                'learning_outcome'=>substr(trim((string)($post['learning_outcome']??'')),0,500),
                'cognitive_level'=>$cognitive,
                'difficulty'=>$difficulty,
                'exam_type'=>$exam_name,
                'tags'=>trim((string)($post['tags'] ?? '')),
                'marks'=>$marks,
                'negative_marks'=>$negative,
                'explanation'=>(string)($post['explanation'] ?? ''),
                'status'=>$status,
                'admin_remark'=>trim((string)($post['admin_remark'] ?? '')),
            ),
            'options'=>$options,
        );
    }

    private function next_code()
    {
        do {
            $code = 'QB-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        } while ($this->db->where('question_code', $code)->count_all_results('question_bank_questions') > 0);
        return $code;
    }

    private function apply_owner_filter($table_or_alias, $owner_id = null, $real_table = null)
    {
        if ($owner_id === null) return;
        $real_table = $real_table ?: $table_or_alias;
        if ($this->db->field_exists('created_by', $real_table)) {
            $this->db->where($table_or_alias . '.created_by', (int)$owner_id);
        }
    }

    private function insert_normalized_question($admin_id, $data, $options)
    {
        $now = date('Y-m-d H:i:s');
        $data['question_code'] = $this->next_code();
        $data['created_by'] = (int)$admin_id;
        $data['updated_by'] = (int)$admin_id;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['review_status'] = $data['status'];
        if ($data['status'] === 'in_review') $data['submitted_for_review_at'] = $now;
        $this->db->insert('question_bank_questions', $data);
        $question_id = (int)$this->db->insert_id();
        if ($question_id > 0) {
            $this->insert_options($question_id, $options);
        }
        return $question_id;
    }

    private function insert_options($question_id, $options)
    {
        $now = date('Y-m-d H:i:s');
        foreach ($options as $index => $option) {
            $this->db->insert('question_bank_options', array(
                'question_id' => (int)$question_id,
                'option_text' => $option['text'],
                'is_correct' => $option['is_correct'],
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }
    }
}
