<?php
declare(strict_types=1);

require_once __DIR__ . '/Installer.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '127.0.0.1');
    $port = (int) ($_POST['port'] ?? 3306);
    $db   = trim($_POST['database'] ?? '');
    $user = trim($_POST['username'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');

    if ($db === '' || $user === '') {
        $err = 'Database name and username are required.';
    } else {
        try {
            // Step 1: Connect to the selected MySQL database
            $pdo = Installer::connectDatabase($host, $port, $db, $user, $pass);

            // Step 2–5: Create all required tables, seed defaults, create/verify default roles, and obtain Super Admin role ID
            $superRoleId = Installer::installSchemaAndRoles($pdo, SMM_ROOT);

            $credPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                'host' => $host,
                'port' => $port,
                'database' => $db,
                'username' => $user,
                'password' => $pass,
                'charset' => 'utf8mb4',
            ], true) . ";\n";
            if (file_put_contents(SMM_CONFIG . '/db_credentials.php', $credPhp, LOCK_EX) === false) {
                throw new RuntimeException('Failed to write config/db_credentials.php. Check folder permissions.');
            }

            if (!is_file(SMM_CONFIG . '/secrets.php')) {
                $secretsPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                    'app_key' => bin2hex(random_bytes(32)),
                    'cron_token' => bin2hex(random_bytes(16)),
                ], true) . ";\n";
                if (file_put_contents(SMM_CONFIG . '/secrets.php', $secretsPhp, LOCK_EX) === false) {
                    throw new RuntimeException('Failed to write config/secrets.php. Check folder permissions.');
                }
            }

            $_SESSION['install_db_ok'] = true;
            $_SESSION['install_super_role_id'] = $superRoleId;
            header('Location: ?step=setup');
            exit;
        } catch (Throwable $e) {
            $err = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php if ($err): ?><div class="alert alert-err"><?= h($err) ?></div><?php endif; ?>
<form method="post">
  <div class="grid2">
    <div><label>MySQL Host</label><input name="host" value="<?= h($_POST['host'] ?? '127.0.0.1') ?>" required></div>
    <div><label>Port</label><input name="port" type="number" value="<?= h((string) ($_POST['port'] ?? '3306')) ?>" required></div>
  </div>
  <label>Database Name</label><input name="database" value="<?= h($_POST['database'] ?? 'smmpanel') ?>" required>
  <div class="grid2">
    <div><label>Database Username</label><input name="username" value="<?= h($_POST['username'] ?? '') ?>" required></div>
    <div><label>Database Password</label><input name="password" type="password"></div>
  </div>
  <button class="btn" type="submit">Test Connection &amp; Import Schema &rarr;</button>
</form>
