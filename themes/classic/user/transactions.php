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
<div class="card">
  <h1 class="section-title" style="margin-bottom:14px">Wallet Transactions</h1>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Type</th><th>Description</th><th>Amount</th><th>Before</th><th>After</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="text-align:center;color:#777083">No transactions recorded yet.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int) $r['id'] ?></td>
          <td><span class="badge <?= in_array($r['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($r['type'])) ?></span></td>
          <td><?= e($r['description']) ?></td>
          <td style="font-weight:700"><?= e(format_money((string) $r['amount'], $displayCurrency)) ?></td>
          <td><?= e(format_money((string) $r['balance_before'], $displayCurrency)) ?></td>
          <td><?= e(format_money((string) $r['balance_after'], $displayCurrency)) ?></td>
          <td><?= e($r['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php render_pagination($p, max(1, (int) ceil($total / $perPage)), '/user/index.php?page=transactions'); ?>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
