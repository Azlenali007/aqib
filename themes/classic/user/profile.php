<?php
declare(strict_types=1);

$pageTitle = 'Profile';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $mobile = trim((string) ($_POST['mobile'] ?? ''));
        if ($fullName === '') {
            Session::flash('error', 'Full name is required.');
        } else {
            Database::execute('UPDATE users SET full_name = ?, mobile = ? WHERE id = ?', [$fullName, $mobile, (int) $user['id']]);
            Session::flash('success', 'Profile updated.');
        }
    } elseif ($action === 'change_password') {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $conf = (string) ($_POST['confirm_password'] ?? '');
        if (!Security::verifyPassword($cur, $user['password_hash'])) {
            Session::flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8 || $new !== $conf) {
            Session::flash('error', 'New password must be at least 8 characters and match.');
        } else {
            Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [Security::hashPassword($new), (int) $user['id']]);
            Session::flash('success', 'Password changed successfully.');
        }
    }
    Response::redirect('/user/index.php?page=profile');
}

require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title"><?= e($user['full_name'] ?: $user['username']) ?></h1>
      <p class="page-banner-sub">User ID: #<?= (int) $user['id'] ?> &bull; Balance: <?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=settings" class="view-all-pill">Preferences &rarr;</a>
</div>

<div class="grid-2">
  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">Profile Information</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div class="form-group"><label class="form-label">Username</label><input class="form-control" readonly value="<?= e($user['username']) ?>"></div>
      <div class="form-group"><label class="form-label">Email Address</label><input class="form-control" readonly value="<?= e($user['email']) ?>"></div>
      <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
      <div class="form-group"><label class="form-label">Mobile Number</label><input class="form-control" name="mobile" value="<?= e($user['mobile'] ?? '') ?>"></div>
      <button class="btn btn-primary" type="submit">Save Profile</button>
    </form>
  </div>

  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">Change Password</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <div class="form-group"><label class="form-label">Current Password</label><input class="form-control" type="password" name="current_password" required></div>
      <div class="form-group"><label class="form-label">New Password</label><input class="form-control" type="password" name="new_password" minlength="8" required></div>
      <div class="form-group"><label class="form-label">Confirm New Password</label><input class="form-control" type="password" name="confirm_password" minlength="8" required></div>
      <button class="btn btn-primary" type="submit">Update Password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
