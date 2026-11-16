<?php
declare(strict_types=1);

$pageTitle = 'Child Panels';
$panels = Database::fetchAll(
    'SELECT cp.*, (SELECT domain FROM child_panel_domains WHERE child_panel_id = cp.id AND is_primary = 1 LIMIT 1) AS primary_domain,
            (SELECT COUNT(*) FROM child_panel_users WHERE child_panel_id = cp.id) AS user_count,
            (SELECT COUNT(*) FROM child_panel_orders WHERE child_panel_id = cp.id) AS order_count
     FROM child_panels cp WHERE cp.owner_user_id = ? ORDER BY cp.created_at DESC',
    [(int) $user['id']]
);

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">My Child Panels</h1>
      <p class="page-banner-sub">Launch and manage isolated branded reseller panels under your custom domain.</p>
    </div>
  </div>
  <a href="/user/index.php?page=child-panels/create" class="btn btn-primary btn-sm">+ Create Child Panel</a>
</div>

<div class="card">
  <div class="section-head">
    <h2 class="section-title">Active &amp; Pending Panels (<?= count($panels) ?>)</h2>
  </div>
  <?php if (empty($panels)): ?>
    <div style="text-align:center;color:var(--muted);padding:28px 14px">
      You haven't created any child panels yet.
      <div style="margin-top:12px"><a href="/user/index.php?page=child-panels/create" class="btn btn-primary btn-sm">Launch Your First Child Panel</a></div>
    </div>
  <?php else: ?>
    <div class="desktop-table-wrap">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Panel Name</th><th>Domain</th><th>Wholesale Wallet</th><th>Users / Orders</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($panels as $cp): ?>
            <tr>
              <td>#<?= (int) $cp['id'] ?></td>
              <td><strong><?= e($cp['site_name']) ?></strong></td>
              <td><?= e($cp['primary_domain'] ?: '-') ?></td>
              <td style="font-weight:800;color:var(--primary)"><?= e(format_money((string) $cp['wallet_balance'], $displayCurrency)) ?></td>
              <td><?= (int) $cp['user_count'] ?> / <?= (int) $cp['order_count'] ?></td>
              <td><span class="badge <?= e(status_badge_class($cp['status'])) ?>"><?= e(ucfirst($cp['status'])) ?></span></td>
              <td><a class="btn btn-primary btn-sm" href="/user/index.php?page=child-panels/details&panel_id=<?= (int) $cp['id'] ?>">Manage</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="mobile-cards-list">
      <?php foreach ($panels as $cp): ?>
        <div class="order-row-card" style="flex-direction:column;align-items:stretch;gap:8px">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
            <strong>#<?= (int) $cp['id'] ?> &middot; <?= e($cp['site_name']) ?></strong>
            <span class="badge <?= e(status_badge_class($cp['status'])) ?>"><?= e(ucfirst($cp['status'])) ?></span>
          </div>
          <div style="font-size:12.5px;color:var(--muted)">Domain: <?= e($cp['primary_domain'] ?: 'Not set') ?> &middot; <?= (int) $cp['user_count'] ?> users &middot; <?= (int) $cp['order_count'] ?> orders</div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <strong style="color:var(--primary)"><?= e(format_money((string) $cp['wallet_balance'], $displayCurrency)) ?></strong>
            <a class="btn btn-primary btn-sm" href="/user/index.php?page=child-panels/details&panel_id=<?= (int) $cp['id'] ?>">Manage</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
