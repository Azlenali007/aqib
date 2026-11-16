<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';

if (AdminAuth::check()) {
    Response::redirect('/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('admin_auth');
    $res = AdminAuth::attemptLogin((string) ($_POST['login'] ?? ''), (string) ($_POST['password'] ?? ''));
    if ($res['success']) {
        Response::redirect('/admin/dashboard.php');
    }
    $error = $res['error'];
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — <?= e((string) get_setting('site_name', 'SMM Panel')) ?></title>
  <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;background:linear-gradient(150deg,#24143D 0%,#4C33D6 100%)">
<div class="adm-card" style="max-width:420px;width:100%;padding:28px">
  <h1 style="margin:0 0 6px;font-size:22px;color:#24143D">Admin Sign In</h1>
  <p style="margin:0 0 18px;font-size:13.5px;color:#777083">Authorized panel administrators only</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field('admin_auth') ?>
    <div class="form-group"><label class="form-label">Username or Email</label><input class="form-control" name="login" required autofocus></div>
    <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn btn-primary" style="width:100%" type="submit">Sign In to Admin</button>
  </form>
</div>
</body>
</html>
