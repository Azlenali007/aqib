<?php
declare(strict_types=1);

class ManualGateway
{
    private ?array $gatewayRow;
    private array $config;

    public function __construct()
    {
        $this->gatewayRow = Database::fetchOne('SELECT * FROM payment_gateways WHERE code = "manual" LIMIT 1');
        $this->config = $this->gatewayRow && $this->gatewayRow['config_json']
            ? (json_decode($this->gatewayRow['config_json'], true) ?: [])
            : [];
    }

    public function isEnabled(): bool
    {
        return !empty($this->gatewayRow['is_active']);
    }

    public function details(): array
    {
        return [
            'gateway' => $this->gatewayRow,
            'config' => $this->config,
        ];
    }
}
