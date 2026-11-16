<?php
declare(strict_types=1);

if (!empty($_REQUEST['orders'])) {
    $ids = array_values(array_filter(array_map('intval', explode(',', (string) $_REQUEST['orders']))));
    $out = [];
    foreach (array_slice($ids, 0, 100) as $oid) {
        $ord = Database::fetchOne('SELECT id, charge, start_count, status, remains, currency FROM orders WHERE id = ? AND user_id = ? LIMIT 1', [$oid, (int) $user['id']]);
        $out[(string) $oid] = $ord ? [
            'charge' => $ord['charge'],
            'start_count' => $ord['start_count'],
            'status' => ucfirst(str_replace('_', ' ', $ord['status'])),
            'remains' => $ord['remains'],
            'currency' => $ord['currency'],
        ] : ['error' => 'Incorrect order ID'];
    }
    Response::json($out);
}

$orderId = (int) ($_REQUEST['order'] ?? 0);
if ($orderId <= 0) {
    Response::json(['error' => 'Incorrect order ID'], 400);
}
$ord = Database::fetchOne('SELECT id, charge, start_count, status, remains, currency FROM orders WHERE id = ? AND user_id = ? LIMIT 1', [$orderId, (int) $user['id']]);
if (!$ord) {
    Response::json(['error' => 'Incorrect order ID'], 404);
}
Response::json([
    'charge' => $ord['charge'],
    'start_count' => $ord['start_count'],
    'status' => ucfirst(str_replace('_', ' ', $ord['status'])),
    'remains' => $ord['remains'],
    'currency' => $ord['currency'],
]);
