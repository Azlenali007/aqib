<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Transactions';

$txs = Database::fetchAll('SELECT * FROM child_panel_wallet_transactions WHERE child_panel_id = ? ORDER BY id DESC LIMIT 50', [$cpPanel['id']]);
require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/transactions');
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:14px">Child Panel Transactions</h1>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($txs)): ?>
        <tr><td colspan="6" style="text-align:center;color:#777083">No transactions yet.</td></tr>
      <?php else: foreach ($txs as $t): ?>
        <tr>
          <td>#<?= (int) $t['id'] ?></td>
          <td><span class="badge badge-muted"><?= e(ucfirst($t['type'])) ?></span></td>
          <td><?= e($t['description']) ?></td>
          <td><?= e(format_money((string) $t['amount'], $displayCurrency)) ?></td>
          <td><?= e(format_money((string) $t['balance_after'], $displayCurrency)) ?></td>
          <td><?= e($t['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
