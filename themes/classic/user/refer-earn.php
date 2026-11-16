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
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Refer &amp; Earn <?= e($commPct) ?>% Commission</h1>
      <p class="page-banner-sub">Earn <?= e($commPct) ?>% whenever referred users deposit at least <?= e(format_money($minDep, $displayCurrency)) ?></p>
    </div>
  </div>
</div>

<div class="card">
  <div class="grid-2">
    <div class="form-group" style="margin-bottom:0"><label class="form-label">Your Referral Code</label><input class="form-control" readonly value="<?= e($user['referral_code']) ?>"></div>
    <div class="form-group" style="margin-bottom:0"><label class="form-label">Your Referral Link</label><input class="form-control" readonly value="<?= e($refLink) ?>"></div>
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
        <tr><td colspan="5" style="text-align:center;color:var(--muted)">No referral commissions yet.</td></tr>
      <?php else: foreach ($history as $h): ?>
        <tr>
          <td><strong><?= e($h['referred_username']) ?></strong></td>
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
