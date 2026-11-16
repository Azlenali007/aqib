<?php
declare(strict_types=1);
$unread = (int) ($unreadNotifications ?? 0);
$notifs = $recentNotifications ?? [];
?>
<div style="position:relative">
  <button type="button" class="icon-circle-btn" data-dropdown-toggle="notifDropdown" aria-label="Notifications">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
    <?php if ($unread > 0): ?>
      <span id="notifUnreadBadge" class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
    <?php endif; ?>
  </button>
  <div id="notifDropdown" class="dropdown-menu notif-popup">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:4px 6px 8px;border-bottom:1px solid #EAE5F1;margin-bottom:6px">
      <strong style="font-size:13.5px">Notifications</strong>
      <button id="markNotifReadBtn" data-csrf="<?= e(csrf_token()) ?>" type="button" style="background:none;border:none;color:#7045D8;font-size:12px;font-weight:700;cursor:pointer">Mark read</button>
    </div>
    <?php if (empty($notifs)): ?>
      <div style="padding:14px;text-align:center;color:#777083;font-size:13px">No notifications yet.</div>
    <?php else: ?>
      <?php foreach ($notifs as $n): ?>
        <a href="<?= e($n['link'] ?: '/user/index.php?page=notifications') ?>" class="notif-item">
          <div style="font-size:13px;font-weight:700;color:#211A2D"><?= e($n['title']) ?></div>
          <div style="font-size:12px;color:#777083;margin-top:2px"><?= e($n['message']) ?></div>
          <div style="font-size:11px;color:#9B94A8;margin-top:3px"><?= e(time_ago($n['created_at'])) ?></div>
        </a>
      <?php endforeach; ?>
      <a href="/user/index.php?page=notifications" style="display:block;text-align:center;padding:8px;font-size:12.5px;font-weight:700;color:#7045D8">View All Notifications &rarr;</a>
    <?php endif; ?>
  </div>
</div>
