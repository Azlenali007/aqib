<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
AdminAuth::requireAuth('payments.view');

$id = (int) ($_GET['id'] ?? 0);
$dep = Database::fetchOne('SELECT proof_file FROM deposits WHERE id = ? LIMIT 1', [$id]);
if (!$dep || empty($dep['proof_file'])) {
    http_response_code(404);
    exit('Proof file not found.');
}

$rel = ltrim(str_replace(['..', '\\'], ['', '/'], (string) $dep['proof_file']), '/');
$full = realpath(SMM_STORAGE . '/' . $rel);
$base = realpath(SMM_STORAGE . '/uploads/payments');
if (!$full || !$base || !str_starts_with($full, $base) || !is_file($full)) {
    http_response_code(404);
    exit('Proof file missing on disk.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($full) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($full) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
