<?php
declare(strict_types=1);

$ordersRaw = (string) ($_REQUEST['orders'] ?? $_REQUEST['order'] ?? '');
$ids = array_values(array_filter(array_map('intval', explode(',', $ordersRaw))));
if (empty($ids)) {
    Response::json(['error' => 'Order ID is required.'], 400);
}

$results = [];
foreach (array_slice($ids, 0, 50) as $oid) {
    $res = OrderService::cancelAndRefund($oid, 'user', (int) $user['id'], 'Cancelled via Reseller API');
    $results[] = ['order' => $oid, 'cancel' => $res['success'] ? 1 : ['error' => $res['error']]];
}
Response::json(count($results) === 1 ? $results[0] : $results);
