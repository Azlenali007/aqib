<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
$siteTagline = (string) get_setting('site_tagline', 'Grow Your Social Media');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:24px 16px">
<div style="width:100%;max-width:440px">
  <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <a href="/public/index.php?page=home" class="view-all-pill">&larr; Back to Home</a>
    <a href="/public/index.php?page=services" style="font-size:13px;font-weight:700;color:var(--primary)">Browse Services</a>
  </div>
  <div class="card" style="padding:30px 26px;margin-bottom:0">
    <div style="text-align:center;margin-bottom:22px">
      <a href="/public/index.php?page=home" style="display:inline-flex;align-items:center;gap:10px;margin-bottom:8px">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true"><defs><linearGradient id="alg" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient></defs><rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#alg)"/><rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#alg)"/><rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#alg)"/><path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/><path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/></svg>
        <div style="text-align:left">
          <div class="brand-title" style="font-size:21px">SMM <span>Panel</span></div>
          <div class="brand-sub"><?= e($siteTagline) ?></div>
        </div>
      </a>
      <h1 style="margin:8px 0 4px;font-size:20px;font-weight:800">Welcome Back</h1>
      <p style="margin:0;color:var(--muted);font-size:13.5px">Sign in to manage orders and grow your channels</p>
    </div>
    <?php require dirname(__DIR__) . '/components/alerts.php'; ?>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field('auth') ?>
      <div class="form-group">
        <label class="form-label">Username or Email</label>
        <input class="form-control" name="login" value="<?= e($_POST['login'] ?? '') ?>" placeholder="Enter your username or email" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-control" type="password" name="password" placeholder="Enter your password" required>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-size:13px">
        <a href="/user/forgot-password.php" style="color:var(--primary);font-weight:700">Forgot Password?</a>
        <a href="/user/register.php" style="color:var(--primary);font-weight:700">Create Account</a>
      </div>
      <button class="btn btn-primary" style="width:100%" type="submit">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
