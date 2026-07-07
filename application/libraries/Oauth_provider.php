<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Oauth_provider
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('oauth', true);
    }

    public function normalize_provider($provider)
    {
        $provider = strtolower((string)$provider);
        if ($provider === 'microsoft') {
            return 'entra';
        }
        return $provider;
    }

    public function get_provider($provider)
    {
        $provider = $this->normalize_provider($provider);
        $providers = $this->CI->config->item('oauth_providers', 'oauth');
        if (!isset($providers[$provider])) {
            throw new Exception('Unsupported login provider.');
        }

        $config = $providers[$provider];
        $config['name'] = $provider;
        $config['redirect_uri'] = $this->resolve_redirect_uri($provider, $config);
        $config['endpoints'] = $this->resolve_endpoints($provider, $config);

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new Exception($config['label'] . ' sign-in is not available yet. Please continue with email.');
        }
        if ($provider === 'entra' && empty($config['tenant_id']) && empty($config['authority'])) {
            throw new Exception('Microsoft sign-in is not available yet. Please continue with email.');
        }

        return $config;
    }

    public function build_authorization_url($provider, $state, $nonce, $code_verifier, $login_hint = '')
    {
        $config = $this->get_provider($provider);
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->base64url(hash('sha256', $code_verifier, true)),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ];

        if ($login_hint !== '' && filter_var($login_hint, FILTER_VALIDATE_EMAIL)) {
            $params['login_hint'] = $login_hint;
        }

        if ($config['name'] === 'google') {
            $params['access_type'] = 'online';
            $params['include_granted_scopes'] = 'true';
        }

        return $config['endpoints']['authorize'] . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchange_code($provider, $code, $code_verifier)
    {
        $config = $this->get_provider($provider);
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code_verifier' => $code_verifier,
        ];

        $response = $this->http_post($config['endpoints']['token'], $params);
        if (empty($response['access_token']) && empty($response['id_token'])) {
            throw new Exception('Authentication token response was incomplete.');
        }
        return $response;
    }

    public function get_profile($provider, array $tokens, $expected_nonce)
    {
        $config = $this->get_provider($provider);
        $claims = [];
        if (!empty($tokens['id_token'])) {
            $claims = $this->decode_jwt_payload($tokens['id_token']);
            $this->validate_id_token_claims($config, $claims, $expected_nonce);
        }

        $userinfo = [];
        if (!empty($tokens['access_token']) && !empty($config['endpoints']['userinfo'])) {
            try {
                $userinfo = $this->http_get_json($config['endpoints']['userinfo'], $tokens['access_token']);
            } catch (Exception $ignored) {
                $userinfo = [];
            }
        }

        if ($config['name'] === 'github' && !empty($tokens['access_token']) && !empty($config['endpoints']['emails'])) {
            try {
                $userinfo['email'] = $this->github_primary_email($this->http_get_json($config['endpoints']['emails'], $tokens['access_token']));
            } catch (Exception $ignored) {
                $userinfo['email'] = isset($userinfo['email']) ? $userinfo['email'] : '';
            }
        }

        $merged = array_merge($claims, $userinfo);
        if (isset($merged['email_verified']) && !$this->truthy_email_verified($merged['email_verified'])) {
            throw new Exception('The provider did not confirm that this email address is verified.');
        }

        $email = $this->first_non_empty($merged, ['email', 'preferred_username', 'upn']);
        $subject = $this->first_non_empty($merged, ['sub', 'oid', 'id']);
        $name = $this->first_non_empty($merged, ['name', 'displayName', 'login']);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('The provider did not return a valid email address.');
        }
        if ($subject === '') {
            throw new Exception('The provider did not return a user subject identifier.');
        }

        $first_name = $this->first_non_empty($merged, ['given_name', 'givenName']);
        $last_name = $this->first_non_empty($merged, ['family_name', 'surname', 'familyName']);
        if ($first_name === '' && $name !== '') {
            $parts = preg_split('/\s+/', trim($name), 2);
            $first_name = isset($parts[0]) ? $parts[0] : '';
            $last_name = isset($parts[1]) ? $parts[1] : '';
        }

        return [
            'provider' => $config['name'],
            'provider_label' => $config['label'],
            'provider_user_id' => (string)$subject,
            'email' => strtolower($email),
            'name' => $name,
            'first_name' => $first_name !== '' ? $first_name : 'Lvalues',
            'last_name' => $last_name,
            'raw_profile' => $merged,
        ];
    }

    public function random_string($length = 64)
    {
        if (function_exists('random_bytes')) {
            return $this->base64url(random_bytes($length));
        }
        return $this->base64url(openssl_random_pseudo_bytes($length));
    }

    private function resolve_redirect_uri($provider, array $config)
    {
        if (!empty($config['redirect_uri'])) {
            return $config['redirect_uri'];
        }
        return site_url('login/oauth-callback/' . $provider);
    }

    private function resolve_endpoints($provider, array $config)
    {
        if ($provider === 'google') {
            return [
                'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token' => 'https://oauth2.googleapis.com/token',
                'userinfo' => 'https://openidconnect.googleapis.com/v1/userinfo',
            ];
        }
        if ($provider === 'linkedin') {
            return [
                'authorize' => 'https://www.linkedin.com/oauth/v2/authorization',
                'token' => 'https://www.linkedin.com/oauth/v2/accessToken',
                'userinfo' => 'https://api.linkedin.com/v2/userinfo',
            ];
        }
        if ($provider === 'github') {
            return [
                'authorize' => 'https://github.com/login/oauth/authorize',
                'token' => 'https://github.com/login/oauth/access_token',
                'userinfo' => 'https://api.github.com/user',
                'emails' => 'https://api.github.com/user/emails',
            ];
        }
        if ($provider === 'apple') {
            return [
                'authorize' => 'https://appleid.apple.com/auth/authorize',
                'token' => 'https://appleid.apple.com/auth/token',
                'userinfo' => '',
            ];
        }

        $authority = !empty($config['authority'])
            ? rtrim($config['authority'], '/')
            : 'https://login.microsoftonline.com/' . rawurlencode($config['tenant_id']);

        return [
            'authorize' => $authority . '/oauth2/v2.0/authorize',
            'token' => $authority . '/oauth2/v2.0/token',
            'userinfo' => 'https://graph.microsoft.com/oidc/userinfo',
        ];
    }

    private function validate_id_token_claims(array $config, array $claims, $expected_nonce)
    {
        if (empty($claims['exp']) || (int)$claims['exp'] < time()) {
            throw new Exception('Authentication session expired. Please try again.');
        }
        if (!empty($expected_nonce) && (empty($claims['nonce']) || !$this->safe_equals($expected_nonce, (string)$claims['nonce']))) {
            throw new Exception('Authentication nonce validation failed.');
        }

        $audience = isset($claims['aud']) ? $claims['aud'] : '';
        if (is_array($audience)) {
            $audience_ok = in_array($config['client_id'], $audience, true);
        } else {
            $audience_ok = $this->safe_equals((string)$config['client_id'], (string)$audience);
        }
        if (!$audience_ok) {
            throw new Exception('Authentication audience validation failed.');
        }

        $issuer = isset($claims['iss']) ? (string)$claims['iss'] : '';
        if ($config['name'] === 'google' && strpos($issuer, 'https://accounts.google.com') !== 0) {
            throw new Exception('Google issuer validation failed.');
        }
        if ($config['name'] === 'entra' && strpos($issuer, 'https://') !== 0) {
            throw new Exception('Microsoft issuer validation failed.');
        }
    }

    private function decode_jwt_payload($jwt)
    {
        $parts = explode('.', (string)$jwt);
        if (count($parts) < 2) {
            throw new Exception('Invalid identity token.');
        }
        $json = base64_decode(strtr($parts[1], '-_', '+/'));
        $claims = json_decode($json, true);
        if (!is_array($claims)) {
            throw new Exception('Invalid identity token claims.');
        }
        return $claims;
    }

    private function http_post($url, array $params)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new Exception('Provider token request failed' . ($error ? ': ' . $error : '.'));
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new Exception('Provider token response was not valid JSON.');
        }
        if (!empty($json['error'])) {
            throw new Exception(isset($json['error_description']) ? $json['error_description'] : $json['error']);
        }
        return $json;
    }

    private function http_get_json($url, $access_token)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $access_token,
            ],
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new Exception('Provider profile request failed' . ($error ? ': ' . $error : '.'));
        }
        $json = json_decode($body, true);
        return is_array($json) ? $json : [];
    }

    private function first_non_empty(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && trim((string)$data[$key]) !== '') {
                return trim((string)$data[$key]);
            }
        }
        return '';
    }

    private function safe_equals($known, $user)
    {
        if (function_exists('hash_equals')) {
            return hash_equals((string)$known, (string)$user);
        }
        $known = (string)$known;
        $user = (string)$user;
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $result === 0;
    }

    private function base64url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}