<?php
declare(strict_types=1);

// Force base_path() to resolve to the IshmaelPHP-Core root during tests to avoid vendor/helper shadowing
if (!defined('ISH_APP_BASE')) {
    define('ISH_APP_BASE', realpath(__DIR__ . '/..'));
}

// Mark testing environment for framework conditionals if any
$_SERVER['ISH_TESTING'] = $_SERVER['ISH_TESTING'] ?? '1';

// Load global helper functions (base_path, env, config, etc.) first so test helpers take precedence
require_once __DIR__ . '/../app/Helpers/helpers.php';

// Composer autoloader (may include a vendor copy of helpers, but our functions are already defined)
require __DIR__ . '/../vendor/autoload.php';

// Ensure test utilities are loaded (not managed by Composer autoload)
require_once __DIR__ . '/Database/AdapterTestUtil.php';

use Ishmael\Core\Logger;

// Initialize Logger to avoid uninitialized static property access during tests
$logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ish_logs_tests';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logPath = $logDir . DIRECTORY_SEPARATOR . 'app.test.log';
Logger::init(['path' => $logPath]);
