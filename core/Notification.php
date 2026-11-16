<?php
declare(strict_types=1);

class Notification
{
    public static function sendToUser(int $userId, string $type, string $title, string $message, ?string $link = null, bool $alsoEmail = false): void
    {
        Database::execute(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)',
            [$userId, $type, mb_substr($title, 0, 160), $message, $link]
        );

        if ($alsoEmail) {
            $email = Database::fetchValue('SELECT email FROM users WHERE id = ?', [$userId]);
            if (is_string($email) && $email !== '') {
                Mailer::send($email, $title, '<p>' . nl2br(Security::e($message)) . '</p>');
            }
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId],
            0
        );
    }
}
