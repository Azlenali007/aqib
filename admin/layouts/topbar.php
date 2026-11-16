<?php
declare(strict_types=1);
?>
<header class="adm-topbar">
  <div style="display:flex;align-items:center;gap:10px">
    <button id="admMenuBtn" class="adm-menu-btn" type="button" aria-label="Menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <strong style="font-size:16px"><?= e($adminTitle ?? 'Admin Panel') ?></strong>
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <span class="badge badge-muted">Base: <?= e(Currency::systemBase()) ?></span>
    <a class="btn btn-outline btn-sm" href="/" target="_blank">Public Site</a>
    <span style="font-size:13px;font-weight:700"><?= e($admin['username'] ?? '') ?></span>
  </div>
</header>
