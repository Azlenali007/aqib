<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$token = trim((string) ($_GET['token'] ?? ''));
if ($token !== '') {
    $user = Database::fetchOne('SELECT id FROM users WHERE email_verify_token = ? LIMIT 1', [$token]);
    if ($user) {
        Database::execute(
            'UPDATE users SET is_verified = 1, email_verified_at = NOW(), email_verify_token = NULL, status = "active" WHERE id = ?',
            [$user['id']]
        );
        Session::start('user');
        Session::flash('success', 'Your email address has been verified! You can now log in.');
        Response::redirect('/user/login.php');
    }
}
Session::start('user');
Session::flash('error', 'Invalid or expired verification link.');
Response::redirect('/user/login.php');
