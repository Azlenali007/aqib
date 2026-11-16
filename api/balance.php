<?php
declare(strict_types=1);

Response::json([
    'balance' => Currency::normalizeDecimal((string) ($user['balance'] ?? '0'), 4),
    'currency' => Currency::systemBase(),
]);
