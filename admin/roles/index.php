<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('roles.manage');
$adminTitle = 'Roles, Permissions & Admin Accounts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_role') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $name));
        $desc = trim((string) ($_POST['description'] ?? ''));
        if ($name !== '' && $slug !== '') {
            try {
                Database::execute('INSERT INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, 0)', [$name, $slug, $desc]);
                Session::flash('success', "Role '$name' created.");
            } catch (Throwable) {
                Session::flash('error', 'Role with that slug already exists.');
            }
        }
    } elseif ($action === 'save_role_perms') {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $role = Database::fetchOne('SELECT slug FROM roles WHERE id = ?', [$roleId]);
        if ($role && $role['slug'] !== 'super_admin') {
            $permIds = array_map('intval', (array) ($_POST['perm_ids'] ?? []));
            Database::transaction(function () use ($roleId, $permIds) {
                Database::execute('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
                foreach ($permIds as $pid) {
                    if ($pid > 0) {
                        Database::execute('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)', [$roleId, $pid]);
                    }
                }
            });
            Logger::activity('admin', (int) $admin['id'], 'roles.update_perms', 'roles', (string) $roleId);
            Session::flash('success', 'Permissions matrix updated.');
        }
    } elseif ($action === 'create_admin') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 2);
        if (strlen($password) < 8 || $username === '' || !Validator::email($email)) {
            Session::flash('error', 'Valid username, email, and min 8-character password required.');
        } else {
            try {
                Database::execute(
                    'INSERT INTO admins (role_id, username, email, full_name, password_hash, status) VALUES (?, ?, ?, ?, ?, "active")',
                    [$roleId, $username, $email, $fullName, Security::hashPassword($password)]
                );
                Logger::activity('admin', (int) $admin['id'], 'admins.create', 'admins', $username);
                Session::flash('success', "Admin '$username' created.");
            } catch (Throwable) {
                Session::flash('error', 'Username or email already exists.');
            }
        }
    } elseif ($action === 'toggle_admin') {
        $aid = (int) ($_POST['admin_id'] ?? 0);
        if ($aid === (int) $admin['id']) {
            Session::flash('error', 'Cannot suspend your own active account.');
        } else {
            $cur = Database::fetchValue('SELECT status FROM admins WHERE id = ?', [$aid]);
            $next = $cur === 'active' ? 'suspended' : 'active';
            Database::execute('UPDATE admins SET status = ? WHERE id = ?', [$next, $aid]);
            Session::flash('success', "Admin #$aid status set to $next.");
        }
    }
    Response::redirect('/admin/roles/index.php');
}

$roles = Database::fetchAll('SELECT * FROM roles ORDER BY id ASC');
$perms = Database::fetchAll('SELECT * FROM permissions ORDER BY module ASC, id ASC');
$rpRows = Database::fetchAll('SELECT role_id, permission_id FROM role_permissions');
$rolePermMap = [];
foreach ($rpRows as $rp) {
    $rolePermMap[(int) $rp['role_id']][(int) $rp['permission_id']] = true;
}
$admins = Database::fetchAll('SELECT a.*, r.name AS role_name FROM admins a INNER JOIN roles r ON r.id = a.role_id ORDER BY a.id ASC');

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Create Admin Account</h2>
    <form method="post" class="grid-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_admin">
      <div class="form-group"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
      <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
      <div class="form-group"><label class="form-label">Password (min 8 chars)</label><input class="form-control" type="password" name="password" minlength="8" required></div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select class="form-select" name="role_id">
          <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end"><button class="btn btn-primary" type="submit">+ Create Admin</button></div>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Create Custom Role</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_role">
      <div class="form-group"><label class="form-label">Role Name</label><input class="form-control" name="name" placeholder="e.g. Catalog Reviewer" required></div>
      <div class="form-group"><label class="form-label">Description</label><input class="form-control" name="description" placeholder="Optional scope note"></div>
      <button class="btn btn-outline btn-sm" type="submit">+ Create Role</button>
    </form>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 10px;font-size:16px">Admin Accounts</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td>#<?= (int) $a['id'] ?></td>
          <td><strong><?= e($a['username']) ?></strong></td>
          <td><?= e($a['full_name']) ?></td>
          <td><?= e($a['email']) ?></td>
          <td><span class="badge badge-muted"><?= e($a['role_name']) ?></span></td>
          <td><span class="badge <?= e(status_badge_class($a['status'])) ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          <td><?= e((string) ($a['last_login_at'] ?? 'Never')) ?></td>
          <td>
            <?php if ((int) $a['id'] !== (int) $admin['id']): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $a['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
            <?php else: ?><span style="font-size:12px;color:#777083">Current</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Role Permission Matrix</h2>
  <?php foreach ($roles as $r): ?>
    <div style="border:1px solid var(--b);border-radius:14px;padding:14px;margin-bottom:14px">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_role_perms">
        <input type="hidden" name="role_id" value="<?= (int) $r['id'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <div><strong><?= e($r['name']) ?></strong> <span style="font-size:12px;color:#777083">(<?= e($r['slug']) ?>) — <?= e($r['description'] ?? '') ?></span></div>
          <?php if ($r['slug'] !== 'super_admin'): ?>
            <button class="btn btn-primary btn-sm" type="submit">Save Permissions</button>
          <?php else: ?>
            <span class="badge badge-success">Unrestricted Full Access</span>
          <?php endif; ?>
        </div>
        <?php if ($r['slug'] !== 'super_admin'): ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px">
            <?php foreach ($perms as $p): $chk = !empty($rolePermMap[(int) $r['id']][(int) $p['id']]); ?>
              <label style="font-size:12.5px;display:flex;align-items:center;gap:6px">
                <input type="checkbox" name="perm_ids[]" value="<?= (int) $p['id'] ?>" <?= $chk ? 'checked' : '' ?>>
                <span><?= e($p['label']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </form>
    </div>
  <?php endforeach; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
