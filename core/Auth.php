<?php
declare(strict_types=1);

class Auth
{
    public static function check(): bool
    {
        Session::start('user');
        $uid = Session::get('user_id');
        if (!is_int($uid) && !ctype_digit((string) $uid)) {
            return false;
        }
        $token = Session::get('user_session_token');
        if (!is_string($token) || $token === '') {
            return false;
        }
        $sess = Database::fetchOne(
            'SELECT id, expires_at, is_revoked FROM user_sessions WHERE user_type = "user" AND user_id = ? AND session_token = ? LIMIT 1',
            [(int) $uid, $token]
        );
        if (!$sess || (int) $sess['is_revoked'] === 1 || strtotime($sess['expires_at']) < time()) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $uid = (int) Session::get('user_id');
        $user = Database::fetchOne(
            'SELECT u.*, w.balance, w.total_deposited, w.total_spent, w.total_refunded
             FROM users u
             LEFT JOIN wallets w ON w.user_id = u.id
             WHERE u.id = ? LIMIT 1',
            [$uid]
        );
        if (!$user || $user['status'] !== 'active') {
            self::logout();
            return null;
        }
        Database::execute('UPDATE user_sessions SET last_activity_at = NOW() WHERE session_token = ?', [Session::get('user_session_token')]);
        return $user;
    }

    public static function requireAuth(): array
    {
        $user = self::user();
        if ($user === null) {
            Session::flash('error', 'Please log in to continue.');
            Response::redirect('/user/login.php');
        }
        return $user;
    }

    public static function attemptLogin(string $login, string $password): array
    {
        $login = trim($login);
        $rl = Security::checkLoginRateLimit('user', $login);
        if (!$rl['allowed']) {
            return ['success' => false, 'error' => "Too many failed login attempts. Please wait {$rl['minutes']} minutes."];
        }

        $user = Database::fetchOne(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            Security::recordLoginAttempt('user', $login, false);
            return ['success' => false, 'error' => 'Invalid username/email or password.'];
        }
        if ($user['status'] === 'suspended') {
            return ['success' => false, 'error' => 'Your account has been suspended. Please contact support.'];
        }
        if ($user['status'] === 'pending' && get_setting('email_verification_required', '0') === '1') {
            return ['success' => false, 'error' => 'Please verify your email address before logging in.'];
        }

        Security::recordLoginAttempt('user', $login, true);
        self::createSession((int) $user['id']);
        if (!empty($user['preferred_currency'])) {
            Session::set('display_currency', strtoupper((string) $user['preferred_currency']));
        }
        Database::execute(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [Security::clientIp(), $user['id']]
        );
        Logger::activity('user', (int) $user['id'], 'user.login');

        return ['success' => true, 'user' => $user];
    }

    public static function createSession(int $userId): void
    {
        Session::start('user');
        Session::regenerate();
        $token = Security::randomToken(32);
        $mins = max(15, (int) get_setting('session_lifetime_minutes', 120));
        $expires = date('Y-m-d H:i:s', time() + ($mins * 60));

        Database::execute(
            'INSERT INTO user_sessions (user_type, user_id, session_token, ip_address, user_agent, expires_at)
             VALUES ("user", ?, ?, ?, ?, ?)',
            [$userId, $token, Security::clientIp(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250), $expires]
        );
        Session::set('user_id', $userId);
        Session::set('user_session_token', $token);
    }

    public static function register(array $input): array
    {
        if (get_setting('registration_enabled', '1') !== '1') {
            return ['success' => false, 'error' => 'New user registration is currently disabled.'];
        }

        $validator = new Validator();
        if (!$validator->validate($input, [
            'username' => 'required|alnum_dash|min:3|max:40',
            'email' => 'required|email|max:160',
            'full_name' => 'required|min:2|max:120',
            'password' => 'required|min:8|max:128',
        ])) {
            return ['success' => false, 'error' => $validator->firstError()];
        }

        if (($input['password'] ?? '') !== ($input['password_confirmation'] ?? $input['password'])) {
            return ['success' => false, 'error' => 'Passwords do not match.'];
        }

        $username = trim($input['username']);
        $email = strtolower(trim($input['email']));

        $exists = Database::fetchOne(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        );
        if ($exists) {
            return ['success' => false, 'error' => 'Username or email is already registered.'];
        }

        $referrerId = null;
        $refCode = trim((string) ($input['referral_code'] ?? ''));
        if ($refCode !== '') {
            $refUser = Database::fetchOne('SELECT id, last_login_ip FROM users WHERE referral_code = ? AND status = "active" LIMIT 1', [$refCode]);
            if ($refUser && $refUser['last_login_ip'] !== Security::clientIp()) {
                $referrerId = (int) $refUser['id'];
            }
        }

        $verifyRequired = get_setting('email_verification_required', '0') === '1';
        $verifyToken = $verifyRequired ? Security::randomToken(24) : null;
        $status = $verifyRequired ? 'pending' : 'active';
        $myRefCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $username), 0, 6)) . strtoupper(bin2hex(random_bytes(3)));
        $baseCurrency = Currency::systemBase();

        $userId = Database::transaction(function () use ($username, $email, $input, $referrerId, $verifyToken, $status, $myRefCode, $baseCurrency) {
            $uid = (int) Database::insert(
                'INSERT INTO users (username, email, full_name, mobile, password_hash, preferred_currency, referral_code, referred_by_user_id, is_verified, email_verify_token, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $username,
                    $email,
                    trim($input['full_name']),
                    trim((string) ($input['mobile'] ?? '')),
                    Security::hashPassword($input['password']),
                    $baseCurrency,
                    $myRefCode,
                    $referrerId,
                    $verifyToken ? 0 : 1,
                    $verifyToken,
                    $status,
                ]
            );

            Database::execute(
                'INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, 0.0000)',
                [$uid, $baseCurrency]
            );

            return $uid;
        });

        Logger::activity('user', $userId, 'user.register', 'users', (string) $userId);

        if ($verifyRequired && $verifyToken !== null) {
            $verifyUrl = app_url('/user/verify-email.php?token=' . urlencode($verifyToken));
            Mailer::send($email, 'Verify Your Email Address', "<p>Welcome, " . Security::e($username) . "!</p><p>Please verify your email: <a href=\"$verifyUrl\">$verifyUrl</a></p>");
        }

        return ['success' => true, 'user_id' => $userId, 'requires_verification' => $verifyRequired];
    }

    public static function logout(): void
    {
        Session::start('user');
        $token = Session::get('user_session_token');
        if (is_string($token) && $token !== '') {
            try {
                Database::execute('UPDATE user_sessions SET is_revoked = 1 WHERE session_token = ?', [$token]);
            } catch (Throwable) {
            }
        }
        Session::destroy();
    }
}
