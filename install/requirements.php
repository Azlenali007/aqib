<?php
declare(strict_types=1);

$checks = [
    ['label' => 'PHP Version >= 8.1 (8.2+ recommended)', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'val' => PHP_VERSION],
    ['label' => 'PDO MySQL Extension', 'ok' => extension_loaded('pdo_mysql'), 'val' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing'],
    ['label' => 'cURL Extension', 'ok' => extension_loaded('curl'), 'val' => extension_loaded('curl') ? 'Loaded' : 'Missing'],
    ['label' => 'OpenSSL Extension', 'ok' => extension_loaded('openssl'), 'val' => extension_loaded('openssl') ? 'Loaded' : 'Missing'],
    ['label' => 'Mbstring Extension', 'ok' => extension_loaded('mbstring'), 'val' => extension_loaded('mbstring') ? 'Loaded' : 'Missing'],
    ['label' => 'JSON Extension', 'ok' => extension_loaded('json'), 'val' => extension_loaded('json') ? 'Loaded' : 'Missing'],
    ['label' => 'Fileinfo Extension', 'ok' => extension_loaded('fileinfo'), 'val' => extension_loaded('fileinfo') ? 'Loaded' : 'Missing'],
    ['label' => 'config/ Writable', 'ok' => is_writable(SMM_CONFIG), 'val' => is_writable(SMM_CONFIG) ? 'Writable' : 'Not Writable'],
    ['label' => 'storage/ Writable', 'ok' => is_writable(SMM_STORAGE) || @mkdir(SMM_STORAGE, 0755, true), 'val' => is_writable(SMM_STORAGE) ? 'Writable' : 'Not Writable'],
];
$allOk = !in_array(false, array_column($checks, 'ok'), true);

foreach ($checks as $c): ?>
  <div class="row">
    <span><?= h($c['label']) ?></span>
    <span class="<?= $c['ok'] ? 'ok' : 'bad' ?>"><?= h($c['val']) ?></span>
  </div>
<?php endforeach; ?>

<?php if ($allOk): ?>
  <a class="btn" href="?step=database">Continue to Database Setup &rarr;</a>
<?php else: ?>
  <div class="alert alert-err" style="margin-top:16px">Please resolve the missing PHP extensions or directory permissions before continuing.</div>
<?php endif; ?>
