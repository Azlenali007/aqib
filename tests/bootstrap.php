<?php
declare(strict_types=1);

define('SMM_TESTING', true);
require_once dirname(__DIR__) . '/includes/init.php';

if (!Database::isConfigured()) {
    fwrite(STDERR, "ERROR: Database is not configured for tests.\n");
    exit(1);
}

class TestRunner
{
    public static int $passed = 0;
    public static int $failed = 0;

    public static function assertTrue(bool $cond, string $msg): void
    {
        if ($cond) {
            self::$passed++;
        } else {
            self::$failed++;
            fwrite(STDERR, "  [FAIL] $msg\n");
            throw new RuntimeException("Assertion failed: $msg");
        }
    }

    public static function assertSame(mixed $expected, mixed $actual, string $msg): void
    {
        if ($expected === $actual) {
            self::$passed++;
        } else {
            self::$failed++;
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            fwrite(STDERR, "  [FAIL] $msg (expected $expStr, got $actStr)\n");
            throw new RuntimeException("Assertion failed: $msg");
        }
    }
}
