<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

if (Auth::check()) {
    Response::redirect('/user/index.php');
}

if (!empty($_GET['ref'])) {
    Session::start('user');
    Session::set('referral_code', trim((string) $_GET['ref']));
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('auth');
    $input = $_POST;
    if (empty($input['referral_code']) && Session::get('referral_code')) {
        $input['referral_code'] = Session::get('referral_code');
    }
    $res = Auth::register($input);
    if ($res['success']) {
        if (!empty($res['requires_verification'])) {
            $success = 'Account created! Please check your email to verify your account before logging in.';
        } else {
            Auth::createSession((int) $res['user_id']);
            Session::flash('success', 'Welcome! Your account has been created.');
            Response::redirect('/user/index.php');
        }
    } else {
        $error = $res['error'];
    }
}

$theme = get_active_theme();
require SMM_THEMES . '/' . $theme . '/user/auth/register.php';
