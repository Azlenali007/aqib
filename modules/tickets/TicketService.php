<?php
declare(strict_types=1);

class TicketService
{
    public static function create(int $userId, string $category, string $subject, string $message, string $priority = 'medium', ?array $attachment = null): array
    {
        $subject = trim($subject);
        $message = trim($message);
        if ($subject === '' || $message === '') {
            return ['success' => false, 'error' => 'Subject and message are required.'];
        }

        $attPath = null;
        if ($attachment && !empty($attachment['tmp_name'])) {
            $up = Upload::store($attachment, 'tickets');
            if (!$up['success']) {
                return $up;
            }
            $attPath = $up['path'];
        }

        $ticketId = Database::transaction(function () use ($userId, $category, $subject, $message, $priority, $attPath) {
            $id = (int) Database::insert(
                'INSERT INTO tickets (user_id, category, subject, priority, status, last_reply_at) VALUES (?, ?, ?, ?, "open", NOW())',
                [$userId, mb_substr($category, 0, 60), mb_substr($subject, 0, 200), in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium']
            );
            Database::execute(
                'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, attachment_path) VALUES (?, "user", ?, ?, ?)',
                [$id, $userId, $message, $attPath]
            );
            return $id;
        });

        Logger::activity('user', $userId, 'ticket.create', 'tickets', (string) $ticketId);
        return ['success' => true, 'ticket_id' => $ticketId];
    }

    public static function reply(int $ticketId, string $senderType, int $senderId, string $message, ?array $attachment = null): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['success' => false, 'error' => 'Reply message cannot be empty.'];
        }
        $ticket = Database::fetchOne('SELECT * FROM tickets WHERE id = ? LIMIT 1', [$ticketId]);
        if (!$ticket) {
            return ['success' => false, 'error' => 'Ticket not found.'];
        }
        if ($senderType === 'user' && (int) $ticket['user_id'] !== $senderId) {
            return ['success' => false, 'error' => 'Unauthorized ticket access.'];
        }

        $attPath = null;
        if ($attachment && !empty($attachment['tmp_name'])) {
            $up = Upload::store($attachment, 'tickets');
            if (!$up['success']) {
                return $up;
            }
            $attPath = $up['path'];
        }

        Database::execute(
            'INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, attachment_path) VALUES (?, ?, ?, ?, ?)',
            [$ticketId, $senderType, $senderId, $message, $attPath]
        );
        $newStatus = $senderType === 'admin' ? 'answered' : 'open';
        Database::execute('UPDATE tickets SET status = ?, last_reply_at = NOW() WHERE id = ?', [$newStatus, $ticketId]);

        if ($senderType === 'admin') {
            Notification::sendToUser((int) $ticket['user_id'], 'ticket', "Reply on Ticket #$ticketId", "Support replied to: {$ticket['subject']}", "/user/index.php?page=ticket-details&id=$ticketId");
        }
        return ['success' => true];
    }
}
