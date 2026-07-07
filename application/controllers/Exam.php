<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper(['url','form','security']);
        $this->load->model('Exam_model', 'exam');
    }

    public function public_list()
    {
        $exams = $this->exam->get_public_exams();
        $site_name = get_settings('system_name') ?: 'Lvalues EdTech';
        $meta_description = 'Practice free mock tests for banking, government, school, IT and professional exams with instant result, feedback and weak area reports on Lvalues EdTech.';
        $page_data = [
            'page_name' => 'exam/public_exams',
            'page_title' => 'Free Mock Tests and Public Exams',
            'exams' => $exams,
            'canonical_url' => site_url('mock-tests'),
            'seo_title_override' => 'Free Mock Tests & Competitive Exams | ' . $site_name,
            'seo_description_override' => $meta_description,
            'seo_canonical_override' => site_url('mock-tests'),
            'seo_type_override' => 'website',
            'seo_json_ld_override' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => 'Free Mock Tests and Public Exams',
                'description' => $meta_description,
                'url' => site_url('mock-tests'),
                'mainEntity' => array_map(function($exam) {
                    return [
                        '@type' => 'Course',
                        'name' => (string)($exam['title'] ?? ''),
                        'description' => substr(trim(strip_tags((string)($exam['description'] ?? ''))), 0, 150),
                        'url' => site_url('mock-tests/' . ($exam['slug'] ?? '')),
                        'provider' => ['@type' => 'Organization', 'name' => 'Lvalues EdTech'],
                    ];
                }, array_slice($exams ?? [], 0, 10)),
            ],
        ];
        $this->load->view('frontend/default-new/index', $page_data);
    }

    public function detail($slug = '')
    {
        $exam = $this->exam->get_exam_by_slug($slug, true);
        if (!$exam) { show_404(); return; }
        $site_name = get_settings('system_name') ?: 'Lvalues EdTech';
        $title = trim((string)($exam['title'] ?? 'Mock Test'));
        $meta_description = trim(strip_tags((string)($exam['description'] ?? '')));
        if ($meta_description === '') $meta_description = 'Attempt the ' . $title . ' mock test online with timer, instant result, feedback and weak area report on Lvalues EdTech.';
        if (strlen($meta_description) > 155) $meta_description = substr($meta_description, 0, 152) . '...';
        $canonical = site_url('mock-tests/'.$exam['slug']);
        $page_data = [
            'page_name' => 'exam/exam_intro',
            'page_title' => $title,
            'exam' => $exam,
            'canonical_url' => $canonical,
            'seo_title_override' => 'Free ' . $title . ' Mock Test | ' . $site_name,
            'seo_description_override' => $meta_description,
            'seo_canonical_override' => $canonical,
            'seo_type_override' => 'article',
            'seo_json_ld_override' => [
                '@context' => 'https://schema.org',
                '@type' => 'Course',
                'name' => $title,
                'description' => $meta_description,
                'url' => $canonical,
                'provider' => ['@type' => 'Organization', 'name' => $site_name, 'url' => site_url()],
            ],
        ];
        $this->load->view('frontend/default-new/index', $page_data);
    }

    public function intro($node_id = 0)
    {
        $exam = $this->exam->get_exam_by_node((int)$node_id, true);
        if (!$exam) { show_404(); return; }
        $page_data = ['page_title'=>$exam['title'], 'exam'=>$exam, 'node_id'=>(int)$node_id];
        $this->load->view('frontend/default-new/exam/exam_intro', $page_data);
    }

    public function start($identifier = '')
    {
        $exam = is_numeric($identifier) ? $this->exam->get_exam_by_node((int)$identifier, true) : $this->exam->get_exam_by_slug($identifier, true);
        if (!$exam) { show_404(); return; }
        if ($this->input->method(true) !== 'POST') { redirect(site_url('mock-tests/'.$exam['slug']), 'refresh'); return; }
        $name = trim((string)$this->input->post('participant_name'));
        $email = trim((string)$this->input->post('participant_email'));
        $mobile = trim((string)$this->input->post('participant_mobile'));
        if ($name === '' || mb_strlen($name) > 120 || mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error_message', 'Enter a valid name and email address to start the exam.');
            redirect(site_url('mock-tests/'.$exam['slug']), 'refresh'); return;
        }
        if (mb_strlen($mobile) > 30) {
            $this->session->set_flashdata('error_message', 'Mobile number must be 30 characters or fewer.');
            redirect(site_url('mock-tests/'.$exam['slug']), 'refresh'); return;
        }
        if (!$this->allow_attempt_start((int)$exam['id'])) {
            $this->session->set_flashdata('error_message', 'Too many exam starts were requested. Please wait a few minutes and try again.');
            redirect(site_url('mock-tests/'.$exam['slug']), 'refresh'); return;
        }
        $requestedMode = (string)($this->input->post('attempt_mode') ?: ($exam['exam_mode'] ?? 'mock'));
        $created = $this->exam->create_attempt((int)$exam['id'], [
            'name'=>$name,
            'email'=>$email,
            'mobile'=>$mobile,
            'user_id'=>(int)$this->session->userdata('user_id'),
        ], $requestedMode);
        if (empty($created['attempt_id']) || empty($created['access_token'])) {
            $this->session->set_flashdata('error_message',$created['message']??'This exam has no active questions available for an attempt.');
            redirect(site_url('mock-tests/'.$exam['slug']),'refresh');
            return;
        }
        $this->remember_attempt_token((int)$created['attempt_id'], (string)$created['access_token']);
        redirect($this->attempt_question_url((int)$created['attempt_id'], (string)$created['access_token'], 0), 'refresh');
    }

    private function allow_attempt_start($examId)
    {
        $now = time();
        $windowStart = $now - 600;
        $attemptStarts = $this->session->userdata('exam_attempt_starts');
        if (!is_array($attemptStarts)) $attemptStarts = [];
        $key = (string)(int)$examId;
        $recent = array_values(array_filter($attemptStarts[$key] ?? [], function($timestamp) use ($windowStart) {
            return (int)$timestamp >= $windowStart;
        }));
        if (count($recent) >= 10) {
            $attemptStarts[$key] = $recent;
            $this->session->set_userdata('exam_attempt_starts', $attemptStarts);
            return false;
        }
        $recent[] = $now;
        $attemptStarts[$key] = $recent;
        $this->session->set_userdata('exam_attempt_starts', $attemptStarts);
        return true;
    }

    public function question($attempt_id = 0, $index = 0, $token = '')
    {
        $attempt = $this->exam->get_attempt((int)$attempt_id);
        if (!$attempt) { show_404(); return; }
        $token = $this->resolve_attempt_token((int)$attempt_id, (string)$token);
        if (!$this->exam->can_access_attempt($attempt, $token, (int)$this->session->userdata('user_id'))) {
            show_error('You do not have access to this exam attempt.', 403);
            return;
        }
        if ($token !== '') $this->remember_attempt_token((int)$attempt_id, $token);
        if (!empty($attempt['submitted_at'])) { redirect($this->attempt_result_url((int)$attempt_id, $token), 'refresh'); return; }
        $exam = $this->exam->get_exam((int)$attempt['exam_id']);
        if (!$exam) { show_404(); return; }
        if ($this->exam->is_attempt_expired($attempt)) {
            $this->exam->finalize_attempt((int)$attempt_id);
            redirect($this->attempt_result_url((int)$attempt_id, $token), 'refresh'); return;
        }
        $questions = $this->exam->get_attempt_questions((int)$attempt_id, false);
        $total = count($questions);
        if ($total === 0) { show_error('This exam has no active questions.'); return; }
        $index = max(0, min((int)$index, $total-1));
        $feedback = null;
        if ($this->input->method(true) === 'POST') {
            $qid = (int)$this->input->post('question_id');
            $selected = $this->input->post('option_ids');
            $action = (string)$this->input->post('nav_action');
            if ($action === 'clear') {
                $this->exam->clear_answer((int)$attempt_id,$qid);
                redirect($this->attempt_question_url((int)$attempt_id,$token,$index),'refresh'); return;
            }
            if ($action === 'review') {
                if ($qid > 0) $this->exam->save_answer((int)$attempt_id,$qid,is_array($selected)?$selected:[]);
                $this->exam->mark_for_review((int)$attempt_id,$qid,true);
                $nextIndex=min($total-1,$index+1);
                redirect($this->attempt_question_url((int)$attempt_id,$token,$nextIndex),'refresh'); return;
            }
            if ($qid > 0) $feedback = $this->exam->save_answer((int)$attempt_id, $qid, is_array($selected) ? $selected : []);
            if ($action === 'submit') { $this->exam->finalize_attempt((int)$attempt_id); redirect($this->attempt_result_url((int)$attempt_id, $token), 'refresh'); return; }
            if ($action === 'check' && !empty($attempt['allow_feedback_during_attempt'])) {
                $this->exam->update_attempt_progress((int)$attempt_id, $index, true);
            } elseif ($action === 'back') {
                $nextIndex = max(0, $index-1);
                $this->exam->update_attempt_progress((int)$attempt_id, $nextIndex, true);
                redirect($this->attempt_question_url((int)$attempt_id, $token, $nextIndex), 'refresh'); return;
            } else {
                $nextIndex = min($total-1, $index+1);
                $this->exam->update_attempt_progress((int)$attempt_id, $nextIndex, true);
                redirect($this->attempt_question_url((int)$attempt_id, $token, $nextIndex), 'refresh'); return;
            }
        }
        $q = $questions[$index];
        $picked = $this->exam->get_saved_option_ids((int)$attempt_id, (int)$q['id']);
        $secondsLeft = !empty($attempt['is_timed']) && !empty($attempt['expires_at']) ? max(0, strtotime($attempt['expires_at']) - time()) : 0;
        $page_data = [
            'page_title'=>$exam['title'],
            'exam'=>$exam,
            'attempt'=>$attempt,
            'question'=>$q,
            'questions'=>$questions,
            'answered_question_ids'=>$this->exam->get_answered_question_ids((int)$attempt_id),
            'review_question_ids'=>$this->exam->get_review_question_ids((int)$attempt_id),
            'index'=>$index,
            'total'=>$total,
            'picked_option_ids'=>$picked,
            'seconds_left'=>$secondsLeft,
            'is_timed'=>!empty($attempt['is_timed']),
            'is_practice'=>($attempt['attempt_mode'] ?? '') === 'practice',
            'feedback'=>$feedback,
            'question_url_base'=>$this->attempt_question_url((int)$attempt_id, $token, ''),
            'autosave_url'=>$token !== '' ? site_url('exam/attempt/'.(int)$attempt_id.'/'.$token.'/autosave') : '',
        ];
        $this->load->view('frontend/default-new/exam/exam_question', $page_data);
    }

    public function autosave($attempt_id = 0, $token = '')
    {
        $attempt = $this->exam->get_attempt((int)$attempt_id);
        $token = $this->resolve_attempt_token((int)$attempt_id, (string)$token);
        if (!$attempt || !$this->exam->can_access_attempt($attempt, $token, (int)$this->session->userdata('user_id'))) {
            return $this->json_response(['ok'=>false, 'message'=>'Access denied.'], 403);
        }
        if ($this->input->method(true) !== 'POST' || !empty($attempt['submitted_at']) || $this->exam->is_attempt_expired($attempt)) {
            return $this->json_response(['ok'=>false, 'message'=>'Attempt is no longer active.'], 409);
        }
        $questionId = (int)$this->input->post('question_id');
        $selected = $this->input->post('option_ids');
        $index = max(0, (int)$this->input->post('question_index'));
        $saved = $this->exam->save_answer((int)$attempt_id, $questionId, is_array($selected) ? $selected : []);
        $this->exam->update_attempt_progress((int)$attempt_id, $index, true);
        if (empty($attempt['allow_feedback_during_attempt']) && is_array($saved)) {
            unset($saved['is_correct'], $saved['marks_awarded'], $saved['correct_option_ids'], $saved['explanation']);
        }
        return $this->json_response(array_merge(['ok'=>!empty($saved['ok']), 'saved_at'=>date('H:i:s')], is_array($saved) ? $saved : []));
    }

    public function result($attempt_id = 0, $token = '')
    {
        $attempt = $this->exam->get_attempt((int)$attempt_id);
        if (!$attempt) { show_404(); return; }
        $token = $this->resolve_attempt_token((int)$attempt_id, (string)$token);
        if (!$this->exam->can_access_attempt($attempt, $token, (int)$this->session->userdata('user_id'))) {
            show_error('You do not have access to this exam result.', 403);
            return;
        }
        if (empty($attempt['submitted_at'])) $attempt = $this->exam->finalize_attempt((int)$attempt_id);
        $exam = $this->exam->get_exam((int)$attempt['exam_id']);
        $report = $this->exam->get_attempt_report((int)$attempt_id);
        $this->load->view('frontend/default-new/exam/exam_result', ['page_title'=>$exam['title'].' - Result', 'exam'=>$exam, 'attempt'=>$attempt, 'report'=>$report]);
    }

    public function by_path($path = '', $slug = '')
    {
        $this->load->model('Content_docs_model', 'content_docs');
        $path = trim(urldecode((string)$path), "/ \t\n\r\0\x0B");
        $slug = trim(urldecode((string)$slug));
        $candidates = [];
        if ($path !== '' && $slug !== '') $candidates[] = $path . '/' . $slug . '-exam';
        if ($path !== '') $candidates[] = $path;
        if ($path !== '' && $slug !== '' && substr($path, -strlen('/'.$slug)) !== '/'.$slug) $candidates[] = $path . '/' . $slug;
        foreach (array_values(array_unique(array_filter($candidates))) as $full_path) {
            $node = $this->content_docs->get_node_by_full_path($full_path);
            if (!empty($node['node_id'])) {
                $exam = $this->exam->get_exam_by_node((int)$node['node_id'], true);
                if (!empty($exam['id'])) { redirect(site_url('exam/' . (int)$node['node_id']), 'refresh'); return; }
            }
        }
        show_404();
    }

    public function by_pretty()
    {
        $args = func_get_args();
        $before_exam = trim(urldecode(implode('/', $args)), "/ \t\n\r\0\x0B");
        if ($before_exam === '') { show_404(); return; }
        $parts = explode('/', $before_exam); $base_slug = array_pop($parts); $path = implode('/', $parts);
        $this->by_path($path, $base_slug);
    }

    private function remember_attempt_token($attemptId, $token)
    {
        if ($token === '') return;
        $tokens = $this->session->userdata('exam_attempt_tokens');
        if (!is_array($tokens)) $tokens = [];
        $tokens[(int)$attemptId] = $token;
        if (count($tokens) > 20) $tokens = array_slice($tokens, -20, null, true);
        $this->session->set_userdata('exam_attempt_tokens', $tokens);
    }

    private function resolve_attempt_token($attemptId, $token)
    {
        if ($token !== '') return $token;
        $tokens = $this->session->userdata('exam_attempt_tokens');
        return is_array($tokens) && !empty($tokens[(int)$attemptId]) ? (string)$tokens[(int)$attemptId] : '';
    }

    private function attempt_question_url($attemptId, $token, $index)
    {
        if ($token !== '') return site_url('exam/attempt/'.(int)$attemptId.'/'.$token.'/q/'.$index);
        return site_url('exam/attempt/'.(int)$attemptId.'/q/'.$index);
    }

    private function attempt_result_url($attemptId, $token)
    {
        return $token !== '' ? site_url('exam/result/'.(int)$attemptId.'/'.$token) : site_url('exam/result/'.(int)$attemptId);
    }

    private function json_response($payload, $status = 200)
    {
        $this->output->set_status_header((int)$status);
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($payload));
    }
}
