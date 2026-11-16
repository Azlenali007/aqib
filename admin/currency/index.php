<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('currency.manage');
$adminTitle = 'Currencies & Exchange Rates';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_now') {
        $res = ExchangeRate::updateAll(true);
        Logger::activity('admin', (int) $admin['id'], 'currency.update_now', 'exchange_rates', null, ['success' => $res['success']]);
        if ($res['success']) {
            Session::flash('success', "Rates updated from {$res['source']} ({$res['updated']} currency pairs refreshed, base {$res['base']}).");
        } else {
            Session::flash('error', $res['error']);
        }
    } elseif ($action === 'save_sources') {
        set_setting('auto_update_enabled', isset($_POST['auto_update_enabled']) ? '1' : '0', 'currency');
        set_setting('primary_rate_source', trim((string) ($_POST['primary_rate_source'] ?? 'frankfurter')), 'currency');
        set_setting('fallback_rate_source', trim((string) ($_POST['fallback_rate_source'] ?? 'open_er')), 'currency');
        Session::flash('success', 'Exchange rate auto-update settings saved.');
    } elseif ($action === 'set_manual_rate') {
        try {
            $base = strtoupper(trim((string) ($_POST['base_currency'] ?? Currency::systemBase())));
            $target = strtoupper(trim((string) ($_POST['target_currency'] ?? '')));
            $rate = trim((string) ($_POST['rate'] ?? ''));
            $override = isset($_POST['is_manual_override']);
            ExchangeRate::setManualRate($base, $target, $rate, $override);
            Logger::activity('admin', (int) $admin['id'], 'currency.manual_rate', 'exchange_rates', "$base->$target", ['rate' => $rate]);
            Session::flash('success', "Exchange rate saved: 1 $base = $rate $target.");
        } catch (Throwable $ex) {
            Session::flash('error', $ex->getMessage());
        }
    } elseif ($action === 'clear_override') {
        $erId = (int) ($_POST['rate_id'] ?? 0);
        Database::execute('UPDATE exchange_rates SET is_manual_override = 0 WHERE id = ?', [$erId]);
        Session::flash('success', 'Manual override cleared; automatic sync can now update this pair.');
    } elseif ($action === 'add_currency') {
        $code = strtoupper(preg_replace('/[^A-Z]/', '', (string) ($_POST['code'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $symbol = trim((string) ($_POST['symbol'] ?? ''));
        $dec = max(0, min(4, (int) ($_POST['decimal_places'] ?? 2)));
        if (strlen($code) !== 3 || $name === '' || $symbol === '') {
            Session::flash('error', '3-letter ISO code, name, and symbol are required.');
        } else {
            Database::execute(
                'INSERT INTO currencies (code, name, symbol, decimal_places, is_base, is_active, sort_order)
                 VALUES (?, ?, ?, ?, 0, 1, 99)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), symbol = VALUES(symbol), decimal_places = VALUES(decimal_places), is_active = 1',
                [$code, $name, $symbol, $dec]
            );
            Session::flash('success', "Currency $code saved.");
        }
    } elseif ($action === 'toggle_currency') {
        $cid = (int) ($_POST['currency_id'] ?? 0);
        $curRow = Database::fetchOne('SELECT is_base, is_active FROM currencies WHERE id = ?', [$cid]);
        if ($curRow && (int) $curRow['is_base'] === 1) {
            Session::flash('error', 'Cannot disable the System Base Currency.');
        } elseif ($curRow) {
            Database::execute('UPDATE currencies SET is_active = ? WHERE id = ?', [(int) $curRow['is_active'] === 1 ? 0 : 1, $cid]);
            Session::flash('success', 'Currency status updated.');
        }
    } elseif ($action === 'set_base_currency') {
        $newBase = strtoupper(trim((string) ($_POST['base_code'] ?? '')));
        $exists = Database::fetchOne('SELECT id FROM currencies WHERE code = ? AND is_active = 1 LIMIT 1', [$newBase]);
        if (!$exists) {
            Session::flash('error', 'Target currency must be active first.');
        } else {
            Database::transaction(function () use ($newBase) {
                Database::execute('UPDATE currencies SET is_base = 0');
                Database::execute('UPDATE currencies SET is_base = 1, is_active = 1 WHERE code = ?', [$newBase]);
                set_setting('system_base_currency', $newBase, 'general');
                Database::execute(
                    "INSERT INTO exchange_rates (base_currency, target_currency, rate, source, is_manual_override, status, fetched_at)
                     VALUES (?, ?, 1.00000000, 'system_identity', 0, 'valid', NOW())
                     ON DUPLICATE KEY UPDATE rate = 1.00000000, status = 'valid', fetched_at = NOW()",
                    [$newBase, $newBase]
                );
            });
            Logger::activity('admin', (int) $admin['id'], 'currency.change_base', 'currencies', $newBase);
            Session::flash('success', "System Base Currency switched to $newBase. Click 'Update Now' to fetch fresh rates for $newBase.");
        }
    }
    Response::redirect('/admin/currency/index.php');
}

$base = Currency::systemBase();
$currencies = CurrencyService::listWithRates();
$allRates = Database::fetchAll('SELECT * FROM exchange_rates ORDER BY base_currency ASC, target_currency ASC');
$history = Database::fetchAll('SELECT * FROM exchange_rate_history ORDER BY id DESC LIMIT 40');

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-kpi-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
  <div class="adm-kpi"><div class="lbl">System Base Accounting Currency</div><div class="val" style="color:var(--p)"><?= e($base) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Last Auto/Manual Rate Fetch</div><div class="val" style="font-size:15px;margin-top:8px"><?= e((string) get_setting('last_rate_update_at', 'Never updated yet')) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Last Active Source</div><div class="val" style="font-size:17px;margin-top:6px"><?= e((string) (get_setting('last_rate_source', '—') ?: '—')) ?></div></div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="margin:0;font-size:16px">Free Exchange Rate API Sources</h2>
      <form method="post" style="margin:0">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_now">
        <button class="btn btn-primary btn-sm" type="submit">Update Rates Now</button>
      </form>
    </div>
    <p style="font-size:12.5px;color:#777083;margin:0 0 12px">No paid API keys required. If all sources fail, last valid saved rates are strictly preserved (never replaced with 1.0 or 0).</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_sources">
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Primary Free Source</label>
          <select class="form-select" name="primary_rate_source">
            <option value="frankfurter" <?= get_setting('primary_rate_source', 'frankfurter') === 'frankfurter' ? 'selected' : '' ?>>Frankfurter (ECB — Free)</option>
            <option value="open_er" <?= get_setting('primary_rate_source') === 'open_er' ? 'selected' : '' ?>>Open Exchange Rates (open.er-api.com)</option>
            <option value="fawaz_cdn" <?= get_setting('primary_rate_source') === 'fawaz_cdn' ? 'selected' : '' ?>>Fawaz Ahmed Currency CDN</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Fallback Free Source</label>
          <select class="form-select" name="fallback_rate_source">
            <option value="open_er" <?= get_setting('fallback_rate_source', 'open_er') === 'open_er' ? 'selected' : '' ?>>Open Exchange Rates (open.er-api.com)</option>
            <option value="fawaz_cdn" <?= get_setting('fallback_rate_source') === 'fawaz_cdn' ? 'selected' : '' ?>>Fawaz Ahmed Currency CDN</option>
            <option value="frankfurter" <?= get_setting('fallback_rate_source') === 'frankfurter' ? 'selected' : '' ?>>Frankfurter (ECB — Free)</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="auto_update_enabled" value="1" <?= get_setting('auto_update_enabled', '1') === '1' ? 'checked' : '' ?>> Enable Cron Auto-Update</label></div>
      <button class="btn btn-outline btn-sm" type="submit">Save Source Preferences</button>
    </form>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Manual Exchange Rate Override</h2>
    <p style="font-size:12.5px;color:#777083;margin:0 0 12px">Set exact conversion rates (e.g., <code>1 USD = 84.50 INR</code> or <code>1 INR = 0.0118 USD</code>). Overridden pairs are protected from auto-overwrite.</p>
    <form method="post" class="grid-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_manual_rate">
      <div class="form-group"><label class="form-label">From Currency</label><input class="form-control" name="base_currency" value="<?= e($base) ?>" maxlength="3" required></div>
      <div class="form-group"><label class="form-label">To Currency</label><input class="form-control" name="target_currency" placeholder="USD" maxlength="3" required></div>
      <div class="form-group"><label class="form-label">Rate (1 From = X To)</label><input class="form-control" type="number" step="0.00000001" min="0.00000001" name="rate" placeholder="84.50000000" required></div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:8px"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="is_manual_override" value="1" checked> Lock Manual Override</label></div>
      <div style="grid-column:span 2"><button class="btn btn-primary btn-sm" type="submit">Save Exchange Rate</button></div>
    </form>
  </div>
</div>

<div class="adm-card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px">
    <h2 style="margin:0;font-size:16px">Panel Currencies &amp; System Base</h2>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_currency">
      <input class="form-control" style="width:85px" name="code" placeholder="CAD" maxlength="3" required>
      <input class="form-control" style="width:150px" name="name" placeholder="Canadian Dollar" required>
      <input class="form-control" style="width:75px" name="symbol" placeholder="C$" required>
      <input class="form-control" style="width:70px" type="number" min="0" max="4" name="decimal_places" value="2">
      <button class="btn btn-outline btn-sm" type="submit">+ Add Currency</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>Code</th><th>Name</th><th>Symbol</th><th>Rate from <?= e($base) ?></th><th>Source</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($currencies as $c): ?>
        <tr>
          <td><strong><?= e($c['code']) ?></strong> <?= (int) $c['is_base'] === 1 ? '<span class="badge badge-success">System Base</span>' : '' ?></td>
          <td><?= e($c['name']) ?></td>
          <td><?= e($c['symbol']) ?></td>
          <td><?= $c['rate'] !== null ? e((string) $c['rate']) : '<span style="color:var(--err);font-weight:700">Missing Rate</span>' ?></td>
          <td><?= e((string) ($c['source'] ?? '—')) ?> <?= !empty($c['is_manual_override']) ? '<span class="badge badge-warning">Manual Lock</span>' : '' ?></td>
          <td><span class="badge <?= (int) $c['is_active'] === 1 ? 'badge-success' : 'badge-muted' ?>"><?= (int) $c['is_active'] === 1 ? 'Active' : 'Disabled' ?></span></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ((int) $c['is_base'] !== 1): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_currency"><input type="hidden" name="currency_id" value="<?= (int) $c['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= (int) $c['is_active'] === 1 ? 'Disable' : 'Enable' ?></button></form>
              <?php if ((int) $c['is_active'] === 1): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Change System Base Accounting Currency to <?= e($c['code']) ?>?')"><?= csrf_field() ?><input type="hidden" name="action" value="set_base_currency"><input type="hidden" name="base_code" value="<?= e($c['code']) ?>"><button class="btn btn-primary btn-sm" type="submit">Set Base</button></form>
              <?php endif; ?>
            <?php else: ?>
              <span style="font-size:12px;color:#777083">Primary Base</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Stored Exchange Rate Pairs</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:460px">
        <thead><tr><th>Pair</th><th>Rate</th><th>Source</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($allRates as $r): ?>
          <tr>
            <td><strong><?= e($r['base_currency']) ?> → <?= e($r['target_currency']) ?></strong></td>
            <td><?= e((string) $r['rate']) ?></td>
            <td><?= e($r['source']) ?></td>
            <td><span class="badge <?= e(status_badge_class($r['status'])) ?>"><?= e($r['status']) ?></span></td>
            <td>
              <?php if ((int) $r['is_manual_override'] === 1): ?>
                <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="clear_override"><input type="hidden" name="rate_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Unlock Auto</button></form>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Rate Update History</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:460px">
        <thead><tr><th>Pair</th><th>Rate</th><th>Source</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="5" style="text-align:center;color:#777083">No update history yet.</td></tr>
        <?php else: foreach ($history as $h): ?>
          <tr>
            <td><?= e($h['base_currency']) ?> → <?= e($h['target_currency']) ?></td>
            <td><?= e((string) $h['rate']) ?></td>
            <td><?= e($h['source']) ?></td>
            <td><span class="badge <?= $h['status'] === 'success' ? 'badge-success' : 'badge-danger' ?>"><?= e($h['status']) ?></span><?php if ($h['error_message']): ?><div style="font-size:11px;color:var(--err)"><?= e($h['error_message']) ?></div><?php endif; ?></td>
            <td><?= e($h['recorded_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
