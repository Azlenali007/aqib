<?php
declare(strict_types=1);

$pageTitle = 'Services';
$catFilter = (int) ($_GET['category_id'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));

$categories = CategoryManager::allActive();
$where = ['s.is_active = 1', 'c.is_active = 1'];
$params = [];
if ($catFilter > 0) {
    $where[] = 's.category_id = ?';
    $params[] = $catFilter;
}
if ($q !== '') {
    $where[] = '(s.name LIKE ? OR s.id = ?)';
    $params[] = "%$q%";
    $params[] = ctype_digit($q) ? (int) $q : 0;
}

$services = Database::fetchAll(
    'SELECT s.*, c.name AS category_name
     FROM services s INNER JOIN categories c ON c.id = s.category_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC',
    $params
);

$favIds = array_map('intval', array_column(Database::fetchAll('SELECT service_id FROM user_favourites WHERE user_id = ?', [(int) $user['id']]), 'service_id'));

require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">All Services (<?= count($services) ?>)</h1>
    <a href="/user/index.php?page=favourites" class="view-all-pill">Favourites ★</a>
  </div>

  <form method="get" class="filter-bar">
    <input type="hidden" name="page" value="services">
    <input class="form-control" style="max-width:250px" name="q" value="<?= e($q) ?>" placeholder="Search service ID or name...">
    <select class="form-select" style="max-width:220px" name="category_id">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>

  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Service</th><th>Rate / 1K</th><th>Min / Max</th><th>Features</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($services)): ?>
        <tr><td colspan="6" style="text-align:center;color:#777083;padding:24px">No services found.</td></tr>
      <?php else: foreach ($services as $s): $isFav = in_array((int) $s['id'], $favIds, true); ?>
        <tr>
          <td><strong>#<?= (int) $s['id'] ?></strong></td>
          <td>
            <div style="display:flex;align-items:center;gap:9px">
              <?= platform_icon_svg((string) $s['platform'], 22) ?>
              <div>
                <div style="font-weight:700"><?= e($s['name']) ?></div>
                <div style="font-size:11.5px;color:#777083"><?= e($s['category_name']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-weight:700;color:#7045D8"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?></td>
          <td><?= number_format((int) $s['min_qty']) ?> / <?= number_format((int) $s['max_qty']) ?></td>
          <td>
            <?php if ($s['refill_supported']): ?><span class="badge badge-success">Refill</span><?php endif; ?>
            <?php if ($s['cancel_supported']): ?><span class="badge badge-warning">Cancel</span><?php endif; ?>
          </td>
          <td style="white-space:nowrap">
            <button type="button" data-fav-btn data-service-id="<?= (int) $s['id'] ?>" data-csrf="<?= e(csrf_token()) ?>" class="btn btn-outline btn-sm"><?= $isFav ? '★ Favourited' : '☆ Favourite' ?></button>
            <a class="btn btn-primary btn-sm" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
