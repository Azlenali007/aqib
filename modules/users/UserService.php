<?php
declare(strict_types=1);

class UserService
{
    public static function getStats(int $userId): array
    {
        $counts = Database::fetchOne(
            "SELECT
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
                SUM(CASE WHEN status IN ('processing','in_progress') THEN 1 ELSE 0 END) AS processing_orders,
                SUM(CASE WHEN status IN ('cancelled','refunded') THEN 1 ELSE 0 END) AS cancelled_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN charge ELSE 0 END), 0) AS total_spending
             FROM orders WHERE user_id = ?",
            [$userId]
        ) ?: [];

        return [
            'total_orders' => (int) ($counts['total_orders'] ?? 0),
            'completed_orders' => (int) ($counts['completed_orders'] ?? 0),
            'pending_orders' => (int) ($counts['pending_orders'] ?? 0),
            'processing_orders' => (int) ($counts['processing_orders'] ?? 0),
            'cancelled_orders' => (int) ($counts['cancelled_orders'] ?? 0),
            'total_spending' => (string) ($counts['total_spending'] ?? '0.0000'),
        ];
    }
}
