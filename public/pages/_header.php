<?php
declare(strict_types=1);
$curPublicPage = $page ?? 'home';
$pubNavItems = [
    ['slug' => 'home', 'label' => 'Home', 'href' => '/public/index.php?page=home'],
    ['slug' => 'services', 'label' => 'Services', 'href' => '/public/index.php?page=services'],
    ['slug' => 'about', 'label' => 'About', 'href' => '/public/index.php?page=about'],
    ['slug' => 'faq', 'label' => 'FAQ', 'href' => '/public/index.php?page=faq'],
    ['slug' => 'contact', 'label' => 'Contact', 'href' => '/public/index.php?page=contact'],
];
$siteNameParts = preg_split('/\s+/', trim($siteName ?: 'SMM Panel'), 2);
$brandFirst = $siteNameParts[0] ?? 'SMM';
$brandRest = $siteNameParts[1] ?? 'Panel';
?>
<header id="pubNavbar" class="pub-nav">
  <div class="container pub-nav-inner">
    <a href="/public/index.php?page=home" class="pub-brand">
      <svg width="36" height="36" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <defs>
          <linearGradient id="pubLogo" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#8B5CF6"/>
            <stop offset="100%" stop-color="#3B82F6"/>
          </linearGradient>
        </defs>
        <rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#pubLogo)"/>
        <rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#pubLogo)"/>
        <rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#pubLogo)"/>
        <path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <div style="min-width:0">
        <div class="pub-brand-title"><?= e($brandFirst) ?> <?= $brandRest !== '' ? '<span>' . e($brandRest) . '</span>' : '' ?></div>
        <div class="pub-brand-sub"><?= e($siteTagline) ?></div>
      </div>
    </a>

    <nav class="pub-links" aria-label="Main Navigation">
      <?php foreach ($pubNavItems as $item): ?>
        <?php $isActive = $curPublicPage === $item['slug']; ?>
        <a href="<?= e($item['href']) ?>"
           class="<?= $isActive ? 'active' : '' ?>"
           <?= $isActive ? 'aria-current="page"' : '' ?>>
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="pub-auth-actions">
      <?php if ($isUserLoggedIn): ?>
        <a href="/user/index.php" class="btn btn-primary">Dashboard</a>
        <a href="/user/logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="/user/login.php" class="btn btn-outline">Login</a>
        <a href="/user/register.php" class="btn btn-primary">Register</a>
      <?php endif; ?>
    </div>

    <button id="pubMenuToggle"
            class="pub-menu-toggle"
            type="button"
            aria-label="Toggle Navigation Menu"
            aria-expanded="false"
            aria-controls="pubMobileMenu">
      <svg id="pubMenuIconOpen" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
      <svg id="pubMenuIconClose" style="display:none" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>
</header>

<div id="pubMobileMenu" class="pub-mobile-menu" role="dialog" aria-label="Mobile Navigation">
  <nav class="pub-mobile-links" aria-label="Mobile Navigation Links">
    <?php foreach ($pubNavItems as $item): ?>
      <?php $isActive = $curPublicPage === $item['slug']; ?>
      <a href="<?= e($item['href']) ?>"
         class="<?= $isActive ? 'active' : '' ?>"
         <?= $isActive ? 'aria-current="page"' : '' ?>>
        <span><?= e($item['label']) ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="pub-mobile-actions">
    <?php if ($isUserLoggedIn): ?>
      <a href="/user/index.php" class="btn btn-primary">Dashboard</a>
      <a href="/user/logout.php" class="btn btn-outline">Logout</a>
    <?php else: ?>
      <a href="/user/login.php" class="btn btn-outline">Login</a>
      <a href="/user/register.php" class="btn btn-primary">Register</a>
    <?php endif; ?>
  </div>
</div>
