<?php
declare(strict_types=1);

$orderId = (int) ($_REQUEST['order'] ?? 0);
if ($orderId <= 0) {
    Response::json(['error' => 'Incorrect order ID'], 400);
}
$res = OrderService::requestRefill($orderId, (int) $user['id']);
if (!$res['success']) {
    Response::json(['error' => $res['error']], 400);
}
Response::json(['refill' => $res['refill_id']]);
