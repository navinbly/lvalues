<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    private function normalize_question_type($type)
    {
        $type = strtolower(trim((string)$type));
        if ($type === 'single_choice') return 'single';
        if ($type === 'multiple_choice') return 'multiple';
        if (!in_array($type, ['single','multiple'], true)) return 'single';
        return $type;
    }

    private function safe_limit_text($text, $limit = 160)
    {
        $text = trim(strip_tags((string)$text));
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }

    public function get_exam_owner_id($exam_id)
    {
        $row = $this->db->select('tutor_id')->get_where('content_exams', ['id'=>(int)$exam_id])->row_array();
        return $row ? (int)$row['tutor_id'] : 0;
    }

    private function slugify($text)
    {
        $text = strtolower(trim((string)$text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'exam';
    }

    private function unique_slug($slug, $exam_id = 0)
    {
        $base = $slug ?: 'exam';
        $i = 2;
        while (true) {
            $this->db->where('slug', $slug);
            if ($exam_id > 0) $this->db->where('id !=', (int)$exam_id);
            $exists = $this->db->get('content_exams')->row_array();
            if (!$exists) return $slug;
            $slug = $base . '-' . $i++;
        }
    }

    public function get_exam_by_node($node_id, $only_published = false)
    {
        $this->db->from('content_exams');
        $this->db->where('node_id', (int)$node_id);
        if ($only_published) {
            $this->db->group_start()->where('is_published', 1)->or_where('status', 'published')->group_end();
            $this->db->where('visibility', 'public');
        }
        return $this->db->get()->row_array();
    }

    public function get_exam($exam_id)
    {
        return $this->db->get_where('content_exams', ['id' => (int)$exam_id])->row_array();
    }

    public function get_exam_by_slug($slug, $only_published = true)
    {
        $this->db->where('slug', (string)$slug);
        if ($only_published) {
            $this->db->group_start()->where('is_published', 1)->or_where('status', 'published')->group_end();
            $this->db->where('visibility', 'public');
        }
        return $this->db->get('content_exams')->row_array();
    }

    public function get_tutor_exams($tutor_id)
    {
        $this->db->select('e.*, COUNT(DISTINCT q.id) AS question_count, COUNT(DISTINCT a.id) AS attempt_count, AVG(a.percentage) AS avg_score');
        $this->db->from('content_exams e');
        $this->db->join('content_exam_questions q', "q.exam_id = e.id AND IFNULL(q.status,'active') = 'active'", 'left');
        $this->db->join('content_exam_attempts a', 'a.exam_id = e.id AND a.submitted_at IS NOT NULL', 'left');
        $this->db->where('e.tutor_id', (int)$tutor_id);
        $this->db->group_by('e.id');
        $this->db->order_by('e.updated_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_all_public_exams_for_admin($owner_id = null, $managed_by_admin = null)
    {
        $this->db->select('e.*, u.first_name, u.last_name, COUNT(DISTINCT q.id) AS question_count, COUNT(DISTINCT a.id) AS attempt_count, AVG(a.percentage) AS avg_score');
        $this->db->from('content_exams e');
        $this->db->join('users u', 'u.id = e.tutor_id', 'left');
        $this->db->join('content_exam_questions q', "q.exam_id = e.id AND IFNULL(q.status,'active')='active'", 'left');
        $this->db->join('content_exam_attempts a', 'a.exam_id = e.id AND a.submitted_at IS NOT NULL', 'left');
        if ($owner_id !== null) {
            $this->db->where('e.created_by', (int)$owner_id);
        }
        if ($managed_by_admin !== null && $this->db->field_exists('managed_by_admin', 'content_exams')) {
            $this->db->where('e.managed_by_admin', (int)$managed_by_admin);
        }
        $this->db->group_by('e.id');
        $this->db->order_by('e.updated_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_public_exams($limit = 0)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->select('e.*, u.first_name, u.last_name, COUNT(DISTINCT q.id) AS question_count, COUNT(DISTINCT a.id) AS attempt_count, ROUND(AVG(a.percentage),2) AS avg_score');
        $this->db->from('content_exams e');
        $this->db->join('users u', 'u.id = e.tutor_id', 'left');
        $this->db->join('content_exam_questions q', "q.exam_id = e.id AND IFNULL(q.status,'active')='active'", 'left');
        $this->db->join('content_exam_attempts a', 'a.exam_id = e.id AND a.submitted_at IS NOT NULL', 'left');
        $this->db->group_start()->where('e.status', 'published')->or_where('e.is_published', 1)->group_end();
        $this->db->where('e.visibility', 'public');
        $this->db->group_start()->where('e.starts_at IS NULL', null, false)->or_where('e.starts_at <=', $now)->group_end();
        $this->db->group_start()->where('e.ends_at IS NULL', null, false)->or_where('e.ends_at >=', $now)->group_end();
        $this->db->group_by('e.id');
        $this->db->order_by('attempt_count', 'DESC');
        $this->db->order_by('e.published_at', 'DESC');
        $this->db->order_by('e.updated_at', 'DESC');
        if ((int)$limit > 0) $this->db->limit((int)$limit);
        return $this->db->get()->result_array();
    }

    public function get_featured_public_exams($limit = 6)
    {
        if (!$this->db->table_exists('content_exams')) return [];
        return $this->get_public_exams((int)$limit > 0 ? (int)$limit : 6);
    }

    public function save_exam($tutor_id, $data, $exam_id = 0)
    {
        $now = date('Y-m-d H:i:s');
        $exam_id = (int)$exam_id;
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') return ['ok' => false, 'message' => 'Exam title is required.'];
        $passing = (float)($data['passing_marks'] ?? 0);
        $total = (float)($data['total_marks'] ?? 0);
        if ($total <= 0) return ['ok' => false, 'message' => 'Total marks must be greater than 0.'];
        if ($passing > $total) return ['ok' => false, 'message' => 'Passing marks cannot be greater than total marks.'];
        if ((int)($data['time_limit_minutes'] ?? 0) <= 0) return ['ok' => false, 'message' => 'Time limit must be positive.'];
        if ((int)($data['question_limit'] ?? 0) <= 0) return ['ok' => false, 'message' => 'Question limit must be positive.'];

        $row = [
            'tutor_id' => (int)$tutor_id,
            'title' => $title,
            'description' => (string)($data['description'] ?? ''),
            'category_id' => (int)($data['category_id'] ?? 0),
            'class_id' => (int)($data['class_id'] ?? 0),
            'subject_id' => (int)($data['subject_id'] ?? 0),
            'difficulty' => (string)($data['difficulty'] ?? 'Beginner'),
            'instructions' => (string)($data['instructions'] ?? ''),
            'policy' => (string)($data['instructions'] ?? ''),
            'time_limit_minutes' => (int)($data['time_limit_minutes'] ?? 30),
            'total_marks' => $total,
            'passing_marks' => $passing,
            'question_limit' => (int)($data['question_limit'] ?? 10),
            'randomize_questions' => !empty($data['randomize_questions']) ? 1 : 0,
            'shuffle_options' => !empty($data['shuffle_options']) ? 1 : 0,
            'show_result_immediately' => !empty($data['show_result_immediately']) ? 1 : 0,
            'show_correct_answers' => !empty($data['show_correct_answers']) ? 1 : 0,
            'show_explanations' => !empty($data['show_explanations']) ? 1 : 0,
            'visibility' => 'public',
            'starts_at' => !empty($data['starts_at']) ? $data['starts_at'] : null,
            'ends_at' => !empty($data['ends_at']) ? $data['ends_at'] : null,
            'updated_at' => $now,
        ];
        $desiredStatus = (string)($data['status'] ?? 'draft');
        if (!in_array($desiredStatus, ['draft','published','archived'], true)) $desiredStatus = 'draft';
        if ($exam_id > 0) {
            $existing = $this->get_exam($exam_id);
            if (!$existing || (int)$existing['tutor_id'] !== (int)$tutor_id) return ['ok' => false, 'message' => 'Exam not found or access denied.'];
            $row['slug'] = $this->unique_slug($this->slugify($title), $exam_id);
            $this->db->where('id', $exam_id)->update('content_exams', $row);
        } else {
            $row['slug'] = $this->unique_slug($this->slugify($title));
            $row['node_id'] = 0;
            $row['status'] = 'draft';
            $row['is_published'] = 0;
            $row['created_by'] = (int)$tutor_id;
            $row['created_at'] = $now;
            $this->db->insert('content_exams', $row);
            $exam_id = (int)$this->db->insert_id();
        }
        if ($desiredStatus === 'published') {
            $pub = $this->publish_exam($tutor_id, $exam_id);
            if (empty($pub['ok'])) return $pub;
        } elseif ($desiredStatus === 'archived') {
            $this->archive_exam($tutor_id, $exam_id);
        }
        return ['ok' => true, 'message' => 'Exam saved successfully.', 'exam_id' => $exam_id];
    }

    public function publish_exam($tutor_id, $exam_id)
    {
        $exam = $this->get_exam((int)$exam_id);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok' => false, 'message' => 'Exam not found or access denied.'];
        if (trim((string)$exam['title']) === '') return ['ok' => false, 'message' => 'Exam title is required.'];
        if ((float)$exam['passing_marks'] > (float)$exam['total_marks']) return ['ok' => false, 'message' => 'Passing marks cannot be greater than total marks.'];
        if ((int)$exam['time_limit_minutes'] <= 0) return ['ok' => false, 'message' => 'Time limit must be positive.'];
        $active = $this->count_active_questions((int)$exam_id);
        if ($active < (int)$exam['question_limit']) return ['ok' => false, 'message' => 'Not enough active questions. Active: '.$active.', required: '.(int)$exam['question_limit']];
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$exam_id)->update('content_exams', ['status'=>'published','is_published'=>1,'visibility'=>'public','published_at'=>$now,'updated_at'=>$now]);
        return ['ok' => true, 'message' => 'Exam published successfully.'];
    }

    public function archive_exam($tutor_id, $exam_id)
    {
        $exam = $this->get_exam((int)$exam_id);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok' => false, 'message' => 'Exam not found or access denied.'];
        $this->db->where('id', (int)$exam_id)->update('content_exams', ['status'=>'archived','is_published'=>0,'updated_at'=>date('Y-m-d H:i:s')]);
        return ['ok' => true, 'message' => 'Exam archived.'];
    }

    public function admin_unpublish($exam_id)
    {
        $this->db->where('id', (int)$exam_id)->update('content_exams', ['status'=>'archived','is_published'=>0,'updated_at'=>date('Y-m-d H:i:s')]);
        return ['ok'=>true,'message'=>'Exam unpublished.'];
    }

    public function count_active_questions($exam_id)
    {
        return (int)$this->db->where('exam_id', (int)$exam_id)->where('status', 'active')->count_all_results('content_exam_questions');
    }

    public function get_questions_with_options($exam_id, $active_only = false, $include_correct = true)
    {
        $this->db->order_by('sort_order', 'ASC')->where('exam_id', (int)$exam_id);
        if ($active_only) $this->db->where('status', 'active');
        $qs = $this->db->get('content_exam_questions')->result_array();
        if (empty($qs)) return [];

        $questionIds = array_map(function($question) {
            return (int)$question['id'];
        }, $qs);
        $optionsByQuestion = [];
        foreach (array_chunk($questionIds, 500) as $questionIdChunk) {
            $options = $this->db->where_in('question_id', $questionIdChunk)
                ->order_by('question_id', 'ASC')->order_by('sort_order', 'ASC')
                ->get('content_exam_options')->result_array();
            foreach ($options as $option) {
                $questionId = (int)$option['question_id'];
                if (!isset($optionsByQuestion[$questionId])) $optionsByQuestion[$questionId] = [];
                if (!$include_correct) unset($option['is_correct']);
                $optionsByQuestion[$questionId][] = $option;
            }
        }
        foreach ($qs as &$q) {
            $q['options'] = $optionsByQuestion[(int)$q['id']] ?? [];
        }
        unset($q);
        return $qs;
    }

    public function get_question($question_id)
    {
        $q = $this->db->get_where('content_exam_questions', ['id'=>(int)$question_id])->row_array();
        if ($q) $q['options'] = $this->db->order_by('sort_order','ASC')->get_where('content_exam_options', ['question_id'=>(int)$question_id])->result_array();
        return $q;
    }

    public function save_question($tutor_id, $exam_id, $post, $question_id = 0)
    {
        $exam = $this->get_exam((int)$exam_id);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok'=>false,'message'=>'Exam not found or access denied.'];
        $text = trim((string)($post['question_text'] ?? ''));
        if ($text === '') return ['ok'=>false,'message'=>'Question text is required.'];
        $type = $this->normalize_question_type($post['question_type'] ?? 'single');
        $options = $post['options'] ?? [];
        $correct = $post['correct_options'] ?? [];
        if (!is_array($options)) $options = [];
        if (!is_array($correct)) $correct = [$correct];
        $clean = [];
        foreach ($options as $idx => $opt) {
            $opt = trim((string)$opt);
            if ($opt !== '') $clean[(int)$idx] = $opt;
        }
        if (count($clean) < 2) return ['ok'=>false,'message'=>'Add at least two options.'];
        if (empty($correct)) return ['ok'=>false,'message'=>'Select correct answer.'];
        if ($type === 'single' && count($correct) > 1) return ['ok'=>false,'message'=>'Single choice must have only one correct answer.'];
        $now = date('Y-m-d H:i:s');
        $row = [
            'exam_id'=>(int)$exam_id,
            'tutor_id'=>(int)$tutor_id,
            'question_text'=>$text,
            'question_type'=>$type,
            'marks'=>(float)($post['marks'] ?? 1),
            'explanation'=>(string)($post['explanation'] ?? ''),
            'topic'=>(string)($post['topic'] ?? ''),
            'difficulty'=>(string)($post['difficulty'] ?? 'Beginner'),
            'status'=>(string)($post['status'] ?? 'active'),
            'updated_at'=>$now,
        ];
        $question_id = (int)$question_id;
        if ($question_id > 0) {
            $q = $this->get_question($question_id);
            if (!$q || (int)$q['exam_id'] !== (int)$exam_id) return ['ok'=>false,'message'=>'Question not found.'];
            $this->db->where('id', $question_id)->update('content_exam_questions', $row);
            $this->db->delete('content_exam_options', ['question_id'=>$question_id]);
        } else {
            $row['sort_order'] = (int)$this->db->where('exam_id',(int)$exam_id)->count_all_results('content_exam_questions') + 1;
            $row['created_at'] = $now;
            $this->db->insert('content_exam_questions', $row);
            $question_id = (int)$this->db->insert_id();
        }
        $sort = 1;
        foreach ($clean as $idx => $optText) {
            $this->db->insert('content_exam_options', [
                'question_id'=>$question_id,
                'option_text'=>$optText,
                'is_correct'=>in_array((string)$idx, array_map('strval',$correct), true) ? 1 : 0,
                'sort_order'=>$sort++,
                'created_at'=>$now,
                'updated_at'=>$now,
            ]);
        }
        return ['ok'=>true,'message'=>'Question saved successfully.'];
    }

    public function archive_question($tutor_id, $question_id)
    {
        $q = $this->get_question((int)$question_id);
        if (!$q) return ['ok'=>false,'message'=>'Question not found.'];
        $exam = $this->get_exam((int)$q['exam_id']);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok'=>false,'message'=>'Access denied.'];
        $this->db->where('id',(int)$question_id)->update('content_exam_questions',['status'=>'archived','updated_at'=>date('Y-m-d H:i:s')]);
        return ['ok'=>true,'message'=>'Question archived.'];
    }

    public function duplicate_question($tutor_id, $question_id)
    {
        $q = $this->get_question((int)$question_id);
        if (!$q) return ['ok'=>false,'message'=>'Question not found.'];
        $exam = $this->get_exam((int)$q['exam_id']);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok'=>false,'message'=>'Access denied.'];
        $opts = $q['options']; unset($q['id'], $q['options']);
        $q['question_text'] .= ' (Copy)'; $q['created_at']=date('Y-m-d H:i:s'); $q['updated_at']=$q['created_at'];
        $q['sort_order'] = (int)$this->db->where('exam_id',(int)$q['exam_id'])->count_all_results('content_exam_questions') + 1;
        $this->db->insert('content_exam_questions', $q);
        $newId = (int)$this->db->insert_id();
        foreach ($opts as $o) { unset($o['id']); $o['question_id']=$newId; $o['created_at']=date('Y-m-d H:i:s'); $o['updated_at']=$o['created_at']; $this->db->insert('content_exam_options',$o); }
        return ['ok'=>true,'message'=>'Question duplicated.'];
    }

    public function create_attempt($exam_id, $participant = [], $mode = 'mock')
    {
        $exam = $this->get_exam((int)$exam_id);
        if (!$exam) return ['attempt_id'=>0, 'access_token'=>''];
        $configuredMode = strtolower((string)($exam['exam_mode'] ?? 'mock'));
        if (!in_array($configuredMode, ['practice','mock','real'], true)) $configuredMode = 'mock';
        if ($mode === 'public' || $mode === '') $mode = $configuredMode;
        if (!in_array($mode, ['practice','mock','real'], true)) $mode = $configuredMode;
        if ($configuredMode === 'real') $mode = 'real';
        $user_id=(int)($participant['user_id']??0);
        $email=trim((string)($participant['email']??''));
        $mobile=trim((string)($participant['mobile']??''));
        $this->db->from('content_exam_attempts')->where('exam_id',(int)$exam_id);
        if($user_id>0)$this->db->where('user_id',$user_id);elseif($email!=='')$this->db->where('participant_email',$email);elseif($mobile!=='')$this->db->where('participant_mobile',$mobile);else$this->db->where('id',0);
        $attempt_count=(int)$this->db->count_all_results();
        $max_attempts=max(0,(int)($exam['max_attempts']??0));
        if ($mode === 'real') $max_attempts = 1;
        if($max_attempts>0&&$attempt_count>=$max_attempts)return ['attempt_id'=>0,'access_token'=>'','message'=>$mode === 'real' ? 'This real test allows only one attempt.' : 'The maximum number of attempts has been reached.'];
        $this->db->from('content_exam_attempts')->where('exam_id',(int)$exam_id);
        if($user_id>0)$this->db->where('user_id',$user_id);elseif($email!=='')$this->db->where('participant_email',$email);elseif($mobile!=='')$this->db->where('participant_mobile',$mobile);else$this->db->where('id',0);
        $latest=$this->db->order_by('started_at','DESC')->get()->row_array();
        $wait=max(0,(int)($exam['retake_wait_minutes']??0));
        if($latest&&$wait>0&&strtotime($latest['started_at'].' +'.$wait.' minutes')>time())return ['attempt_id'=>0,'access_token'=>'','message'=>'Please wait before starting another attempt.'];
        $questions = $this->get_questions_with_options((int)$exam_id, true, true);
        $selected = $this->select_attempt_questions($exam, $questions);
        if (empty($selected)) return ['attempt_id'=>0, 'access_token'=>''];
        $ids = array_map(function($q){ return (int)$q['id']; }, $selected);
        $now = date('Y-m-d H:i:s');
        $isTimed = $mode !== 'practice';
        if ($mode === 'real') $isTimed = true;
        $rawToken = bin2hex(random_bytes(24));
        $totalMarks = 0;
        foreach ($selected as $question) $totalMarks += (float)$question['marks'];

        $this->db->trans_start();
        $this->db->insert('content_exam_attempts', [
            'exam_id'=>(int)$exam_id,
            'user_id'=>$user_id,
            'attempt_code'=>substr(bin2hex(random_bytes(12)), 0, 16),
            'access_token_hash'=>hash('sha256', $rawToken),
            'participant_name'=>(string)($participant['name'] ?? ''),
            'participant_email'=>(string)($participant['email'] ?? ''),
            'participant_mobile'=>(string)($participant['mobile'] ?? ''),
            'selected_question_ids'=>json_encode($ids),
            'attempt_mode'=>$mode,
            'current_question_index'=>0,
            'pattern_version'=>(int)($exam['pattern_version'] ?? 1),
            'is_timed'=>$isTimed ? 1 : 0,
            'allow_feedback_during_attempt'=>$mode === 'practice' ? 1 : 0,
            'started_at'=>$now,
            'expires_at'=>$isTimed ? date('Y-m-d H:i:s', strtotime($now) + (max(1, (int)$exam['time_limit_minutes']) * 60)) : null,
            'last_activity_at'=>$now,
            'created_at'=>$now,
            'moderation_status'=>!empty($exam['manual_moderation_required'])?'pending':'auto_graded',
            'total_questions'=>count($ids),
            'total_marks'=>$totalMarks,
        ]);
        $attemptId = (int)$this->db->insert_id();
        $this->snapshot_attempt_questions($attemptId, $selected, !empty($exam['shuffle_options']));
        $this->db->trans_complete();

        if (!$this->db->trans_status()) return ['attempt_id'=>0, 'access_token'=>''];
        return ['attempt_id'=>$attemptId, 'access_token'=>$rawToken];
    }

    private function order_attempt_questions($questions, $randomize)
    {
        $groups = [];
        foreach ($questions as $question) {
            $key = (int)($question['section_id'] ?? 0);
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][] = $question;
        }
        $ordered = [];
        foreach ($groups as $group) {
            if ($randomize && count($group) > 1) shuffle($group);
            foreach ($group as $question) $ordered[] = $question;
        }
        return $ordered;
    }

    private function select_attempt_questions($exam, $questions)
    {
        $groups=[]; foreach($questions as $question){$key=(int)($question['section_id']??0);if(!isset($groups[$key]))$groups[$key]=[];$groups[$key][]=$question;}
        $sectionCounts=[];
        if($this->db->table_exists('content_exam_sections')){
            $sections=$this->db->where('exam_id',(int)$exam['id'])->order_by('sort_order','ASC')->get('content_exam_sections')->result_array();
            foreach($sections as $section)$sectionCounts[(int)$section['id']]=max(1,(int)($section['selection_count']??$section['question_count']));
        }
        $selected=[];
        foreach($groups as $sectionId=>$group){
            if(count($group)>1)shuffle($group);
            $count=$sectionCounts[$sectionId]??count($group);
            foreach(array_slice($group,0,min($count,count($group))) as $question)$selected[]=$question;
        }
        if(empty($sectionCounts)){
            if(!empty($exam['randomize_questions'])&&count($selected)>1)shuffle($selected);
            $selected=array_slice($selected,0,min((int)$exam['question_limit'],count($selected)));
        }
        return $selected;
    }

    private function snapshot_attempt_questions($attemptId, $questions, $shuffleOptions)
    {
        $sectionIds = [];
        foreach ($questions as $question) {
            if (!empty($question['section_id'])) $sectionIds[] = (int)$question['section_id'];
        }
        $sectionMap = [];
        if (!empty($sectionIds)) {
            $sections = $this->db->where_in('id', array_values(array_unique($sectionIds)))
                ->order_by('sort_order', 'ASC')->get('content_exam_sections')->result_array();
            foreach ($sections as $section) $sectionMap[(int)$section['id']] = $section;
        }

        $order = 1;
        $rows = [];
        $createdAt = date('Y-m-d H:i:s');
        foreach ($questions as $question) {
            $options = $question['options'] ?? [];
            if ($shuffleOptions && count($options) > 1) shuffle($options);
            $snapshotOptions = [];
            $correctIds = [];
            foreach ($options as $option) {
                $snapshotOptions[] = [
                    'id'=>(int)$option['id'],
                    'option_text'=>(string)$option['option_text'],
                    'sort_order'=>(int)($option['sort_order'] ?? 0),
                ];
                if (!empty($option['is_correct'])) $correctIds[] = (int)$option['id'];
            }
            $sectionId = (int)($question['section_id'] ?? 0);
            $section = $sectionMap[$sectionId] ?? null;
            $snapshotRow = [
                'attempt_id'=>(int)$attemptId,
                'question_id'=>(int)$question['id'],
                'section_id'=>$sectionId > 0 ? $sectionId : null,
                'section_title'=>$section ? (string)$section['title'] : 'General',
                'section_order'=>$section ? (int)$section['sort_order'] : 1,
                'question_order'=>$order++,
                'question_text'=>(string)$question['question_text'],
                'question_type'=>(string)$question['question_type'],
                'marks'=>(float)$question['marks'],
                'negative_marks'=>(float)($question['negative_marks'] ?? 0),
                'explanation'=>(string)($question['explanation'] ?? ''),
                'topic'=>(string)($question['topic'] ?? ''),
                'difficulty'=>(string)($question['difficulty'] ?? ''),
                'options_json'=>json_encode($snapshotOptions),
                'correct_option_ids'=>json_encode($correctIds),
                'created_at'=>$createdAt,
            ];
            if ($this->db->field_exists('section_time_minutes', 'content_exam_attempt_questions')) {
                $snapshotRow['section_time_minutes'] = $section ? (int)($section['section_time_minutes'] ?? 0) : 0;
            }
            $rows[] = $snapshotRow;
        }
        foreach (array_chunk($rows, 250) as $chunk) {
            $this->db->insert_batch('content_exam_attempt_questions', $chunk);
        }
    }

    public function get_attempt($attempt_id)
    {
        return $this->db->get_where('content_exam_attempts', ['id'=>(int)$attempt_id])->row_array();
    }

    public function get_attempt_questions($attempt_id, $include_correct = false)
    {
        $attempt = $this->get_attempt($attempt_id);
        if (!$attempt) return [];
        if ($this->db->table_exists('content_exam_attempt_questions')) {
            $rows = $this->db->where('attempt_id', (int)$attempt_id)
                ->order_by('question_order', 'ASC')->get('content_exam_attempt_questions')->result_array();
            if (!empty($rows)) {
                foreach ($rows as &$row) {
                    $row['id'] = (int)$row['question_id'];
                    $options = json_decode((string)$row['options_json'], true);
                    $correctIds = json_decode((string)$row['correct_option_ids'], true);
                    if (!is_array($options)) $options = [];
                    if (!is_array($correctIds)) $correctIds = [];
                    foreach ($options as &$option) {
                        if ($include_correct) $option['is_correct'] = in_array((int)$option['id'], array_map('intval', $correctIds), true) ? 1 : 0;
                    }
                    unset($option);
                    $row['options'] = $options;
                    unset($row['options_json'], $row['correct_option_ids']);
                }
                unset($row);
                return $rows;
            }
        }
        $ids = json_decode((string)($attempt['selected_question_ids'] ?? '[]'), true);
        if (!is_array($ids) || empty($ids)) {
            $answerRows = $this->db->select('question_id')->where('attempt_id', (int)$attempt_id)
                ->order_by('id', 'ASC')->get('content_exam_attempt_answers')->result_array();
            $ids = array_map(function($row){ return (int)$row['question_id']; }, $answerRows);
        }
        if (empty($ids)) return [];
        $questions = [];
        foreach ($ids as $id) {
            $q = $this->get_question((int)$id);
            if (!$q) continue;
            if (!$include_correct) foreach ($q['options'] as &$o) unset($o['is_correct']);
            unset($o);
            $questions[] = $q;
        }
        return $questions;
    }

    public function save_answer($attempt_id, $question_id, $selected_options)
    {
        $attempt_id = (int)$attempt_id; $question_id = (int)$question_id;
        $attempt = $this->get_attempt($attempt_id);
        if (!$attempt || !empty($attempt['submitted_at'])) return ['ok'=>false];
        $allowed_question_ids = json_decode((string)($attempt['selected_question_ids'] ?? '[]'), true);
        if (!is_array($allowed_question_ids) || !in_array($question_id, array_map('intval', $allowed_question_ids), true)) return ['ok'=>false];
        if (!is_array($selected_options)) $selected_options = [$selected_options];
        $selected_options = array_values(array_unique(array_map('intval', $selected_options)));
        $q = $this->get_attempt_question($attempt_id, $question_id, true);
        if (!$q) return ['ok'=>false];
        $correctIds = [];
        $validOptionIds = [];
        foreach ($q['options'] as $o) {
            $validOptionIds[] = (int)$o['id'];
            if ((int)$o['is_correct'] === 1) $correctIds[] = (int)$o['id'];
        }
        $selected_options = array_values(array_intersect($selected_options, $validOptionIds));
        sort($correctIds); $sel = $selected_options; sort($sel);
        $is_correct = ($sel === $correctIds) ? 1 : 0;
        $marks_awarded = $is_correct ? (float)$q['marks'] : (empty($selected_options) ? 0.0 : -1 * (float)($q['negative_marks'] ?? 0));
        $row = ['attempt_id'=>$attempt_id,'question_id'=>$question_id,'option_id'=>isset($selected_options[0])?$selected_options[0]:0,'selected_option_ids'=>json_encode($selected_options),'is_correct'=>$is_correct,'marks_awarded'=>$marks_awarded,'created_at'=>date('Y-m-d H:i:s')];
        $existing = $this->db->get_where('content_exam_attempt_answers',['attempt_id'=>$attempt_id,'question_id'=>$question_id])->row_array();
        if ($existing) $this->db->where('id',(int)$existing['id'])->update('content_exam_attempt_answers',$row); else $this->db->insert('content_exam_attempt_answers',$row);
        $this->db->where('id', $attempt_id)->update('content_exam_attempts', [
            'last_activity_at'=>date('Y-m-d H:i:s'),
            'autosaved_at'=>date('Y-m-d H:i:s'),
        ]);
        return [
            'ok'=>true,
            'is_correct'=>(bool)$is_correct,
            'marks_awarded'=>$marks_awarded,
            'correct_option_ids'=>$correctIds,
            'explanation'=>(string)($q['explanation'] ?? ''),
        ];
    }

    private function get_attempt_question($attemptId, $questionId, $includeCorrect = false)
    {
        $questions = $this->get_attempt_questions((int)$attemptId, $includeCorrect);
        foreach ($questions as $question) {
            if ((int)$question['id'] === (int)$questionId) return $question;
        }
        return null;
    }

    public function can_access_attempt($attempt, $rawToken = '', $sessionUserId = 0)
    {
        if (!$attempt) return false;
        if ((int)$attempt['user_id'] > 0 && (int)$attempt['user_id'] === (int)$sessionUserId) return true;
        $storedHash = (string)($attempt['access_token_hash'] ?? '');
        return $storedHash !== '' && $rawToken !== '' && hash_equals($storedHash, hash('sha256', $rawToken));
    }

    public function is_attempt_expired($attempt)
    {
        return !empty($attempt['is_timed']) && !empty($attempt['expires_at']) && strtotime($attempt['expires_at']) <= time();
    }

    public function update_attempt_progress($attemptId, $index, $autosaved = false)
    {
        $row = [
            'current_question_index'=>max(0, (int)$index),
            'last_activity_at'=>date('Y-m-d H:i:s'),
        ];
        if ($autosaved) $row['autosaved_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$attemptId)->update('content_exam_attempts', $row);
    }

    public function get_answered_question_ids($attemptId)
    {
        $rows = $this->db->select('question_id')->where('attempt_id', (int)$attemptId)
            ->where("selected_option_ids IS NOT NULL AND selected_option_ids <> '[]'", null, false)
            ->get('content_exam_attempt_answers')->result_array();
        return array_map(function($row){ return (int)$row['question_id']; }, $rows);
    }

    public function get_review_question_ids($attemptId)
    {
        $rows=$this->db->select('question_id')->where('attempt_id',(int)$attemptId)->where('is_marked_for_review',1)->get('content_exam_attempt_answers')->result_array();
        return array_map(function($row){return (int)$row['question_id'];},$rows);
    }

    public function mark_for_review($attemptId, $questionId, $marked = true)
    {
        $existing=$this->db->get_where('content_exam_attempt_answers',['attempt_id'=>(int)$attemptId,'question_id'=>(int)$questionId])->row_array();
        if($existing)$this->db->where('id',(int)$existing['id'])->update('content_exam_attempt_answers',['is_marked_for_review'=>$marked?1:0]);
        else $this->db->insert('content_exam_attempt_answers',['attempt_id'=>(int)$attemptId,'question_id'=>(int)$questionId,'option_id'=>0,'selected_option_ids'=>'[]','is_correct'=>0,'is_marked_for_review'=>$marked?1:0,'marks_awarded'=>0,'created_at'=>date('Y-m-d H:i:s')]);
    }

    public function clear_answer($attemptId, $questionId)
    {
        $this->db->where(['attempt_id'=>(int)$attemptId,'question_id'=>(int)$questionId])->update('content_exam_attempt_answers',['option_id'=>0,'selected_option_ids'=>'[]','is_correct'=>0,'marks_awarded'=>0]);
    }

    public function finalize_attempt($attempt_id)
    {
        $attempt = $this->get_attempt((int)$attempt_id);
        if (!$attempt) return null;
        if (!empty($attempt['submitted_at'])) return $attempt;
        $exam = $this->get_exam((int)$attempt['exam_id']);
        $questions = $this->get_attempt_questions($attempt_id, true);
        $answers = $this->db->get_where('content_exam_attempt_answers',['attempt_id'=>(int)$attempt_id])->result_array();
        $map = []; foreach ($answers as $a) $map[(int)$a['question_id']] = $a;
        $correct = 0; $incorrect = 0; $attempted = 0; $skipped = 0; $score = 0; $weak = [];
        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            if (isset($map[$qid])) {
                $selectedIds = json_decode((string)($map[$qid]['selected_option_ids'] ?? '[]'), true);
                if (!is_array($selectedIds)) $selectedIds = [];
                $selectedIds = array_filter(array_map('intval', $selectedIds));
                if (empty($selectedIds)) {
                    $skipped++;
                    if (!empty($q['topic'])) $weak[] = $q['topic'];
                    continue;
                }
                $attempted++;
                $score += (float)$map[$qid]['marks_awarded'];
                if ((int)$map[$qid]['is_correct'] === 1) {
                    $correct++;
                } else {
                    $incorrect++;
                    if (!empty($q['topic'])) $weak[] = $q['topic'];
                }
            } else {
                $skipped++;
                if (!empty($q['topic'])) $weak[] = $q['topic'];
            }
        }
        $totalMarks = (float)($attempt['total_marks'] ?? 0);
        if ($totalMarks <= 0) { foreach ($questions as $q) $totalMarks += (float)$q['marks']; }
        $percentage = $totalMarks > 0 ? round(($score/$totalMarks)*100, 2) : 0;
        $accuracy = $attempted > 0 ? round(($correct/$attempted)*100, 2) : 0;
        $passed = $score >= (float)($exam['passing_marks'] ?? 0) ? 1 : 0;
        $weakCounts = [];
        foreach (array_filter($weak) as $topic) { $weakCounts[$topic] = ($weakCounts[$topic] ?? 0) + 1; }
        arsort($weakCounts);
        $weakTopics = array_keys($weakCounts);
        $suggestion = $this->build_improvement_suggestion($weakTopics);
        $submitted = date('Y-m-d H:i:s');
        $timeTaken = max(0, strtotime($submitted) - strtotime($attempt['started_at']));
        $previous = null;
        if ((int)$attempt['user_id'] > 0) {
            $previous = $this->db->select('percentage')->where('exam_id', (int)$attempt['exam_id'])
                ->where('user_id', (int)$attempt['user_id'])->where('submitted_at IS NOT NULL', null, false)
                ->where('id !=', (int)$attempt_id)->order_by('submitted_at', 'DESC')->limit(1)
                ->get('content_exam_attempts')->row_array();
        }
        $performanceDelta = $previous && $previous['percentage'] !== null ? round($percentage - (float)$previous['percentage'], 2) : null;
        $this->db->where('id',(int)$attempt_id)->update('content_exam_attempts',[
            'submitted_at'=>$submitted,'time_taken_seconds'=>$timeTaken,'score'=>$score,'passed'=>$passed,
            'attempted_questions'=>$attempted,'correct_answers'=>$correct,'incorrect_answers'=>$incorrect,
            'skipped_questions'=>$skipped,
            'marks_obtained'=>$score,'total_marks'=>$totalMarks,'percentage'=>$percentage,
            'accuracy_percentage'=>$accuracy,'performance_delta'=>$performanceDelta,
            'result_status'=>$passed?'pass':'fail','weak_topics'=>json_encode($weakTopics),'improvement_suggestions'=>$suggestion,
        ]);
        $this->rebuild_attempt_insights((int)$attempt_id, $questions, $map);
        return $this->get_attempt($attempt_id);
    }

    public function rebuild_attempt_insights($attemptId, $questions = null, $answerMap = null)
    {
        $attempt = $this->get_attempt((int)$attemptId);
        if (!$attempt || !$this->db->table_exists('content_exam_attempt_insights')) return [];
        if ($questions === null) $questions = $this->get_attempt_questions((int)$attemptId, true);
        if ($answerMap === null) {
            $answers = $this->db->get_where('content_exam_attempt_answers', ['attempt_id'=>(int)$attemptId])->result_array();
            $answerMap = [];
            foreach ($answers as $answer) $answerMap[(int)$answer['question_id']] = $answer;
        }
        $dimensions = [];
        foreach ($questions as $question) {
            $groups = [
                ['section', (string)($question['section_id'] ?? 0), trim((string)($question['section_title'] ?? 'General')) ?: 'General'],
                ['topic', strtolower(trim((string)($question['topic'] ?? 'general'))) ?: 'general', trim((string)($question['topic'] ?? 'General')) ?: 'General'],
                ['difficulty', strtolower(trim((string)($question['difficulty'] ?? 'unspecified'))) ?: 'unspecified', trim((string)($question['difficulty'] ?? 'Unspecified')) ?: 'Unspecified'],
            ];
            $answer = $answerMap[(int)$question['id']] ?? null;
            $selected = $answer ? json_decode((string)($answer['selected_option_ids'] ?? '[]'), true) : [];
            $wasAttempted = is_array($selected) && count(array_filter(array_map('intval', $selected))) > 0;
            foreach ($groups as $group) {
                list($type, $key, $label) = $group;
                $bucketKey = $type . ':' . $key;
                if (!isset($dimensions[$bucketKey])) {
                    $dimensions[$bucketKey] = [
                        'dimension_type'=>$type, 'dimension_key'=>substr($key, 0, 190), 'dimension_label'=>substr($label, 0, 190),
                        'total_questions'=>0, 'attempted_questions'=>0, 'correct_answers'=>0, 'incorrect_answers'=>0,
                        'skipped_questions'=>0, 'marks_obtained'=>0.0, 'total_marks'=>0.0,
                    ];
                }
                $dimensions[$bucketKey]['total_questions']++;
                $dimensions[$bucketKey]['total_marks'] += (float)$question['marks'];
                if (!$wasAttempted) {
                    $dimensions[$bucketKey]['skipped_questions']++;
                } else {
                    $dimensions[$bucketKey]['attempted_questions']++;
                    $dimensions[$bucketKey]['marks_obtained'] += (float)($answer['marks_awarded'] ?? 0);
                    if (!empty($answer['is_correct'])) $dimensions[$bucketKey]['correct_answers']++;
                    else $dimensions[$bucketKey]['incorrect_answers']++;
                }
            }
        }
        $this->db->where('attempt_id', (int)$attemptId)->delete('content_exam_attempt_insights');
        foreach ($dimensions as &$row) {
            $row['attempt_id'] = (int)$attemptId;
            $row['exam_id'] = (int)$attempt['exam_id'];
            $row['user_id'] = (int)$attempt['user_id'];
            $row['accuracy_percentage'] = $row['attempted_questions'] > 0 ? round(($row['correct_answers'] / $row['attempted_questions']) * 100, 2) : 0;
            $row['score_percentage'] = $row['total_marks'] > 0 ? round(($row['marks_obtained'] / $row['total_marks']) * 100, 2) : 0;
            $row['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('content_exam_attempt_insights', $row);
        }
        unset($row);
        return array_values($dimensions);
    }

    public function get_attempt_insights($attemptId)
    {
        if (!$this->db->table_exists('content_exam_attempt_insights')) return [];
        $rows = $this->db->where('attempt_id', (int)$attemptId)
            ->order_by('dimension_type', 'ASC')->order_by('score_percentage', 'ASC')
            ->get('content_exam_attempt_insights')->result_array();
        if (empty($rows)) $rows = $this->rebuild_attempt_insights((int)$attemptId);
        $grouped = ['section'=>[], 'topic'=>[], 'difficulty'=>[]];
        foreach ($rows as $row) $grouped[$row['dimension_type']][] = $row;
        return $grouped;
    }

    public function get_attempt_comparison($attemptId)
    {
        $attempt = $this->get_attempt((int)$attemptId);
        if (!$attempt) return [];
        $previous = null;
        if ((int)$attempt['user_id'] > 0) {
            $previous = $this->db->where('exam_id', (int)$attempt['exam_id'])
                ->where('user_id', (int)$attempt['user_id'])->where('submitted_at IS NOT NULL', null, false)
                ->where('submitted_at <', $attempt['submitted_at'])->order_by('submitted_at', 'DESC')->limit(1)
                ->get('content_exam_attempts')->row_array();
        }
        $cohort = $this->db->select('COUNT(*) attempts, AVG(percentage) average_percentage')
            ->where('exam_id', (int)$attempt['exam_id'])->where('submitted_at IS NOT NULL', null, false)
            ->where('percentage IS NOT NULL', null, false)->get('content_exam_attempts')->row_array();
        return [
            'previous'=>$previous,
            'delta'=>$previous && $previous['percentage'] !== null ? round((float)$attempt['percentage'] - (float)$previous['percentage'], 2) : null,
            'cohort_average'=>round((float)($cohort['average_percentage'] ?? 0), 2),
            'cohort_attempts'=>(int)($cohort['attempts'] ?? 0),
        ];
    }

    private function build_improvement_suggestion($weakTopics)
    {
        if (empty($weakTopics)) {
            return 'Good attempt. Keep practicing to improve speed and accuracy.';
        }
        $top = array_slice($weakTopics, 0, 5);
        return 'You need more practice in: '.implode(', ', $top).'. Revise these topics, read related Lvalues books/articles if available, then attempt this exam again in practice mode.';
    }

    public function get_saved_option_ids($attempt_id, $question_id)
    {
        $row = $this->db->select('selected_option_ids, option_id')->get_where('content_exam_attempt_answers',['attempt_id'=>(int)$attempt_id,'question_id'=>(int)$question_id])->row_array();
        if (!$row) return [];
        $ids = json_decode((string)($row['selected_option_ids'] ?? '[]'), true);
        if (!is_array($ids) || empty($ids)) $ids = [(int)$row['option_id']];
        return array_map('intval', $ids);
    }

    public function get_saved_option_id($attempt_id, $question_id)
    {
        $ids = $this->get_saved_option_ids($attempt_id, $question_id);
        return !empty($ids) ? (int)$ids[0] : 0;
    }

    public function get_attempt_report($attempt_id)
    {
        $attempt = $this->get_attempt((int)$attempt_id);
        if (!$attempt) return ['items'=>[]];
        $exam = $this->get_exam((int)$attempt['exam_id']);
        $questions = $this->get_attempt_questions($attempt_id, true);
        $items = [];
        foreach ($questions as $q) {
            $selected = $this->get_saved_option_ids($attempt_id, (int)$q['id']);
            $your = []; $correct = [];
            foreach ($q['options'] as $o) {
                if (in_array((int)$o['id'], $selected, true)) $your[] = $o['option_text'];
                if ((int)$o['is_correct'] === 1) $correct[] = $o['option_text'];
            }
            $ans = $this->db->get_where('content_exam_attempt_answers',['attempt_id'=>(int)$attempt_id,'question_id'=>(int)$q['id']])->row_array();
            $items[] = ['question'=>$q['question_text'],'marks'=>(float)$q['marks'],'marks_awarded'=>(float)($ans['marks_awarded'] ?? 0),'is_correct'=>!empty($ans)&&((int)$ans['is_correct']===1),'your_answer'=>implode(', ',$your),'correct_answer'=>implode(', ',$correct),'explanation'=>$q['explanation'] ?? '','topic'=>$q['topic'] ?? '','section'=>$q['section_title'] ?? 'General','difficulty'=>$q['difficulty'] ?? ''];
        }
        return ['items'=>$items, 'insights'=>$this->get_attempt_insights((int)$attempt_id), 'comparison'=>$this->get_attempt_comparison((int)$attempt_id)];
    }

    public function get_attempts_for_tutor($tutor_id, $exam_id = 0)
    {
        $this->db->select('a.*, e.title AS exam_title');
        $this->db->from('content_exam_attempts a');
        $this->db->join('content_exams e','e.id=a.exam_id');
        $this->db->where('e.tutor_id',(int)$tutor_id);
        if ((int)$exam_id > 0) $this->db->where('a.exam_id',(int)$exam_id);
        $this->db->order_by('a.created_at','DESC');
        return $this->db->get()->result_array();
    }


    public function get_tutor_analytics($tutor_id)
    {
        $tutor_id = (int)$tutor_id;
        $summary = $this->db->query("SELECT COUNT(DISTINCT e.id) total_exams, COUNT(DISTINCT a.id) total_attempts, ROUND(AVG(a.percentage),2) avg_score, ROUND(AVG(a.time_taken_seconds),0) avg_time_seconds, ROUND(SUM(CASE WHEN a.result_status='pass' THEN 1 ELSE 0 END)*100/NULLIF(COUNT(a.id),0),2) pass_percentage FROM content_exams e LEFT JOIN content_exam_attempts a ON a.exam_id=e.id AND a.submitted_at IS NOT NULL WHERE e.tutor_id=?", [$tutor_id])->row_array();
        $popular = $this->db->query("SELECT e.id,e.title,COUNT(a.id) attempts FROM content_exams e LEFT JOIN content_exam_attempts a ON a.exam_id=e.id AND a.submitted_at IS NOT NULL WHERE e.tutor_id=? GROUP BY e.id ORDER BY attempts DESC LIMIT 1", [$tutor_id])->row_array();
        $missed = $this->db->query("SELECT q.id,q.question_text,COUNT(aa.id) missed_count FROM content_exam_questions q JOIN content_exams e ON e.id=q.exam_id LEFT JOIN content_exam_attempt_answers aa ON aa.question_id=q.id AND aa.is_correct=0 WHERE e.tutor_id=? GROUP BY q.id ORDER BY missed_count DESC LIMIT 10", [$tutor_id])->result_array();
        $topics = $this->db->query("SELECT q.topic,COUNT(aa.id) attempts,ROUND(SUM(CASE WHEN aa.is_correct=1 THEN 1 ELSE 0 END)*100/NULLIF(COUNT(aa.id),0),2) accuracy FROM content_exam_questions q JOIN content_exams e ON e.id=q.exam_id JOIN content_exam_attempt_answers aa ON aa.question_id=q.id WHERE e.tutor_id=? AND q.topic IS NOT NULL AND q.topic<>'' GROUP BY q.topic ORDER BY accuracy ASC, attempts DESC", [$tutor_id])->result_array();
        return ['summary'=>$summary ?: [], 'popular'=>$popular ?: [], 'missed'=>$missed, 'topics'=>$topics];
    }

    public function get_admin_analytics()
    {
        $summary = $this->db->query("SELECT COUNT(*) total_exams, SUM(CASE WHEN status='published' THEN 1 ELSE 0 END) published_exams FROM content_exams")->row_array();
        $attempts = $this->db->query("SELECT COUNT(*) total_attempts FROM content_exam_attempts WHERE submitted_at IS NOT NULL")->row_array();
        $popular = $this->db->query("SELECT e.id,e.title,CONCAT(IFNULL(u.first_name,''),' ',IFNULL(u.last_name,'')) tutor_name,COUNT(a.id) attempts,ROUND(AVG(a.percentage),2) avg_score FROM content_exams e LEFT JOIN users u ON u.id=e.tutor_id LEFT JOIN content_exam_attempts a ON a.exam_id=e.id AND a.submitted_at IS NOT NULL GROUP BY e.id ORDER BY attempts DESC LIMIT 10")->result_array();
        $tutors = $this->db->query("SELECT e.tutor_id,CONCAT(IFNULL(u.first_name,''),' ',IFNULL(u.last_name,'')) tutor_name,COUNT(DISTINCT e.id) exams,COUNT(a.id) attempts,ROUND(AVG(a.percentage),2) avg_score FROM content_exams e LEFT JOIN users u ON u.id=e.tutor_id LEFT JOIN content_exam_attempts a ON a.exam_id=e.id AND a.submitted_at IS NOT NULL GROUP BY e.tutor_id ORDER BY attempts DESC")->result_array();
        return ['summary'=>array_merge($summary ?: [], $attempts ?: []), 'popular'=>$popular, 'tutors'=>$tutors];
    }

    public function get_exam_analytics_dashboard($examId = 0)
    {
        $where = $examId > 0 ? ' AND a.exam_id='.(int)$examId : '';
        $summary = $this->db->query("SELECT COUNT(*) attempts,
            COUNT(DISTINCT NULLIF(a.user_id,0)) students,
            ROUND(AVG(a.percentage),2) average_score,
            ROUND(AVG(a.accuracy_percentage),2) average_accuracy,
            ROUND(AVG(a.time_taken_seconds),0) average_time,
            ROUND(SUM(CASE WHEN a.result_status='pass' THEN 1 ELSE 0 END)*100/NULLIF(COUNT(*),0),2) pass_rate
            FROM content_exam_attempts a WHERE a.submitted_at IS NOT NULL {$where}")->row_array();
        $modes = $this->db->query("SELECT a.attempt_mode,COUNT(*) attempts,ROUND(AVG(a.percentage),2) average_score,
            ROUND(AVG(a.accuracy_percentage),2) average_accuracy
            FROM content_exam_attempts a WHERE a.submitted_at IS NOT NULL {$where}
            GROUP BY a.attempt_mode ORDER BY attempts DESC")->result_array();
        $topics = $this->db->query("SELECT i.dimension_label topic,SUM(i.total_questions) questions,
            SUM(i.attempted_questions) attempted,SUM(i.correct_answers) correct_answers,
            ROUND(SUM(i.correct_answers)*100/NULLIF(SUM(i.attempted_questions),0),2) accuracy,
            ROUND(SUM(i.marks_obtained)*100/NULLIF(SUM(i.total_marks),0),2) score_percentage
            FROM content_exam_attempt_insights i
            WHERE i.dimension_type='topic' ".($examId > 0 ? 'AND i.exam_id='.(int)$examId : '')."
            GROUP BY i.dimension_key,i.dimension_label ORDER BY accuracy ASC,questions DESC LIMIT 25")->result_array();
        $sections = $this->db->query("SELECT i.dimension_label section_name,SUM(i.total_questions) questions,
            SUM(i.correct_answers) correct_answers,SUM(i.attempted_questions) attempted,
            ROUND(SUM(i.correct_answers)*100/NULLIF(SUM(i.attempted_questions),0),2) accuracy,
            ROUND(SUM(i.marks_obtained)*100/NULLIF(SUM(i.total_marks),0),2) score_percentage
            FROM content_exam_attempt_insights i
            WHERE i.dimension_type='section' ".($examId > 0 ? 'AND i.exam_id='.(int)$examId : '')."
            GROUP BY i.dimension_key,i.dimension_label ORDER BY score_percentage ASC")->result_array();
        $recent = $this->db->select('a.*, e.title exam_title')->from('content_exam_attempts a')
            ->join('content_exams e', 'e.id=a.exam_id', 'left')->where('a.submitted_at IS NOT NULL', null, false);
        if ($examId > 0) $recent->where('a.exam_id', (int)$examId);
        $recent = $recent->order_by('a.submitted_at', 'DESC')->limit(50)->get()->result_array();
        return ['summary'=>$summary ?: [], 'modes'=>$modes, 'topics'=>$topics, 'sections'=>$sections, 'recent'=>$recent];
    }

    public function get_student_attempts($user_id)
    {
        return $this->db->select('a.*, e.title AS exam_title, e.slug')
            ->from('content_exam_attempts a')
            ->join('content_exams e','e.id=a.exam_id','left')
            ->where('a.user_id',(int)$user_id)
            ->order_by('a.created_at','DESC')->get()->result_array();
    }

    public function get_student_exam_analytics($userId)
    {
        $userId = (int)$userId;
        $summary = $this->db->query("SELECT COUNT(*) attempts,
            COUNT(DISTINCT exam_id) exams_attempted,
            ROUND(AVG(percentage),2) average_score,
            MAX(percentage) best_score,
            ROUND(AVG(accuracy_percentage),2) average_accuracy,
            ROUND(SUM(CASE WHEN result_status='pass' THEN 1 ELSE 0 END)*100/NULLIF(COUNT(*),0),2) pass_rate
            FROM content_exam_attempts WHERE user_id=? AND submitted_at IS NOT NULL", [$userId])->row_array();
        $trend = $this->db->select('a.id,a.exam_id,a.attempt_mode,a.percentage,a.accuracy_percentage,a.performance_delta,a.submitted_at,e.title exam_title')
            ->from('content_exam_attempts a')->join('content_exams e','e.id=a.exam_id','left')
            ->where('a.user_id',$userId)->where('a.submitted_at IS NOT NULL', null, false)
            ->order_by('a.submitted_at','ASC')->limit(30)->get()->result_array();
        $topics = [];
        if ($this->db->table_exists('content_exam_attempt_insights')) {
            $topics = $this->db->query("SELECT dimension_label topic,SUM(total_questions) questions,
                SUM(attempted_questions) attempted,SUM(correct_answers) correct_answers,
                ROUND(SUM(correct_answers)*100/NULLIF(SUM(attempted_questions),0),2) accuracy,
                ROUND(SUM(marks_obtained)*100/NULLIF(SUM(total_marks),0),2) score_percentage
                FROM content_exam_attempt_insights WHERE user_id=? AND dimension_type='topic'
                GROUP BY dimension_key,dimension_label ORDER BY accuracy ASC,questions DESC LIMIT 10", [$userId])->result_array();
        }
        return ['summary'=>$summary ?: [], 'trend'=>$trend, 'topics'=>$topics];
    }

    public function validate_import_row($row)
    {
        $errors = [];
        $required = ['question_text','question_type','option_a','option_b','correct_answers','marks'];
        foreach ($required as $col) if (trim((string)($row[$col] ?? '')) === '') $errors[] = $col.' is required';
        $type = strtolower(trim((string)($row['question_type'] ?? '')));
        if (!in_array($type, ['single_choice','multiple_choice','single','multiple'], true)) $errors[] = 'Invalid question_type';
        if (!is_numeric($row['marks'] ?? null)) $errors[] = 'marks must be numeric';
        $difficulty = strtolower(trim((string)($row['difficulty'] ?? 'beginner')));
        if ($difficulty !== '' && !in_array($difficulty, ['beginner','intermediate','advanced'], true)) $errors[] = 'Invalid difficulty';
        $letters = array_filter(array_map('trim', explode(',', strtoupper((string)($row['correct_answers'] ?? '')))));
        foreach ($letters as $l) if (!in_array($l, ['A','B','C','D','E'], true)) $errors[] = 'correct_answers must use A-E';
        if (($type === 'single_choice' || $type === 'single') && count($letters) > 1) $errors[] = 'single_choice accepts one correct answer only';
        return $errors;
    }

    public function import_questions_from_rows($tutor_id, $exam_id, $rows, $skip_invalid = true)
    {
        $exam = $this->get_exam((int)$exam_id);
        if (!$exam || (int)$exam['tutor_id'] !== (int)$tutor_id) return ['ok'=>false,'message'=>'Exam not found or access denied.'];
        $summary = ['total'=>0,'imported'=>0,'failed'=>0,'duplicates'=>0,'errors'=>[]];
        foreach ($rows as $lineNo => $row) {
            $summary['total']++;
            $errors = $this->validate_import_row($row);
            if (!empty($errors)) {
                $summary['failed']++;
                $summary['errors'][] = ['row'=>$lineNo,'message'=>implode('; ', $errors)];
                if (!$skip_invalid) break;
                continue;
            }
            $questionText = trim((string)$row['question_text']);
            $dup = $this->db->where('exam_id',(int)$exam_id)->where('question_text',$questionText)->count_all_results('content_exam_questions');
            if ($dup > 0) { $summary['duplicates']++; continue; }
            $options = [];
            foreach (['a','b','c','d','e'] as $i=>$letter) $options[$i] = (string)($row['option_'.$letter] ?? '');
            $letters = array_filter(array_map('trim', explode(',', strtoupper((string)$row['correct_answers']))));
            $correct = [];
            foreach ($letters as $l) $correct[] = (string)(ord($l)-65);
            $post = [
                'question_text'=>$questionText,
                'question_type'=>$this->normalize_question_type($row['question_type']),
                'options'=>$options,
                'correct_options'=>$correct,
                'marks'=>(float)$row['marks'],
                'explanation'=>(string)($row['explanation'] ?? ''),
                'topic'=>(string)($row['topic'] ?? ''),
                'difficulty'=>ucfirst(strtolower((string)($row['difficulty'] ?? 'beginner'))),
                'status'=>'active',
            ];
            $res = $this->save_question($tutor_id, $exam_id, $post, 0);
            if (!empty($res['ok'])) $summary['imported']++; else { $summary['failed']++; $summary['errors'][]=['row'=>$lineNo,'message'=>$res['message'] ?? 'Unknown error']; }
        }
        $summary['ok'] = true;
        return $summary;
    }

    public function save_exam_with_questions($node_id, $exam_data, $questions)
    {
        // Backward-compatible admin content-node builder. It now stores into the same enhanced tables.
        $tutor_id = (int)($exam_data['created_by'] ?? 0);
        $submitForReview = !empty($exam_data['is_published']);
        $exam_data['status'] = 'draft';
        $exam_data['instructions'] = $exam_data['policy'] ?? '';
        $exam_data['total_marks'] = array_sum(array_map(function($q){ return (float)($q['marks'] ?? 1); }, $questions));
        $exam_data['question_limit'] = max(1, count($questions));
        $existing = $this->get_exam_by_node((int)$node_id, false);
        $res = $this->save_exam($tutor_id, $exam_data, $existing ? (int)$existing['id'] : 0);
        if (empty($res['ok'])) return 0;
        $exam_id = (int)$res['exam_id'];
        $this->db->where('id',$exam_id)->update('content_exams',['node_id'=>(int)$node_id]);
        $old = $this->db->get_where('content_exam_questions',['exam_id'=>$exam_id])->result_array();
        foreach ($old as $q) $this->db->delete('content_exam_options',['question_id'=>(int)$q['id']]);
        $this->db->delete('content_exam_questions',['exam_id'=>$exam_id]);
        foreach ($questions as $q) {
            $post = ['question_text'=>$q['text'] ?? '', 'question_type'=>'single', 'marks'=>$q['marks'] ?? 1, 'options'=>$q['options'] ?? [], 'correct_options'=>[(string)($q['correct_index'] ?? 0)], 'status'=>'active'];
            $this->save_question($tutor_id, $exam_id, $post, 0);
        }
        $this->db->where('id',$exam_id)->update('content_exams',[
            'status'=>'draft',
            'review_status'=>$submitForReview?'in_review':'draft',
            'is_published'=>0,
            'submitted_for_review_at'=>$submitForReview?date('Y-m-d H:i:s'):null,
            'updated_at'=>date('Y-m-d H:i:s'),
        ]);
        return $exam_id;
    }
}
