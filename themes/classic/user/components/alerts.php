<?php
declare(strict_types=1);
$flashOk = Session::flash('success');
$flashErr = Session::flash('error');
$flashInfo = Session::flash('info');
?>
<?php if ($flashOk): ?><div class="alert alert-success"><span><?= e($flashOk) ?></span></div><?php endif; ?>
<?php if ($flashErr): ?><div class="alert alert-error"><span><?= e($flashErr) ?></span></div><?php endif; ?>
<?php if ($flashInfo): ?><div class="alert alert-info"><span><?= e($flashInfo) ?></span></div><?php endif; ?>
<?php if (!empty($currencyCtx) && empty($currencyCtx['rate_available'])): ?>
  <div class="alert alert-warning" role="status">
    <span><?= e((string) $currencyCtx['warning']) ?></span>
    <a href="<?= e(currency_switch_url((string) $currencyCtx['base_currency'])) ?>" class="btn btn-outline btn-sm" style="border-color:#F59E0B;color:#92400E">Switch to <?= e((string) $currencyCtx['base_currency']) ?></a>
  </div>
<?php endif; ?>
