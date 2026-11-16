<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
AdminAuth::requireAuth('orders.export');
$rows = Database::fetchAll('SELECT o.id, u.username, s.name AS service, o.link, o.quantity, o.charge, o.provider_cost, o.profit, o.status, o.created_at FROM orders o INNER JOIN users u ON u.id=o.user_id INNER JOIN services s ON s.id=o.service_id ORDER BY o.id DESC LIMIT 5000');
Response::csv('orders_' . date('Ymd_His') . '.csv', ['ID', 'Username', 'Service', 'Link', 'Quantity', 'Charge', 'Provider Cost', 'Profit', 'Status', 'Created At'], $rows);
