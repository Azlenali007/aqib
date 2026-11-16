<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('tickets.view');
$adminTitle = 'Support Tickets';

$status = trim((string) ($_GET['status'] ?? ''));
$priority = trim((string) ($_GET['priority'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 't.status = ?';
    $params[] = $status;
}
if ($priority !== '') {
    $where[] = 't.priority = ?';
    $params[] = $priority;
}
if ($q !== '') {
    $where[] = '(t.subject LIKE ? OR u.username LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$tickets = Database::fetchAll(
    'SELECT t.*, u.username, a.username AS assigned_admin
     FROM tickets t
     INNER JOIN users u ON u.id = t.user_id
     LEFT JOIN admins a ON a.id = t.assigned_admin_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY FIELD(t.status, "open","pending","answered","closed"), t.last_reply_at DESC, t.id DESC LIMIT 120',
    $params
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <form method="get" class="filter-bar">
    <input class="form-control" style="max-width:240px" name="q" value="<?= e($q) ?>" placeholder="Search subject or username...">
    <select class="form-select" style="max-width:150px" name="status">
      <option value="">All Statuses</option>
      <?php foreach (['open', 'pending', 'answered', 'closed'] as $st): ?>
        <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="form-select" style="max-width:150px" name="priority">
      <option value="">All Priorities</option>
      <?php foreach (['low', 'medium', 'high', 'urgent'] as $pr): ?>
        <option value="<?= e($pr) ?>" <?= $priority === $pr ? 'selected' : '' ?>><?= e(ucfirst($pr)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>

  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>User</th><th>Category</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Last Reply</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($tickets)): ?>
        <tr><td colspan="9" style="text-align:center;color:#777083">No support tickets found.</td></tr>
      <?php else: foreach ($tickets as $t): ?>
        <tr>
          <td>#<?= (int) $t['id'] ?></td>
          <td><a href="/admin/users/view.php?id=<?= (int) $t['user_id'] ?>"><strong><?= e($t['username']) ?></strong></a></td>
          <td><?= e(ucfirst($t['category'])) ?></td>
          <td><a href="/admin/tickets/view.php?id=<?= (int) $t['id'] ?>" style="font-weight:700;color:var(--p)"><?= e($t['subject']) ?></a></td>
          <td><span class="badge <?= $t['priority'] === 'urgent' || $t['priority'] === 'high' ? 'badge-danger' : 'badge-muted' ?>"><?= e(ucfirst($t['priority'])) ?></span></td>
          <td><span class="badge <?= e(status_badge_class($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td><?= e($t['assigned_admin'] ?? 'Unassigned') ?></td>
          <td><?= e(substr((string) ($t['last_reply_at'] ?? $t['created_at']), 0, 16)) ?></td>
          <td><a class="btn btn-outline btn-sm" href="/admin/tickets/view.php?id=<?= (int) $t['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
