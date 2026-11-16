<?php
declare(strict_types=1);

$pageTitle = 'Payment History';
$deposits = Database::fetchAll('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [(int) $user['id']]);
require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">Payment &amp; Deposit History</h1>
    <a href="/user/index.php?page=add-funds" class="btn btn-primary btn-sm">+ Add Funds</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Receipt #</th><th>Gateway</th><th>Amount</th><th>Net Credit</th><th>Reference / UTR</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($deposits)): ?>
        <tr><td colspan="7" style="text-align:center;color:#777083">No payment records found.</td></tr>
      <?php else: foreach ($deposits as $d): ?>
        <tr>
          <td><strong>#<?= (int) $d['id'] ?></strong></td>
          <td><?= e(ucfirst($d['gateway_code'])) ?></td>
          <td><?= e(format_money((string) $d['amount'], $displayCurrency)) ?></td>
          <td style="font-weight:700;color:#16A36A"><?= e(format_money((string) $d['net_credit'], $displayCurrency)) ?></td>
          <td><?= e($d['gateway_payment_id'] ?: ($d['utr_reference'] ?: $d['gateway_order_id'] ?: '-')) ?></td>
          <td>
            <span class="badge <?= e(status_badge_class($d['status'])) ?>"><?= e(ucfirst($d['status'])) ?></span>
            <?php if ($d['admin_note']): ?><div style="font-size:11px;color:#777083"><?= e($d['admin_note']) ?></div><?php endif; ?>
          </td>
          <td><?= e($d['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
