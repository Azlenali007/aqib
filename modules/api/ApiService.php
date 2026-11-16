<?php
declare(strict_types=1);

class ApiService
{
    public static function authenticateKey(string $apiKey): ?array
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return null;
        }
        $row = Database::fetchOne(
            'SELECT ak.id AS key_id, u.*, w.balance
             FROM api_keys ak
             INNER JOIN users u ON u.id = ak.user_id
             LEFT JOIN wallets w ON w.user_id = u.id
             WHERE ak.api_key = ? AND ak.is_active = 1 AND u.status = "active" LIMIT 1',
            [$apiKey]
        );
        if ($row) {
            Database::execute('UPDATE api_keys SET last_used_at = NOW(), last_used_ip = ? WHERE id = ?', [Security::clientIp(), $row['key_id']]);
        }
        return $row;
    }

    public static function regenerateUserKey(int $userId): string
    {
        $newKey = 'smm_' . bin2hex(random_bytes(24));
        Database::execute(
            'INSERT INTO api_keys (user_id, api_key, is_active) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE api_key = VALUES(api_key), is_active = 1, updated_at = NOW()',
            [$userId, $newKey]
        );
        Logger::activity('user', $userId, 'api.regenerate_key');
        return $newKey;
    }
}
