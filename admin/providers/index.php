<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('providers.view');
$adminTitle = 'Provider API Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && AdminAuth::can('providers.manage', $admin)) {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_provider') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $url = trim((string) ($_POST['api_url'] ?? ''));
        $key = trim((string) ($_POST['api_key'] ?? ''));
        $curr = strtoupper(trim((string) ($_POST['currency'] ?? '')));
        if ($name === '' || !filter_var($url, FILTER_VALIDATE_URL) || $key === '' || strlen($curr) !== 3) {
            Session::flash('error', 'Valid name, API URL, API Key, and 3-letter ISO currency code are required.');
        } else {
            Database::execute(
                'INSERT INTO providers (name, api_url, api_key_enc, currency, priority, sync_enabled, status) VALUES (?, ?, ?, ?, ?, ?, "active")',
                [$name, $url, Security::encrypt($key), $curr, (int) ($_POST['priority'] ?? 1), !empty($_POST['sync_enabled']) ? 1 : 0]
            );
            Session::flash('success', "Provider '$name' ($curr) added.");
        }
    } elseif ($action === 'test_provider') {
        $pid = (int) ($_POST['provider_id'] ?? 0);
        $prov = Database::fetchOne('SELECT * FROM providers WHERE id = ? LIMIT 1', [$pid]);
        if ($prov) {
            $res = Provider::client($prov)->testConnection();
            if ($res['success']) {
                Database::execute('UPDATE providers SET balance = ?, balance_currency = ?, last_balance_sync_at = NOW() WHERE id = ?', [Currency::normalizeDecimal($res['balance'], 4), $res['currency'], $pid]);
                Session::flash('success', "Connection OK! Balance: {$res['balance']} {$res['currency']}");
            } else {
                Session::flash('error', "Connection failed: {$res['error']}");
            }
        }
    } elseif ($action === 'toggle_provider') {
        $pid = (int) ($_POST['provider_id'] ?? 0);
        $st = ($_POST['new_status'] ?? 'active') === 'disabled' ? 'disabled' : 'active';
        Database::execute('UPDATE providers SET status = ? WHERE id = ?', [$st, $pid]);
        Session::flash('success', 'Provider status updated.');
    } elseif ($action === 'delete_provider') {
        $pid = (int) ($_POST['provider_id'] ?? 0);
        Database::execute('DELETE FROM providers WHERE id = ?', [$pid]);
        Session::flash('success', 'Provider deleted.');
    }
    Response::redirect('/admin/providers/index.php');
}

$providers = Database::fetchAll('SELECT p.*, (SELECT COUNT(*) FROM services s WHERE s.provider_id = p.id) AS service_count FROM providers p ORDER BY p.priority ASC, p.id DESC');
$currencies = Database::fetchAll('SELECT code, name FROM currencies ORDER BY sort_order ASC');

require dirname(__DIR__) . '/layouts/header.php';
?>
<?php if (AdminAuth::can('providers.manage', $admin)): ?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Add External SMM Provider</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?><input type="hidden" name="action" value="add_provider">
    <input class="form-control" name="name" placeholder="Provider Name" required>
    <input class="form-control" type="url" name="api_url" placeholder="https://provider.com/api/v2" required>
    <input class="form-control" name="api_key" placeholder="Provider API Key" required>
    <select class="form-select" name="currency" required>
      <?php foreach ($currencies as $c): ?><option value="<?= e($c['code']) ?>"><?= e($c['code']) ?> — <?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
    <input class="form-control" type="number" name="priority" value="1" placeholder="Priority">
    <div style="display:flex;gap:10px;align-items:center">
      <label style="font-size:13px"><input type="checkbox" name="sync_enabled" value="1" checked> Auto Sync</label>
      <button class="btn btn-primary" type="submit">+ Add Provider</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="adm-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h2 style="margin:0;font-size:16px">Configured Providers</h2>
    <a class="btn btn-outline btn-sm" href="/admin/providers/logs.php">View Provider &amp; Error Logs</a>
  </div>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Provider</th><th>Configured Currency</th><th>Live Balance</th><th>Imported Services</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($providers)): ?><tr><td colspan="7" style="text-align:center;color:#777083">No providers configured yet.</td></tr>
      <?php else: foreach ($providers as $p): ?>
        <tr>
          <td>#<?= (int) $p['id'] ?></td>
          <td><strong><?= e($p['name']) ?></strong><div style="font-size:11.5px;color:#777083"><?= e($p['api_url']) ?></div></td>
          <td><span class="badge badge-muted"><?= e($p['currency']) ?></span></td>
          <td style="font-weight:700"><?= e(Currency::formatRaw((string) $p['balance'], (string) ($p['balance_currency'] ?: $p['currency']), 2)) ?> <?= e($p['balance_currency'] ?: $p['currency']) ?></td>
          <td><?= (int) $p['service_count'] ?></td>
          <td><span class="badge <?= e(status_badge_class($p['status'])) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
          <td style="white-space:nowrap;display:flex;gap:5px">
            <a class="btn btn-primary btn-sm" href="/admin/providers/import.php?provider_id=<?= (int) $p['id'] ?>">Import Services</a>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="test_provider"><input type="hidden" name="provider_id" value="<?= (int) $p['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Test</button></form>
            <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_provider"><input type="hidden" name="provider_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="new_status" value="<?= $p['status']==='active'?'disabled':'active' ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $p['status']==='active'?'Disable':'Enable' ?></button></form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete provider?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_provider"><input type="hidden" name="provider_id" value="<?= (int) $p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Del</button></form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
