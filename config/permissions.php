<?php
declare(strict_types=1);

return [
    'modules' => [
        'dashboard' => ['dashboard.view'],
        'users' => ['users.view', 'users.create', 'users.edit', 'users.delete', 'users.wallet'],
        'orders' => ['orders.view', 'orders.edit', 'orders.cancel', 'orders.refill', 'orders.export'],
        'categories' => ['categories.manage'],
        'services' => ['services.view', 'services.manage'],
        'providers' => ['providers.view', 'providers.manage'],
        'payments' => ['payments.view', 'payments.approve'],
        'gateways' => ['gateways.manage'],
        'wallet' => ['wallet.view'],
        'currency' => ['currency.manage'],
        'referrals' => ['referrals.manage'],
        'child_panels' => ['child_panels.manage'],
        'tickets' => ['tickets.view', 'tickets.reply'],
        'notifications' => ['notifications.manage'],
        'website' => ['website.manage'],
        'themes' => ['themes.manage'],
        'reports' => ['reports.view'],
        'roles' => ['roles.manage'],
        'security' => ['security.view'],
        'settings' => ['settings.manage'],
    ],
];
