<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('services.manage');
$sid = (int) ($_GET['id'] ?? 0);
$svc = Database::fetchOne('SELECT * FROM services WHERE id = ? LIMIT 1', [$sid]);
if (!$svc) { Session::flash('error', 'Service not found.'); Response::redirect('/admin/services/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $plat = (string) ($_POST['platform'] ?? 'other');
    Database::execute(
        'UPDATE services SET category_id=?, provider_id=?, provider_service_id=?, name=?, description=?, platform=?, icon=?, min_qty=?, max_qty=?, selling_price=?, profit_margin_percent=?, refill_supported=?, cancel_supported=?, is_featured=?, is_active=?, sort_order=? WHERE id=?',
        [
            (int) $_POST['category_id'],
            !empty($_POST['provider_id']) ? (int) $_POST['provider_id'] : null,
            trim((string) $_POST['provider_service_id']) ?: null,
            trim((string) $_POST['name']),
            trim((string) $_POST['description']),
            $plat, $plat,
            max(1, (int) $_POST['min_qty']), max(10, (int) $_POST['max_qty']),
            Currency::normalizeDecimal((string) $_POST['selling_price'], 6),
            Currency::normalizeDecimal((string) $_POST['profit_margin_percent'], 2),
            !empty($_POST['refill_supported']) ? 1 : 0,
            !empty($_POST['cancel_supported']) ? 1 : 0,
            !empty($_POST['is_featured']) ? 1 : 0,
            !empty($_POST['is_active']) ? 1 : 0,
            (int) $_POST['sort_order'],
            $sid,
        ]
    );
    Session::flash('success', 'Service updated.');
    Response::redirect('/admin/services/index.php');
}

$categories = Database::fetchAll('SELECT * FROM categories ORDER BY sort_order ASC');
$providers = Database::fetchAll('SELECT * FROM providers ORDER BY id ASC');
$adminTitle = "Edit Service #$sid";
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card" style="max-width:740px">
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Service Name</label><input class="form-control" name="name" value="<?= e($svc['name']) ?>" required></div>
      <div class="form-group"><label class="form-label">Category</label><select class="form-select" name="category_id"><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $svc['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Platform Icon</label><select class="form-select" name="platform"><?php foreach (SUPPORTED_PLATFORMS as $k => $lbl): ?><option value="<?= e($k) ?>" <?= $svc['platform']===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Provider</label><select class="form-select" name="provider_id"><option value="">Manual</option><?php foreach ($providers as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $svc['provider_id']==$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Provider Service ID</label><input class="form-control" name="provider_service_id" value="<?= e($svc['provider_service_id'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Sort Order</label><input class="form-control" type="number" name="sort_order" value="<?= (int) $svc['sort_order'] ?>"></div>
      <div class="form-group"><label class="form-label">Min Qty</label><input class="form-control" type="number" name="min_qty" value="<?= (int) $svc['min_qty'] ?>"></div>
      <div class="form-group"><label class="form-label">Max Qty</label><input class="form-control" type="number" name="max_qty" value="<?= (int) $svc['max_qty'] ?>"></div>
      <div class="form-group"><label class="form-label">Selling Price / 1K (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.0001" name="selling_price" value="<?= e((string) $svc['selling_price']) ?>"></div>
      <div class="form-group"><label class="form-label">Profit Margin (%)</label><input class="form-control" type="number" step="0.01" name="profit_margin_percent" value="<?= e((string) $svc['profit_margin_percent']) ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description"><?= e($svc['description'] ?? '') ?></textarea></div>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px">
      <label><input type="checkbox" name="is_active" value="1" <?= $svc['is_active']?'checked':'' ?>> Active</label>
      <label><input type="checkbox" name="is_featured" value="1" <?= $svc['is_featured']?'checked':'' ?>> Featured</label>
      <label><input type="checkbox" name="refill_supported" value="1" <?= $svc['refill_supported']?'checked':'' ?>> Refill Supported</label>
      <label><input type="checkbox" name="cancel_supported" value="1" <?= $svc['cancel_supported']?'checked':'' ?>> Cancel Supported</label>
    </div>
    <button class="btn btn-primary" type="submit">Save Service</button>
    <a class="btn btn-outline" href="/admin/services/index.php">&larr; Back</a>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
