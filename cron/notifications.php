<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$unread = (int) Database::fetchValue('SELECT COUNT(*) FROM notifications WHERE is_read = 0', [], 0);
cron_record_run('notifications', "Active unread user notifications: $unread");
if (PHP_SAPI === 'cli') {
    echo "Notifications cron complete: $unread unread\n";
}
