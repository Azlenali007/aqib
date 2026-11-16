<?php
declare(strict_types=1);
$wa = trim((string) get_setting('whatsapp_number', ''));
$tg = trim((string) get_setting('telegram_username', ''));
?>
</main>
<?php if ($wa !== '' || $tg !== ''): ?>
<div style="position:fixed;right:16px;bottom:84px;z-index:45;display:flex;flex-direction:column;gap:8px">
  <?php if ($tg !== ''): ?>
    <a href="https://t.me/<?= e(ltrim($tg, '@')) ?>" target="_blank" rel="noopener noreferrer" title="Telegram Support" style="width:44px;height:44px;border-radius:50%;background:#229ED9;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(34,158,217,.35)">
      <?= platform_icon_svg('telegram', 24) ?>
    </a>
  <?php endif; ?>
  <?php if ($wa !== ''): ?>
    <a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $wa)) ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp Support" style="width:44px;height:44px;border-radius:50%;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(37,211,102,.35)">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.6 14.1c-.2.7-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.8-.7-3.1-1.3-5.1-4.5-5.3-4.7-.2-.2-1.3-1.7-1.3-3.2s.8-2.3 1.1-2.6c.3-.3.6-.4.8-.4h.6c.2 0 .5 0 .7.6.2.6.8 2 .9 2.1.1.2.1.4 0 .6-.1.2-.2.3-.4.5l-.5.6c-.2.2-.3.4-.1.7.2.3.8 1.4 1.8 2.3 1.3 1.1 2.3 1.5 2.7 1.7.3.1.5.1.7-.1l.9-1.1c.2-.3.5-.2.8-.1.3.1 1.9.9 2.2 1.1.3.2.5.3.6.4.1.2.1.9-.1 1.5z"/></svg>
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>
</div>
<?php require __DIR__ . '/bottom-nav.php'; ?>
<script src="/themes/classic/user/assets/js/app.js"></script>
</body>
</html>
