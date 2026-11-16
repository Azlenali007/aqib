<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('auth');
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = Database::fetchOne('SELECT id, username, email FROM users WHERE email = ? LIMIT 1', [$email]);
        if ($user) {
            $rawToken = Security::randomToken(32);
            $hash = hash('sha256', $rawToken);
            $expires = date('Y-m-d H:i:s', time() + 3600);
            Database::execute(
                'INSERT INTO password_resets (user_type, email, token_hash, expires_at) VALUES ("user", ?, ?, ?)',
                [$email, $hash, $expires]
            );
            $resetUrl = app_url('/user/reset-password.php?token=' . urlencode($rawToken));
            Mailer::send($email, 'Password Reset Request', "<p>Hello " . e($user['username']) . ",</p><p>Reset your password using the link below (valid for 1 hour):</p><p><a href=\"$resetUrl\">$resetUrl</a></p>");
        }
        $success = 'If an account matches that email address, a password reset link has been sent.';
    }
}

$theme = get_active_theme();
require SMM_THEMES . '/' . $theme . '/user/auth/forgot-password.php';
