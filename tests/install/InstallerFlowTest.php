<?php
declare(strict_types=1);

require_once SMM_ROOT . '/install/Installer.php';

function run_installer_flow_tests(): void
{
    $cfg = require SMM_CONFIG . '/database.php';
    $host = (string) $cfg['host'];
    $port = (int) $cfg['port'];
    $user = (string) $cfg['username'];
    $pass = (string) $cfg['password'];

    $freshDb = 'smm_install_verify';
    $rootPdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $rootPdo->exec("DROP DATABASE IF EXISTS `$freshDb`");
    $rootPdo->exec("CREATE DATABASE `$freshDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 1. Connect to selected MySQL database
    $pdo = Installer::connectDatabase($host, $port, $freshDb, $user, $pass);
    $activeDb = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    TestRunner::assertSame($freshDb, $activeDb, 'Connected to exact target database');

    // 2-5. Create all tables, seed defaults, create default roles, verify super_admin role & retrieve actual ID
    $superRoleId = Installer::installSchemaAndRoles($pdo, SMM_ROOT);
    TestRunner::assertTrue($superRoleId > 0, 'Super Admin role exists with positive ID after schema+seed');

    $roleCount = (int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    TestRunner::assertTrue($roleCount >= 5, 'All 5 default roles created despite leading SQL comments');

    // Verify FK constraint fk_admins_role is active and rejects bogus role_id
    $fkRejected = false;
    try {
        $pdo->prepare('INSERT INTO admins (role_id, username, email, full_name, password_hash) VALUES (999999, "bad", "bad@example.com", "Bad", "x")')->execute();
    } catch (PDOException $ex) {
        if ((int) ($ex->errorInfo[1] ?? 0) === 1452) {
            $fkRejected = true;
        }
    }
    TestRunner::assertTrue($fkRejected, 'Foreign key fk_admins_role strictly rejects invalid role_id');

    // 6-7. Create initial admin ('admin' / 'admin123' / 'Super Admin') & verify FK relationship
    $res1 = Installer::createOrVerifyInitialAdmin($pdo, 'admin', 'admin@example.com', 'Super Admin', 'admin123');
    TestRunner::assertSame($superRoleId, $res1['role_id'], 'Admin created with exact Super Admin role_id');
    TestRunner::assertSame('super_admin', $res1['role_slug'], 'Admin FK join confirms super_admin role');

    $admRow = $pdo->query("SELECT * FROM admins WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    TestRunner::assertTrue($admRow !== false, 'Default admin record exists');
    TestRunner::assertSame('active', $admRow['status'], 'Default admin status is active');
    TestRunner::assertSame(0, (int) $admRow['two_factor_enabled'], '2FA disabled initially');
    TestRunner::assertTrue(password_verify('admin123', $admRow['password_hash']), 'Admin password_hash verifies against admin123');

    // Retry safety: re-running roles + admin creation does NOT duplicate or delete existing data
    $pdo->exec("INSERT INTO users (username, email, full_name, password_hash, referral_code, status) VALUES ('keepme', 'keep@example.com', 'Keep User', 'x', 'KEEP123', 'active')");
    $res2 = Installer::createOrVerifyInitialAdmin($pdo, 'admin', 'admin@example.com', 'Super Admin', 'admin123');
    TestRunner::assertSame($res1['admin_id'], $res2['admin_id'], 'Retry updates/verifies same admin record without duplicate');
    $admCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    TestRunner::assertSame(1, $admCount, 'Retry produces exactly 1 admin row');
    $keptUser = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'keepme'")->fetchColumn();
    TestRunner::assertSame(1, $keptUser, 'Retry preserves existing application user data');

    // Dynamic role_id test when super_admin role ID != 1 + Migration 003 test
    $brokenDb = 'smm_broken_existing';
    $rootPdo->exec("DROP DATABASE IF EXISTS `$brokenDb`");
    $rootPdo->exec("CREATE DATABASE `$brokenDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $brokenPdo = Installer::connectDatabase($host, $port, $brokenDb, $user, $pass);
    Installer::executeSqlFile($brokenPdo, SMM_ROOT . '/database/schema.sql');
    // Pre-insert a custom role so super_admin gets id = 2 (NOT 1!)
    $brokenPdo->exec("INSERT INTO roles (name, slug, is_system) VALUES ('Custom First Role', 'custom_first', 0)");
    $brokenPdo->exec("INSERT INTO users (username, email, full_name, password_hash, referral_code, status) VALUES ('existing_u', 'ex@example.com', 'Existing User', 'x', 'EX123', 'active')");

    Installer::executeSqlFile($brokenPdo, SMM_ROOT . '/database/migrations/003_fix_admin_roles_fk.sql');
    $fixedAdmin = $brokenPdo->query("SELECT a.role_id, a.username, a.password_hash, r.slug FROM admins a INNER JOIN roles r ON r.id = a.role_id WHERE a.username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    TestRunner::assertTrue($fixedAdmin !== false, 'Migration 003 repairs empty roles & inserts default admin');
    TestRunner::assertSame(2, (int) $fixedAdmin['role_id'], 'Migration 003 resolves actual super_admin role_id=2 dynamically without hardcoding 1');
    TestRunner::assertTrue(password_verify('admin123', $fixedAdmin['password_hash']), 'Migration 003 admin logs in with admin123');
    $keptExisting = (int) $brokenPdo->query("SELECT COUNT(*) FROM users WHERE username = 'existing_u'")->fetchColumn();
    TestRunner::assertSame(1, $keptExisting, 'Migration 003 preserves existing users');

    // Clean up temporary test databases
    $rootPdo->exec("DROP DATABASE IF EXISTS `$freshDb`");
    $rootPdo->exec("DROP DATABASE IF EXISTS `$brokenDb`");
}
