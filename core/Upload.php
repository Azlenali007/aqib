<?php
declare(strict_types=1);

class Upload
{
    public static function store(array $file, string $subDir, array $allowedMimes = []): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload failed or no file provided.'];
        }

        $cfg = require SMM_CONFIG . '/security.php';
        $maxBytes = (int) ($cfg['max_upload_bytes'] ?? 5242880);
        if ((int) ($file['size'] ?? 0) > $maxBytes || (int) ($file['size'] ?? 0) <= 0) {
            return ['success' => false, 'error' => 'File size exceeds allowed limit (5MB).'];
        }

        $mimes = $allowedMimes ?: ($cfg['allowed_upload_mimes'] ?? ['image/jpeg' => 'jpg', 'image/png' => 'png']);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        if (!is_string($detectedMime) || !isset($mimes[$detectedMime])) {
            return ['success' => false, 'error' => 'Unsupported or unsafe file type.'];
        }

        $ext = $mimes[$detectedMime];
        $safeSub = preg_replace('/[^a-zA-Z0-9_-]/', '', $subDir);
        $targetDir = SMM_STORAGE . '/uploads/' . $safeSub;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $targetDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'error' => 'Unable to store uploaded file.'];
        }

        return [
            'success' => true,
            'path' => 'uploads/' . $safeSub . '/' . $filename,
            'filename' => $filename,
            'mime' => $detectedMime,
        ];
    }
}
