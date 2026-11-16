<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
$siteTagline = (string) get_setting('site_tagline', 'Grow Your Social Media');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set New Password — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:24px 16px">
<div style="width:100%;max-width:440px">
  <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
    <a href="/public/index.php?page=home" class="view-all-pill">&larr; Back to Home</a>
    <a href="/user/login.php" style="font-size:13px;font-weight:700;color:var(--primary)">Sign In</a>
  </div>
  <div class="card" style="padding:30px 26px;margin-bottom:0">
    <div style="text-align:center;margin-bottom:20px">
      <div class="brand-title" style="font-size:20px">SMM <span>Panel</span></div>
      <div class="brand-sub" style="margin-bottom:10px"><?= e($siteTagline) ?></div>
      <h1 style="margin:0;font-size:20px;font-weight:800">Set New Password</h1>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= e($success) ?></div>
      <a class="btn btn-primary" style="width:100%" href="/user/login.php">Sign In Now</a>
    <?php else: ?>
      <form method="post">
        <?= csrf_field('auth') ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <div class="form-group"><label class="form-label">New Password (min 8 chars)</label><input class="form-control" type="password" name="password" minlength="8" required autofocus></div>
        <div class="form-group"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" minlength="8" required></div>
        <button class="btn btn-primary" style="width:100%" type="submit">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
