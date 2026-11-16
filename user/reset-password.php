<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('auth');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirmation'] ?? '');
    if (strlen($password) < 8 || $password !== $confirm) {
        $error = 'Passwords must match and be at least 8 characters.';
    } else {
        $hash = hash('sha256', $token);
        $reset = Database::fetchOne(
            'SELECT * FROM password_resets WHERE user_type = "user" AND token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$hash]
        );
        if (!$reset) {
            $error = 'Invalid or expired password reset token.';
        } else {
            Database::execute('UPDATE users SET password_hash = ? WHERE email = ?', [Security::hashPassword($password), $reset['email']]);
            Database::execute('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$reset['id']]);
            $success = 'Your password has been reset successfully. You may now log in.';
        }
    }
}

$theme = get_active_theme();
require SMM_THEMES . '/' . $theme . '/user/auth/reset-password.php';
