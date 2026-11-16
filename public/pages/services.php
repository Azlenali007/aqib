<?php
declare(strict_types=1);
$q = trim((string) ($_GET['q'] ?? ''));
$catId = (int) ($_GET['category_id'] ?? 0);
$cats = CategoryManager::allActive();
$where = ['s.is_active = 1', 'c.is_active = 1'];
$params = [];
if ($catId > 0) { $where[] = 's.category_id = ?'; $params[] = $catId; }
if ($q !== '') { $where[] = 's.name LIKE ?'; $params[] = "%$q%"; }
$services = Database::fetchAll('SELECT s.*, c.name AS cat_name FROM services s INNER JOIN categories c ON c.id = s.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY c.sort_order ASC, s.sort_order ASC', $params);
$baseCurr = Currency::systemBase();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Services — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px">
  <div class="card">
    <h1 style="margin:0 0 14px">Services Price List</h1>
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
      <input type="hidden" name="page" value="services">
      <input name="q" value="<?= e($q) ?>" placeholder="Search services..." style="padding:10px 14px;border:1px solid #EAE5F1;border-radius:12px;min-width:220px">
      <select name="category_id" style="padding:10px 14px;border:1px solid #EAE5F1;border-radius:12px">
        <option value="0">All Categories</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="table-responsive">
      <table>
        <thead><tr><th>ID</th><th>Service</th><th>Category</th><th>Rate / 1K</th><th>Min / Max</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($services)): ?>
          <tr><td colspan="6" style="text-align:center;color:#777083">No active services listed yet.</td></tr>
        <?php else: foreach ($services as $s): ?>
          <tr>
            <td>#<?= (int) $s['id'] ?></td>
            <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $s['platform'], 20) ?><span><?= e($s['name']) ?></span></div></td>
            <td><?= e($s['cat_name']) ?></td>
            <td style="font-weight:700;color:#7045D8"><?= e(format_money((string) $s['selling_price'], $baseCurr)) ?></td>
            <td><?= number_format((int) $s['min_qty']) ?> / <?= number_format((int) $s['max_qty']) ?></td>
            <td><a class="btn btn-outline" style="padding:6px 12px;font-size:12.5px" href="/user/index.php?page=new-order&service_id=<?= (int) $s['id'] ?>">Order</a></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
