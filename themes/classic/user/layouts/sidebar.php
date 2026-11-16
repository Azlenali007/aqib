<?php
declare(strict_types=1);
$navItems = [
    ['group' => 'Overview', 'items' => [
        ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z"/></svg>'],
        ['page' => 'new-order', 'label' => 'New Order', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>'],
        ['page' => 'mass-order', 'label' => 'Mass Order', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>'],
    ]],
    ['group' => 'Services & Orders', 'items' => [
        ['page' => 'services', 'label' => 'Services', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>'],
        ['page' => 'favourites', 'label' => 'Favourites', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/></svg>'],
        ['page' => 'orders', 'label' => 'Orders History', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>'],
        ['page' => 'refill', 'label' => 'Refills', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15"/></svg>'],
    ]],
    ['group' => 'Billing & Wallet', 'items' => [
        ['page' => 'wallet', 'label' => 'Wallet', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h4"/></svg>'],
        ['page' => 'add-funds', 'label' => 'Add Funds', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>'],
        ['page' => 'transactions', 'label' => 'Transactions', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'],
        ['page' => 'payments', 'label' => 'Payment History', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20"/></svg>'],
    ]],
    ['group' => 'Reseller & Growth', 'items' => [
        ['page' => 'api', 'label' => 'API & Docs', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'],
        ['page' => 'child-panels', 'label' => 'Child Panels', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>'],
        ['page' => 'refer-earn', 'label' => 'Refer & Earn', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    ]],
    ['group' => 'Account', 'items' => [
        ['page' => 'tickets', 'label' => 'Support Tickets', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'],
        ['page' => 'notifications', 'label' => 'Notifications', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>'],
        ['page' => 'profile', 'label' => 'Profile', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'],
        ['page' => 'settings', 'label' => 'Settings', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.6.85 1 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'],
    ]],
];

$brandLogoSvg = '<svg width="36" height="36" viewBox="0 0 40 40" fill="none" aria-hidden="true"><defs><linearGradient id="smmLogoG" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient></defs><rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#smmLogoG)"/><rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#smmLogoG)"/><rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#smmLogoG)"/><path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$renderNav = static function (array $navItems, string $currentPage): void {
    foreach ($navItems as $grp) {
        echo '<div class="sidebar-group-label">' . e($grp['group']) . '</div>';
        foreach ($grp['items'] as $item) {
            $active = ($currentPage === $item['page'] || str_starts_with($currentPage, $item['page'] . '/')) ? 'active' : '';
            echo '<a class="sidebar-link ' . $active . '" href="/user/index.php?page=' . e($item['page']) . '">' . $item['icon'] . '<span>' . e($item['label']) . '</span></a>';
        }
    }
    echo '<a class="sidebar-link" style="color:#E5485D;margin-top:8px" href="/user/logout.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Logout</span></a>';
};
?>
<aside class="sidebar">
  <a href="/user/index.php?page=dashboard" class="sidebar-brand">
    <?= $brandLogoSvg ?>
    <div>
      <div class="brand-title">SMM <span>Panel</span></div>
      <div class="brand-sub"><?= e($siteTagline) ?></div>
    </div>
  </a>
  <?php $renderNav($navItems, $page ?? 'dashboard'); ?>
</aside>

<div id="drawerBackdrop" class="mobile-drawer-backdrop"></div>
<aside id="mobileDrawer" class="mobile-drawer" aria-label="Mobile Navigation">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <a href="/user/index.php?page=dashboard" style="display:flex;align-items:center;gap:10px">
      <?= $brandLogoSvg ?>
      <div>
        <div class="brand-title">SMM <span>Panel</span></div>
        <div class="brand-sub"><?= e($siteTagline) ?></div>
      </div>
    </a>
    <button id="closeDrawerBtn" class="menu-btn" type="button" aria-label="Close Menu">&times;</button>
  </div>
  <?php $renderNav($navItems, $page ?? 'dashboard'); ?>
</aside>
