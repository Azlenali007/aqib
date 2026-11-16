<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$orders = Database::fetchAll(
    "SELECT o.*, p.api_url, p.api_key_enc, p.currency AS provider_currency
     FROM orders o
     INNER JOIN providers p ON p.id = o.provider_id
     WHERE o.status IN ('pending','processing','in_progress')
       AND o.provider_order_id IS NOT NULL AND o.provider_order_id <> ''
       AND p.status = 'active'
     ORDER BY o.last_synced_at ASC
     LIMIT 200"
);

$grouped = [];
foreach ($orders as $o) {
    $grouped[(int) $o['provider_id']][] = $o;
}

$updated = 0;
foreach ($grouped as $providerId => $provOrders) {
    $first = $provOrders[0];
    $client = new GenericProvider($providerId, $first['api_url'], Security::decrypt($first['api_key_enc']), $first['provider_currency']);

    foreach (array_chunk($provOrders, 50) as $chunk) {
        $extIds = array_column($chunk, 'provider_order_id');
        $res = $client->getMultiOrderStatus($extIds);
        if (!$res['success']) {
            continue;
        }
        $statuses = $res['statuses'] ?? [];
        foreach ($chunk as $ord) {
            $extId = (string) $ord['provider_order_id'];
            $st = $statuses[$extId] ?? null;
            if (!is_array($st) || empty($st['status'])) {
                continue;
            }
            $mappedStatus = strtolower(str_replace(' ', '_', trim((string) $st['status'])));
            if (!in_array($mappedStatus, ORDER_STATUSES, true)) {
                $mappedStatus = $ord['status'];
            }
            $startCount = isset($st['start_count']) && is_numeric($st['start_count']) ? (int) $st['start_count'] : $ord['start_count'];
            $remains = isset($st['remains']) && is_numeric($st['remains']) ? (int) $st['remains'] : $ord['remains'];

            if ($mappedStatus === 'cancelled' && $ord['status'] !== 'cancelled') {
                OrderService::cancelAndRefund((int) $ord['id'], 'cron', 0, 'Cancelled by provider sync');
                $updated++;
                continue;
            }

            if ($mappedStatus === 'partial' && $ord['status'] !== 'partial' && $remains > 0 && $remains < (int) $ord['quantity']) {
                $refundRatio = Currency::div((string) $remains, (string) $ord['quantity'], 6);
                $refundAmt = Currency::mul((string) $ord['charge'], $refundRatio, 4);
                if (Currency::compare($refundAmt, '0', 4) > 0) {
                    WalletService::credit((int) $ord['user_id'], $refundAmt, 'partial_refund', (string) $ord['id'], "Partial refund for Order #{$ord['id']} ($remains remains)", 'partial_refund_' . $ord['id'], 'refund');
                }
            }

            Database::execute(
                'UPDATE orders SET status = ?, start_count = ?, remains = ?, last_synced_at = NOW() WHERE id = ?',
                [$mappedStatus, $startCount, $remains, $ord['id']]
            );
            if ($mappedStatus !== $ord['status']) {
                Database::execute(
                    'INSERT INTO order_logs (order_id, actor_type, old_status, new_status, message) VALUES (?, "cron", ?, ?, "Status synced from provider")',
                    [$ord['id'], $ord['status'], $mappedStatus]
                );
            }
            $updated++;
        }
    }
}

cron_record_run('order_sync', "Checked " . count($orders) . " orders, updated $updated");
if (PHP_SAPI === 'cli') {
    echo "Order sync complete: $updated updated\n";
}
