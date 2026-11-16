<?php
declare(strict_types=1);

class RazorpayGateway
{
    private array $config;
    private ?array $gatewayRow;

    public function __construct()
    {
        $this->gatewayRow = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "razorpay" LIMIT 1');
        $this->config = $this->gatewayRow && $this->gatewayRow['config_json']
            ? (json_decode($this->gatewayRow['config_json'], true) ?: [])
            : [];
    }

    public function isConfigured(): bool
    {
        return !empty($this->gatewayRow['is_active'])
            && !empty($this->config['key_id'])
            && !empty($this->config['key_secret']);
    }

    public function keyId(): string
    {
        return (string) ($this->config['key_id'] ?? '');
    }

    public function createOrder(string $amount, string $currency, string $receiptId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Razorpay gateway is not configured or disabled.'];
        }
        $keyId = (string) $this->config['key_id'];
        $keySecret = Security::decrypt((string) $this->config['key_secret']);
        if ($keySecret === '') {
            $keySecret = (string) $this->config['key_secret'];
        }

        $subunits = (int) round((float) Currency::mul($amount, '100', 2));
        $payload = json_encode([
            'amount' => $subunits,
            'currency' => strtoupper($currency),
            'receipt' => $receiptId,
            'payment_capture' => 1,
        ]);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $keyId . ':' . $keySecret,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'error' => 'Razorpay cURL error: ' . $err];
        }
        $data = json_decode($raw, true);
        if ($code >= 400 || !is_array($data) || empty($data['id'])) {
            $msg = $data['error']['description'] ?? "Razorpay order creation failed (HTTP $code).";
            return ['success' => false, 'error' => $msg];
        }
        return ['success' => true, 'order' => $data];
    }

    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $keySecret = Security::decrypt((string) ($this->config['key_secret'] ?? ''));
        if ($keySecret === '') {
            $keySecret = (string) ($this->config['key_secret'] ?? '');
        }
        if ($keySecret === '' || $orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $rawBody, string $headerSignature): bool
    {
        $webhookSecret = Security::decrypt((string) ($this->config['webhook_secret'] ?? ''));
        if ($webhookSecret === '') {
            $webhookSecret = (string) ($this->config['webhook_secret'] ?? '');
        }
        if ($webhookSecret === '' || $headerSignature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $webhookSecret);
        return hash_equals($expected, $headerSignature);
    }
}
