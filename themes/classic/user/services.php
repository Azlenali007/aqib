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

<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Services Catalog (<?= count($services) ?>)</h1>
      <p class="page-banner-sub">Rates shown per 1,000 in <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=favourites" class="view-all-pill">Favourites ★</a>
</div>

<div class="card">
  <form method="get" class="filter-bar">
    <input type="hidden" name="page" value="services">
    <input class="form-control" style="max-width:260px" name="q" value="<?= e($q) ?>" placeholder="Search service ID or name...">
    <select class="form-select" style="max-width:230px" name="category_id">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>

  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Service</th><th>Rate / 1K</th><th>Min / Max</th><th>Features</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($services)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">No services found.</td></tr>
      <?php else: foreach ($services as $s): $isFav = in_array((int) $s['id'], $favIds, true); ?>
        <tr>
          <td><strong>#<?= (int) $s['id'] ?></strong></td>
          <td>
            <div style="display:flex;align-items:center;gap:9px">
              <?= platform_icon_svg((string) $s['platform'], 22) ?>
              <div>
                <div style="font-weight:700"><?= e($s['name']) ?></div>
                <div style="font-size:11.5px;color:var(--muted)"><?= e($s['category_name']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-weight:800;color:var(--primary)"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?></td>
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

  <div class="mobile-cards-list">
    <?php if (empty($services)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No services found.</div>
    <?php else: foreach ($services as $s): $isFav = in_array((int) $s['id'], $favIds, true); ?>
      <div class="order-row-card" style="flex-direction:column;align-items:stretch;gap:10px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
          <div class="order-row-left">
            <div class="order-row-icon"><?= platform_icon_svg((string) $s['platform'], 24) ?></div>
            <div style="min-width:0">
              <div class="order-row-title" style="white-space:normal">#<?= (int) $s['id'] ?> &bull; <?= e($s['name']) ?></div>
              <div class="order-row-meta"><?= e($s['category_name']) ?> &bull; Min <?= number_format((int) $s['min_qty']) ?> - Max <?= number_format((int) $s['max_qty']) ?></div>
            </div>
          </div>
          <span class="price-pill" style="flex-shrink:0"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?>/1K</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
          <div style="display:flex;gap:6px">
            <?php if ($s['refill_supported']): ?><span class="badge badge-success">Refill</span><?php endif; ?>
            <?php if ($s['cancel_supported']): ?><span class="badge badge-warning">Cancel</span><?php endif; ?>
          </div>
          <div style="display:flex;gap:6px;margin-left:auto">
            <button type="button" data-fav-btn data-service-id="<?= (int) $s['id'] ?>" data-csrf="<?= e(csrf_token()) ?>" class="btn btn-outline btn-sm"><?= $isFav ? '★' : '☆' ?></button>
            <a class="btn btn-primary btn-sm" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order Now</a>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
