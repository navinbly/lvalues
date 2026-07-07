<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Communication providers
| Keep production credentials in environment variables or a server-only
| override. WhatsApp is intentionally disabled until a provider is configured.
*/
$config['communication_max_recipients'] = 500;
$config['communication_support_email'] = getenv('LVALUES_SUPPORT_EMAIL') ?: '';
$config['whatsapp_enabled'] = filter_var(getenv('LVALUES_WHATSAPP_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN);
$config['whatsapp_provider'] = getenv('LVALUES_WHATSAPP_PROVIDER') ?: '';
$config['whatsapp_api_url'] = getenv('LVALUES_WHATSAPP_API_URL') ?: '';
$config['whatsapp_api_token'] = getenv('LVALUES_WHATSAPP_API_TOKEN') ?: '';
$config['whatsapp_sender_id'] = getenv('LVALUES_WHATSAPP_SENDER_ID') ?: '';
