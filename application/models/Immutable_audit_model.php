<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Immutable_audit_model extends CI_Model
{
    public function record(string $event_type, string $action, string $entity_type, $entity_id, array $before = [], array $after = [], array $metadata = []): int
    {
        if (!$this->db->table_exists('immutable_audit_logs')) return 0;

        $this->db->trans_start();
        $actor_id = (int)($metadata['actor_user_id'] ?? $this->session->userdata('user_id') ?? 0);
        $actor_role = (string)($metadata['actor_role'] ?? ($this->session->userdata('admin_login') ? 'admin' : ($this->session->userdata('role') ?: 'system')));
        unset($metadata['actor_user_id'], $metadata['actor_role']);

        $head = $this->db->query('SELECT head_hash FROM immutable_audit_chain_head WHERE id=1 FOR UPDATE')->row_array();
        $previous = $head['head_hash'] ?? null;
        $created_at = date('Y-m-d H:i:s.') . sprintf('%06d', (int)((microtime(true) - floor(microtime(true))) * 1000000));
        $event_uuid = $this->uuid();
        $request_id = (string)($this->input->get_request_header('X-Request-ID', true) ?: $this->input->get_request_header('X-Correlation-ID', true) ?: $event_uuid);
        $payload = array(
            'event_uuid' => $event_uuid,
            'event_type' => $event_type,
            'action' => $action,
            'actor_user_id' => $actor_id ?: null,
            'actor_role' => $actor_role,
            'entity_type' => $entity_type,
            'entity_id' => (string)$entity_id,
            'before_json' => $this->json($before),
            'after_json' => $this->json($after),
            'metadata_json' => $this->json($metadata),
            'request_id' => substr($request_id, 0, 100),
            'ip_hash' => hash('sha256', (string)$this->input->ip_address()),
            'user_agent_hash' => hash('sha256', (string)$this->input->user_agent()),
            'previous_hash' => $previous ?: null,
            'created_at' => $created_at,
        );
        $payload['event_hash'] = hash('sha256', ($previous ?: '') . '|' . $this->json($payload));
        $this->db->insert('immutable_audit_logs', $payload);
        $id = (int)$this->db->insert_id();
        $this->db->where('id', 1)->set('event_count', 'event_count + 1', false)->update('immutable_audit_chain_head', array(
            'head_hash' => $payload['event_hash'],
            'updated_at' => $created_at,
        ));
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : 0;
    }

    public function verify_chain(): array
    {
        $rows = $this->db->order_by('id', 'ASC')->get('immutable_audit_logs')->result_array();
        $previous = '';
        foreach ($rows as $row) {
            $stored = $row['event_hash'];
            unset($row['id'], $row['event_hash']);
            $expected = hash('sha256', $previous . '|' . $this->json($row));
            if (!hash_equals($stored, $expected) || (string)$row['previous_hash'] !== $previous) {
                return array('ok' => false, 'failed_id' => $row['event_uuid']);
            }
            $previous = $stored;
        }
        return array('ok' => true, 'count' => count($rows), 'head_hash' => $previous);
    }

    private function json($value): string
    {
        return (string)json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
