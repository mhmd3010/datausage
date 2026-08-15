<?php

// Data Usage Cron Sync Runner
// Location: system/plugin/ui/data_usage/cron_sync.php
// Crontab: */5 * * * * php /var/www/html/demo/system/plugin/ui/data_usage/cron_sync.php >/dev/null 2>&1

require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../dataUsage.php';

$lockFile = $CACHE_PATH . '/cron_data_usage.lock';
if (!is_dir($CACHE_PATH)) {
    @mkdir($CACHE_PATH, 0777, true);
}

$lock = fopen($lockFile, 'c');
if ($lock === false) {
    exit(1);
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    // Another sync process is running
    fclose($lock);
    exit(0);
}

data_usage_set_config('data_usage_last_cron_run', date('Y-m-d H:i:s'));
data_usage_sync();

flock($lock, LOCK_UN);
fclose($lock);
