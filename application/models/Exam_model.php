<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exam_model
 * Simple MCQ exam engine linked to content node_id.
 *
 * Tables required (create once via SQL):
 *  - content_exams
 *  - content_exam_questions
 *  - content_exam_options
 *  - content_exam_attempts
 *  - content_exam_attempt_answers
 */
class Exam_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // ---------------- Exams ----------------
    public function get_exam_by_node($node_id, $only_published = false)
    {
        $this->db->from('content_exams');
        $this->db->where('node_id', (int)$node_id);
        if ($only_published) {
            $this->db->where('is_published', 1);
        }
        return $this->db->get()->row_array();
    }

    public function get_exam($exam_id)
    {
        return $this->db->get_where('content_exams', ['id' => (int)$exam_id])->row_array();
    }

    public function save_exam_with_questions($node_id, $exam_data, $questions)
    {
        $node_id = (int)$node_id;
        $existing = $this->get_exam_by_node($node_id, false);

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $exam_id = (int)$existing['id'];
            $exam_data['updated_at'] = $now;
            $this->db->where('id', $exam_id)->update('content_exams', $exam_data);

            // wipe old questions/options (simple and reliable)
            $old_qs = $this->db->get_where('content_exam_questions', ['exam_id' => $exam_id])->result_array();
            foreach ($old_qs as $q) {
                $this->db->delete('content_exam_options', ['question_id' => (int)$q['id']]);
            }
            $this->db->delete('content_exam_questions', ['exam_id' => $exam_id]);
        } else {
            $exam_data['node_id'] = $node_id;
            $exam_data['created_at'] = $now;
            $exam_data['updated_at'] = $now;
            $this->db->insert('content_exams', $exam_data);
            $exam_id = (int)$this->db->insert_id();
        }

        // insert new questions/options
        $sort = 1;
        foreach ($questions as $q) {
            $qrow = [
                'exam_id' => $exam_id,
                'question_text' => $q['text'],
                'marks' => (float)$q['marks'],
                'sort_order' => $sort++,
            ];
            $this->db->insert('content_exam_questions', $qrow);
            $qid = (int)$this->db->insert_id();

            foreach ($q['options'] as $idx => $optText) {
                if (trim($optText) === '') continue;
                $this->db->insert('content_exam_options', [
                    'question_id' => $qid,
                    'option_text' => $optText,
                    'is_correct' => ((int)$q['correct_index'] === (int)$idx) ? 1 : 0,
                ]);
            }
        }

        return $exam_id;
    }

    public function get_questions_with_options($exam_id)
    {
        $qs = $this->db->order_by('sort_order', 'ASC')
            ->get_where('content_exam_questions', ['exam_id' => (int)$exam_id])
            ->result_array();

        foreach ($qs as &$q) {
            $q['options'] = $this->db->get_where('content_exam_options', ['question_id' => (int)$q['id']])->result_array();
        }
        unset($q);
        return $qs;
    }

    // ---------------- Attempts ----------------
    public function create_attempt($exam_id, $user_id)
    {
        $this->db->insert('content_exam_attempts', [
            'exam_id' => (int)$exam_id,
            'user_id' => (int)$user_id,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    public function get_attempt($attempt_id)
    {
        return $this->db->get_where('content_exam_attempts', ['id' => (int)$attempt_id])->row_array();
    }

    public function save_answer($attempt_id, $question_id, $option_id)
    {
        $attempt_id = (int)$attempt_id;
        $question_id = (int)$question_id;
        $option_id = (int)$option_id;

        $opt = $this->db->get_where('content_exam_options', ['id' => $option_id, 'question_id' => $question_id])->row_array();
        $is_correct = $opt ? (int)$opt['is_correct'] : 0;
        $q = $this->db->get_where('content_exam_questions', ['id' => $question_id])->row_array();
        $marks_awarded = ($is_correct && $q) ? (float)$q['marks'] : 0.0;

        $existing = $this->db->get_where('content_exam_attempt_answers', [
            'attempt_id' => $attempt_id,
            'question_id' => $question_id,
        ])->row_array();

        $row = [
            'attempt_id' => $attempt_id,
            'question_id' => $question_id,
            'option_id' => $option_id,
            'is_correct' => $is_correct,
            'marks_awarded' => $marks_awarded,
        ];

        if ($existing) {
            $this->db->where('id', (int)$existing['id'])->update('content_exam_attempt_answers', $row);
        } else {
            $this->db->insert('content_exam_attempt_answers', $row);
        }
    }

    public function finalize_attempt($attempt_id)
    {
        $attempt = $this->get_attempt($attempt_id);
        if (!$attempt) return null;

        $exam = $this->get_exam((int)$attempt['exam_id']);
        $score = (float)$this->db->select_sum('marks_awarded')
            ->get_where('content_exam_attempt_answers', ['attempt_id' => (int)$attempt_id])
            ->row()->marks_awarded;
        if (!$score) $score = 0.0;

        $passed = 0;
        if ($exam && isset($exam['passing_marks'])) {
            $passed = ($score >= (float)$exam['passing_marks']) ? 1 : 0;
        }

        $this->db->where('id', (int)$attempt_id)->update('content_exam_attempts', [
            'submitted_at' => date('Y-m-d H:i:s'),
            'score' => $score,
            'passed' => $passed,
        ]);

        return ['score' => $score, 'passed' => $passed];
    }

    public function get_attempt_answers_map($attempt_id)
    {
        $rows = $this->db->get_where('content_exam_attempt_answers', ['attempt_id' => (int)$attempt_id])->result_array();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['question_id']] = $r;
        }
        return $map;
    }
	
	    /**
     * Return previously saved option_id for a question in an attempt (0 if not answered).
     */
    public function get_saved_option_id($attempt_id, $question_id)
    {
        $row = $this->db
            ->select('option_id')
            ->get_where('content_exam_attempt_answers', [
                'attempt_id' => (int)$attempt_id,
                'question_id' => (int)$question_id,
            ])
            ->row_array();

        return !empty($row['option_id']) ? (int)$row['option_id'] : 0;
    }

    /**
     * Build a detailed report for the result screen:
     * - question text
     * - marks, marks_awarded
     * - your answer text (if any)
     * - correct answer text
     * - correctness
     */
    public function get_attempt_report($attempt_id)
    {
        $attempt_id = (int)$attempt_id;
        $attempt = $this->get_attempt($attempt_id);
        if (!$attempt) {
            return ['items' => []];
        }

        $exam_id = (int)$attempt['exam_id'];

        // One query report (better performance than many small queries)
        $rows = $this->db->query("
            SELECT
                q.id AS question_id,
                q.question_text,
                q.marks,
                IFNULL(a.marks_awarded, 0) AS marks_awarded,
                IFNULL(a.is_correct, 0) AS is_correct,
                your_opt.option_text AS your_answer,
                correct_opt.option_text AS correct_answer
            FROM content_exam_questions q
            LEFT JOIN content_exam_attempt_answers a
                ON a.question_id = q.id AND a.attempt_id = ?
            LEFT JOIN content_exam_options your_opt
                ON your_opt.id = a.option_id
            LEFT JOIN content_exam_options correct_opt
                ON correct_opt.question_id = q.id AND correct_opt.is_correct = 1
            WHERE q.exam_id = ?
            ORDER BY q.sort_order ASC
        ", [$attempt_id, $exam_id])->result_array();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'question' => $r['question_text'],
                'marks' => (float)$r['marks'],
                'marks_awarded' => (float)$r['marks_awarded'],
                'is_correct' => ((int)$r['is_correct'] === 1),
                'your_answer' => $r['your_answer'] ?? '',
                'correct_answer' => $r['correct_answer'] ?? '',
            ];
        }

        return ['items' => $items];
    }

	
	
}
