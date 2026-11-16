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
<div class="grid-2" style="align-items:start">
  <div class="card">
    <h1 class="section-title" style="margin-bottom:14px">Open Support Ticket</h1>
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
    <h2 class="section-title" style="margin-bottom:14px">My Tickets</h2>
    <div class="table-responsive">
      <table class="data-table" style="min-width:420px">
        <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="4" style="text-align:center;color:#777083">No support tickets yet.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td>#<?= (int) $t['id'] ?></td>
            <td><a href="/user/index.php?page=ticket-details&id=<?= (int) $t['id'] ?>" style="color:#7045D8;font-weight:700"><?= e($t['subject']) ?></a></td>
            <td><span class="badge <?= e(status_badge_class($t['status'])) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
            <td><?= e(time_ago($t['updated_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
