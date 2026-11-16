<?php
declare(strict_types=1);

$pageTitle = 'Notifications';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all') {
    Security::requireCsrf();
    Database::execute('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ?', [(int) $user['id']]);
    Session::flash('success', 'All notifications marked as read.');
    Response::redirect('/user/index.php?page=notifications');
}
$rows = Database::fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [(int) $user['id']]);

require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">Notifications</h1>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_all"><button class="btn btn-outline btn-sm" type="submit">Mark All Read</button></form>
  </div>
  <?php if (empty($rows)): ?>
    <p style="color:#777083">No notifications yet.</p>
  <?php else: foreach ($rows as $n): ?>
    <div style="padding:12px 14px;border-radius:14px;border:1px solid #EAE5F1;background:<?= $n['is_read'] ? '#fff' : '#F6F2FE' ?>;margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;gap:10px">
        <strong><?= e($n['title']) ?></strong>
        <span style="font-size:11.5px;color:#777083"><?= e(time_ago($n['created_at'])) ?></span>
      </div>
      <p style="margin:4px 0 0;font-size:13.5px;color:#777083"><?= e($n['message']) ?></p>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
