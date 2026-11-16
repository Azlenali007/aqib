<?php
declare(strict_types=1);

function run_wallet_transaction_tests(): void
{
    $suffix = bin2hex(random_bytes(4));
    $reg = Auth::register([
        'username' => 'walusr_' . $suffix,
        'email' => "wal_$suffix@example.com",
        'full_name' => 'Wallet User',
        'password' => 'WalletPass#123',
        'password_confirmation' => 'WalletPass#123',
    ]);
    $uid = (int) $reg['user_id'];

    // Credit with idempotency key
    $c1 = WalletService::credit($uid, '500.00', 'deposit', '999', 'Test credit', 'idem_test_' . $suffix);
    TestRunner::assertTrue($c1['success'] === true && $c1['duplicate'] === false, 'Initial wallet credit succeeds');
    TestRunner::assertSame('500.0000', $c1['balance_after'], 'Wallet balance becomes 500.0000');

    // Duplicate credit with same idempotency key MUST NOT double-credit!
    $c2 = WalletService::credit($uid, '500.00', 'deposit', '999', 'Duplicate test credit', 'idem_test_' . $suffix);
    TestRunner::assertTrue($c2['success'] === true && $c2['duplicate'] === true, 'Duplicate idempotency key detected');
    $balNow = (string) Database::fetchValue('SELECT balance FROM wallets WHERE user_id = ?', [$uid]);
    TestRunner::assertSame('500.0000', $balNow, 'Balance not double-credited on duplicate key');

    // Over-debit fails atomically
    $dFail = WalletService::debit($uid, '600.00', 'order', '1', 'Too expensive');
    TestRunner::assertTrue($dFail['success'] === false, 'Over-debit fails with insufficient balance');
    $balAfterFail = (string) Database::fetchValue('SELECT balance FROM wallets WHERE user_id = ?', [$uid]);
    TestRunner::assertSame('500.0000', $balAfterFail, 'Balance unchanged after failed debit');

    // Valid debit
    $dOk = WalletService::debit($uid, '125.50', 'order', '2', 'Order charge');
    TestRunner::assertTrue($dOk['success'] === true && $dOk['balance_after'] === '374.5000', 'Debit reduces balance accurately to 374.5000');

    Database::execute('DELETE FROM users WHERE id = ?', [$uid]);
}
