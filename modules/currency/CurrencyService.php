<?php
declare(strict_types=1);

class CurrencyService
{
    public static function listWithRates(): array
    {
        $base = Currency::systemBase();
        return Database::fetchAll(
            'SELECT c.*, er.rate, er.source, er.is_manual_override, er.status AS rate_status, er.fetched_at
             FROM currencies c
             LEFT JOIN exchange_rates er ON er.base_currency = ? AND er.target_currency = c.code
             ORDER BY c.sort_order ASC, c.code ASC',
            [$base]
        );
    }
}
