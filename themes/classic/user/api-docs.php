<?php
declare(strict_types=1);

$pageTitle = 'API Documentation';
$endpoint = app_url('/api/index.php');
require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">SMM Reseller API v2 Documentation</h1>
      <p class="page-banner-sub">JSON responses via HTTP POST (<code>application/x-www-form-urlencoded</code>)</p>
    </div>
  </div>
  <a href="/user/index.php?page=api" class="view-all-pill">&larr; My API Key</a>
</div>

<div class="card">
  <p style="font-size:13.5px;color:var(--muted);margin-top:0">Endpoint URL: <code><?= e($endpoint) ?></code></p>

  <h3 style="margin-top:16px">1. Service List</h3>
  <pre style="background:#24143D;color:#F7F5FB;padding:14px;border-radius:14px;overflow:auto;font-size:12.5px">POST <?= e($endpoint) ?>
key=YOUR_API_KEY&action=services</pre>

  <h3>2. Add Order</h3>
  <pre style="background:#24143D;color:#F7F5FB;padding:14px;border-radius:14px;overflow:auto;font-size:12.5px">POST <?= e($endpoint) ?>
key=YOUR_API_KEY&action=add&service=1&link=https://instagram.com/user&quantity=1000</pre>

  <h3>3. Order Status / Multi-Order Status</h3>
  <pre style="background:#24143D;color:#F7F5FB;padding:14px;border-radius:14px;overflow:auto;font-size:12.5px">POST <?= e($endpoint) ?>
key=YOUR_API_KEY&action=status&order=101
// or multiple:
key=YOUR_API_KEY&action=status&orders=101,102</pre>

  <h3>4. Refill &amp; Cancel</h3>
  <pre style="background:#24143D;color:#F7F5FB;padding:14px;border-radius:14px;overflow:auto;font-size:12.5px">POST <?= e($endpoint) ?>
key=YOUR_API_KEY&action=refill&order=101
key=YOUR_API_KEY&action=cancel&orders=101</pre>

  <h3>5. Account Balance</h3>
  <pre style="background:#24143D;color:#F7F5FB;padding:14px;border-radius:14px;overflow:auto;font-size:12.5px">POST <?= e($endpoint) ?>
key=YOUR_API_KEY&action=balance</pre>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
