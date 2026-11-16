<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('services.view');
$adminTitle = 'Services & Bulk Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && AdminAuth::can('services.manage', $admin)) {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_service') {
        $cost = (string) ($_POST['provider_cost_base'] ?? '0');
        $sell = (string) ($_POST['selling_price'] ?? '0');
        $plat = (string) ($_POST['platform'] ?? 'other');
        Database::execute(
            'INSERT INTO services (category_id, provider_id, provider_service_id, name, description, platform, icon, min_qty, max_qty, provider_price, provider_currency, provider_cost_base, system_base_currency, selling_price, refill_supported, cancel_supported, is_featured, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                (int) $_POST['category_id'],
                !empty($_POST['provider_id']) ? (int) $_POST['provider_id'] : null,
                trim((string) ($_POST['provider_service_id'] ?? '')) ?: null,
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')),
                $plat,
                $plat,
                max(1, (int) $_POST['min_qty']),
                max(10, (int) $_POST['max_qty']),
                Currency::normalizeDecimal($cost, 6),
                Currency::systemBase(),
                Currency::normalizeDecimal($cost, 6),
                Currency::systemBase(),
                Currency::normalizeDecimal($sell, 6),
                !empty($_POST['refill_supported']) ? 1 : 0,
                !empty($_POST['cancel_supported']) ? 1 : 0,
                !empty($_POST['is_featured']) ? 1 : 0,
            ]
        );
        Session::flash('success', 'Service added.');
    } elseif ($action === 'bulk') {
        $res = ServiceManager::bulkAction((string) ($_POST['bulk_action'] ?? ''), $_POST['service_ids'] ?? [], $_POST, (int) $admin['id']);
        if ($res['success']) {
            $msg = "Bulk operation finished: {$res['success_count']} succeeded, {$res['fail_count']} failed.";
            if (!empty($res['failures'])) {
                $msg .= ' First error: #' . $res['failures'][0]['id'] . ' (' . $res['failures'][0]['reason'] . ')';
            }
            Session::flash($res['fail_count'] > 0 && $res['success_count'] === 0 ? 'error' : 'success', $msg);
        } else {
            Session::flash('error', $res['error']);
        }
    }
    Response::redirect('/admin/services/index.php');
}

$categories = Database::fetchAll('SELECT * FROM categories ORDER BY sort_order ASC');
$providers = Database::fetchAll('SELECT * FROM providers ORDER BY id ASC');
$catFilter = (int) ($_GET['category_id'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];
if ($catFilter > 0) { $where[] = 's.category_id = ?'; $params[] = $catFilter; }
if ($q !== '') { $where[] = '(s.name LIKE ? OR s.id = ?)'; $params[] = "%$q%"; $params[] = ctype_digit($q) ? (int) $q : 0; }

$services = Database::fetchAll(
    'SELECT s.*, c.name AS category_name, p.name AS provider_name
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     LEFT JOIN providers p ON p.id = s.provider_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY s.category_id ASC, s.sort_order ASC, s.id DESC LIMIT 200',
    $params
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<?php if (AdminAuth::can('services.manage', $admin)): ?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Create Service</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?><input type="hidden" name="action" value="create_service">
    <input class="form-control" name="name" placeholder="Service Name" required>
    <select class="form-select" name="category_id" required><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
    <select class="form-select" name="platform"><?php foreach (SUPPORTED_PLATFORMS as $k => $lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?></select>
    <input class="form-control" type="number" name="min_qty" value="10" placeholder="Min Qty" required>
    <input class="form-control" type="number" name="max_qty" value="50000" placeholder="Max Qty" required>
    <input class="form-control" type="number" step="0.0001" name="provider_cost_base" value="0" placeholder="Cost (<?= e(Currency::systemBase()) ?>)">
    <input class="form-control" type="number" step="0.0001" name="selling_price" placeholder="Selling Price / 1K (<?= e(Currency::systemBase()) ?>)" required>
    <select class="form-select" name="provider_id"><option value="">Manual (No Provider)</option><?php foreach ($providers as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
    <input class="form-control" name="provider_service_id" placeholder="Provider Service ID">
    <div style="grid-column:1/-1;display:flex;gap:14px;flex-wrap:wrap;align-items:center">
      <label><input type="checkbox" name="refill_supported" value="1"> Refill</label>
      <label><input type="checkbox" name="cancel_supported" value="1"> Cancel</label>
      <label><input type="checkbox" name="is_featured" value="1"> Featured</label>
      <button class="btn btn-primary" type="submit">+ Add Service</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="adm-card">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px">
    <form method="get" class="filter-bar" style="margin:0">
      <input class="form-control" style="max-width:220px" name="q" value="<?= e($q) ?>" placeholder="Search service ID or name">
      <select class="form-select" style="max-width:200px" name="category_id"><option value="0">All Categories</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $catFilter===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
      <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    </form>
    <a class="btn btn-outline btn-sm" href="/admin/services/export.php">Export CSV</a>
  </div>

  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="bulk">
    <?php if (AdminAuth::can('services.manage', $admin)) require dirname(__DIR__) . '/components/bulk-toolbar.php'; ?>
    <div class="table-responsive">
      <table class="adm-table">
        <thead><tr><th><input type="checkbox" id="bulkSelectAll"></th><th>ID</th><th>Service</th><th>Provider &amp; Original Cost</th><th>Base Cost</th><th>Selling / 1K</th><th>Margin</th><th>Status</th><th>Edit</th></tr></thead>
        <tbody>
        <?php if (empty($services)): ?><tr><td colspan="9" style="text-align:center;color:#777083">No services yet. Add manually or import from a Provider.</td></tr>
        <?php else: foreach ($services as $s): ?>
          <tr>
            <td><input type="checkbox" class="bulk-row-cb" name="service_ids[]" value="<?= (int) $s['id'] ?>"></td>
            <td>#<?= (int) $s['id'] ?></td>
            <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 20) ?><div><strong><?= e($s['name']) ?></strong><div style="font-size:11.5px;color:#777083"><?= e($s['category_name']) ?></div></div></div></td>
            <td><?= e($s['provider_name'] ?: 'Manual') ?><?php if ($s['provider_currency']): ?><div style="font-size:11.5px;color:#777083"><?= e(Currency::formatRaw((string) $s['provider_price'], (string) $s['provider_currency'], 4)) ?> <?= e($s['provider_currency']) ?> (x<?= e((string) $s['provider_exchange_rate']) ?>)</div><?php endif; ?></td>
            <td><?= e(format_money((string) $s['provider_cost_base'], null, 4)) ?></td>
            <td style="font-weight:700;color:#7045D8"><?= e(format_money((string) $s['selling_price'], null, 4)) ?></td>
            <td><?= e((string) $s['profit_margin_percent']) ?>%</td>
            <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $s['is_active'] ? 'Active' : 'Disabled' ?></span></td>
            <td><a class="btn btn-outline btn-sm" href="/admin/services/edit.php?id=<?= (int) $s['id'] ?>">Edit</a></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
