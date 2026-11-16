<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('child_panels.manage');
$adminTitle = 'Child Panels Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_panel') {
        $ownerId = (int) ($_POST['owner_user_id'] ?? 0);
        $res = ChildPanelService::createPanel(
            $ownerId,
            (string) ($_POST['site_name'] ?? ''),
            (string) ($_POST['domain'] ?? ''),
            (string) ($_POST['currency'] ?? Currency::systemBase()),
            (string) ($_POST['margin'] ?? '25.00')
        );
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Child panel #{$res['panel_id']} created." : $res['error']);
    } elseif ($action === 'set_status') {
        $pid = (int) ($_POST['panel_id'] ?? 0);
        $st = $_POST['status'] ?? 'pending';
        if (in_array($st, ['pending', 'active', 'suspended', 'rejected'], true)) {
            Database::execute('UPDATE child_panels SET status = ? WHERE id = ?', [$st, $pid]);
            Logger::activity('admin', (int) $admin['id'], 'child_panel.status_' . $st, 'child_panels', (string) $pid);
            Session::flash('success', "Child panel #$pid set to $st.");
        }
    } elseif ($action === 'save_global') {
        set_setting('child_panel_enabled', isset($_POST['child_panel_enabled']) ? '1' : '0', 'child_panel');
        set_setting('child_panel_creation_fee', Currency::normalizeDecimal((string) ($_POST['child_panel_creation_fee'] ?? '0.00'), 2), 'child_panel');
        Session::flash('success', 'Child panel global settings saved.');
    }
    Response::redirect('/admin/child-panels/index.php');
}

$panels = Database::fetchAll(
    'SELECT cp.*, u.username AS owner_username,
            (SELECT domain FROM child_panel_domains WHERE child_panel_id = cp.id AND is_primary = 1 LIMIT 1) AS primary_domain,
            (SELECT COUNT(*) FROM child_panel_users WHERE child_panel_id = cp.id) AS child_users_count,
            (SELECT COUNT(*) FROM child_panel_orders WHERE child_panel_id = cp.id) AS child_orders_count
     FROM child_panels cp
     INNER JOIN users u ON u.id = cp.owner_user_id
     ORDER BY cp.id DESC'
);
$usersList = Database::fetchAll('SELECT id, username FROM users WHERE status = "active" ORDER BY id DESC LIMIT 200');
$currencies = Currency::activeCurrencies();

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Create Child Panel for User</h2>
    <form method="post" class="grid-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_panel">
      <div class="form-group">
        <label class="form-label">Owner User</label>
        <select class="form-select" name="owner_user_id" required>
          <option value="">Select Owner</option>
          <?php foreach ($usersList as $u): ?><option value="<?= (int) $u['id'] ?>">#<?= (int) $u['id'] ?> — <?= e($u['username']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Site Name</label><input class="form-control" name="site_name" placeholder="Client SMM Panel" required></div>
      <div class="form-group"><label class="form-label">Custom Domain</label><input class="form-control" name="domain" placeholder="panel.clientdomain.com" required></div>
      <div class="form-group">
        <label class="form-label">Currency &amp; Margin %</label>
        <div style="display:flex;gap:6px">
          <select class="form-select" name="currency"><?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?></option><?php endforeach; ?></select>
          <input class="form-control" type="number" step="0.01" name="margin" value="25.00" placeholder="25%">
        </div>
      </div>
      <div style="grid-column:span 2"><button class="btn btn-primary btn-sm" type="submit">+ Create Child Panel</button></div>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Child Panel Global Settings</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_global">
      <div class="form-group"><label class="form-label">Panel Creation Fee (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" min="0" name="child_panel_creation_fee" value="<?= e((string) get_setting('child_panel_creation_fee', '0.00')) ?>"></div>
      <div class="form-group"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="child_panel_enabled" value="1" <?= get_setting('child_panel_enabled', '1') === '1' ? 'checked' : '' ?>> Allow Users to Order Child Panels</label></div>
      <button class="btn btn-outline btn-sm" type="submit">Save Global Settings</button>
    </form>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">All Child Panels (Isolated Tenants)</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Panel Name</th><th>Owner</th><th>Domain</th><th>Wholesale Bal</th><th>Users / Orders</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($panels)): ?>
        <tr><td colspan="8" style="text-align:center;color:#777083">No child panels created yet.</td></tr>
      <?php else: foreach ($panels as $cp): ?>
        <tr>
          <td>#<?= (int) $cp['id'] ?></td>
          <td><strong><?= e($cp['site_name']) ?></strong><div style="font-size:11px;color:#777083">Currency: <?= e($cp['currency']) ?> • Margin: <?= e((string) $cp['default_profit_margin']) ?>%</div></td>
          <td><a href="/admin/users/view.php?id=<?= (int) $cp['owner_user_id'] ?>"><?= e($cp['owner_username']) ?></a></td>
          <td><code><?= e($cp['primary_domain'] ?? '—') ?></code></td>
          <td style="font-weight:700"><?= e(format_money((string) $cp['wallet_balance'])) ?></td>
          <td><?= (int) $cp['child_users_count'] ?> users / <?= (int) $cp['child_orders_count'] ?> orders</td>
          <td><span class="badge <?= e(status_badge_class($cp['status'])) ?>"><?= e(ucfirst($cp['status'])) ?></span></td>
          <td style="display:flex;gap:5px;flex-wrap:wrap">
            <a class="btn btn-outline btn-sm" href="/admin/child-panels/view.php?id=<?= (int) $cp['id'] ?>">Manage</a>
            <?php if ($cp['status'] !== 'active'): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="panel_id" value="<?= (int) $cp['id'] ?>"><input type="hidden" name="status" value="active"><button class="btn btn-success btn-sm" type="submit">Activate</button></form>
            <?php endif; ?>
            <?php if ($cp['status'] === 'active'): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="set_status"><input type="hidden" name="panel_id" value="<?= (int) $cp['id'] ?>"><input type="hidden" name="status" value="suspended"><button class="btn btn-danger btn-sm" type="submit">Suspend</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
