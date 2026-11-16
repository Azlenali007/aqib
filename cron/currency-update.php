<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$res = ExchangeRate::updateAll(false);
$msg = $res['success']
    ? "Updated {$res['updated']} pairs via {$res['source']}"
    : ($res['error'] ?? 'Failed');

cron_record_run('currency_update', $msg);
if (PHP_SAPI === 'cli') {
    echo "Currency update: $msg\n";
}
