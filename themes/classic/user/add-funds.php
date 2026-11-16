<?php
declare(strict_types=1);

$pageTitle = 'Add Funds';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['gateway'] ?? '') === 'manual') {
    Security::requireCsrf();
    $res = PaymentService::submitManualDeposit(
        (int) $user['id'],
        (string) ($_POST['amount'] ?? '0'),
        (string) ($_POST['utr_reference'] ?? ''),
        (string) ($_POST['user_note'] ?? ''),
        $_FILES['proof_file'] ?? null
    );
    Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? "Manual deposit #{$res['deposit_id']} submitted for admin verification." : $res['error']);
    Response::redirect('/user/index.php?page=payments');
}

$gateways = Database::fetchAll('SELECT * FROM payment_gateways ORDER BY sort_order ASC');
$rzpGw = null;
$manualGw = null;
foreach ($gateways as $gw) {
    if ($gw['code'] === 'razorpay') $rzpGw = $gw;
    if ($gw['code'] === 'manual') $manualGw = $gw;
}
$rzpReady = $rzpGw && (int) $rzpGw['is_active'] === 1 && Payment::razorpay()->isConfigured();
$manualReady = $manualGw && (int) $manualGw['is_active'] === 1;
$manualCfg = ($manualGw && $manualGw['config_json']) ? (json_decode($manualGw['config_json'], true) ?: []) : [];

require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">Add Funds to Wallet</h1>
    <span class="badge badge-processing">Current: <?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></span>
  </div>

  <?php if (!$rzpReady && !$manualReady): ?>
    <div class="alert alert-info">No payment gateways are currently active. An administrator must configure Razorpay or Manual Payment details in Admin &rarr; Payment Gateways before deposits can be accepted.</div>
  <?php else: ?>
    <div class="grid-2">
      <?php if ($rzpReady): ?>
        <div style="border:1px solid #EAE5F1;border-radius:18px;padding:18px;background:#FAF8FE">
          <h2 style="margin:0 0 6px;font-size:17px">Razorpay Instant Checkout</h2>
          <p style="margin:0 0 14px;font-size:13px;color:#777083">UPI, Credit/Debit Cards, NetBanking. Min: <?= e(format_money((string) $rzpGw['min_amount'])) ?> | Max: <?= e(format_money((string) $rzpGw['max_amount'])) ?></p>
          <div class="form-group">
            <label class="form-label">Amount (<?= e(Currency::systemBase()) ?>)</label>
            <input id="rzpAmount" type="number" step="0.01" min="<?= e((string) $rzpGw['min_amount']) ?>" max="<?= e((string) $rzpGw['max_amount']) ?>" class="form-control" placeholder="e.g. 500">
          </div>
          <button id="rzpPayBtn" type="button" class="btn btn-primary" style="width:100%">Pay with Razorpay</button>
          <div id="rzpStatus" style="margin-top:10px;font-size:13px"></div>
        </div>
      <?php endif; ?>

      <?php if ($manualReady): ?>
        <div style="border:1px solid #EAE5F1;border-radius:18px;padding:18px;background:#FAF8FE">
          <h2 style="margin:0 0 6px;font-size:17px">Manual Bank / UPI Transfer</h2>
          <p style="margin:0 0 10px;font-size:13px;color:#777083">Min: <?= e(format_money((string) $manualGw['min_amount'])) ?> | Max: <?= e(format_money((string) $manualGw['max_amount'])) ?></p>
          <div style="background:#fff;border:1px solid #EAE5F1;border-radius:13px;padding:12px;font-size:13px;margin-bottom:14px">
            <?php if (!empty($manualCfg['upi_id'])): ?><div><strong>UPI ID:</strong> <?= e($manualCfg['upi_id']) ?></div><?php endif; ?>
            <?php if (!empty($manualCfg['account_name'])): ?><div><strong>Account Name:</strong> <?= e($manualCfg['account_name']) ?></div><?php endif; ?>
            <?php if (!empty($manualCfg['account_number'])): ?><div><strong>Account No:</strong> <?= e($manualCfg['account_number']) ?></div><?php endif; ?>
            <?php if (!empty($manualCfg['ifsc'])): ?><div><strong>IFSC / Swift:</strong> <?= e($manualCfg['ifsc']) ?></div><?php endif; ?>
            <?php if (!empty($manualCfg['instructions'])): ?><div style="margin-top:6px;color:#777083"><?= nl2br(e($manualCfg['instructions'])) ?></div><?php endif; ?>
          </div>
          <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="gateway" value="manual">
            <div class="form-group"><label class="form-label">Amount Sent (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" name="amount" required></div>
            <div class="form-group"><label class="form-label">UTR / Transaction Reference ID</label><input class="form-control" name="utr_reference" required placeholder="Enter unique bank/UPI reference"></div>
            <div class="form-group"><label class="form-label">Payment Screenshot / Receipt (Optional)</label><input class="form-control" type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf"></div>
            <div class="form-group"><label class="form-label">Notes</label><input class="form-control" name="user_note" placeholder="Sender name or remarks"></div>
            <button type="submit" class="btn btn-primary" style="width:100%">Submit Payment Proof</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($rzpReady): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzpPayBtn')?.addEventListener('click', async () => {
  const amt = document.getElementById('rzpAmount').value;
  const statusEl = document.getElementById('rzpStatus');
  if (!amt || parseFloat(amt) <= 0) {
    statusEl.innerHTML = '<span style="color:#E5485D">Enter a valid amount.</span>';
    return;
  }
  statusEl.textContent = 'Creating secure Razorpay order...';
  const fd = new FormData();
  fd.append('_csrf', <?= json_encode(csrf_token()) ?>);
  fd.append('amount', amt);
  const res = await fetch('/user/index.php?ajax=create_razorpay_order', { method: 'POST', body: fd });
  const data = await res.json();
  if (!data.success) {
    statusEl.innerHTML = '<span style="color:#E5485D">' + (data.error || 'Order creation failed') + '</span>';
    return;
  }
  const rzp = new Razorpay({
    key: data.key_id,
    amount: data.amount_subunits,
    currency: data.currency,
    name: <?= json_encode((string) get_setting('site_name', 'SMM Panel')) ?>,
    description: 'Wallet Deposit #' + data.deposit_id,
    order_id: data.razorpay_order_id,
    handler: async function (response) {
      statusEl.textContent = 'Verifying payment signature...';
      const vfd = new FormData();
      vfd.append('_csrf', <?= json_encode(csrf_token()) ?>);
      vfd.append('razorpay_order_id', response.razorpay_order_id);
      vfd.append('razorpay_payment_id', response.razorpay_payment_id);
      vfd.append('razorpay_signature', response.razorpay_signature);
      const vres = await fetch('/user/index.php?ajax=verify_razorpay', { method: 'POST', body: vfd });
      const vdata = await vres.json();
      if (vdata.success) {
        window.location.href = '/user/index.php?page=wallet';
      } else {
        statusEl.innerHTML = '<span style="color:#E5485D">' + (vdata.error || 'Verification failed') + '</span>';
      }
    }
  });
  rzp.open();
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/layouts/footer.php'; ?>
