<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/database/DatabaseTest.php';
require_once __DIR__ . '/authentication/AuthTest.php';
require_once __DIR__ . '/currency/CurrencyConversionTest.php';
require_once __DIR__ . '/providers/BulkImportTest.php';
require_once __DIR__ . '/wallet/WalletTransactionTest.php';
require_once __DIR__ . '/orders/OrderAndBulkServiceTest.php';
require_once __DIR__ . '/payments/RazorpayAndManualTest.php';
require_once __DIR__ . '/child-panels/ChildPanelIsolationTest.php';

$suites = [
    'Database Schema & Seed Test' => 'run_database_tests',
    'User & Admin Auth / RBAC Test' => 'run_authentication_tests',
    '3-Tier Currency & Profit Margin Test' => 'run_currency_conversion_tests',
    'Provider Preview & Bulk Import Test' => 'run_bulk_import_tests',
    'Atomic Wallet & Idempotency Test' => 'run_wallet_transaction_tests',
    'Order Lifecycle & Bulk Services Test' => 'run_order_and_bulk_service_tests',
    'Razorpay HMAC & Manual Approval Test' => 'run_payment_tests',
    'Child Panel Tenant Isolation Test' => 'run_child_panel_isolation_tests',
];

echo "=== SMM Panel Pro — PHP Test Suite ===\n\n";
foreach ($suites as $label => $fn) {
    echo "Running: $label ... ";
    $fn();
    echo "OK\n";
}

echo "\nSUMMARY: " . TestRunner::$passed . " assertions passed, " . TestRunner::$failed . " failed.\n";
exit(TestRunner::$failed > 0 ? 1 : 0);
