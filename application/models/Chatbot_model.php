<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Chatbot_model extends CI_Model
{
    private $log_table = 'chatbot_logs';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_log_table();
    }

    public function answer_question(string $question, string $visitor_ip): array
    {
        $question = $this->clean_text($question);
        $match = $this->find_best_faq_match($question);

        $answer_found = !empty($match);
        $answer = $answer_found
            ? $this->clean_answer($match['answer'])
            : 'Sorry, I could not find an answer to that question.';

        $matched_faq_id = $answer_found ? (int) $match['id'] : null;

        $this->log_interaction($question, $matched_faq_id, $answer_found, $visitor_ip);

        return array(
            'answer_found' => $answer_found,
            'answer' => $answer,
            'matched_faq_id' => $matched_faq_id,
            'fallback_actions' => $answer_found ? array() : $this->fallback_actions(),
        );
    }

    public function is_request_allowed(string $visitor_ip): bool
    {
        if (!$this->db->table_exists($this->log_table)) {
            return true;
        }

        $recent_count = $this->db
            ->where('visitor_ip', $visitor_ip)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 60))
            ->count_all_results($this->log_table);

        return $recent_count < 20;
    }

    private function find_best_faq_match(string $question): array
    {
        $faqs = $this->get_website_faqs();
        $normalized_question = $this->normalize($question);
        $question_tokens = $this->tokens($normalized_question);

        $best = array('score' => 0, 'faq' => array());

        foreach ($faqs as $faq) {
            $faq_question = (string) ($faq['question'] ?? '');
            $normalized_faq = $this->normalize($faq_question);
            if ($normalized_faq === '') {
                continue;
            }

            $score = 0;
            if ($normalized_question === $normalized_faq) {
                $score = 1000;
            } elseif (strpos($normalized_faq, $normalized_question) !== false || strpos($normalized_question, $normalized_faq) !== false) {
                $score = 700;
            }

            $faq_tokens = $this->tokens($normalized_faq);
            $overlap = array_intersect($question_tokens, $faq_tokens);
            if (!empty($overlap)) {
                $score += (count($overlap) * 80) + (count($overlap) / max(count($faq_tokens), 1) * 120);
            }

            similar_text($normalized_question, $normalized_faq, $percent);
            $score += $percent;

            if ($score > $best['score']) {
                $best = array('score' => $score, 'faq' => $faq);
            }
        }

        return $best['score'] >= 160 ? $best['faq'] : array();
    }

    private function get_website_faqs(): array
    {
        $row = $this->db
            ->select('value')
            ->where('key', 'website_faqs')
            ->get('frontend_settings', 1)
            ->row_array();

        $decoded = json_decode($row['value'] ?? '[]', true);
        if (!is_array($decoded)) {
            return array();
        }

        $faqs = array();
        foreach ($decoded as $key => $faq) {
            if (!is_array($faq) || empty($faq['question']) || empty($faq['answer'])) {
                continue;
            }
            $faqs[] = array(
                'id' => is_numeric($key) ? (int) $key : count($faqs),
                'question' => (string) $faq['question'],
                'answer' => (string) $faq['answer'],
            );
        }

        return $faqs;
    }

    private function normalize(string $value): string
    {
        $value = strtolower($this->clean_text($value));
        $value = preg_replace('/[^a-z0-9\s]+/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function tokens(string $value): array
    {
        $stop_words = array('the', 'is', 'are', 'a', 'an', 'do', 'does', 'how', 'what', 'can', 'i', 'you', 'to', 'for', 'of', 'in', 'on', 'and', 'or');
        $tokens = array_filter(explode(' ', $value), function ($token) use ($stop_words) {
            return strlen($token) > 2 && !in_array($token, $stop_words, true);
        });
        return array_values(array_unique($tokens));
    }

    private function clean_text(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function clean_answer(string $answer): string
    {
        $answer = htmlspecialchars_decode($answer, ENT_QUOTES);
        return $this->clean_text($answer);
    }

    private function fallback_actions(): array
    {
        $contact_info = json_decode(get_frontend_settings('contact_info'), true);
        $phone = preg_replace('/\D+/', '', (string) ($contact_info['phone'] ?? get_settings('phone')));
        if ($phone === '') {
            $phone = '917420920360';
        }

        return array(
            array('label' => 'Contact Support', 'url' => site_url('home/contact_us')),
            array('label' => 'Send Enquiry', 'url' => site_url('home/contact_us')),
            array('label' => 'WhatsApp Us', 'url' => 'https://wa.me/' . $phone . '?text=' . rawurlencode('Hello Lvalues, I need help with courses.')),
        );
    }

    private function log_interaction(string $question, ?int $matched_faq_id, bool $answer_found, string $visitor_ip): void
    {
        if (!$this->db->table_exists($this->log_table)) {
            return;
        }

        $this->db->insert($this->log_table, array(
            'question' => $question,
            'matched_faq_id' => $matched_faq_id,
            'answer_found' => $answer_found ? 1 : 0,
            'visitor_ip' => $visitor_ip,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    private function ensure_log_table(): void
    {
        if ($this->db->table_exists($this->log_table)) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS chatbot_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            matched_faq_id INT NULL,
            answer_found TINYINT(1) NOT NULL DEFAULT 0,
            visitor_ip VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_chatbot_logs_created_at (created_at),
            KEY idx_chatbot_logs_visitor_ip (visitor_ip),
            KEY idx_chatbot_logs_matched_faq_id (matched_faq_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}