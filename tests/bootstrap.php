<?php
declare(strict_types=1);

// Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Mark testing environment for framework conditionals if any
$_SERVER['ISH_TESTING'] = $_SERVER['ISH_TESTING'] ?? '1';

use Ishmael\Core\Logger;

// Initialize Logger to avoid uninitialized static property access during tests
$logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs_tests';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logPath = $logDir . DIRECTORY_SEPARATOR . 'app.test.log';
Logger::init(['path' => $logPath]);
