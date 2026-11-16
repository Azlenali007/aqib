<?php
declare(strict_types=1);

function run_authentication_tests(): void
{
    $suffix = bin2hex(random_bytes(4));
    $res = Auth::register([
        'username' => 'testusr_' . $suffix,
        'email' => "testusr_$suffix@example.com",
        'full_name' => 'Test User',
        'password' => 'StrongPass#123',
        'password_confirmation' => 'StrongPass#123',
    ]);
    TestRunner::assertTrue($res['success'] === true, 'User registration succeeds');
    $uid = (int) $res['user_id'];

    $wallet = Database::fetchOne('SELECT * FROM wallets WHERE user_id = ?', [$uid]);
    TestRunner::assertTrue($wallet !== null, 'Wallet auto-created upon user registration');
    TestRunner::assertSame('0.0000', $wallet['balance'], 'New user starts with zero balance');

    // Duplicate registration rejected
    $dup = Auth::register([
        'username' => 'testusr_' . $suffix,
        'email' => "testusr_$suffix@example.com",
        'full_name' => 'Test User',
        'password' => 'StrongPass#123',
        'password_confirmation' => 'StrongPass#123',
    ]);
    TestRunner::assertTrue($dup['success'] === false, 'Duplicate username/email rejected');

    // Password verification & invalid password
    $uRow = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$uid]);
    TestRunner::assertTrue(Security::verifyPassword('StrongPass#123', $uRow['password_hash']), 'Password hash verifies');
    TestRunner::assertTrue(!Security::verifyPassword('WrongPass', $uRow['password_hash']), 'Wrong password fails verification');

    // Separate Admin RBAC verification
    $adminRole = Database::fetchOne('SELECT id FROM roles WHERE slug = "support_staff" LIMIT 1');
    $admUsername = 'testadm_' . $suffix;
    $aid = (int) Database::insert(
        'INSERT INTO admins (role_id, username, email, full_name, password_hash, status) VALUES (?, ?, ?, ?, ?, "active")',
        [$adminRole['id'], $admUsername, "$admUsername@example.com", 'Support Agent', Security::hashPassword('AdminPass#123')]
    );
    $admRow = Database::fetchOne('SELECT a.*, r.slug AS role_slug FROM admins a INNER JOIN roles r ON r.id=a.role_id WHERE a.id = ?', [$aid]);
    TestRunner::assertTrue(AdminAuth::can('tickets.reply', $admRow), 'Support Staff has tickets.reply permission');
    TestRunner::assertTrue(!AdminAuth::can('roles.manage', $admRow), 'Support Staff cannot access roles.manage');

    // Cleanup test actors
    Database::execute('DELETE FROM admins WHERE id = ?', [$aid]);
    Database::execute('DELETE FROM users WHERE id = ?', [$uid]);
}
