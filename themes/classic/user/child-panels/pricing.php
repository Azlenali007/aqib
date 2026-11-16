<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Pricing';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $margin = (string) ($_POST['default_profit_margin'] ?? '25');
    if (is_numeric($margin) && (float) $margin >= 0) {
        $normMargin = Currency::normalizeDecimal($margin, 2);
        Database::execute('UPDATE child_panels SET default_profit_margin = ? WHERE id = ? AND owner_user_id = ?', [$normMargin, $cpPanel['id'], $user['id']]);
        if (!empty($_POST['apply_all'])) {
            $svcs = Database::fetchAll('SELECT cps.id, s.selling_price FROM child_panel_services cps INNER JOIN services s ON s.id = cps.parent_service_id WHERE cps.child_panel_id = ?', [$cpPanel['id']]);
            $mult = Currency::add('1', Currency::div($normMargin, '100', 6), 6);
            foreach ($svcs as $sv) {
                $newPrice = Currency::mul((string) $sv['selling_price'], $mult, 6);
                Database::execute('UPDATE child_panel_services SET profit_margin_percent = ?, custom_selling_price = ? WHERE id = ? AND child_panel_id = ?', [$normMargin, $newPrice, $sv['id'], $cpPanel['id']]);
            }
        }
        Session::flash('success', 'Child panel profit margin updated.');
    }
    Response::redirect('/user/index.php?page=child-panels/pricing&panel_id=' . $cpPanel['id']);
}

require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/pricing');
?>
<div class="card" style="max-width:560px">
  <h1 class="section-title" style="margin-bottom:14px">Global Profit Margin &amp; Pricing</h1>
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Default Markup Margin (%)</label><input class="form-control" type="number" step="0.5" name="default_profit_margin" value="<?= e((string) $cpPanel['default_profit_margin']) ?>" required></div>
    <div class="form-group"><label style="display:flex;align-items:center;gap:8px;font-size:13.5px"><input type="checkbox" name="apply_all" value="1"> Recalculate all services on this child panel with this margin</label></div>
    <button class="btn btn-primary" type="submit">Update Pricing</button>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
