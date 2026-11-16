<?php
declare(strict_types=1);

class ExchangeRate
{
    public static function updateAll(bool $force = false): array
    {
        if (!$force && get_setting('auto_update_enabled', '1') !== '1') {
            return ['success' => false, 'error' => 'Automatic currency updates are disabled in settings.'];
        }

        $baseCurrency = Currency::systemBase();
        $currencies = Database::fetchAll('SELECT code FROM currencies WHERE is_active = 1');
        $targetCodes = array_values(array_filter(array_map(fn($r) => strtoupper($r['code']), $currencies), fn($c) => $c !== $baseCurrency));
        if (empty($targetCodes)) {
            return ['success' => true, 'updated' => 0, 'source' => 'none', 'message' => 'No secondary currencies active.'];
        }

        $primary = (string) get_setting('primary_rate_source', 'frankfurter');
        $fallback = (string) get_setting('fallback_rate_source', 'open_er');
        $sourcesOrder = array_unique([$primary, $fallback, 'frankfurter', 'open_er', 'fawaz_cdn']);

        $errors = [];
        foreach ($sourcesOrder as $sourceKey) {
            $fetcher = self::resolveSource($sourceKey);
            if ($fetcher === null) {
                continue;
            }
            $res = $fetcher->fetchRates($baseCurrency, $targetCodes);
            if ($res['success'] && !empty($res['rates'])) {
                $updated = 0;
                foreach ($res['rates'] as $target => $rateVal) {
                    if (!is_numeric($rateVal) || (float) $rateVal <= 0) {
                        continue;
                    }
                    $rateNorm = Currency::normalizeDecimal((string) $rateVal, 8);
                    $existing = Database::fetchOne(
                        'SELECT id, is_manual_override FROM exchange_rates WHERE base_currency = ? AND target_currency = ? LIMIT 1',
                        [$baseCurrency, $target]
                    );
                    if ($existing && (int) $existing['is_manual_override'] === 1) {
                        continue;
                    }
                    if ($existing) {
                        Database::execute(
                            "UPDATE exchange_rates SET rate = ?, source = ?, status = 'valid', fetched_at = NOW() WHERE id = ?",
                            [$rateNorm, $sourceKey, $existing['id']]
                        );
                    } else {
                        Database::execute(
                            "INSERT INTO exchange_rates (base_currency, target_currency, rate, source, is_manual_override, status, fetched_at)
                             VALUES (?, ?, ?, ?, 0, 'valid', NOW())",
                            [$baseCurrency, $target, $rateNorm, $sourceKey]
                        );
                    }
                    Database::execute(
                        "INSERT INTO exchange_rate_history (base_currency, target_currency, rate, source, status)
                         VALUES (?, ?, ?, ?, 'success')",
                        [$baseCurrency, $target, $rateNorm, $sourceKey]
                    );
                    $updated++;
                }

                // Always keep identity pair valid
                Database::execute(
                    "INSERT INTO exchange_rates (base_currency, target_currency, rate, source, is_manual_override, status, fetched_at)
                     VALUES (?, ?, 1.00000000, 'system_identity', 0, 'valid', NOW())
                     ON DUPLICATE KEY UPDATE rate = 1.00000000, status = 'valid', fetched_at = NOW()",
                    [$baseCurrency, $baseCurrency]
                );

                set_setting('last_rate_update_at', date('Y-m-d H:i:s'), 'currency');
                set_setting('last_rate_source', $sourceKey, 'currency');

                return [
                    'success' => true,
                    'updated' => $updated,
                    'source' => $sourceKey,
                    'base' => $baseCurrency,
                ];
            }
            $errors[$sourceKey] = $res['error'] ?? 'Unknown API failure';
            Database::execute(
                "INSERT INTO exchange_rate_history (base_currency, target_currency, rate, source, status, error_message)
                 VALUES (?, ?, 0.00000000, ?, 'failed', ?)",
                [$baseCurrency, 'ALL', $sourceKey, mb_substr($errors[$sourceKey], 0, 250)]
            );
        }

        // Never overwrite existing valid rates with 1.0 or 0! Preserve last valid rates.
        return [
            'success' => false,
            'error' => 'All exchange rate sources failed. Existing valid rates preserved. Details: ' . json_encode($errors),
            'errors' => $errors,
        ];
    }

    private static function resolveSource(string $key): ?object
    {
        return match ($key) {
            'frankfurter' => new Frankfurter(),
            'open_er' => new OpenER(),
            'fawaz_cdn' => new FreeCurrencyFallback(),
            default => null,
        };
    }

    public static function setManualRate(string $base, string $target, string $rate, bool $isOverride = true): void
    {
        $base = strtoupper(trim($base));
        $target = strtoupper(trim($target));
        $rateNorm = Currency::normalizeDecimal($rate, 8);
        if ((float) $rateNorm <= 0) {
            throw new InvalidArgumentException('Exchange rate must be greater than zero.');
        }

        $existing = Database::fetchOne(
            'SELECT id FROM exchange_rates WHERE base_currency = ? AND target_currency = ? LIMIT 1',
            [$base, $target]
        );
        if ($existing) {
            Database::execute(
                "UPDATE exchange_rates SET rate = ?, source = 'manual', is_manual_override = ?, status = 'valid', fetched_at = NOW() WHERE id = ?",
                [$rateNorm, $isOverride ? 1 : 0, $existing['id']]
            );
        } else {
            Database::execute(
                "INSERT INTO exchange_rates (base_currency, target_currency, rate, source, is_manual_override, status, fetched_at)
                 VALUES (?, ?, ?, 'manual', ?, 'valid', NOW())",
                [$base, $target, $rateNorm, $isOverride ? 1 : 0]
            );
        }

        Database::execute(
            "INSERT INTO exchange_rate_history (base_currency, target_currency, rate, source, status)
             VALUES (?, ?, ?, 'manual', 'success')",
            [$base, $target, $rateNorm]
        );
    }
}
