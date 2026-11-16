<?php
declare(strict_types=1);

$pageTitle = 'Mass Order';
$massResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $res = OrderService::placeMassOrder((int) $user['id'], (string) ($_POST['lines'] ?? ''));
    $massResults = $res;
    if ($res['success']) {
        Session::flash('success', "Processed mass order #{$res['mass_order_id']}: {$res['success_count']} succeeded, {$res['fail_count']} failed.");
    } else {
        Session::flash('error', $res['error'] ?? 'All lines failed validation.');
    }
}

$history = Database::fetchAll('SELECT * FROM mass_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 15', [(int) $user['id']]);
$servicesSample = Database::fetchAll('SELECT id, selling_price FROM services WHERE is_active = 1');

require __DIR__ . '/layouts/header.php';
?>

<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Mass Order</h1>
      <p class="page-banner-sub">Bulk-submit up to 100 orders at once &bull; Displaying amounts in <?= e($currencyCtx['effective_currency']) ?></p>
    </div>
  </div>
  <a href="/user/index.php?page=new-order" class="view-all-pill">Single Order &rarr;</a>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:8px">Bulk Order Lines</h2>
  <p style="color:var(--muted);font-size:13.5px;margin:0 0 16px">Submit one order per line using the format: <code>service_id | link | quantity</code></p>

  <form method="post" id="massOrderForm">
    <?= csrf_field() ?>
    <div class="form-group">
      <label class="form-label">Order Lines (Max 100 per submission)</label>
      <textarea id="massLines" name="lines" class="form-control" rows="7" placeholder="12 | https://instagram.com/example | 1000&#10;15 | https://youtube.com/watch?v=abc | 500" required><?= e($_POST['lines'] ?? '') ?></textarea>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
      <div id="massPreview" style="font-weight:800;color:var(--primary);font-size:15px">Estimated Total: <?= e(format_money('0', $displayCurrency)) ?></div>
      <button type="submit" class="btn btn-primary">Place Mass Order</button>
    </div>
  </form>

  <?php if ($massResults && !empty($massResults['results'])): ?>
    <div style="margin-top:18px" class="table-responsive">
      <table class="data-table">
        <thead><tr><th>Line</th><th>Status</th><th>Order ID / Error</th><th>Charge</th></tr></thead>
        <tbody>
        <?php foreach ($massResults['results'] as $r): ?>
          <tr>
            <td>#<?= (int) $r['line'] ?></td>
            <td><span class="badge <?= $r['success'] ? 'badge-success' : 'badge-danger' ?>"><?= $r['success'] ? 'Placed' : 'Failed' ?></span></td>
            <td><?= $r['success'] ? ('Order #' . (int) $r['order_id']) : e($r['error']) ?></td>
            <td><?= $r['success'] ? e(format_money((string) $r['charge'], $displayCurrency)) : '-' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2 class="section-title" style="margin-bottom:12px">Mass Order History</h2>
  <div class="table-responsive">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Lines</th><th>Succeeded</th><th>Failed</th><th>Total Charge</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (empty($history)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted)">No mass orders yet.</td></tr>
      <?php else: foreach ($history as $h): ?>
        <tr>
          <td><strong>#<?= (int) $h['id'] ?></strong></td>
          <td><?= (int) $h['total_lines'] ?></td>
          <td style="color:#16A36A;font-weight:700"><?= (int) $h['success_count'] ?></td>
          <td style="color:#DC2626;font-weight:700"><?= (int) $h['fail_count'] ?></td>
          <td style="font-weight:700"><?= e(format_money((string) $h['total_charge'], $displayCurrency)) ?></td>
          <td><?= e($h['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function(){
  const svcPrices = <?= json_encode(array_column($servicesSample, 'selling_price', 'id')) ?>;
  const textarea = document.getElementById('massLines');
  const preview = document.getElementById('massPreview');
  if (!textarea || !preview) return;
  const rate = <?= json_encode((float) ($currencyCtx['rate'] ?? '1.0')) ?>;
  const sym = <?= json_encode($currencyCtx['meta']['symbol']) ?>;
  const userDiscountPct = <?= json_encode((float) ($user['custom_rate_percent'] ?? 0)) ?>;

  function calc() {
    let total = 0;
    textarea.value.split(/\r?\n/).forEach(line => {
      const p = line.split('|').map(x => x.trim());
      if (p.length >= 3 && svcPrices[p[0]] && !isNaN(p[2])) {
        const effPrice = parseFloat(svcPrices[p[0]]) * (1 - (userDiscountPct / 100));
        total += (effPrice * parseInt(p[2], 10)) / 1000;
      }
    });
    preview.textContent = 'Estimated Total: ' + sym + (total * rate).toFixed(4);
  }
  textarea.addEventListener('input', calc);
  calc();
})();
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
