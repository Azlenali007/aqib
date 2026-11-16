<?php
declare(strict_types=1);
$heroTitle = (string) get_setting('hero_title', 'Scale Every Social Channel From One Powerful SMM Panel');
$heroSub = (string) get_setting('hero_subtitle', 'Automated delivery, real-time provider syncing, multi-currency wallet, Razorpay checkout, and reseller API.');
$svcCount = (int) Database::fetchValue('SELECT COUNT(*) FROM services WHERE is_active = 1', [], 0);
$catCount = (int) Database::fetchValue('SELECT COUNT(*) FROM categories WHERE is_active = 1', [], 0);
$featuredServices = Database::fetchAll('SELECT s.*, c.name AS cat_name FROM services s INNER JOIN categories c ON c.id = s.category_id WHERE s.is_active = 1 ORDER BY s.is_featured DESC, s.id ASC LIMIT 8');
$baseCurr = Currency::systemBase();
$pubCurr = $isUserLoggedIn ? active_user_currency(Auth::user()) : $baseCurr;
$pubCtx = Currency::getDisplayContext($pubCurr);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($siteName) ?> — <?= e($siteTagline) ?></title>
  <meta name="description" content="<?= e($seoDesc) ?>">
  <meta name="keywords" content="<?= e($seoKeywords) ?>">
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/public/assets/css/public.css">
</head>
<body>
<?php require dirname(__DIR__) . '/pages/_header.php'; ?>
<div class="container">
  <section class="hero">
    <div>
      <span style="display:inline-flex;align-items:center;gap:6px;background:#EFEAFE;color:#5B35D5;padding:6px 14px;border-radius:999px;font-size:12.5px;font-weight:700;margin-bottom:14px">Automated Reseller &amp; Agency Platform</span>
      <h1><?= e($heroTitle) ?></h1>
      <p><?= e($heroSub) ?></p>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <?php if ($isUserLoggedIn): ?>
          <a href="/user/index.php" class="btn btn-primary">Open Dashboard &rarr;</a>
        <?php else: ?>
          <a href="/user/register.php" class="btn btn-primary">Get Started Free &rarr;</a>
          <a href="/user/login.php" class="btn btn-outline">Login</a>
        <?php endif; ?>
        <a href="/public/index.php?page=services" class="btn btn-outline">Browse Services</a>
      </div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#5B42F3 0%,#7045D8 60%,#482FC9 100%);color:#fff;border:none;box-shadow:0 18px 38px rgba(91,66,243,.28)">
      <div style="font-size:13px;opacity:.88;font-weight:600">Live Platform Catalog</div>
      <div style="font-size:34px;font-weight:800;margin:6px 0;line-height:1.15"><?= number_format($svcCount) ?> Active Services</div>
      <div style="font-size:14px;opacity:.9;margin-bottom:18px">Across <?= $catCount ?> Social Media Categories</div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;background:rgba(255,255,255,.14);padding:14px;border-radius:18px">
        <div style="text-align:center"><?= platform_icon_svg('instagram', 28) ?></div>
        <div style="text-align:center"><?= platform_icon_svg('youtube', 28) ?></div>
        <div style="text-align:center"><?= platform_icon_svg('facebook', 28) ?></div>
        <div style="text-align:center"><?= platform_icon_svg('telegram', 28) ?></div>
      </div>
    </div>
  </section>

  <section class="grid-3" style="margin-bottom:28px">
    <div class="card"><h3 style="margin:0 0 8px;color:#7045D8;font-size:17px">Instant Provider API Sync</h3><p style="margin:0;color:#6E667D;font-size:14px">Real-time order forwarding, status tracking, refill checks, and currency-accurate pricing conversions.</p></div>
    <div class="card"><h3 style="margin:0 0 8px;color:#7045D8;font-size:17px">Multi-Currency Wallet</h3><p style="margin:0;color:#6E667D;font-size:14px">Razorpay instant checkout and verified manual bank/UPI deposits with atomic ledger protection.</p></div>
    <div class="card"><h3 style="margin:0 0 8px;color:#7045D8;font-size:17px">Reseller API &amp; Child Panels</h3><p style="margin:0;color:#6E667D;font-size:14px">Connect your own automation via REST API v2 or launch isolated white-label child panels.</p></div>
  </section>

  <?php if (!empty($featuredServices)): ?>
    <section class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <h2 style="margin:0;font-size:20px">Popular Services</h2>
          <span style="background:#EFEAFE;color:#5B35D5;font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px">Currency: <?= e($pubCtx['effective_currency']) ?></span>
        </div>
        <a href="/public/index.php?page=services" style="background:#EFEAFE;color:#7045D8;font-weight:700;font-size:13px;padding:7px 14px;border-radius:999px">View Full Catalog &rarr;</a>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>ID</th><th>Service</th><th>Category</th><th>Rate / 1K</th><th>Min - Max</th></tr></thead>
          <tbody>
          <?php foreach ($featuredServices as $fs): ?>
            <tr>
              <td><strong>#<?= (int) $fs['id'] ?></strong></td>
              <td><div style="display:flex;align-items:center;gap:8px"><?= platform_icon_svg((string) $fs['platform'], 20) ?><span><?= e($fs['name']) ?></span></div></td>
              <td><?= e($fs['cat_name']) ?></td>
              <td style="font-weight:700;color:#7045D8"><?= e(format_money((string) $fs['selling_price'], $pubCurr)) ?></td>
              <td><?= number_format((int) $fs['min_qty']) ?> - <?= number_format((int) $fs['max_qty']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/pages/_footer.php'; ?>
</body></html>
