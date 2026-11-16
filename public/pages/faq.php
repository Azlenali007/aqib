<?php
declare(strict_types=1);
$faqs = json_decode((string) get_setting('faq_json', '[]'), true) ?: [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FAQ — <?= e($siteName) ?></title><link rel="stylesheet" href="/public/assets/css/public.css"></head><body>
<?php require __DIR__ . '/_header.php'; ?>
<div class="container" style="padding-top:28px;max-width:860px">
  <div class="card">
    <h1 style="margin:0 0 16px">Frequently Asked Questions</h1>
    <?php foreach ($faqs as $idx => $item): ?>
      <div style="border:1px solid #EAE2F5;border-radius:16px;padding:14px 16px;margin-bottom:10px;background:#FAF8FE">
        <button data-faq-toggle type="button" aria-expanded="true" style="background:none;border:none;width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;text-align:left;font-weight:700;font-size:15px;cursor:pointer;color:#1F182B;padding:0">
          <span><?= e($item['q'] ?? '') ?></span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7045D8" stroke-width="2.4" style="flex-shrink:0"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div style="margin-top:8px;color:#6E667D;font-size:14px;display:block"><?= e($item['a'] ?? '') ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
</body></html>
