<?php
declare(strict_types=1);

class GenericProvider implements ProviderInterface
{
    private int $providerId;
    private string $apiUrl;
    private string $apiKey;
    private string $configuredCurrency;

    public function __construct(int $providerId, string $apiUrl, string $apiKey, string $configuredCurrency)
    {
        $this->providerId = $providerId;
        $this->apiUrl = trim($apiUrl);
        $this->apiKey = trim($apiKey);
        $this->configuredCurrency = strtoupper(trim($configuredCurrency));
    }

    private function request(string $action, array $params = []): array
    {
        if ($this->apiUrl === '' || $this->apiKey === '') {
            return ['success' => false, 'error' => 'Provider API URL or API Key is not configured.'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'PHP cURL extension is required for provider API calls.'];
        }

        $payload = array_merge([
            'key' => $this->apiKey,
            'action' => $action,
        ], $params);

        $cfg = require SMM_CONFIG . '/integrations.php';
        $timeout = (int) ($cfg['provider_timeout'] ?? 20);
        $start = microtime(true);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $isError = ($raw === false || $httpCode >= 400);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['error'])) {
            $isError = true;
        }

        $safeSummary = json_encode(['action' => $action, 'params' => array_diff_key($params, ['key' => 1])]);
        $errMsg = $curlErr ?: (is_array($decoded) && isset($decoded['error']) ? (string) $decoded['error'] : ($raw === false ? 'Connection failed' : null));

        if ($this->providerId > 0) {
            try {
                Database::execute(
                    'INSERT INTO provider_logs (provider_id, action, request_summary, response_code, response_body, is_error, error_message, duration_ms)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $this->providerId,
                        $action,
                        $safeSummary,
                        $httpCode ?: null,
                        is_string($raw) ? mb_substr($raw, 0, 16000) : null,
                        $isError ? 1 : 0,
                        $errMsg ? mb_substr($errMsg, 0, 250) : null,
                        $durationMs,
                    ]
                );
            } catch (Throwable) {
            }
        }

        if ($raw === false) {
            return ['success' => false, 'error' => 'cURL error: ' . $curlErr];
        }
        if (!is_array($decoded)) {
            return ['success' => false, 'error' => "Invalid JSON response from provider (HTTP $httpCode)."];
        }
        if (isset($decoded['error'])) {
            return ['success' => false, 'error' => (string) $decoded['error'], 'data' => $decoded];
        }

        return ['success' => true, 'data' => $decoded, 'http_code' => $httpCode];
    }

    public function testConnection(): array
    {
        return $this->getBalance();
    }

    public function getBalance(): array
    {
        $res = $this->request('balance');
        if (!$res['success']) {
            return $res;
        }
        $data = $res['data'];
        if (!isset($data['balance'])) {
            return ['success' => false, 'error' => 'Provider response missing balance field.'];
        }
        $currency = !empty($data['currency']) ? strtoupper(trim((string) $data['currency'])) : $this->configuredCurrency;
        return [
            'success' => true,
            'balance' => (string) $data['balance'],
            'currency' => $currency,
        ];
    }

    public function getServices(): array
    {
        $res = $this->request('services');
        if (!$res['success']) {
            return $res;
        }
        $list = $res['data'];
        if (!is_array($list) || !array_is_list($list)) {
            return ['success' => false, 'error' => 'Provider services response is not a valid list.'];
        }
        return ['success' => true, 'services' => $list];
    }

    public function addOrder(string $serviceId, string $link, int $quantity, array $extra = []): array
    {
        $params = array_merge([
            'service' => $serviceId,
            'link' => $link,
            'quantity' => $quantity,
        ], $extra);
        $res = $this->request('add', $params);
        if (!$res['success']) {
            return $res;
        }
        if (!isset($res['data']['order'])) {
            return ['success' => false, 'error' => 'Provider did not return an order ID.'];
        }
        return ['success' => true, 'provider_order_id' => (string) $res['data']['order']];
    }

    public function getOrderStatus(string $providerOrderId): array
    {
        $res = $this->request('status', ['order' => $providerOrderId]);
        if (!$res['success']) {
            return $res;
        }
        return ['success' => true, 'status' => $res['data']];
    }

    public function getMultiOrderStatus(array $providerOrderIds): array
    {
        $res = $this->request('status', ['orders' => implode(',', $providerOrderIds)]);
        if (!$res['success']) {
            return $res;
        }
        return ['success' => true, 'statuses' => $res['data']];
    }

    public function requestRefill(string $providerOrderId): array
    {
        $res = $this->request('refill', ['order' => $providerOrderId]);
        if (!$res['success']) {
            return $res;
        }
        return ['success' => true, 'refill_id' => (string) ($res['data']['refill'] ?? '')];
    }

    public function cancelOrder(string $providerOrderId): array
    {
        $res = $this->request('cancel', ['orders' => $providerOrderId]);
        if (!$res['success']) {
            return $res;
        }
        return ['success' => true, 'data' => $res['data']];
    }
}
