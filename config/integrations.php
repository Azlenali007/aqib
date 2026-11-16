<?php
declare(strict_types=1);

return [
    'currency_sources' => [
        'frankfurter' => [
            'name' => 'Frankfurter API',
            'url' => 'https://api.frankfurter.dev/v1/latest',
            'timeout' => 10,
        ],
        'open_er' => [
            'name' => 'Open ER API',
            'url' => 'https://open.er-api.com/v6/latest/',
            'timeout' => 10,
        ],
        'fawaz_cdn' => [
            'name' => 'Fawaz Ahmed Currency API',
            'url' => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/',
            'timeout' => 10,
        ],
    ],
    'provider_timeout' => 20,
    'razorpay_api_base' => 'https://api.razorpay.com/v1',
];
