<?php
declare(strict_types=1);

function e(?string $value): string
{
    return Security::e($value);
}

function get_setting(string $key, mixed $default = null): mixed
{
    static $cache = null;
    if (!Database::isConfigured()) {
        return $default;
    }
    if ($cache === null) {
        $cache = [];
        try {
            $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable) {
            return $default;
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function set_setting(string $key, ?string $value, string $group = 'general', bool $isSecret = false): void
{
    Database::execute(
        'INSERT INTO settings (setting_group, setting_key, setting_value, is_secret)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)',
        [$group, $key, $value, $isSecret ? 1 : 0]
    );
}

function get_active_theme(): string
{
    $theme = (string) get_setting('active_theme', 'classic');
    $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme) ?: 'classic';
    if (!is_dir(SMM_THEMES . '/' . $theme)) {
        return 'classic';
    }
    return $theme;
}

function app_url(string $path = ''): string
{
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim((string) get_setting('site_url', ''), '/');
    if ($base === '') {
        $base = "$scheme://$host";
    }
    return $base . '/' . ltrim($path, '/');
}

function active_user_currency(?array $user = null): string
{
    static $activeCodes = null;
    if ($activeCodes === null) {
        $activeCodes = [];
        try {
            foreach (Currency::activeCurrencies() as $c) {
                $activeCodes[] = strtoupper((string) $c['code']);
            }
        } catch (Throwable) {
        }
    }

    $candidates = [];
    if (isset($_SESSION['display_currency']) && is_string($_SESSION['display_currency'])) {
        $candidates[] = strtoupper(trim($_SESSION['display_currency']));
    }
    if ($user && !empty($user['preferred_currency'])) {
        $candidates[] = strtoupper(trim((string) $user['preferred_currency']));
    }

    foreach ($candidates as $code) {
        if ($code !== '' && (empty($activeCodes) || in_array($code, $activeCodes, true))) {
            if (isset($_SESSION) && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['display_currency'] = $code;
            }
            return $code;
        }
    }
    return Currency::systemBase();
}

function currency_switch_url(string $currencyCode): string
{
    $params = $_GET;
    $params['page'] = $params['page'] ?? 'dashboard';
    $params['set_currency'] = strtoupper(trim($currencyCode));
    return '/user/index.php?' . http_build_query($params);
}

function format_money(string|float $amountBase, ?string $displayCurrency = null, ?int $decimals = null): string
{
    return Currency::format($amountBase, $displayCurrency, $decimals);
}

function detect_platform(string $text): string
{
    $t = strtolower($text);
    if (str_contains($t, 'instagram') || str_contains($t, 'ig ') || str_contains($t, 'reels')) return 'instagram';
    if (str_contains($t, 'youtube') || str_contains($t, 'yt ')) return 'youtube';
    if (str_contains($t, 'facebook') || str_contains($t, 'fb ')) return 'facebook';
    if (str_contains($t, 'telegram') || str_contains($t, 'tg ')) return 'telegram';
    if (str_contains($t, 'tiktok')) return 'tiktok';
    if (str_contains($t, 'twitter') || str_contains($t, 'x.com') || str_contains($t, 'tweet')) return 'twitter';
    if (str_contains($t, 'linkedin')) return 'linkedin';
    if (str_contains($t, 'spotify')) return 'spotify';
    return 'other';
}

function platform_icon_svg(string $platform, int $size = 24): string
{
    $p = strtolower(trim($platform));
    $s = $size;
    return match ($p) {
        'instagram' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><defs><linearGradient id="igGrad_' . $s . '" x1="2" y1="22" x2="22" y2="2" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#F58529"/><stop offset="45%" stop-color="#DD2A7B"/><stop offset="80%" stop-color="#8134AF"/><stop offset="100%" stop-color="#515BD4"/></linearGradient></defs><rect x="2" y="2" width="20" height="20" rx="6" fill="url(#igGrad_' . $s . ')"/><rect x="6.5" y="6.5" width="11" height="11" rx="3.2" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="2.7" stroke="#fff" stroke-width="1.8"/><circle cx="15.8" cy="8.2" r="1" fill="#fff"/></svg>',
        'youtube' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="4.5" width="20" height="15" rx="4.2" fill="#FF0000"/><path d="M10 9L16 12L10 15V9Z" fill="#FFFFFF"/></svg>',
        'facebook' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#1877F2"/><path d="M13.5 19V12.7H15.6L15.9 10.2H13.5V8.6C13.5 7.9 13.7 7.4 14.7 7.4H16V5.1C15.7 5.1 14.9 5 14 5C12.1 5 10.8 6.2 10.8 8.4V10.2H8.7V12.7H10.8V19H13.5Z" fill="#FFFFFF"/></svg>',
        'telegram' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#229ED9"/><path d="M16.9 7.8L15.1 16.4C15 17 14.6 17.1 14.1 16.8L11.4 14.8L10.1 16.1C9.9 16.2 9.8 16.3 9.5 16.3L9.7 13.5L14.8 8.9C15 8.7 14.7 8.6 14.4 8.8L8.1 12.8L5.4 11.9C4.8 11.7 4.8 11.3 5.5 11L16 7C16.5 6.8 17 7.1 16.9 7.8Z" fill="#FFFFFF"/></svg>',
        'tiktok' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5.5" fill="#111111"/><path d="M14.2 6V13.8C14.2 15.2 13.1 16.3 11.7 16.3C10.3 16.3 9.2 15.2 9.2 13.8C9.2 12.4 10.3 11.3 11.7 11.3C12 11.3 12.2 11.3 12.4 11.4V9.3C12.2 9.3 11.9 9.2 11.7 9.2C9.1 9.2 7 11.3 7 13.9C7 16.5 9.1 18.6 11.7 18.6C14.3 18.6 16.4 16.5 16.4 13.9V9.8C17.3 10.4 18.4 10.8 19.5 10.8V8.7C17.7 8.7 16.3 7.5 16.1 6H14.2Z" fill="#25F4EE"/><path d="M13.7 6V13.8C13.7 15.2 12.6 16.3 11.2 16.3" stroke="#FE2C55" stroke-width="1.3"/></svg>',
        'twitter' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5.5" fill="#0F1419"/><path d="M16.9 6.5H19L14.4 11.8L19.8 18.5H15.6L12.3 14.2L8.5 18.5H6.4L11.3 12.9L6.1 6.5H10.4L13.4 10.4L16.9 6.5ZM16.2 17.3H17.3L9.8 7.6H8.6L16.2 17.3Z" fill="#FFFFFF"/></svg>',
        'linkedin' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" fill="#0A66C2"/><path d="M7.5 10V17H9.8V10H7.5ZM8.65 6.5C7.85 6.5 7.3 7.05 7.3 7.75C7.3 8.45 7.85 9 8.65 9C9.45 9 10 8.45 10 7.75C10 7.05 9.45 6.5 8.65 6.5ZM11.5 10V17H13.8V13.1C13.8 12.05 14.45 11.6 15.1 11.6C15.75 11.6 16.2 12.05 16.2 13.1V17H18.5V12.7C18.5 10.7 17.45 9.8 15.95 9.8C14.85 9.8 14.15 10.35 13.8 10.95V10H11.5Z" fill="#FFFFFF"/></svg>',
        'spotify' => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#1DB954"/><path d="M7.5 9.5C10.5 8.6 14 8.8 16.8 10.3M8.2 12.4C10.7 11.7 13.5 11.9 15.8 13.1M8.9 15.2C10.9 14.6 13.1 14.8 14.9 15.8" stroke="#FFFFFF" stroke-width="1.7" stroke-linecap="round"/></svg>',
        default => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#7045D8"/><path d="M12 4C14.5 6.5 14.5 17.5 12 20M12 4C9.5 6.5 9.5 17.5 12 20M4.5 12H19.5M5.5 8.5H18.5M5.5 15.5H18.5" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"/></svg>',
    };
}

function status_badge_class(string $status): string
{
    return match (strtolower($status)) {
        'completed', 'approved', 'active', 'paid', 'valid', 'answered' => 'badge-success',
        'processing', 'in_progress' => 'badge-processing',
        'pending', 'open', 'partial' => 'badge-warning',
        'cancelled', 'rejected', 'suspended', 'failed', 'refunded', 'closed' => 'badge-danger',
        default => 'badge-muted',
    };
}

function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    if (!$ts) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function format_qty(int $qty): string
{
    if ($qty >= 1000000) return rtrim(rtrim(number_format($qty / 1000000, 1), '0'), '.') . 'M';
    if ($qty >= 1000) return rtrim(rtrim(number_format($qty / 1000, 1), '0'), '.') . 'K';
    return number_format($qty);
}
