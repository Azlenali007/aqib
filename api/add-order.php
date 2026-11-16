<?php
declare(strict_types=1);

$serviceId = (int) ($_REQUEST['service'] ?? 0);
$link = trim((string) ($_REQUEST['link'] ?? ''));
$quantity = (int) ($_REQUEST['quantity'] ?? 0);

if ($serviceId <= 0 || $link === '' || $quantity <= 0) {
    Response::json(['error' => 'Parameters service, link, and quantity are required.'], 400);
}

$res = OrderService::placeOrder((int) $user['id'], $serviceId, $link, $quantity, 'api');
if (!$res['success']) {
    Response::json(['error' => $res['error']], 400);
}
Response::json(['order' => $res['order_id']]);
