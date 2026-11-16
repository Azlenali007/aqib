<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('wallet.view');
$adminTitle = 'Wallet Ledger & Adjustments';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (!AdminAuth::can('users.wallet', $admin)) {
        Session::flash('error', 'Missing users.wallet permission.');
        Response::redirect('/admin/wallet/index.php');
    }
    $uid = (int) ($_POST['user_id'] ?? 0);
    $mode = ($_POST['mode'] ?? 'credit') === 'debit' ? 'debit' : 'credit';
    $amount = trim((string) ($_POST['amount'] ?? '0'));
    $desc = trim((string) ($_POST['description'] ?? 'Admin wallet adjustment'));

    if ($uid <= 0 || !is_numeric($amount) || (float) $amount <= 0) {
        Session::flash('error', 'Valid user ID and positive amount are required.');
    } else {
        if ($mode === 'credit') {
            $res = WalletService::credit($uid, $amount, 'admin_adjustment', (string) $admin['id'], $desc, null, 'adjustment', (int) $admin['id']);
        } else {
            $res = WalletService::debit($uid, $amount, 'admin_adjustment', (string) $admin['id'], $desc, null, (int) $admin['id'], false);
        }
        if ($res['success']) {
            Logger::activity('admin', (int) $admin['id'], 'wallet.' . $mode, 'users', (string) $uid, ['amount' => $amount, 'desc' => $desc]);
            Session::flash('success', "Wallet {$mode} completed for User #$uid.");
        } else {
            Session::flash('error', $res['error'] ?? 'Wallet operation failed.');
        }
    }
    Response::redirect('/admin/wallet/index.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR wt.description LIKE ? OR wt.reference_id LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($type !== '') {
    $where[] = 'wt.type = ?';
    $params[] = $type;
}

$txs = Database::fetchAll(
    'SELECT wt.*, u.username FROM wallet_transactions wt INNER JOIN users u ON u.id = wt.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY wt.id DESC LIMIT 150',
    $params
);
$totalLiability = (string) Database::fetchValue('SELECT COALESCE(SUM(balance),0) FROM wallets', [], '0');
$usersList = Database::fetchAll('SELECT id, username, email FROM users ORDER BY id DESC LIMIT 200');

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-kpi"><div class="lbl">Total Active User Wallet Balances (System Base)</div><div class="val"><?= e(format_money($totalLiability)) ?></div></div>
  <div class="adm-kpi"><div class="lbl">Ledger Entries Shown</div><div class="val"><?= count($txs) ?></div></div>
</div>

<?php if (AdminAuth::can('users.wallet', $admin)): ?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Manual Wallet Credit / Debit (<?= e(Currency::systemBase()) ?>)</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?>
    <select class="form-select" name="user_id" required>
      <option value="">Select User</option>
      <?php foreach ($usersList as $u): ?>
        <option value="<?= (int) $u['id'] ?>">#<?= (int) $u['id'] ?> — <?= e($u['username']) ?> (<?= e($u['email']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <select class="form-select" name="mode">
      <option value="credit">Credit (+ Add Funds)</option>
      <option value="debit">Debit (- Deduct Funds)</option>
    </select>
    <input class="form-control" type="number" step="0.0001" min="0.01" name="amount" placeholder="Amount in <?= e(Currency::systemBase()) ?>" required>
    <input class="form-control" name="description" placeholder="Audit Reason / Note" required style="grid-column:span 2">
    <button class="btn btn-primary" type="submit">Apply Adjustment</button>
  </form>
</div>
<?php endif; ?>

<div class="adm-card">
  <form method="get" class="filter-bar">
    <input class="form-control" style="max-width:250px" name="q" value="<?= e($q) ?>" placeholder="Search username, reference, note...">
    <select class="form-select" style="max-width:160px" name="type">
      <option value="">All Types</option>
      <?php foreach (['credit', 'debit', 'refund', 'referral', 'adjustment'] as $tp): ?>
        <option value="<?= e($tp) ?>" <?= $type === $tp ? 'selected' : '' ?>><?= e(ucfirst($tp)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
  </form>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Amount</th><th>Before</th><th>After</th><th>Reference</th><th>Description</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($txs)): ?>
        <tr><td colspan="9" style="text-align:center;color:#777083">No wallet transactions recorded yet.</td></tr>
      <?php else: foreach ($txs as $t): ?>
        <tr>
          <td>#<?= (int) $t['id'] ?></td>
          <td><a href="/admin/users/view.php?id=<?= (int) $t['user_id'] ?>"><strong><?= e($t['username']) ?></strong></a></td>
          <td><span class="badge badge-muted"><?= e(ucfirst($t['type'])) ?></span></td>
          <td style="font-weight:700"><?= e(format_money((string) $t['amount'])) ?></td>
          <td><?= e(format_money((string) $t['balance_before'])) ?></td>
          <td><?= e(format_money((string) $t['balance_after'])) ?></td>
          <td><?= e($t['reference_type']) ?><?= $t['reference_id'] ? ' #' . e($t['reference_id']) : '' ?></td>
          <td><?= e($t['description']) ?></td>
          <td><?= e(substr($t['created_at'], 0, 16)) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
