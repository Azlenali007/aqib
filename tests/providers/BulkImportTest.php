<?php
declare(strict_types=1);

function run_bulk_import_tests(): void
{
    $provId = (int) Database::insert(
        'INSERT INTO providers (name, api_url, api_key_enc, currency, status) VALUES (?, ?, ?, ?, "active")',
        ['Test INR Provider', 'https://provider.invalid/api/v2', Security::encrypt('test_key'), 'INR']
    );
    $catId = (int) Database::fetchValue('SELECT id FROM categories ORDER BY id ASC LIMIT 1');

    // Seed USD->INR for mixed testing, keep ZMW missing to test partial failure isolation
    ExchangeRate::setManualRate('USD', 'INR', '84.00000000', true);

    $provider = Database::fetchOne('SELECT * FROM providers WHERE id = ?', [$provId]);
    $remote = [
        ['service' => '9001', 'name' => 'Instagram Followers HQ', 'category' => 'IG', 'rate' => '1.00', 'min' => 100, 'max' => 10000, 'refill' => true],
        ['service' => '9002', 'name' => 'YouTube Views USD', 'category' => 'YT', 'rate' => '2.00', 'currency' => 'USD', 'min' => 500, 'max' => 50000],
        ['service' => '9003', 'name' => 'Invalid Unmapped Currency', 'category' => 'Other', 'rate' => '5.00', 'currency' => 'ZMW', 'min' => 10, 'max' => 100],
    ];

    $preview = ProviderService::buildImportPreview($provider, $remote, '20');
    TestRunner::assertSame(3, count($preview['items']), 'Preview returns all 3 rows');
    TestRunner::assertTrue($preview['items'][0]['valid'] === true && $preview['items'][0]['selling_price'] === '1.200000', 'Preview calculates INR service correctly (1.00 -> 1.20)');
    TestRunner::assertTrue($preview['items'][1]['valid'] === true && $preview['items'][1]['selling_price'] === '201.600000', 'Preview calculates USD service correctly (2*84*1.2 = 201.60)');
    TestRunner::assertTrue($preview['items'][2]['valid'] === false, 'Preview flags unmapped ZMW currency as blocked');

    // Batch bulk import (valid rows succeed even though row 3 fails)
    $importRes = ProviderService::bulkImport($provId, $preview['items'], $catId, '20', 'skip', 1);
    TestRunner::assertSame(2, $importRes['success_count'], 'Bulk import commits 2 valid services');
    TestRunner::assertSame(1, $importRes['fail_count'], 'Bulk import records 1 failed row without aborting batch');

    // Duplicate skip test
    $importDup = ProviderService::bulkImport($provId, [$preview['items'][0]], $catId, '20', 'skip', 1);
    TestRunner::assertSame(1, $importDup['skipped_count'], 'Duplicate service skipped in skip mode');

    // Cleanup
    Database::execute('DELETE FROM services WHERE provider_id = ?', [$provId]);
    Database::execute('DELETE FROM providers WHERE id = ?', [$provId]);
    Database::execute("DELETE FROM exchange_rates WHERE base_currency='USD' AND target_currency='INR'");
}
