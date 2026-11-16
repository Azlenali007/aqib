<?php
declare(strict_types=1);

$pageTitle = 'Orders';
require __DIR__ . '/components/pagination.php';

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['from'] ?? ''));
$dateTo = trim((string) ($_GET['to'] ?? ''));
$pageNum = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($pageNum - 1) * $perPage;

$where = ['o.user_id = ?'];
$params = [(int) $user['id']];

if ($statusFilter !== '' && in_array($statusFilter, ORDER_STATUSES, true)) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    if (ctype_digit(ltrim($search, '#'))) {
        $where[] = 'o.id = ?';
        $params[] = (int) ltrim($search, '#');
    } else {
        $where[] = '(o.link LIKE ? OR s.name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
}
if ($dateFrom !== '') {
    $where[] = 'o.created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'o.created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereSql = implode(' AND ', $where);
$totalRows = (int) Database::fetchValue(
    "SELECT COUNT(*) FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE $whereSql",
    $params,
    0
);
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$orders = Database::fetchAll(
    "SELECT o.*, s.name AS service_name, s.platform
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE $whereSql
     ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

require __DIR__ . '/layouts/header.php';
?>

<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Orders History (<?= number_format($totalRows) ?>)</h1>
      <p class="page-banner-sub">Track delivery progress, start counts, remains, and charges in <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=new-order" class="btn btn-primary btn-sm">+ New Order</a>
</div>

<div class="card">
  <form method="get" class="filter-bar">
    <input type="hidden" name="page" value="orders">
    <input class="form-control" style="max-width:220px" name="q" value="<?= e($search) ?>" placeholder="Order ID, link, or service">
    <select class="form-select" style="max-width:165px" name="status">
      <option value="">All Statuses</option>
      <?php foreach (ORDER_STATUSES as $st): ?>
        <option value="<?= e($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $st))) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="form-control" style="max-width:145px" type="date" name="from" value="<?= e($dateFrom) ?>">
    <input class="form-control" style="max-width:145px" type="date" name="to" value="<?= e($dateTo) ?>">
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>

  <div class="table-responsive desktop-table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Service</th>
          <th>Link</th>
          <th>Quantity</th>
          <th>Charge</th>
          <th>Start / Remains</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">No orders match your filter criteria.</td></tr>
      <?php else: foreach ($orders as $ord): ?>
        <tr>
          <td><strong>#<?= (int) $ord['id'] ?></strong></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <?= platform_icon_svg((string) $ord['platform'], 20) ?>
              <span style="font-weight:600"><?= e($ord['service_name']) ?></span>
            </div>
          </td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($ord['link']) ?></td>
          <td><?= number_format((int) $ord['quantity']) ?></td>
          <td style="font-weight:700;color:var(--primary)"><?= e(format_money((string) $ord['charge'], $displayCurrency)) ?></td>
          <td><?= $ord['start_count'] !== null ? number_format((int) $ord['start_count']) : '-' ?> / <?= $ord['remains'] !== null ? number_format((int) $ord['remains']) : '-' ?></td>
          <td><span class="badge <?= e(status_badge_class($ord['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $ord['status']))) ?></span></td>
          <td><a class="btn btn-outline btn-sm" href="/user/index.php?page=order-details&id=<?= (int) $ord['id'] ?>">Details</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="mobile-cards-list">
    <?php if (empty($orders)): ?>
      <div style="text-align:center;color:var(--muted);padding:20px">No orders match your filter criteria.</div>
    <?php else: foreach ($orders as $ord): ?>
      <?php require __DIR__ . '/components/order-card.php'; ?>
    <?php endforeach; endif; ?>
  </div>

  <?php render_pagination($pageNum, $totalPages, '/user/index.php?page=orders&status=' . urlencode($statusFilter) . '&q=' . urlencode($search) . '&from=' . urlencode($dateFrom) . '&to=' . urlencode($dateTo)); ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
