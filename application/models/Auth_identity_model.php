<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_identity_model extends CI_Model
{
    public function ensure_schema()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `user_auth_identities` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `provider` VARCHAR(40) NOT NULL,
                `provider_user_id` VARCHAR(191) NOT NULL,
                `email` VARCHAR(191) NOT NULL,
                `name` VARCHAR(191) DEFAULT NULL,
                `role_snapshot` VARCHAR(30) NOT NULL DEFAULT 'student',
                `raw_profile` LONGTEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                `last_login_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_provider_subject` (`provider`, `provider_user_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function find_or_create_user_from_oauth(array $profile, $registration_type = 'student')
    {
        $this->ensure_schema();

        $provider = strtolower((string)$profile['provider']);
        $provider_user_id = (string)$profile['provider_user_id'];
        $email = strtolower(trim((string)$profile['email']));
        $now = date('Y-m-d H:i:s');

        $identity = $this->db
            ->get_where('user_auth_identities', [
                'provider' => $provider,
                'provider_user_id' => $provider_user_id,
            ], 1)
            ->row_array();

        if (!empty($identity)) {
            $user = $this->db->get_where('users', ['id' => (int)$identity['user_id']], 1)->row_array();
            if (empty($user)) {
                throw new Exception('The linked Lvalues account no longer exists.');
            }
            $this->update_identity($identity['id'], $user, $profile, $now);
            $this->touch_user($user['id']);
            return $user;
        }

        $existing_email_identity = $this->db
            ->where('email', $email)
            ->where('provider !=', $provider)
            ->get('user_auth_identities')
            ->row_array();

        $user = $this->db->get_where('users', ['email' => $email], 1)->row_array();
        if (!empty($existing_email_identity) && empty($user)) {
            throw new Exception('This email is already linked to another login provider.');
        }

        if (!empty($user)) {
            if ((int)$user['status'] !== 1) {
                $this->db->where('id', (int)$user['id'])->update('users', [
                    'status' => 1,
                    'last_modified' => time(),
                ]);
                $user['status'] = 1;
            }
        } else {
            $user_id = $this->create_user($profile, $registration_type);
            $user = $this->db->get_where('users', ['id' => (int)$user_id], 1)->row_array();
        }

        $this->insert_identity($user, $profile, $now);
        $this->touch_user($user['id']);

        return $user;
    }

    private function create_user(array $profile, $registration_type = 'student')
    {
        $data = [
            'first_name' => html_escape($profile['first_name']),
            'last_name' => html_escape($profile['last_name']),
            'email' => strtolower($profile['email']),
            'password' => sha1(bin2hex(openssl_random_pseudo_bytes(16))),
            'verification_code' => rand(100000, 999999),
            'last_modified' => time(),
            'status' => 1,
            'wishlist' => json_encode([]),
            'date_added' => time(),
            'role_id' => 2,
            'is_instructor' => 0,
            'social_links' => json_encode(['facebook' => '', 'twitter' => '', 'linkedin' => '']),
            'payment_keys' => json_encode([]),
            'image' => md5(random_string('alnum', 16)),
        ];

        $this->db->insert('users', $data);
        return (int)$this->db->insert_id();
    }

    private function insert_identity(array $user, array $profile, $now)
    {
        $this->db->insert('user_auth_identities', [
            'user_id' => (int)$user['id'],
            'provider' => strtolower($profile['provider']),
            'provider_user_id' => (string)$profile['provider_user_id'],
            'email' => strtolower($profile['email']),
            'name' => $profile['name'],
            'role_snapshot' => $this->role_label($user),
            'raw_profile' => json_encode($profile['raw_profile']),
            'created_at' => $now,
            'updated_at' => $now,
            'last_login_at' => $now,
        ]);
    }

    private function update_identity($identity_id, array $user, array $profile, $now)
    {
        $this->db->where('id', (int)$identity_id)->update('user_auth_identities', [
            'email' => strtolower($profile['email']),
            'name' => $profile['name'],
            'role_snapshot' => $this->role_label($user),
            'raw_profile' => json_encode($profile['raw_profile']),
            'updated_at' => $now,
            'last_login_at' => $now,
        ]);
    }

    private function touch_user($user_id)
    {
        $this->db->where('id', (int)$user_id)->update('users', ['last_modified' => time()]);
    }

    private function role_label(array $user)
    {
        if ((int)($user['role_id'] ?? 0) === 1) {
            return 'admin';
        }
        if ((int)($user['is_instructor'] ?? 0) === 1) {
            return 'trainer';
        }
        return 'student';
    }
}