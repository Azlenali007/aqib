<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (($_POST['action'] ?? '') === 'regen_api') {
        $newKey = 'cp_' . bin2hex(random_bytes(24));
        Database::execute('UPDATE child_panels SET api_key = ? WHERE id = ? AND owner_user_id = ?', [$newKey, $cpPanel['id'], $user['id']]);
        Session::flash('success', 'Child panel API key regenerated.');
    }
    Response::redirect('/user/index.php?page=child-panels/settings&panel_id=' . $cpPanel['id']);
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.85 1 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Child Panel API &amp; Settings</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Manage panel API credentials and security.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/settings'); ?>
<div class="card" style="max-width:600px">
  <h2 class="section-title" style="margin-bottom:14px">API Credentials</h2>
  <div class="form-group"><label class="form-label">Child Panel API Key</label><input class="form-control" readonly value="<?= e($cpPanel['api_key']) ?>"></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="regen_api">
    <button class="btn btn-outline" type="submit">Regenerate API Key</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
