<?php
declare(strict_types=1);
$terms = (string) get_setting('terms_content', '');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Terms of Service — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px;max-width:860px"><div class="card"><h1 style="margin:0 0 12px">Terms of Service</h1><div style="color:#777083;white-space:pre-line"><?= e($terms) ?></div></div></div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
