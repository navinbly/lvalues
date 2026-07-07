<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security_login_model
 * --------------------
 * Handles all DB operations for the Suspicious Login / New Device Alert feature.
 *
 * Tables used:
 *   - user_login_history
 *   - trusted_user_devices
 *   - security_alert_logs
 *
 * Design principles:
 *   - Every public method is wrapped in try/catch so a DB error never breaks login.
 *   - Tables are checked for existence before queries (graceful degradation).
 *   - No login-blocking logic here – callers decide what to do with the results.
 */
class Security_login_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('security_login');
    }

    // ═══════════════════════════════════════════════════════════
    // Login History
    // ═══════════════════════════════════════════════════════════

    /**
     * Insert a row into user_login_history.
     *
     * @param  array $data  Keys: user_id, role, email, ip_address, user_agent,
     *                      browser_name, browser_version, os_name, device_type,
     *                      city, state, country, is_suspicious, alert_sent
     * @return int  Inserted row ID, or 0 on failure
     */
    public function record_login(array $data)
    {
        try {
            if (!$this->db->table_exists('user_login_history')) {
                return 0;
            }

            $insert = [
                'user_id'         => (int)($data['user_id']         ?? 0),
                'role'            => (string)($data['role']          ?? 'student'),
                'email'           => (string)($data['email']         ?? ''),
                'ip_address'      => (string)($data['ip_address']    ?? ''),
                'user_agent'      => (string)($data['user_agent']    ?? ''),
                'browser_name'    => (string)($data['browser_name']  ?? ''),
                'browser_version' => (string)($data['browser_version'] ?? ''),
                'os_name'         => (string)($data['os_name']       ?? ''),
                'device_type'     => (string)($data['device_type']   ?? 'unknown'),
                'city'            => $data['city']    ?? null,
                'state'           => $data['state']   ?? null,
                'country'         => $data['country'] ?? null,
                'is_suspicious'   => (int)($data['is_suspicious'] ?? 0),
                'alert_sent'      => (int)($data['alert_sent']    ?? 0),
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('user_login_history', $insert);
            return (int)$this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::record_login failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark a login history row as alert_sent = 1.
     *
     * @param int $history_id
     */
    public function mark_alert_sent(int $history_id)
    {
        try {
            if (!$this->db->table_exists('user_login_history') || $history_id <= 0) {
                return;
            }
            $this->db->where('id', $history_id)->update('user_login_history', ['alert_sent' => 1]);
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::mark_alert_sent failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark a login history row as suspicious.
     *
     * @param int $history_id
     */
    public function mark_suspicious(int $history_id)
    {
        try {
            if (!$this->db->table_exists('user_login_history') || $history_id <= 0) {
                return;
            }
            $this->db->where('id', $history_id)->update('user_login_history', ['is_suspicious' => 1]);
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::mark_suspicious failed: ' . $e->getMessage());
        }
    }

    /**
     * Get paginated login history for a specific user.
     *
     * @param  int $user_id
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function get_login_history_for_user(int $user_id, int $limit = 20, int $offset = 0)
    {
        try {
            if (!$this->db->table_exists('user_login_history')) {
                return [];
            }
            return $this->db
                ->where('user_id', $user_id)
                ->order_by('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get('user_login_history')
                ->result_array();
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::get_login_history_for_user failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count total history rows for a user (for pagination).
     *
     * @param  int $user_id
     * @return int
     */
    public function count_login_history_for_user(int $user_id)
    {
        try {
            if (!$this->db->table_exists('user_login_history')) {
                return 0;
            }
            return (int)$this->db->where('user_id', $user_id)->count_all_results('user_login_history');
        } catch (Exception $e) {
            return 0;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Trusted Devices
    // ═══════════════════════════════════════════════════════════

    /**
     * Fetch a trusted device record by user + fingerprint.
     *
     * @param  int    $user_id
     * @param  string $fingerprint
     * @return array|null
     */
    public function get_trusted_device(int $user_id, string $fingerprint)
    {
        try {
            if (!$this->db->table_exists('trusted_user_devices') || $fingerprint === '') {
                return null;
            }
            $row = $this->db
                ->where('user_id', $user_id)
                ->where('device_fingerprint', $fingerprint)
                ->where('status', 'active')
                ->limit(1)
                ->get('trusted_user_devices')
                ->row_array();

            return $row ?: null;
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::get_trusted_device failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add or update a trusted device for a user.
     *
     * @param int    $user_id
     * @param string $role
     * @param string $fingerprint
     * @param array  $context  Keys: ip_address, browser_name, os_name, city, country
     */
    public function trust_device(int $user_id, string $role, string $fingerprint, array $context = [])
    {
        try {
            if (!$this->db->table_exists('trusted_user_devices') || $fingerprint === '') {
                return;
            }

            $existing = $this->get_trusted_device($user_id, $fingerprint);
            $now = date('Y-m-d H:i:s');

            if ($existing) {
                // Update last_used_at and ensure status active
                $this->db
                    ->where('user_id', $user_id)
                    ->where('device_fingerprint', $fingerprint)
                    ->update('trusted_user_devices', [
                        'last_used_at' => $now,
                        'status'       => 'active',
                        'ip_address'   => $context['ip_address']  ?? null,
                        'city'         => $context['city']         ?? null,
                        'country'      => $context['country']      ?? null,
                    ]);
            } else {
                $this->db->insert('trusted_user_devices', [
                    'user_id'            => $user_id,
                    'role'               => $role,
                    'device_fingerprint' => $fingerprint,
                    'ip_address'         => $context['ip_address']  ?? null,
                    'browser_name'       => $context['browser_name'] ?? null,
                    'os_name'            => $context['os_name']      ?? null,
                    'city'               => $context['city']         ?? null,
                    'country'            => $context['country']      ?? null,
                    'trusted_at'         => $now,
                    'last_used_at'       => $now,
                    'status'             => 'active',
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::trust_device failed: ' . $e->getMessage());
        }
    }

    /**
     * Revoke all trusted devices for a user (used when they report "Not me").
     *
     * @param int $user_id
     */
    public function revoke_all_user_devices(int $user_id)
    {
        try {
            if (!$this->db->table_exists('trusted_user_devices')) {
                return;
            }
            $this->db->where('user_id', $user_id)->update('trusted_user_devices', ['status' => 'revoked']);
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::revoke_all_user_devices failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if a login is suspicious by comparing with trusted devices.
     *
     * A login is suspicious if the device fingerprint is NOT in trusted_user_devices.
     * First-ever login (no trusted devices at all) is NOT suspicious – it auto-trusts.
     *
     * @param  int    $user_id
     * @param  string $fingerprint
     * @param  array  $context      Keys: ip_address, city, country
     * @return bool
     */
    public function is_suspicious_login(int $user_id, string $fingerprint, array $context = [])
    {
        try {
            if (!$this->db->table_exists('trusted_user_devices')) {
                return false; // Can't determine, assume safe
            }

            // Check total trusted device count for this user
            $total_trusted = (int)$this->db
                ->where('user_id', $user_id)
                ->where('status', 'active')
                ->count_all_results('trusted_user_devices');

            // First-ever login: no trusted devices yet → not suspicious, auto-trust
            if ($total_trusted === 0) {
                return false;
            }

            // Known device?
            $known = $this->get_trusted_device($user_id, $fingerprint);
            
            if ($known === null) {
                return true; // Unknown device -> suspicious
            }

            // Device is known, but check if IP or Location changed
            $current_ip = $context['ip_address'] ?? '';
            if ($current_ip !== '' && $current_ip !== '::1' && $current_ip !== '127.0.0.1') {
                if ($known['ip_address'] !== $current_ip) {
                    return true; // New IP Address
                }
            }

            $current_city = $context['city'] ?? null;
            $current_country = $context['country'] ?? null;
            if ($current_city && $current_country) {
                if ($known['city'] !== $current_city || $known['country'] !== $current_country) {
                    return true; // New Location
                }
            }

            return false; // Safe
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::is_suspicious_login failed: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Security Alert Logs & Tokens
    // ═══════════════════════════════════════════════════════════

    /**
     * Create a new security alert token and insert into security_alert_logs.
     *
     * @param  int    $user_id
     * @param  string $role
     * @param  string $email
     * @param  int    $login_history_id
     * @param  string $alert_type
     * @return string  The generated token (64-char hex), or '' on failure
     */
    public function create_alert_token(int $user_id, string $role, string $email, int $login_history_id, string $alert_type = 'new_device')
    {
        try {
            if (!$this->db->table_exists('security_alert_logs')) {
                return '';
            }

            $token   = bin2hex(random_bytes(32)); // 64-char hex
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            $this->db->insert('security_alert_logs', [
                'user_id'          => $user_id,
                'role'             => $role,
                'email'            => $email,
                'alert_type'       => $alert_type,
                'login_history_id' => $login_history_id > 0 ? $login_history_id : null,
                'token'            => $token,
                'token_expires_at' => $expires,
                'user_response'    => 'pending',
                'email_sent_status'=> 'skipped',
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            return $token;
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::create_alert_token failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Update email_sent_status on a security alert log (by token).
     *
     * @param string $token
     * @param string $status  'sent'|'failed'|'skipped'
     * @param string $error   Optional error message
     */
    public function update_alert_email_status(string $token, string $status, string $error = '')
    {
        try {
            if (!$this->db->table_exists('security_alert_logs') || $token === '') {
                return;
            }
            $this->db->where('token', $token)->update('security_alert_logs', [
                'email_sent_status' => $status,
                'email_error'       => $error,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::update_alert_email_status failed: ' . $e->getMessage());
        }
    }

    /**
     * Look up an alert log by token. Returns the row or null.
     * Also checks expiry. If expired, returns null.
     *
     * @param  string $token
     * @return array|null
     */
    public function resolve_token(string $token)
    {
        try {
            if (!$this->db->table_exists('security_alert_logs') || strlen($token) !== 64) {
                return null;
            }

            $row = $this->db
                ->where('token', $token)
                ->limit(1)
                ->get('security_alert_logs')
                ->row_array();

            if (empty($row)) {
                return null;
            }

            // Check expiry
            if (strtotime($row['token_expires_at']) < time()) {
                return null; // Expired
            }

            // Only allow responding to pending alerts
            if ($row['user_response'] !== 'pending') {
                return null; // Already responded
            }

            return $row;
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::resolve_token failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user_response on an alert log.
     *
     * @param string $token
     * @param string $response  'confirmed'|'not_me'
     */
    public function update_alert_response(string $token, string $response)
    {
        try {
            if (!$this->db->table_exists('security_alert_logs') || $token === '') {
                return;
            }
            $this->db->where('token', $token)->update('security_alert_logs', [
                'user_response' => $response,
                'responded_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::update_alert_response failed: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Admin: All Security Alerts
    // ═══════════════════════════════════════════════════════════

    /**
     * Get paginated security alert logs with joined user login history for admin panel.
     *
     * @param  int    $limit
     * @param  int    $offset
     * @param  array  $filters  Optional: ['role' => '', 'user_response' => '', 'is_suspicious' => '']
     * @return array
     */
    public function get_all_security_alerts(int $limit = 50, int $offset = 0, array $filters = [])
    {
        try {
            if (!$this->db->table_exists('security_alert_logs')) {
                return [];
            }

            $this->db->select('sal.*, ulh.ip_address, ulh.browser_name, ulh.os_name, ulh.device_type, ulh.city, ulh.state, ulh.country, ulh.is_suspicious, ulh.created_at AS login_time, u.first_name, u.last_name, u.email AS user_email');
            $this->db->from('security_alert_logs sal');
            $this->db->join('users u', 'u.id = sal.user_id', 'left');
            $this->db->join('user_login_history ulh', 'ulh.id = sal.login_history_id', 'left');

            if (!empty($filters['role'])) {
                $this->db->where('sal.role', $filters['role']);
            }
            if (!empty($filters['user_response'])) {
                $this->db->where('sal.user_response', $filters['user_response']);
            }
            if (isset($filters['is_suspicious']) && $filters['is_suspicious'] !== '') {
                $this->db->where('ulh.is_suspicious', (int)$filters['is_suspicious']);
            }

            $this->db->order_by('sal.created_at', 'DESC');
            $this->db->limit($limit, $offset);

            return $this->db->get()->result_array();
        } catch (Exception $e) {
            log_message('error', 'Security_login_model::get_all_security_alerts failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count total alert rows (for pagination), with optional filters.
     *
     * @param  array $filters
     * @return int
     */
    public function count_all_security_alerts(array $filters = [])
    {
        try {
            if (!$this->db->table_exists('security_alert_logs')) {
                return 0;
            }
            $this->db->from('security_alert_logs sal');
            if (!empty($filters['role'])) {
                $this->db->where('sal.role', $filters['role']);
            }
            if (!empty($filters['user_response'])) {
                $this->db->where('sal.user_response', $filters['user_response']);
            }
            return (int)$this->db->count_all_results();
        } catch (Exception $e) {
            return 0;
        }
    }
}
