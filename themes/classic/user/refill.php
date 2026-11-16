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
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Refill Requests</h1>
      <p class="page-banner-sub">Monitor refill status updates synced directly from providers</p>
    </div>
  </div>
  <a href="/user/index.php?page=orders" class="view-all-pill">My Orders &rarr;</a>
</div>

<div class="card">
  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead><tr><th>Refill ID</th><th>Order ID</th><th>Service</th><th>Status</th><th>Requested</th></tr></thead>
      <tbody>
      <?php if (empty($refills)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No refill requests submitted yet.</td></tr>
      <?php else: foreach ($refills as $rf): ?>
        <tr>
          <td><strong>#<?= (int) $rf['id'] ?></strong></td>
          <td><a href="/user/index.php?page=order-details&id=<?= (int) $rf['order_id'] ?>" style="color:var(--primary);font-weight:700">#<?= (int) $rf['order_id'] ?></a></td>
          <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $rf['platform'], 20) ?><span><?= e($rf['service_name']) ?></span></div></td>
          <td><span class="badge <?= e(status_badge_class($rf['status'])) ?>"><?= e(ucfirst($rf['status'])) ?></span></td>
          <td><?= e($rf['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mobile-cards-list">
    <?php if (empty($refills)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No refill requests submitted yet.</div>
    <?php else: foreach ($refills as $rf): ?>
      <a href="/user/index.php?page=order-details&id=<?= (int) $rf['order_id'] ?>" class="order-row-card">
        <div class="order-row-left">
          <div class="order-row-icon"><?= platform_icon_svg((string) $rf['platform'], 24) ?></div>
          <div style="min-width:0">
            <div class="order-row-title"><?= e($rf['service_name']) ?></div>
            <div class="order-row-meta">Refill #<?= (int) $rf['id'] ?> &bull; Order #<?= (int) $rf['order_id'] ?></div>
          </div>
        </div>
        <span class="badge <?= e(status_badge_class($rf['status'])) ?>"><?= e(ucfirst($rf['status'])) ?></span>
      </a>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
