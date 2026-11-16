<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$action = strtolower(trim((string) ($_REQUEST['action'] ?? '')));
if ($action === '') {
    Response::json(['error' => 'Missing action parameter.'], 400);
}

$user = require __DIR__ . '/auth.php';

switch ($action) {
    case 'services':
        require __DIR__ . '/services.php';
        break;
    case 'add':
        require __DIR__ . '/add-order.php';
        break;
    case 'status':
        require __DIR__ . '/order-status.php';
        break;
    case 'refill':
        require __DIR__ . '/refill.php';
        break;
    case 'cancel':
        require __DIR__ . '/cancel.php';
        break;
    case 'balance':
        require __DIR__ . '/balance.php';
        break;
    default:
        Response::json(['error' => 'Invalid API action.'], 400);
}
