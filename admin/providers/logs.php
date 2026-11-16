<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('providers.view');
$adminTitle = 'Provider API Logs';
$logs = Database::fetchAll('SELECT pl.*, p.name AS provider_name FROM provider_logs pl INNER JOIN providers p ON p.id = pl.provider_id ORDER BY pl.id DESC LIMIT 100');
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h2 style="margin:0;font-size:16px">Recent Provider API &amp; Error Logs</h2>
    <a class="btn btn-outline btn-sm" href="/admin/providers/index.php">&larr; Back to Providers</a>
  </div>
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Provider</th><th>Action</th><th>HTTP</th><th>Duration</th><th>Status / Error</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($logs)): ?><tr><td colspan="7" style="text-align:center;color:#777083">No provider API logs yet.</td></tr>
      <?php else: foreach ($logs as $l): ?>
        <tr>
          <td>#<?= (int) $l['id'] ?></td>
          <td><?= e($l['provider_name']) ?></td>
          <td><code><?= e($l['action']) ?></code></td>
          <td><?= $l['response_code'] ?: '-' ?></td>
          <td><?= $l['duration_ms'] ?> ms</td>
          <td><span class="badge <?= $l['is_error'] ? 'badge-danger' : 'badge-success' ?>"><?= $l['is_error'] ? e($l['error_message'] ?: 'Error') : 'OK' ?></span></td>
          <td><?= e($l['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
