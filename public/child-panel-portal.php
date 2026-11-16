<?php
declare(strict_types=1);

if (!defined('ACTIVE_CHILD_PANEL_ID')) {
    http_response_code(404);
    exit('Child panel not found.');
}

$panel = Database::fetchOne('SELECT * FROM child_panels WHERE id = ? AND status = "active" LIMIT 1', [ACTIVE_CHILD_PANEL_ID]);
if (!$panel) {
    http_response_code(503);
    exit('This panel is currently unavailable.');
}

Session::start('user');
$cpErr = '';
$cpMsg = '';
$cpUserId = (int) Session::get('cp_user_id_' . $panel['id'], 0);
$cpUser = $cpUserId > 0
    ? Database::fetchOne(
        'SELECT u.*, w.balance FROM child_panel_users u LEFT JOIN child_panel_wallets w ON w.child_user_id = u.id AND w.child_panel_id = u.child_panel_id WHERE u.id = ? AND u.child_panel_id = ? AND u.status = "active" LIMIT 1',
        [$cpUserId, $panel['id']]
    ) : null;

if (isset($_GET['cp_logout'])) {
    Session::remove('cp_user_id_' . $panel['id']);
    Response::redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf('cp_' . $panel['id']);
    $act = $_POST['cp_action'] ?? '';
    if ($act === 'register') {
        $res = ChildPanelService::createChildUser((int) $panel['id'], (string) ($_POST['username'] ?? ''), (string) ($_POST['email'] ?? ''), (string) ($_POST['full_name'] ?? ''), (string) ($_POST['password'] ?? ''));
        if ($res['success']) {
            Session::set('cp_user_id_' . $panel['id'], $res['child_user_id']);
            Response::redirect('/');
        }
        $cpErr = $res['error'];
    } elseif ($act === 'login') {
        $u = Database::fetchOne('SELECT * FROM child_panel_users WHERE child_panel_id = ? AND (username = ? OR email = ?) LIMIT 1', [$panel['id'], trim((string) $_POST['login']), trim((string) $_POST['login'])]);
        if ($u && Security::verifyPassword((string) $_POST['password'], $u['password_hash']) && $u['status'] === 'active') {
            Session::set('cp_user_id_' . $panel['id'], (int) $u['id']);
            Response::redirect('/');
        }
        $cpErr = 'Invalid credentials for this child panel.';
    } elseif ($act === 'order' && $cpUser) {
        $res = ChildPanelService::placeChildOrder((int) $panel['id'], (int) $cpUser['id'], (int) ($_POST['child_service_id'] ?? 0), (string) ($_POST['link'] ?? ''), (int) ($_POST['quantity'] ?? 0));
        if ($res['success']) {
            $cpMsg = "Order #{$res['child_order_id']} placed!";
            $cpUser = Database::fetchOne('SELECT u.*, w.balance FROM child_panel_users u LEFT JOIN child_panel_wallets w ON w.child_user_id = u.id WHERE u.id = ? AND u.child_panel_id = ?', [$cpUser['id'], $panel['id']]);
        } else {
            $cpErr = $res['error'];
        }
    }
}

$cpServices = Database::fetchAll(
    'SELECT cps.*, s.min_qty, s.max_qty, s.platform FROM child_panel_services cps
     INNER JOIN services s ON s.id = cps.parent_service_id
     WHERE cps.child_panel_id = ? AND cps.is_enabled = 1 AND s.is_active = 1 ORDER BY cps.id ASC',
    [$panel['id']]
);
$cpOrders = $cpUser ? Database::fetchAll('SELECT cpo.*, cps.custom_name FROM child_panel_orders cpo INNER JOIN child_panel_services cps ON cps.id = cpo.child_service_id WHERE cpo.child_panel_id = ? AND cpo.child_user_id = ? ORDER BY cpo.created_at DESC LIMIT 20', [$panel['id'], $cpUser['id']]) : [];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($panel['site_name']) ?></title>
  <link rel="stylesheet" href="/themes/classic/user/assets/css/classic.css">
  <style>:root{--primary:<?= e($panel['primary_color']) ?>}</style>
</head>
<body style="padding:20px">
<div style="max-width:960px;margin:0 auto">
  <div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
      <div class="brand-title"><?= e($panel['site_name']) ?></div>
      <div class="brand-sub"><?= e($panel['tagline'] ?? 'Social Media Marketing') ?></div>
    </div>
    <?php if ($cpUser): ?>
      <div style="display:flex;align-items:center;gap:10px">
        <span class="badge badge-processing">Balance: <?= e(format_money((string) ($cpUser['balance'] ?? '0'), $panel['currency'])) ?></span>
        <a class="btn btn-outline btn-sm" href="?cp_logout=1">Logout (<?= e($cpUser['username']) ?>)</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($cpErr): ?><div class="alert alert-error"><?= e($cpErr) ?></div><?php endif; ?>
  <?php if ($cpMsg): ?><div class="alert alert-success"><?= e($cpMsg) ?></div><?php endif; ?>

  <?php if (!$cpUser): ?>
    <div class="grid-2">
      <div class="card">
        <h2 class="section-title" style="margin-bottom:12px">Sign In</h2>
        <form method="post"><?= csrf_field('cp_' . $panel['id']) ?><input type="hidden" name="cp_action" value="login">
          <div class="form-group"><label class="form-label">Username or Email</label><input class="form-control" name="login" required></div>
          <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
          <button class="btn btn-primary" type="submit">Sign In</button>
        </form>
      </div>
      <div class="card">
        <h2 class="section-title" style="margin-bottom:12px">Create Account</h2>
        <form method="post"><?= csrf_field('cp_' . $panel['id']) ?><input type="hidden" name="cp_action" value="register">
          <div class="form-group"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
          <div class="form-group"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
          <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
          <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
          <button class="btn btn-primary" type="submit">Register</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="grid-2">
      <div class="card">
        <h2 class="section-title" style="margin-bottom:12px">Place Order</h2>
        <form method="post"><?= csrf_field('cp_' . $panel['id']) ?><input type="hidden" name="cp_action" value="order">
          <div class="form-group"><label class="form-label">Service</label><select class="form-select" name="child_service_id"><?php foreach ($cpServices as $cs): ?><option value="<?= (int) $cs['id'] ?>">#<?= (int) $cs['id'] ?> - <?= e($cs['custom_name']) ?> (<?= e(format_money((string) $cs['custom_selling_price'], $panel['currency'])) ?>/1K)</option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Link</label><input class="form-control" name="link" required></div>
          <div class="form-group"><label class="form-label">Quantity</label><input class="form-control" type="number" name="quantity" required></div>
          <button class="btn btn-primary" type="submit">Submit Order</button>
        </form>
      </div>
      <div class="card">
        <h2 class="section-title" style="margin-bottom:12px">My Orders</h2>
        <div class="table-responsive"><table class="data-table" style="min-width:340px"><thead><tr><th>ID</th><th>Service</th><th>Qty</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($cpOrders as $co): ?><tr><td>#<?= (int) $co['id'] ?></td><td><?= e($co['custom_name']) ?></td><td><?= (int) $co['quantity'] ?></td><td><?= e(ucfirst($co['status'])) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
