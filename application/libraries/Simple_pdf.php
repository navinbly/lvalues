<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Simple_pdf
{
    private $pages = [];
    private $current_lines = [];
    private $max_lines = 48;

    public function add_title(string $text): void
    {
        $this->add_line($text);
        $this->add_line(str_repeat('-', min(70, max(10, strlen($text)))));
    }

    public function add_heading(string $text): void
    {
        $this->add_line('');
        $this->add_line($text);
    }

    public function add_line(string $text = ''): void
    {
        foreach ($this->wrap_text($text, 92) as $line) {
            if (count($this->current_lines) >= $this->max_lines) {
                $this->commit_page();
            }
            $this->current_lines[] = $line;
        }
    }

    public function output(string $filename): void
    {
        $this->commit_page();
        $pdf = $this->build_pdf();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $this->sanitize_filename($filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function commit_page(): void
    {
        if (!empty($this->current_lines) || empty($this->pages)) {
            $this->pages[] = $this->current_lines;
            $this->current_lines = [];
        }
    }

    private function build_pdf(): string
    {
        $objects = [];
        $pages_kids = [];
        $object_id = 1;

        $catalog_id = $object_id++;
        $pages_id = $object_id++;
        $font_id = $object_id++;

        $objects[$font_id] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($this->pages as $page_lines) {
            $page_id = $object_id++;
            $content_id = $object_id++;
            $pages_kids[] = $page_id . ' 0 R';

            $content = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($page_lines as $line) {
                $content .= '(' . $this->pdf_escape($line) . ") Tj\nT*\n";
            }
            $content .= "ET";

            $objects[$content_id] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
            $objects[$page_id] = '<< /Type /Page /Parent ' . $pages_id . ' 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 ' . $font_id . ' 0 R >> >> /Contents ' . $content_id . ' 0 R >>';
        }

        $objects[$pages_id] = '<< /Type /Pages /Kids [' . implode(' ', $pages_kids) . '] /Count ' . count($pages_kids) . ' >>';
        $objects[$catalog_id] = '<< /Type /Catalog /Pages ' . $pages_id . ' 0 R >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref_offset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalog_id . " 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n%%EOF";

        return $pdf;
    }

    private function wrap_text(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if ($text === '') {
            return [''];
        }
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function pdf_escape(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function sanitize_filename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
        return $filename ?: 'progress_report.pdf';
    }
}
