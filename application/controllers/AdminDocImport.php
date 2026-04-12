<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AdminDocImport extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('upload');

        // Prevent CI profiler from injecting HTML
        if (method_exists($this->output, 'enable_profiler')) {
            $this->output->enable_profiler(false);
        }
    }

    public function upload_docx()
    {
        // Buffer absolutely everything from this point
        ob_start();

		// ✅ IMPORTANT: never print notices/warnings into JSON response
		@ini_set('display_errors', '0');
		@ini_set('log_errors', '1');  // keep logging on
		error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);


        // Convert PHP warnings/notices into exceptions so they don't echo HTML
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->_json(false, null, 'Invalid request method (POST required).');
            }

            // ✅ Load PHPWord after buffering
			$candidates = [
				APPPATH . 'third_party/src/PhpWord/Autoloader.php',
				APPPATH . 'third_party/phpword/src/PhpWord/Autoloader.php',
				APPPATH . 'third_party/phpword/src/PhpOffice/PhpWord/Autoloader.php',
				APPPATH . 'third_party/PHPWord/src/PhpOffice/PhpWord/Autoloader.php',
			];

			$autoloadPath = null;
			foreach ($candidates as $p) {
				if (file_exists($p)) { $autoloadPath = $p; break; }
			}
			if (!$autoloadPath) {
				return $this->_json(false, null, 'PHPWord Autoloader not found. Checked: ' . implode(' | ', $candidates));
			}

			require_once $autoloadPath;
			\PhpOffice\PhpWord\Autoloader::register();


            // Ensure class exists
            if (!class_exists('PhpOffice\\PhpWord\\IOFactory')) {
                return $this->_json(false, null, 'PHPWord IOFactory not available (autoload issue).');
            }

            if (empty($_FILES['docx_file']) || empty($_FILES['docx_file']['name'])) {
                return $this->_json(false, null, 'Please choose a DOCX file.');
            }

            $ext = strtolower(pathinfo($_FILES['docx_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'docx') {
                return $this->_json(false, null, 'Only .docx files are allowed.');
            }

            $uploadDir = FCPATH . 'uploads/doc_import/';
            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                return $this->_json(false, null, 'Cannot create uploads/doc_import (permission issue).');
            }

            $config = [
                'upload_path'   => $uploadDir,
                'allowed_types' => 'docx',
                'max_size'      => 10240, // 10MB
                'encrypt_name'  => true,
            ];
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('docx_file')) {
                return $this->_json(false, null, strip_tags($this->upload->display_errors()));
            }

            $up = $this->upload->data();
            $docxPath = $up['full_path'];

            $imgRelDir = 'uploads/ckeditor_images/docimport_' . date('Ymd_His') . '_' . substr(md5($docxPath), 0, 6) . '/';
            $imgAbsDir = FCPATH . $imgRelDir;

            if (!is_dir($imgAbsDir) && !@mkdir($imgAbsDir, 0755, true)) {
                return $this->_json(false, null, 'Cannot create uploads/ckeditor_images (permission issue).');
            }

            // ✅ Use PHPWord fully-qualified names (no "use" needed)
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
            $writer  = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');

            if (method_exists($writer, 'setImagesRoot'))   $writer->setImagesRoot($imgAbsDir);
            if (method_exists($writer, 'setImagesFolder')) $writer->setImagesFolder($imgAbsDir);

            $tmpHtml = $uploadDir . 'tmp_' . basename($docxPath) . '.html';
            $writer->save($tmpHtml);

            $html = @file_get_contents($tmpHtml);
            if ($html === false || trim($html) === '') {
                return $this->_json(false, null, 'DOCX conversion produced empty HTML.');
            }

            $html = $this->_extract_body_html($html);
            $html = $this->_rewrite_img_src($html, base_url($imgRelDir));

            @unlink($tmpHtml);

            return $this->_json(true, ['html' => $html], null);

        } catch (Throwable $e) {
            // Log the real exception server-side
            log_message('error', 'DOCX import exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->_json(false, null, 'Server error during DOCX import. Check application/logs.');
        } finally {
            restore_error_handler();
        }
    }

    private function _json($ok, $payload = null, $error = null)
    {
        // Grab + log any stray output (warnings/HTML)
        $buffer = ob_get_clean();
        if (!empty($buffer)) {
            log_message('error', "DOCX import stray output:\n" . $buffer);
        }

        $resp = ['ok' => (bool)$ok];
        if ($ok && is_array($payload)) $resp = array_merge($resp, $payload);
        if (!$ok) $resp['error'] = $error ?: 'Unknown error';

        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function _extract_body_html($html)
    {
        if (preg_match('~<body[^>]*>(.*)</body>~is', $html, $m)) {
            return trim($m[1]);
        }
        return $html;
    }

    private function _rewrite_img_src($html, $imgBaseUrl)
    {
        return preg_replace_callback(
            '~<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>~i',
            function ($m) use ($imgBaseUrl) {
                $before = $m[1];
                $src = $m[2];
                $after = $m[3];

                if (preg_match('~^(https?:|data:)~i', $src)) {
                    return "<img{$before}src=\"{$src}\"{$after}>";
                }

                $src = ltrim($src, '/');
                $newSrc = rtrim($imgBaseUrl, '/') . '/' . $src;
                return "<img{$before}src=\"{$newSrc}\"{$after}>";
            },
            $html
        );
    }
}
