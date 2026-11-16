<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('child_panels.manage');
$panelId = (int) ($_GET['id'] ?? 0);
$panel = Database::fetchOne(
    'SELECT cp.*, u.username AS owner_username FROM child_panels cp INNER JOIN users u ON u.id = cp.owner_user_id WHERE cp.id = ? LIMIT 1',
    [$panelId]
);
if (!$panel) {
    Session::flash('error', 'Child panel not found.');
    Response::redirect('/admin/child-panels/index.php');
}
$adminTitle = 'Child Panel #' . $panelId . ' — ' . $panel['site_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_panel') {
        Database::execute(
            'UPDATE child_panels SET site_name = ?, tagline = ?, primary_color = ?, default_profit_margin = ?, status = ?, admin_note = ? WHERE id = ?',
            [
                trim((string) ($_POST['site_name'] ?? $panel['site_name'])),
                trim((string) ($_POST['tagline'] ?? '')),
                trim((string) ($_POST['primary_color'] ?? '#7045D8')),
                Currency::normalizeDecimal((string) ($_POST['default_profit_margin'] ?? '25'), 2),
                $_POST['status'] ?? $panel['status'],
                trim((string) ($_POST['admin_note'] ?? '')),
                $panelId,
            ]
        );
        Session::flash('success', 'Child panel configuration updated.');
    } elseif ($action === 'adjust_balance') {
        $amt = trim((string) ($_POST['amount'] ?? '0'));
        $mode = ($_POST['mode'] ?? 'credit') === 'debit' ? 'debit' : 'credit';
        if (is_numeric($amt) && (float) $amt > 0) {
            $amtNorm = Currency::normalizeDecimal($amt, 4);
            $newBal = $mode === 'credit'
                ? Currency::add((string) $panel['wallet_balance'], $amtNorm, 4)
                : Currency::sub((string) $panel['wallet_balance'], $amtNorm, 4);
            if ((float) $newBal < 0) {
                Session::flash('error', 'Insufficient panel balance to deduct.');
            } else {
                Database::execute('UPDATE child_panels SET wallet_balance = ? WHERE id = ?', [$newBal, $panelId]);
                Database::execute(
                    'INSERT INTO child_panel_wallet_transactions (child_panel_id, type, amount, balance_after, description) VALUES (?, ?, ?, ?, ?)',
                    [$panelId, $mode, $amtNorm, $newBal, "Admin wholesale $mode"]
                );
                Session::flash('success', "Child panel wholesale balance updated to " . format_money($newBal) . '.');
            }
        }
    } elseif ($action === 'verify_domain') {
        Database::execute('UPDATE child_panel_domains SET is_verified = 1, verified_at = NOW() WHERE id = ? AND child_panel_id = ?', [(int) ($_POST['domain_id'] ?? 0), $panelId]);
        Session::flash('success', 'Domain manually marked verified.');
    } elseif ($action === 'add_domain') {
        $dom = strtolower(trim((string) preg_replace('#^https?://#i', '', (string) ($_POST['domain'] ?? ''))));
        if ($dom !== '' && str_contains($dom, '.')) {
            Database::execute(
                'INSERT INTO child_panel_domains (child_panel_id, domain, verification_token, is_verified, is_primary) VALUES (?, ?, ?, 1, 0)',
                [$panelId, $dom, 'smm-verify-' . bin2hex(random_bytes(8))]
            );
            Session::flash('success', "Domain $dom attached.");
        }
    } elseif ($action === 'toggle_service') {
        $cpsId = (int) ($_POST['cps_id'] ?? 0);
        Database::execute('UPDATE child_panel_services SET is_enabled = 1 - is_enabled WHERE id = ? AND child_panel_id = ?', [$cpsId, $panelId]);
        Session::flash('success', 'Child panel service toggled.');
    }
    Response::redirect('/admin/child-panels/view.php?id=' . $panelId);
}

$domains = Database::fetchAll('SELECT * FROM child_panel_domains WHERE child_panel_id = ? ORDER BY is_primary DESC, id ASC', [$panelId]);
$childUsers = Database::fetchAll('SELECT u.*, w.balance FROM child_panel_users u LEFT JOIN child_panel_wallets w ON w.child_user_id = u.id WHERE u.child_panel_id = ? ORDER BY u.id DESC LIMIT 50', [$panelId]);
$childOrders = Database::fetchAll('SELECT o.*, u.username FROM child_panel_orders o INNER JOIN child_panel_users u ON u.id = o.child_user_id WHERE o.child_panel_id = ? ORDER BY o.id DESC LIMIT 50', [$panelId]);
$childServices = Database::fetchAll('SELECT cps.*, s.name AS parent_name, s.selling_price AS parent_price FROM child_panel_services cps INNER JOIN services s ON s.id = cps.parent_service_id WHERE cps.child_panel_id = ? ORDER BY cps.id ASC LIMIT 100', [$panelId]);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div style="margin-bottom:14px"><a class="btn btn-outline btn-sm" href="/admin/child-panels/index.php">&larr; Back to Child Panels</a></div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Branding &amp; Status (Owner: <?= e($panel['owner_username']) ?>)</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_panel">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Site Name</label><input class="form-control" name="site_name" value="<?= e($panel['site_name']) ?>"></div>
        <div class="form-group"><label class="form-label">Tagline</label><input class="form-control" name="tagline" value="<?= e($panel['tagline'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Primary Color</label><input class="form-control" type="color" name="primary_color" value="<?= e($panel['primary_color']) ?>"></div>
        <div class="form-group"><label class="form-label">Default Margin %</label><input class="form-control" type="number" step="0.01" name="default_profit_margin" value="<?= e((string) $panel['default_profit_margin']) ?>"></div>
        <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['pending', 'active', 'suspended', 'rejected'] as $st): ?><option value="<?= e($st) ?>" <?= $panel['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Admin Note</label><input class="form-control" name="admin_note" value="<?= e($panel['admin_note'] ?? '') ?>"></div>
      </div>
      <button class="btn btn-primary btn-sm" type="submit">Save Configuration</button>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Wholesale Balance &amp; Custom Domains</h2>
    <div style="font-size:22px;font-weight:800;color:var(--p);margin-bottom:10px"><?= e(format_money((string) $panel['wallet_balance'])) ?></div>
    <form method="post" class="filter-bar">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="adjust_balance">
      <select class="form-select" style="max-width:110px" name="mode"><option value="credit">+ Add</option><option value="debit">- Deduct</option></select>
      <input class="form-control" style="max-width:140px" type="number" step="0.01" min="0.01" name="amount" placeholder="Amount" required>
      <button class="btn btn-primary btn-sm" type="submit">Update Balance</button>
    </form>
    <hr style="border:none;border-top:1px solid var(--b);margin:14px 0">
    <form method="post" class="filter-bar">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_domain">
      <input class="form-control" style="max-width:220px" name="domain" placeholder="extra.domain.com" required>
      <button class="btn btn-outline btn-sm" type="submit">+ Add Domain</button>
    </form>
    <?php foreach ($domains as $d): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #F2EEF9">
        <div><code><?= e($d['domain']) ?></code> <span class="badge <?= (int) $d['is_verified'] === 1 ? 'badge-success' : 'badge-warning' ?>"><?= (int) $d['is_verified'] === 1 ? 'Verified' : 'Unverified' ?></span></div>
        <?php if ((int) $d['is_verified'] !== 1): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify_domain"><input type="hidden" name="domain_id" value="<?= (int) $d['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Force Verify</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Isolated Child Users (<?= count($childUsers) ?>)</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:440px">
        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($childUsers)): ?><tr><td colspan="5" style="text-align:center;color:#777083">No child users registered.</td></tr>
        <?php else: foreach ($childUsers as $cu): ?>
          <tr><td>#<?= (int) $cu['id'] ?></td><td><?= e($cu['username']) ?></td><td><?= e($cu['email']) ?></td><td><?= e(format_money((string) ($cu['balance'] ?? '0'))) ?></td><td><span class="badge <?= e(status_badge_class($cu['status'])) ?>"><?= e($cu['status']) ?></span></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Isolated Child Orders (<?= count($childOrders) ?>)</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:440px">
        <thead><tr><th>ID</th><th>User</th><th>Qty</th><th>Charge</th><th>Profit</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($childOrders)): ?><tr><td colspan="6" style="text-align:center;color:#777083">No child panel orders yet.</td></tr>
        <?php else: foreach ($childOrders as $co): ?>
          <tr><td>#<?= (int) $co['id'] ?></td><td><?= e($co['username']) ?></td><td><?= (int) $co['quantity'] ?></td><td><?= e(format_money((string) $co['charge'])) ?></td><td><?= e(format_money((string) $co['panel_profit'])) ?></td><td><span class="badge <?= e(status_badge_class($co['status'])) ?>"><?= e($co['status']) ?></span></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 10px;font-size:16px">Assigned Child Panel Services (<?= count($childServices) ?>)</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Service Name</th><th>Parent Price</th><th>Child Price</th><th>Margin</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($childServices)): ?><tr><td colspan="7" style="text-align:center;color:#777083">No services assigned.</td></tr>
      <?php else: foreach ($childServices as $cs): ?>
        <tr>
          <td>#<?= (int) $cs['id'] ?></td>
          <td><?= e($cs['custom_name'] ?: $cs['parent_name']) ?></td>
          <td><?= e(format_money((string) $cs['parent_price'])) ?></td>
          <td style="font-weight:700"><?= e(format_money((string) $cs['custom_selling_price'])) ?></td>
          <td><?= e((string) $cs['profit_margin_percent']) ?>%</td>
          <td><span class="badge <?= (int) $cs['is_enabled'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $cs['is_enabled'] === 1 ? 'Enabled' : 'Disabled' ?></span></td>
          <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_service"><input type="hidden" name="cps_id" value="<?= (int) $cs['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Toggle</button></form></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
