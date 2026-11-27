<?php

// Data Usage CLI Cron Sync
// Crontab example: every 5 minutes -> php /path/to/system/plugin/dataUsage/cron_sync.php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$rootPath = realpath(__DIR__ . '/../../..');
if (!$rootPath || !is_file($rootPath . '/init.php')) {
    fwrite(STDERR, "Could not locate PHPNuxBill init.php\n");
    exit(1);
}

chdir($rootPath);
require_once $rootPath . '/init.php';

if (function_exists('data_usage_sync')) {
    data_usage_sync();
}
