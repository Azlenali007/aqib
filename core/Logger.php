<?php
declare(strict_types=1);

class Logger
{
    public static function activity(
        string $actorType,
        ?int $actorId,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        array $details = []
    ): void {
        try {
            $ip = Security::clientIp();
            Database::execute(
                'INSERT INTO activity_logs (actor_type, actor_id, action, entity_type, entity_id, details_json, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $actorType,
                    $actorId,
                    $action,
                    $entityType,
                    $entityId,
                    $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                    $ip,
                ]
            );
        } catch (Throwable $e) {
            self::file('activity_fallback.log', "$actorType#$actorId $action: " . $e->getMessage());
        }
    }

    public static function file(string $filename, string $message): void
    {
        $dir = SMM_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $safeFile = basename($filename);
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($dir . '/' . $safeFile, $line, FILE_APPEND | LOCK_EX);
    }
}
