<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('providers.manage');

$providerId = (int) ($_GET['provider_id'] ?? $_POST['provider_id'] ?? 0);
$provider = Database::fetchOne('SELECT * FROM providers WHERE id = ? LIMIT 1', [$providerId]);
if (!$provider) { Session::flash('error', 'Provider not found.'); Response::redirect('/admin/providers/index.php'); }

$margin = (string) ($_REQUEST['margin'] ?? '20');
$preview = null;
$fetchError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_import') {
    Security::requireCsrf();
    $selectedIds = $_POST['selected_ext_ids'] ?? [];
    $allPayloads = $_POST['payload_json'] ?? [];
    $itemsToImport = [];
    foreach ($selectedIds as $extId) {
        if (!empty($allPayloads[$extId])) {
            $decoded = json_decode((string) $allPayloads[$extId], true);
            if (is_array($decoded)) $itemsToImport[] = $decoded;
        }
    }
    if (empty($itemsToImport)) {
        Session::flash('error', 'Select at least one service to import.');
    } else {
        $res = ProviderService::bulkImport(
            $providerId,
            $itemsToImport,
            (int) ($_POST['category_id'] ?? 0),
            (string) ($_POST['margin'] ?? '20'),
            (string) ($_POST['duplicate_mode'] ?? 'skip'),
            !empty($_POST['enable_on_import']) ? 1 : 0,
            (int) $admin['id']
        );
        if ($res['success']) {
            $msg = "Import complete: {$res['success_count']} imported, {$res['skipped_count']} skipped duplicates, {$res['fail_count']} failed.";
            if (!empty($res['failures'])) $msg .= ' Sample error: ' . $res['failures'][0]['reason'];
            Session::flash($res['fail_count'] > 0 && $res['success_count'] === 0 ? 'error' : 'success', $msg);
        } else {
            Session::flash('error', $res['error']);
        }
        Response::redirect('/admin/providers/import.php?provider_id=' . $providerId . '&margin=' . urlencode($margin));
    }
}

$remoteRes = ProviderService::fetchRemoteServices($providerId);
if ($remoteRes['success']) {
    $preview = ProviderService::buildImportPreview($provider, $remoteRes['services'], $margin);
} else {
    $fetchError = $remoteRes['error'];
}

$categories = Database::fetchAll('SELECT * FROM categories ORDER BY sort_order ASC');
$adminTitle = 'Bulk Import — ' . $provider['name'];
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px">
    <div>
      <h2 style="margin:0;font-size:17px">Import Services from <?= e($provider['name']) ?></h2>
      <div style="font-size:12.5px;color:#777083">Provider Currency: <strong><?= e($provider['currency']) ?></strong> &rarr; System Base Currency: <strong><?= e(Currency::systemBase()) ?></strong></div>
    </div>
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="provider_id" value="<?= $providerId ?>">
      <label style="font-size:12.5px;font-weight:700">Preview Margin (%):</label>
      <input class="form-control" style="max-width:100px" type="number" step="0.5" name="margin" value="<?= e($margin) ?>">
      <button class="btn btn-outline btn-sm" type="submit">Recalculate Preview</button>
    </form>
  </div>

  <?php if ($fetchError): ?>
    <div class="alert alert-error">Unable to fetch provider services: <?= e($fetchError) ?></div>
  <?php elseif ($preview): ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="bulk_import"><input type="hidden" name="provider_id" value="<?= $providerId ?>"><input type="hidden" name="margin" value="<?= e($margin) ?>">
      <div class="bulk-bar">
        <strong id="bulkSelectedCount" style="color:#4D28B5;font-size:13px">0 selected</strong>
        <select name="category_id" class="form-select" style="max-width:220px" required>
          <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
        <select name="duplicate_mode" class="form-select" style="max-width:190px">
          <option value="skip">Duplicates: Skip Existing</option>
          <option value="update">Duplicates: Update Price &amp; Info</option>
        </select>
        <label style="font-size:13px"><input type="checkbox" name="enable_on_import" value="1" checked> Enable Immediately</label>
        <button class="btn btn-primary btn-sm" type="submit">Import Selected Services</button>
      </div>

      <div class="table-responsive">
        <table class="adm-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="bulkSelectAll"></th>
              <th>Ext ID</th>
              <th>Service Name</th>
              <th>Provider Price</th>
              <th>Exchange Rate</th>
              <th>Converted Cost (<?= e(Currency::systemBase()) ?>)</th>
              <th>Margin</th>
              <th>Final Selling Price</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($preview['items'] as $item): ?>
            <tr>
              <td>
                <?php if ($item['valid']): ?>
                  <input type="checkbox" class="bulk-row-cb" name="selected_ext_ids[]" value="<?= e($item['external_id']) ?>">
                  <input type="hidden" name="payload_json[<?= e($item['external_id']) ?>]" value="<?= e(json_encode($item)) ?>">
                <?php endif; ?>
              </td>
              <td>#<?= e($item['external_id']) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:7px">
                  <?= platform_icon_svg((string) ($item['platform'] ?? 'other'), 18) ?>
                  <div><strong><?= e($item['name']) ?></strong><div style="font-size:11px;color:#777083"><?= e($item['external_category'] ?? '') ?></div></div>
                </div>
              </td>
              <td><?= e($item['provider_price'] ?? '-') ?> <strong><?= e($item['provider_currency'] ?? '') ?></strong></td>
              <td><?= $item['exchange_rate'] !== null ? e($item['exchange_rate']) . '<div style="font-size:11px;color:#777083">' . e($item['rate_source']) . '</div>' : '<span style="color:#E5485D">Missing Rate</span>' ?></td>
              <td><?= $item['converted_cost'] !== null ? e(format_money((string) $item['converted_cost'], null, 4)) : '-' ?></td>
              <td><?= e((string) ($item['profit_margin_percent'] ?? $margin)) ?>%</td>
              <td style="font-weight:700;color:#7045D8"><?= $item['selling_price'] !== null ? e(format_money((string) $item['selling_price'], null, 4)) : '-' ?></td>
              <td>
                <?php if (!$item['valid']): ?>
                  <span class="badge badge-danger" title="<?= e($item['error']) ?>">Blocked: <?= e($item['error']) ?></span>
                <?php elseif ($item['is_duplicate']): ?>
                  <span class="badge badge-warning">Duplicate (#<?= (int) $item['existing_service_id'] ?>)</span>
                <?php else: ?>
                  <span class="badge badge-success">Ready</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
  <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
