<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Chatbot extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Chatbot_model', 'chatbot_model');
    }

    public function ask()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') {
            return $this->json_response(array('status' => false, 'message' => 'Invalid request.'), 405);
        }

        $question = trim((string) $this->input->post('question', true));
        if ($question === '' || strlen($question) > 500) {
            return $this->json_response(array('status' => false, 'message' => 'Please enter a short question.'), 422);
        }

        $visitor_ip = $this->visitor_ip();

        if (!$this->chatbot_model->is_request_allowed($visitor_ip)) {
            return $this->json_response(array('status' => false, 'message' => 'Please wait a moment before asking another question.'), 429);
        }

        $result = $this->chatbot_model->answer_question($question, $visitor_ip);

        return $this->json_response(array(
            'status' => true,
            'answer_found' => (bool) $result['answer_found'],
            'answer' => $result['answer'],
            'matched_faq_id' => $result['matched_faq_id'],
            'fallback_actions' => $result['fallback_actions'],
        ));
    }

    private function json_response(array $payload, int $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    private function visitor_ip(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
