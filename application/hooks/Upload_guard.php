<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload_guard
{
    private $mime_map = array(
        'jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'), 'png' => array('image/png'),
        'gif' => array('image/gif'), 'webp' => array('image/webp'), 'svg' => array('image/svg+xml', 'text/plain'),
        'pdf' => array('application/pdf'), 'txt' => array('text/plain'), 'csv' => array('text/plain', 'text/csv', 'application/csv'),
        'json' => array('application/json', 'text/plain'), 'xml' => array('application/xml', 'text/xml', 'text/plain'),
        'doc' => array('application/msword', 'application/CDFV2', 'application/octet-stream'),
        'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
        'xls' => array('application/vnd.ms-excel', 'application/CDFV2', 'application/octet-stream'),
        'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'),
        'ppt' => array('application/vnd.ms-powerpoint', 'application/CDFV2', 'application/octet-stream'),
        'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'),
        'zip' => array('application/zip', 'application/x-zip-compressed'),
        'mp3' => array('audio/mpeg', 'audio/mp3'), 'wav' => array('audio/wav', 'audio/x-wav'),
        'm4a' => array('audio/mp4', 'video/mp4'), 'ogg' => array('audio/ogg', 'video/ogg', 'application/ogg'),
        'mp4' => array('video/mp4', 'application/octet-stream'), 'webm' => array('video/webm'),
        'mov' => array('video/quicktime'), 'avi' => array('video/x-msvideo'),
        'srt' => array('text/plain', 'application/x-subrip'), 'vtt' => array('text/vtt', 'text/plain'),
    );

    public function validate()
    {
        if (empty($_FILES)) return;
        $CI =& get_instance();
        $CI->config->load('hardening');
        $max = (int)(getenv('LVALUES_UPLOAD_MAX_BYTES') ?: 256 * 1024 * 1024);
        foreach ($this->flatten($_FILES) as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                $this->reject($CI, $file, 'Invalid upload request.', 'rejected', 'unknown', 400);
            }
            if ((int)$file['size'] <= 0 || (int)$file['size'] > $max) {
                $this->reject($CI, $file, 'Uploaded file exceeds the platform size limit.', 'rejected', 'unknown', 413);
            }
            $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
            if (!isset($this->mime_map[$extension])) {
                $this->reject($CI, $file, 'Uploaded file extension is not allowed.', 'rejected', 'unknown', 415);
            }
            $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!in_array($mime, $this->mime_map[$extension], true)) {
                $this->reject($CI, $file, 'Uploaded file MIME type does not match its extension.', 'rejected', $mime, 415);
            }
            $head = (string)file_get_contents($file['tmp_name'], false, null, 0, min((int)$file['size'], 1024 * 1024));
            if (stripos($head, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE') !== false || preg_match('/<\?(?:php|=)/i', $head)) {
                $this->reject($CI, $file, 'Uploaded file failed malware/content inspection.', 'rejected', $mime, 422);
            }
            $scanner = trim((string)$CI->config->item('hardening_malware_scanner'));
            if ($scanner === '' && $CI->config->item('hardening_require_malware_scanner')) {
                $this->reject($CI, $file, 'Malware scanning is temporarily unavailable.', 'scanner_unavailable', $mime, 503);
            }
            if ($scanner !== '') {
                $output = array();
                $code = 1;
                exec($scanner . ' ' . escapeshellarg($file['tmp_name']), $output, $code);
                if ($code !== 0) $this->reject($CI, $file, 'Uploaded file was rejected by malware scanning.', 'rejected', $mime, 422);
            }
        }
    }

    private function flatten(array $files): array
    {
        $result = array();
        foreach ($files as $file) {
            if (!is_array($file['name'] ?? null)) {
                $result[] = $file;
                continue;
            }
            foreach (array_keys($file['name']) as $key) {
                $result[] = array(
                    'name' => $file['name'][$key], 'type' => $file['type'][$key] ?? '',
                    'tmp_name' => $file['tmp_name'][$key] ?? '', 'error' => $file['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $file['size'][$key] ?? 0,
                );
            }
        }
        return $result;
    }

    private function reject($CI, array $file, string $reason, string $malware, string $mime, int $status): void
    {
        if (isset($CI->db) && $CI->db->table_exists('secure_upload_events')) {
            $tmp = (string)($file['tmp_name'] ?? '');
            $CI->db->insert('secure_upload_events', array(
                'actor_user_id' => (int)$CI->session->userdata('user_id') ?: null,
                'owner_user_id' => (int)$CI->session->userdata('user_id') ?: null,
                'entity_type' => 'global_upload_guard',
                'original_name' => substr((string)($file['name'] ?? 'unknown'), 0, 255),
                'extension' => substr(strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)), 0, 20),
                'detected_mime' => substr($mime, 0, 120),
                'file_size' => (int)($file['size'] ?? 0),
                'sha256' => is_file($tmp) ? hash_file('sha256', $tmp) : str_repeat('0', 64),
                'malware_status' => $malware,
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ));
        }
        $CI->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode(array('status' => false, 'message' => $reason)))->_display();
        exit;
    }
}
