<?php
declare(strict_types=1);

$pageTitle = 'Refill Requests';
$refills = Database::fetchAll(
    'SELECT r.*, o.link, s.name AS service_name, s.platform
     FROM refill_requests r
     INNER JOIN orders o ON o.id = r.order_id
     INNER JOIN services s ON s.id = o.service_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 50',
    [(int) $user['id']]
);

require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:14px">Refill Requests</h1>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Refill ID</th><th>Order ID</th><th>Service</th><th>Status</th><th>Requested</th></tr></thead>
      <tbody>
      <?php if (empty($refills)): ?>
        <tr><td colspan="5" style="text-align:center;color:#777083">No refill requests submitted yet.</td></tr>
      <?php else: foreach ($refills as $rf): ?>
        <tr>
          <td>#<?= (int) $rf['id'] ?></td>
          <td><a href="/user/index.php?page=order-details&id=<?= (int) $rf['order_id'] ?>" style="color:#7045D8;font-weight:700">#<?= (int) $rf['order_id'] ?></a></td>
          <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $rf['platform'], 20) ?><span><?= e($rf['service_name']) ?></span></div></td>
          <td><span class="badge <?= e(status_badge_class($rf['status'])) ?>"><?= e(ucfirst($rf['status'])) ?></span></td>
          <td><?= e($rf['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
