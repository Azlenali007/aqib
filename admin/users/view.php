<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('users.view');
$uid = (int) ($_GET['id'] ?? 0);
$u = Database::fetchOne('SELECT u.*, w.balance, w.total_deposited, w.total_spent FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE u.id = ? LIMIT 1', [$uid]);
if (!$u) { Session::flash('error', 'User not found.'); Response::redirect('/admin/users/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'edit_user' && AdminAuth::can('users.edit', $admin)) {
        Database::execute(
            'UPDATE users SET full_name = ?, mobile = ?, custom_rate_percent = ?, is_verified = ?, status = ?, admin_notes = ? WHERE id = ?',
            [trim((string) $_POST['full_name']), trim((string) $_POST['mobile']), (float) $_POST['custom_rate_percent'], !empty($_POST['is_verified']) ? 1 : 0, $_POST['status'], trim((string) $_POST['admin_notes']), $uid]
        );
        Logger::activity('admin', (int) $admin['id'], 'users.edit', 'users', (string) $uid);
        Session::flash('success', 'User details updated.');
    } elseif ($action === 'reset_password' && AdminAuth::can('users.edit', $admin)) {
        $pw = (string) ($_POST['new_password'] ?? '');
        if (strlen($pw) >= 8) {
            Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [Security::hashPassword($pw), $uid]);
            Session::flash('success', 'User password reset.');
        } else {
            Session::flash('error', 'Password must be at least 8 characters.');
        }
    } elseif ($action === 'wallet_adjust' && AdminAuth::can('users.wallet', $admin)) {
        $type = $_POST['adjust_type'] ?? 'credit';
        $amt = (string) ($_POST['amount'] ?? '0');
        $note = trim((string) ($_POST['note'] ?? 'Admin wallet adjustment'));
        $res = $type === 'debit'
            ? WalletService::debit($uid, $amt, 'admin_adjustment', (string) $admin['id'], $note, null, (int) $admin['id'], false)
            : WalletService::credit($uid, $amt, 'admin_adjustment', (string) $admin['id'], $note, null, 'adjustment', (int) $admin['id']);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Wallet adjusted successfully.' : $res['error']);
    }
    Response::redirect('/admin/users/view.php?id=' . $uid);
}

$orders = Database::fetchAll('SELECT o.*, s.name AS service_name FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.user_id = ? ORDER BY o.id DESC LIMIT 20', [$uid]);
$txs = Database::fetchAll('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 20', [$uid]);
$adminTitle = 'User #' . $uid . ' — ' . $u['username'];
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 12px;font-size:16px">Edit User Profile</h2>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="edit_user">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" value="<?= e($u['full_name']) ?>" required></div>
        <div class="form-group"><label class="form-label">Mobile</label><input class="form-control" name="mobile" value="<?= e($u['mobile'] ?? '') ?>"></div>
      </div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Discount Rate (%)</label><input class="form-control" type="number" step="0.1" name="custom_rate_percent" value="<?= e((string) $u['custom_rate_percent']) ?>"></div>
        <div class="form-group"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?= $u['status']==='active'?'selected':'' ?>>Active</option><option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Suspended</option><option value="pending" <?= $u['status']==='pending'?'selected':'' ?>>Pending</option></select></div>
      </div>
      <div class="form-group"><label><input type="checkbox" name="is_verified" value="1" <?= $u['is_verified']?'checked':'' ?>> Verified Badge</label></div>
      <div class="form-group"><label class="form-label">Admin Notes</label><textarea class="form-control" name="admin_notes"><?= e($u['admin_notes'] ?? '') ?></textarea></div>
      <button class="btn btn-primary" type="submit">Save Changes</button>
    </form>
  </div>

  <div>
    <div class="adm-card">
      <h2 style="margin:0 0 8px;font-size:16px">Wallet Balance: <?= e(format_money((string) ($u['balance'] ?? '0'))) ?></h2>
      <?php if (AdminAuth::can('users.wallet', $admin)): ?>
      <form method="post" class="grid-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="wallet_adjust">
        <div class="form-group"><label class="form-label">Operation</label><select class="form-select" name="adjust_type"><option value="credit">Add Balance (+)</option><option value="debit">Deduct Balance (-)</option></select></div>
        <div class="form-group"><label class="form-label">Amount (<?= e(Currency::systemBase()) ?>)</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" required></div>
        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Reason / Note</label><input class="form-control" name="note" required placeholder="Manual credit/deduction reason"></div>
        <button class="btn btn-primary" type="submit">Apply Adjustment</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="adm-card">
      <h2 style="margin:0 0 10px;font-size:16px">Reset Password</h2>
      <form method="post" class="filter-bar">
        <?= csrf_field() ?><input type="hidden" name="action" value="reset_password">
        <input class="form-control" type="password" name="new_password" placeholder="New password (min 8 chars)" minlength="8" required style="max-width:260px">
        <button class="btn btn-outline btn-sm" type="submit">Set Password</button>
      </form>
    </div>
  </div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Recent Orders</h2>
    <div class="table-responsive"><table class="adm-table" style="min-width:380px"><thead><tr><th>ID</th><th>Service</th><th>Charge</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($orders as $o): ?><tr><td>#<?= (int) $o['id'] ?></td><td><?= e($o['service_name']) ?></td><td><?= e(format_money((string) $o['charge'])) ?></td><td><?= e($o['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Wallet Transactions</h2>
    <div class="table-responsive"><table class="adm-table" style="min-width:380px"><thead><tr><th>Type</th><th>Amount</th><th>After</th><th>Note</th></tr></thead><tbody>
      <?php foreach ($txs as $t): ?><tr><td><?= e($t['type']) ?></td><td><?= e(format_money((string) $t['amount'])) ?></td><td><?= e(format_money((string) $t['balance_after'])) ?></td><td><?= e($t['description']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
