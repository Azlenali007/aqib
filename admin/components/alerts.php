<?php
declare(strict_types=1);
$ok = Session::flash('success');
$err = Session::flash('error');
$info = Session::flash('info');
?>
<?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
<?php if ($info): ?><div class="alert" style="background:#F1EBFE;color:#4E2CB8"><?= e($info) ?></div><?php endif; ?>
