<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Services';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $cpsId = (int) ($_POST['cps_id'] ?? 0);
    $enabled = !empty($_POST['is_enabled']) ? 1 : 0;
    $price = (string) ($_POST['custom_selling_price'] ?? '0');
    if ($cpsId > 0 && is_numeric($price) && (float) $price >= 0) {
        Database::execute('UPDATE child_panel_services SET is_enabled = ?, custom_selling_price = ? WHERE id = ? AND child_panel_id = ?', [$enabled, Currency::normalizeDecimal($price, 6), $cpsId, $cpPanel['id']]);
        Session::flash('success', 'Service updated.');
    }
    Response::redirect('/user/index.php?page=child-panels/services&panel_id=' . $cpPanel['id']);
}

$services = Database::fetchAll(
    'SELECT cps.*, s.name AS parent_name, s.selling_price AS parent_price, s.platform
     FROM child_panel_services cps INNER JOIN services s ON s.id = cps.parent_service_id
     WHERE cps.child_panel_id = ? ORDER BY cps.id ASC LIMIT 100',
    [$cpPanel['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Child Panel Services</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Manage isolated catalog pricing (base inputs in <?= e(Currency::systemBase()) ?>).</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/services'); ?>
<div class="card">
  <div class="section-head">
    <h2 class="section-title">Isolated Service Catalog (<?= count($services) ?>)</h2>
  </div>
  <?php if (empty($services)): ?>
    <div style="text-align:center;color:var(--muted);padding:24px">No child panel services configured yet.</div>
  <?php else: ?>
    <div class="desktop-table-wrap">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>Service</th><th>Your Wholesale</th><th>Display Selling</th><th>Set Selling (<?= e(Currency::systemBase()) ?>)</th><th>Status</th><th>Save</th></tr></thead>
          <tbody>
          <?php foreach ($services as $s): $fid = 'cps-form-' . (int) $s['id']; ?>
            <tr>
              <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 20) ?><span><?= e($s['custom_name'] ?: $s['parent_name']) ?></span></div></td>
              <td><?= e(format_money((string) $s['parent_price'], $displayCurrency)) ?></td>
              <td style="font-weight:700;color:var(--primary)"><?= e(format_money((string) $s['custom_selling_price'], $displayCurrency)) ?></td>
              <td><input form="<?= e($fid) ?>" class="form-control" style="max-width:125px;padding:6px 10px" type="number" step="0.0001" name="custom_selling_price" value="<?= e((string) $s['custom_selling_price']) ?>"></td>
              <td><label style="font-size:13px"><input form="<?= e($fid) ?>" type="checkbox" name="is_enabled" value="1" <?= $s['is_enabled'] ? 'checked' : '' ?>> Enabled</label></td>
              <td>
                <form id="<?= e($fid) ?>" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="cps_id" value="<?= (int) $s['id'] ?>">
                  <button class="btn btn-primary btn-sm" type="submit">Save</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="mobile-cards-list">
      <?php foreach ($services as $s): ?>
        <form method="post" class="order-row-card" style="flex-direction:column;align-items:stretch;gap:10px">
          <?= csrf_field() ?>
          <input type="hidden" name="cps_id" value="<?= (int) $s['id'] ?>">
          <div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 20) ?><strong><?= e($s['custom_name'] ?: $s['parent_name']) ?></strong></div>
          <div style="font-size:12px;color:var(--muted)">Wholesale: <?= e(format_money((string) $s['parent_price'], $displayCurrency)) ?> &middot; Selling: <strong><?= e(format_money((string) $s['custom_selling_price'], $displayCurrency)) ?></strong></div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input class="form-control" style="max-width:130px;padding:6px 10px" type="number" step="0.0001" name="custom_selling_price" value="<?= e((string) $s['custom_selling_price']) ?>">
            <label style="font-size:13px"><input type="checkbox" name="is_enabled" value="1" <?= $s['is_enabled'] ? 'checked' : '' ?>> Enabled</label>
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
