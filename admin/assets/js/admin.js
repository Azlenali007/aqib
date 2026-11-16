document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('admSidebar');
  const toggleBtn = document.getElementById('admMenuBtn');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Select All checkboxes for Bulk Operations
  const selectAll = document.getElementById('bulkSelectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.bulk-row-cb').forEach(cb => {
        cb.checked = selectAll.checked;
      });
      updateBulkCount();
    });
  }
  document.querySelectorAll('.bulk-row-cb').forEach(cb => {
    cb.addEventListener('change', updateBulkCount);
  });

  function updateBulkCount() {
    const el = document.getElementById('bulkSelectedCount');
    if (!el) return;
    const n = document.querySelectorAll('.bulk-row-cb:checked').length;
    el.textContent = n + ' selected';
  }
  updateBulkCount();
});
