<?php
declare(strict_types=1);

function run_database_tests(): void
{
    $tables = Database::pdo()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $required = [
        'roles', 'permissions', 'role_permissions', 'admins',
        'currencies', 'exchange_rates', 'exchange_rate_history',
        'users', 'wallets', 'wallet_transactions',
        'categories', 'providers', 'services', 'provider_service_mappings',
        'orders', 'mass_orders', 'refill_requests', 'order_logs',
        'payment_gateways', 'deposits', 'payments',
        'referrals', 'tickets', 'ticket_messages', 'notifications', 'announcements',
        'child_panels', 'child_panel_domains', 'child_panel_users', 'child_panel_wallets',
        'child_panel_services', 'child_panel_orders',
        'api_keys', 'activity_logs', 'settings', 'migrations',
    ];
    foreach ($required as $t) {
        TestRunner::assertTrue(in_array($t, $tables, true), "Schema contains required table '$t'");
    }

    $baseCurr = Currency::systemBase();
    TestRunner::assertSame('INR', $baseCurr, 'Default seeded system base currency is INR');

    $rolesCount = (int) Database::fetchValue('SELECT COUNT(*) FROM roles');
    TestRunner::assertTrue($rolesCount >= 5, 'Seeded RBAC roles exist');
}
