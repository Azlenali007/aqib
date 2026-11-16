<?php
declare(strict_types=1);
?>
<div class="alert alert-ok">Installation completed successfully! The installer is now locked via <code>storage/installed.lock</code>.</div>
<p style="color:#777083;font-size:14px;line-height:1.6">
  Next steps for production cPanel hosting:
</p>
<ul style="color:#211A2D;font-size:14px;line-height:1.8">
  <li>Log in to the <strong>Admin Panel</strong> to configure Payment Gateways (Razorpay / Manual), API Providers, and Currency Exchange Rates.</li>
  <li>Set up cPanel Cron Jobs for <code>cron/order-sync.php</code>, <code>cron/currency-update.php</code>, and <code>cron/provider-sync.php</code>.</li>
  <li>Optionally delete the <code>/install</code> directory for extra hardening.</li>
</ul >
<div style="display:flex;gap:12px;flex-wrap:wrap">
  <a class="btn" href="/admin/login.php">Open Admin Panel</a>
  <a class="btn" style="background:#24143D" href="/user/register.php">User Registration</a>
  <a class="btn" style="background:#fff;color:#7045D8;border:1px solid #EAE5F1" href="/">Public Website</a>
</div>
