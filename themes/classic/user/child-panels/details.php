<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Overview';

$stats = Database::fetchOne(
    'SELECT (SELECT COUNT(*) FROM child_panel_users WHERE child_panel_id = ?) AS users,
            (SELECT COUNT(*) FROM child_panel_orders WHERE child_panel_id = ?) AS orders,
            (SELECT COALESCE(SUM(charge), 0) FROM child_panel_orders WHERE child_panel_id = ?) AS revenue,
            (SELECT COALESCE(SUM(panel_profit), 0) FROM child_panel_orders WHERE child_panel_id = ?) AS profit',
    [$cpPanel['id'], $cpPanel['id'], $cpPanel['id'], $cpPanel['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/details');
?>
<div class="card">
  <div class="section-head">
    <div>
      <h1 class="section-title"><?= e($cpPanel['site_name']) ?> (#<?= (int) $cpPanel['id'] ?>)</h1>
      <div style="font-size:12.5px;color:#777083">API Key: <code><?= e($cpPanel['api_key']) ?></code></div>
    </div>
    <span class="badge <?= e(status_badge_class($cpPanel['status'])) ?>"><?= e(ucfirst($cpPanel['status'])) ?></span>
  </div>
  <div class="kpi-grid">
    <div class="kpi-box"><div class="kpi-label">Panel Wholesale Balance</div><div class="kpi-val" style="color:#7045D8"><?= e(format_money((string) $cpPanel['wallet_balance'], $displayCurrency)) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Isolated Users</div><div class="kpi-val"><?= (int) ($stats['users'] ?? 0) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Panel Orders</div><div class="kpi-val"><?= (int) ($stats['orders'] ?? 0) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Net Panel Profit</div><div class="kpi-val" style="color:#16A36A"><?= e(format_money((string) ($stats['profit'] ?? '0'), $displayCurrency)) ?></div></div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
