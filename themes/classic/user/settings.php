<?php
declare(strict_types=1);

$pageTitle = 'Account Settings';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'preferences') {
        $curr = strtoupper(trim((string) ($_POST['preferred_currency'] ?? Currency::systemBase())));
        $validCurr = Database::fetchOne('SELECT code FROM currencies WHERE code = ? AND is_active = 1 LIMIT 1', [$curr]);
        if (!$validCurr) {
            Session::flash('error', 'Invalid or inactive currency selected.');
            Response::redirect('/user/index.php?page=settings');
        }
        $tz = trim((string) ($_POST['timezone'] ?? 'Asia/Kolkata'));
        $lang = trim((string) ($_POST['language'] ?? 'en'));
        Database::execute('UPDATE users SET preferred_currency = ?, timezone = ?, language = ? WHERE id = ?', [$validCurr['code'], $tz, $lang, (int) $user['id']]);
        $_SESSION['display_currency'] = $validCurr['code'];
        $ctxCheck = Currency::getDisplayContext($validCurr['code']);
        if (!$ctxCheck['rate_available']) {
            Session::flash('error', $ctxCheck['warning'] ?? "Exchange rate unavailable for {$validCurr['code']}.");
        } else {
            Session::flash('success', "Account preferences updated. Display currency set to {$validCurr['code']}.");
        }
    } elseif ($action === 'revoke_sessions') {
        $curToken = Session::get('user_session_token');
        Database::execute('UPDATE user_sessions SET is_revoked = 1 WHERE user_type = "user" AND user_id = ? AND session_token <> ?', [(int) $user['id'], $curToken]);
        Session::flash('success', 'All other active sessions have been signed out.');
    }
    Response::redirect('/user/index.php?page=settings');
}

$sessions = Database::fetchAll('SELECT * FROM user_sessions WHERE user_type = "user" AND user_id = ? AND is_revoked = 0 ORDER BY last_activity_at DESC LIMIT 10', [(int) $user['id']]);
$baseCode = Currency::systemBase();
require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.85 1 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Display &amp; Regional Settings</h1>
      <p class="page-banner-sub">Manage your display currency, timezone, and active login sessions</p>
    </div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">Preferences</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="preferences">
      <div class="form-group">
        <label class="form-label">Preferred Display Currency</label>
        <select name="preferred_currency" class="form-select">
          <?php foreach ($activeCurrencies as $c):
            $hasRate = ($c['code'] === $baseCode) || (Currency::getExchangeRate($baseCode, $c['code']) !== null);
          ?>
            <option value="<?= e($c['code']) ?>" <?= $displayCurrency === $c['code'] ? 'selected' : '' ?>>
              <?= e($c['code']) ?> (<?= e($c['symbol']) ?>) — <?= e($c['name']) ?><?= $hasRate ? '' : ' [Rate N/A]' ?>
            </option>
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
