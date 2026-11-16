<?php
declare(strict_types=1);
?>
<header class="pub-nav">
  <div class="container pub-nav-inner">
    <a href="/" style="display:flex;align-items:center;gap:10px">
      <svg width="34" height="34" viewBox="0 0 40 40" fill="none"><defs><linearGradient id="pubLogo" x1="4" y1="36" x2="36" y2="4" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient></defs><rect x="6" y="23" width="6" height="11" rx="2.5" fill="url(#pubLogo)"/><rect x="15" y="17" width="6" height="17" rx="2.5" fill="url(#pubLogo)"/><rect x="24" y="10" width="6" height="24" rx="2.5" fill="url(#pubLogo)"/><path d="M5 20L17 11L22 14L33 5" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/><path d="M27 5H33V11" stroke="#5B42F3" stroke-width="2.6" stroke-linecap="round"/></svg>
      <div><strong style="font-size:18px">SMM <span style="color:#7045D8">Panel</span></strong><div style="font-size:11px;color:#777083"><?= e($siteTagline) ?></div></div>
    </a>
    <nav class="pub-links">
      <a href="/public/index.php?page=home">Home</a>
      <a href="/public/index.php?page=services">Services</a>
      <a href="/public/index.php?page=faq">FAQ</a>
      <a href="/public/index.php?page=about">About</a>
      <a href="/public/index.php?page=contact">Contact</a>
    </nav>
    <div style="display:flex;gap:8px">
      <?php if ($isUserLoggedIn): ?>
        <a href="/user/index.php" class="btn btn-primary">Dashboard</a>
      <?php else: ?>
        <a href="/user/login.php" class="btn btn-outline">Login</a>
        <a href="/user/register.php" class="btn btn-primary">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</header>
