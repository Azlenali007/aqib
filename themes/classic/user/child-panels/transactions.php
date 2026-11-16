<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Transactions';

$txs = Database::fetchAll('SELECT * FROM child_panel_wallet_transactions WHERE child_panel_id = ? ORDER BY id DESC LIMIT 50', [$cpPanel['id']]);
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Child Panel Transactions</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Wholesale ledger history and funding records.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/transactions'); ?>
<div class="card">
  <h2 class="section-title" style="margin-bottom:14px">Wholesale Ledger (<?= count($txs) ?>)</h2>
  <?php if (empty($txs)): ?>
    <div style="text-align:center;color:var(--muted);padding:24px">No transactions yet.</div>
  <?php else: ?>
    <div class="desktop-table-wrap">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($txs as $t): ?>
            <tr>
              <td>#<?= (int) $t['id'] ?></td>
              <td><span class="badge badge-muted"><?= e(ucfirst($t['type'])) ?></span></td>
              <td><?= e($t['description']) ?></td>
              <td style="font-weight:700"><?= e(format_money((string) $t['amount'], $displayCurrency)) ?></td>
              <td><?= e(format_money((string) $t['balance_after'], $displayCurrency)) ?></td>
              <td><?= e($t['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="mobile-cards-list">
      <?php foreach ($txs as $t): ?>
        <div class="order-row-card" style="flex-direction:column;align-items:stretch;gap:8px">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
            <strong>#<?= (int) $t['id'] ?> &middot; <?= e(ucfirst($t['type'])) ?></strong>
            <strong><?= e(format_money((string) $t['amount'], $displayCurrency)) ?></strong>
          </div>
          <div style="font-size:12.5px;color:var(--muted)"><?= e($t['description']) ?></div>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted)">
            <span>After: <?= e(format_money((string) $t['balance_after'], $displayCurrency)) ?></span>
            <span><?= e($t['created_at']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
