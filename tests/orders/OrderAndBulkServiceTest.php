<?php
declare(strict_types=1);

function run_order_and_bulk_service_tests(): void
{
    $suffix = bin2hex(random_bytes(4));
    $reg = Auth::register([
        'username' => 'ordusr_' . $suffix,
        'email' => "ord_$suffix@example.com",
        'full_name' => 'Order User',
        'password' => 'OrderPass#123',
        'password_confirmation' => 'OrderPass#123',
    ]);
    $uid = (int) $reg['user_id'];
    WalletService::credit($uid, '200.00', 'deposit', '1', 'Seed balance for order test');

    $catId = (int) Database::fetchValue('SELECT id FROM categories ORDER BY id ASC LIMIT 1');
    $svcId1 = (int) Database::insert(
        'INSERT INTO services (category_id, name, platform, min_qty, max_qty, provider_price, provider_currency, provider_cost_base, provider_exchange_rate, system_base_currency, profit_margin_percent, selling_price, cancel_supported, is_active)
         VALUES (?, "Test Followers 1K", "instagram", 100, 10000, 50.000000, "INR", 50.000000, 1.00000000, "INR", 20.00, 60.000000, 1, 1)',
        [$catId]
    );
    $svcId2 = (int) Database::insert(
        'INSERT INTO services (category_id, name, platform, min_qty, max_qty, provider_price, provider_currency, provider_cost_base, provider_exchange_rate, system_base_currency, profit_margin_percent, selling_price, is_active)
         VALUES (?, "Test Likes 1K", "instagram", 100, 10000, 20.000000, "INR", 20.000000, 1.00000000, "INR", 20.00, 24.000000, 1)',
        [$catId]
    );

    // Place single order: 500 qty @ 60.00 / 1000 = 30.00 INR
    $ord = OrderService::placeOrder($uid, $svcId1, 'https://instagram.com/example', 500);
    TestRunner::assertTrue($ord['success'] === true, 'Order placement succeeds');
    TestRunner::assertSame('30.0000', $ord['charge'], 'Order charge computed accurately (500/1000 * 60 = 30.0000)');
    TestRunner::assertSame('170.0000', $ord['balance_after'], 'Wallet deducted atomically to 170.0000');

    // Cancel and refund order
    $cancel = OrderService::cancelAndRefund((int) $ord['order_id'], 'user', $uid, 'Test cancel');
    TestRunner::assertTrue($cancel['success'] === true, 'Order cancel succeeds');
    $balRefund = (string) Database::fetchValue('SELECT balance FROM wallets WHERE user_id = ?', [$uid]);
    TestRunner::assertSame('200.0000', $balRefund, 'Cancelled order refunds full 30.0000 back to 200.0000');

    // Bulk service management: margin change & disable
    $bulkMargin = ServiceManager::bulkAction('change_margin', [$svcId1, $svcId2], ['profit_margin_percent' => '50']);
    TestRunner::assertSame(2, $bulkMargin['success_count'], 'Bulk profit margin update updates both services');
    $newSelling1 = (string) Database::fetchValue('SELECT selling_price FROM services WHERE id = ?', [$svcId1]);
    TestRunner::assertSame('75.000000', $newSelling1, '50% margin on 50.000000 cost = 75.000000 selling price');

    // Bulk delete protection: svcId1 has an order so cannot be deleted, svcId2 has none so deletes
    $bulkDel = ServiceManager::bulkAction('delete', [$svcId1, $svcId2]);
    TestRunner::assertSame(1, $bulkDel['success_count'], 'Unused service deleted cleanly');
    TestRunner::assertSame(1, $bulkDel['fail_count'], 'Service with existing order protected from deletion');

    // Cleanup
    Database::execute('DELETE FROM orders WHERE user_id = ?', [$uid]);
    Database::execute('DELETE FROM services WHERE id = ?', [$svcId1]);
    Database::execute('DELETE FROM users WHERE id = ?', [$uid]);
}
