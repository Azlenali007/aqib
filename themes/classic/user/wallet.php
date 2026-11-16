<?php
declare(strict_types=1);

$pageTitle = 'Wallet';
$recentTx = Database::fetchAll('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10', [(int) $user['id']]);
$recentDep = Database::fetchAll('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10', [(int) $user['id']]);

require __DIR__ . '/layouts/header.php';
?>
<div class="wallet-card" style="margin-bottom:18px">
  <div class="wallet-left">
    <div class="wallet-icon-box" aria-hidden="true">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M16 12h4v2h-4a1 1 0 0 1 0-2z" fill="#fff"/></svg>
    </div>
    <div>
      <div class="wallet-label">Available Balance (Display: <?= e($currencyCtx['effective_currency']) ?> &bull; Accounting: <?= e(Currency::systemBase()) ?>)</div>
      <div class="wallet-amount"><?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></div>
      <span class="wallet-status-pill">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        <?= $user['status'] === 'active' ? 'Active Account' : ucfirst($user['status']) ?>
      </span>
    </div>
  </div>
  <div class="wallet-right">
    <div class="wallet-divider"></div>
    <a href="/user/index.php?page=add-funds" class="add-funds-pill">
      <span>+ Add Funds</span>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Total Deposited</div><div class="kpi-val" style="color:#16A36A"><?= e(format_money((string) ($user['total_deposited'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Total Spent</div><div class="kpi-val"><?= e(format_money((string) ($user['total_spent'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Total Refunded</div><div class="kpi-val" style="color:var(--primary)"><?= e(format_money((string) ($user['total_refunded'] ?? '0'), $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Display Currency</div><div class="kpi-val"><?= e($currencyCtx['effective_currency']) ?></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="section-head"><h2 class="section-title">Recent Ledger Entries</h2><a href="/user/index.php?page=transactions" class="view-all-pill">All &rarr;</a></div>
    <div class="table-responsive">
      <table class="data-table" style="min-width:420px">
        <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Balance</th></tr></thead>
        <tbody>
        <?php if (empty($recentTx)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">No transactions yet.</td></tr>
        <?php else: foreach ($recentTx as $tx): ?>
          <tr>
            <td><span class="badge <?= in_array($tx['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($tx['type'])) ?></span></td>
            <td><?= e($tx['description']) ?></td>
            <td style="font-weight:700"><?= e(format_money((string) $tx['amount'], $displayCurrency)) ?></td>
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
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">No deposits yet.</td></tr>
        <?php else: foreach ($recentDep as $dep): ?>
          <tr>
            <td><strong>#<?= (int) $dep['id'] ?></strong></td>
            <td><?= e(ucfirst($dep['gateway_code'])) ?></td>
            <td style="font-weight:700"><?= e(format_money((string) $dep['net_credit'], $displayCurrency)) ?></td>
            <td><span class="badge <?= e(status_badge_class($dep['status'])) ?>"><?= e(ucfirst($dep['status'])) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
