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
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/settings');
?>
<div class="card" style="max-width:600px">
  <h1 class="section-title" style="margin-bottom:14px">Child Panel API &amp; Settings</h1>
  <div class="form-group"><label class="form-label">Child Panel API Key</label><input class="form-control" readonly value="<?= e($cpPanel['api_key']) ?>"></div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="regen_api">
    <button class="btn btn-outline" type="submit">Regenerate API Key</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
