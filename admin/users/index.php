<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('users.view');
$adminTitle = 'User Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user' && AdminAuth::can('users.create', $admin)) {
        $res = Auth::register($_POST);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'User created successfully.' : $res['error']);
    } elseif ($action === 'toggle_status' && AdminAuth::can('users.edit', $admin)) {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $newStatus = ($_POST['new_status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
        Database::execute('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $uid]);
        Logger::activity('admin', (int) $admin['id'], 'users.status_' . $newStatus, 'users', (string) $uid);
        Session::flash('success', "User #$uid status updated to $newStatus.");
    } elseif ($action === 'delete_user' && AdminAuth::can('users.delete', $admin)) {
        $uid = (int) ($_POST['user_id'] ?? 0);
        Database::execute('DELETE FROM users WHERE id = ?', [$uid]);
        Logger::activity('admin', (int) $admin['id'], 'users.delete', 'users', (string) $uid);
        Session::flash('success', "User #$uid deleted.");
    }
    Response::redirect('/admin/users/index.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)';
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($status !== '') {
    $where[] = 'u.status = ?';
    $params[] = $status;
}

$users = Database::fetchAll(
    'SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE ' . implode(' AND ', $where) . ' ORDER BY u.id DESC LIMIT 100',
    $params
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<?php if (AdminAuth::can('users.create', $admin)): ?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Add New User</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_user">
    <input class="form-control" name="username" placeholder="Username" required>
    <input class="form-control" name="full_name" placeholder="Full Name" required>
    <input class="form-control" type="email" name="email" placeholder="Email Address" required>
    <input class="form-control" name="mobile" placeholder="Mobile (Optional)">
    <input class="form-control" type="password" name="password" placeholder="Password (min 8 chars)" minlength="8" required>
    <button class="btn btn-primary" type="submit">+ Create User</button>
  </form>
</div>
<?php endif; ?>

<div class="adm-card">
  <form method="get" class="filter-bar">
    <input class="form-control" style="max-width:250px" name="q" value="<?= e($q) ?>" placeholder="Search username, email, name...">
    <select class="form-select" style="max-width:160px" name="status">
      <option value="">All Statuses</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
      <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Balance</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($users)): ?><tr><td colspan="7" style="text-align:center;color:#777083">No users found.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td>#<?= (int) $u['id'] ?></td>
          <td><strong><?= e($u['username']) ?></strong><div style="font-size:11.5px;color:#777083"><?= e($u['full_name']) ?></div></td>
          <td><?= e($u['email']) ?></td>
          <td style="font-weight:700"><?= e(format_money((string) ($u['balance'] ?? '0'))) ?></td>
          <td><span class="badge <?= e(status_badge_class($u['status'])) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
          <td><?= e(substr($u['created_at'], 0, 10)) ?></td>
          <td style="white-space:nowrap;display:flex;gap:5px">
            <a class="btn btn-outline btn-sm" href="/admin/users/view.php?id=<?= (int) $u['id'] ?>">Manage</a>
            <?php if (AdminAuth::can('users.edit', $admin)): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="new_status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
            <?php endif; ?>
            <?php if (AdminAuth::can('users.delete', $admin)): ?>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete user permanently?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
