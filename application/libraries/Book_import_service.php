<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Book_import_service
{
    public function word_to_structure($path, $imageDirectory, $imageBaseUrl)
    {
        $this->load_phpword();
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        if (method_exists($writer, 'setImagesRoot')) $writer->setImagesRoot($imageDirectory);
        if (method_exists($writer, 'setImagesFolder')) $writer->setImagesFolder($imageDirectory);
        $temporary = tempnam(sys_get_temp_dir(), 'lv-book-') . '.html';
        $writer->save($temporary);
        $html = (string)file_get_contents($temporary);
        @unlink($temporary);
        $html = preg_replace_callback('~<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>~i', function($match) use ($imageBaseUrl) {
            $src = $match[2];
            if (!preg_match('~^(?:https?:|data:)~i', $src)) $src = rtrim($imageBaseUrl,'/').'/'.ltrim($src,'/');
            return '<img'.$match[1].'src="'.htmlspecialchars($src, ENT_QUOTES, 'UTF-8').'"'.$match[3].'>';
        }, $html);
        if (preg_match('~<body[^>]*>(.*)</body>~is', $html, $match)) $html = $match[1];
        return $this->split_headings($html);
    }

    private function split_headings($html)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="lv-import-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $dom->getElementById('lv-import-root');
        $chapters = [];
        $chapter = ['title'=>'Chapter 1','pages'=>[]];
        $page = ['title'=>'Introduction','html'=>''];
        foreach (iterator_to_array($root->childNodes) as $node) {
            $tag = strtolower((string)$node->nodeName);
            if ($tag === 'h1') {
                $this->flush_page($chapter, $page);
                $this->flush_chapter($chapters, $chapter);
                $chapter = ['title'=>trim($node->textContent) ?: 'Chapter '.(count($chapters)+1),'pages'=>[]];
                $page = ['title'=>'Introduction','html'=>''];
                continue;
            }
            if ($tag === 'h2') {
                $this->flush_page($chapter, $page);
                $page = ['title'=>trim($node->textContent) ?: 'Page '.(count($chapter['pages'])+1),'html'=>''];
                continue;
            }
            $page['html'] .= $dom->saveHTML($node);
        }
        $this->flush_page($chapter, $page);
        $this->flush_chapter($chapters, $chapter);
        return $chapters;
    }

    private function flush_page(&$chapter, &$page)
    {
        if (trim(strip_tags((string)$page['html'])) !== '' || empty($chapter['pages'])) $chapter['pages'][] = $page;
    }

    private function flush_chapter(&$chapters, &$chapter)
    {
        if (!empty($chapter['pages'])) $chapters[] = $chapter;
    }

    private function load_phpword()
    {
        $candidates = [
            APPPATH.'third_party/src/PhpWord/Autoloader.php',
            APPPATH.'third_party/phpword/src/PhpWord/Autoloader.php',
            APPPATH.'third_party/phpword/src/PhpOffice/PhpWord/Autoloader.php',
            APPPATH.'third_party/PHPWord/src/PhpOffice/PhpWord/Autoloader.php',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                require_once $path;
                \PhpOffice\PhpWord\Autoloader::register();
                return;
            }
        }
        throw new RuntimeException('PHPWord is not installed.');
    }
}
