<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('tickets.view');
$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = Database::fetchOne(
    'SELECT t.*, u.username, u.email FROM tickets t INNER JOIN users u ON u.id = t.user_id WHERE t.id = ? LIMIT 1',
    [$ticketId]
);
if (!$ticket) {
    Session::flash('error', 'Ticket not found.');
    Response::redirect('/admin/tickets/index.php');
}
$adminTitle = "Ticket #$ticketId — " . $ticket['subject'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    if (!AdminAuth::can('tickets.reply', $admin)) {
        Session::flash('error', 'Missing tickets.reply permission.');
        Response::redirect('/admin/tickets/view.php?id=' . $ticketId);
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'reply') {
        $res = TicketService::reply($ticketId, 'admin', (int) $admin['id'], (string) ($_POST['message'] ?? ''), $_FILES['attachment'] ?? null);
        Session::flash($res['success'] ? 'success' : 'error', $res['success'] ? 'Reply posted and user notified.' : $res['error']);
    } elseif ($action === 'update_meta') {
        $assigned = (int) ($_POST['assigned_admin_id'] ?? 0);
        $prio = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent'], true) ? $_POST['priority'] : $ticket['priority'];
        $stat = in_array($_POST['status'] ?? '', ['open', 'pending', 'answered', 'closed'], true) ? $_POST['status'] : $ticket['status'];
        Database::execute(
            'UPDATE tickets SET assigned_admin_id = ?, priority = ?, status = ? WHERE id = ?',
            [$assigned > 0 ? $assigned : null, $prio, $stat, $ticketId]
        );
        Session::flash('success', 'Ticket properties updated.');
    }
    Response::redirect('/admin/tickets/view.php?id=' . $ticketId);
}

$messages = Database::fetchAll('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY id ASC', [$ticketId]);
$adminsList = Database::fetchAll('SELECT id, username, full_name FROM admins WHERE status = "active" ORDER BY username ASC');

require dirname(__DIR__) . '/layouts/header.php';
?>
<div style="margin-bottom:14px"><a class="btn btn-outline btn-sm" href="/admin/tickets/index.php">&larr; Back to Tickets</a></div>

<div class="grid-2" style="grid-template-columns:1.5fr 1fr">
  <div class="adm-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div>
        <h2 style="margin:0;font-size:17px"><?= e($ticket['subject']) ?></h2>
        <div style="font-size:12.5px;color:#777083">From: <a href="/admin/users/view.php?id=<?= (int) $ticket['user_id'] ?>"><strong><?= e($ticket['username']) ?></strong></a> (<?= e($ticket['email']) ?>) • <?= e(ucfirst($ticket['category'])) ?></div>
      </div>
      <span class="badge <?= e(status_badge_class($ticket['status'])) ?>"><?= e(ucfirst($ticket['status'])) ?></span>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
      <?php foreach ($messages as $m): $isAdm = $m['sender_type'] === 'admin'; ?>
        <div style="padding:12px 14px;border-radius:14px;border:1px solid <?= $isAdm ? '#D8C9FB' : 'var(--b)' ?>;background:<?= $isAdm ? '#F7F3FE' : '#FAF9FD' ?>">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:#777083;margin-bottom:6px">
            <strong><?= $isAdm ? 'Support Staff' : e($ticket['username']) ?></strong>
            <span><?= e($m['created_at']) ?></span>
          </div>
          <div style="white-space:pre-wrap;font-size:13.5px"><?= e($m['message']) ?></div>
          <?php if (!empty($m['attachment_path'])): ?>
            <div style="margin-top:8px"><a class="btn btn-outline btn-sm" target="_blank" href="/admin/tickets/attachment.php?msg_id=<?= (int) $m['id'] ?>">View Attachment</a></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (AdminAuth::can('tickets.reply', $admin)): ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reply">
        <div class="form-group"><label class="form-label">Reply Message</label><textarea class="form-control" rows="4" name="message" placeholder="Write helpful reply to user..." required></textarea></div>
        <div class="form-group"><label class="form-label">Attachment (Optional JPG/PNG/PDF)</label><input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf"></div>
        <button class="btn btn-primary" type="submit">Send Reply</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="adm-card" style="height:fit-content">
    <h2 style="margin:0 0 12px;font-size:16px">Ticket Controls</h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_meta">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <?php foreach (['open', 'pending', 'answered', 'closed'] as $st): ?>
            <option value="<?= e($st) ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Priority</label>
        <select class="form-select" name="priority">
          <?php foreach (['low', 'medium', 'high', 'urgent'] as $pr): ?>
            <option value="<?= e($pr) ?>" <?= $ticket['priority'] === $pr ? 'selected' : '' ?>><?= e(ucfirst($pr)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Admin</label>
        <select class="form-select" name="assigned_admin_id">
          <option value="0">Unassigned</option>
          <?php foreach ($adminsList as $a): ?>
            <option value="<?= (int) $a['id'] ?>" <?= (int) $ticket['assigned_admin_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['username']) ?> (<?= e($a['full_name']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-outline" type="submit">Update Ticket</button>
    </form>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
