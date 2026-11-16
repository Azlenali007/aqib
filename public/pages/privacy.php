<?php
declare(strict_types=1);
$privacy = (string) get_setting('privacy_content', '');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Privacy Policy — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px;max-width:900px"><div class="card" style="padding:28px"><div class="hero-pill" style="margin-bottom:12px">Privacy</div><h1 style="margin:0 0 14px;font-size:28px;font-weight:800">Privacy Policy</h1><div style="color:var(--muted);font-size:15px;line-height:1.7;white-space:pre-line"><?= e($privacy) ?></div></div></div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
