<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('payments.view');
$adminTitle = 'Payments & Deposits';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (!AdminAuth::can('payments.approve', $admin)) {
        Session::flash('error', 'Missing payments.approve permission.');
        Response::redirect('/admin/payments/index.php');
    }
    $action = $_POST['action'] ?? '';
    $depId = (int) ($_POST['deposit_id'] ?? 0);
    $note = trim((string) ($_POST['admin_note'] ?? ''));

    if ($action === 'approve_manual') {
        $res = PaymentService::approveManualDeposit($depId, (int) $admin['id'], $note);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Manual deposit #$depId approved and credited." : $res['error']);
    } elseif ($action === 'reject_manual') {
        $res = PaymentService::rejectManualDeposit($depId, (int) $admin['id'], $note !== '' ? $note : 'Rejected by admin');
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Manual deposit #$depId rejected." : $res['error']);
    }
    Response::redirect('/admin/payments/index.php');
}

$status = trim((string) ($_GET['status'] ?? ''));
$gateway = trim((string) ($_GET['gateway'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 'd.status = ?';
    $params[] = $status;
}
if ($gateway !== '') {
    $where[] = 'd.gateway_code = ?';
    $params[] = $gateway;
}
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR d.utr_reference LIKE ? OR d.gateway_order_id LIKE ? OR d.gateway_payment_id LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$deposits = Database::fetchAll(
    'SELECT d.*, u.username, u.email
     FROM deposits d
     INNER JOIN users u ON u.id = d.user_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY d.id DESC LIMIT 150',
    $params
);

$pendingCount = (int) Database::fetchValue("SELECT COUNT(*) FROM deposits WHERE gateway_code = 'manual' AND status = 'pending'", [], 0);
$totalApproved = (string) Database::fetchValue("SELECT COALESCE(SUM(net_credit),0) FROM deposits WHERE status IN ('paid','approved')", [], '0');
$gatewayLogs = Database::fetchAll(
    'SELECT p.*, u.username FROM payments p INNER JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 40'
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-kpi-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
  <div class="adm-kpi"><div class="lbl">Pending Manual Deposits</div><div class="val" style="color:var(--warn)"><?= $pendingCount ?></div></div>
  <div class="adm-kpi"><div class="lbl">Total Verified Deposits</div><div class="val" style="color:var(--ok)"><?= e(format_money($totalApproved)) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Webhook Endpoint</div><div class="val" style="font-size:13px;word-break:break-all;margin-top:8px"><?= e(app_url('/api/razorpay-webhook.php')) ?></div></div>
</div>

<div class="adm-card">
  <form method="get" class="filter-bar">
    <input class="form-control" style="max-width:240px" name="q" value="<?= e($q) ?>" placeholder="Search user, UTR, order/payment ID">
    <select class="form-select" style="max-width:150px" name="gateway">
      <option value="">All Gateways</option>
      <option value="razorpay" <?= $gateway === 'razorpay' ? 'selected' : '' ?>>Razorpay</option>
      <option value="manual" <?= $gateway === 'manual' ? 'selected' : '' ?>>Manual</option>
    </select>
    <select class="form-select" style="max-width:150px" name="status">
      <option value="">All Statuses</option>
      <?php foreach (['pending', 'paid', 'approved', 'rejected', 'failed'] as $st): ?>
        <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <a class="btn btn-outline btn-sm" href="/admin/gateways/index.php">Gateway Settings</a>
  </form>

  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>User</th><th>Gateway</th><th>Net Credit</th><th>Fee / Paid</th><th>Reference / Proof</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($deposits)): ?>
        <tr><td colspan="9" style="text-align:center;color:#777083">No deposit records found.</td></tr>
      <?php else: foreach ($deposits as $d): ?>
        <tr>
          <td>#<?= (int) $d['id'] ?></td>
          <td><strong><a href="/admin/users/view.php?id=<?= (int) $d['user_id'] ?>"><?= e($d['username']) ?></a></strong><div style="font-size:11px;color:#777083"><?= e($d['email']) ?></div></td>
          <td><?= e(ucfirst($d['gateway_code'])) ?></td>
          <td style="font-weight:700"><?= e(format_money((string) $d['net_credit'])) ?></td>
          <td><span style="font-size:12px"><?= e(format_money((string) $d['amount'])) ?></span><div style="font-size:11px;color:#777083">Fee: <?= e(format_money((string) $d['fee'])) ?></div></td>
          <td>
            <?php if (!empty($d['utr_reference'])): ?><div>UTR: <code><?= e($d['utr_reference']) ?></code></div><?php endif; ?>
            <?php if (!empty($d['gateway_payment_id'])): ?><div>Pay ID: <code><?= e($d['gateway_payment_id']) ?></code></div><?php endif; ?>
            <?php if (!empty($d['user_note'])): ?><div style="font-size:11.5px;color:#777083"><?= e($d['user_note']) ?></div><?php endif; ?>
            <?php if (!empty($d['proof_file'])): ?>
              <a class="btn btn-outline btn-sm" style="margin-top:4px" target="_blank" href="/admin/payments/proof.php?id=<?= (int) $d['id'] ?>">View Proof</a>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= e(status_badge_class($d['status'])) ?>"><?= e(ucfirst($d['status'])) ?></span>
            <?php if (!empty($d['admin_note'])): ?><div style="font-size:11px;color:#777083;margin-top:3px"><?= e($d['admin_note']) ?></div><?php endif; ?>
          </td>
          <td><?= e(substr($d['created_at'], 0, 16)) ?></td>
          <td>
            <?php if ($d['gateway_code'] === 'manual' && $d['status'] === 'pending' && AdminAuth::can('payments.approve', $admin)): ?>
              <form method="post" style="display:flex;flex-direction:column;gap:5px;min-width:160px">
                <?= csrf_field() ?>
                <input type="hidden" name="deposit_id" value="<?= (int) $d['id'] ?>">
                <input class="form-control" style="padding:5px 8px;font-size:12px" name="admin_note" placeholder="Admin note / reason">
                <div style="display:flex;gap:5px">
                  <button class="btn btn-success btn-sm" type="submit" name="action" value="approve_manual" onclick="return confirm('Approve & credit wallet?')">Approve</button>
                  <button class="btn btn-danger btn-sm" type="submit" name="action" value="reject_manual" onclick="return confirm('Reject this manual deposit?')">Reject</button>
                </div>
              </form>
            <?php else: ?>
              <span style="font-size:12px;color:#777083">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Verified Gateway Payment Logs</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Deposit #</th><th>User</th><th>Gateway</th><th>External ID</th><th>Event</th><th>Amount</th><th>Verified</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($gatewayLogs)): ?>
        <tr><td colspan="9" style="text-align:center;color:#777083">No payment gateway logs recorded yet.</td></tr>
      <?php else: foreach ($gatewayLogs as $p): ?>
        <tr>
          <td>#<?= (int) $p['id'] ?></td>
          <td>#<?= (int) $p['deposit_id'] ?></td>
          <td><?= e($p['username']) ?></td>
          <td><?= e(ucfirst($p['gateway_code'])) ?></td>
          <td><code><?= e($p['external_id'] ?? '—') ?></code></td>
          <td><?= e($p['event_type']) ?></td>
          <td><?= e(format_money((string) $p['amount'])) ?></td>
          <td><?= (int) $p['signature_verified'] === 1 ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-danger">No</span>' ?></td>
          <td><?= e($p['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
