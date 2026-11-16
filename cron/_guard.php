<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

if (PHP_SAPI !== 'cli') {
    $sec = require SMM_CONFIG . '/security.php';
    $expected = (string) ($sec['cron_token'] ?? '');
    $provided = (string) ($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Forbidden: Invalid cron token.');
    }
}

function cron_record_run(string $jobName, string $summary): void
{
    set_setting('cron_last_' . $jobName, date('Y-m-d H:i:s') . ' | ' . $summary, 'cron');
    Logger::file('cron.log', "[$jobName] $summary");
}
