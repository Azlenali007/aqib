<?php
declare(strict_types=1);

class FreeCurrencyFallback
{
    public function fetchRates(string $baseCurrency, array $targetCurrencies): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL extension missing'];
        }
        $baseLower = strtolower($baseCurrency);
        $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/{$baseLower}.json";

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
        if (!is_array($json) || empty($json[$baseLower]) || !is_array($json[$baseLower])) {
            return ['success' => false, 'error' => 'Invalid Fawaz Currency API response format'];
        }
        $filtered = [];
        foreach ($targetCurrencies as $t) {
            $tl = strtolower($t);
            $tu = strtoupper($t);
            if (isset($json[$baseLower][$tl]) && is_numeric($json[$baseLower][$tl])) {
                $filtered[$tu] = $json[$baseLower][$tl];
            }
        }
        return ['success' => true, 'rates' => $filtered];
    }
}
