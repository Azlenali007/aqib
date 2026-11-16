<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Orders';

$orders = Database::fetchAll(
    'SELECT cpo.*, cpu.username AS child_username, cps.custom_name
     FROM child_panel_orders cpo
     INNER JOIN child_panel_users cpu ON cpu.id = cpo.child_user_id
     INNER JOIN child_panel_services cps ON cps.id = cpo.child_service_id
     WHERE cpo.child_panel_id = ? ORDER BY cpo.created_at DESC LIMIT 50',
    [$cpPanel['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Child Panel Orders</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Live customer orders and net reseller margins.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/orders'); ?>
<div class="card">
  <h2 class="section-title" style="margin-bottom:14px">Recent Panel Orders (<?= count($orders) ?>)</h2>
  <?php if (empty($orders)): ?>
    <div style="text-align:center;color:var(--muted);padding:24px">No orders on this child panel yet.</div>
  <?php else: ?>
    <div class="desktop-table-wrap">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Child User</th><th>Service</th><th>Qty</th><th>Charge</th><th>Profit</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= (int) $o['id'] ?></td>
              <td><?= e($o['child_username']) ?></td>
              <td><?= e($o['custom_name']) ?></td>
              <td><?= number_format((int) $o['quantity']) ?></td>
              <td><?= e(format_money((string) $o['charge'], $displayCurrency)) ?></td>
              <td style="color:var(--success);font-weight:700"><?= e(format_money((string) $o['panel_profit'], $displayCurrency)) ?></td>
              <td><span class="badge <?= e(status_badge_class($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="mobile-cards-list">
      <?php foreach ($orders as $o): ?>
        <div class="order-row-card" style="flex-direction:column;align-items:stretch;gap:8px">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
            <strong>#<?= (int) $o['id'] ?> &middot; <?= e($o['child_username']) ?></strong>
            <span class="badge <?= e(status_badge_class($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></span>
          </div>
          <div style="font-size:13px;font-weight:600"><?= e($o['custom_name']) ?></div>
          <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--muted)">
            <span>Qty: <?= number_format((int) $o['quantity']) ?> &middot; <?= e(format_money((string) $o['charge'], $displayCurrency)) ?></span>
            <strong style="color:var(--success)">Profit: <?= e(format_money((string) $o['panel_profit'], $displayCurrency)) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
