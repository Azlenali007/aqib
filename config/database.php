<?php
declare(strict_types=1);

$localCredsFile = __DIR__ . '/db_credentials.php';
$localCreds = is_file($localCredsFile) ? (require $localCredsFile) : [];

return [
    'driver' => 'mysql',
    'host' => $localCreds['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1'),
    'port' => (int) ($localCreds['port'] ?? (getenv('DB_PORT') ?: 3306)),
    'database' => $localCreds['database'] ?? (getenv('DB_NAME') ?: 'smmpanel'),
    'username' => $localCreds['username'] ?? (getenv('DB_USER') ?: 'root'),
    'password' => $localCreds['password'] ?? (getenv('DB_PASS') ?: ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
