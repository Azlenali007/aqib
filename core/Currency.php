<?php
declare(strict_types=1);

class Currency
{
    public static function normalizeDecimal(string|int|float $value, int $scale = 8): string
    {
        $str = trim((string) $value);
        if ($str === '' || !preg_match('/^[+-]?\d+(?:\.\d+)?$/', $str)) {
            throw new InvalidArgumentException("Invalid decimal value: $str");
        }
        $neg = str_starts_with($str, '-');
        $str = ltrim($str, '+-');
        [$intPart, $fracPart] = array_pad(explode('.', $str, 2), 2, '');
        $intPart = ltrim($intPart, '0');
        if ($intPart === '') {
            $intPart = '0';
        }
        $fracPart = str_pad(substr($fracPart, 0, $scale), $scale, '0');
        $res = $scale > 0 ? "$intPart.$fracPart" : $intPart;
        return ($neg && ($intPart !== '0' || trim($fracPart, '0') !== '')) ? "-$res" : $res;
    }

    public static function add(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        $aStr = self::normalizeDecimal($a, $scale);
        $bStr = self::normalizeDecimal($b, $scale);
        if (function_exists('bcadd')) {
            return bcadd($aStr, $bStr, $scale);
        }
        $factor = 10 ** $scale;
        $sum = self::toScaledInt($aStr, $scale) + self::toScaledInt($bStr, $scale);
        return self::fromScaledInt($sum, $scale, $factor);
    }

    public static function sub(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        $aStr = self::normalizeDecimal($a, $scale);
        $bStr = self::normalizeDecimal($b, $scale);
        if (function_exists('bcsub')) {
            return bcsub($aStr, $bStr, $scale);
        }
        $factor = 10 ** $scale;
        $diff = self::toScaledInt($aStr, $scale) - self::toScaledInt($bStr, $scale);
        return self::fromScaledInt($diff, $scale, $factor);
    }

    public static function mul(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        $aStr = self::normalizeDecimal($a, 8);
        $bStr = self::normalizeDecimal($b, 8);
        if (function_exists('bcmul')) {
            return bcmul($aStr, $bStr, $scale);
        }
        return self::stringMul($aStr, $bStr, $scale);
    }

    public static function div(string|int|float $a, string|int|float $b, int $scale = 6): string
    {
        $aStr = self::normalizeDecimal($a, 8);
        $bStr = self::normalizeDecimal($b, 8);
        if ((float) $bStr == 0.0) {
            throw new DivisionByZeroError('Division by zero in Currency::div');
        }
        if (function_exists('bcdiv')) {
            return bcdiv($aStr, $bStr, $scale);
        }
        $aF = (int) round((float) $aStr * 1000000);
        $bF = (int) round((float) $bStr * 1000000);
        $val = ($aF * (10 ** $scale)) / $bF;
        return self::fromScaledInt((int) round($val), $scale, 10 ** $scale);
    }

    public static function compare(string|int|float $a, string|int|float $b, int $scale = 6): int
    {
        $aStr = self::normalizeDecimal($a, $scale);
        $bStr = self::normalizeDecimal($b, $scale);
        if (function_exists('bccomp')) {
            return bccomp($aStr, $bStr, $scale);
        }
        $ia = self::toScaledInt($aStr, $scale);
        $ib = self::toScaledInt($bStr, $scale);
        return $ia <=> $ib;
    }

    private static function toScaledInt(string $norm, int $scale): int
    {
        $neg = str_starts_with($norm, '-');
        $clean = str_replace(['-', '.'], '', $norm);
        $val = (int) $clean;
        return $neg ? -$val : $val;
    }

    private static function fromScaledInt(int $val, int $scale, int $factor): string
    {
        $neg = $val < 0;
        $abs = abs($val);
        $intPart = intdiv($abs, $factor);
        $fracPart = str_pad((string) ($abs % $factor), $scale, '0', STR_PAD_LEFT);
        $out = $scale > 0 ? "$intPart.$fracPart" : (string) $intPart;
        return ($neg && $abs > 0) ? "-$out" : $out;
    }

    private static function stringMul(string $a, string $b, int $outScale): string
    {
        $neg = (str_starts_with($a, '-') xor str_starts_with($b, '-'));
        $a = ltrim($a, '-');
        $b = ltrim($b, '-');
        [$ai, $af] = array_pad(explode('.', $a, 2), 2, '');
        [$bi, $bf] = array_pad(explode('.', $b, 2), 2, '');
        $dec = strlen($af) + strlen($bf);
        $numA = ltrim($ai . $af, '0') ?: '0';
        $numB = ltrim($bi . $bf, '0') ?: '0';
        if ($numA === '0' || $numB === '0') {
            return self::normalizeDecimal('0', $outScale);
        }
        $lenA = strlen($numA);
        $lenB = strlen($numB);
        $res = array_fill(0, $lenA + $lenB, 0);
        for ($i = $lenA - 1; $i >= 0; $i--) {
            for ($j = $lenB - 1; $j >= 0; $j--) {
                $p = ((int) $numA[$i]) * ((int) $numB[$j]) + $res[$i + $j + 1];
                $res[$i + $j + 1] = $p % 10;
                $res[$i + $j] += intdiv($p, 10);
            }
        }
        $str = ltrim(implode('', $res), '0') ?: '0';
        if ($dec > 0) {
            $str = str_pad($str, $dec + 1, '0', STR_PAD_LEFT);
            $intPart = substr($str, 0, -$dec);
            $fracPart = substr($str, -$dec);
            $raw = $intPart . '.' . $fracPart;
        } else {
            $raw = $str;
        }
        return self::normalizeDecimal(($neg ? '-' : '') . $raw, $outScale);
    }

    public static function systemBase(): string
    {
        try {
            $code = Database::fetchValue('SELECT code FROM currencies WHERE is_base = 1 LIMIT 1');
            if (is_string($code) && strlen($code) === 3) {
                return strtoupper($code);
            }
            $s = get_setting('system_base_currency', 'INR');
            return strtoupper((string) $s ?: 'INR');
        } catch (Throwable) {
            return 'INR';
        }
    }

    public static function activeCurrencies(): array
    {
        try {
            return Database::fetchAll('SELECT * FROM currencies WHERE is_active = 1 ORDER BY sort_order ASC, code ASC');
        } catch (Throwable) {
            return [['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_places' => 2, 'is_base' => 1]];
        }
    }

    public static function getCurrencyMeta(string $code): array
    {
        $code = strtoupper(trim($code));
        try {
            $row = Database::fetchOne('SELECT * FROM currencies WHERE code = ? LIMIT 1', [$code]);
            if ($row) {
                return $row;
            }
        } catch (Throwable) {
        }
        $fallback = SUPPORTED_CURRENCIES_META[$code] ?? ['name' => $code, 'symbol' => $code . ' '];
        return ['code' => $code, 'name' => $fallback['name'], 'symbol' => $fallback['symbol'], 'decimal_places' => 2];
    }

    /**
     * Get exact valid exchange rate from $from -> $to.
     * Returns null if missing/invalid — NEVER silently returns 1.0 or 0 for mismatched currencies!
     */
    public static function getExchangeRate(string $fromCurrency, string $toCurrency): ?array
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        if ($from === '' || $to === '') {
            return null;
        }
        if ($from === $to) {
            return ['rate' => '1.00000000', 'source' => 'identity', 'fetched_at' => date('Y-m-d H:i:s')];
        }

        // Direct pair
        $direct = Database::fetchOne(
            "SELECT rate, source, fetched_at FROM exchange_rates WHERE base_currency = ? AND target_currency = ? AND status = 'valid' LIMIT 1",
            [$from, $to]
        );
        if ($direct && (float) $direct['rate'] > 0) {
            return ['rate' => self::normalizeDecimal($direct['rate'], 8), 'source' => $direct['source'], 'fetched_at' => $direct['fetched_at']];
        }

        // Inverse pair
        $inverse = Database::fetchOne(
            "SELECT rate, source, fetched_at FROM exchange_rates WHERE base_currency = ? AND target_currency = ? AND status = 'valid' LIMIT 1",
            [$to, $from]
        );
        if ($inverse && (float) $inverse['rate'] > 0) {
            return ['rate' => self::div('1', $inverse['rate'], 8), 'source' => $inverse['source'] . ' (inverse)', 'fetched_at' => $inverse['fetched_at']];
        }

        // Cross via system base currency or USD
        foreach ([self::systemBase(), 'USD'] as $pivot) {
            if ($pivot === $from || $pivot === $to) {
                continue;
            }
            $r1 = self::getDirectOrInverse($from, $pivot);
            $r2 = self::getDirectOrInverse($pivot, $to);
            if ($r1 !== null && $r2 !== null) {
                return [
                    'rate' => self::mul($r1, $r2, 8),
                    'source' => "cross:$pivot",
                    'fetched_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        return null;
    }

    private static function getDirectOrInverse(string $from, string $to): ?string
    {
        $d = Database::fetchValue(
            "SELECT rate FROM exchange_rates WHERE base_currency = ? AND target_currency = ? AND status = 'valid' LIMIT 1",
            [$from, $to]
        );
        if ($d !== null && (float) $d > 0) {
            return self::normalizeDecimal($d, 8);
        }
        $inv = Database::fetchValue(
            "SELECT rate FROM exchange_rates WHERE base_currency = ? AND target_currency = ? AND status = 'valid' LIMIT 1",
            [$to, $from]
        );
        if ($inv !== null && (float) $inv > 0) {
            return self::div('1', $inv, 8);
        }
        return null;
    }

    /**
     * Convert provider price in providerCurrency -> systemBaseCurrency and calculate selling price with profit margin.
     */
    public static function calculateImportPricing(
        string|float $providerPrice,
        string $providerCurrency,
        string $systemBaseCurrency,
        string|float $profitMarginPercent
    ): array {
        $pCurr = strtoupper(trim($providerCurrency));
        $bCurr = strtoupper(trim($systemBaseCurrency));
        if ($pCurr === '' || strlen($pCurr) !== 3) {
            return ['success' => false, 'error' => 'Provider currency is missing or invalid ISO code.'];
        }
        if ($bCurr === '' || strlen($bCurr) !== 3) {
            return ['success' => false, 'error' => 'System base currency is missing or invalid ISO code.'];
        }

        $rateInfo = self::getExchangeRate($pCurr, $bCurr);
        if ($rateInfo === null) {
            return [
                'success' => false,
                'error' => "No valid exchange rate found for $pCurr → $bCurr. Update exchange rates or set a manual rate first.",
            ];
        }

        $priceNorm = self::normalizeDecimal($providerPrice, 6);
        $rateNorm = $rateInfo['rate'];
        $convertedCost = self::mul($priceNorm, $rateNorm, 6);
        $marginMultiplier = self::add('1', self::div($profitMarginPercent, '100', 6), 6);
        $sellingPrice = self::mul($convertedCost, $marginMultiplier, 6);

        return [
            'success' => true,
            'provider_price' => $priceNorm,
            'provider_currency' => $pCurr,
            'system_base_currency' => $bCurr,
            'exchange_rate' => $rateNorm,
            'rate_source' => $rateInfo['source'],
            'converted_cost' => $convertedCost,
            'profit_margin_percent' => self::normalizeDecimal($profitMarginPercent, 2),
            'selling_price' => $sellingPrice,
        ];
    }

    public static function convertFromBase(string|float $amountBase, string $targetCurrency): string
    {
        $base = self::systemBase();
        $target = strtoupper(trim($targetCurrency));
        if ($target === $base) {
            return self::normalizeDecimal($amountBase, 4);
        }
        $rateInfo = self::getExchangeRate($base, $target);
        if ($rateInfo === null) {
            return self::normalizeDecimal($amountBase, 4);
        }
        return self::mul($amountBase, $rateInfo['rate'], 4);
    }

    public static function format(string|float $amountBase, ?string $displayCurrency = null, ?int $decimals = null): string
    {
        $base = self::systemBase();
        $target = strtoupper(trim($displayCurrency ?: $base));
        $converted = $amountBase;
        $usedCurrency = $base;

        if ($target !== $base) {
            $rateInfo = self::getExchangeRate($base, $target);
            if ($rateInfo !== null) {
                $converted = self::mul($amountBase, $rateInfo['rate'], 4);
                $usedCurrency = $target;
            }
        }

        $meta = self::getCurrencyMeta($usedCurrency);
        $dec = $decimals ?? (int) ($meta['decimal_places'] ?? 2);
        $num = (float) self::normalizeDecimal($converted, max(2, $dec));
        return $meta['symbol'] . number_format($num, $dec, '.', ',');
    }

    public static function formatRaw(string|float $rawAmount, string $currencyCode, int $decimals = 2): string
    {
        $meta = self::getCurrencyMeta($currencyCode);
        $num = (float) self::normalizeDecimal($rawAmount, max(2, $decimals));
        return $meta['symbol'] . number_format($num, $decimals, '.', ',');
    }
}
