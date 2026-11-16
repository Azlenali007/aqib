<?php
declare(strict_types=1);

$pageTitle = 'Payment History';
$deposits = Database::fetchAll('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [(int) $user['id']]);
require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Payment &amp; Deposit History</h1>
      <p class="page-banner-sub">All Razorpay &amp; manual transfer deposits displayed in <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=add-funds" class="btn btn-primary btn-sm">+ Add Funds</a>
</div>

<div class="card">
  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead><tr><th>Receipt #</th><th>Gateway</th><th>Amount</th><th>Net Credit</th><th>Reference / UTR</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($deposits)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">No payment records found.</td></tr>
      <?php else: foreach ($deposits as $d): ?>
        <tr>
          <td><strong>#<?= (int) $d['id'] ?></strong></td>
          <td><?= e(ucfirst($d['gateway_code'])) ?></td>
          <td><?= e(format_money((string) $d['amount'], $displayCurrency)) ?></td>
          <td style="font-weight:700;color:#16A36A"><?= e(format_money((string) $d['net_credit'], $displayCurrency)) ?></td>
          <td><?= e($d['gateway_payment_id'] ?: ($d['utr_reference'] ?: $d['gateway_order_id'] ?: '-')) ?></td>
          <td>
            <span class="badge <?= e(status_badge_class($d['status'])) ?>"><?= e(ucfirst($d['status'])) ?></span>
            <?php if ($d['admin_note']): ?><div style="font-size:11px;color:var(--muted)"><?= e($d['admin_note']) ?></div><?php endif; ?>
          </td>
          <td><?= e($d['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mobile-cards-list">
    <?php if (empty($deposits)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No payment records found.</div>
    <?php else: foreach ($deposits as $d): ?>
      <div class="order-row-card">
        <div style="min-width:0">
          <div style="font-weight:700;font-size:14px">Receipt #<?= (int) $d['id'] ?> &bull; <?= e(ucfirst($d['gateway_code'])) ?></div>
          <div class="order-row-meta">Ref: <?= e($d['gateway_payment_id'] ?: ($d['utr_reference'] ?: $d['gateway_order_id'] ?: '-')) ?> &bull; <?= e($d['created_at']) ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-weight:800;color:#16A36A"><?= e(format_money((string) $d['net_credit'], $displayCurrency)) ?></div>
          <span class="badge <?= e(status_badge_class($d['status'])) ?>"><?= e(ucfirst($d['status'])) ?></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
