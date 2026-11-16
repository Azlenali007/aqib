<?php
declare(strict_types=1);

class Security
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($password, $algo);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    public static function csrfToken(string $scope = 'default'): string
    {
        Session::start();
        if (!isset($_SESSION['_csrf'][$scope]) || !is_array($_SESSION['_csrf'][$scope])) {
            $_SESSION['_csrf'][$scope] = [
                'token' => self::randomToken(32),
                'created_at' => time(),
            ];
        }
        $ttl = (int) ((require SMM_CONFIG . '/security.php')['csrf_ttl_seconds'] ?? 7200);
        if ((time() - (int) $_SESSION['_csrf'][$scope]['created_at']) > $ttl) {
            $_SESSION['_csrf'][$scope] = [
                'token' => self::randomToken(32),
                'created_at' => time(),
            ];
        }
        return $_SESSION['_csrf'][$scope]['token'];
    }

    public static function verifyCsrf(?string $token, string $scope = 'default'): bool
    {
        Session::start();
        $stored = $_SESSION['_csrf'][$scope]['token'] ?? null;
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function requireCsrf(string $scope = 'default'): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::verifyCsrf(is_string($token) ? $token : null, $scope)) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                Response::json(['success' => false, 'error' => 'Invalid or expired CSRF token. Please refresh and try again.'], 419);
            }
            http_response_code(419);
            exit('Invalid or expired security token (CSRF). Please go back, refresh, and try again.');
        }
    }

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        $cfg = require SMM_CONFIG . '/security.php';
        $key = hash('sha256', (string) $cfg['app_key'], true);
        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(16);
            $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            if ($cipher !== false) {
                $mac = hash_hmac('sha256', $iv . $cipher, $key, true);
                return 'v1:' . base64_encode($iv . $mac . $cipher);
            }
        }
        return 'plain:' . base64_encode($plaintext);
    }

    public static function decrypt(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }
        if (str_starts_with($encoded, 'plain:')) {
            return (string) base64_decode(substr($encoded, 6), true);
        }
        if (!str_starts_with($encoded, 'v1:')) {
            return $encoded;
        }
        $raw = base64_decode(substr($encoded, 3), true);
        if ($raw === false || strlen($raw) < 49) {
            return '';
        }
        $cfg = require SMM_CONFIG . '/security.php';
        $key = hash('sha256', (string) $cfg['app_key'], true);
        $iv = substr($raw, 0, 16);
        $mac = substr($raw, 16, 32);
        $cipher = substr($raw, 48);
        $calcMac = hash_hmac('sha256', $iv . $cipher, $key, true);
        if (!hash_equals($mac, $calcMac)) {
            return '';
        }
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    public static function checkLoginRateLimit(string $scope, string $identifier): array
    {
        $cfg = require SMM_CONFIG . '/security.php';
        $max = (int) ($cfg['rate_limit']['max_attempts'] ?? 5);
        $mins = (int) ($cfg['rate_limit']['window_minutes'] ?? 15);
        $ip = self::clientIp();

        $count = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM login_attempts
             WHERE scope = ? AND (ip_address = ? OR identifier = ?)
               AND succeeded = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [$scope, $ip, $identifier, $mins],
            0
        );

        return [
            'allowed' => $count < $max,
            'attempts' => $count,
            'max' => $max,
            'minutes' => $mins,
        ];
    }

    public static function recordLoginAttempt(string $scope, string $identifier, bool $succeeded): void
    {
        Database::execute(
            'INSERT INTO login_attempts (scope, identifier, ip_address, succeeded) VALUES (?, ?, ?, ?)',
            [$scope, mb_substr($identifier, 0, 160), self::clientIp(), $succeeded ? 1 : 0]
        );
    }

    public static function sanitizeRedirect(string $target, string $fallback = '/'): string
    {
        $target = trim($target);
        if ($target === '' || str_starts_with($target, '//') || str_contains($target, '://')) {
            return $fallback;
        }
        return str_starts_with($target, '/') ? $target : '/' . $target;
    }
}
