<?php
declare(strict_types=1);

class OpenER
{
    public function fetchRates(string $baseCurrency, array $targetCurrencies): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL extension missing'];
        }
        $base = strtoupper($baseCurrency);
        $url = 'https://open.er-api.com/v6/latest/' . urlencode($base);

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
        if (!is_array($json) || ($json['result'] ?? '') !== 'success' || empty($json['rates'])) {
            return ['success' => false, 'error' => 'Invalid OpenER response format'];
        }
        $filtered = [];
        foreach ($targetCurrencies as $t) {
            $tUp = strtoupper($t);
            if (isset($json['rates'][$tUp]) && is_numeric($json['rates'][$tUp])) {
                $filtered[$tUp] = $json['rates'][$tUp];
            }
        }
        return ['success' => true, 'rates' => $filtered];
    }
}
