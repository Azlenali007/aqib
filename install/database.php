<?php
declare(strict_types=1);

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
            $dsnNoDb = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsnNoDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '', $db) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . str_replace('`', '', $db) . "`");

            $schemaSql = file_get_contents(SMM_ROOT . '/database/schema.sql');
            $seedSql   = file_get_contents(SMM_ROOT . '/database/seeds/default_data.sql');

            foreach ([$schemaSql, $seedSql] as $sqlBlock) {
                $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sqlBlock)));
                foreach ($statements as $stmt) {
                    if ($stmt !== '' && !str_starts_with($stmt, '--')) {
                        $pdo->exec($stmt);
                    }
                }
            }

            $credPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                'host' => $host,
                'port' => $port,
                'database' => $db,
                'username' => $user,
                'password' => $pass,
                'charset' => 'utf8mb4',
            ], true) . ";\n";
            file_put_contents(SMM_CONFIG . '/db_credentials.php', $credPhp, LOCK_EX);

            $secretsPhp = "<?php\ndeclare(strict_types=1);\nreturn " . var_export([
                'app_key' => bin2hex(random_bytes(32)),
                'cron_token' => bin2hex(random_bytes(16)),
            ], true) . ";\n";
            file_put_contents(SMM_CONFIG . '/secrets.php', $secretsPhp, LOCK_EX);

            $_SESSION['install_db_ok'] = true;
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
