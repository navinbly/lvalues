<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exam (Frontend)
 * URL examples:
 *  - /exam/{node_id}               -> intro
 *  - /exam/start/{node_id}         -> creates attempt, redirects to q1
 *  - /exam/attempt/{attempt}/q/{i} -> question page (0-based)
 *  - /exam/result/{attempt}        -> result
 */
class Exam extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('Exam_model', 'exam');
    }

    public function intro($node_id = 0)
    {
        $node_id = (int)$node_id;
        $exam = $this->exam->get_exam_by_node($node_id, true);
        if (!$exam) {
            show_404();
            return;
        }

        $page_data = [
            'page_title' => $exam['title'],
            'exam' => $exam,
            'node_id' => $node_id,
        ];
        $this->load->view('frontend/default-new/exam/exam_intro', $page_data);
    }

    public function start($node_id = 0)
    {
        $node_id = (int)$node_id;
        $exam = $this->exam->get_exam_by_node($node_id, true);
        if (!$exam) {
            show_404();
            return;
        }

        // Require login (user)
        $user_id = (int)$this->session->userdata('user_id');
        if ($user_id <= 0) {
            // send back after login
            $this->session->set_userdata('redirect_to', site_url('exam/'.$node_id));
            redirect(site_url('login'), 'refresh');
            return;
        }

        $attempt_id = $this->exam->create_attempt((int)$exam['id'], $user_id);
        redirect(site_url('exam/attempt/'.$attempt_id.'/q/0'), 'refresh');
    }

    public function question($attempt_id = 0, $index = 0)
    {
        $attempt_id = (int)$attempt_id;
        $index = (int)$index;

        $attempt = $this->exam->get_attempt($attempt_id);
        if (!$attempt) {
            show_404();
            return;
        }

        // block viewing someone else's attempt
        $user_id = (int)$this->session->userdata('user_id');
        if ($user_id <= 0 || (int)$attempt['user_id'] !== $user_id) {
            redirect(site_url('login'), 'refresh');
            return;
        }

        $exam = $this->exam->get_exam((int)$attempt['exam_id']);
        $questions = $this->exam->get_questions_with_options((int)$attempt['exam_id']);
        $total = count($questions);
        if ($total === 0) {
            show_error('This exam has no questions yet.');
            return;
        }

        if ($index < 0) $index = 0;
        if ($index >= $total) $index = $total - 1;

        // handle POST (save + nav)
        if ($this->input->method(true) === 'POST') {
            $selected = $this->input->post('option_id');
            $qid = (int)$this->input->post('question_id');
            if ($qid > 0) {
                $this->exam->save_answer($attempt_id, $qid, (int)$selected);
            }

            $action = $this->input->post('nav_action'); // back|next|submit
            $cur = (int)$this->input->post('index');

            if ($action === 'back') {
                redirect(site_url('exam/attempt/'.$attempt_id.'/q/'.max(0, $cur-1)), 'refresh');
                return;
            }
            if ($action === 'next') {
                redirect(site_url('exam/attempt/'.$attempt_id.'/q/'.min($total-1, $cur+1)), 'refresh');
                return;
            }
            if ($action === 'submit') {
                $this->exam->finalize_attempt($attempt_id);
                redirect(site_url('exam/result/'.$attempt_id), 'refresh');
                return;
            }
        }

        $q = $questions[$index];
        $picked = $this->exam->get_saved_option_id($attempt_id, (int)$q['id']);

        $page_data = [
            'page_title' => $exam['title'],
            'exam' => $exam,
            'attempt' => $attempt,
            'question' => $q,
            'index' => $index,
            'total' => $total,
            'picked_option_id' => $picked,
        ];
        $this->load->view('frontend/default-new/exam/exam_question', $page_data);
    }

    public function result($attempt_id = 0)
    {
        $attempt_id = (int)$attempt_id;
        $attempt = $this->exam->get_attempt($attempt_id);
        if (!$attempt) {
            show_404();
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        if ($user_id <= 0 || (int)$attempt['user_id'] !== $user_id) {
            redirect(site_url('login'), 'refresh');
            return;
        }

        $exam = $this->exam->get_exam((int)$attempt['exam_id']);
        $report = $this->exam->get_attempt_report($attempt_id);

        $page_data = [
            'page_title' => $exam['title'].' - Result',
            'exam' => $exam,
            'attempt' => $attempt,
            'report' => $report,
        ];
        $this->load->view('frontend/default-new/exam/exam_result', $page_data);
    }

    public function by_path($path = '', $slug = '')
    {
        // Route: /blog/<any depth>/<slug>-exam  -> /exam/by_path/<path>/<slug>
        // Goal: resolve the correct content node that has a *published* exam attached,
        // even if an "-exam" leaf node exists but the exam is actually attached to the parent node.
        $this->load->model('Content_docs_model', 'content_docs');
        $this->load->model('Exam_model', 'exam');

        $path = trim((string)$path, "/ \t\n\r\0\x0B");
        $slug = trim((string)$slug);

        // Normalize (URLs may be URL-encoded)
        $path = urldecode($path);
        $slug = urldecode($slug);

        // Candidate full_path values to try (in order)
        $candidates = [];
		
		header('Content-Type: text/plain');
		echo "PATH=$path\nSLUG=$slug\n\n";
		echo "Candidates:\n";
		foreach ($candidates as $c) echo " - $c\n";
		echo "\n";

        // 1) Leaf exam node: <path>/<slug>-exam (e.g., gcp/.../gcs/gcs-exam)
        if ($path !== '' && $slug !== '') {
            $candidates[] = $path . '/' . $slug . '-exam';
        }

        // 2) Parent/topic node: <path> (e.g., gcp/.../gcs)
        if ($path !== '') {
            $candidates[] = $path;
        }

        // 3) If $path does not already end with $slug, try <path>/<slug> too
        if ($path !== '' && $slug !== '' && substr($path, -strlen('/'.$slug)) !== '/'.$slug) {
            $candidates[] = $path . '/' . $slug;
        }

        // De-duplicate candidates while preserving order
        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $full_path) {
            $node = $this->content_docs->get_node_by_full_path($full_path);
            if (empty($node) || empty($node['node_id'])) {
                continue;
            }

            $node_id = (int)$node['node_id'];

            // Ensure an exam exists and is published for this node
            $exam = $this->exam->get_exam_by_node($node_id, true);
            if (!empty($exam) && !empty($exam['id'])) {
                redirect(site_url('exam/' . $node_id), 'refresh');
                return;
            }
        }

        // Nothing matched a published exam
        show_404();
    }
	
	//public function by_pretty($before_exam = '')
//{
//    die("HIT by_pretty with: " . $before_exam);
//}

	
	public function by_pretty()
{
    $args = func_get_args();
    $before_exam = trim(urldecode(implode('/', $args)), "/ \t\n\r\0\x0B");

    if ($before_exam === '') {
        show_404();
        return;
    }

    // Example before_exam:
    // cloud/google-cloud-platform/associate-engineer/gcs/gcs
    // where the last segment ("gcs") is the base slug.
    $parts = explode('/', $before_exam);
    $base_slug = array_pop($parts);           // "gcs"
    $path = implode('/', $parts);             // ".../gcs"

    // IMPORTANT:
    // Your actual leaf node is "gcs-exam" (not "gcs" or "gcs/gcs").
    // So pass base slug to by_path which will try "<path>/<base>-exam".
    $this->by_path($path, $base_slug);
}






}
