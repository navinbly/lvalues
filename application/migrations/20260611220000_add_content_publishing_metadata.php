<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_content_publishing_metadata extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('content_node_pages')) {
            return;
        }
        if (!$this->db->field_exists('content_category', 'content_node_pages')) {
            $this->db->query("ALTER TABLE content_node_pages ADD COLUMN content_category VARCHAR(120) NULL AFTER meta_keywords");
        }
        if (!$this->db->field_exists('content_tags', 'content_node_pages')) {
            $this->db->query("ALTER TABLE content_node_pages ADD COLUMN content_tags VARCHAR(500) NULL AFTER content_category");
        }
        if (!$this->index_exists('content_node_pages', 'idx_content_pages_category_status')) {
            $this->db->query("CREATE INDEX idx_content_pages_category_status ON content_node_pages(content_category, status)");
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('content_node_pages')) {
            return;
        }
        if ($this->index_exists('content_node_pages', 'idx_content_pages_category_status')) {
            $this->db->query("DROP INDEX idx_content_pages_category_status ON content_node_pages");
        }
        if ($this->db->field_exists('content_tags', 'content_node_pages')) {
            $this->db->query("ALTER TABLE content_node_pages DROP COLUMN content_tags");
        }
        if ($this->db->field_exists('content_category', 'content_node_pages')) {
            $this->db->query("ALTER TABLE content_node_pages DROP COLUMN content_category");
        }
    }

    private function index_exists($table, $index)
    {
        $query = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            array($table, $index)
        );
        return $query && $query->num_rows() > 0;
    }
}
