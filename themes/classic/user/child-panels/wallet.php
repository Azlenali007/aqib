<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Wallet';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $res = ChildPanelService::addFundsToPanel((int) $cpPanel['id'], (int) $user['id'], (string) ($_POST['amount'] ?? '0'));
    Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Funds transferred to child panel wholesale balance.' : $res['error']);
    Response::redirect('/user/index.php?page=child-panels/wallet&panel_id=' . $cpPanel['id']);
}

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h4"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Child Panel Wholesale Wallet</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Transfer funds from your main wallet into your reseller wholesale balance.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/wallet'); ?>
<div class="card" style="max-width:560px">
  <h2 class="section-title" style="margin-bottom:10px">Fund Wholesale Balance</h2>
  <div class="kpi-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:16px">
    <div class="kpi-box"><div class="kpi-label">Panel Wholesale</div><div class="kpi-val" style="color:var(--primary)"><?= e(format_money((string) $cpPanel['wallet_balance'], $displayCurrency)) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Your Main Wallet</div><div class="kpi-val"><?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></div></div>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Transfer Amount (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" min="1" name="amount" required></div>
    <button class="btn btn-primary" type="submit">Transfer Funds to Child Panel</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
