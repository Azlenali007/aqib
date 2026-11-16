<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('security.view');
$adminTitle = 'Security, Audit & Error Logs';

$attempts = Database::fetchAll('SELECT * FROM login_attempts ORDER BY id DESC LIMIT 60');
$activities = Database::fetchAll('SELECT * FROM activity_logs ORDER BY id DESC LIMIT 60');

$readLogTail = static function (string $filename): string {
    $path = SMM_STORAGE . '/logs/' . $filename;
    if (!is_file($path)) {
        return 'Log file not created yet (no entries).';
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!$lines) {
        return 'Empty log file.';
    }
    return implode("\n", array_slice($lines, -50));
};

require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Recent Login Attempts</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:460px">
        <thead><tr><th>Scope</th><th>Identifier</th><th>IP</th><th>Outcome</th><th>Time</th></tr></thead>
        <tbody>
        <?php if (empty($attempts)): ?><tr><td colspan="5" style="text-align:center;color:#777083">No login attempts recorded yet.</td></tr>
        <?php else: foreach ($attempts as $la): ?>
          <tr>
            <td><?= e($la['scope']) ?></td>
            <td><?= e($la['identifier']) ?></td>
            <td><code><?= e($la['ip_address']) ?></code></td>
            <td><span class="badge <?= (int) $la['succeeded'] === 1 ? 'badge-success' : 'badge-danger' ?>"><?= (int) $la['succeeded'] === 1 ? 'Success' : 'Failed' ?></span></td>
            <td><?= e($la['attempted_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Admin &amp; System Activity Logs</h2>
    <div class="table-responsive">
      <table class="adm-table" style="min-width:460px">
        <thead><tr><th>Actor</th><th>Action</th><th>Entity</th><th>IP</th><th>Time</th></tr></thead>
        <tbody>
        <?php if (empty($activities)): ?><tr><td colspan="5" style="text-align:center;color:#777083">No activity logs recorded yet.</td></tr>
        <?php else: foreach ($activities as $al): ?>
          <tr>
            <td><?= e($al['actor_type']) ?> #<?= (int) ($al['actor_id'] ?? 0) ?></td>
            <td><code><?= e($al['action']) ?></code></td>
            <td><?= e(($al['entity_type'] ?? '') . ($al['entity_id'] ? ' #' . $al['entity_id'] : '')) ?></td>
            <td><code><?= e($al['ip_address'] ?? '—') ?></code></td>
            <td><?= e($al['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="grid-2">
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">Application Error Log (storage/logs/error.log)</h2>
    <pre style="background:#1E1332;color:#E9E4F5;padding:12px;border-radius:12px;font-size:11.5px;max-height:280px;overflow:auto"><?= e($readLogTail('error.log')) ?></pre>
  </div>
  <div class="adm-card">
    <h2 style="margin:0 0 10px;font-size:16px">PHP Runtime Log (storage/logs/php_errors.log)</h2>
    <pre style="background:#1E1332;color:#E9E4F5;padding:12px;border-radius:12px;font-size:11.5px;max-height:280px;overflow:auto"><?= e($readLogTail('php_errors.log')) ?></pre>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
