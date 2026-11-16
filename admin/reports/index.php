<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('reports.view');
$adminTitle = 'Reports & Analytics Export';

$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) ? $_GET['to'] : date('Y-m-d');

$summary = ReportService::summary($from, $to);
$ordersByStatus = Database::fetchAll(
    'SELECT status, COUNT(*) AS cnt, COALESCE(SUM(charge),0) AS total_charge, COALESCE(SUM(profit),0) AS total_profit
     FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status',
    [$from . ' 00:00:00', $to . ' 23:59:59']
);
$providerPerf = Database::fetchAll(
    'SELECT p.name, COUNT(o.id) AS orders_count, COALESCE(SUM(o.provider_cost),0) AS cost, COALESCE(SUM(o.profit),0) AS profit
     FROM providers p LEFT JOIN orders o ON o.provider_id = p.id AND o.created_at BETWEEN ? AND ?
     GROUP BY p.id ORDER BY orders_count DESC',
    [$from . ' 00:00:00', $to . ' 23:59:59']
);
$childPanelStats = Database::fetchOne(
    'SELECT COUNT(*) AS panels_count, COALESCE(SUM(wallet_balance),0) AS total_bal FROM child_panels'
) ?: [];

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <form method="get" class="filter-bar">
    <label style="font-size:12.5px;font-weight:700">From:</label>
    <input class="form-control" style="max-width:165px" type="date" name="from" value="<?= e($from) ?>">
    <label style="font-size:12.5px;font-weight:700">To:</label>
    <input class="form-control" style="max-width:165px" type="date" name="to" value="<?= e($to) ?>">
    <button class="btn btn-primary btn-sm" type="submit">Apply Range</button>
    <a class="btn btn-outline btn-sm" href="/admin/reports/export.php?type=orders&from=<?= e($from) ?>&to=<?= e($to) ?>">Export Orders CSV</a>
    <a class="btn btn-outline btn-sm" href="/admin/reports/export.php?type=deposits&from=<?= e($from) ?>&to=<?= e($to) ?>">Export Deposits CSV</a>
    <a class="btn btn-outline btn-sm" href="/admin/reports/export.php?type=wallet&from=<?= e($from) ?>&to=<?= e($to) ?>">Export Wallet CSV</a>
  </form>
</div>

<div class="adm-kpi-grid">
  <div class="adm-kpi"><div class="lbl">Sales Revenue</div><div class="val"><?= e(format_money((string) ($summary['orders']['revenue'] ?? '0'))) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Provider Cost</div><div class="val"><?= e(format_money((string) ($summary['orders']['cost'] ?? '0'))) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Net Profit</div><div class="val" style="color:var(--ok)"><?= e(format_money((string) ($summary['orders']['profit'] ?? '0'))) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Verified Deposits (<?= (int) ($summary['deposits']['deposit_count'] ?? 0) ?>)</div><div class="val"><?= e(format_money((string) ($summary['deposits']['total_deposits'] ?? '0'))) ?></div></div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Orders Breakdown by Status</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:440px">
        <thead><tr><th>Status</th><th>Orders</th><th>Charge</th><th>Profit</th></tr></thead>
        <tbody>
        <?php if (empty($ordersByStatus)): ?><tr><td colspan="4" style="text-align:center;color:#777083">No orders in selected range.</td></tr>
        <?php else: foreach ($ordersByStatus as $r): ?>
          <tr><td><span class="badge <?= e(status_badge_class($r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= (int) $r['cnt'] ?></td><td><?= e(format_money((string) $r['total_charge'])) ?></td><td><?= e(format_money((string) $r['total_profit'])) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Provider Performance &amp; Growth</h2>
    <p style="font-size:12.5px;color:#777083;margin:0 0 10px">New Users in Period: <strong><?= (int) $summary['new_users'] ?></strong> • Child Panels: <strong><?= (int) ($childPanelStats['panels_count'] ?? 0) ?></strong></p>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:440px">
        <thead><tr><th>Provider</th><th>Orders</th><th>Cost</th><th>Profit</th></tr></thead>
        <tbody>
        <?php if (empty($providerPerf)): ?><tr><td colspan="4" style="text-align:center;color:#777083">No providers configured.</td></tr>
        <?php else: foreach ($providerPerf as $p): ?>
          <tr><td><strong><?= e($p['name']) ?></strong></td><td><?= (int) $p['orders_count'] ?></td><td><?= e(format_money((string) $p['cost'])) ?></td><td><?= e(format_money((string) $p['profit'])) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
