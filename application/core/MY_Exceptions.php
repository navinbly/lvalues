<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions
{
    public function show_exception($exception)
    {
        $reference = $this->reference_id();
        log_message('error', sprintf('[%s] Unhandled %s: %s in %s:%d%s%s',
            $reference, get_class($exception), $exception->getMessage(),
            $exception->getFile(), $exception->getLine(), PHP_EOL, $exception->getTraceAsString()
        ));
        if (is_cli()) return parent::show_exception($exception);
        set_status_header(500);
        echo $this->generic_response($reference);
    }

    public function show_php_error($severity, $message, $filepath, $line)
    {
        $reference = $this->reference_id();
        log_message('error', sprintf('[%s] PHP %s: %s in %s:%d', $reference, $severity, $message, $filepath, $line));
        if (is_cli()) return parent::show_php_error($severity, $message, $filepath, $line);
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int)$severity, $fatal, true)) return;
        set_status_header(500);
        echo $this->generic_response($reference);
    }

    private function reference_id()
    {
        try {
            return strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        } catch (Exception $exception) {
            return strtoupper(substr(sha1(uniqid('', true)), 0, 12));
        }
    }

    private function generic_response($reference)
    {
        return '<!doctype html><html><head><meta charset="utf-8"><title>Application error</title></head>'
            . '<body><main style="max-width:680px;margin:80px auto;font-family:Arial,sans-serif">'
            . '<h1>Something went wrong</h1><p>The error was logged. Please try again.</p>'
            . '<p><small>Reference: ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</small></p></main></body></html>';
    }
}
