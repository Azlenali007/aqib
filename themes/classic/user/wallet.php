<?php
declare(strict_types=1);

$pageTitle = 'Wallet';
$recentTx = Database::fetchAll('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10', [(int) $user['id']]);
$recentDep = Database::fetchAll('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10', [(int) $user['id']]);

require __DIR__ . '/layouts/header.php';
?>
<div class="wallet-card" style="margin-bottom:18px">
  <div class="wallet-left">
    <div class="wallet-icon-box"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M16 12h4v2h-4a1 1 0 0 1 0-2z" fill="#fff"/></svg></div>
    <div>
      <div class="wallet-label">Available Balance (Base: <?= e(Currency::systemBase()) ?>)</div>
      <div class="wallet-amount"><?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></div>
    </div>
  </div>
  <div class="wallet-right">
    <a href="/user/index.php?page=add-funds" class="add-funds-pill">+ Add Funds &rarr;</a>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Total Deposited</div><div class="kpi-val" style="color:#16A36A"><?= e(format_money((string) ($user['total_deposited'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Total Spent</div><div class="kpi-val"><?= e(format_money((string) ($user['total_spent'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Total Refunded</div><div class="kpi-val" style="color:#7045D8"><?= e(format_money((string) ($user['total_refunded'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Account Status</div><div class="kpi-val"><?= e(ucfirst($user['status'])) ?></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="section-head"><h2 class="section-title">Recent Ledger Entries</h2><a href="/user/index.php?page=transactions" class="view-all-pill">All &rarr;</a></div>
    <div class="table-responsive">
      <table class="data-table" style="min-width:420px">
        <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Balance</th></tr></thead>
        <tbody>
        <?php if (empty($recentTx)): ?>
          <tr><td colspan="4" style="text-align:center;color:#777083">No transactions yet.</td></tr>
        <?php else: foreach ($recentTx as $tx): ?>
          <tr>
            <td><span class="badge <?= in_array($tx['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($tx['type'])) ?></span></td>
            <td><?= e($tx['description']) ?></td>
            <td><?= e(format_money((string) $tx['amount'], $displayCurrency)) ?></td>
            <td><?= e(format_money((string) $tx['balance_after'], $displayCurrency)) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="section-head"><h2 class="section-title">Recent Deposits</h2><a href="/user/index.php?page=payments" class="view-all-pill">All &rarr;</a></div>
    <div class="table-responsive">
      <table class="data-table" style="min-width:380px">
        <thead><tr><th>ID</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (empty($recentDep)): ?>
          <tr><td colspan="4" style="text-align:center;color:#777083">No deposits yet.</td></tr>
        <?php else: foreach ($recentDep as $dep): ?>
          <tr>
            <td>#<?= (int) $dep['id'] ?></td>
            <td><?= e(ucfirst($dep['gateway_code'])) ?></td>
            <td><?= e(format_money((string) $dep['net_credit'], $displayCurrency)) ?></td>
            <td><span class="badge <?= e(status_badge_class($dep['status'])) ?>"><?= e(ucfirst($dep['status'])) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
