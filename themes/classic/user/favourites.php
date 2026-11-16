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
<div class="card">
  <div class="section-head">
    <h1 class="section-title">Favourite Services</h1>
    <a href="/user/index.php?page=services" class="view-all-pill">Browse Services &rarr;</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Service</th><th>Rate / 1K</th><th>Min / Max</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($services)): ?>
        <tr><td colspan="5" style="text-align:center;color:#777083;padding:24px">No favourite services saved yet.</td></tr>
      <?php else: foreach ($services as $s): ?>
        <tr>
          <td>#<?= (int) $s['id'] ?></td>
          <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 22) ?><span><?= e($s['name']) ?></span></div></td>
          <td style="font-weight:700;color:#7045D8"><?= e(format_money((string) $s['selling_price'], $displayCurrency)) ?></td>
          <td><?= number_format((int) $s['min_qty']) ?> / <?= number_format((int) $s['max_qty']) ?></td>
          <td><a class="btn btn-primary btn-sm" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order Now</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
