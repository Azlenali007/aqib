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
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title"><?= e($cpPanel['site_name']) ?> (#<?= (int) $cpPanel['id'] ?>)</h1>
      <p class="page-banner-sub">Isolated tenant overview, wholesale balance, and reseller profit metrics.</p>
    </div>
  </div>
  <span class="badge <?= e(status_badge_class($cpPanel['status'])) ?>"><?= e(ucfirst($cpPanel['status'])) ?></span>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/details'); ?>
<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Panel Wholesale Balance</div><div class="kpi-val" style="color:var(--primary)"><?= e(format_money((string) $cpPanel['wallet_balance'], $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Isolated Users</div><div class="kpi-val"><?= (int) ($stats['users'] ?? 0) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Panel Orders</div><div class="kpi-val"><?= (int) ($stats['orders'] ?? 0) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Net Panel Profit</div><div class="kpi-val" style="color:var(--success)"><?= e(format_money((string) ($stats['profit'] ?? '0'), $displayCurrency)) ?></div></div>
</div>
<div class="card">
  <div class="section-head">
    <h2 class="section-title">Panel Summary</h2>
    <a class="btn btn-primary btn-sm" href="/user/index.php?page=child-panels/wallet&panel_id=<?= (int) $cpPanel['id'] ?>">Fund Wholesale Wallet</a>
  </div>
  <div style="font-size:13.5px;color:var(--muted);line-height:1.7">
    <div><strong>Total Panel Revenue:</strong> <?= e(format_money((string) ($stats['revenue'] ?? '0'), $displayCurrency)) ?></div>
    <div><strong>Default Profit Margin:</strong> <?= e((string) $cpPanel['default_profit_margin']) ?>%</div>
    <div><strong>Panel API Key:</strong> <code style="background:#F4EFFC;padding:3px 8px;border-radius:8px;color:var(--primary)"><?= e($cpPanel['api_key']) ?></code></div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
