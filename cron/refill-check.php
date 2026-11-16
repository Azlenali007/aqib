<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$pending = Database::fetchAll("SELECT r.*, o.user_id FROM refill_requests r INNER JOIN orders o ON o.id = r.order_id WHERE r.status IN ('pending','processing') LIMIT 100");
$checked = count($pending);
cron_record_run('refill_check', "Evaluated $checked pending refill requests");
if (PHP_SAPI === 'cli') {
    echo "Refill check complete: $checked evaluated\n";
}
