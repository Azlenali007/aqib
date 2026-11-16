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
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Custom Domains &amp; DNS</h1>
      <p class="page-banner-sub"><?= e($cpPanel['site_name']) ?> &mdash; Connect domains and verify DNS TXT records.</p>
    </div>
  </div>
</div>
<?php render_child_panel_tabs((int) $cpPanel['id'], 'child-panels/domains'); ?>
<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Add Custom Domain</h2>
  <form method="post" class="filter-bar">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_domain">
    <input class="form-control" style="max-width:320px" name="domain" placeholder="custom.yourdomain.com" required>
    <button class="btn btn-primary btn-sm" type="submit">+ Add Domain</button>
  </form>
  <div class="desktop-table-wrap">
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
              <?php else: ?>&mdash;<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="mobile-cards-list">
    <?php foreach ($domains as $d): ?>
      <div class="order-row-card" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          <strong><?= e($d['domain']) ?></strong>
          <span class="badge <?= $d['is_verified'] ? 'badge-success' : 'badge-warning' ?>"><?= $d['is_verified'] ? 'Verified' : 'Pending DNS' ?></span>
        </div>
        <div style="font-size:12px;color:var(--muted)">TXT: <code><?= e($d['verification_token']) ?></code></div>
        <?php if (!$d['is_verified']): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="domain_id" value="<?= (int) $d['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Verify DNS</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
