<?php
declare(strict_types=1);

$pageTitle = 'API Documentation';
$endpoint = app_url('/api/index.php');
require __DIR__ . '/layouts/header.php';
?>
<div class="card">
  <div class="section-head">
    <h1 class="section-title">SMM Reseller API v2 Documentation</h1>
    <a href="/user/index.php?page=api" class="view-all-pill">&larr; My API Key</a>
  </div>
  <p style="font-size:13.5px;color:#777083">All requests use HTTP <code>POST</code> (application/x-www-form-urlencoded) to <code><?= e($endpoint) ?></code> and return JSON.</p>

  <h3 style="margin-top:18px">1. Service List</h3>
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
