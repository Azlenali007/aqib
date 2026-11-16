<?php
declare(strict_types=1);

class Router
{
    public static function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    public static function resolveUserPage(string $route): ?string
    {
        $map = [
            '' => 'dashboard.php',
            'dashboard' => 'dashboard.php',
            'new-order' => 'new-order.php',
            'mass-order' => 'mass-order.php',
            'orders' => 'orders.php',
            'order-details' => 'order-details.php',
            'refill' => 'refill.php',
            'services' => 'services.php',
            'favourites' => 'favourites.php',
            'wallet' => 'wallet.php',
            'add-funds' => 'add-funds.php',
            'transactions' => 'transactions.php',
            'payments' => 'payments.php',
            'api' => 'api.php',
            'api-docs' => 'api-docs.php',
            'tickets' => 'tickets.php',
            'ticket-details' => 'ticket-details.php',
            'notifications' => 'notifications.php',
            'refer-earn' => 'refer-earn.php',
            'profile' => 'profile.php',
            'settings' => 'settings.php',
            'child-panels' => 'child-panels/index.php',
            'child-panels/create' => 'child-panels/create.php',
            'child-panels/details' => 'child-panels/details.php',
            'child-panels/domains' => 'child-panels/domains.php',
            'child-panels/branding' => 'child-panels/branding.php',
            'child-panels/pricing' => 'child-panels/pricing.php',
            'child-panels/services' => 'child-panels/services.php',
            'child-panels/users' => 'child-panels/users.php',
            'child-panels/orders' => 'child-panels/orders.php',
            'child-panels/wallet' => 'child-panels/wallet.php',
            'child-panels/transactions' => 'child-panels/transactions.php',
            'child-panels/settings' => 'child-panels/settings.php',
        ];
        $clean = trim($route, '/');
        if (!isset($map[$clean])) {
            return null;
        }
        $theme = get_active_theme();
        $file = SMM_THEMES . '/' . $theme . '/user/' . $map[$clean];
        return is_file($file) ? $file : null;
    }
}
