<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Security_headers
{
    public function apply()
    {
        $CI =& get_instance();
        $CI->config->load('hardening');

        $CI->output->set_header('X-Frame-Options: SAMEORIGIN');
        $CI->output->set_header('X-Content-Type-Options: nosniff');
        $CI->output->set_header('Referrer-Policy: strict-origin-when-cross-origin');
        $CI->output->set_header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()');
        $CI->output->set_header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        $CI->output->set_header('X-Permitted-Cross-Domain-Policies: none');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $policy = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://www.google.com https://www.gstatic.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
            . "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
            . "img-src 'self' data: blob: https:; media-src 'self' blob: https:; "
            . "connect-src 'self' https: wss:; frame-src 'self' https://www.youtube.com https://www.google.com https://player.vimeo.com";
        if ($https && ENVIRONMENT === 'production') $policy .= '; upgrade-insecure-requests';
        $report_uri = trim((string)$CI->config->item('hardening_csp_report_uri'));
        if ($report_uri !== '') $policy .= '; report-uri ' . $report_uri;
        $header = $CI->config->item('hardening_csp_report_only') ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ';
        $CI->output->set_header($header . $policy);

        if ($https && ENVIRONMENT === 'production') {
            $CI->output->set_header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        }
    }
}
