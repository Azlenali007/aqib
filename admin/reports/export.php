<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
AdminAuth::requireAuth('reports.view');

$type = $_GET['type'] ?? 'orders';
$from = (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'))) . ' 00:00:00';
$to = (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) ? $_GET['to'] : date('Y-m-d')) . ' 23:59:59';

if ($type === 'deposits') {
    $rows = Database::fetchAll('SELECT d.id, u.username, d.gateway_code, d.amount, d.fee, d.net_credit, d.utr_reference, d.status, d.created_at FROM deposits d INNER JOIN users u ON u.id=d.user_id WHERE d.created_at BETWEEN ? AND ? ORDER BY d.id DESC', [$from, $to]);
    Response::csv('deposits_report_' . date('Ymd') . '.csv', ['ID', 'Username', 'Gateway', 'Paid', 'Fee', 'Net Credit', 'UTR', 'Status', 'Created At'], $rows);
}
if ($type === 'wallet') {
    $rows = Database::fetchAll('SELECT wt.id, u.username, wt.type, wt.amount, wt.balance_before, wt.balance_after, wt.reference_type, wt.description, wt.created_at FROM wallet_transactions wt INNER JOIN users u ON u.id=wt.user_id WHERE wt.created_at BETWEEN ? AND ? ORDER BY wt.id DESC', [$from, $to]);
    Response::csv('wallet_ledger_' . date('Ymd') . '.csv', ['ID', 'Username', 'Type', 'Amount', 'Before', 'After', 'Ref Type', 'Description', 'Created At'], $rows);
}

$rows = Database::fetchAll('SELECT o.id, u.username, s.name AS service, o.quantity, o.charge, o.provider_cost, o.profit, o.status, o.created_at FROM orders o INNER JOIN users u ON u.id=o.user_id INNER JOIN services s ON s.id=o.service_id WHERE o.created_at BETWEEN ? AND ? ORDER BY o.id DESC', [$from, $to]);
Response::csv('orders_report_' . date('Ymd') . '.csv', ['ID', 'Username', 'Service', 'Quantity', 'Charge', 'Cost', 'Profit', 'Status', 'Created At'], $rows);
