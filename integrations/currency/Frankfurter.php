<?php
declare(strict_types=1);

class Frankfurter
{
    public function fetchRates(string $baseCurrency, array $targetCurrencies): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL extension missing'];
        }
        $base = strtoupper($baseCurrency);
        $targets = array_map('strtoupper', $targetCurrencies);
        $url = 'https://api.frankfurter.dev/v1/latest?base=' . urlencode($base) . '&symbols=' . urlencode(implode(',', $targets));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'SMMPanel-CurrencySync/1.0',
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            return ['success' => false, 'error' => $err ?: "HTTP $code"];
        }
        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['rates']) || !is_array($json['rates'])) {
            return ['success' => false, 'error' => 'Invalid Frankfurter response format'];
        }
        return ['success' => true, 'rates' => $json['rates']];
    }
}
