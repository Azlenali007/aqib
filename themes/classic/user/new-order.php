<?php
declare(strict_types=1);

$pageTitle = 'New Order';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $sid = (int) ($_POST['service_id'] ?? 0);
    $link = trim((string) ($_POST['link'] ?? ''));
    $qty = (int) ($_POST['quantity'] ?? 0);

    $res = OrderService::placeOrder((int) $user['id'], $sid, $link, $qty, 'web');
    if ($res['success']) {
        Session::flash('success', "Order #{$res['order_id']} placed successfully! Charged: " . format_money($res['charge'], $displayCurrency));
        Response::redirect('/user/index.php?page=order-details&id=' . $res['order_id']);
    }
    Session::flash('error', $res['error']);
    Response::redirect('/user/index.php?page=new-order&service_id=' . $sid);
}

$categories = CategoryManager::allActive();
$services = Database::fetchAll(
    'SELECT s.*, c.name AS category_name, c.platform AS category_platform
     FROM services s
     INNER JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1 AND c.is_active = 1
     ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC'
);

$favIds = array_column(
    Database::fetchAll('SELECT service_id FROM user_favourites WHERE user_id = ?', [(int) $user['id']]),
    'service_id'
);
$favIds = array_map('intval', $favIds);

$recentServices = Database::fetchAll(
    'SELECT DISTINCT s.id, s.name, s.platform, s.category_id
     FROM orders o INNER JOIN services s ON s.id = o.service_id
     WHERE o.user_id = ? AND s.is_active = 1 ORDER BY o.created_at DESC LIMIT 6',
    [(int) $user['id']]
);

$selectedCatId = (int) ($_GET['category_id'] ?? 0);
$selectedSvcId = (int) ($_GET['service_id'] ?? 0);
if ($selectedSvcId > 0 && $selectedCatId === 0) {
    foreach ($services as $s) {
        if ((int) $s['id'] === $selectedSvcId) {
            $selectedCatId = (int) $s['category_id'];
            break;
        }
    }
}

$rateInfo = Currency::getExchangeRate(Currency::systemBase(), $displayCurrency);
$displayRate = $rateInfo ? (float) $rateInfo['rate'] : 1.0;
$dispMeta = Currency::getCurrencyMeta($displayCurrency);

$servicesJson = [];
foreach ($services as $s) {
    $servicesJson[] = [
        'id' => (int) $s['id'],
        'category_id' => (int) $s['category_id'],
        'name' => $s['name'],
        'platform' => $s['platform'],
        'description' => $s['description'],
        'min_qty' => (int) $s['min_qty'],
        'max_qty' => (int) $s['max_qty'],
        'selling_price' => (float) $s['selling_price'],
        'refill' => (bool) $s['refill_supported'],
        'cancel' => (bool) $s['cancel_supported'],
        'is_fav' => in_array((int) $s['id'], $favIds, true),
    ];
}

require __DIR__ . '/layouts/header.php';
?>

<div class="grid-2" style="grid-template-columns: 1.35fr 1fr; align-items:start">
  <div class="card">
    <div class="section-head">
      <h1 class="section-title">Place New Order</h1>
      <span class="badge badge-processing">Balance: <?= e(format_money((string) ($user['balance'] ?? '0'), $displayCurrency)) ?></span>
    </div>

    <?php if (empty($services)): ?>
      <div class="alert alert-info">No active services are currently available. Please check back later or contact support.</div>
    <?php else: ?>
      <form method="post" id="newOrderForm">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label" for="svcSearch">Search Services</label>
          <input id="svcSearch" class="form-control" type="search" placeholder="Search by ID, name, or platform (e.g. Instagram Followers)...">
        </div>

        <div class="form-group">
          <label class="form-label" for="categorySelect">Category</label>
          <div style="display:flex;align-items:center;gap:10px">
            <span id="catIconBox" class="order-row-icon"><?= platform_icon_svg('other', 24) ?></span>
            <select id="categorySelect" class="form-select">
              <option value="0">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" data-platform="<?= e($cat['platform']) ?>" <?= $selectedCatId === (int) $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="serviceSelect">Service</label>
          <div style="display:flex;align-items:center;gap:10px">
            <span id="svcIconBox" class="order-row-icon"><?= platform_icon_svg('other', 24) ?></span>
            <select id="serviceSelect" name="service_id" class="form-select" required></select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="orderLink">Target Link</label>
          <input id="orderLink" name="link" class="form-control" placeholder="https://instagram.com/username or post URL" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label" for="orderQty">Quantity</label>
            <input id="orderQty" name="quantity" type="number" class="form-control" min="1" required>
            <small id="qtyHint" style="color:#777083;font-size:12px">Min: - | Max: -</small>
          </div>
          <div class="form-group">
            <label class="form-label">Estimated Charge</label>
            <div id="chargePreview" style="padding:11px 14px;border-radius:13px;background:#F3EEFD;color:#4D28B5;font-weight:800;font-size:16px">
              <?= e($dispMeta['symbol']) ?>0.00
            </div>
            <small id="ratePer1kHint" style="color:#777083;font-size:12px">Price per 1,000: -</small>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">Submit Order</button>
      </form>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <h2 class="section-title" style="margin-bottom:10px">Service Details</h2>
      <div id="svcMetaBox" style="font-size:13.5px;color:#777083">
        <p style="margin:0 0 8px"><strong>Service ID:</strong> <span id="metaSvcId">-</span></p>
        <p style="margin:0 0 8px"><strong>Refill / Cancel:</strong> <span id="metaFlags">-</span></p>
        <div id="metaDesc" style="background:#FAF8FE;border:1px solid #EFEAF9;border-radius:13px;padding:12px;white-space:pre-line">Select a service to read delivery details and guidelines.</div>
      </div>
    </div>

    <?php if (!empty($recentServices)): ?>
      <div class="card">
        <h2 class="section-title" style="margin-bottom:10px">Recently Used Services</h2>
        <?php foreach ($recentServices as $rs): ?>
          <a href="/user/index.php?page=new-order&service_id=<?= (int) $rs['id'] ?>" class="order-row-card" style="padding:9px 11px">
            <div class="order-row-left">
              <?= platform_icon_svg((string) $rs['platform'], 22) ?>
              <span style="font-size:13px;font-weight:700"><?= e($rs['name']) ?></span>
            </div>
            <span style="font-size:12px;color:#7045D8;font-weight:700">Select</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
@media(max-width:900px){ .grid-2[style*="1.35fr"]{ grid-template-columns:1fr !important; } }
</style>

<script>
(function(){
  const services = <?= json_encode($servicesJson, JSON_UNESCAPED_UNICODE) ?>;
  const displayRate = <?= json_encode($displayRate) ?>;
  const currencySymbol = <?= json_encode($dispMeta['symbol']) ?>;
  const userDiscountPct = <?= json_encode((float) ($user['custom_rate_percent'] ?? 0)) ?>;
  const preselectSvcId = <?= (int) $selectedSvcId ?>;

  const platformIcons = {
    instagram: <?= json_encode(platform_icon_svg('instagram', 24)) ?>,
    youtube: <?= json_encode(platform_icon_svg('youtube', 24)) ?>,
    facebook: <?= json_encode(platform_icon_svg('facebook', 24)) ?>,
    telegram: <?= json_encode(platform_icon_svg('telegram', 24)) ?>,
    tiktok: <?= json_encode(platform_icon_svg('tiktok', 24)) ?>,
    twitter: <?= json_encode(platform_icon_svg('twitter', 24)) ?>,
    linkedin: <?= json_encode(platform_icon_svg('linkedin', 24)) ?>,
    spotify: <?= json_encode(platform_icon_svg('spotify', 24)) ?>,
    other: <?= json_encode(platform_icon_svg('other', 24)) ?>
  };

  const catSel = document.getElementById('categorySelect');
  const svcSel = document.getElementById('serviceSelect');
  const searchInp = document.getElementById('svcSearch');
  const qtyInp = document.getElementById('orderQty');
  if (!catSel || !svcSel) return;

  function renderServices() {
    const catId = parseInt(catSel.value, 10) || 0;
    const q = (searchInp.value || '').trim().toLowerCase();
    const catOpt = catSel.options[catSel.selectedIndex];
    const catPlat = catOpt ? (catOpt.getAttribute('data-platform') || 'other') : 'other';
    document.getElementById('catIconBox').innerHTML = platformIcons[catPlat] || platformIcons.other;

    const filtered = services.filter(s => {
      if (catId > 0 && s.category_id !== catId) return false;
      if (q && !s.name.toLowerCase().includes(q) && !String(s.id).includes(q) && !s.platform.toLowerCase().includes(q)) return false;
      return true;
    });

    svcSel.innerHTML = '';
    if (filtered.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No matching services';
      svcSel.appendChild(opt);
      updateServiceDetails();
      return;
    }

    filtered.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      const dispPer1k = (s.selling_price * displayRate).toFixed(2);
      opt.textContent = `#${s.id} - ${s.name} (${currencySymbol}${dispPer1k}/1K)`;
      if (s.id === preselectSvcId) opt.selected = true;
      svcSel.appendChild(opt);
    });
    updateServiceDetails();
  }

  function updateServiceDetails() {
    const sid = parseInt(svcSel.value, 10);
    const s = services.find(x => x.id === sid);
    if (!s) {
      document.getElementById('metaSvcId').textContent = '-';
      document.getElementById('metaFlags').textContent = '-';
      document.getElementById('metaDesc').textContent = 'No service selected.';
      return;
    }
    document.getElementById('svcIconBox').innerHTML = platformIcons[s.platform] || platformIcons.other;
    document.getElementById('metaSvcId').textContent = '#' + s.id;
    document.getElementById('metaFlags').textContent = (s.refill ? 'Refill Supported' : 'No Refill') + ' • ' + (s.cancel ? 'Cancel Supported' : 'No Cancel');
    document.getElementById('metaDesc').textContent = s.description || 'Standard automated delivery.';
    qtyInp.min = s.min_qty;
    qtyInp.max = s.max_qty;
    if (!qtyInp.value || parseInt(qtyInp.value, 10) < s.min_qty) {
      qtyInp.value = s.min_qty;
    }
    document.getElementById('qtyHint').textContent = `Min: ${s.min_qty.toLocaleString()} | Max: ${s.max_qty.toLocaleString()}`;
    updateCharge();
  }

  function updateCharge() {
    const sid = parseInt(svcSel.value, 10);
    const s = services.find(x => x.id === sid);
    const qty = parseInt(qtyInp.value, 10) || 0;
    if (!s) return;
    const effPrice = s.selling_price * (1 - (userDiscountPct / 100));
    const chargeBase = (effPrice * qty) / 1000;
    const chargeDisp = chargeBase * displayRate;
    const per1kDisp = effPrice * displayRate;
    document.getElementById('chargePreview').textContent = currencySymbol + chargeDisp.toFixed(4);
    document.getElementById('ratePer1kHint').textContent = `Price per 1,000: ${currencySymbol}${per1kDisp.toFixed(2)}`;
  }

  catSel.addEventListener('change', renderServices);
  searchInp.addEventListener('input', renderServices);
  svcSel.addEventListener('change', updateServiceDetails);
  qtyInp.addEventListener('input', updateCharge);
  renderServices();
})();
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
