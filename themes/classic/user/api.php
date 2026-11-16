<?php
declare(strict_types=1);

$pageTitle = 'Reseller API';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regen_key') {
    Security::requireCsrf();
    $newKey = ApiService::regenerateUserKey((int) $user['id']);
    Session::flash('success', 'API Key generated successfully.');
    Response::redirect('/user/index.php?page=api');
}

$keyRow = Database::fetchOne('SELECT * FROM api_keys WHERE user_id = ? LIMIT 1', [(int) $user['id']]);
$apiOrders = Database::fetchAll("SELECT o.*, s.name AS service_name FROM orders o INNER JOIN services s ON s.id = o.service_id WHERE o.user_id = ? AND o.source = 'api' ORDER BY o.created_at DESC LIMIT 20", [(int) $user['id']]);

require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Reseller API Access</h1>
      <p class="page-banner-sub">Automate orders and sync statuses using REST API v2</p>
    </div>
  </div>
  <a href="/user/index.php?page=api-docs" class="view-all-pill">API Documentation &rarr;</a>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:14px">Credentials &amp; Endpoint</h2>
  <div class="form-group">
    <label class="form-label">API Endpoint URL</label>
    <input class="form-control" readonly value="<?= e(app_url('/api/index.php')) ?>">
  </div>
  <div class="form-group">
    <label class="form-label">Your Secret API Key</label>
    <input class="form-control" readonly value="<?= e($keyRow['api_key'] ?? 'Not generated yet') ?>">
    <?php if (!empty($keyRow['last_used_at'])): ?>
      <small style="color:var(--muted)">Last used: <?= e($keyRow['last_used_at']) ?> (IP: <?= e($keyRow['last_used_ip'] ?? '-') ?>)</small>
    <?php endif; ?>
  </div>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="regen_key">
    <button class="btn btn-primary" type="submit"><?= $keyRow ? 'Regenerate API Key' : 'Generate API Key' ?></button>
  </form>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Recent API Orders</h2>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Service</th><th>Quantity</th><th>Charge</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($apiOrders)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:20px">No API orders placed yet.</td></tr>
      <?php else: foreach ($apiOrders as $ao): ?>
        <tr>
          <td><strong>#<?= (int) $ao['id'] ?></strong></td>
          <td><?= e($ao['service_name']) ?></td>
          <td><?= number_format((int) $ao['quantity']) ?></td>
          <td style="font-weight:700;color:var(--primary)"><?= e(format_money((string) $ao['charge'], $displayCurrency)) ?></td>
          <td><span class="badge <?= e(status_badge_class($ao['status'])) ?>"><?= e(ucfirst($ao['status'])) ?></span></td>
          <td><?= e($ao['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
