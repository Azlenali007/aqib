<?php
declare(strict_types=1);

$pageTitle = 'Favourite Services';
$services = Database::fetchAll(
    'SELECT s.*, c.name AS category_name
     FROM user_favourites uf
     INNER JOIN services s ON s.id = uf.service_id
     INNER JOIN categories c ON c.id = s.category_id
     WHERE uf.user_id = ? AND s.is_active = 1
     ORDER BY uf.created_at DESC',
    [(int) $user['id']]
);

require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Favourite Services (<?= count($services) ?>)</h1>
      <p class="page-banner-sub">Quick access to your bookmarked services in <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=services" class="view-all-pill">Browse Services &rarr;</a>
</div>

<div class="card">
  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Service</th><th>Rate / 1K</th><th>Min / Max</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($services)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No favourite services saved yet.</td></tr>
      <?php else: foreach ($services as $s): ?>
        <tr>
          <td><strong>#<?= (int) $s['id'] ?></strong></td>
          <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 22) ?><span><?= e($s['name']) ?></span></div></td>
          <td style="font-weight:700;color:var(--primary)"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?></td>
          <td><?= number_format((int) $s['min_qty']) ?> / <?= number_format((int) $s['max_qty']) ?></td>
          <td><a class="btn btn-primary btn-sm" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order Now</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mobile-cards-list">
    <?php if (empty($services)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No favourite services saved yet.</div>
    <?php else: foreach ($services as $s): ?>
      <div class="order-row-card">
        <div class="order-row-left">
          <div class="order-row-icon"><?= platform_icon_svg((string) $s['platform'], 24) ?></div>
          <div style="min-width:0">
            <div class="order-row-title"><?= e($s['name']) ?></div>
            <div class="order-row-meta"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?>/1K &bull; <?= number_format((int) $s['min_qty']) ?> - <?= number_format((int) $s['max_qty']) ?></div>
          </div>
        </div>
        <a class="btn btn-primary btn-sm" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order</a>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
