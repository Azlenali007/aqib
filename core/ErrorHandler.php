<?php
declare(strict_types=1);

class ErrorHandler
{
    private static bool $registered = false;

    public static function register(bool $debug = false): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        $logDir = defined('SMM_STORAGE') ? SMM_STORAGE . '/logs' : dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        ini_set('error_log', $logDir . '/php_errors.log');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($debug, $logDir): void {
            self::logException($e, $logDir);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "[ERROR] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n");
                exit(1);
            }
            if (!headers_sent()) {
                http_response_code(500);
            }
            $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
                || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isApi) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => $debug ? $e->getMessage() : 'An internal server error occurred. Check error logs.',
                ]);
                exit;
            }

            echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>System Error</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#F7F5FB;color:#211A2D;margin:0;padding:32px;display:flex;align-items:center;justify-content:center;min-height:100vh}';
            echo '.box{max-width:640px;width:100%;background:#fff;border:1px solid #EAE5F1;border-radius:20px;padding:28px;box-shadow:0 10px 30px rgba(36,20,61,.08)}';
            echo 'h1{margin:0 0 8px;font-size:20px;color:#7045D8}p{color:#777083;line-height:1.5}pre{background:#24143D;color:#F7F5FB;padding:14px;border-radius:12px;overflow:auto;font-size:12px}</style></head><body><div class="box">';
            echo '<h1>Something went wrong</h1>';
            if ($debug) {
                echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<pre>' . htmlspecialchars($e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
            } else {
                echo '<p>We encountered an unexpected error while processing your request. The details have been logged securely for administrators.</p>';
            }
            echo '</div></body></html>';
            exit;
        });
    }

    public static function logException(Throwable $e, ?string $logDir = null): void
    {
        $logDir ??= (defined('SMM_STORAGE') ? SMM_STORAGE . '/logs' : dirname(__DIR__) . '/storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        @file_put_contents($logDir . '/error.log', $line, FILE_APPEND | LOCK_EX);
    }
}
