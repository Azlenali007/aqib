<?php
declare(strict_types=1);

class Cache
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return $default;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return $default;
        }
        $data = @json_decode($raw, true);
        if (!is_array($data) || !isset($data['expires_at'])) {
            return $default;
        }
        if ($data['expires_at'] !== 0 && time() > (int) $data['expires_at']) {
            @unlink($path);
            return $default;
        }
        return $data['value'] ?? $default;
    }

    public static function put(string $key, mixed $value, int $ttlSeconds = 300): void
    {
        $dir = SMM_STORAGE . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode([
            'expires_at' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE);
        @file_put_contents(self::path($key), $payload, LOCK_EX);
    }

    public static function forget(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function flush(): int
    {
        $dir = SMM_STORAGE . '/cache';
        if (!is_dir($dir)) {
            return 0;
        }
        $count = 0;
        foreach (glob($dir . '/*.cache.json') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    private static function path(string $key): string
    {
        return SMM_STORAGE . '/cache/' . sha1($key) . '.cache.json';
    }
}
