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
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/wallet');
?>
<div class="card" style="max-width:540px">
  <h1 class="section-title" style="margin-bottom:8px">Fund Child Panel Wholesale Wallet</h1>
  <p style="color:#777083;font-size:13.5px;margin:0 0 14px">
    Panel Wholesale Balance: <strong><?= e(format_money((string) $cpPanel['wallet_balance'], $displayCurrency)) ?></strong><br>
    Your Main Wallet: <strong><?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></strong>
  </p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Transfer Amount (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" min="1" name="amount" required></div>
    <button class="btn btn-primary" type="submit">Transfer Funds to Child Panel</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
