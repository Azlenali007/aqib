<?php
declare(strict_types=1);

function get_user_notifications(int $userId, int $limit = 10): array
{
    return Database::fetchAll(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
        [$userId, $limit]
    );
}
