<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set New Password — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div class="card" style="max-width:440px;width:100%;padding:28px">
  <h2 style="margin:0 0 8px">Set New Password</h2>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
    <a class="btn btn-primary" style="width:100%" href="/user/login.php">Sign In Now</a>
  <?php else: ?>
    <form method="post">
      <?= csrf_field('auth') ?>
      <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
      <div class="form-group"><label class="form-label">New Password</label><input class="form-control" type="password" name="password" minlength="8" required></div>
      <div class="form-group"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" minlength="8" required></div>
      <button class="btn btn-primary" style="width:100%" type="submit">Update Password</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
