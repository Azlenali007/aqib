<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
enforce_maintenance_mode();

// Check if incoming Host matches an active verified Child Panel domain
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$host = explode(':', $host)[0];
if ($host !== '' && Database::isConfigured()) {
    $childDomain = Database::fetchOne(
        'SELECT cp.* FROM child_panel_domains cpd
         INNER JOIN child_panels cp ON cp.id = cpd.child_panel_id
         WHERE cpd.domain = ? AND cp.status = "active" LIMIT 1',
        [$host]
    );
    if ($childDomain) {
        define('ACTIVE_CHILD_PANEL_ID', (int) $childDomain['id']);
        require __DIR__ . '/public/child-panel-portal.php';
        exit;
    }
}

require __DIR__ . '/public/index.php';
