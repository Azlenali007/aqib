<?php
declare(strict_types=1);
?>
<header class="topbar">
  <div class="topbar-left">
    <button id="openDrawerBtn" class="menu-btn" type="button" aria-label="Open Navigation">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <a href="/user/index.php?page=dashboard" class="topbar-brand-mobile">
      <svg width="34" height="34" viewBox="0 0 40 40" fill="none" aria-hidden="true"><defs><linearGradient id="smmLogoMob" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient></defs><rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#smmLogoMob)"/><rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#smmLogoMob)"/><rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#smmLogoMob)"/><path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <div>
        <div class="brand-title">SMM <span>Panel</span></div>
        <div class="brand-sub"><?= e((string) get_setting('site_tagline', 'Grow Your Social Media')) ?></div>
      </div>
    </a>
  </div>

  <div class="topbar-actions">
    <?php require dirname(__DIR__) . '/components/currency-selector.php'; ?>
    <?php require dirname(__DIR__) . '/components/notification-popup.php'; ?>
    <a href="/user/index.php?page=profile" class="avatar-btn" title="<?= e($user['username']) ?>">
      <?= strtoupper(substr($user['username'], 0, 1)) ?>
    </a>
  </div>
</header>
