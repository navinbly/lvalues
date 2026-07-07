<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['hardening_upload_max_bytes'] = 10 * 1024 * 1024;
$config['hardening_malware_scanner'] = getenv('LVALUES_MALWARE_SCANNER') ?: '';
$config['hardening_require_malware_scanner'] = in_array(ENVIRONMENT, array('staging', 'production'), true);
$config['hardening_csp_report_only'] = getenv('LVALUES_CSP_REPORT_ONLY') === '1';
$config['hardening_csp_report_uri'] = getenv('LVALUES_CSP_REPORT_URI') ?: '';
