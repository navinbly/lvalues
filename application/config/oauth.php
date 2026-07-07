<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('lvalues_oauth_env')) {
    function lvalues_oauth_env($key, $default = '') {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }
        return ($value === false || $value === null) ? $default : trim((string)$value);
    }
}

$generic_redirect = lvalues_oauth_env('AUTH_REDIRECT_URI');

$config['oauth_providers'] = [
    'google' => [
        'label' => 'Google',
        'client_id' => lvalues_oauth_env('GOOGLE_CLIENT_ID'),
        'client_secret' => lvalues_oauth_env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => lvalues_oauth_env('GOOGLE_REDIRECT_URI', $generic_redirect),
        'scopes' => ['openid', 'profile', 'email'],
    ],
    'entra' => [
        'label' => 'Microsoft',
        'tenant_id' => lvalues_oauth_env('ENTRA_TENANT_ID'),
        'client_id' => lvalues_oauth_env('ENTRA_CLIENT_ID', lvalues_oauth_env('MICROSOFT_CLIENT_ID')),
        'client_secret' => lvalues_oauth_env('ENTRA_CLIENT_SECRET', lvalues_oauth_env('MICROSOFT_CLIENT_SECRET')),
        'redirect_uri' => lvalues_oauth_env('ENTRA_REDIRECT_URI', lvalues_oauth_env('MICROSOFT_REDIRECT_URI', $generic_redirect)),
        'authority' => lvalues_oauth_env('ENTRA_AUTHORITY'),
        'scopes' => ['openid', 'profile', 'email', 'offline_access'],
    ],
    'linkedin' => [
        'label' => 'LinkedIn',
        'client_id' => lvalues_oauth_env('LINKEDIN_CLIENT_ID'),
        'client_secret' => lvalues_oauth_env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => lvalues_oauth_env('LINKEDIN_REDIRECT_URI', $generic_redirect),
        'scopes' => ['openid', 'profile', 'email'],
    ],
    'github' => [
        'label' => 'GitHub',
        'client_id' => lvalues_oauth_env('GITHUB_CLIENT_ID'),
        'client_secret' => lvalues_oauth_env('GITHUB_CLIENT_SECRET'),
        'redirect_uri' => lvalues_oauth_env('GITHUB_REDIRECT_URI', $generic_redirect),
        'scopes' => ['read:user', 'user:email'],
    ],
    'apple' => [
        'label' => 'Apple',
        'client_id' => lvalues_oauth_env('APPLE_CLIENT_ID'),
        'client_secret' => lvalues_oauth_env('APPLE_CLIENT_SECRET'),
        'redirect_uri' => lvalues_oauth_env('APPLE_REDIRECT_URI', $generic_redirect),
        'scopes' => ['openid', 'name', 'email'],
    ],
];