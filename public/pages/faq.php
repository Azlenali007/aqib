<?php
declare(strict_types=1);
$faqs = json_decode((string) get_setting('faq_json', '[]'), true) ?: [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FAQ — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px;max-width:860px">
  <div class="card">
    <h1 style="margin:0 0 16px">Frequently Asked Questions</h1>
    <?php foreach ($faqs as $item): ?>
      <div style="border:1px solid #EAE5F1;border-radius:14px;padding:14px;margin-bottom:10px">
        <button data-faq-toggle type="button" style="background:none;border:none;width:100%;text-align:left;font-weight:700;font-size:15px;cursor:pointer;color:#211A2D"><?= e($item['q'] ?? '') ?></button>
        <div style="margin-top:8px;color:#777083;font-size:14px"><?= e($item['a'] ?? '') ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
