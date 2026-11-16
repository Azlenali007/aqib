<?php
declare(strict_types=1);

function run_payment_tests(): void
{
    $suffix = bin2hex(random_bytes(4));
    $reg = Auth::register([
        'username' => 'payusr_' . $suffix,
        'email' => "pay_$suffix@example.com",
        'full_name' => 'Payment User',
        'password' => 'PayPass#123',
        'password_confirmation' => 'PayPass#123',
    ]);
    $uid = (int) $reg['user_id'];

    // Test Razorpay HMAC signature verification (checkout + webhook)
    $origRzp = Database::fetchOne('SELECT config_json, is_active FROM payment_gateways WHERE code = "razorpay"');
    $testConfig = json_encode([
        'key_id' => 'rzp_test_unit',
        'key_secret' => Security::encrypt('rzp_secret_unit_123'),
        'webhook_secret' => Security::encrypt('wh_secret_unit_456'),
        'mode' => 'test',
    ]);
    Database::execute('UPDATE payment_gateways SET config_json = ?, is_active = 1 WHERE code = "razorpay"', [$testConfig]);

    $rzp = new RazorpayGateway();
    $orderId = 'order_test_' . $suffix;
    $paymentId = 'pay_test_' . $suffix;
    $validSig = hash_hmac('sha256', $orderId . '|' . $paymentId, 'rzp_secret_unit_123');
    TestRunner::assertTrue($rzp->verifyCheckoutSignature($orderId, $paymentId, $validSig), 'Valid Razorpay checkout HMAC verifies');
    TestRunner::assertTrue(!$rzp->verifyCheckoutSignature($orderId, $paymentId, 'tampered_sig'), 'Tampered Razorpay signature rejected');

    $rawWebhook = json_encode([
        'event' => 'payment.captured',
        'payload' => ['payment' => ['entity' => ['id' => $paymentId, 'order_id' => $orderId]]],
    ]);
    $whSig = hash_hmac('sha256', $rawWebhook, 'wh_secret_unit_456');
    TestRunner::assertTrue($rzp->verifyWebhookSignature($rawWebhook, $whSig), 'Valid Razorpay webhook HMAC verifies');
    TestRunner::assertTrue(!$rzp->verifyWebhookSignature($rawWebhook, 'bad_wh_sig'), 'Invalid webhook HMAC rejected');

    // Test Manual Gateway Approval single credit + idempotency
    Database::execute('UPDATE payment_gateways SET is_active = 1 WHERE code = "manual"');
    $utr = 'UTR' . strtoupper($suffix);
    $sub = PaymentService::submitManualDeposit($uid, '250.00', $utr, 'Test manual bank transfer');
    TestRunner::assertTrue($sub['success'] === true, 'Manual deposit submission succeeds');
    $depId = (int) $sub['deposit_id'];

    // Create temp admin ID
    $admRole = (int) Database::fetchValue('SELECT id FROM roles WHERE slug="super_admin" LIMIT 1');
    $admId = (int) Database::insert(
        'INSERT INTO admins (role_id, username, email, full_name, password_hash, status) VALUES (?, ?, ?, "Pay Admin", ?, "active")',
        [$admRole, 'payadm_' . $suffix, "payadm_$suffix@example.com", Security::hashPassword('AdminPass#123')]
    );

    $app1 = PaymentService::approveManualDeposit($depId, $admId, 'Verified UTR');
    TestRunner::assertTrue($app1['success'] === true, 'Manual deposit approval credits wallet');
    $app2 = PaymentService::approveManualDeposit($depId, $admId, 'Double click');
    TestRunner::assertTrue($app2['success'] === false, 'Second approval attempt on same deposit blocked');

    $bal = (string) Database::fetchValue('SELECT balance FROM wallets WHERE user_id = ?', [$uid]);
    TestRunner::assertSame('250.0000', $bal, 'Manual deposit credited exact 250.0000 once');

    // Restore original gateway states & cleanup
    Database::execute('UPDATE payment_gateways SET config_json = ?, is_active = ? WHERE code = "razorpay"', [$origRzp['config_json'], $origRzp['is_active']]);
    Database::execute('UPDATE payment_gateways SET is_active = 0 WHERE code = "manual"');
    Database::execute('DELETE FROM admins WHERE id = ?', [$admId]);
    Database::execute('DELETE FROM users WHERE id = ?', [$uid]);
}
