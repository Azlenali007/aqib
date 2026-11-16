<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once SMM_CORE . '/ErrorHandler.php';

$appConfig = require SMM_CONFIG . '/app.php';
ErrorHandler::register((bool) ($appConfig['debug'] ?? false));

// Autoload core, integrations, and modules classes
spl_autoload_register(static function (string $class): void {
    $candidates = [
        SMM_CORE . '/' . $class . '.php',
        SMM_INTEGRATIONS . '/providers/' . $class . '.php',
        SMM_INTEGRATIONS . '/payments/' . $class . '.php',
        SMM_INTEGRATIONS . '/currency/' . $class . '.php',
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
    foreach (glob(SMM_MODULES . '/*/' . $class . '.php') ?: [] as $modFile) {
        require_once $modFile;
        return;
    }
});

require_once SMM_INCLUDES . '/functions.php';
require_once SMM_INCLUDES . '/csrf.php';
require_once SMM_INCLUDES . '/permissions.php';
require_once SMM_INCLUDES . '/maintenance.php';
require_once SMM_INCLUDES . '/notifications.php';

// If installer has not been completed and we are not inside /install/ or CLI
$reqPath = $_SERVER['REQUEST_URI'] ?? '';
if (PHP_SAPI !== 'cli' && !Database::isConfigured() && !str_starts_with($reqPath, '/install')) {
    header('Location: /install/index.php');
    exit;
}

if (Database::isConfigured()) {
    try {
        $tz = (string) get_setting('default_timezone', $appConfig['timezone'] ?? 'Asia/Kolkata');
        @date_default_timezone_set($tz ?: 'Asia/Kolkata');
    } catch (Throwable) {
        date_default_timezone_set('Asia/Kolkata');
    }
}
