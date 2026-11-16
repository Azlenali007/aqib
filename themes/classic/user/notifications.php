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
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Notifications</h1>
      <p class="page-banner-sub">Real-time order updates, deposit confirmations, and support replies</p>
    </div>
  </div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_all"><button class="btn btn-outline btn-sm" type="submit">Mark All Read</button></form>
</div>

<div class="card">
  <?php if (empty($rows)): ?>
    <p style="color:var(--muted);margin:0;text-align:center;padding:20px">No notifications yet.</p>
  <?php else: foreach ($rows as $n): ?>
    <div style="padding:13px 15px;border-radius:16px;border:1px solid var(--border);background:<?= $n['is_read'] ? '#fff' : '#F6F2FE' ?>;margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;gap:10px">
        <strong style="color:var(--text)"><?= e($n['title']) ?></strong>
        <span style="font-size:11.5px;color:var(--muted);white-space:nowrap"><?= e(time_ago($n['created_at'])) ?></span>
      </div>
      <p style="margin:4px 0 0;font-size:13.5px;color:var(--muted)"><?= e($n['message']) ?></p>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
