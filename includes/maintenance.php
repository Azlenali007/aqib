<?php
declare(strict_types=1);

function enforce_maintenance_mode(): void
{
    if (PHP_SAPI === 'cli' || !Database::isConfigured()) {
        return;
    }
    $path = $_SERVER['REQUEST_URI'] ?? '';
    if (str_starts_with($path, '/admin') || str_starts_with($path, '/install') || str_starts_with($path, '/cron')) {
        return;
    }
    if (get_setting('maintenance_mode', '0') === '1') {
        if (AdminAuth::check()) {
            return;
        }
        http_response_code(503);
        $msg = (string) get_setting('maintenance_message', 'Scheduled maintenance in progress.');
        $site = (string) get_setting('site_name', 'SMM Panel');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . e($site) . ' - Maintenance</title>';
        echo '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F7F5FB;font-family:system-ui,-apple-system,sans-serif;color:#211A2D;padding:24px}';
        echo '.card{max-width:480px;width:100%;background:#fff;border:1px solid #EAE5F1;border-radius:22px;padding:36px;text-align:center;box-shadow:0 16px 40px rgba(112,69,216,.12)}';
        echo 'h1{margin:0 0 12px;color:#7045D8;font-size:24px}p{color:#777083;line-height:1.6;margin:0}</style></head><body>';
        echo '<div class="card"><h1>Scheduled Maintenance</h1><p>' . e($msg) . '</p></div></body></html>';
        exit;
    }
}
