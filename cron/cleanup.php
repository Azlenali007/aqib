<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$delSessions = Database::execute('DELETE FROM user_sessions WHERE expires_at < NOW() OR is_revoked = 1');
$delAttempts = Database::execute('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
$delResets = Database::execute('DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL');

cron_record_run('cleanup', "Purged $delSessions expired sessions, $delAttempts old login attempts, $delResets password resets");
if (PHP_SAPI === 'cli') {
    echo "Cleanup complete.\n";
}
