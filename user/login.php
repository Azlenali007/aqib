<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

if (Auth::check()) {
    Response::redirect('/user/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('auth');
    $res = Auth::attemptLogin((string) ($_POST['login'] ?? ''), (string) ($_POST['password'] ?? ''));
    if ($res['success']) {
        Response::redirect('/user/index.php');
    }
    $error = $res['error'];
}

$theme = get_active_theme();
require SMM_THEMES . '/' . $theme . '/user/auth/login.php';
