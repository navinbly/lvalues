<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam_pattern_model extends CI_Model
{
    public function get_exam($exam_id)
    {
        return $this->db->get_where('content_exams', array('id'=>(int)$exam_id))->row_array();
    }

    public function get_sections($exam_id)
    {
        $sections = $this->db->order_by('sort_order','ASC')
            ->get_where('content_exam_sections', array('exam_id'=>(int)$exam_id))->result_array();
        foreach ($sections as &$section) {
            $section['questions'] = $this->db
                ->select('m.*, q.question_code, q.question_text, q.question_type, q.topic, q.difficulty, q.marks, q.negative_marks')
                ->from('content_exam_question_bank_map m')
                ->join('question_bank_questions q', 'q.id=m.question_bank_id')
                ->where('m.section_id', (int)$section['id'])
                ->order_by('m.sort_order','ASC')->get()->result_array();
        }
        unset($section);
        return $sections;
    }

    public function get_active_bank_questions($limit = 2000, $owner_id = null)
    {
        $this->db->select('q.*')
            ->from('question_bank_questions q')
            ->where('q.status','active');
        $this->apply_owner_filter('q', $owner_id, 'question_bank_questions');
        return $this->db->order_by('q.exam_type','ASC')->order_by('q.topic','ASC')->order_by('q.id','DESC')
            ->limit((int)$limit)->get()->result_array();
    }

    /**
     * Server-side question pool search for the exam pattern builder, so the full
     * question bank is never embedded in the page. Returns the total matching
     * count plus a limited page of rows; any $include_ids (already selected in
     * the pattern) are always returned so their labels can render.
     */
    public function search_bank_questions($owner_id, $exam_type = '', $topic = '', $difficulty = '', $include_ids = array(), $limit = 300)
    {
        $apply_filters = function() use ($owner_id, $exam_type, $topic, $difficulty) {
            $this->db->where('status', 'active');
            $this->apply_owner_filter('question_bank_questions', $owner_id);
            if (trim((string)$exam_type) !== '') $this->db->where('exam_type', trim((string)$exam_type));
            if (trim((string)$topic) !== '') {
                $section = trim((string)$topic);
                $this->db->group_start()->where('question_section', $section)
                    ->or_group_start()->where("(question_section IS NULL OR question_section='')", null, false)->where('topic', $section)->group_end()->group_end();
            }
            if (trim((string)$difficulty) !== '') $this->db->where('difficulty', trim((string)$difficulty));
        };

        $apply_filters();
        $total = (int)$this->db->count_all_results('question_bank_questions');

        $apply_filters();
        $rows = $this->db->select('id, question_code, question_text, question_type, exam_type, question_section, topic, difficulty, marks')
            ->order_by('id', 'DESC')->limit((int)$limit)->get('question_bank_questions')->result_array();

        $include_ids = array_values(array_unique(array_filter(array_map('intval', (array)$include_ids))));
        if (!empty($include_ids)) {
            $have = array_map('intval', array_column($rows, 'id'));
            $missing = array_diff($include_ids, $have);
            if (!empty($missing)) {
                $extra = $this->db->select('id, question_code, question_text, question_type, exam_type, question_section, topic, difficulty, marks')
                    ->where_in('id', array_values($missing))->get('question_bank_questions')->result_array();
                $rows = array_merge($extra, $rows);
            }
        }

        $questions = array_map(function($q) {
            return array(
                'id' => (int)$q['id'],
                'code' => (string)$q['question_code'],
                'text' => (string)$q['question_text'],
                'type' => (string)$q['question_type'],
                'exam' => (string)$q['exam_type'],
                'topic' => trim((string)($q['question_section'] ?? '')) !== '' ? (string)$q['question_section'] : (string)$q['topic'],
                'difficulty' => (string)$q['difficulty'],
                'marks' => (float)$q['marks'],
            );
        }, $rows);

        return array('total' => $total, 'questions' => $questions);
    }

    private function get_matching_question_ids($exam_name, $sub_category, $difficulty, $exclude_ids = array(), $owner_id = null)
    {
        $this->db->select('id')->from('question_bank_questions')->where('status', 'active');
        $this->apply_owner_filter('question_bank_questions', $owner_id);
        if (trim((string)$exam_name) !== '') $this->db->where('exam_type', trim((string)$exam_name));
        if (trim((string)$sub_category) !== '') {
            $section = trim((string)$sub_category);
            $this->db->group_start()->where('question_section', $section)
                ->or_group_start()->where("(question_section IS NULL OR question_section='')", null, false)->where('topic', $section)->group_end()->group_end();
        }
        if (trim((string)$difficulty) !== '') $this->db->where('difficulty', trim((string)$difficulty));
        if (!empty($exclude_ids)) $this->db->where_not_in('id', array_keys($exclude_ids));
        $rows = $this->db->order_by('id', 'DESC')->get()->result_array();
        return array_map('intval', array_column($rows, 'id'));
    }

    private function is_valid_master_pair($exam_name, $section_name, $owner_id = null)
    {
        $exam_name = trim((string)$exam_name);
        $section_name = trim((string)$section_name);
        if ($exam_name === '' || $section_name === '') return false;
        if (!$this->db->table_exists('question_bank_exam_masters') || !$this->db->table_exists('question_bank_section_masters')) {
            return true; // backward compatible before SQL is applied
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

    public function save_pattern($admin_id, $post, $exam_id = 0, $managed_by_admin = 1)
    {
        $title = trim((string)($post['title'] ?? ''));
        $duration = (int)($post['time_limit_minutes'] ?? 0);
        $passing = (float)($post['passing_marks'] ?? 0);
        $sections = is_array($post['sections'] ?? null) ? $post['sections'] : array();
        if ($title === '') return array('ok'=>false,'message'=>'Exam title is required.');
        if ($duration <= 0) return array('ok'=>false,'message'=>'Duration must be greater than zero.');
        if (empty($sections)) return array('ok'=>false,'message'=>'Add at least one exam section.');
        $starts_at = trim((string)($post['starts_at'] ?? ''));
        $ends_at = trim((string)($post['ends_at'] ?? ''));
        if ($starts_at !== '' && strtotime($starts_at) === false) return array('ok'=>false,'message'=>'Exam start date is invalid.');
        if ($ends_at !== '' && strtotime($ends_at) === false) return array('ok'=>false,'message'=>'Exam end date is invalid.');
        if ($starts_at !== '' && $ends_at !== '' && strtotime($ends_at) <= strtotime($starts_at)) {
            return array('ok'=>false,'message'=>'Exam end date must be after its start date.');
        }

        $normalized = array();
        $total_marks = 0;
        $question_count = 0;
        foreach ($sections as $section_index => $section) {
            $section_title = trim((string)($section['title'] ?? ''));
            $source_exam = trim((string)($section['source_exam_type'] ?? ''));
            $source_topic = trim((string)($section['source_topic'] ?? ''));
            $source_difficulty = trim((string)($section['source_difficulty'] ?? ''));
            if ($source_exam === '') return array('ok'=>false,'message'=>'Every section must select Exam Name from master.');
            if ($source_topic === '') return array('ok'=>false,'message'=>'Every section must select Sub Category / Section from master.');
            if (!$this->is_valid_master_pair($source_exam, $source_topic, (int)$admin_id)) return array('ok'=>false,'message'=>'Invalid Exam Name and Sub Category / Section combination in section "' . ($section_title ?: 'Untitled') . '". Create/select it in your own Question Bank master first.');
            $random_count = max(1, (int)($section['random_count'] ?? 0));
            $selection_mode = 'random_pool';
            $question_ids = $this->get_matching_question_ids($source_exam, $source_topic, $source_difficulty, array(), (int)$admin_id);
            $default_marks = (float)($section['default_marks'] ?? 1);
            $default_negative = (float)($section['default_negative_marks'] ?? 0);
            if ($section_title === '') return array('ok'=>false,'message'=>'Every section requires a title.');
            if (empty($question_ids)) return array('ok'=>false,'message'=>'Section "' . $section_title . '" has no active questions in the selected Question Bank pool.');
            if ($random_count > count($question_ids)) return array('ok'=>false,'message'=>'Section "' . $section_title . '" has only ' . count($question_ids) . ' matching active questions for the requested draw count of ' . $random_count . '.');
            if ($default_marks <= 0 || $default_negative < 0 || $default_negative > $default_marks) {
                return array('ok'=>false,'message'=>'Section "' . $section_title . '" has invalid marks or negative marks.');
            }
            $questions = array();
            foreach (array_chunk($question_ids, 500) as $question_id_chunk) {
                $this->db->where_in('id', $question_id_chunk)->where('status','active');
                $this->apply_owner_filter('question_bank_questions', (int)$admin_id);
                $chunk_questions = $this->db->get('question_bank_questions')->result_array();
                foreach ($chunk_questions as $question) $questions[] = $question;
            }
            if (count($questions) !== count($question_ids)) return array('ok'=>false,'message'=>'One or more selected questions are unavailable.');
            $question_map = array();
            foreach ($questions as $question) $question_map[(int)$question['id']] = $question;
            $selection_count = $random_count;
            $section_time_minutes = max(0, (int)($section['section_time_minutes'] ?? 0));
            $section_marks = $default_marks * $selection_count;
            $normalized[] = array(
                'title'=>$section_title,
                'instructions'=>(string)($section['instructions'] ?? ''),
                'source_exam_type'=>$source_exam,
                'source_topic'=>$source_topic,
                'source_difficulty'=>$source_difficulty,
                'default_marks'=>$default_marks,
                'default_negative_marks'=>$default_negative,
                'question_ids'=>$question_ids,
                'question_map'=>$question_map,
                'question_count'=>$selection_count,
                'pool_count'=>count($question_ids),
                'section_time_minutes'=>$section_time_minutes,
                'selection_mode'=>$selection_mode,
                'total_marks'=>$section_marks,
                'sort_order'=>$section_index + 1,
            );
            $question_count += $selection_count;
            $total_marks += $section_marks;
        }
        if ($passing < 0 || $passing > $total_marks) return array('ok'=>false,'message'=>'Passing marks must be between zero and total marks.');

        $now = date('Y-m-d H:i:s');
        $status = (string)($post['status'] ?? 'draft');
        if (!in_array($status,array('draft','in_review','published','archived'),true)) $status='draft';
        if (in_array($status, array('in_review','published'), true) && trim(strip_tags((string)($post['instructions'] ?? ''))) === '') {
            return array('ok'=>false,'message'=>'Candidate instructions are required before review or publish.');
        }
        $review_status = $status === 'in_review' ? 'in_review' : $status;
        $managed_by_admin = (int)$managed_by_admin === 1 ? 1 : 0;
        $exam_data = array(
            'tutor_id'=>$managed_by_admin ? null : (int)$admin_id,
            'managed_by_admin'=>$managed_by_admin,
            'title'=>$title,
            'slug'=>$this->unique_slug($title, (int)$exam_id),
            'description'=>(string)($post['description'] ?? ''),
            'instructions'=>(string)($post['instructions'] ?? ''),
            'policy'=>(string)($post['instructions'] ?? ''),
            'category_id'=>(int)($post['category_id'] ?? 0),
            'class_id'=>(int)($post['class_id'] ?? 0),
            'subject_id'=>(int)($post['subject_id'] ?? 0),
            'difficulty'=>(string)($post['difficulty'] ?? 'Beginner'),
            'exam_type'=>trim((string)($post['exam_type'] ?? '')),
            'time_limit_minutes'=>$duration,
            'total_marks'=>$total_marks,
            'passing_marks'=>$passing,
            'question_limit'=>$question_count,
            'randomize_questions'=>!empty($post['randomize_questions'])?1:0,
            'shuffle_options'=>!empty($post['shuffle_options'])?1:0,
            'show_result_immediately'=>!empty($post['show_result_immediately'])?1:0,
            'show_correct_answers'=>!empty($post['show_correct_answers'])?1:0,
            'show_explanations'=>!empty($post['show_explanations'])?1:0,
            'max_attempts'=>max(0,min(100,(int)($post['max_attempts']??0))),
            'retake_wait_minutes'=>max(0,min(43200,(int)($post['retake_wait_minutes']??0))),
            'manual_moderation_required'=>!empty($post['manual_moderation_required'])?1:0,
            'visibility'=>'public',
            'starts_at'=>$starts_at !== '' ? date('Y-m-d H:i:s', strtotime($starts_at)) : null,
            'ends_at'=>$ends_at !== '' ? date('Y-m-d H:i:s', strtotime($ends_at)) : null,
            'status'=>$status === 'in_review' ? 'draft' : $status,
            'review_status'=>$review_status,
            'is_published'=>$status === 'published' ? 1 : 0,
            'submitted_for_review_at'=>$status === 'in_review' ? $now : null,
            'updated_by'=>(int)$admin_id,
            'updated_at'=>$now,
        );
        if ($status === 'published') {
            $exam_data['published_at'] = $now;
        }

        if ($this->db->field_exists('exam_mode', 'content_exams')) {
            $mode = strtolower(trim((string)($post['exam_mode'] ?? 'mock')));
            $exam_data['exam_mode'] = in_array($mode, array('mock','real','practice'), true) ? $mode : 'mock';
        }

        if ((int)$exam_id > 0) {
            $existing = $this->get_exam($exam_id);
            if (!$existing) return array('ok'=>false,'message'=>'Exam not found.');
        }

        $this->db->trans_start();
        if ((int)$exam_id > 0) {
            // Keep the public URL stable: never regenerate the slug of an existing exam.
            if (trim((string)($existing['slug'] ?? '')) !== '') {
                $exam_data['slug'] = $existing['slug'];
            }
            $exam_data['pattern_version'] = (int)($existing['pattern_version'] ?? 1) + 1;
            $this->db->where('id',(int)$exam_id)->update('content_exams',$exam_data);
            $this->db->where('exam_id',(int)$exam_id)->delete('content_exam_sections');
        } else {
            $exam_data['node_id']=0;
            $exam_data['created_by']=(int)$admin_id;
            $exam_data['created_at']=$now;
            $exam_data['pattern_version']=1;
            $this->db->insert('content_exams',$exam_data);
            $exam_id=(int)$this->db->insert_id();
        }

        foreach ($normalized as $section) {
            $section_row = array(
                'exam_id'=>$exam_id,'title'=>$section['title'],'instructions'=>$section['instructions'],
                'question_count'=>$section['question_count'],'selection_count'=>$section['question_count'],'pool_count'=>$section['pool_count'],
                'source_exam_type'=>$section['source_exam_type'],'source_section'=>$section['source_topic'],'source_difficulty'=>$section['source_difficulty'],
                'total_marks'=>$section['total_marks'],
                'default_marks'=>$section['default_marks'],'default_negative_marks'=>$section['default_negative_marks'],
                'sort_order'=>$section['sort_order'],'created_at'=>$now,'updated_at'=>$now
            );
            if ($this->db->field_exists('section_time_minutes', 'content_exam_sections')) $section_row['section_time_minutes'] = $section['section_time_minutes'];
            if ($this->db->field_exists('selection_mode', 'content_exam_sections')) $section_row['selection_mode'] = $section['selection_mode'];
            $this->db->insert('content_exam_sections', $section_row);
            $section_id=(int)$this->db->insert_id();
            foreach ($section['question_ids'] as $index=>$question_id) {
                $question=$section['question_map'][$question_id];
                $this->db->insert('content_exam_question_bank_map', array(
                    'exam_id'=>$exam_id,'section_id'=>$section_id,'question_bank_id'=>$question_id,
                    'marks_override'=>$section['default_marks'],'negative_marks_override'=>$section['default_negative_marks'],
                    'sort_order'=>$index+1,'created_at'=>$now,'updated_at'=>$now
                ));
            }
        }
        if ($status === 'published') {
            $this->materialize_exam((int)$exam_id, (int)$admin_id);
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return array('ok'=>false,'message'=>'Exam pattern could not be saved.');
        }
        if ($status === 'published') {
            return array('ok'=>true,'message'=>'Exam pattern published. It is live on the mock tests page now.','exam_id'=>$exam_id);
        }
        return array('ok'=>true,'message'=>$status==='in_review'?'Exam pattern submitted for admin review.':'Exam pattern saved successfully.','exam_id'=>$exam_id);
    }

    public function materialize_exam($exam_id, $admin_id)
    {
        $now=date('Y-m-d H:i:s');
        $this->db->where('exam_id',(int)$exam_id)->where('status','active')
            ->update('content_exam_questions',array('status'=>'archived','updated_at'=>$now));
        $maps=$this->db->select('m.*, q.question_text,q.question_type,q.marks,q.negative_marks,q.explanation,q.topic,q.difficulty')
            ->from('content_exam_question_bank_map m')
            ->join('question_bank_questions q','q.id=m.question_bank_id')
            ->where('m.exam_id',(int)$exam_id)
            ->where('q.status','active')
            ->order_by('m.section_id','ASC')->order_by('m.sort_order','ASC')->get()->result_array();
        $optionsByQuestion = array();
        $bankQuestionIds = array_values(array_unique(array_map(function($map) {
            return (int)$map['question_bank_id'];
        }, $maps)));
        foreach (array_chunk($bankQuestionIds, 500) as $questionIdChunk) {
            if (empty($questionIdChunk)) continue;
            $options = $this->db->where_in('question_id', $questionIdChunk)
                ->order_by('question_id','ASC')->order_by('sort_order','ASC')
                ->get('question_bank_options')->result_array();
            foreach ($options as $option) {
                $questionId = (int)$option['question_id'];
                if (!isset($optionsByQuestion[$questionId])) $optionsByQuestion[$questionId] = array();
                $optionsByQuestion[$questionId][] = $option;
            }
        }
        $legacyOptions = array();
        foreach ($maps as $index=>$map) {
            $this->db->insert('content_exam_questions',array(
                'exam_id'=>(int)$exam_id,'question_bank_id'=>(int)$map['question_bank_id'],'section_id'=>(int)$map['section_id'],
                'tutor_id'=>(int)$admin_id,'question_text'=>$map['question_text'],'question_type'=>$map['question_type'],
                'marks'=>$map['marks_override']!==null?$map['marks_override']:$map['marks'],
                'negative_marks'=>$map['negative_marks_override']!==null?$map['negative_marks_override']:$map['negative_marks'],
                'explanation'=>$map['explanation'],'topic'=>$map['topic'],'difficulty'=>$map['difficulty'],
                'status'=>'active','sort_order'=>$index+1,'created_at'=>$now,'updated_at'=>$now
            ));
            $legacy_id=(int)$this->db->insert_id();
            $options=$optionsByQuestion[(int)$map['question_bank_id']] ?? array();
            foreach ($options as $option) {
                $legacyOptions[]=array(
                    'question_id'=>$legacy_id,'option_text'=>$option['option_text'],'is_correct'=>$option['is_correct'],
                    'sort_order'=>$option['sort_order'],'created_at'=>$now,'updated_at'=>$now
                );
            }
            $this->db->where('id',(int)$map['id'])->update('content_exam_question_bank_map',array('materialized_question_id'=>$legacy_id,'updated_at'=>$now));
        }
        foreach (array_chunk($legacyOptions, 500) as $optionChunk) {
            $this->db->insert_batch('content_exam_options', $optionChunk);
        }
    }

    public function archive_exam($admin_id, $exam_id)
    {
        if (!$this->get_exam($exam_id)) return array('ok'=>false,'message'=>'Exam not found.');
        $now=date('Y-m-d H:i:s');
        $this->db->where('id',(int)$exam_id)->update('content_exams',array(
            'status'=>'archived','is_published'=>0,'updated_by'=>(int)$admin_id,'updated_at'=>$now
        ));
        return array('ok'=>true,'message'=>'Exam archived.');
    }

    public function publish_pattern($admin_id, $exam_id)
    {
        $exam = $this->get_exam($exam_id);
        if (!$exam) return array('ok'=>false,'message'=>'Exam not found.');
        if ((int)$exam['time_limit_minutes'] <= 0) return array('ok'=>false,'message'=>'Time limit must be positive before publishing.');
        $validation = $this->validate_pools_for_publish((int)$exam_id);
        if (empty($validation['ok'])) return $validation;
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('id',(int)$exam_id)->update('content_exams',array(
            'status'=>'published','review_status'=>'published','is_published'=>1,'visibility'=>'public',
            'published_at'=>$now,'reviewed_by'=>(int)$admin_id,'reviewed_at'=>$now,
            'updated_by'=>(int)$admin_id,'updated_at'=>$now
        ));
        $this->materialize_exam((int)$exam_id,(int)$admin_id);
        $this->db->trans_complete();
        return $this->db->trans_status()
            ? array('ok'=>true,'message'=>'Exam published. It is live on the mock tests page now.')
            : array('ok'=>false,'message'=>'Exam could not be published.');
    }

    public function validate_pools_for_publish($exam_id)
    {
        $sections = $this->db->where('exam_id', (int)$exam_id)
            ->order_by('sort_order', 'ASC')->get('content_exam_sections')->result_array();
        if (empty($sections)) return array('ok'=>false,'message'=>'Exam must contain at least one section before publishing.');
        foreach ($sections as $section) {
            $available = (int)$this->db->select('COUNT(DISTINCT m.question_bank_id) AS total', false)
                ->from('content_exam_question_bank_map m')
                ->join('question_bank_questions q', 'q.id=m.question_bank_id')
                ->where('m.section_id', (int)$section['id'])
                ->where('q.status', 'active')
                ->get()->row()->total;
            $required = max(1, (int)($section['selection_count'] ?? $section['question_count'] ?? 0));
            if ($available < $required) {
                return array(
                    'ok'=>false,
                    'message'=>'Section "' . (string)$section['title'] . '" has only ' . $available . ' active question(s) available, but ' . $required . ' are required before publishing.'
                );
            }
        }
        return array('ok'=>true,'message'=>'Question pools are ready.');
    }

    public function unpublish_pattern($admin_id, $exam_id)
    {
        if (!$this->get_exam($exam_id)) return array('ok'=>false,'message'=>'Exam not found.');
        $this->db->where('id',(int)$exam_id)->update('content_exams',array(
            'status'=>'draft','review_status'=>'draft','is_published'=>0,
            'updated_by'=>(int)$admin_id,'updated_at'=>date('Y-m-d H:i:s')
        ));
        return array('ok'=>true,'message'=>'Exam unpublished and moved back to draft.');
    }

    private function unique_slug($title, $exam_id=0)
    {
        $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')) ?: 'exam';
        $slug=$base; $i=2;
        while (true) {
            $this->db->where('slug',$slug);
            if ($exam_id>0) $this->db->where('id !=',(int)$exam_id);
            if (!$this->db->get('content_exams')->row_array()) return $slug;
            $slug=$base.'-'.$i++;
        }
    }

    private function apply_owner_filter($table_or_alias, $owner_id = null, $real_table = null)
    {
        if ($owner_id === null) return;
        $real_table = $real_table ?: $table_or_alias;
        if ($this->db->field_exists('created_by', $real_table)) {
            $this->db->where($table_or_alias . '.created_by', (int)$owner_id);
        }
    }
}
