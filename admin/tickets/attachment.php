<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
AdminAuth::requireAuth('tickets.view');

$msgId = (int) ($_GET['msg_id'] ?? 0);
$msg = Database::fetchOne('SELECT attachment_path FROM ticket_messages WHERE id = ? LIMIT 1', [$msgId]);
if (!$msg || empty($msg['attachment_path'])) {
    http_response_code(404);
    exit('Attachment not found.');
}

$rel = ltrim(str_replace(['..', '\\'], ['', '/'], (string) $msg['attachment_path']), '/');
$full = realpath(SMM_STORAGE . '/' . $rel);
$base = realpath(SMM_STORAGE . '/uploads/tickets');
if (!$full || !$base || !str_starts_with($full, $base) || !is_file($full)) {
    http_response_code(404);
    exit('Attachment missing on disk.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($full) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($full) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
