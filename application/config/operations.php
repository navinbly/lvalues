<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['operations_backup_directory'] = getenv('LVALUES_BACKUP_DIR') ?: APPPATH . '../storage/backups';
$config['operations_backup_retention_days'] = (int)(getenv('LVALUES_BACKUP_RETENTION_DAYS') ?: 14);
$config['operations_mysqldump_binary'] = getenv('LVALUES_MYSQLDUMP_BIN') ?: (PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump');
$config['operations_mysql_binary'] = getenv('LVALUES_MYSQL_BIN') ?: (PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysql.exe' : 'mysql');
$config['operations_alert_email'] = getenv('LVALUES_ALERT_EMAIL') ?: '';
$config['operations_health_token'] = getenv('LVALUES_HEALTH_TOKEN') ?: '';
$config['operations_providers'] = array_filter(array(
    'smtp' => getenv('LVALUES_SMTP_HEALTH_URL') ?: '',
    'whatsapp' => getenv('LVALUES_WHATSAPP_HEALTH_URL') ?: '',
    'payment' => getenv('LVALUES_PAYMENT_HEALTH_URL') ?: '',
));
