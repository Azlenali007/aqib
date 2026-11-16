<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
$admin = AdminAuth::requireAuth('dashboard.view');
$adminTitle = 'Admin Dashboard';

$userStats = Database::fetchOne("SELECT COUNT(*) AS total, SUM(status='active') AS active FROM users") ?: [];
$orderStats = Database::fetchOne(
    "SELECT COUNT(*) AS total,
            SUM(status='pending') AS pending,
            SUM(status IN ('processing','in_progress')) AS processing,
            SUM(status='completed') AS completed,
            SUM(status IN ('cancelled','refunded')) AS cancelled,
            COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN charge ELSE 0 END), 0) AS revenue,
            COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN profit ELSE 0 END), 0) AS profit
     FROM orders"
) ?: [];
$walletSum = (string) Database::fetchValue('SELECT COALESCE(SUM(balance),0) FROM wallets', [], '0');
$depositSum = (string) Database::fetchValue("SELECT COALESCE(SUM(net_credit),0) FROM deposits WHERE status IN ('paid','approved')", [], '0');
$providerCount = (int) Database::fetchValue("SELECT COUNT(*) FROM providers WHERE status='active'", [], 0);
$childPanelCount = (int) Database::fetchValue("SELECT COUNT(*) FROM child_panels", [], 0);

// 7-Day real sales chart data
$chartDays = Database::fetchAll(
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt, COALESCE(SUM(charge), 0) AS rev
     FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status NOT IN ('cancelled','refunded','failed')
     GROUP BY DATE(created_at) ORDER BY day ASC"
);
$maxRev = 1.0;
foreach ($chartDays as $cd) { if ((float) $cd['rev'] > $maxRev) $maxRev = (float) $cd['rev']; }

$recentOrders = Database::fetchAll('SELECT o.*, u.username, s.name AS service_name FROM orders o INNER JOIN users u ON u.id = o.user_id INNER JOIN services s ON s.id = o.service_id ORDER BY o.created_at DESC LIMIT 8');
$recentDeposits = Database::fetchAll('SELECT d.*, u.username FROM deposits d INNER JOIN users u ON u.id = d.user_id ORDER BY d.created_at DESC LIMIT 8');

require __DIR__ . '/layouts/header.php';
?>
<div class="adm-kpi-grid">
  <div class="adm-kpi"><div class="lbl">Total Users (Active)</div><div class="val"><?= (int) ($userStats['total'] ?? 0) ?> <small style="font-size:13px;color:#16A36A">(<?= (int) ($userStats['active'] ?? 0) ?>)</small></div></div>
  <div class="adm-kpi"><div class="lbl">Total Orders</div><div class="val"><?= (int) ($orderStats['total'] ?? 0) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Gross Revenue</div><div class="val" style="color:#7045D8"><?= e(format_money((string) ($orderStats['revenue'] ?? '0'))) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Net Profit</div><div class="val" style="color:#16A36A"><?= e(format_money((string) ($orderStats['profit'] ?? '0'))) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Pending / Processing</div><div class="val"><?= (int) ($orderStats['pending'] ?? 0) ?> / <?= (int) ($orderStats['processing'] ?? 0) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Completed / Cancelled</div><div class="val"><?= (int) ($orderStats['completed'] ?? 0) ?> / <?= (int) ($orderStats['cancelled'] ?? 0) ?></div></div>
  <div class="adm-kpi"><div class="lbl">User Wallets / Deposits</div><div class="val" style="font-size:18px"><?= e(format_money($walletSum)) ?> / <?= e(format_money($depositSum)) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Providers / Child Panels</div><div class="val"><?= $providerCount ?> / <?= $childPanelCount ?></div></div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">7-Day Sales Revenue</h2>
  <?php if (empty($chartDays)): ?>
    <p style="color:#777083;font-size:13.5px;margin:0">No order revenue recorded in the last 7 days yet.</p>
  <?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:12px;height:140px;padding-top:12px">
      <?php foreach ($chartDays as $cd): $h = max(8, (int) round(((float) $cd['rev'] / $maxRev) * 110)); ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
          <div style="font-size:11px;font-weight:700;color:#7045D8"><?= e(format_money((string) $cd['rev'])) ?></div>
          <div style="width:100%;max-width:44px;height:<?= $h ?>px;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,#8B5CF6,#5B42F3)"></div>
          <div style="font-size:11px;color:#777083"><?= e(substr($cd['day'], 5)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="grid-2">
  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <h2 style="margin:0;font-size:16px">Recent Orders</h2>
      <a href="/admin/orders/index.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:440px">
        <thead><tr><th>ID</th><th>User</th><th>Service</th><th>Charge</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($recentOrders)): ?><tr><td colspan="5" style="text-align:center;color:#777083">No orders yet.</td></tr>
        <?php else: foreach ($recentOrders as $o): ?>
          <tr>
            <td>#<?= (int) $o['id'] ?></td>
            <td><?= e($o['username']) ?></td>
            <td><?= e($o['service_name']) ?></td>
            <td><?= e(format_money((string) $o['charge'])) ?></td>
            <td><span class="badge <?= e(status_badge_class($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <h2 style="margin:0;font-size:16px">Recent Deposits</h2>
      <a href="/admin/payments/index.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:420px">
        <thead><tr><th>ID</th><th>User</th><th>Gateway</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($recentDeposits)): ?><tr><td colspan="5" style="text-align:center;color:#777083">No deposits yet.</td></tr>
        <?php else: foreach ($recentDeposits as $d): ?>
          <tr>
            <td>#<?= (int) $d['id'] ?></td>
            <td><?= e($d['username']) ?></td>
            <td><?= e(ucfirst($d['gateway_code'])) ?></td>
            <td><?= e(format_money((string) $d['net_credit'])) ?></td>
            <td><span class="badge <?= e(status_badge_class($d['status'])) ?>"><?= e(ucfirst($d['status'])) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
