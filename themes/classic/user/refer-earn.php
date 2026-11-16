<?php
declare(strict_types=1);

$pageTitle = 'Refer & Earn';
$refLink = app_url('/user/register.php?ref=' . urlencode($user['referral_code']));
$totalReferred = (int) Database::fetchValue('SELECT COUNT(*) FROM users WHERE referred_by_user_id = ?', [(int) $user['id']], 0);
$totalEarned = (string) Database::fetchValue("SELECT COALESCE(SUM(commission_amount), 0) FROM referrals WHERE referrer_user_id = ? AND status = 'approved'", [(int) $user['id']], '0.0000');
$history = Database::fetchAll(
    'SELECT r.*, u.username AS referred_username FROM referrals r INNER JOIN users u ON u.id = r.referred_user_id WHERE r.referrer_user_id = ? ORDER BY r.created_at DESC LIMIT 30',
    [(int) $user['id']]
);
$commPct = (string) get_setting('referral_commission_percent', '5.00');
$minDep = (string) get_setting('referral_min_deposit', '100.00');

require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:8px">Refer &amp; Earn <?= e($commPct) ?>% Commission</h1>
  <p style="color:#777083;font-size:13.5px;margin:0 0 16px">Share your link. Earn <?= e($commPct) ?>% whenever referred users deposit at least <?= e(format_money($minDep, $displayCurrency)) ?>.</p>
  <div class="grid-2">
    <div class="form-group"><label class="form-label">Your Referral Code</label><input class="form-control" readonly value="<?= e($user['referral_code']) ?>"></div>
    <div class="form-group"><label class="form-label">Your Referral Link</label><input class="form-control" readonly value="<?= e($refLink) ?>"></div>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Total Referred Users</div><div class="kpi-val"><?= $totalReferred ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Commission Rate</div><div class="kpi-val"><?= e($commPct) ?>%</div></div>
  <div class="kpi-box"><div class="kpi-label">Approved Earnings</div><div class="kpi-val" style="color:#16A36A"><?= e(format_money($totalEarned, $displayCurrency)) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Min Qualifying Deposit</div><div class="kpi-val"><?= e(format_money($minDep, $displayCurrency)) ?></div></div>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Referral Commission History</h2>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Referred User</th><th>Deposit Amount</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($history)): ?>
        <tr><td colspan="5" style="text-align:center;color:#777083">No referral commissions yet.</td></tr>
      <?php else: foreach ($history as $h): ?>
        <tr>
          <td><?= e($h['referred_username']) ?></td>
          <td><?= e(format_money((string) $h['base_amount'], $displayCurrency)) ?></td>
          <td style="font-weight:700;color:#16A36A"><?= e(format_money((string) $h['commission_amount'], $displayCurrency)) ?></td>
          <td><span class="badge <?= e(status_badge_class($h['status'])) ?>"><?= e(ucfirst($h['status'])) ?></span></td>
          <td><?= e($h['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
