<?php
declare(strict_types=1);

define('SMM_ROOT', dirname(__DIR__));
define('SMM_CONFIG', SMM_ROOT . '/config');
define('SMM_CORE', SMM_ROOT . '/core');
define('SMM_INCLUDES', SMM_ROOT . '/includes');
define('SMM_MODULES', SMM_ROOT . '/modules');
define('SMM_INTEGRATIONS', SMM_ROOT . '/integrations');
define('SMM_THEMES', SMM_ROOT . '/themes');
define('SMM_STORAGE', SMM_ROOT . '/storage');
define('SMM_INSTALL_LOCK', SMM_STORAGE . '/installed.lock');

define('ORDER_STATUSES', [
    'pending',
    'processing',
    'in_progress',
    'completed',
    'partial',
    'cancelled',
    'refunded',
    'failed',
]);

define('SUPPORTED_PLATFORMS', [
    'instagram' => 'Instagram',
    'youtube' => 'YouTube',
    'facebook' => 'Facebook',
    'telegram' => 'Telegram',
    'tiktok' => 'TikTok',
    'twitter' => 'X (Twitter)',
    'linkedin' => 'LinkedIn',
    'spotify' => 'Spotify',
    'other' => 'Other',
]);

define('SUPPORTED_CURRENCIES_META', [
    'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
    'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
    'EUR' => ['name' => 'Euro', 'symbol' => '€'],
    'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
    'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
    'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳'],
    'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => 'Rs'],
]);
