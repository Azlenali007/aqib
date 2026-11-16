document.addEventListener('DOMContentLoaded', () => {
  // Mobile Sidebar Drawer
  const drawer = document.getElementById('mobileDrawer');
  const backdrop = document.getElementById('drawerBackdrop');
  const openBtn = document.getElementById('openDrawerBtn');
  const closeBtn = document.getElementById('closeDrawerBtn');

  function toggleDrawer(show) {
    if (!drawer || !backdrop) return;
    drawer.classList.toggle('open', show);
    backdrop.classList.toggle('open', show);
  }
  if (openBtn) openBtn.addEventListener('click', () => toggleDrawer(true));
  if (closeBtn) closeBtn.addEventListener('click', () => toggleDrawer(false));
  if (backdrop) backdrop.addEventListener('click', () => toggleDrawer(false));

  // Dropdown menus (Currency, Notifications)
  document.querySelectorAll('[data-dropdown-toggle]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const targetId = btn.getAttribute('data-dropdown-toggle');
      const menu = document.getElementById(targetId);
      document.querySelectorAll('.dropdown-menu.open').forEach(m => {
        if (m !== menu) m.classList.remove('open');
      });
      if (menu) menu.classList.toggle('open');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.open').forEach(m => m.classList.remove('open'));
  });

  // Mark notifications read
  const markReadBtn = document.getElementById('markNotifReadBtn');
  if (markReadBtn) {
    markReadBtn.addEventListener('click', async () => {
      const csrf = markReadBtn.getAttribute('data-csrf') || '';
      const fd = new FormData();
      fd.append('_csrf', csrf);
      const res = await fetch('/user/index.php?ajax=mark_notifications_read', { method: 'POST', body: fd });
      if (res.ok) {
        const badge = document.getElementById('notifUnreadBadge');
        if (badge) badge.remove();
      }
    });
  }

  // Favourite toggle buttons
  document.querySelectorAll('[data-fav-btn]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const sid = btn.getAttribute('data-service-id');
      const csrf = btn.getAttribute('data-csrf') || '';
      const fd = new FormData();
      fd.append('_csrf', csrf);
      fd.append('service_id', sid);
      const res = await fetch('/user/index.php?ajax=toggle_fav', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        btn.classList.toggle('is-fav', !!data.favourited);
        btn.textContent = data.favourited ? '★ Favourited' : '☆ Favourite';
      }
    });
  });
});
