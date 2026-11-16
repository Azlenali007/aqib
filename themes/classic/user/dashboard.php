<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$stats = UserService::getStats((int) $user['id']);

$recentOrders = Database::fetchAll(
    'SELECT o.*, s.name AS service_name, s.platform
     FROM orders o
     INNER JOIN services s ON s.id = o.service_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC LIMIT 5',
    [(int) $user['id']]
);

$recentTransactions = Database::fetchAll(
    'SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
    [(int) $user['id']]
);

$announcements = Database::fetchAll(
    "SELECT * FROM announcements WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY created_at DESC LIMIT 3"
);

// Popular platforms fetched dynamically with minimum active service price
$popularPlatforms = ['instagram' => 'Followers', 'youtube' => 'Subscribers', 'facebook' => 'Likes', 'telegram' => 'Members'];
$popularCards = [];
foreach ($popularPlatforms as $platKey => $subLabel) {
    $minPrice = Database::fetchValue(
        'SELECT MIN(selling_price) FROM services WHERE is_active = 1 AND platform = ?',
        [$platKey]
    );
    $catId = Database::fetchValue(
        'SELECT id FROM categories WHERE is_active = 1 AND platform = ? ORDER BY sort_order ASC LIMIT 1',
        [$platKey]
    );
    $popularCards[] = [
        'platform' => $platKey,
        'title' => ucfirst($platKey),
        'subtitle' => $subLabel,
        'min_price' => $minPrice,
        'category_id' => $catId,
    ];
}

require __DIR__ . '/layouts/header.php';
?>

<!-- Hero Row: Welcome Card + Wallet Balance Card (Matching Reference Image) -->
<div class="hero-grid">
  <div class="welcome-card">
    <div class="welcome-left">
      <div class="welcome-wave" aria-hidden="true">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
          <path d="M18.5 11.5V6.5a1.5 1.5 0 0 0-3 0v4M15.5 10.5V4.5a1.5 1.5 0 0 0-3 0v6M12.5 10.5V5.5a1.5 1.5 0 0 0-3 0v7.5L7.6 11.1a1.5 1.5 0 0 0-2.1 2.1l4.4 5.1A6 6 0 0 0 14.5 20.5h.5a5.5 5.5 0 0 0 5.5-5.5v-3.5a1.5 1.5 0 0 0-2 0z" fill="#FBBF24" stroke="#D97706" stroke-width="1.2"/>
        </svg>
      </div>
      <div style="min-width:0">
        <p class="welcome-sub">Welcome back,</p>
        <div class="welcome-name">
          <span><?= e($user['username']) ?></span>
          <?php if ((int) $user['is_verified'] === 1): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" aria-label="Verified Account">
              <path d="M22.5 12.5c0-1.58-.88-2.95-2.15-3.6.15-.44.23-.92.23-1.42 0-2.21-1.79-4-4-4-.5 0-.98.08-1.42.23C14.51 2.44 13.14 1.56 11.56 1.56c-1.58 0-2.95.88-3.6 2.15-.44-.15-.92-.23-1.42-.23-2.21 0-4 1.79-4 4 0 .5.08.98.23 1.42C1.5 9.55.62 10.92.62 12.5c0 1.58.88 2.95 2.15 3.6-.15.44-.23.92-.23 1.42 0 2.21 1.79 4 4 4 .5 0 .98-.08 1.42-.23.65 1.27 2.02 2.15 3.6 2.15 1.58 0 2.95-.88 3.6-2.15.44.15.92.23 1.42.23 2.21 0 4-1.79 4-4 0-.5-.08-.98-.23-1.42 1.27-.65 2.15-2.02 2.15-3.6z" fill="#1D9BF0"/>
              <path d="M10.2 16.5l-3.7-3.7 1.4-1.4 2.3 2.3 5.4-5.4 1.4 1.4-6.8 6.8z" fill="#fff"/>
            </svg>
          <?php endif; ?>
        </div>
        <p class="welcome-id">User ID: #<?= (int) $user['id'] ?></p>
      </div>
    </div>
    <div class="welcome-art" aria-hidden="true">
      <svg width="102" height="72" viewBox="0 0 110 76" fill="none">
        <defs>
          <linearGradient id="barGradA" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#5B42F3"/></linearGradient>
          <linearGradient id="barGradB" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#60A5FA"/><stop offset="100%" stop-color="#3B82F6"/></linearGradient>
        </defs>
        <rect x="8" y="48" width="16" height="22" rx="4" fill="url(#barGradA)" opacity="0.88"/>
        <rect x="31" y="38" width="16" height="32" rx="4" fill="url(#barGradB)"/>
        <rect x="54" y="28" width="16" height="42" rx="4" fill="url(#barGradB)"/>
        <rect x="77" y="16" width="18" height="54" rx="4" fill="url(#barGradA)"/>
        <path d="M10 38C34 32 60 22 92 6" stroke="#6D42E6" stroke-width="3.2" stroke-linecap="round"/>
        <path d="M84 5L94 5.5L92 15" stroke="#6D42E6" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>

  <div class="wallet-card">
    <div class="wallet-left">
      <div class="wallet-icon-box" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M16 12h4v2h-4a1 1 0 0 1 0-2z" fill="#fff"/></svg>
      </div>
      <div style="min-width:0">
        <div class="wallet-label">Your Balance</div>
        <div class="wallet-amount"><?= e(format_money((string) ($user['balance'] ?? '0.00'), $displayCurrency)) ?></div>
        <span class="wallet-status-pill">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          <?= $user['status'] === 'active' ? 'Active Account' : ucfirst($user['status']) ?>
        </span>
      </div>
    </div>
    <div class="wallet-right">
      <div class="wallet-divider"></div>
      <a href="/user/index.php?page=add-funds" class="add-funds-pill">
        <span>+ Add Funds</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>
</div>

<!-- Quick Actions 2x2 / 4-col Grid -->
<div class="quick-grid">
  <a href="/user/index.php?page=new-order" class="quick-card">
    <div class="quick-card-left">
      <div class="quick-icon qi-purple">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 14.8l.1-.1L8.1 13h7.4c.75 0 1.4-.4 1.75-1.03L21 5H5.2L4.27 3H1v2h2l3.6 7.6-1.35 2.45C4.52 16.37 5.48 18 7 18h12v-2H7.4c-.14 0-.25-.11-.2-.2z"/></svg>
      </div>
      <div style="min-width:0"><p class="quick-title">New Order</p><p class="quick-sub">Place Order Now</p></div>
    </div>
    <span class="chevron-circle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
  </a>

  <a href="/user/index.php?page=services" class="quick-card">
    <div class="quick-card-left">
      <div class="quick-icon qi-blue">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
      </div>
      <div style="min-width:0"><p class="quick-title">Services</p><p class="quick-sub">View All Services</p></div>
    </div>
    <span class="chevron-circle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
  </a>

  <a href="/user/index.php?page=add-funds" class="quick-card">
    <div class="quick-card-left">
      <div class="quick-icon qi-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M21 7H3V5h18v2zm0 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h18zm-4 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg>
      </div>
      <div style="min-width:0"><p class="quick-title">Add Funds</p><p class="quick-sub">Recharge Wallet</p></div>
    </div>
    <span class="chevron-circle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
  </a>

  <a href="/user/index.php?page=orders" class="quick-card">
    <div class="quick-card-left">
      <div class="quick-icon qi-orange">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-3 14H8v-2h8v2zm0-4H8v-2h8v2zm0-4H8V7h8v2z"/></svg>
      </div>
      <div style="min-width:0"><p class="quick-title">Orders</p><p class="quick-sub">Check Your Orders</p></div>
    </div>
    <span class="chevron-circle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></span>
  </a>
</div>

<!-- Popular Services Section -->
<div class="card">
  <div class="section-head">
    <h2 class="section-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2C12 2 17 6.5 17 12.2C17 15.8 14.8 19 12 20.5C9.2 19 7 15.8 7 12.2C7 9.8 8.3 7.5 9.5 6C9.5 8 10.6 9.2 11.4 9.5C11.1 7 12 2 12 2Z" fill="#F97316"/><path d="M12 10.5C12 10.5 14.5 12.6 14.5 15C14.5 16.8 13.4 18.2 12 19C10.6 18.2 9.5 16.8 9.5 15C9.5 13.4 10.5 12 12 10.5Z" fill="#FACC15"/></svg>
      Popular Services
    </h2>
    <a href="/user/index.php?page=services" class="view-all-pill">View All &rarr;</a>
  </div>
  <div class="popular-grid">
    <?php foreach ($popularCards as $pc): ?>
      <a href="/user/index.php?page=new-order<?= $pc['category_id'] ? '&category_id=' . (int) $pc['category_id'] : '' ?>" class="popular-item">
        <div class="popular-icon-wrap">
          <?= platform_icon_svg($pc['platform'], 28) ?>
        </div>
        <div class="popular-name"><?= e($pc['title']) ?></div>
        <div class="popular-sub"><?= e($pc['subtitle']) ?></div>
        <span class="price-pill">
          <?= $pc['min_price'] !== null ? 'From ' . e(format_money((string) $pc['min_price'], $displayCurrency)) : 'Explore' ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Real User Order Statistics KPIs -->
<div class="kpi-grid">
  <div class="kpi-box"><div class="kpi-label">Total Orders</div><div class="kpi-val"><?= number_format($stats['total_orders']) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Completed</div><div class="kpi-val" style="color:#16A36A"><?= number_format($stats['completed_orders']) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Pending / Processing</div><div class="kpi-val" style="color:#7045D8"><?= number_format($stats['pending_orders'] + $stats['processing_orders']) ?></div></div>
  <div class="kpi-box"><div class="kpi-label">Total Spending</div><div class="kpi-val"><?= e(format_money($stats['total_spending'], $displayCurrency)) ?></div></div>
</div>

<!-- Recent Orders Section -->
<div class="card">
  <div class="section-head">
    <h2 class="section-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B42F3" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>
      Recent Orders
    </h2>
    <a href="/user/index.php?page=orders" class="view-all-pill">View All &rarr;</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <div style="text-align:center;padding:24px 12px;color:#777083">
      <p style="margin:0 0 12px">You haven't placed any orders yet.</p>
      <a href="/user/index.php?page=new-order" class="btn btn-primary btn-sm">Place Your First Order</a>
    </div>
  <?php else: ?>
    <?php foreach ($recentOrders as $ord): ?>
      <?php require __DIR__ . '/components/order-card.php'; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Desktop Secondary Row: Recent Transactions & Announcements -->
<div class="grid-2">
  <div class="card">
    <div class="section-head">
      <h2 class="section-title">Recent Transactions</h2>
      <a href="/user/index.php?page=transactions" class="view-all-pill">View All &rarr;</a>
    </div>
    <?php if (empty($recentTransactions)): ?>
      <p style="color:#777083;font-size:13.5px;margin:0">No wallet transactions recorded yet.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table" style="min-width:380px">
          <thead><tr><th>Type</th><th>Description</th><th>Amount</th></tr></thead>
          <tbody>
          <?php foreach ($recentTransactions as $tx): ?>
            <tr>
              <td><span class="badge <?= in_array($tx['type'], ['credit','refund','referral'], true) ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($tx['type'])) ?></span></td>
              <td style="font-size:12.5px"><?= e($tx['description']) ?></td>
              <td style="font-weight:700"><?= e(format_money((string) $tx['amount'], $displayCurrency)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="section-head">
      <h2 class="section-title">Announcements</h2>
      <a href="/user/index.php?page=new-order" class="view-all-pill">Quick Order &rarr;</a>
    </div>
    <?php if (empty($announcements)): ?>
      <p style="color:#777083;font-size:13.5px;margin:0">No active system announcements right now.</p>
    <?php else: ?>
      <?php foreach ($announcements as $ann): ?>
        <div style="padding:12px;border-radius:14px;background:#FAF8FE;border:1px solid #EFEAF9;margin-bottom:10px">
          <div style="font-weight:700;font-size:14px;color:#211A2D"><?= e($ann['title']) ?></div>
          <div style="font-size:13px;color:#777083;margin-top:4px"><?= nl2br(e($ann['content'])) ?></div>
          <div style="font-size:11px;color:#9B94A8;margin-top:4px"><?= e(time_ago($ann['created_at'])) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
