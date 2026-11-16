<?php
declare(strict_types=1);
?>
<div class="bulk-bar">
  <strong id="bulkSelectedCount" style="color:#4D28B5;font-size:13px">0 selected</strong>
  <select name="bulk_action" id="bulkActionSelect" class="form-select" style="max-width:220px" required>
    <option value="">— Bulk Action —</option>
    <option value="enable">Enable Selected</option>
    <option value="disable">Disable Selected</option>
    <option value="change_category">Change Category</option>
    <option value="change_provider">Change Provider</option>
    <option value="change_margin">Change Profit Margin (%)</option>
    <option value="change_price">Set Fixed Selling Price</option>
    <option value="enable_refill">Enable Refill Support</option>
    <option value="disable_refill">Disable Refill Support</option>
    <option value="enable_cancel">Enable Cancel Support</option>
    <option value="disable_cancel">Disable Cancel Support</option>
    <option value="delete">Delete Selected</option>
  </select>
  <select name="category_id" class="form-select" style="max-width:190px">
    <option value="">Target Category...</option>
    <?php foreach ($categories ?? [] as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="provider_id" class="form-select" style="max-width:170px">
    <option value="">Target Provider...</option>
    <?php foreach ($providers ?? [] as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
  </select>
  <input class="form-control" style="max-width:130px" type="number" step="0.01" name="profit_margin_percent" placeholder="Margin %">
  <input class="form-control" style="max-width:130px" type="number" step="0.0001" name="selling_price" placeholder="Price">
  <button class="btn btn-primary btn-sm" type="submit" onclick="return document.getElementById('bulkActionSelect').value !== 'delete' || confirm('Delete selected services without orders?')">Apply Bulk Action</button>
</div>
