<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('orders.view');
$adminTitle = 'Order Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status' && AdminAuth::can('orders.edit', $admin)) {
        $oid = (int) ($_POST['order_id'] ?? 0);
        $newStatus = (string) ($_POST['status'] ?? '');
        if ($newStatus === 'cancelled') {
            $res = OrderService::cancelAndRefund($oid, 'admin', (int) $admin['id'], 'Cancelled by admin');
            Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Order #$oid cancelled & refunded." : $res['error']);
        } elseif (in_array($newStatus, ORDER_STATUSES, true)) {
            $old = Database::fetchValue('SELECT status FROM orders WHERE id = ?', [$oid]);
            Database::execute('UPDATE orders SET status = ?, start_count = ?, remains = ? WHERE id = ?', [$newStatus, $_POST['start_count'] !== '' ? (int) $_POST['start_count'] : null, $_POST['remains'] !== '' ? (int) $_POST['remains'] : null, $oid]);
            Database::execute('INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message) VALUES (?, "admin", ?, ?, ?, "Manual status update by admin")', [$oid, $admin['id'], $old, $newStatus]);
            Session::flash('success', "Order #$oid updated to $newStatus.");
        }
    } elseif ($action === 'bulk_orders' && AdminAuth::can('orders.edit', $admin)) {
        $ids = array_map('intval', $_POST['order_ids'] ?? []);
        $bAct = (string) ($_POST['bulk_status'] ?? '');
        $cnt = 0;
        foreach ($ids as $oid) {
            if ($bAct === 'cancelled') {
                if (OrderService::cancelAndRefund($oid, 'admin', (int) $admin['id'], 'Bulk cancelled')['success']) $cnt++;
            } elseif (in_array($bAct, ORDER_STATUSES, true)) {
                $cnt += Database::execute('UPDATE orders SET status = ? WHERE id = ?', [$bAct, $oid]);
            }
        }
        Session::flash('success', "Bulk action applied to $cnt orders.");
    }
    Response::redirect('/admin/orders/index.php');
}

$status = trim((string) ($_GET['status'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1=1'];
$params = [];
if ($status !== '') { $where[] = 'o.status = ?'; $params[] = $status; }
if ($q !== '') {
    $where[] = '(o.id = ? OR u.username LIKE ? OR o.link LIKE ? OR o.provider_order_id LIKE ?)';
    $params[] = ctype_digit($q) ? (int) $q : 0; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
$orders = Database::fetchAll(
    'SELECT o.*, u.username, s.name AS service_name, p.name AS provider_name
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     INNER JOIN services s ON s.id = o.service_id
     LEFT JOIN providers p ON p.id = o.provider_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT 100',
    $params
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
    <form method="get" class="filter-bar" style="margin:0">
      <input class="form-control" style="max-width:240px" name="q" value="<?= e($q) ?>" placeholder="Order ID, user, link, provider ID">
      <select class="form-select" style="max-width:160px" name="status">
        <option value="">All Statuses</option>
        <?php foreach (ORDER_STATUSES as $st): ?><option value="<?= e($st) ?>" <?= $status===$st?'selected':'' ?>><?= e(ucfirst(str_replace('_',' ',$st))) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    </form>
    <?php if (AdminAuth::can('orders.export', $admin)): ?>
      <a class="btn btn-outline btn-sm" href="/admin/orders/export.php">Export CSV</a>
    <?php endif; ?>
  </div>

  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bulk_orders">
    <div class="bulk-bar">
      <strong id="bulkSelectedCount" style="font-size:13px;color:#4D28B5">0 selected</strong>
      <select name="bulk_status" class="form-select" style="max-width:200px">
        <option value="">Bulk Set Status...</option>
        <?php foreach (ORDER_STATUSES as $st): ?><option value="<?= e($st) ?>"><?= e(ucfirst(str_replace('_',' ',$st))) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Apply to Selected</button>
    </div>

    <div class="table-responsive">
      <table class="adm-table">
        <thead><tr><th><input type="checkbox" id="bulkSelectAll"></th><th>ID</th><th>User</th><th>Service &amp; Provider</th><th>Qty</th><th>Charge / Profit</th><th>Status</th><th>Manage</th></tr></thead>
        <tbody>
        <?php if (empty($orders)): ?><tr><td colspan="8" style="text-align:center;color:#777083">No orders found.</td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td><input type="checkbox" class="bulk-row-cb" name="order_ids[]" value="<?= (int) $o['id'] ?>"></td>
            <td><strong>#<?= (int) $o['id'] ?></strong><?php if ($o['provider_order_id']): ?><div style="font-size:11px;color:#777083">Ext: <?= e($o['provider_order_id']) ?></div><?php endif; ?></td>
            <td><?= e($o['username']) ?></td>
            <td><strong><?= e($o['service_name']) ?></strong><div style="font-size:11.5px;color:#777083"><?= e($o['provider_name'] ?: 'Manual') ?> &bull; <?= e($o['link']) ?></div></td>
            <td><?= number_format((int) $o['quantity']) ?></td>
            <td><?= e(format_money((string) $o['charge'])) ?><div style="font-size:11px;color:#16A36A">+<?= e(format_money((string) $o['profit'])) ?></div></td>
            <td><span class="badge <?= e(status_badge_class($o['status'])) ?>"><?= e(ucfirst(str_replace('_',' ',$o['status']))) ?></span></td>
            <td><a class="btn btn-outline btn-sm" href="/admin/orders/view.php?id=<?= (int) $o['id'] ?>">Edit</a></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
