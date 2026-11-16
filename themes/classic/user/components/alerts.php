<?php
declare(strict_types=1);
$flashOk = Session::flash('success');
$flashErr = Session::flash('error');
$flashInfo = Session::flash('info');
?>
<?php if ($flashOk): ?><div class="alert alert-success"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="alert alert-error"><?= e($flashErr) ?></div><?php endif; ?>
<?php if ($flashInfo): ?><div class="alert alert-info"><?= e($flashInfo) ?></div><?php endif; ?>
