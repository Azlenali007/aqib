<?php
declare(strict_types=1);

$pageTitle = 'Account Settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'preferences') {
        $curr = strtoupper(trim((string) ($_POST['preferred_currency'] ?? Currency::systemBase())));
        $tz = trim((string) ($_POST['timezone'] ?? 'Asia/Kolkata'));
        $lang = trim((string) ($_POST['language'] ?? 'en'));
        Database::execute('UPDATE users SET preferred_currency = ?, timezone = ?, language = ? WHERE id = ?', [$curr, $tz, $lang, (int) $user['id']]);
        $_SESSION['display_currency'] = $curr;
        Session::flash('success', 'Account preferences updated.');
    } elseif ($action === 'revoke_sessions') {
        $curToken = Session::get('user_session_token');
        Database::execute('UPDATE user_sessions SET is_revoked = 1 WHERE user_type = "user" AND user_id = ? AND session_token <> ?', [(int) $user['id'], $curToken]);
        Session::flash('success', 'All other active sessions have been signed out.');
    }
    Response::redirect('/user/index.php?page=settings');
}

$sessions = Database::fetchAll('SELECT * FROM user_sessions WHERE user_type = "user" AND user_id = ? AND is_revoked = 0 ORDER BY last_activity_at DESC LIMIT 10', [(int) $user['id']]);
require __DIR__ . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="card">
    <h1 class="section-title" style="margin-bottom:14px">Display &amp; Regional Settings</h1>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="preferences">
      <div class="form-group">
        <label class="form-label">Preferred Display Currency</label>
        <select name="preferred_currency" class="form-select">
          <?php foreach ($activeCurrencies as $c): ?>
            <option value="<?= e($c['code']) ?>" <?= $displayCurrency === $c['code'] ? 'selected' : '' ?>><?= e($c['code']) ?> (<?= e($c['symbol']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Time Zone</label>
        <select name="timezone" class="form-select">
          <?php foreach (['Asia/Kolkata', 'UTC', 'America/New_York', 'Europe/London', 'Asia/Dubai'] as $tz): ?>
            <option value="<?= e($tz) ?>" <?= ($user['timezone'] ?? 'Asia/Kolkata') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Language</label>
        <select name="language" class="form-select"><option value="en" selected>English</option></select>
      </div>
      <button class="btn btn-primary" type="submit">Save Preferences</button>
    </form>
  </div>

  <div class="card">
    <div class="section-head">
      <h2 class="section-title">Active Login Sessions</h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="revoke_sessions"><button class="btn btn-outline btn-sm" type="submit">Sign Out Others</button></form>
    </div>
    <div class="table-responsive">
      <table class="data-table" style="min-width:380px">
        <thead><tr><th>IP Address</th><th>Last Activity</th><th>Expires</th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
          <tr>
            <td><?= e($s['ip_address']) ?></td>
            <td><?= e(time_ago($s['last_activity_at'])) ?></td>
            <td><?= e($s['expires_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
