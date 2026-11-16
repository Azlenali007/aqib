<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('gateways.manage');
$adminTitle = 'Payment Gateways';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $code = trim((string) ($_POST['code'] ?? ''));

    if ($code === 'razorpay') {
        $row = Database::fetchOne('SELECT config_json FROM payment_gateways WHERE code = "razorpay" LIMIT 1');
        $cur = $row && $row['config_json'] ? (json_decode($row['config_json'], true) ?: []) : [];

        $keyId = trim((string) ($_POST['key_id'] ?? ''));
        $newSecret = trim((string) ($_POST['key_secret'] ?? ''));
        $newWebhook = trim((string) ($_POST['webhook_secret'] ?? ''));
        $mode = ($_POST['mode'] ?? 'test') === 'live' ? 'live' : 'test';

        $cfg = [
            'key_id' => $keyId,
            'key_secret' => $newSecret !== '' ? Security::encrypt($newSecret) : ($cur['key_secret'] ?? ''),
            'webhook_secret' => $newWebhook !== '' ? Security::encrypt($newWebhook) : ($cur['webhook_secret'] ?? ''),
            'mode' => $mode,
        ];

        Database::execute(
            'UPDATE payment_gateways SET name = ?, description = ?, min_amount = ?, max_amount = ?, fee_percent = ?, config_json = ?, is_active = ? WHERE code = "razorpay"',
            [
                trim((string) ($_POST['name'] ?? 'Razorpay')),
                trim((string) ($_POST['description'] ?? '')),
                (float) ($_POST['min_amount'] ?? 10),
                (float) ($_POST['max_amount'] ?? 100000),
                (float) ($_POST['fee_percent'] ?? 0),
                json_encode($cfg),
                isset($_POST['is_active']) ? 1 : 0,
            ]
        );
        Logger::activity('admin', (int) $admin['id'], 'gateways.razorpay_update');
        Session::flash('success', 'Razorpay gateway settings saved.');
    } elseif ($code === 'manual') {
        $cfg = [
            'instructions' => trim((string) ($_POST['instructions'] ?? '')),
            'upi_id' => trim((string) ($_POST['upi_id'] ?? '')),
            'account_name' => trim((string) ($_POST['account_name'] ?? '')),
            'account_number' => trim((string) ($_POST['account_number'] ?? '')),
            'ifsc' => trim((string) ($_POST['ifsc'] ?? '')),
        ];
        Database::execute(
            'UPDATE payment_gateways SET name = ?, description = ?, min_amount = ?, max_amount = ?, fee_percent = ?, config_json = ?, is_active = ? WHERE code = "manual"',
            [
                trim((string) ($_POST['name'] ?? 'Manual Bank / UPI Transfer')),
                trim((string) ($_POST['description'] ?? '')),
                (float) ($_POST['min_amount'] ?? 50),
                (float) ($_POST['max_amount'] ?? 200000),
                0.00,
                json_encode($cfg),
                isset($_POST['is_active']) ? 1 : 0,
            ]
        );
        Logger::activity('admin', (int) $admin['id'], 'gateways.manual_update');
        Session::flash('success', 'Manual Bank / UPI gateway settings saved.');
    }
    Response::redirect('/admin/gateways/index.php');
}

$rzp = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "razorpay" LIMIT 1');
$rzpCfg = $rzp && $rzp['config_json'] ? (json_decode($rzp['config_json'], true) ?: []) : [];
$man = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "manual" LIMIT 1');
$manCfg = $man && $man['config_json'] ? (json_decode($man['config_json'], true) ?: []) : [];

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="margin:0;font-size:17px">Razorpay Checkout &amp; Webhook</h2>
      <span class="badge <?= !empty($rzp['is_active']) ? 'badge-success' : 'badge-muted' ?>"><?= !empty($rzp['is_active']) ? 'Enabled' : 'Disabled' ?></span>
    </div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="code" value="razorpay">
      <div class="form-group"><label class="form-label">Display Name</label><input class="form-control" name="name" value="<?= e($rzp['name'] ?? 'Razorpay (UPI / Cards / NetBanking)') ?>" required></div>
      <div class="form-group"><label class="form-label">Description</label><input class="form-control" name="description" value="<?= e($rzp['description'] ?? '') ?>"></div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Key ID</label><input class="form-control" name="key_id" value="<?= e($rzpCfg['key_id'] ?? '') ?>" placeholder="rzp_live_... or rzp_test_..."></div>
        <div class="form-group"><label class="form-label">Mode</label><select class="form-select" name="mode"><option value="test" <?= ($rzpCfg['mode'] ?? '') === 'test' ? 'selected' : '' ?>>Test</option><option value="live" <?= ($rzpCfg['mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option></select></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Key Secret <?= !empty($rzpCfg['key_secret']) ? '(Saved Encrypted — leave blank to keep)' : '' ?></label><input class="form-control" type="password" name="key_secret" placeholder="Enter new Key Secret"></div>
        <div class="form-group"><label class="form-label">Webhook Secret <?= !empty($rzpCfg['webhook_secret']) ? '(Saved Encrypted)' : '' ?></label><input class="form-control" type="password" name="webhook_secret" placeholder="Enter Webhook Secret"></div>
      </div>
      <div class="grid-3">
        <div class="form-group"><label class="form-label">Min Amount</label><input class="form-control" type="number" step="0.01" name="min_amount" value="<?= e((string) ($rzp['min_amount'] ?? '10.00')) ?>"></div>
        <div class="form-group"><label class="form-label">Max Amount</label><input class="form-control" type="number" step="0.01" name="max_amount" value="<?= e((string) ($rzp['max_amount'] ?? '100000.00')) ?>"></div>
        <div class="form-group"><label class="form-label">Fee %</label><input class="form-control" type="number" step="0.01" name="fee_percent" value="<?= e((string) ($rzp['fee_percent'] ?? '0.00')) ?>"></div>
      </div>
      <div class="form-group"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="is_active" value="1" <?= !empty($rzp['is_active']) ? 'checked' : '' ?>> Enable Razorpay Gateway</label></div>
      <div style="font-size:12px;color:#777083;margin-bottom:12px">Webhook URL for Razorpay Dashboard: <code><?= e(app_url('/api/razorpay-webhook.php')) ?></code> (Events: <code>payment.captured</code>, <code>order.paid</code>)</div>
      <button class="btn btn-primary" type="submit">Save Razorpay Config</button>
    </form>
  </div>

  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="margin:0;font-size:17px">Manual Bank / UPI Transfer</h2>
      <span class="badge <?= !empty($man['is_active']) ? 'badge-success' : 'badge-muted' ?>"><?= !empty($man['is_active']) ? 'Enabled' : 'Disabled' ?></span>
    </div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="code" value="manual">
      <div class="form-group"><label class="form-label">Display Name</label><input class="form-control" name="name" value="<?= e($man['name'] ?? 'Manual Bank / UPI Transfer') ?>" required></div>
      <div class="form-group"><label class="form-label">Short Description</label><input class="form-control" name="description" value="<?= e($man['description'] ?? '') ?>"></div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">UPI ID</label><input class="form-control" name="upi_id" value="<?= e($manCfg['upi_id'] ?? '') ?>" placeholder="merchant@upi"></div>
        <div class="form-group"><label class="form-label">Account Holder Name</label><input class="form-control" name="account_name" value="<?= e($manCfg['account_name'] ?? '') ?>"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Bank Account Number</label><input class="form-control" name="account_number" value="<?= e($manCfg['account_number'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">IFSC / SWIFT Code</label><input class="form-control" name="ifsc" value="<?= e($manCfg['ifsc'] ?? '') ?>"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Min Amount</label><input class="form-control" type="number" step="0.01" name="min_amount" value="<?= e((string) ($man['min_amount'] ?? '50.00')) ?>"></div>
        <div class="form-group"><label class="form-label">Max Amount</label><input class="form-control" type="number" step="0.01" name="max_amount" value="<?= e((string) ($man['max_amount'] ?? '200000.00')) ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Payment Instructions</label><textarea class="form-control" rows="3" name="instructions"><?= e($manCfg['instructions'] ?? '') ?></textarea></div>
      <div class="form-group"><label style="font-size:13px;font-weight:700"><input type="checkbox" name="is_active" value="1" <?= !empty($man['is_active']) ? 'checked' : '' ?>> Enable Manual Payment Gateway</label></div>
      <button class="btn btn-primary" type="submit">Save Manual Config</button>
    </form>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
