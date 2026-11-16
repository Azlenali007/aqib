<?php
declare(strict_types=1);

class ReportService
{
    public static function summary(string $fromDate, string $toDate): array
    {
        $from = $fromDate . ' 00:00:00';
        $to = $toDate . ' 23:59:59';

        $orders = Database::fetchOne(
            "SELECT
                COUNT(*) AS order_count,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN charge ELSE 0 END), 0) AS revenue,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN provider_cost ELSE 0 END), 0) AS cost,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','failed') THEN profit ELSE 0 END), 0) AS profit
             FROM orders WHERE created_at BETWEEN ? AND ?",
            [$from, $to]
        ) ?: [];

        $deposits = Database::fetchOne(
            "SELECT COUNT(*) AS deposit_count, COALESCE(SUM(net_credit), 0) AS total_deposits
             FROM deposits WHERE status IN ('paid','approved') AND created_at BETWEEN ? AND ?",
            [$from, $to]
        ) ?: [];

        $newUsers = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?',
            [$from, $to],
            0
        );

        return [
            'orders' => $orders,
            'deposits' => $deposits,
            'new_users' => $newUsers,
        ];
    }
}
