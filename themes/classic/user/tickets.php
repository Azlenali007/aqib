<?php
declare(strict_types=1);

$pageTitle = 'Support Tickets';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $res = TicketService::create(
        (int) $user['id'],
        (string) ($_POST['category'] ?? 'general'),
        (string) ($_POST['subject'] ?? ''),
        (string) ($_POST['message'] ?? ''),
        (string) ($_POST['priority'] ?? 'medium'),
        $_FILES['attachment'] ?? null
    );
    if ($res['success']) {
        Session::flash('success', "Ticket #{$res['ticket_id']} opened.");
        Response::redirect('/user/index.php?page=ticket-details&id=' . $res['ticket_id']);
    }
    Session::flash('error', $res['error']);
    Response::redirect('/user/index.php?page=tickets');
}

$tickets = Database::fetchAll('SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC LIMIT 50', [(int) $user['id']]);
require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">Support Center</h1>
      <p class="page-banner-sub">Open a ticket for order help, billing questions, or API integration support</p>
    </div>
  </div>
</div>

<div class="grid-2" style="align-items:start">
  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">Open Support Ticket</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category" class="form-select">
            <option value="order">Order Issue</option>
            <option value="payment">Payment / Billing</option>
            <option value="api">API / Child Panel</option>
            <option value="general">General Inquiry</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Subject</label><input class="form-control" name="subject" required></div>
      <div class="form-group"><label class="form-label">Message</label><textarea class="form-control" name="message" required></textarea></div>
      <div class="form-group"><label class="form-label">Attachment (Optional)</label><input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf"></div>
      <button class="btn btn-primary" type="submit">Submit Ticket</button>
    </form>
  </div>

  <div class="card">
    <h2 class="section-title" style="margin-bottom:14px">My Tickets (<?= count($tickets) ?>)</h2>
    <div class="table-responsive desktop-table-wrap">
      <table class="data-table" style="min-width:420px">
        <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--muted)">No support tickets yet.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td><strong>#<?= (int) $t['id'] ?></strong></td>
            <td><a href="/user/index.php?page=ticket-details&id=<?= (int) $t['id'] ?>" style="color:var(--primary);font-weight:700"><?= e($t['subject']) ?></a></td>
            <td><span class="badge <?= e(status_badge_class($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
            <td><?= e(time_ago($t['updated_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mobile-cards-list">
      <?php if (empty($tickets)): ?>
        <div style="text-align:center;color:var(--muted);padding:16px">No support tickets yet.</div>
      <?php else: foreach ($tickets as $t): ?>
        <a href="/user/index.php?page=ticket-details&id=<?= (int) $t['id'] ?>" class="order-row-card">
          <div style="min-width:0">
            <div class="order-row-title">#<?= (int) $t['id'] ?> &bull; <?= e($t['subject']) ?></div>
            <div class="order-row-meta"><?= e(ucfirst($t['category'])) ?> &bull; <?= e(time_ago($t['updated_at'])) ?></div>
          </div>
          <span class="badge <?= e(status_badge_class($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
