<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Services';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $cpsId = (int) ($_POST['cps_id'] ?? 0);
    $enabled = !empty($_POST['is_enabled']) ? 1 : 0;
    $price = (string) ($_POST['custom_selling_price'] ?? '0');
    if ($cpsId > 0 && is_numeric($price) && (float) $price >= 0) {
        Database::execute('UPDATE child_panel_services SET is_enabled = ?, custom_selling_price = ? WHERE id = ? AND child_panel_id = ?', [$enabled, Currency::normalizeDecimal($price, 6), $cpsId, $cpPanel['id']]);
        Session::flash('success', 'Service updated.');
    }
    Response::redirect('/user/index.php?page=child-panels/services&panel_id=' . $cpPanel['id']);
}

$services = Database::fetchAll(
    'SELECT cps.*, s.name AS parent_name, s.selling_price AS parent_price, s.platform
     FROM child_panel_services cps INNER JOIN services s ON s.id = cps.parent_service_id
     WHERE cps.child_panel_id = ? ORDER BY cps.id ASC LIMIT 100',
    [$cpPanel['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/services');
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:14px">Child Panel Services (Isolated)</h1>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Service</th><th>Your Wholesale</th><th>Child Selling Price</th><th>Status</th><th>Save</th></tr></thead>
      <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="cps_id" value="<?= (int) $s['id'] ?>">
            <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 20) ?><span><?= e($s['custom_name'] ?: $s['parent_name']) ?></span></div></td>
            <td><?= e(format_money((string) $s['parent_price'], $displayCurrency)) ?></td>
            <td><input class="form-control" style="max-width:120px;padding:6px 10px" type="number" step="0.0001" name="custom_selling_price" value="<?= e((string) $s['custom_selling_price']) ?>"></td>
            <td><label style="font-size:13px"><input type="checkbox" name="is_enabled" value="1" <?= $s['is_enabled'] ? 'checked' : '' ?>> Enabled</label></td>
            <td><button class="btn btn-primary btn-sm" type="submit">Save</button></td>
          </form>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
