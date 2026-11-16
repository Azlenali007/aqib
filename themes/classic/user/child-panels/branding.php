<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Branding';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $siteName = trim((string) ($_POST['site_name'] ?? $cpPanel['site_name']));
    $tagline = trim((string) ($_POST['tagline'] ?? $cpPanel['tagline']));
    $color = trim((string) ($_POST['primary_color'] ?? '#7045D8'));
    $logoPath = $cpPanel['logo_path'];
    if (!empty($_FILES['logo']['tmp_name'])) {
        $up = Upload::store($_FILES['logo'], 'profiles');
        if ($up['success']) $logoPath = $up['path'];
    }
    Database::execute('UPDATE child_panels SET site_name = ?, tagline = ?, primary_color = ?, logo_path = ? WHERE id = ? AND owner_user_id = ?', [$siteName, $tagline, $color, $logoPath, $cpPanel['id'], $user['id']]);
    Session::flash('success', 'Branding updated.');
    Response::redirect('/user/index.php?page=child-panels/branding&panel_id=' . $cpPanel['id']);
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Branding &amp; Appearance</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Customize logo, tagline, and primary theme accent.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/branding'); ?>
<div class="card" style="max-width:640px">
  <h2 class="section-title" style="margin-bottom:14px">Brand Settings</h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Website Name</label><input class="form-control" name="site_name" value="<?= e($cpPanel['site_name']) ?>" required></div>
    <div class="form-group"><label class="form-label">Tagline</label><input class="form-control" name="tagline" value="<?= e($cpPanel['tagline'] ?? '') ?>"></div>
    <div class="form-group"><label class="form-label">Primary Brand Color</label><input class="form-control" type="color" style="height:44px;max-width:120px" name="primary_color" value="<?= e($cpPanel['primary_color']) ?>"></div>
    <div class="form-group"><label class="form-label">Custom Logo</label><input class="form-control" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp"></div>
    <button class="btn btn-primary" type="submit">Save Branding</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
