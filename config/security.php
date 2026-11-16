<?php
declare(strict_types=1);

$localSecretsFile = __DIR__ . '/secrets.php';
$localSecrets = is_file($localSecretsFile) ? (require $localSecretsFile) : [];

return [
    'app_key' => $localSecrets['app_key'] ?? (getenv('APP_KEY') ?: hash('sha256', __DIR__ . '|smmpanel-default-change-on-install')),
    'cron_token' => $localSecrets['cron_token'] ?? (getenv('CRON_TOKEN') ?: ''),
    'session_name_user' => 'SMMPANEL_USER_SESS',
    'session_name_admin' => 'SMMPANEL_ADMIN_SESS',
    'csrf_ttl_seconds' => 7200,
    'max_upload_bytes' => 5 * 1024 * 1024,
    'allowed_upload_mimes' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ],
    'rate_limit' => [
        'max_attempts' => 5,
        'window_minutes' => 15,
    ],
];
