<?php
declare(strict_types=1);

class Provider
{
    public static function client(array $providerRow): GenericProvider
    {
        $apiKey = Security::decrypt((string) $providerRow['api_key_enc']);
        return new GenericProvider(
            (int) $providerRow['id'],
            (string) $providerRow['api_url'],
            $apiKey,
            strtoupper((string) $providerRow['currency'])
        );
    }
}
