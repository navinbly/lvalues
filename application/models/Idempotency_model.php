<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Idempotency_model extends CI_Model
{
    public function key(string $scope, array $payload, string $provided = ''): string
    {
        $provided = trim($provided ?: (string)$this->input->get_request_header('X-Idempotency-Key', true));
        if ($provided !== '') return substr(preg_replace('/[^a-zA-Z0-9_.:-]/', '', $provided), 0, 128);
        return hash('sha256', $scope . '|' . date('Y-m-d') . '|' . json_encode($payload));
    }

    public function claim(string $scope, string $key, int $actor_id, array $payload): array
    {
        $request_hash = hash('sha256', json_encode($payload));
        $this->db->query(
            "INSERT IGNORE INTO operation_idempotency_keys
                (operation_scope,idempotency_key,actor_user_id,request_hash,status,expires_at)
             VALUES (?,?,?,?, 'in_progress', ?)",
            array($scope, $key, $actor_id ?: null, $request_hash, date('Y-m-d H:i:s', strtotime('+30 days')))
        );
        if ($this->db->affected_rows() === 1) return array('proceed' => true, 'id' => (int)$this->db->insert_id());

        $existing = $this->db->get_where('operation_idempotency_keys', array(
            'operation_scope' => $scope,
            'idempotency_key' => $key,
        ), 1)->row_array();
        if (!$existing) return array('proceed' => false, 'conflict' => true, 'response' => array('status' => false, 'message' => 'The operation could not acquire an idempotency key.'));
        if (!hash_equals((string)$existing['request_hash'], $request_hash)) {
            return array('proceed' => false, 'conflict' => true, 'response' => array('status' => false, 'message' => 'Idempotency key was already used with different data.'));
        }
        return array('proceed' => false, 'conflict' => false, 'response' => json_decode((string)$existing['response_json'], true) ?: array('status' => true, 'duplicate' => true));
    }

    public function complete(int $id, array $response, string $resource_type = '', $resource_id = ''): void
    {
        $this->db->where('id', $id)->update('operation_idempotency_keys', array(
            'status' => 'completed',
            'resource_type' => $resource_type ?: null,
            'resource_id' => $resource_id === '' ? null : (string)$resource_id,
            'response_json' => json_encode($response),
            'completed_at' => date('Y-m-d H:i:s'),
        ));
    }
}
