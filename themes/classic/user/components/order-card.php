<?php
declare(strict_types=1);
// Reusable order row card component expecting $ord array and $displayCurrency
?>
<a href="/user/index.php?page=order-details&id=<?= (int) $ord['id'] ?>" class="order-row-card">
  <div class="order-row-left">
    <div class="order-row-icon">
      <?= platform_icon_svg((string) ($ord['platform'] ?? 'other'), 26) ?>
    </div>
    <div style="min-width:0">
      <div class="order-row-title"><?= e($ord['service_name'] ?? ('Service #' . $ord['service_id'])) ?></div>
      <div class="order-row-meta">
        Qty: <?= e(format_qty((int) $ord['quantity'])) ?> &bull; Order ID: #<?= (int) $ord['id'] ?>
      </div>
    </div>
  </div>
  <div class="order-row-right">
    <span class="badge <?= e(status_badge_class((string) $ord['status'])) ?>">
      <?= e(ucfirst(str_replace('_', ' ', (string) $ord['status']))) ?>
    </span>
    <span class="chevron-circle" aria-hidden="true">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </div>
</a>
