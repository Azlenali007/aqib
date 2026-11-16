<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('referrals.manage');
$adminTitle = 'Referral Program & Commissions';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_settings') {
        set_setting('referral_enabled', isset($_POST['referral_enabled']) ? '1' : '0', 'referral');
        set_setting('referral_commission_percent', Currency::normalizeDecimal((string) ($_POST['referral_commission_percent'] ?? '5.00'), 2), 'referral');
        set_setting('referral_min_deposit', Currency::normalizeDecimal((string) ($_POST['referral_min_deposit'] ?? '100.00'), 2), 'referral');
        set_setting('referral_auto_approve', isset($_POST['referral_auto_approve']) ? '1' : '0', 'referral');
        Logger::activity('admin', (int) $admin['id'], 'referrals.settings_update');
        Session::flash('success', 'Referral program settings updated.');
    } elseif ($action === 'approve') {
        $res = ReferralService::approve((int) ($_POST['referral_id'] ?? 0), (int) $admin['id']);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Referral commission approved and credited.' : $res['error']);
    } elseif ($action === 'reject') {
        $res = ReferralService::reject((int) ($_POST['referral_id'] ?? 0), (int) $admin['id']);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Referral commission rejected.' : $res['error']);
    }
    Response::redirect('/admin/referrals/index.php');
}

$referrals = Database::fetchAll(
    'SELECT r.*, u1.username AS referrer_name, u2.username AS referred_name
     FROM referrals r
     INNER JOIN users u1 ON u1.id = r.referrer_user_id
     INNER JOIN users u2 ON u2.id = r.referred_user_id
     ORDER BY r.id DESC LIMIT 120'
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Referral Program Settings</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">
    <div class="form-group"><label class="form-label">Commission % on Deposit</label><input class="form-control" type="number" step="0.01" min="0" max="50" name="referral_commission_percent" value="<?= e((string) get_setting('referral_commission_percent', '5.00')) ?>"></div>
    <div class="form-group"><label class="form-label">Minimum Qualifying Deposit (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" min="0" name="referral_min_deposit" value="<?= e((string) get_setting('referral_min_deposit', '100.00')) ?>"></div>
    <div class="form-group" style="display:flex;flex-direction:column;justify-content:center;gap:6px">
      <label style="font-size:13px;font-weight:700"><input type="checkbox" name="referral_enabled" value="1" <?= get_setting('referral_enabled', '1') === '1' ? 'checked' : '' ?>> Enable Referral Program</label>
      <label style="font-size:13px;font-weight:700"><input type="checkbox" name="referral_auto_approve" value="1" <?= get_setting('referral_auto_approve', '0') === '1' ? 'checked' : '' ?>> Auto-Approve Commissions</label>
    </div>
    <div><button class="btn btn-primary btn-sm" type="submit">Save Settings</button></div>
  </form>
</div>

<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Referral Commissions</h2>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Referrer</th><th>Referred User</th><th>Deposit Ref</th><th>Base Amount</th><th>Rate</th><th>Commission</th><th>Signup IP</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (empty($referrals)): ?>
        <tr><td colspan="10" style="text-align:center;color:#777083">No referral commissions recorded yet.</td></tr>
      <?php else: foreach ($referrals as $r): ?>
        <tr>
          <td>#<?= (int) $r['id'] ?></td>
          <td><strong><?= e($r['referrer_name']) ?></strong></td>
          <td><?= e($r['referred_name']) ?></td>
          <td>Deposit #<?= (int) $r['trigger_reference_id'] ?></td>
          <td><?= e(format_money((string) $r['base_amount'])) ?></td>
          <td><?= e((string) $r['commission_percent']) ?>%</td>
          <td style="font-weight:700;color:var(--ok)"><?= e(format_money((string) $r['commission_amount'])) ?></td>
          <td><code><?= e($r['signup_ip'] ?? '—') ?></code></td>
          <td><span class="badge <?= e(status_badge_class($r['status'])) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="referral_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-success btn-sm" name="action" value="approve" type="submit">Approve</button></form>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="referral_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="reject" type="submit">Reject</button></form>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
