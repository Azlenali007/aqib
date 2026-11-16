<?php
declare(strict_types=1);

function run_child_panel_isolation_tests(): void
{
    $suffix = bin2hex(random_bytes(4));
    $ownerReg = Auth::register([
        'username' => 'cpown_' . $suffix,
        'email' => "cpown_$suffix@example.com",
        'full_name' => 'Panel Owner',
        'password' => 'OwnerPass#123',
        'password_confirmation' => 'OwnerPass#123',
    ]);
    $ownerId = (int) $ownerReg['user_id'];
    WalletService::credit($ownerId, '500.00', 'deposit', '1', 'Owner seed balance');

    // Create two isolated child panels A and B
    $pA = ChildPanelService::createPanel($ownerId, 'Panel Alpha', "alpha-$suffix.example.com", 'INR', '25.00');
    $pB = ChildPanelService::createPanel($ownerId, 'Panel Beta', "beta-$suffix.example.com", 'INR', '30.00');
    TestRunner::assertTrue($pA['success'] && $pB['success'], 'Two child panels created');
    $panelA = (int) $pA['panel_id'];
    $panelB = (int) $pB['panel_id'];

    Database::execute("UPDATE child_panels SET status = 'active' WHERE id IN (?, ?)", [$panelA, $panelB]);
    ChildPanelService::addFundsToPanel($panelA, $ownerId, '100.00');
    ChildPanelService::addFundsToPanel($panelB, $ownerId, '100.00');

    // Create isolated child user inside Panel A
    $cuA = ChildPanelService::createChildUser($panelA, 'child_u1', "cu1_$suffix@alpha.test", 'Child User A', 'ChildPass#123');
    TestRunner::assertTrue($cuA['success'] === true, 'Child user created in Panel A');
    $childUserA = (int) $cuA['child_user_id'];
    ChildPanelService::creditChildUserWallet($panelA, $childUserA, '50.00', 'Initial child credit');

    // Create a parent service + assign ONLY to Panel B
    $catId = (int) Database::fetchValue('SELECT id FROM categories ORDER BY id ASC LIMIT 1');
    $parentSvcId = (int) Database::insert(
        'INSERT INTO services (category_id, name, platform, min_qty, max_qty, provider_price, provider_currency, provider_cost_base, provider_exchange_rate, system_base_currency, profit_margin_percent, selling_price, is_active)
         VALUES (?, "Isolated Svc", "instagram", 10, 1000, 10.000000, "INR", 10.000000, 1.00000000, "INR", 20.00, 12.000000, 1)',
        [$catId]
    );
    $cpsB = (int) Database::insert(
        'INSERT INTO child_panel_services (child_panel_id, parent_service_id, custom_name, profit_margin_percent, custom_selling_price, is_enabled) VALUES (?, ?, "Beta Only Svc", 25.00, 15.000000, 1)',
        [$panelB, $parentSvcId]
    );

    // Cross-tenant attack attempt: Panel A user tries to order using Panel B's service or Panel B context -> MUST FAIL!
    $cross1 = ChildPanelService::placeChildOrder($panelB, $childUserA, $cpsB, 'https://ig.test/1', 100);
    TestRunner::assertTrue($cross1['success'] === false, 'Panel A user rejected inside Panel B');

    $cross2 = ChildPanelService::placeChildOrder($panelA, $childUserA, $cpsB, 'https://ig.test/1', 100);
    TestRunner::assertTrue($cross2['success'] === false, 'Panel B service rejected inside Panel A');

    // Valid order within Panel A using Panel A service
    $cpsA = (int) Database::insert(
        'INSERT INTO child_panel_services (child_panel_id, parent_service_id, custom_name, profit_margin_percent, custom_selling_price, is_enabled) VALUES (?, ?, "Alpha Svc", 25.00, 15.000000, 1)',
        [$panelA, $parentSvcId]
    );
    $okOrder = ChildPanelService::placeChildOrder($panelA, $childUserA, $cpsA, 'https://ig.test/valid', 1000);
    TestRunner::assertTrue($okOrder['success'] === true, 'Authorized Panel A user order on Panel A service succeeds');
    TestRunner::assertSame('15.0000', $okOrder['charge'], 'Child order charged 15.0000');
    TestRunner::assertSame('3.0000', $okOrder['panel_profit'], 'Panel profit calculated accurately (15.00 - 12.00 = 3.0000)');

    // Cleanup
    Database::execute('DELETE FROM child_panels WHERE id IN (?, ?)', [$panelA, $panelB]);
    Database::execute('DELETE FROM services WHERE id = ?', [$parentSvcId]);
    Database::execute('DELETE FROM users WHERE id = ?', [$ownerId]);
}
