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
<div class="card">
  <div class="section-head">
    <div>
      <h1 class="section-title">#<?= (int) $ticket['id'] ?> — <?= e($ticket['subject']) ?></h1>
      <div style="font-size:12.5px;color:#777083">Category: <?= e(ucfirst($ticket['category'])) ?> &bull; Priority: <?= e(ucfirst($ticket['priority'])) ?></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <span class="badge <?= e(status_badge_class($ticket['status'])) ?>"><?= e(ucfirst($ticket['status'])) ?></span>
      <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="close"><button class="btn btn-outline btn-sm" type="submit">Close Ticket</button></form>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
    <?php foreach ($messages as $m): $isUser = $m['sender_type'] === 'user'; ?>
      <div style="padding:14px;border-radius:16px;background:<?= $isUser ? '#F3EEFD' : '#FAF8FE' ?>;border:1px solid #EAE5F1">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#777083;margin-bottom:6px">
          <strong><?= $isUser ? e($user['username']) : 'Support Team' ?></strong>
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
