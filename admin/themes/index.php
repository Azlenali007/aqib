<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('themes.manage');
$adminTitle = 'Theme Management (Classic)';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $primary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($_POST['classic_primary_color'] ?? '')) ? $_POST['classic_primary_color'] : '#7045D8';
    $secondary = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($_POST['classic_secondary_color'] ?? '')) ? $_POST['classic_secondary_color'] : '#8B5CF6';
    set_setting('active_theme', 'classic', 'theme');
    set_setting('classic_primary_color', $primary, 'theme');
    set_setting('classic_secondary_color', $secondary, 'theme');
    Logger::activity('admin', (int) $admin['id'], 'themes.update', 'themes', 'classic');
    Session::flash('success', 'Classic Theme settings updated.');
    Response::redirect('/admin/themes/index.php');
}

$themeMeta = [];
$jsonPath = SMM_THEMES . '/classic/theme.json';
if (is_file($jsonPath)) {
    $themeMeta = json_decode((string) file_get_contents($jsonPath), true) ?: [];
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card" style="max-width:760px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <div>
      <h2 style="margin:0;font-size:18px"><?= e($themeMeta['name'] ?? 'Classic') ?> Theme</h2>
      <div style="font-size:12.5px;color:#777083"><?= e($themeMeta['description'] ?? 'Premium White + Lavender + Purple SMM Panel User Theme') ?> (v<?= e($themeMeta['version'] ?? '1.0.0') ?>)</div>
    </div>
    <span class="badge badge-success">Active Default</span>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Primary Brand Purple</label><input class="form-control" type="color" name="classic_primary_color" value="<?= e((string) get_setting('classic_primary_color', '#7045D8')) ?>"></div>
      <div class="form-group"><label class="form-label">Secondary Accent Purple</label><input class="form-control" type="color" name="classic_secondary_color" value="<?= e((string) get_setting('classic_secondary_color', '#8B5CF6')) ?>"></div>
    </div>
    <button class="btn btn-primary" type="submit">Save Classic Theme Settings</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
