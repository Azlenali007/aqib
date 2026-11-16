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

<div class="page-banner">
  <div class="page-banner-left">
    <div class="order-row-icon" style="width:46px;height:46px;border-radius:15px">
      <?= platform_icon_svg((string) $order['platform'], 28) ?>
    </div>
    <div style="min-width:0">
      <h1 class="page-banner-title">Order #<?= (int) $order['id'] ?></h1>
      <p class="page-banner-sub"><?= e($order['service_name']) ?></p>
    </div>
  </div>
  <span class="badge <?= e(status_badge_class($order['status'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
</div>

<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Quantity</div><div class="kpi-val"><?= number_format((int) $order['quantity']) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Order Charge (<?= e($currencyCtx['effective_currency']) ?>)</div><div class="kpi-val" style="color:var(--primary)"><?= e(format_money((string) $order['charge'], $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Start Count</div><div class="kpi-val"><?= $order['start_count'] !== null ? number_format((int) $order['start_count']) : '-' ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Remains</div><div class="kpi-val"><?= $order['remains'] !== null ? number_format((int) $order['remains']) : '-' ?></div></div>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Order Summary</h2>
  <p style="font-size:13.5px;margin:0 0 8px"><strong>Target Link:</strong> <span style="word-break:break-all;color:var(--primary)"><?= e($order['link']) ?></span></p>
  <p style="font-size:13.5px;margin:0;color:var(--muted)"><strong>Placed At:</strong> <?= e($order['created_at']) ?> &bull; <strong>Source:</strong> <?= e(strtoupper($order['source'])) ?></p>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
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
  <?php if (empty($logs)): ?>
    <p style="color:var(--muted);font-size:13.5px;margin:0">No timeline events recorded yet.</p>
  <?php else: foreach ($logs as $lg): ?>
    <div style="padding:11px 13px;border-radius:14px;background:#FAF8FE;border:1px solid #EFEAF9;margin-bottom:8px;font-size:13.5px">
      <strong><?= e(ucfirst($lg['new_status'])) ?></strong> — <?= e($lg['message']) ?>
      <div style="font-size:11.5px;color:var(--muted);margin-top:3px"><?= e($lg['created_at']) ?> (<?= e($lg['actor_type']) ?>)</div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
