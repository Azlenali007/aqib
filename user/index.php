<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
enforce_maintenance_mode();

$user = Auth::requireAuth();
require __DIR__ . '/router.php';
