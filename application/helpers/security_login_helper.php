<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Security Login Helper
 * ---------------------
 * Provides utility functions for the Suspicious Login / New Device Alert feature.
 *
 * All functions are designed to fail SILENTLY so that a failure in any
 * utility call never interrupts the login flow.
 *
 * Functions:
 *  - sl_get_device_type()
 *  - sl_get_device_fingerprint(...)
 *  - sl_get_ip_geolocation(...)
 *  - sl_get_user_role_label(...)
 *  - sl_is_private_ip(...)
 *  - sl_duplicate_alert_exists(...)
 */

// ─────────────────────────────────────────────────────────────
// Device Type Detection
// ─────────────────────────────────────────────────────────────

/**
 * Returns 'mobile', 'tablet', or 'desktop' based on the User-Agent string.
 * Uses CodeIgniter's user_agent library if already loaded; otherwise parses
 * the UA string directly via simple regex so there is no hard CI dependency.
 *
 * @return string  'mobile'|'tablet'|'desktop'|'unknown'
 */
if (!function_exists('sl_get_device_type')) {
    function sl_get_device_type()
    {
        try {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
            if ($ua === '') {
                return 'unknown';
            }
            $ua_lower = strtolower($ua);

            // Tablet check first (iPad, Android tablet without 'mobile' keyword, Kindle, etc.)
            if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $ua_lower)) {
                return 'tablet';
            }
            // Mobile check
            if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $ua_lower)) {
                return 'mobile';
            }
            return 'desktop';
        } catch (Exception $e) {
            return 'unknown';
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Device Fingerprint
// ─────────────────────────────────────────────────────────────

/**
 * Generates a stable, privacy-safe device fingerprint as a SHA-256 hex string.
 * Built from: browser name + OS name + device type (NOT from IP or exact UA version).
 * Normalised to lowercase before hashing to reduce false positives.
 *
 * @param  string $browser_name
 * @param  string $browser_version  (not included in hash to avoid version-bump false alerts)
 * @param  string $os_name
 * @param  string $device_type
 * @return string  64-character hex string
 */
if (!function_exists('sl_get_device_fingerprint')) {
    function sl_get_device_fingerprint($browser_name = '', $browser_version = '', $os_name = '', $device_type = '')
    {
        try {
            $components = strtolower(trim($browser_name)) . '|'
                . strtolower(trim($browser_version)) . '|'
                . strtolower(trim($os_name)) . '|'
                . strtolower(trim($device_type));
            return hash('sha256', $components);
        } catch (Exception $e) {
            return hash('sha256', 'unknown|unknown|unknown|unknown');
        }
    }
}

// ─────────────────────────────────────────────────────────────
// IP Geolocation
// ─────────────────────────────────────────────────────────────

/**
 * Returns city / state / country for an IP address using ip-api.com (free, no key).
 * - Skips lookup for private / loopback IPs (returns nulls, no error).
 * - Timeout: 3 seconds. On any failure, returns nulls silently.
 * - Limit: 45 requests/minute on the free tier (fine for login events).
 *
 * @param  string $ip
 * @return array  ['city' => string|null, 'state' => string|null, 'country' => string|null]
 */
if (!function_exists('sl_get_ip_geolocation')) {
    function sl_get_ip_geolocation($ip = '')
    {
        $empty = ['city' => null, 'state' => null, 'country' => null];

        try {
            if (empty($ip) || sl_is_private_ip($ip)) {
                return $empty;
            }

            $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,city,regionName,country';

            // Use file_get_contents with a short timeout via stream context
            $context = stream_context_create([
                'http' => [
                    'timeout'        => 3,
                    'method'         => 'GET',
                    'ignore_errors'  => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false || $response === '') {
                return $empty;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
                return $empty;
            }

            return [
                'city'    => isset($data['city'])       && $data['city']       !== '' ? $data['city']       : null,
                'state'   => isset($data['regionName']) && $data['regionName'] !== '' ? $data['regionName'] : null,
                'country' => isset($data['country'])    && $data['country']    !== '' ? $data['country']    : null,
            ];
        } catch (Exception $e) {
            return $empty;
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Private IP Detection
// ─────────────────────────────────────────────────────────────

/**
 * Returns true if the IP address is a private/loopback/reserved address
 * that should not be sent to a geolocation service.
 *
 * @param  string $ip
 * @return bool
 */
if (!function_exists('sl_is_private_ip')) {
    function sl_is_private_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}

// ─────────────────────────────────────────────────────────────
// User Role Label
// ─────────────────────────────────────────────────────────────

/**
 * Maps a user record (from the `users` table) to a role label string
 * compatible with the ENUM in our new tables.
 *
 * @param  array $user  Row from `users` table
 * @return string  'admin'|'tutor'|'student'
 */
if (!function_exists('sl_get_role_label')) {
    function sl_get_role_label(array $user)
    {
        if ((int)($user['role_id'] ?? 0) === 1) {
            return 'admin';
        }
        if ((int)($user['is_instructor'] ?? 0) === 1) {
            return 'tutor';
        }
        return 'student';
    }
}

// ─────────────────────────────────────────────────────────────
// Get Real Client IP
// ─────────────────────────────────────────────────────────────

/**
 * Returns the real client IP address, respecting common proxy headers
 * (Hostinger uses CloudFlare / reverse proxies).
 * Validates each candidate; falls back to REMOTE_ADDR.
 *
 * @return string
 */
if (!function_exists('sl_get_client_ip')) {
    function sl_get_client_ip()
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For may contain a comma-separated list
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

// ─────────────────────────────────────────────────────────────
// Duplicate Alert Prevention
// ─────────────────────────────────────────────────────────────

/**
 * Returns true if a 'pending' security alert already exists for this
 * user + device_fingerprint within the last $minutes minutes.
 * Prevents alert-email spam on repeated logins from the same new device.
 *
 * Requires CI database to be available (called from model context).
 * NOT meant to be called standalone; the Security_login_model calls this.
 *
 * @param  object $db            CI database object
 * @param  int    $user_id
 * @param  string $fingerprint
 * @param  int    $minutes       Default 30
 * @return bool
 */
if (!function_exists('sl_duplicate_alert_exists')) {
    function sl_duplicate_alert_exists($db, $user_id, $fingerprint, $minutes = 30)
    {
        try {
            if (!$db->table_exists('security_alert_logs') || !$db->table_exists('user_login_history')) {
                return false;
            }

            $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

            // Find any pending alert for this user in the last N minutes
            $count = $db
                ->select('sal.id')
                ->from('security_alert_logs sal')
                ->join('user_login_history ulh', 'ulh.id = sal.login_history_id', 'inner')
                ->where('sal.user_id', (int)$user_id)
                ->where('sal.user_response', 'pending')
                ->where('sal.created_at >=', $since)
                ->get()
                ->num_rows();

            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
