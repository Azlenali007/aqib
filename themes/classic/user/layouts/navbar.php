<?php
declare(strict_types=1);
$siteParts = preg_split('/\s+/', trim($siteName ?: 'SMM Panel'), 2);
$brandFirst = $siteParts[0] ?? 'SMM';
$brandRest = $siteParts[1] ?? 'Panel';
?>
<header class="topbar">
  <div class="topbar-left">
    <button id="openDrawerBtn" class="menu-btn" type="button" aria-label="Open Navigation Menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <a href="/user/index.php?page=dashboard" class="topbar-brand-mobile">
      <svg width="34" height="34" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <defs>
          <linearGradient id="smmLogoMob" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#8B5CF6"/>
            <stop offset="100%" stop-color="#3B82F6"/>
          </linearGradient>
        </defs>
        <rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#smmLogoMob)"/>
        <rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#smmLogoMob)"/>
        <rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#smmLogoMob)"/>
        <path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <div style="min-width:0">
        <div class="brand-title"><?= e($brandFirst) ?> <?= $brandRest !== '' ? '<span>' . e($brandRest) . '</span>' : '' ?></div>
        <div class="brand-sub"><?= e($siteTagline) ?></div>
      </div>
    </a>

    <div class="topbar-desktop-heading">
      <div class="topbar-page-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
      <div class="topbar-breadcrumb">
        <a href="/user/index.php?page=dashboard" style="color:var(--primary)">Panel</a>
        <span>/</span>
        <span><?= e($pageTitle ?? 'Dashboard') ?></span>
      </div>
    </div>
  </div>

  <div class="topbar-actions">
    <a href="/user/index.php?page=wallet" class="topbar-wallet-chip" title="Wallet Balance">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M16 12h4v2h-4a1 1 0 0 1 0-2z"/></svg>
      <span><?= e(format_money((string) ($user['balance'] ?? '0.00'), $displayCurrency)) ?></span>
    </a>

    <?php require dirname(__DIR__) . '/components/currency-selector.php'; ?>
    <?php require dirname(__DIR__) . '/components/notification-popup.php'; ?>

    <div class="avatar-wrap">
      <button type="button" class="avatar-btn" data-dropdown-toggle="profileDropdown" aria-label="Account Menu" aria-expanded="false" title="<?= e($user['username']) ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z"/>
        </svg>
      </button>
      <div id="profileDropdown" class="dropdown-menu" style="min-width:215px">
        <div style="padding:8px 12px 10px;border-bottom:1px solid #F0EAF9;margin-bottom:6px">
          <div style="font-size:13.5px;font-weight:800;color:var(--text)"><?= e($user['username']) ?></div>
          <div style="font-size:11.5px;color:var(--muted)">ID: #<?= (int) $user['id'] ?> &bull; <?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></div>
        </div>
        <a href="/user/index.php?page=profile" class="dropdown-item"><span>Profile</span></a>
        <a href="/user/index.php?page=wallet" class="dropdown-item"><span>Wallet &amp; Funds</span></a>
        <a href="/user/index.php?page=settings" class="dropdown-item"><span>Account Settings</span></a>
        <a href="/public/index.php?page=home" class="dropdown-item"><span>Public Website</span></a>
        <a href="/user/logout.php" class="dropdown-item" style="color:#DC2626"><span>Logout</span></a>
      </div>
    </div>
  </div>
</header>
