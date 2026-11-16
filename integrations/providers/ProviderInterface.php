<?php
declare(strict_types=1);

interface ProviderInterface
{
    public function testConnection(): array;
    public function getBalance(): array;
    public function getServices(): array;
    public function addOrder(string $serviceId, string $link, int $quantity, array $extra = []): array;
    public function getOrderStatus(string $providerOrderId): array;
    public function getMultiOrderStatus(array $providerOrderIds): array;
    public function requestRefill(string $providerOrderId): array;
    public function cancelOrder(string $providerOrderId): array;
}
