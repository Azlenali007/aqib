<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div class="card" style="max-width:440px;width:100%;padding:28px">
  <div style="text-align:center;margin-bottom:22px">
    <a href="/" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:8px">
      <svg width="38" height="38" viewBox="0 0 40 40" fill="none"><defs><linearGradient id="alg" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient></defs><rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#alg)"/><rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#alg)"/><rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#alg)"/><path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/><path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/></svg>
      <span class="brand-title" style="font-size:22px">SMM <span>Panel</span></span>
    </a>
    <p style="margin:0;color:#777083;font-size:14px">Sign in to manage orders and grow your channels</p>
  </div>
  <?php require dirname(__DIR__) . '/components/alerts.php'; ?>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field('auth') ?>
    <div class="form-group">
      <label class="form-label">Username or Email</label>
      <input class="form-control" name="login" value="<?= e($_POST['login'] ?? '') ?>" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;font-size:13px">
      <a href="/user/forgot-password.php" style="color:#7045D8;font-weight:700">Forgot Password?</a>
      <a href="/user/register.php" style="color:#7045D8;font-weight:700">Create Account</a>
    </div>
    <button class="btn btn-primary" style="width:100%" type="submit">Sign In</button>
  </form>
</div>
</body>
</html>
