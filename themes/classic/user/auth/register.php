<?php
declare(strict_types=1);
$siteName = (string) get_setting('site_name', 'SMM Panel');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Account — <?= e($siteName) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div class="card" style="max-width:480px;width:100%;padding:28px">
  <div style="text-align:center;margin-bottom:20px">
    <div class="brand-title" style="font-size:22px">Create <span>Account</span></div>
    <p style="margin:4px 0 0;color:#777083;font-size:14px">Join <?= e($siteName) ?> to place automated SMM orders</p>
  </div>
  <?php if (!empty($error)): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field('auth') ?>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Username</label><input class="form-control" name="username" value="<?= e($_POST['username'] ?? '') ?>" required></div>
      <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required></div>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Email Address</label><input class="form-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
      <div class="form-group"><label class="form-label">Mobile (optional)</label><input class="form-control" name="mobile" value="<?= e($_POST['mobile'] ?? '') ?>"></div>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Password (min 8 chars)</label><input class="form-control" type="password" name="password" minlength="8" required></div>
      <div class="form-group"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="password_confirmation" minlength="8" required></div>
    </div>
    <div class="form-group">
      <label class="form-label">Referral Code (optional)</label>
      <input class="form-control" name="referral_code" value="<?= e($_POST['referral_code'] ?? Session::get('referral_code', '')) ?>">
    </div>
    <button class="btn btn-primary" style="width:100%" type="submit">Create Account</button>
  </form>
  <p style="text-align:center;margin:16px 0 0;font-size:13.5px;color:#777083">
    Already have an account? <a href="/user/login.php" style="color:#7045D8;font-weight:700">Sign In</a>
  </p>
</div>
</body>
</html>
