<?php
declare(strict_types=1);

if (empty($_SESSION['install_db_ok']) && !is_file(SMM_CONFIG . '/db_credentials.php')) {
    header('Location: ?step=database');
    exit;
}

require_once __DIR__ . '/Installer.php';
require_once SMM_ROOT . '/core/Database.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim($_POST['site_name'] ?? 'SMM Panel');
    $baseCurr = strtoupper(trim($_POST['base_currency'] ?? 'INR'));
    $adminUser = trim($_POST['admin_username'] ?? 'admin');
    $adminFullName = trim($_POST['admin_full_name'] ?? 'Super Admin');
    $adminEmail = strtolower(trim($_POST['admin_email'] ?? 'admin@example.com'));
    $adminPass = (string) ($_POST['admin_password'] ?? '');

    if ($adminUser === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
        $err = 'Please enter a valid admin username, email, and password (minimum 8 characters).';
    } else {
        try {
            // Step 1: Connect to the configured MySQL database
            Database::reset();
            $pdo = Database::connect();

            // Step 2–5: Ensure tables, seed defaults if retrying partial setup, verify roles exist
            if (is_file(SMM_ROOT . '/database/seeds/default_data.sql')) {
                Installer::executeSqlFile($pdo, SMM_ROOT . '/database/seeds/default_data.sql');
            }
            Installer::verifyRequiredTables($pdo);

            $pdo->beginTransaction();

            // Update site & base currency settings idempotently
            $pdo->prepare('UPDATE currencies SET is_base = CASE WHEN code = ? THEN 1 ELSE 0 END')->execute([$baseCurr]);
            $pdo->prepare(
                "INSERT INTO settings (setting_group, setting_key, setting_value, is_secret) VALUES ('general', 'site_name', ?, 0)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute([$siteName]);
            $pdo->prepare(
                "INSERT INTO settings (setting_group, setting_key, setting_value, is_secret) VALUES ('general', 'system_base_currency', ?, 0)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute([$baseCurr]);
            $pdo->prepare(
                "INSERT INTO settings (setting_group, setting_key, setting_value, is_secret) VALUES ('general', 'support_email', ?, 0)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            )->execute([$adminEmail]);

            // Step 4–7: Ensure Super Admin role exists, lookup real role_id, create/verify admin with FK check
            $adminResult = Installer::createOrVerifyInitialAdmin(
                $pdo,
                $adminUser,
                $adminEmail,
                $adminFullName,
                $adminPass
            );

            // Step 8: Mark migration applied
            $pdo->prepare("INSERT IGNORE INTO migrations (migration) VALUES ('003_fix_admin_roles_fk')")->execute();
            $pdo->commit();

            // Prepare writable storage directories
            foreach (['logs', 'cache', 'backups', 'sessions', 'uploads/tickets', 'uploads/payments', 'uploads/profiles'] as $sub) {
                $dir = SMM_STORAGE . '/' . $sub;
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }

            // Lock installation ONLY after all DB & FK checks succeeded
            if (file_put_contents(SMM_INSTALL_LOCK, 'Installed at ' . date('c') . ' | admin_id=' . $adminResult['admin_id'] . ' | role_id=' . $adminResult['role_id']) === false) {
                throw new RuntimeException('Installation verified in DB, but failed to write storage/installed.lock.');
            }

            header('Location: ?step=complete');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
<form method="post">
  <div class="grid2">
    <div><label>Website Name</label><input name="site_name" value="<?= h($_POST['site_name'] ?? 'SMM Panel') ?>" required></div>
    <div>
      <label>System Base Accounting Currency</label>
      <select name="base_currency">
        <option value="INR" <?= ($_POST['base_currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>INR (₹ Indian Rupee)</option>
        <option value="USD" <?= ($_POST['base_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD ($ US Dollar)</option>
        <option value="EUR" <?= ($_POST['base_currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR (€ Euro)</option>
        <option value="GBP" <?= ($_POST['base_currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP (£ British Pound)</option>
      </select>
    </div>
  </div>
  <div class="grid2">
    <div><label>Super Admin Username</label><input name="admin_username" value="<?= h($_POST['admin_username'] ?? 'admin') ?>" required></div>
    <div><label>Super Admin Full Name</label><input name="admin_full_name" value="<?= h($_POST['admin_full_name'] ?? 'Super Admin') ?>" required></div>
  </div>
  <div class="grid2">
    <div><label>Super Admin Email</label><input name="admin_email" type="email" value="<?= h($_POST['admin_email'] ?? 'admin@example.com') ?>" required></div>
    <div><label>Super Admin Password (min 8 chars, default: admin123)</label><input name="admin_password" type="password" value="<?= h($_POST['admin_password'] ?? 'admin123') ?>" minlength="8" required></div>
  </div>
  <button class="btn" type="submit">Complete Installation &rarr;</button>
</form>
