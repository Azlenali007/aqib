<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div class="card" style="max-width:440px;width:100%;padding:28px">
  <h2 style="margin:0 0 8px">Reset Password</h2>
  <p style="margin:0 0 18px;color:#777083;font-size:14px">Enter your registered email to receive a password reset link.</p>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field('auth') ?>
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input class="form-control" type="email" name="email" required>
    </div>
    <button class="btn btn-primary" style="width:100%" type="submit">Send Reset Link</button>
  </form>
  <p style="text-align:center;margin:16px 0 0;font-size:13.5px"><a href="/user/login.php" style="color:#7045D8;font-weight:700">&larr; Back to Login</a></p>
</div>
</body>
</html>
