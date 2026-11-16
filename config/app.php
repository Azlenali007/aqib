<?php
declare(strict_types=1);

return [
    'name' => 'SMM Panel',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => (getenv('APP_DEBUG') === '1'),
    'timezone' => 'Asia/Kolkata',
    'base_currency' => 'INR',
    'default_theme' => 'classic',
    'root_path' => dirname(__DIR__),
    'storage_path' => dirname(__DIR__) . '/storage',
    'upload_path' => dirname(__DIR__) . '/storage/uploads',
    'log_path' => dirname(__DIR__) . '/storage/logs',
    'cache_path' => dirname(__DIR__) . '/storage/cache',
    'backup_path' => dirname(__DIR__) . '/storage/backups',
    'session_path' => dirname(__DIR__) . '/storage/sessions',
];
