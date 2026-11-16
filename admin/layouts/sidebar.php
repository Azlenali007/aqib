<?php
declare(strict_types=1);
$cur = $_SERVER['REQUEST_URI'] ?? '';
$isAct = static fn(string $path): string => str_contains($cur, $path) ? 'active' : '';
?>
<aside id="admSidebar" class="adm-sidebar">
  <div class="adm-brand">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7045D8,#5B42F3);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff">A</div>
    <div><strong>Admin Panel</strong><span><?= e($admin['role_name'] ?? 'Administrator') ?></span></div>
  </div>

  <div class="adm-grp">Core</div>
  <a class="adm-link <?= $isAct('/admin/dashboard.php') ?>" href="/admin/dashboard.php">Dashboard</a>
  <?php if (admin_can('users.view')): ?><a class="adm-link <?= $isAct('/admin/users/') ?>" href="/admin/users/index.php">Users &amp; Wallets</a><?php endif; ?>
  <?php if (admin_can('orders.view')): ?><a class="adm-link <?= $isAct('/admin/orders/') ?>" href="/admin/orders/index.php">Orders &amp; Refills</a><?php endif; ?>

  <div class="adm-grp">Catalog &amp; API</div>
  <?php if (admin_can('categories.manage')): ?><a class="adm-link <?= $isAct('/admin/categories/') ?>" href="/admin/categories/index.php">Categories</a><?php endif; ?>
  <?php if (admin_can('services.view')): ?><a class="adm-link <?= $isAct('/admin/services/') ?>" href="/admin/services/index.php">Services &amp; Bulk</a><?php endif; ?>
  <?php if (admin_can('providers.view')): ?><a class="adm-link <?= $isAct('/admin/providers/') ?>" href="/admin/providers/index.php">Providers &amp; Import</a><?php endif; ?>

  <div class="adm-grp">Finance &amp; Currency</div>
  <?php if (admin_can('payments.view')): ?><a class="adm-link <?= $isAct('/admin/payments/') ?>" href="/admin/payments/index.php">Payments &amp; Deposits</a><?php endif; ?>
  <?php if (admin_can('gateways.manage')): ?><a class="adm-link <?= $isAct('/admin/gateways/') ?>" href="/admin/gateways/index.php">Payment Gateways</a><?php endif; ?>
  <?php if (admin_can('wallet.view')): ?><a class="adm-link <?= $isAct('/admin/wallet/') ?>" href="/admin/wallet/index.php">Wallet Ledger</a><?php endif; ?>
  <?php if (admin_can('currency.manage')): ?><a class="adm-link <?= $isAct('/admin/currency/') ?>" href="/admin/currency/index.php">Currencies &amp; Rates</a><?php endif; ?>
  <?php if (admin_can('referrals.manage')): ?><a class="adm-link <?= $isAct('/admin/referrals/') ?>" href="/admin/referrals/index.php">Referrals</a><?php endif; ?>

  <div class="adm-grp">Operations</div>
  <?php if (admin_can('child_panels.manage')): ?><a class="adm-link <?= $isAct('/admin/child-panels/') ?>" href="/admin/child-panels/index.php">Child Panels</a><?php endif; ?>
  <?php if (admin_can('tickets.view')): ?><a class="adm-link <?= $isAct('/admin/tickets/') ?>" href="/admin/tickets/index.php">Support Tickets</a><?php endif; ?>
  <?php if (admin_can('notifications.manage')): ?><a class="adm-link <?= $isAct('/admin/notifications/') ?>" href="/admin/notifications/index.php">Notifications</a><?php endif; ?>
  <?php if (admin_can('reports.view')): ?><a class="adm-link <?= $isAct('/admin/reports/') ?>" href="/admin/reports/index.php">Reports &amp; Export</a><?php endif; ?>

  <div class="adm-grp">Platform &amp; Security</div>
  <?php if (admin_can('website.manage')): ?><a class="adm-link <?= $isAct('/admin/website/') ?>" href="/admin/website/index.php">Public Website &amp; SEO</a><?php endif; ?>
  <?php if (admin_can('themes.manage')): ?><a class="adm-link <?= $isAct('/admin/themes/') ?>" href="/admin/themes/index.php">Themes (Classic)</a><?php endif; ?>
  <?php if (admin_can('roles.manage')): ?><a class="adm-link <?= $isAct('/admin/roles/') ?>" href="/admin/roles/index.php">Roles &amp; Admins</a><?php endif; ?>
  <?php if (admin_can('security.view')): ?><a class="adm-link <?= $isAct('/admin/security/') ?>" href="/admin/security/index.php">Security &amp; Logs</a><?php endif; ?>
  <?php if (admin_can('settings.manage')): ?><a class="adm-link <?= $isAct('/admin/settings/') ?>" href="/admin/settings/index.php">System Settings</a><?php endif; ?>
  <a class="adm-link" style="color:#FDA4AF;margin-top:8px" href="/admin/logout.php">Sign Out</a>
</aside>
