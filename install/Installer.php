<?php
declare(strict_types=1);

class Installer
{
    /**
     * Connect to the target MySQL database.
     * Connects directly first (cPanel shared hosting safe when user lacks global CREATE DATABASE privilege),
     * and only attempts CREATE DATABASE if MySQL returns error 1049 (Unknown database).
     */
    public static function connectDatabase(string $host, int $port, string $db, string $user, string $pass): PDO
    {
        $dbClean = str_replace('`', '', trim($db));
        if ($dbClean === '') {
            throw new RuntimeException('Database name cannot be empty.');
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbClean);
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $errCode = (int) ($e->errorInfo[1] ?? 0);
            if ($errCode !== 1049) {
                throw $e;
            }
            $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsnNoDb, $user, $pass, $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbClean}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbClean}`");
        }

        $activeDb = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        if (strcasecmp($activeDb, $dbClean) !== 0) {
            throw new RuntimeException("Connected to unexpected database '{$activeDb}' instead of '{$dbClean}'.");
        }

        return $pdo;
    }

    /**
     * Quote-aware, comment-stripping SQL statement splitter.
     * Never drops an INSERT/CREATE statement just because a SQL comment precedes it.
     */
    public static function splitSql(string $sql): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
        $statements = [];
        $buf = '';
        $len = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $escaped = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($inSingle || $inDouble || $inBacktick) {
                $buf .= $ch;
                if ($escaped) {
                    $escaped = false;
                } elseif ($ch === '\\' && ($inSingle || $inDouble)) {
                    $escaped = true;
                } elseif ($ch === "'" && $inSingle) {
                    if ($next === "'") {
                        $buf .= $next;
                        $i++;
                    } else {
                        $inSingle = false;
                    }
                } elseif ($ch === '"' && $inDouble) {
                    if ($next === '"') {
                        $buf .= $next;
                        $i++;
                    } else {
                        $inDouble = false;
                    }
                } elseif ($ch === '`' && $inBacktick) {
                    $inBacktick = false;
                }
                continue;
            }

            // Line comments: -- ... or # ...
            if (($ch === '-' && $next === '-' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) || $ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $buf .= "\n";
                continue;
            }

            // Block comments: /* ... */
            if ($ch === '/' && $next === '*') {
                $i += 2;
                while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                $buf .= ' ';
                continue;
            }

            if ($ch === "'") {
                $inSingle = true;
                $buf .= $ch;
                continue;
            }
            if ($ch === '"') {
                $inDouble = true;
                $buf .= $ch;
                continue;
            }
            if ($ch === '`') {
                $inBacktick = true;
                $buf .= $ch;
                continue;
            }

            if ($ch === ';') {
                $trimmed = trim($buf);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        $tail = trim($buf);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    public static function executeSqlFile(PDO $pdo, string $filePath): int
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Missing SQL file: ' . basename($filePath));
        }
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new RuntimeException('Unable to read SQL file: ' . basename($filePath));
        }
        $statements = self::splitSql($raw);
        $count = 0;
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
            $count++;
        }
        return $count;
    }

    /**
     * Step 2 + Step 3 + Step 4 + Step 5: Create tables, seed defaults, verify required tables,
     * ensure default roles exist, and return the verified Super Admin role ID.
     */
    public static function installSchemaAndRoles(PDO $pdo, string $rootDir): int
    {
        self::executeSqlFile($pdo, $rootDir . '/database/schema.sql');
        self::executeSqlFile($pdo, $rootDir . '/database/seeds/default_data.sql');

        foreach (['001_initial_schema.sql', '002_seed_defaults.sql'] as $mig) {
            $migPath = $rootDir . '/database/migrations/' . $mig;
            if (is_file($migPath)) {
                self::executeSqlFile($pdo, $migPath);
            }
        }

        self::verifyRequiredTables($pdo);
        return self::ensureDefaultRoles($pdo);
    }

    public static function verifyRequiredTables(PDO $pdo): void
    {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $required = [
            'roles', 'permissions', 'role_permissions', 'admins',
            'currencies', 'exchange_rates', 'users', 'wallets',
            'categories', 'services', 'orders', 'payment_gateways',
            'settings', 'migrations',
        ];
        foreach ($required as $tbl) {
            if (!in_array($tbl, $tables, true)) {
                throw new RuntimeException("Required database table '{$tbl}' was not created.");
            }
        }
    }

    /**
     * Ensure default roles and permissions exist idempotently (safe on retry),
     * then query and return the exact Super Admin role ID from MySQL.
     */
    public static function ensureDefaultRoles(PDO $pdo): int
    {
        $defaultRoles = [
            ['Super Admin', 'super_admin', 'Full unrestricted access to all modules and security settings'],
            ['Admin', 'admin', 'Full operational access excluding destructive system overrides'],
            ['Support Staff', 'support_staff', 'Manage support tickets, view orders and user profiles'],
            ['Finance Manager', 'finance_manager', 'Manage deposits, manual payment approvals, wallet ledger and financial reports'],
            ['Order Manager', 'order_manager', 'Manage orders, refills, services, categories and providers'],
        ];

        $insRole = $pdo->prepare(
            'INSERT IGNORE INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES (?, ?, ?, 1)'
        );
        foreach ($defaultRoles as $r) {
            $insRole->execute($r);
        }

        // Ensure super_admin has all existing permissions in role_permissions
        $pdo->exec(
            "INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
             SELECT r.`id`, p.`id` FROM `roles` r CROSS JOIN `permissions` p WHERE r.`slug` = 'super_admin'"
        );

        $stmt = $pdo->prepare("SELECT `id`, `name`, `slug` FROM `roles` WHERE `slug` = 'super_admin' LIMIT 1");
        $stmt->execute();
        $superRole = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$superRole || (int) $superRole['id'] <= 0) {
            throw new RuntimeException('Failed to verify Super Admin role in the roles table.');
        }

        return (int) $superRole['id'];
    }

    /**
     * Step 4 -> 7: Ensure Super Admin role exists, retrieve actual role_id,
     * create (or safely verify/update on retry) the initial admin account,
     * and verify the foreign key relationship before returning.
     */
    public static function createOrVerifyInitialAdmin(
        PDO $pdo,
        string $username,
        string $email,
        string $fullName,
        string $password
    ): array {
        $username = trim($username);
        $email = strtolower(trim($email));
        $fullName = trim($fullName) !== '' ? trim($fullName) : 'Super Admin';

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new InvalidArgumentException('Valid admin username, email, and password (minimum 8 characters) are required.');
        }

        $started = !$pdo->inTransaction();
        if ($started) {
            $pdo->beginTransaction();
        }

        try {
            self::verifyRequiredTables($pdo);
            $superRoleId = self::ensureDefaultRoles($pdo);

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if (!is_string($passwordHash) || $passwordHash === '') {
                throw new RuntimeException('Failed to hash administrator password.');
            }

            // Check for existing admin (retry safety — never duplicate admins)
            $findStmt = $pdo->prepare(
                'SELECT a.`id`, a.`role_id`, a.`username`, a.`email`, a.`status`, a.`two_factor_enabled`, r.`slug` AS role_slug
                 FROM `admins` a
                 LEFT JOIN `roles` r ON r.`id` = a.`role_id`
                 WHERE a.`username` = ? OR a.`email` = ?
                 ORDER BY a.`id` ASC
                 LIMIT 1'
            );
            $findStmt->execute([$username, $email]);
            $existing = $findStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Safely verify/repair existing admin record on retry without creating a duplicate
                $upd = $pdo->prepare(
                    'UPDATE `admins`
                     SET `role_id` = ?, `username` = ?, `email` = ?, `full_name` = ?, `password_hash` = ?, `status` = "active", `two_factor_enabled` = 0
                     WHERE `id` = ?'
                );
                $upd->execute([$superRoleId, $username, $email, $fullName, $passwordHash, (int) $existing['id']]);
                $adminId = (int) $existing['id'];
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO `admins` (`role_id`, `username`, `email`, `full_name`, `password_hash`, `status`, `two_factor_enabled`)
                     VALUES (?, ?, ?, ?, ?, "active", 0)'
                );
                $ins->execute([$superRoleId, $username, $email, $fullName, $passwordHash]);
                $adminId = (int) $pdo->lastInsertId();
            }

            // Step 7: Explicitly verify created/updated admin record AND fk_admins_role join integrity
            $verifyStmt = $pdo->prepare(
                'SELECT a.`id`, a.`role_id`, a.`username`, a.`email`, a.`full_name`, a.`status`, a.`two_factor_enabled`,
                        r.`slug` AS role_slug, r.`name` AS role_name
                 FROM `admins` a
                 INNER JOIN `roles` r ON r.`id` = a.`role_id`
                 WHERE a.`id` = ? AND a.`role_id` = ? AND r.`slug` = "super_admin"
                 LIMIT 1'
            );
            $verifyStmt->execute([$adminId, $superRoleId]);
            $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);

            if (!$verified) {
                throw new RuntimeException('Post-insert verification failed: Admin role foreign key relationship could not be confirmed.');
            }

            if ($started && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return [
                'admin_id' => $adminId,
                'role_id' => $superRoleId,
                'username' => $verified['username'],
                'email' => $verified['email'],
                'role_slug' => $verified['role_slug'],
                'existed' => (bool) $existing,
            ];
        } catch (Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
