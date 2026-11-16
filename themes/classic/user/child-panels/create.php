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
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Launch a New Child Panel</h1>
      <p class="page-banner-sub">Isolated branded SMM panel under your own domain. Creation fee: <strong><?= e(format_money($fee, $displayCurrency)) ?></strong></p>
    </div>
  </div>
  <a href="/user/index.php?page=child-panels" class="btn btn-outline btn-sm">&larr; Back to Panels</a>
</div>

<div class="card" style="max-width:680px">
  <h2 class="section-title" style="margin-bottom:14px">Panel Configuration</h2>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Website Name</label><input class="form-control" name="site_name" placeholder="e.g. BoostGram SMM" required></div>
    <div class="form-group"><label class="form-label">Custom Domain</label><input class="form-control" name="domain" placeholder="panel.yourdomain.com" required></div>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Panel Currency</label>
        <select name="currency" class="form-select">
          <?php foreach ($activeCurrencies as $c): ?>
            <option value="<?= e($c['code']) ?>"><?= e($c['code']) ?> (<?= e($c['symbol']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Default Profit Margin (%)</label><input class="form-control" type="number" step="0.5" min="0" name="default_profit_margin" value="25.00" required></div>
    </div>
    <button class="btn btn-primary" type="submit">Create Child Panel (<?= e(format_money($fee, $displayCurrency)) ?>)</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
