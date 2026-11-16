<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('orders.view');
$oid = (int) ($_GET['id'] ?? 0);
$order = Database::fetchOne('SELECT o.*, u.username, s.name AS service_name FROM orders o INNER JOIN users u ON u.id=o.user_id INNER JOIN services s ON s.id=o.service_id WHERE o.id = ? LIMIT 1', [$oid]);
if (!$order) { Session::flash('error', 'Order not found.'); Response::redirect('/admin/orders/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && AdminAuth::can('orders.edit', $admin)) {
    Security::requireCsrf();
    $newStatus = (string) ($_POST['status'] ?? $order['status']);
    if ($newStatus === 'cancelled' && $order['status'] !== 'cancelled') {
        $res = OrderService::cancelAndRefund($oid, 'admin', (int) $admin['id'], 'Admin cancelled order');
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Order cancelled and refunded.' : $res['error']);
    } elseif (in_array($newStatus, ORDER_STATUSES, true)) {
        Database::execute('UPDATE orders SET status = ?, provider_order_id = ?, start_count = ?, remains = ? WHERE id = ?', [$newStatus, trim((string) $_POST['provider_order_id']) ?: null, $_POST['start_count'] !== '' ? (int) $_POST['start_count'] : null, $_POST['remains'] !== '' ? (int) $_POST['remains'] : null, $oid]);
        Database::execute('INSERT INTO order_logs (order_id, actor_type, actor_id, old_status, new_status, message) VALUES (?, "admin", ?, ?, ?, "Edited by admin")', [$oid, $admin['id'], $order['status'], $newStatus]);
        Session::flash('success', 'Order updated.');
    }
    Response::redirect('/admin/orders/view.php?id=' . $oid);
}

$logs = Database::fetchAll('SELECT * FROM order_logs WHERE order_id = ? ORDER BY id DESC', [$oid]);
$adminTitle = "Edit Order #$oid";
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card" style="max-width:680px">
  <h2 style="margin:0 0 12px;font-size:17px">Order #<?= $oid ?> — <?= e($order['service_name']) ?></h2>
  <p style="font-size:13px;color:#777083">User: <strong><?= e($order['username']) ?></strong> | Link: <?= e($order['link']) ?> | Qty: <?= (int) $order['quantity'] ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (ORDER_STATUSES as $st): ?><option value="<?= e($st) ?>" <?= $order['status']===$st?'selected':'' ?>><?= e(ucfirst(str_replace('_',' ',$st))) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Provider Order ID</label><input class="form-control" name="provider_order_id" value="<?= e($order['provider_order_id'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Start Count</label><input class="form-control" type="number" name="start_count" value="<?= e((string) ($order['start_count'] ?? '')) ?>"></div>
      <div class="form-group"><label class="form-label">Remains</label><input class="form-control" type="number" name="remains" value="<?= e((string) ($order['remains'] ?? '')) ?>"></div>
    </div>
    <button class="btn btn-primary" type="submit">Update Order</button>
    <a class="btn btn-outline" href="/admin/orders/index.php">&larr; Back</a>
  </form>
</div>
<div class="adm-card">
  <h3 style="margin:0 0 10px;font-size:15px">Order Logs</h3>
  <?php foreach ($logs as $l): ?><div style="padding:8px 0;border-bottom:1px solid #F2EEF9;font-size:13px"><strong><?= e($l['new_status']) ?></strong> — <?= e($l['message']) ?> <span style="color:#777083">(<?= e($l['created_at']) ?>)</span></div><?php endforeach; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
