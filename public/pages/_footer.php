<?php
declare(strict_types=1);
?>
<footer>
  <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</div>
    <div style="display:flex;gap:16px">
      <a href="/public/index.php?page=terms">Terms of Service</a>
      <a href="/public/index.php?page=privacy">Privacy Policy</a>
      <a href="/public/index.php?page=faq">FAQ</a>
      <a href="/public/index.php?page=contact">Contact</a>
    </div>
  </div>
</footer>
<script src="/public/assets/js/public.js"></script>
