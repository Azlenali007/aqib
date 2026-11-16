<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (($_POST['action'] ?? '') === 'create_user') {
        $res = ChildPanelService::createChildUser((int) $cpPanel['id'], (string) ($_POST['username'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['full_name'] ?? ''), (string) ($_POST['password'] ?? ''));
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Child panel user created.' : $res['error']);
    } elseif (($_POST['action'] ?? '') === 'credit_user') {
        $res = ChildPanelService::creditChildUserWallet((int) $cpPanel['id'], (int) ($_POST['child_user_id'] ?? 0), (string) ($_POST['amount'] ?? '0'), 'Panel owner credit');
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Child user balance credited.' : $res['error']);
    }
    Response::redirect('/user/index.php?page=child-panels/users&panel_id=' . $cpPanel['id']);
}

$users = Database::fetchAll(
    'SELECT cpu.*, cpw.balance FROM child_panel_users cpu
     LEFT JOIN child_panel_wallets cpw ON cpw.child_user_id = cpu.id AND cpw.child_panel_id = cpu.child_panel_id
     WHERE cpu.child_panel_id = ? ORDER BY cpu.created_at DESC',
    [$cpPanel['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/users');
?>
<div class="grid-2" style="align-items:start">
  <div class="card">
    <h1 class="section-title" style="margin-bottom:14px">Add Isolated Child User</h1>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_user">
      <div class="form-group"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
      <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
      <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
      <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="6" required></div>
      <button class="btn btn-primary" type="submit">Create Child User</button>
    </form>
  </div>
  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">Panel Users (<?= count($users) ?>)</h2>
    <div class="table-responsive">
      <table class="data-table" style="min-width:440px">
        <thead><tr><th>User</th><th>Balance</th><th>Status</th><th>Credit</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="4" style="text-align:center;color:#777083">No users registered on this child panel.</td></tr>
        <?php else: foreach ($users as $cu): ?>
          <tr>
            <td><strong><?= e($cu['username']) ?></strong><div style="font-size:11.5px;color:#777083"><?= e($cu['email']) ?></div></td>
            <td style="font-weight:700"><?= e(format_money((string) ($cu['balance'] ?? '0'), $displayCurrency)) ?></td>
            <td><span class="badge <?= e(status_badge_class($cu['status'])) ?>"><?= e(ucfirst($cu['status'])) ?></span></td>
            <td>
              <form method="post" style="display:flex;gap:4px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="credit_user">
                <input type="hidden" name="child_user_id" value="<?= (int) $cu['id'] ?>">
                <input class="form-control" style="width:80px;padding:5px 8px" type="number" step="0.01" name="amount" placeholder="Amt" required>
                <button class="btn btn-outline btn-sm" type="submit">+</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
