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
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/orders');
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:14px">Child Panel Orders</h1>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Child User</th><th>Service</th><th>Qty</th><th>Charge</th><th>Profit</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="7" style="text-align:center;color:#777083">No orders on this child panel yet.</td></tr>
      <?php else: foreach ($orders as $o): ?>
        <tr>
          <td>#<?= (int) $o['id'] ?></td>
          <td><?= e($o['child_username']) ?></td>
          <td><?= e($o['custom_name']) ?></td>
          <td><?= number_format((int) $o['quantity']) ?></td>
          <td><?= e(format_money((string) $o['charge'], $displayCurrency)) ?></td>
          <td style="color:#16A36A;font-weight:700"><?= e(format_money((string) $o['panel_profit'], $displayCurrency)) ?></td>
          <td><span class="badge <?= e(status_badge_class($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
