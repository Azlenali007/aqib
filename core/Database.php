<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(?array $overrideConfig = null): PDO
    {
        if (self::$pdo !== null && $overrideConfig === null) {
            return self::$pdo;
        }

        $cfg = $overrideConfig ?? require SMM_CONFIG . '/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            (int) ($cfg['port'] ?? 3306),
            $cfg['database'],
            $cfg['charset'] ?? 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        $pdo = new PDO($dsn, (string) $cfg['username'], (string) $cfg['password'], $options);
        if ($overrideConfig === null) {
            self::$pdo = $pdo;
        }
        return $pdo;
    }

    public static function pdo(): PDO
    {
        return self::connect();
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function isConfigured(): bool
    {
        return is_file(SMM_INSTALL_LOCK) && is_file(SMM_CONFIG . '/db_credentials.php');
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchValue(string $sql, array $params = [], mixed $default = null): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val === false ? $default : $val;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): string
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $started = !$pdo->inTransaction();
        if ($started) {
            $pdo->beginTransaction();
        }
        try {
            $result = $callback($pdo);
            if ($started && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
