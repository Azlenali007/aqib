<?php
declare(strict_types=1);

class NotificationService
{
    public static function broadcastAll(string $title, string $message, bool $sendEmail = false): int
    {
        $users = Database::fetchAll('SELECT id, email FROM users WHERE status = "active"');
        $count = 0;
        foreach ($users as $u) {
            Notification::sendToUser((int) $u['id'], 'announcement', $title, $message, '/user/index.php?page=notifications', $sendEmail);
            $count++;
        }
        return $count;
    }
}
