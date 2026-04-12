<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'third_party/phpword/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\IOFactory;

class TestPhpWord extends CI_Controller
{
    public function index()
    {
        echo "PHPWord autoloader OK ✅<br>";

        // Just verify IOFactory exists
        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            echo "IOFactory exists ✅";
        } else {
            echo "IOFactory NOT found ❌";
        }
    }
}
