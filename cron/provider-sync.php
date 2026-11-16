<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard.php';

$providers = Database::fetchAll('SELECT * FROM providers WHERE status = "active" AND sync_enabled = 1');
$systemBase = Currency::systemBase();
$syncedProviders = 0;
$updatedServices = 0;

foreach ($providers as $p) {
    $client = Provider::client($p);
    $bal = $client->getBalance();
    if ($bal['success']) {
        Database::execute(
            'UPDATE providers SET balance = ?, balance_currency = ?, last_balance_sync_at = NOW() WHERE id = ?',
            [Currency::normalizeDecimal($bal['balance'], 4), $bal['currency'], $p['id']]
        );
    }

    $remote = $client->getServices();
    if (!$remote['success']) {
        continue;
    }
    $syncedProviders++;
    $remoteById = [];
    foreach ($remote['services'] as $rs) {
        if (!empty($rs['service'])) {
            $remoteById[(string) $rs['service']] = $rs;
        }
    }

    $mapped = Database::fetchAll(
        'SELECT s.*, psm.auto_sync_price FROM services s
         INNER JOIN provider_service_mappings psm ON psm.service_id = s.id
         WHERE s.provider_id = ? AND psm.auto_sync_price = 1',
        [$p['id']]
    );

    foreach ($mapped as $svc) {
        $extId = (string) $svc['provider_service_id'];
        if (!isset($remoteById[$extId])) {
            continue;
        }
        $rs = $remoteById[$extId];
        $extCurrency = !empty($rs['currency']) ? strtoupper(trim((string) $rs['currency'])) : strtoupper((string) $p['currency']);
        $calc = Currency::calculateImportPricing((string) ($rs['rate'] ?? '0'), $extCurrency, $systemBase, (string) $svc['profit_margin_percent']);
        if (!$calc['success']) {
            continue;
        }
        Database::execute(
            'UPDATE services SET provider_price = ?, provider_currency = ?, provider_cost_base = ?, provider_exchange_rate = ?, selling_price = ?, last_synced_at = NOW() WHERE id = ?',
            [$calc['provider_price'], $calc['provider_currency'], $calc['converted_cost'], $calc['exchange_rate'], $calc['selling_price'], $svc['id']]
        );
        $updatedServices++;
    }
}

cron_record_run('provider_sync', "Synced $syncedProviders providers, updated $updatedServices service prices");
if (PHP_SAPI === 'cli') {
    echo "Provider sync complete: $syncedProviders providers, $updatedServices services\n";
}
