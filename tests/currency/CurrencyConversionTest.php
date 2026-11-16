<?php
declare(strict_types=1);

function run_currency_conversion_tests(): void
{
    // Test 1: INR Provider (₹1) + INR System Base -> Cost must be ₹1.000000, NEVER converted as USD!
    // With 20% margin -> Selling price must be ₹1.200000
    $calcSame = Currency::calculateImportPricing('1.00', 'INR', 'INR', '20');
    TestRunner::assertTrue($calcSame['success'] === true, 'INR -> INR conversion succeeds');
    TestRunner::assertSame('1.00000000', $calcSame['exchange_rate'], 'INR -> INR exchange rate is exact 1.00000000');
    TestRunner::assertSame('1.000000', $calcSame['converted_cost'], '₹1 INR provider price converts to ₹1.000000 cost');
    TestRunner::assertSame('1.200000', $calcSame['selling_price'], '₹1 INR + 20% margin = ₹1.200000 selling price');

    // Test 2: USD Provider ($1.00) -> INR System Base with explicit rate 1 USD = 85.00 INR
    ExchangeRate::setManualRate('INR', 'USD', '0.01176471', true); // or direct USD->INR
    ExchangeRate::setManualRate('USD', 'INR', '85.00000000', true);
    $calcUsd = Currency::calculateImportPricing('1.00', 'USD', 'INR', '20');
    TestRunner::assertTrue($calcUsd['success'] === true, 'USD -> INR conversion succeeds');
    TestRunner::assertSame('85.000000', $calcUsd['converted_cost'], '$1 USD at 85 INR = 85.000000 INR cost');
    TestRunner::assertSame('102.000000', $calcUsd['selling_price'], '85.00 INR + 20% margin = 102.000000 INR selling price');

    // Test 3: Missing rate for unconfigured exotic currency (e.g. ZMW) MUST return error (NEVER default to 1.0!)
    $calcMissing = Currency::calculateImportPricing('10.00', 'ZMW', 'INR', '20');
    TestRunner::assertTrue($calcMissing['success'] === false, 'Missing exchange rate blocks import instead of faking 1.0');

    // Clean up test rate
    Database::execute("DELETE FROM exchange_rates WHERE (base_currency='USD' AND target_currency='INR') OR (base_currency='INR' AND target_currency='USD')");
}
