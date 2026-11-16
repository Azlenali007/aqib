<?php
declare(strict_types=1);
// Reusable order row card component expecting $ord array and $displayCurrency
$ordStatus = strtolower((string) ($ord['status'] ?? 'pending'));
$showCheck = in_array($ordStatus, ['completed', 'processing', 'in_progress', 'active'], true);
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
        <?php if (isset($ord['charge'])): ?>
          &bull; <strong style="color:var(--primary)"><?= e(format_money((string) $ord['charge'], $displayCurrency)) ?></strong>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="order-row-right">
    <span class="badge <?= e(status_badge_class($ordStatus)) ?>">
      <?php if ($showCheck): ?>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="#16A36A" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8.5 12.2l2.3 2.3 4.7-4.8" stroke="#fff" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?php endif; ?>
      <?= e(ucfirst(str_replace('_', ' ', $ordStatus))) ?>
    </span>
    <span class="chevron-circle" aria-hidden="true">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    </span>
  </div>
</a>
