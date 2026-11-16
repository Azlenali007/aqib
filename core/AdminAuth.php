<?php
declare(strict_types=1);

class AdminAuth
{
    public static function check(): bool
    {
        Session::start('admin');
        $aid = Session::get('admin_id');
        $token = Session::get('admin_session_token');
        if ((!$aid && !ctype_digit((string) $aid)) || !is_string($token) || $token === '') {
            return false;
        }
        $sess = Database::fetchOne(
            'SELECT id, expires_at, is_revoked FROM user_sessions WHERE user_type = "admin" AND user_id = ? AND session_token = ? LIMIT 1',
            [(int) $aid, $token]
        );
        if (!$sess || (int) $sess['is_revoked'] === 1 || strtotime($sess['expires_at']) < time()) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function admin(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $aid = (int) Session::get('admin_id');
        $admin = Database::fetchOne(
            'SELECT a.*, r.name AS role_name, r.slug AS role_slug
             FROM admins a
             INNER JOIN roles r ON r.id = a.role_id
             WHERE a.id = ? LIMIT 1',
            [$aid]
        );
        if (!$admin || $admin['status'] !== 'active') {
            self::logout();
            return null;
        }
        Database::execute('UPDATE user_sessions SET last_activity_at = NOW() WHERE session_token = ?', [Session::get('admin_session_token')]);
        return $admin;
    }

    public static function requireAuth(?string $permission = null): array
    {
        $admin = self::admin();
        if ($admin === null) {
            Response::redirect('/admin/login.php');
        }
        if ($permission !== null && !self::can($permission, $admin)) {
            http_response_code(403);
            exit('Access denied: Missing required permission (' . Security::e($permission) . ').');
        }
        return $admin;
    }

    public static function can(string $permission, ?array $admin = null): bool
    {
        $admin ??= self::admin();
        if (!$admin) {
            return false;
        }
        if ($admin['role_slug'] === 'super_admin') {
            return true;
        }
        $has = Database::fetchValue(
            'SELECT 1 FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.perm_key = ? LIMIT 1',
            [(int) $admin['role_id'], $permission]
        );
        return $has !== null;
    }

    public static function attemptLogin(string $login, string $password): array
    {
        $login = trim($login);
        $rl = Security::checkLoginRateLimit('admin', $login);
        if (!$rl['allowed']) {
            return ['success' => false, 'error' => "Too many failed admin login attempts. Wait {$rl['minutes']} minutes."];
        }

        $admin = Database::fetchOne(
            'SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
        if (!$admin || !Security::verifyPassword($password, $admin['password_hash'])) {
            Security::recordLoginAttempt('admin', $login, false);
            return ['success' => false, 'error' => 'Invalid admin credentials.'];
        }
        if ($admin['status'] !== 'active') {
            return ['success' => false, 'error' => 'This admin account is suspended.'];
        }

        Security::recordLoginAttempt('admin', $login, true);
        Session::start('admin');
        Session::regenerate();
        $token = Security::randomToken(32);
        $expires = date('Y-m-d H:i:s', time() + 7200);

        Database::execute(
            'INSERT INTO user_sessions (user_type, user_id, session_token, ip_address, user_agent, expires_at)
             VALUES ("admin", ?, ?, ?, ?, ?)',
            [(int) $admin['id'], $token, Security::clientIp(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250), $expires]
        );
        Session::set('admin_id', (int) $admin['id']);
        Session::set('admin_session_token', $token);

        Database::execute(
            'UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [Security::clientIp(), $admin['id']]
        );
        Logger::activity('admin', (int) $admin['id'], 'admin.login');

        return ['success' => true, 'admin' => $admin];
    }

    public static function logout(): void
    {
        Session::start('admin');
        $token = Session::get('admin_session_token');
        if (is_string($token) && $token !== '') {
            try {
                Database::execute('UPDATE user_sessions SET is_revoked = 1 WHERE session_token = ?', [$token]);
            } catch (Throwable) {
            }
        }
        Session::destroy();
    }
}
