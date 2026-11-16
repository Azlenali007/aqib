<?php
declare(strict_types=1);
$curCode = $currencyCtx['effective_currency'] ?? ($displayCurrency ?? Currency::systemBase());
$curMeta = Currency::getCurrencyMeta($curCode);
$baseCode = Currency::systemBase();
$rateMissing = empty($currencyCtx['rate_available']);
?>
<div class="currency-pill">
  <button type="button" class="currency-btn" data-dropdown-toggle="currencyDropdown" aria-expanded="false" aria-label="Switch Display Currency">
    <?php if ($rateMissing): ?>
      <span class="currency-warn-dot" title="Requested rate missing — displaying <?= e($curCode) ?>"></span>
    <?php endif; ?>
    <span><?= e($curMeta['symbol']) ?> <?= e($curCode) ?></span>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
  </button>
  <div id="currencyDropdown" class="dropdown-menu">
    <div style="padding:5px 10px 7px;font-size:11px;font-weight:700;color:#8D85A0;text-transform:uppercase;letter-spacing:.05em">Display Currency</div>
    <?php foreach ($activeCurrencies ?? [] as $c):
      $hasRate = ($c['code'] === $baseCode) || (Currency::getExchangeRate($baseCode, $c['code']) !== null);
      $isSelected = ($c['code'] === ($displayCurrency ?? $curCode));
    ?>
      <a href="<?= e(currency_switch_url((string) $c['code'])) ?>"
         class="dropdown-item <?= $isSelected ? 'active' : '' ?>">
        <span><strong><?= e($c['symbol']) ?> <?= e($c['code']) ?></strong></span>
        <span style="font-size:11px;color:<?= $hasRate ? '#777083' : '#D97706' ?>">
          <?= $hasRate ? e($c['name']) : 'Rate N/A' ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
