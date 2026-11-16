<?php
declare(strict_types=1);
$curCode = $displayCurrency ?? Currency::systemBase();
$curMeta = Currency::getCurrencyMeta($curCode);
?>
<div class="currency-pill">
  <button type="button" class="currency-btn" data-dropdown-toggle="currencyDropdown">
    <span><?= e($curMeta['symbol']) ?> <?= e($curCode) ?></span>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
  </button>
  <div id="currencyDropdown" class="dropdown-menu">
    <?php foreach ($activeCurrencies ?? [] as $c): ?>
      <a href="/user/index.php?page=<?= urlencode($page ?? 'dashboard') ?>&set_currency=<?= urlencode($c['code']) ?>"
         class="dropdown-item <?= $c['code'] === $curCode ? 'active' : '' ?>">
        <span><?= e($c['symbol']) ?> <?= e($c['code']) ?></span>
        <span style="font-size:11px;color:#777083"><?= e($c['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
