<?php
declare(strict_types=1);
require __DIR__ . '/_context.php';
$pageTitle = 'Child Panel Domains';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (($_POST['action'] ?? '') === 'add_domain') {
        $dom = strtolower(trim((string) preg_replace('#^https?://#i', '', (string) ($_POST['domain'] ?? ''))));
        $dom = rtrim($dom, '/');
        if ($dom === '' || !str_contains($dom, '.')) {
            Session::flash('error', 'Invalid domain name.');
        } else {
            $dup = Database::fetchOne('SELECT id FROM child_panel_domains WHERE domain = ? LIMIT 1', [$dom]);
            if ($dup) {
                Session::flash('error', 'Domain already registered.');
            } else {
                Database::execute('INSERT INTO child_panel_domains (child_panel_id, domain, verification_token, is_primary) VALUES (?, ?, ?, 0)', [$cpPanel['id'], $dom, 'smm-verify-' . bin2hex(random_bytes(8))]);
                Session::flash('success', 'Domain added. Add the TXT record and click Verify.');
            }
        }
    } elseif (($_POST['action'] ?? '') === 'verify') {
        $res = ChildPanelService::verifyDomainDns((int) ($_POST['domain_id'] ?? 0), (int) $cpPanel['id']);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Domain DNS verified!' : $res['error']);
    }
    Response::redirect('/user/index.php?page=child-panels/domains&panel_id=' . $cpPanel['id']);
}

$domains = Database::fetchAll('SELECT * FROM child_panel_domains WHERE child_panel_id = ? ORDER BY is_primary DESC, id ASC', [$cpPanel['id']]);
require dirname(__DIR__) . '/layouts/header.php';
render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/domains');
?>
<div class="card">
  <h1 class="section-title" style="margin-bottom:12px">Custom Domains &amp; DNS Verification</h1>
  <form method="post" class="filter-bar">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_domain">
    <input class="form-control" style="max-width:300px" name="domain" placeholder="custom.yourdomain.com" required>
    <button class="btn btn-primary btn-sm" type="submit">+ Add Domain</button>
  </form>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>Domain</th><th>TXT Verification Record</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($domains as $d): ?>
        <tr>
          <td><strong><?= e($d['domain']) ?></strong> <?= $d['is_primary'] ? '<span class="badge badge-muted">Primary</span>' : '' ?></td>
          <td><code><?= e($d['verification_token']) ?></code></td>
          <td><span class="badge <?= $d['is_verified'] ? 'badge-success' : 'badge-warning' ?>"><?= $d['is_verified'] ? 'Verified' : 'Pending DNS' ?></span></td>
          <td>
            <?php if (!$d['is_verified']): ?>
              <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="domain_id" value="<?= (int) $d['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Verify DNS</button></form>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
