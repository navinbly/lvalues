<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

$hook['post_controller_constructor'][] = array(
	'class'    => 'Security_headers',
	'function' => 'apply',
	'filename' => 'Security_headers.php',
	'filepath' => 'hooks',
	'params'   => array()
);

$hook['post_controller_constructor'][] = array(
	'class'    => 'Upload_guard',
	'function' => 'validate',
	'filename' => 'Upload_guard.php',
	'filepath' => 'hooks',
	'params'   => array()
);

$hook['post_controller'][] = array(
    'class'    => 'Secure_forms',
    'function' => 'inject',
    'filename' => 'Secure_forms.php',
    'filepath' => 'hooks',
    'params'   => array()
);
