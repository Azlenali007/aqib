<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('settings.manage');
$adminTitle = 'System Settings, Backups & Cron Health';

if (isset($_GET['download_backup'])) {
    $file = basename((string) $_GET['download_backup']);
    $full = realpath(SMM_STORAGE . '/backups/' . $file);
    $base = realpath(SMM_STORAGE . '/backups');
    if ($full && $base && str_starts_with($full, $base) && is_file($full)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($full));
        readfile($full);
        exit;
    }
    Session::flash('error', 'Backup file not found.');
    Response::redirect('/admin/settings/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_general') {
        set_setting('site_name', trim((string) ($_POST['site_name'] ?? 'SMM Panel')), 'general');
        set_setting('site_tagline', trim((string) ($_POST['site_tagline'] ?? 'Grow Your Social Media')), 'general');
        set_setting('site_url', rtrim(trim((string) ($_POST['site_url'] ?? '')), '/'), 'general');
        set_setting('default_timezone', trim((string) ($_POST['default_timezone'] ?? 'Asia/Kolkata')), 'general');
        set_setting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0', 'general');
        set_setting('maintenance_message', trim((string) ($_POST['maintenance_message'] ?? '')), 'general');
        set_setting('registration_enabled', isset($_POST['registration_enabled']) ? '1' : '0', 'general');
        set_setting('email_verification_required', isset($_POST['email_verification_required']) ? '1' : '0', 'general');
        Logger::activity('admin', (int) $admin['id'], 'settings.general_update');
        Session::flash('success', 'General & maintenance settings updated.');
    } elseif ($action === 'save_smtp') {
        set_setting('smtp_host', trim((string) ($_POST['smtp_host'] ?? '')), 'smtp');
        set_setting('smtp_port', trim((string) ($_POST['smtp_port'] ?? '587')), 'smtp');
        set_setting('smtp_user', trim((string) ($_POST['smtp_user'] ?? '')), 'smtp');
        $pass = trim((string) ($_POST['smtp_pass'] ?? ''));
        if ($pass !== '') {
            set_setting('smtp_pass', Security::encrypt($pass), 'smtp', true);
        }
        set_setting('smtp_from_email', trim((string) ($_POST['smtp_from_email'] ?? '')), 'smtp');
        set_setting('smtp_from_name', trim((string) ($_POST['smtp_from_name'] ?? '')), 'smtp');
        Logger::activity('admin', (int) $admin['id'], 'settings.smtp_update');
        Session::flash('success', 'SMTP email configuration saved.');
    } elseif ($action === 'create_backup') {
        $res = SettingsService::createSqlBackup();
        Logger::activity('admin', (int) $admin['id'], 'settings.create_backup', 'backups', $res['filename'] ?? null);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "SQL Backup created: {$res['filename']}" : $res['error']);
    }
    Response::redirect('/admin/settings/index.php');
}

$backups = [];
$backupDir = SMM_STORAGE . '/backups';
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '/*.sql') ?: [] as $f) {
        $backups[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
    }
    usort($backups, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
}

$cronToken = (string) ((require SMM_CONFIG . '/app.php')['cron_token'] ?? 'CHANGE_ME');
$rootPath = SMM_ROOT;

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">General &amp; Maintenance Settings</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_general">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Site Name</label><input class="form-control" name="site_name" value="<?= e((string) get_setting('site_name', 'SMM Panel')) ?>" required></div>
        <div class="form-group"><label class="form-label">Site Tagline</label><input class="form-control" name="site_tagline" value="<?= e((string) get_setting('site_tagline', 'Grow Your Social Media')) ?>"></div>
        <div class="form-group"><label class="form-label">Canonical Site URL</label><input class="form-control" name="site_url" value="<?= e((string) get_setting('site_url', '')) ?>" placeholder="https://yourdomain.com"></div>
        <div class="form-group"><label class="form-label">Default Timezone</label><input class="form-control" name="default_timezone" value="<?= e((string) get_setting('default_timezone', 'Asia/Kolkata')) ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Maintenance Mode Banner Message</label><input class="form-control" name="maintenance_message" value="<?= e((string) get_setting('maintenance_message', '')) ?>"></div>
      <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px">
        <label style="font-size:13px;font-weight:700"><input type="checkbox" name="maintenance_mode" value="1" <?= get_setting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>> Enable Maintenance Mode (Public &amp; User Panel)</label>
        <label style="font-size:13px;font-weight:700"><input type="checkbox" name="registration_enabled" value="1" <?= get_setting('registration_enabled', '1') === '1' ? 'checked' : '' ?>> Allow New User Registrations</label>
        <label style="font-size:13px;font-weight:700"><input type="checkbox" name="email_verification_required" value="1" <?= get_setting('email_verification_required', '0') === '1' ? 'checked' : '' ?>> Require Email Verification on Login</label>
      </div>
      <button class="btn btn-primary btn-sm" type="submit">Save General Settings</button>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">SMTP Mailer Settings</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_smtp">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">SMTP Host</label><input class="form-control" name="smtp_host" value="<?= e((string) get_setting('smtp_host', '')) ?>" placeholder="smtp.hostinger.com"></div>
        <div class="form-group"><label class="form-label">SMTP Port</label><input class="form-control" name="smtp_port" value="<?= e((string) get_setting('smtp_port', '587')) ?>"></div>
        <div class="form-group"><label class="form-label">SMTP Username</label><input class="form-control" name="smtp_user" value="<?= e((string) get_setting('smtp_user', '')) ?>"></div>
        <div class="form-group"><label class="form-label">SMTP Password (leave blank to keep)</label><input class="form-control" type="password" name="smtp_pass"></div>
        <div class="form-group"><label class="form-label">From Email</label><input class="form-control" type="email" name="smtp_from_email" value="<?= e((string) get_setting('smtp_from_email', '')) ?>"></div>
        <div class="form-group"><label class="form-label">From Name</label><input class="form-control" name="smtp_from_name" value="<?= e((string) get_setting('smtp_from_name', 'SMM Panel')) ?>"></div>
      </div>
      <button class="btn btn-primary btn-sm" type="submit">Save SMTP Configuration</button>
    </form>
  </div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="margin:0;font-size:16px">Database SQL Backups</h2>
      <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="create_backup"><button class="btn btn-primary btn-sm" type="submit">+ Create SQL Backup</button></form>
    </div>
    <?php if (empty($backups)): ?>
      <p style="font-size:13px;color:#777083">No backups generated yet.</p>
    <?php else: foreach ($backups as $b): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #F2EEF9">
        <div><strong><?= e($b['name']) ?></strong> <span style="font-size:12px;color:#777083">(<?= number_format($b['size'] / 1024, 1) ?> KB)</span></div>
        <a class="btn btn-outline btn-sm" href="/admin/settings/index.php?download_backup=<?= urlencode($b['name']) ?>">Download</a>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">System Health &amp; cPanel Cron Jobs</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12.5px;margin-bottom:12px">
      <div>PHP Version: <strong><?= e(PHP_VERSION) ?></strong></div>
      <div>PDO MySQL: <strong><?= extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing' ?></strong></div>
      <div>cURL: <strong><?= extension_loaded('curl') ? 'Loaded' : 'Missing' ?></strong></div>
      <div>OpenSSL: <strong><?= extension_loaded('openssl') ? 'Loaded' : 'Missing' ?></strong></div>
      <div>BCMath: <strong><?= extension_loaded('bcmath') ? 'Loaded' : 'Fallback Active' ?></strong></div>
      <div>Storage Writable: <strong><?= is_writable(SMM_STORAGE) ? 'Yes' : 'No' ?></strong></div>
    </div>
    <div style="font-size:12px;background:#FAF8FE;border:1px solid var(--b);border-radius:12px;padding:10px;line-height:1.7">
      <div><code>*/2 * * * * php <?= e($rootPath) ?>/cron/order-sync.php</code></div>
      <div><code>*/30 * * * * php <?= e($rootPath) ?>/cron/provider-sync.php</code></div>
      <div><code>0 */6 * * * php <?= e($rootPath) ?>/cron/currency-update.php</code></div>
      <div><code>*/5 * * * * php <?= e($rootPath) ?>/cron/refill-check.php</code></div>
      <div><code>0 3 * * * php <?= e($rootPath) ?>/cron/cleanup.php</code></div>
    </div>
    <div style="font-size:11.5px;color:#777083;margin-top:6px">Web-cron token (if CLI unavailable): <code>?token=<?= e($cronToken) ?></code></div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
