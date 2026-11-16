<?php
declare(strict_types=1);

$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = Database::fetchOne('SELECT * FROM tickets WHERE id = ? AND user_id = ? LIMIT 1', [$ticketId, (int) $user['id']]);
if (!$ticket) {
    Session::flash('error', 'Ticket not found.');
    Response::redirect('/user/index.php?page=tickets');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (($_POST['action'] ?? '') === 'close') {
        Database::execute("UPDATE tickets SET status = 'closed' WHERE id = ? AND user_id = ?", [$ticketId, (int) $user['id']]);
        Session::flash('success', 'Ticket closed.');
    } else {
        $res = TicketService::reply($ticketId, 'user', (int) $user['id'], (string) ($_POST['message'] ?? ''), $_FILES['attachment'] ?? null);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Reply posted.' : $res['error']);
    }
    Response::redirect('/user/index.php?page=ticket-details&id=' . $ticketId);
}

$messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC', [$ticketId]);
$pageTitle = "Ticket #$ticketId";
require __DIR__ . '/layouts/header.php';
?>
<div class="page-banner">
  <div class="page-banner-left">
    <div class="page-banner-icon" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div>
      <h1 class="page-banner-title">#<?= (int) $ticket['id'] ?> — <?= e($ticket['subject']) ?></h1>
      <p class="page-banner-sub">Category: <?= e(ucfirst($ticket['category'])) ?> &bull; Priority: <?= e(ucfirst($ticket['priority'])) ?></p>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <span class="badge <?= e(status_badge_class($ticket['status'])) ?>"><?= e(ucfirst($ticket['status'])) ?></span>
    <a href="/user/index.php?page=tickets" class="view-all-pill">&larr; All Tickets</a>
  </div>
</div>

<div class="card">
  <?php if ($ticket['status'] !== 'closed'): ?>
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="close"><button class="btn btn-outline btn-sm" type="submit">Close Ticket</button></form>
    </div>
  <?php endif; ?>

  <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
    <?php foreach ($messages as $m): $isUser = $m['sender_type'] === 'user'; ?>
      <div style="padding:14px;border-radius:16px;background:<?= $isUser ? '#F3EEFD' : '#FAF8FE' ?>;border:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px">
          <strong style="color:<?= $isUser ? 'var(--primary)' : 'var(--text)' ?>"><?= $isUser ? e($user['username']) : 'Support Team' ?></strong>
          <span><?= e($m['created_at']) ?></span>
        </div>
        <div style="font-size:14px;white-space:pre-line"><?= e($m['message']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($ticket['status'] !== 'closed'): ?>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group"><label class="form-label">Reply</label><textarea class="form-control" name="message" required></textarea></div>
      <div class="form-group"><label class="form-label">Attachment (Optional)</label><input class="form-control" type="file" name="attachment"></div>
      <button class="btn btn-primary" type="submit">Send Reply</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/layouts/footer.php'; ?>
