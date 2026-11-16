<?php
declare(strict_types=1);

$pageTitle = 'Child Panels';
$panels = Database::fetchAll(
    'SELECT cp.*, (SELECT domain FROM child_panel_domains WHERE child_panel_id = cp.id AND is_primary = 1 LIMIT 1) AS primary_domain,
            (SELECT COUNT(*) FROM child_panel_users WHERE child_panel_id = cp.id) AS user_count,
            (SELECT COUNT(*) FROM child_panel_orders WHERE child_panel_id = cp.id) AS order_count
     FROM child_panels cp WHERE cp.owner_user_id = ? ORDER BY cp.created_at DESC',
    [(int) $user['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">My Child Panels</h1>
    <a href="/user/index.php?page=child-panels/create" class="btn btn-primary btn-sm">+ Create Child Panel</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Panel Name</th><th>Domain</th><th>Wallet</th><th>Users / Orders</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($panels)): ?>
        <tr><td colspan="7" style="text-align:center;color:#777083;padding:24px">You haven't created any child panels yet.</td></tr>
      <?php else: foreach ($panels as $cp): ?>
        <tr>
          <td>#<?= (int) $cp['id'] ?></td>
          <td><strong><?= e($cp['site_name']) ?></strong></td>
          <td><?= e($cp['primary_domain'] ?: '-') ?></td>
          <td style="font-weight:700"><?= e(format_money((string) $cp['wallet_balance'], $displayCurrency)) ?></td>
          <td><?= (int) $cp['user_count'] ?> / <?= (int) $cp['order_count'] ?></td>
          <td><span class="badge <?= e(status_badge_class($cp['status'])) ?>"><?= e(ucfirst($cp['status'])) ?></span></td>
          <td><a class="btn btn-primary btn-sm" href="/user/index.php?page=child-panels/details&panel_id=<?= (int) $cp['id'] ?>">Manage</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
