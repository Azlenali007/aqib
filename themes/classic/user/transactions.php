<?php
declare(strict_types=1);

$pageTitle = 'Transactions';
require __DIR__ . '/components/pagination.php';

$p = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;
$offset = ($p - 1) * $perPage;
$total = (int) Database::fetchValue('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?', [(int) $user['id']], 0);
$rows = Database::fetchAll("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT $perPage OFFSET $offset", [(int) $user['id']]);

require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Wallet Transactions</h1>
      <p class="page-banner-sub">Complete ledger history converted to <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=wallet" class="view-all-pill">Wallet Summary &rarr;</a>
</div>

<div class="card">
  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Type</th><th>Description</th><th>Amount</th><th>Before</th><th>After</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">No transactions recorded yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><strong>#<?= (int) $r['id'] ?></strong></td>
          <td><span class="badge <?= in_array($r['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($r['type'])) ?></span></td>
          <td><?= e($r['description']) ?></td>
          <td style="font-weight:800;color:var(--primary)"><?= e(format_money((string) $r['amount'], $displayCurrency)) ?></td>
          <td><?= e(format_money((string) $r['balance_before'], $displayCurrency)) ?></td>
          <td><?= e(format_money((string) $r['balance_after'], $displayCurrency)) ?></td>
          <td><?= e($r['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mobile-cards-list">
    <?php if (empty($rows)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No transactions recorded yet.</div>
    <?php else: foreach ($rows as $r): ?>
      <div class="order-row-card">
        <div style="min-width:0">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
            <span class="badge <?= in_array($r['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($r['type'])) ?></span>
            <strong style="font-size:13px">#<?= (int) $r['id'] ?></strong>
          </div>
          <div style="font-size:13px;font-weight:700"><?= e($r['description']) ?></div>
          <div class="order-row-meta"><?= e($r['created_at']) ?> &bull; After: <?= e(format_money((string) $r['balance_after'], $displayCurrency)) ?></div>
        </div>
        <div style="font-weight:800;color:var(--primary);flex-shrink:0"><?= e(format_money((string) $r['amount'], $displayCurrency)) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <?php render_pagination($p, max(1, (int) ceil($total / $perPage)), '/user/index.php?page=transactions'); ?>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
