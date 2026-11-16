<?php
declare(strict_types=1);

$orderId = (int) ($_GET['id'] ?? 0);
$order = Database::fetchOne(
    'SELECT o.*, s.name AS service_name, s.platform, s.description AS service_desc
     FROM orders o INNER JOIN services s ON s.id = o.service_id
     WHERE o.id = ? AND o.user_id = ? LIMIT 1',
    [$orderId, (int) $user['id']]
);

if (!$order) {
    Session::flash('error', 'Order not found.');
    Response::redirect('/user/index.php?page=orders');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'refill') {
        $res = OrderService::requestRefill($orderId, (int) $user['id']);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Refill request #{$res['refill_id']} submitted." : $res['error']);
    } elseif ($action === 'cancel') {
        $res = OrderService::cancelAndRefund($orderId, 'user', (int) $user['id'], 'Cancelled by user');
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Order cancelled and refunded to your wallet.' : $res['error']);
    }
    Response::redirect('/user/index.php?page=order-details&id=' . $orderId);
}

$logs = Database::fetchAll('SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC', [$orderId]);
$pageTitle = "Order #$orderId";
require __DIR__ . '/layouts/header.php';
?>

<div class="card">
  <div class="section-head">
    <div style="display:flex;align-items:center;gap:10px">
      <?= platform_icon_svg((string) $order['platform'], 28) ?>
      <div>
        <h1 class="section-title">Order #<?= (int) $order['id'] ?></h1>
        <div style="font-size:13px;color:#777083"><?= e($order['service_name']) ?></div>
      </div>
    </div>
    <span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
  </div>

  <div class="grid-3" style="margin-bottom:16px">
    <div class="kpi-box"><div class="kpi-label">Quantity</div><div class="kpi-val"><?= number_format((int) $order['quantity']) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Charge</div><div class="kpi-val"><?= e(format_money((string) $order['charge'], $displayCurrency)) ?></div></div>
    <div class="kpi-box"><div class="kpi-label">Remains / Start</div><div class="kpi-val"><?= $order['remains'] ?? '-' ?> / <?= $order['start_count'] ?? '-' ?></div></div>
  </div>

  <p style="font-size:13.5px"><strong>Target Link:</strong> <span style="word-break:break-all;color:#7045D8"><?= e($order['link']) ?></span></p>
  <p style="font-size:13.5px"><strong>Placed At:</strong> <?= e($order['created_at']) ?> &bull; <strong>Source:</strong> <?= e(strtoupper($order['source'])) ?></p>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
    <?php if ((int) $order['refill_available'] === 1 && $order['status'] === 'completed'): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="refill"><button class="btn btn-primary btn-sm" type="submit">Request Refill</button></form>
    <?php endif; ?>
    <?php if ((int) $order['cancel_available'] === 1 && in_array($order['status'], ['pending', 'processing', 'in_progress'], true)): ?>
      <form method="post" onsubmit="return confirm('Cancel this order and refund wallet?')"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button class="btn btn-danger btn-sm" type="submit">Cancel Order</button></form>
    <?php endif; ?>
    <a href="/user/index.php?page=orders" class="btn btn-outline btn-sm">&larr; Back to Orders</a>
  </div>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Order Timeline</h2>
  <?php foreach ($logs as $lg): ?>
    <div style="padding:10px 12px;border-bottom:1px solid #F2EEF9;font-size:13.5px">
      <strong><?= e(ucfirst($lg['new_status'])) ?></strong> — <?= e($lg['message']) ?>
      <div style="font-size:11.5px;color:#777083"><?= e($lg['created_at']) ?> (<?= e($lg['actor_type']) ?>)</div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
