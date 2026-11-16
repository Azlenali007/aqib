<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('notifications.manage');
$adminTitle = 'Notifications & Announcements';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'send_notification') {
        $target = trim((string) ($_POST['target_user_id'] ?? 'all'));
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $sendEmail = isset($_POST['send_email']);

        if ($title === '' || $message === '') {
            Session::flash('error', 'Notification title and message are required.');
        } elseif ($target === 'all') {
            $sent = NotificationService::broadcastAll($title, $message, $sendEmail);
            Logger::activity('admin', (int) $admin['id'], 'notifications.broadcast', null, null, ['count' => $sent]);
            Session::flash('success', "Notification broadcast sent to $sent active users.");
        } else {
            $uid = (int) $target;
            Notification::sendToUser($uid, 'system', $title, $message, '/user/index.php?page=notifications', $sendEmail);
            Logger::activity('admin', (int) $admin['id'], 'notifications.send_user', 'users', (string) $uid);
            Session::flash('success', "Notification sent to User #$uid.");
        }
    } elseif ($action === 'create_announcement') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $badge = in_array($_POST['badge_type'] ?? 'info', ['info', 'success', 'warning', 'promo'], true) ? $_POST['badge_type'] : 'info';
        if ($title !== '' && $content !== '') {
            Database::execute(
                'INSERT INTO announcements (title, content, badge_type, is_active, created_by_admin_id) VALUES (?, ?, ?, 1, ?)',
                [$title, $content, $badge, (int) $admin['id']]
            );
            Session::flash('success', 'Announcement published.');
        }
    } elseif ($action === 'toggle_announcement') {
        $id = (int) ($_POST['id'] ?? 0);
        Database::execute('UPDATE announcements SET is_active = 1 - is_active WHERE id = ?', [$id]);
        Session::flash('success', 'Announcement status updated.');
    } elseif ($action === 'delete_announcement') {
        Database::execute('DELETE FROM announcements WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
        Session::flash('success', 'Announcement deleted.');
    }
    Response::redirect('/admin/notifications/index.php');
}

$usersList = Database::fetchAll('SELECT id, username FROM users WHERE status = "active" ORDER BY id DESC LIMIT 200');
$announcements = Database::fetchAll('SELECT * FROM announcements ORDER BY id DESC LIMIT 40');
$recentNotifs = Database::fetchAll('SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.id DESC LIMIT 30');

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Send Notification</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="send_notification">
      <div class="form-group">
        <label class="form-label">Recipient</label>
        <select class="form-select" name="target_user_id">
          <option value="all">All Active Users (Broadcast)</option>
          <?php foreach ($usersList as $u): ?><option value="<?= (int) $u['id'] ?>">#<?= (int) $u['id'] ?> — <?= e($u['username']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="form-group"><label class="form-label">Message</label><textarea class="form-control" rows="3" name="message" required></textarea></div>
      <div class="form-group"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="send_email" value="1"> Also send via SMTP Email (if configured)</label></div>
      <button class="btn btn-primary btn-sm" type="submit">Send Notification</button>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Publish Panel Announcement</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_announcement">
      <div class="form-group"><label class="form-label">Headline</label><input class="form-control" name="title" required></div>
      <div class="form-group">
        <label class="form-label">Badge Type</label>
        <select class="form-select" name="badge_type"><option value="info">Info</option><option value="success">Success</option><option value="warning">Warning</option><option value="promo">Promo</option></select>
      </div>
      <div class="form-group"><label class="form-label">Announcement Content</label><textarea class="form-control" rows="3" name="content" required></textarea></div>
      <button class="btn btn-primary btn-sm" type="submit">Publish Announcement</button>
    </form>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 10px;font-size:16px">Announcements</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Content</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($announcements)): ?><tr><td colspan="6" style="text-align:center;color:#777083">No announcements yet.</td></tr>
      <?php else: foreach ($announcements as $a): ?>
        <tr>
          <td>#<?= (int) $a['id'] ?></td>
          <td><strong><?= e($a['title']) ?></strong></td>
          <td><span class="badge badge-muted"><?= e(ucfirst($a['badge_type'])) ?></span></td>
          <td><?= e(mb_substr($a['content'], 0, 120)) ?></td>
          <td><span class="badge <?= (int) $a['is_active'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $a['is_active'] === 1 ? 'Active' : 'Hidden' ?></span></td>
          <td style="display:flex;gap:5px">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_announcement"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Toggle</button></form>
            <form method="post" onsubmit="return confirm('Delete announcement?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_announcement"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 10px;font-size:16px">Recent User Notifications Log</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Read</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($recentNotifs)): ?><tr><td colspan="6" style="text-align:center;color:#777083">No notifications sent yet.</td></tr>
      <?php else: foreach ($recentNotifs as $n): ?>
        <tr><td>#<?= (int) $n['id'] ?></td><td><?= e($n['username'] ?? 'User #' . $n['user_id']) ?></td><td><?= e($n['type']) ?></td><td><?= e($n['title']) ?></td><td><?= (int) $n['is_read'] === 1 ? 'Yes' : 'Unread' ?></td><td><?= e($n['created_at']) ?></td></tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
