<?php
declare(strict_types=1);

class ProviderService
{
    public static function fetchRemoteServices(int $providerId): array
    {
        $provider = Database::fetchOne('SELECT * FROM providers WHERE id = ? LIMIT 1', [$providerId]);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found.'];
        }
        $client = Provider::client($provider);
        $res = $client->getServices();
        if (!$res['success']) {
            return $res;
        }
        return ['success' => true, 'provider' => $provider, 'services' => $res['services']];
    }

    /**
     * Build a complete import preview showing Provider Price, Provider Currency, System Base Currency,
     * Exchange Rate, Converted Cost, Profit Margin, and Final Selling Price.
     */
    public static function buildImportPreview(array $provider, array $remoteServices, string $profitMarginPercent = '20'): array
    {
        $systemBase = Currency::systemBase();
        $configuredProvCurrency = strtoupper(trim((string) ($provider['currency'] ?? '')));

        $existingRows = Database::fetchAll(
            'SELECT provider_service_id, id, name FROM services WHERE provider_id = ?',
            [(int) $provider['id']]
        );
        $existingMap = [];
        foreach ($existingRows as $er) {
            $existingMap[(string) $er['provider_service_id']] = $er;
        }

        $preview = [];
        foreach ($remoteServices as $item) {
            $extId = (string) ($item['service'] ?? '');
            $name = trim((string) ($item['name'] ?? ''));
            $rawRate = (string) ($item['rate'] ?? '0');
            $extCurrency = !empty($item['currency']) ? strtoupper(trim((string) $item['currency'])) : $configuredProvCurrency;

            if ($extId === '' || $name === '' || !is_numeric($rawRate)) {
                $preview[] = [
                    'external_id' => $extId,
                    'name' => $name ?: 'Unnamed Service',
                    'valid' => false,
                    'error' => 'Missing service ID, name, or numeric rate.',
                ];
                continue;
            }

            $calc = Currency::calculateImportPricing($rawRate, $extCurrency, $systemBase, $profitMarginPercent);
            $isDuplicate = isset($existingMap[$extId]);

            $preview[] = [
                'external_id' => $extId,
                'name' => $name,
                'external_category' => (string) ($item['category'] ?? 'General'),
                'platform' => detect_platform($name . ' ' . ($item['category'] ?? '')),
                'min_qty' => max(1, (int) ($item['min'] ?? 10)),
                'max_qty' => max(10, (int) ($item['max'] ?? 100000)),
                'description' => (string) ($item['desc'] ?? $item['description'] ?? ''),
                'refill_supported' => !empty($item['refill']) ? 1 : 0,
                'cancel_supported' => !empty($item['cancel']) ? 1 : 0,
                'dripfeed_supported' => !empty($item['dripfeed']) ? 1 : 0,
                'is_duplicate' => $isDuplicate,
                'existing_service_id' => $isDuplicate ? (int) $existingMap[$extId]['id'] : null,
                'valid' => $calc['success'],
                'error' => $calc['error'] ?? null,
                'provider_price' => $calc['provider_price'] ?? Currency::normalizeDecimal($rawRate, 6),
                'provider_currency' => $extCurrency,
                'system_base_currency' => $systemBase,
                'exchange_rate' => $calc['exchange_rate'] ?? null,
                'rate_source' => $calc['rate_source'] ?? null,
                'converted_cost' => $calc['converted_cost'] ?? null,
                'profit_margin_percent' => $calc['profit_margin_percent'] ?? $profitMarginPercent,
                'selling_price' => $calc['selling_price'] ?? null,
                'raw' => $item,
            ];
        }

        return [
            'system_base_currency' => $systemBase,
            'provider_currency' => $configuredProvCurrency,
            'items' => $preview,
        ];
    }

    /**
     * Bulk import selected (or all) services safely in batches with duplicate handling and per-item failure reporting.
     */
    public static function bulkImport(
        int $providerId,
        array $selectedItems,
        int $categoryId,
        string $profitMarginPercent,
        string $duplicateMode = 'skip',
        int $enableOnImport = 1,
        ?int $adminId = null
    ): array {
        $provider = Database::fetchOne('SELECT * FROM providers WHERE id = ? LIMIT 1', [$providerId]);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found.'];
        }
        $category = Database::fetchOne('SELECT id FROM categories WHERE id = ? LIMIT 1', [$categoryId]);
        if (!$category) {
            return ['success' => false, 'error' => 'Target category does not exist.'];
        }

        $systemBase = Currency::systemBase();
        $configuredProvCurrency = strtoupper(trim((string) ($provider['currency'] ?? '')));

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;
        $failures = [];

        foreach ($selectedItems as $item) {
            $extId = trim((string) ($item['external_id'] ?? $item['service'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $rawRate = (string) ($item['provider_price'] ?? $item['rate'] ?? '0');
            $extCurrency = strtoupper(trim((string) ($item['provider_currency'] ?? $item['currency'] ?? $configuredProvCurrency)));

            if ($extId === '' || $name === '' || !is_numeric($rawRate)) {
                $failCount++;
                $failures[] = ['external_id' => $extId ?: '?', 'name' => $name ?: 'Unknown', 'reason' => 'Invalid service metadata or non-numeric rate.'];
                continue;
            }

            $calc = Currency::calculateImportPricing($rawRate, $extCurrency, $systemBase, $profitMarginPercent);
            if (!$calc['success']) {
                $failCount++;
                $failures[] = ['external_id' => $extId, 'name' => $name, 'reason' => $calc['error']];
                continue;
            }

            $existing = Database::fetchOne(
                'SELECT id FROM services WHERE provider_id = ? AND provider_service_id = ? LIMIT 1',
                [$providerId, $extId]
            );

            if ($existing && $duplicateMode === 'skip') {
                $skippedCount++;
                continue;
            }

            $platform = detect_platform($name . ' ' . ($item['external_category'] ?? ''));

            try {
                if ($existing && $duplicateMode === 'update') {
                    Database::execute(
                        'UPDATE services SET
                            name = ?, description = ?, min_qty = ?, max_qty = ?,
                            provider_price = ?, provider_currency = ?, provider_cost_base = ?,
                            provider_exchange_rate = ?, system_base_currency = ?, profit_margin_percent = ?,
                            selling_price = ?, refill_supported = ?, cancel_supported = ?,
                            last_synced_at = NOW()
                         WHERE id = ?',
                        [
                            mb_substr($name, 0, 220),
                            (string) ($item['description'] ?? ''),
                            max(1, (int) ($item['min_qty'] ?? 10)),
                            max(10, (int) ($item['max_qty'] ?? 100000)),
                            $calc['provider_price'],
                            $calc['provider_currency'],
                            $calc['converted_cost'],
                            $calc['exchange_rate'],
                            $calc['system_base_currency'],
                            $calc['profit_margin_percent'],
                            $calc['selling_price'],
                            !empty($item['refill_supported']) ? 1 : 0,
                            !empty($item['cancel_supported']) ? 1 : 0,
                            $existing['id'],
                        ]
                    );
                    $serviceId = (int) $existing['id'];
                } else {
                    $serviceId = (int) Database::insert(
                        'INSERT INTO services (
                            category_id, provider_id, provider_service_id, name, description, platform, icon,
                            min_qty, max_qty, provider_price, provider_currency, provider_cost_base,
                            provider_exchange_rate, system_base_currency, profit_margin_percent, selling_price,
                            refill_supported, cancel_supported, dripfeed_supported, is_active, imported_at, last_synced_at
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                        [
                            $categoryId,
                            $providerId,
                            $extId,
                            mb_substr($name, 0, 220),
                            (string) ($item['description'] ?? ''),
                            $platform,
                            $platform,
                            max(1, (int) ($item['min_qty'] ?? 10)),
                            max(10, (int) ($item['max_qty'] ?? 100000)),
                            $calc['provider_price'],
                            $calc['provider_currency'],
                            $calc['converted_cost'],
                            $calc['exchange_rate'],
                            $calc['system_base_currency'],
                            $calc['profit_margin_percent'],
                            $calc['selling_price'],
                            !empty($item['refill_supported']) ? 1 : 0,
                            !empty($item['cancel_supported']) ? 1 : 0,
                            !empty($item['dripfeed_supported']) ? 1 : 0,
                            $enableOnImport ? 1 : 0,
                        ]
                    );
                }

                Database::execute(
                    'INSERT INTO provider_service_mappings (provider_id, service_id, external_service_id, external_name, external_category, external_rate, external_currency, last_payload_json)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE service_id = VALUES(service_id), external_name = VALUES(external_name), external_rate = VALUES(external_rate), external_currency = VALUES(external_currency), last_payload_json = VALUES(last_payload_json)',
                    [
                        $providerId,
                        $serviceId,
                        $extId,
                        mb_substr($name, 0, 255),
                        mb_substr((string) ($item['external_category'] ?? ''), 0, 160),
                        $calc['provider_price'],
                        $calc['provider_currency'],
                        json_encode($item),
                    ]
                );

                $successCount++;
            } catch (Throwable $e) {
                $failCount++;
                $failures[] = ['external_id' => $extId, 'name' => $name, 'reason' => $e->getMessage()];
            }
        }

        Logger::activity('admin', $adminId, 'provider.bulk_import', 'providers', (string) $providerId, [
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'skipped_count' => $skippedCount,
        ]);

        return [
            'success' => true,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'skipped_count' => $skippedCount,
            'failures' => $failures,
        ];
    }
}
