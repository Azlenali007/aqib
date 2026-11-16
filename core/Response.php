<?php
declare(strict_types=1);

class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): never
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
        }
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
        $parsed = parse_url($ref);
        $path = ($parsed['path'] ?? $fallback) . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        self::redirect(Security::sanitizeRedirect($path, $fallback));
    }

    public static function csv(string $filename, array $headers, array $rows): never
    {
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
