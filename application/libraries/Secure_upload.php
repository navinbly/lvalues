<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Secure_upload
{
    private $CI;
    private $mime_map = array(
        'jpg' => array('image/jpeg'),
        'jpeg' => array('image/jpeg'),
        'png' => array('image/png'),
        'gif' => array('image/gif'),
        'webp' => array('image/webp'),
        'pdf' => array('application/pdf'),
        'doc' => array('application/msword', 'application/CDFV2', 'application/octet-stream'),
        'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'),
        'json' => array('application/json', 'text/plain'),
        'zip' => array('application/zip', 'application/x-zip-compressed'),
    );

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('hardening');
    }

    public function store(array $file, string $destination, array $options = array()): array
    {
        $actor_id = (int)($options['actor_user_id'] ?? $this->CI->session->userdata('user_id') ?? 0);
        $owner_id = (int)($options['owner_user_id'] ?? $actor_id);
        $is_admin = !empty($options['is_admin']) || $this->CI->session->userdata('admin_login') == true;
        if (!$is_admin && ($actor_id <= 0 || $owner_id !== $actor_id)) {
            return $this->reject($file, $options, 'Upload ownership validation failed.', 'rejected');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->reject($file, $options, 'The uploaded file is invalid.', 'rejected');
        }

        $allowed = array_values(array_intersect(array_keys($this->mime_map), array_map('strtolower', $options['extensions'] ?? array_keys($this->mime_map))));
        $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $allowed, true)) {
            return $this->reject($file, $options, 'File extension is not allowed.', 'rejected');
        }
        $max = (int)($options['max_bytes'] ?? $this->CI->config->item('hardening_upload_max_bytes'));
        $size = (int)($file['size'] ?? filesize($file['tmp_name']));
        if ($size <= 0 || $size > $max) {
            return $this->reject($file, $options, 'File size is outside the allowed limit.', 'rejected');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->mime_map[$extension], true)) {
            return $this->reject($file, $options, 'Detected MIME type does not match the file extension.', 'rejected', $mime);
        }

        $scan = $this->scan($file['tmp_name']);
        if ($scan['status'] !== 'clean') {
            return $this->reject($file, $options, $scan['message'], $scan['status'], $mime);
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            return $this->reject($file, $options, 'Upload directory could not be created.', 'rejected', $mime);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $path = rtrim($destination, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return $this->reject($file, $options, 'Uploaded file could not be stored.', 'rejected', $mime);
        }
        @chmod($path, 0644);
        $this->log($file, $options, $extension, $mime, $size, hash_file('sha256', $path), 'clean', 'accepted', $path, null);
        return array('ok' => true, 'file_name' => $name, 'path' => $path, 'mime' => $mime, 'size' => $size, 'sha256' => hash_file('sha256', $path), 'malware_status' => 'clean');
    }

    private function scan(string $path): array
    {
        $head = (string)file_get_contents($path, false, null, 0, min(filesize($path), 1024 * 1024));
        if (stripos($head, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE') !== false || preg_match('/<\?(?:php|=)/i', $head)) {
            return array('status' => 'rejected', 'message' => 'The file failed malware/content inspection.');
        }
        $scanner = trim((string)$this->CI->config->item('hardening_malware_scanner'));
        if ($scanner === '') {
            return $this->CI->config->item('hardening_require_malware_scanner')
                ? array('status' => 'scanner_unavailable', 'message' => 'The malware scanner is unavailable.')
                : array('status' => 'clean', 'message' => 'Baseline inspection passed.');
        }
        $output = array();
        $code = 1;
        exec($scanner . ' ' . escapeshellarg($path), $output, $code);
        return $code === 0
            ? array('status' => 'clean', 'message' => 'Malware scan passed.')
            : array('status' => 'rejected', 'message' => 'The malware scanner rejected the file.');
    }

    private function reject(array $file, array $options, string $reason, string $malware_status, string $mime = ''): array
    {
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $tmp = (string)($file['tmp_name'] ?? '');
        $this->log($file, $options, $extension, $mime ?: 'unknown', (int)($file['size'] ?? 0), is_file($tmp) ? hash_file('sha256', $tmp) : str_repeat('0', 64), $malware_status, 'rejected', null, $reason);
        return array('ok' => false, 'message' => $reason, 'malware_status' => $malware_status);
    }

    private function log(array $file, array $options, string $extension, string $mime, int $size, string $sha, string $malware, string $status, ?string $path, ?string $reason): void
    {
        if (!$this->CI->db->table_exists('secure_upload_events')) return;
        $this->CI->db->insert('secure_upload_events', array(
            'actor_user_id' => (int)($options['actor_user_id'] ?? $this->CI->session->userdata('user_id') ?? 0) ?: null,
            'owner_user_id' => (int)($options['owner_user_id'] ?? $this->CI->session->userdata('user_id') ?? 0) ?: null,
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => isset($options['entity_id']) ? (string)$options['entity_id'] : null,
            'original_name' => substr((string)($file['name'] ?? 'unknown'), 0, 255),
            'stored_path' => $path,
            'extension' => substr($extension, 0, 20),
            'detected_mime' => substr($mime, 0, 120),
            'file_size' => $size,
            'sha256' => $sha,
            'malware_status' => in_array($malware, array('clean', 'rejected', 'scanner_unavailable'), true) ? $malware : 'rejected',
            'status' => $status,
            'rejection_reason' => $reason,
        ));
    }
}
