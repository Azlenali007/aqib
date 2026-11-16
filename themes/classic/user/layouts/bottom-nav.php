<?php
declare(strict_types=1);
$curPage = $page ?? 'dashboard';
?>
<nav class="bottom-nav" aria-label="Bottom Navigation">
  <a href="/user/index.php?page=dashboard" class="bottom-nav-item <?= $curPage === 'dashboard' ? 'active' : '' ?>">
    <svg width="21" height="21" viewBox="0 0 24 24" fill="<?= $curPage === 'dashboard' ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z"/></svg>
    <span>Home</span>
  </a>
  <a href="/user/index.php?page=services" class="bottom-nav-item <?= $curPage === 'services' ? 'active' : '' ?>">
    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.8"/><rect x="14" y="3" width="7" height="7" rx="1.8"/><rect x="3" y="14" width="7" height="7" rx="1.8"/><rect x="14" y="14" width="7" height="7" rx="1.8"/></svg>
    <span>Services</span>
  </a>
  <a href="/user/index.php?page=new-order" class="bottom-nav-center" aria-label="New Order">
    <span class="bottom-fab">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </span>
    <span style="font-size:11px;font-weight:700;color:<?= $curPage === 'new-order' ? 'var(--primary)' : 'var(--muted)' ?>;margin-top:2px">Order</span>
  </a>
  <a href="/user/index.php?page=orders" class="bottom-nav-item <?= ($curPage === 'orders' || $curPage === 'order-details') ? 'active' : '' ?>">
    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
    <span>Orders</span>
  </a>
  <a href="/user/index.php?page=profile" class="bottom-nav-item <?= ($curPage === 'profile' || $curPage === 'settings') ? 'active' : '' ?>">
    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span>Profile</span>
  </a>
</nav>
