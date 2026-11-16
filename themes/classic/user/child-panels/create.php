<?php
declare(strict_types=1);

$pageTitle = 'Create Child Panel';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $res = ChildPanelService::createPanel(
        (int) $user['id'],
        (string) ($_POST['site_name'] ?? ''),
        (string) ($_POST['domain'] ?? ''),
        (string) ($_POST['currency'] ?? Currency::systemBase()),
        (string) ($_POST['default_profit_margin'] ?? '25.00')
    );
    if ($res['success']) {
        Session::flash('success', 'Child panel created! Verify your domain TXT record and wait for admin activation.');
        Response::redirect('/user/index.php?page=child-panels/details&panel_id=' . $res['panel_id']);
    }
    Session::flash('error', $res['error']);
    Response::redirect('/user/index.php?page=child-panels/create');
}

$fee = (string) get_setting('child_panel_creation_fee', '0.00');
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="card" style="max-width:640px">
  <h1 class="section-title" style="margin-bottom:8px">Launch a New Child Panel</h1>
  <p style="color:#777083;font-size:13.5px;margin:0 0 16px">Launch an isolated branded SMM panel under your custom domain. Creation fee: <strong><?= e(format_money($fee, $displayCurrency)) ?></strong></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Website Name</label><input class="form-control" name="site_name" placeholder="e.g. BoostGram SMM" required></div>
    <div class="form-group"><label class="form-label">Custom Domain</label><input class="form-control" name="domain" placeholder="panel.yourdomain.com" required></div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Currency</label>
        <select name="currency" class="form-select">
          <?php foreach ($activeCurrencies as $c): ?>
            <option value="<?= e($c['code']) ?>"><?= e($c['code']) ?> (<?= e($c['symbol']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Default Profit Margin (%)</label><input class="form-control" type="number" step="0.5" name="default_profit_margin" value="25.00" required></div>
    </div>
    <button class="btn btn-primary" type="submit">Create Child Panel</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
