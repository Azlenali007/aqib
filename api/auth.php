<?php
declare(strict_types=1);

$key = trim((string) ($_REQUEST['key'] ?? ''));
if ($key === '') {
    Response::json(['error' => 'API key is required.'], 401);
}
$apiUser = ApiService::authenticateKey($key);
if (!$apiUser) {
    Response::json(['error' => 'Invalid or inactive API key.'], 401);
}
return $apiUser;
