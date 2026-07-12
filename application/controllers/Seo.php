<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seo extends CI_Controller
{
    private $production_base_url = 'https://lvalues.com';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
    }

    public function sitemap()
    {
        $urls = [];
        $this->add_url($urls, '', '1.0', 'daily');
        $this->add_url($urls, 'home/courses', '0.9', 'daily');
        $this->add_url($urls, 'tutors', '0.8', 'weekly');
        $this->add_url($urls, 'books', '0.9', 'daily');
        $this->add_url($urls, 'mock-tests', '0.9', 'daily');
        $this->add_url($urls, 'blog', '0.8', 'daily');
        $this->add_url($urls, 'blogs', '0.7', 'weekly');
        $this->add_url($urls, 'home/about_us', '0.6', 'monthly');
        $this->add_url($urls, 'home/faq', '0.6', 'monthly');
        $this->add_url($urls, 'home/contact_us', '0.5', 'monthly');
        $this->add_url($urls, 'parents', '0.8', 'monthly');
        $this->add_url($urls, 'tutor-earnings', '0.8', 'monthly');
        $this->add_url($urls, 'tutor-verification', '0.8', 'monthly');
        $this->add_url($urls, 'home/privacy_policy', '0.3', 'yearly');
        $this->add_url($urls, 'home/terms_and_condition', '0.3', 'yearly');
        $this->add_url($urls, 'home/refund_policy', '0.3', 'yearly');

        $this->add_custom_pages($urls);
        $this->add_courses($urls);
        $this->add_legacy_blogs($urls);
        $this->add_content_nodes($urls);
        $this->add_mock_tests($urls);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url) {
            $node = $xml->addChild('url');
            $node->addChild('loc', htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8'));
            $lastmod = $this->format_lastmod($url['lastmod'] ?? null);
            if ($lastmod !== null) {
                $node->addChild('lastmod', $lastmod);
            }
            $node->addChild('changefreq', $url['changefreq']);
            $node->addChild('priority', $url['priority']);
        }

        $this->output
            ->set_content_type('application/xml')
            ->set_output($xml->asXML());
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            'Sitemap: ' . $this->public_url('sitemap.xml'),
            '',
        ];
        $this->output
            ->set_content_type('text/plain')
            ->set_output(implode("\n", $lines));
    }

    private function add_custom_pages(&$urls)
    {
        if (!$this->db->table_exists('custom_page')) return;
        $select = ['page_url'];
        if ($this->has_column('custom_page', 'date_added')) $select[] = 'date_added';
        if ($this->has_column('custom_page', 'last_modified')) $select[] = 'last_modified';
        if ($this->has_column('custom_page', 'updated_at')) $select[] = 'updated_at';
        $rows = $this->db->select(implode(', ', $select))
            ->where('page_url !=', '')
            ->get('custom_page')
            ->result_array();
        foreach ($rows as $row) {
            $this->add_url($urls, 'page/' . $row['page_url'], '0.5', 'monthly', $row['last_modified'] ?? $row['updated_at'] ?? $row['date_added'] ?? null);
        }
    }

    private function add_courses(&$urls)
    {
        if (!$this->db->table_exists('course')) return;
        $select = ['id', 'title'];
        if ($this->has_column('course', 'date_added')) $select[] = 'date_added';
        if ($this->has_column('course', 'last_modified')) $select[] = 'last_modified';
        $this->db->select(implode(', ', $select));
        if ($this->has_column('course', 'status')) {
            $this->db->where('status', 'active');
        }
        if ($this->has_column('course', 'last_modified')) {
            $this->db->order_by('last_modified', 'DESC');
        } else {
            $this->db->order_by('id', 'DESC');
        }
        $rows = $this->db->get('course', 2000)->result_array();
        foreach ($rows as $row) {
            $slug = rawurlencode(slugify($row['title'] ?? 'course'));
            $this->add_url($urls, 'home/course/' . $slug . '/' . (int)$row['id'], '0.8', 'weekly', $row['last_modified'] ?? $row['date_added'] ?? null);
        }
    }

    private function add_legacy_blogs(&$urls)
    {
        if (!$this->db->table_exists('blogs')) return;
        $id_column = $this->has_column('blogs', 'blog_id') ? 'blog_id' : 'id';
        $select = [$id_column, 'title'];
        if ($this->has_column('blogs', 'added_date')) $select[] = 'added_date';
        if ($this->has_column('blogs', 'updated_date')) $select[] = 'updated_date';
        if ($this->has_column('blogs', 'date_added')) $select[] = 'date_added';
        if ($this->has_column('blogs', 'last_modified')) $select[] = 'last_modified';
        $this->db->select(implode(', ', $select));
        if ($this->has_column('blogs', 'status')) {
            $this->db->where('status', 1);
        }
        $this->db->order_by($id_column, 'DESC');
        $rows = $this->db->get('blogs', 1000)->result_array();
        foreach ($rows as $row) {
            $slug = rawurlencode(slugify($row['title'] ?? 'blog'));
            $this->add_url($urls, 'blog/details/' . $slug . '/' . (int)$row[$id_column], '0.7', 'weekly', $row['updated_date'] ?? $row['last_modified'] ?? $row['added_date'] ?? $row['date_added'] ?? null);
        }
    }

    private function add_content_nodes(&$urls)
    {
        if (!$this->db->table_exists('content_nodes')) return;
        $select = ['title', 'slug', 'full_path', 'content_type'];
        foreach (['node_id', 'root_key', 'updated_at', 'approved_at', 'created_at'] as $column) {
            if ($this->has_column('content_nodes', $column)) $select[] = $column;
        }
        $this->db->select(implode(', ', $select));
        if ($this->has_column('content_nodes', 'status')) {
            $this->db->where('status', 'published');
        }
        if ($this->has_column('content_nodes', 'is_deleted')) {
            $this->db->where('COALESCE(is_deleted,0)', 0, false);
        }
        $this->db->where_in('content_type', ['book', 'page', 'article', 'legacy']);
        $rows = $this->db->get('content_nodes', 3000)->result_array();
        foreach ($rows as $row) {
            $type = (string)($row['content_type'] ?? '');
            if ($type === 'book') {
                $path = 'books/' . ($row['slug'] ?: slugify($row['title'] ?? 'book'));
            } else {
                $full_path = trim((string)($row['full_path'] ?? ''), '/');
                if ($full_path === '') continue;
                $path = 'blog/' . $full_path;
            }
            $this->add_url($urls, $path, $type === 'book' ? '0.8' : '0.7', 'weekly', $row['updated_at'] ?? $row['approved_at'] ?? $row['created_at'] ?? null);
        }
    }

    private function add_mock_tests(&$urls)
    {
        if (!$this->db->table_exists('content_exams')) return;
        $select = ['slug', 'title'];
        foreach (['updated_at', 'published_at', 'created_at'] as $column) {
            if ($this->has_column('content_exams', $column)) $select[] = $column;
        }
        $this->db->select(implode(', ', $select));
        if ($this->has_column('content_exams', 'status') && $this->has_column('content_exams', 'is_published')) {
            $this->db->group_start()->where('status', 'published')->or_where('is_published', 1)->group_end();
        } elseif ($this->has_column('content_exams', 'status')) {
            $this->db->where('status', 'published');
        } elseif ($this->has_column('content_exams', 'is_published')) {
            $this->db->where('is_published', 1);
        }
        if ($this->has_column('content_exams', 'visibility')) {
            $this->db->where('visibility', 'public');
        }
        $rows = $this->db->get('content_exams', 1000)->result_array();
        foreach ($rows as $row) {
            if (empty($row['slug'])) continue;
            $this->add_url($urls, 'mock-tests/' . $row['slug'], '0.8', 'weekly', $row['updated_at'] ?? $row['published_at'] ?? $row['created_at'] ?? null);
        }
    }

    private function add_url(&$urls, $path, $priority = '0.5', $changefreq = 'weekly', $lastmod = null)
    {
        $loc = $this->public_url($path);
        $urls[$loc] = [
            'loc' => $loc,
            'priority' => $priority,
            'changefreq' => $changefreq,
            'lastmod' => $lastmod,
        ];
    }

    private function public_url($path = '')
    {
        $base = $this->public_base_url();
        $path = trim((string)$path, '/');
        $path = preg_replace('/[\x00-\x20\x7F]+/', '', $path);
        return $base . ($path !== '' ? '/' . $path : '/');
    }

    private function public_base_url()
    {
        $env = trim((string)getenv('LVALUES_PUBLIC_BASE_URL'));
        if ($env !== '') return rtrim($env, '/');

        $base = rtrim(base_url(), '/');
        $host = strtolower((string)parse_url($base, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return rtrim($this->production_base_url, '/');
        }
        return $base;
    }

    private function has_column($table, $column)
    {
        static $columns = [];
        if (!isset($columns[$table])) {
            $columns[$table] = $this->db->list_fields($table);
        }
        return in_array($column, $columns[$table], true);
    }

    private function format_lastmod($value)
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int)$value;
        } else {
            $timestamp = strtotime((string)$value);
        }

        if ($timestamp === false || $timestamp <= 315532800) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}
