<?php
declare(strict_types=1);

if (empty($_SESSION['install_db_ok']) && !is_file(SMM_CONFIG . '/db_credentials.php')) {
    header('Location: ?step=database');
    exit;
}

require_once SMM_ROOT . '/core/Database.php';
require_once SMM_ROOT . '/core/Security.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim($_POST['site_name'] ?? 'SMM Panel');
    $baseCurr = strtoupper(trim($_POST['base_currency'] ?? 'INR'));
    $adminUser = trim($_POST['admin_username'] ?? 'admin');
    $adminEmail = strtolower(trim($_POST['admin_email'] ?? ''));
    $adminPass = (string) ($_POST['admin_password'] ?? '');

    if ($adminUser === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPass) < 8) {
        $err = 'Please enter a valid admin username, email, and password (minimum 8 characters).';
    } else {
        try {
            $pdo = Database::connect();
            $pdo->beginTransaction();

            // Set system base currency
            $pdo->prepare('UPDATE currencies SET is_base = CASE WHEN code = ? THEN 1 ELSE 0 END')->execute([$baseCurr]);
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'")->execute([$siteName]);
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'system_base_currency'")->execute([$baseCurr]);
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'support_email'")->execute([$adminEmail]);

            $hash = Security::hashPassword($adminPass);
            $pdo->prepare(
                'INSERT INTO admins (role_id, username, email, full_name, password_hash, status)
                 VALUES (1, ?, ?, "Super Administrator", ?, "active")
                 ON DUPLICATE KEY UPDATE email = VALUES(email), password_hash = VALUES(password_hash), status = "active"'
            )->execute([$adminUser, $adminEmail, $hash]);

            $pdo->commit();

            // Create storage folders and lock installer
            foreach (['logs', 'cache', 'backups', 'sessions', 'uploads/tickets', 'uploads/payments', 'uploads/profiles'] as $sub) {
                $dir = SMM_STORAGE . '/' . $sub;
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }
            file_put_contents(SMM_INSTALL_LOCK, 'Installed at ' . date('c'));

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
        <option value="INR" selected>INR (₹ Indian Rupee)</option>
        <option value="USD">USD ($ US Dollar)</option>
        <option value="EUR">EUR (€ Euro)</option>
        <option value="GBP">GBP (£ British Pound)</option>
      </select>
    </div>
  </div>
  <div class="grid2">
    <div><label>Super Admin Username</label><input name="admin_username" value="<?= h($_POST['admin_username'] ?? 'admin') ?>" required></div>
    <div><label>Super Admin Email</label><input name="admin_email" type="email" value="<?= h($_POST['admin_email'] ?? '') ?>" required></div>
  </div>
  <label>Super Admin Password (min 8 chars)</label><input name="admin_password" type="password" minlength="8" required>
  <button class="btn" type="submit">Complete Installation &rarr;</button>
</form>
