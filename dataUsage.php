<?php

/**
 * Bismillahir Rahmanir Rahim
 * FREE PALESTINE
 * Plugin Name: Data Usage
 * Author: mahmud
 * Contact: https://t.me/mhmd_3010
 */

use PEAR2\Net\RouterOS;

// Register Admin Menu
register_menu("Data Usage", true, "data_usage_admin", "AFTER_REPORTS", "fa fa-area-chart", '', 'info', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_history", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_user", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_scripts", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_logs", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_clear_logs", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_sync", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_export", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_disconnect", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_install_poller", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_remove_poller", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_manage_profile_scripts", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_live_status", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_async_routers_status", "", "", '', '', ['SuperAdmin', 'Admin']);
register_menu("", true, "data_usage_live_traffic", "", "", '', '', ['SuperAdmin', 'Admin']);

// Register Public Webhook & API Endpoints
register_menu("", false, "data_usage_webhook", "", "");
register_menu("", false, "data_usage_collector", "", "");
register_menu("", false, "data_usage_api", "", "");
register_menu("", false, "dataUsage_webhook", "", "");

define('DATA_USAGE_PLUGIN_VERSION', '1.1.3');
define('DATA_USAGE_DB_VERSION', '1.0.6');

/**
 * Auto-initialize Database Tables
 */
function data_usage_init_db()
{
    $current = data_usage_get_config('data_usage_db_version', '0');
    if ($current === DATA_USAGE_DB_VERSION) {
        return;
    }

    // Active & Closed Sessions Table
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_data_usage_sessions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(128) NOT NULL,
        router_id INT NOT NULL DEFAULT 0,
        router_name VARCHAR(100) DEFAULT '',
        username VARCHAR(100) NOT NULL,
        service VARCHAR(32) NOT NULL DEFAULT 'PPPoE',
        ip_address VARCHAR(45) DEFAULT '',
        mac_address VARCHAR(45) DEFAULT '',
        uptime VARCHAR(32) DEFAULT NULL,
        status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
        bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
        bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
        total_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        last_bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
        last_bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
        start_time DATETIME NOT NULL,
        last_seen DATETIME NOT NULL,
        closed_time DATETIME DEFAULT NULL,
        close_reason VARCHAR(64) DEFAULT NULL,
        INDEX idx_session_router (router_id, session_id),
        INDEX idx_user_status (username, status),
        INDEX idx_router_status (router_id, status),
        INDEX idx_service (service),
        INDEX idx_last_seen (last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        ORM::raw_execute("ALTER TABLE tbl_data_usage_sessions ADD COLUMN IF NOT EXISTS uptime VARCHAR(32) DEFAULT NULL");
    } catch (Exception $e) {}

    try {
        ORM::raw_execute("ALTER TABLE tbl_data_usage_sessions ADD INDEX IF NOT EXISTS idx_router_user (router_id, username)");
        ORM::raw_execute("ALTER TABLE tbl_data_usage_sessions ADD INDEX IF NOT EXISTS idx_sess_router_user_closed (router_id, username, closed_time, last_seen)");
    } catch (Exception $e) {}

    // Daily Aggregated Usage Table
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_data_usage_daily (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        usage_date DATE NOT NULL,
        router_id INT NOT NULL DEFAULT 0,
        username VARCHAR(100) NOT NULL,
        service VARCHAR(32) NOT NULL DEFAULT 'PPPoE',
        mac_address VARCHAR(45) DEFAULT '',
        bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
        bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
        total_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_date_router_user_svc (usage_date, router_id, username, service),
        INDEX idx_date (usage_date),
        INDEX idx_user (username),
        INDEX idx_router (router_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Separate Router Users Table (Isolated from session tracking)
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_data_usage_router_users (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        router_id INT NOT NULL DEFAULT 0,
        username VARCHAR(100) NOT NULL,
        service VARCHAR(32) NOT NULL DEFAULT 'PPPoE',
        mac_address VARCHAR(50) DEFAULT '',
        profile VARCHAR(100) DEFAULT '',
        comment VARCHAR(255) DEFAULT '',
        disabled TINYINT(1) NOT NULL DEFAULT 0,
        missing_since DATETIME DEFAULT NULL,
        last_synced DATETIME DEFAULT NULL,
        UNIQUE KEY uk_router_user_svc (router_id, username, service),
        INDEX idx_router_user (router_id, username),
        INDEX idx_username (username),
        INDEX idx_service (service),
        INDEX idx_missing (missing_since)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        ORM::raw_execute("ALTER TABLE tbl_data_usage_router_users ADD COLUMN IF NOT EXISTS missing_since DATETIME DEFAULT NULL");
        ORM::raw_execute("ALTER TABLE tbl_data_usage_router_users ADD INDEX IF NOT EXISTS idx_ru_router_missing_user (router_id, missing_since, username)");
        ORM::raw_execute("ALTER TABLE tbl_data_usage_daily ADD INDEX IF NOT EXISTS idx_daily_date_router_svc (usage_date, router_id, service)");
    } catch (Exception $e) {}

    // Event & Poller Logs Table
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS tbl_data_usage_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        log_time DATETIME NOT NULL,
        router_id INT NOT NULL DEFAULT 0,
        router_name VARCHAR(100) DEFAULT '',
        event_type VARCHAR(32) NOT NULL,
        username VARCHAR(100) DEFAULT '',
        details TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'info',
        ip_address VARCHAR(45) DEFAULT '',
        INDEX idx_time (log_time),
        INDEX idx_event (event_type),
        INDEX idx_router (router_id),
        INDEX idx_user (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    data_usage_set_config('data_usage_db_version', DATA_USAGE_DB_VERSION);
}

// Config Helpers
function data_usage_get_config($key, $default = '')
{
    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    return $row ? $row->value : $default;
}

function data_usage_set_config($key, $value)
{
    $row = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
    if (!$row) {
        $row = ORM::for_table('tbl_appconfig')->create();
        $row->setting = $key;
    }
    $row->value = (string)$value;
    $row->save();
}

function data_usage_require_admin()
{
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'] ?? '', ['SuperAdmin', 'Admin'], true)) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
    }
    return $admin;
}

function data_usage_get_webhook_token()
{
    return trim((string) data_usage_get_config('data_usage_webhook_token', ''));
}

function data_usage_webhook_url($appUrl = null)
{
    $base = rtrim($appUrl !== null ? $appUrl : APP_URL, '/') . '/?_route=plugin/data_usage_webhook';
    $token = data_usage_get_webhook_token();
    return $token !== '' ? $base . '&token=' . rawurlencode($token) : $base;
}

function data_usage_batch_customers(array $usernames)
{
    $map = [];
    $unique = array_values(array_unique(array_filter($usernames, function ($u) {
        return $u !== '' && $u !== null;
    })));
    if (empty($unique)) {
        return $map;
    }
    try {
        foreach (array_chunk($unique, 500) as $chunk) {
            $rows = ORM::for_table('tbl_customers')
                ->select_many('username', 'fullname')
                ->where_in('username', $chunk)
                ->find_array();
            foreach ($rows as $r) {
                $map[strtolower($r['username'])] = $r['fullname'];
            }
        }
    } catch (Exception $e) {}
    return $map;
}

/**
 * Event & Activity Logger Helper
 */
function data_usage_log($eventType, $details, $status = 'info', $routerId = 0, $username = '', $routerName = '')
{
    try {
        if ($routerId > 0 && empty($routerName)) {
            $r = ORM::for_table('tbl_routers')->find_one($routerId);
            if ($r) $routerName = $r->name;
        }

        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($clientIp, ',') !== false) {
            $parts = explode(',', $clientIp);
            $clientIp = trim($parts[0]);
        }

        $log = ORM::for_table('tbl_data_usage_logs')->create();
        $log->log_time = date('Y-m-d H:i:s');
        $log->router_id = (int)$routerId;
        $log->router_name = (string)$routerName;
        $log->event_type = (string)$eventType;
        $log->username = (string)$username;
        $log->details = (string)$details;
        $log->status = (string)$status;
        $log->ip_address = (string)$clientIp;
        $log->save();

        if (rand(1, 100) === 1) {
            ORM::raw_execute("DELETE FROM tbl_data_usage_logs WHERE id < (SELECT min_id FROM (SELECT IFNULL(MIN(id), 0) as min_id FROM (SELECT id FROM tbl_data_usage_logs ORDER BY id DESC LIMIT 3000) as t) as t2)");
        }
    } catch (Exception $e) {}
}

/**
 * Format bytes to readable human-friendly string (B, KB, MB, GB, TB)
 */
function data_usage_format_bytes($bytes, $precision = 2)
{
    $bytes = (float)$bytes;
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Format bits per second to readable rate (bps, Kbps, Mbps, Gbps)
 */
function data_usage_format_bps($bps, $precision = 2)
{
    $bps = (float)$bps;
    if ($bps <= 0) return '0 bps';
    if ($bps >= 1000000000) {
        return round($bps / 1000000000, $precision) . ' Gbps';
    }
    if ($bps >= 1000000) {
        return round($bps / 1000000, $precision) . ' Mbps';
    }
    if ($bps >= 1000) {
        return round($bps / 1000, 1) . ' Kbps';
    }
    return round($bps, 0) . ' bps';
}

/**
 * Format seconds to readable duration
 */
function data_usage_format_uptime($seconds)
{
    $seconds = (int)$seconds;
    if ($seconds <= 0) return '0s';
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $mins = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    $out = [];
    if ($days > 0) $out[] = $days . 'd';
    if ($hours > 0) $out[] = $hours . 'h';
    if ($mins > 0) $out[] = $mins . 'm';
    if ($secs > 0 || empty($out)) $out[] = $secs . 's';
    return implode('', $out);
}

/**
 * Helper to fetch cron run status & time info
 */
function data_usage_get_cron_info()
{
    $lastCronRun = data_usage_get_config('data_usage_last_cron_run', '');
    $cronStatus = 'needed'; // 'active', 'delayed', 'needed'
    $cronAgo = '';

    if (!empty($lastCronRun)) {
        $diffSec = max(0, time() - strtotime($lastCronRun));
        $cronAgo = data_usage_format_uptime($diffSec) . ' ago';
        if ($diffSec <= 900) { // within 15 min
            $cronStatus = 'active';
        } else {
            $cronStatus = 'delayed';
        }
    }

    return [
        'last_run' => $lastCronRun,
        'status'   => $cronStatus,
        'ago'      => $cronAgo
    ];
}

/**
 * Helper to record usage deltas into tbl_data_usage_daily
 */
function data_usage_add_daily_delta($routerId, $username, $service, $mac, $deltaIn, $deltaOut, $date = null)
{
    if ($deltaIn <= 0 && $deltaOut <= 0) {
        return;
    }
    $date = $date ?: date('Y-m-d');
    $deltaIn = max(0, (int)$deltaIn);
    $deltaOut = max(0, (int)$deltaOut);
    $deltaTotal = $deltaIn + $deltaOut;

    $stmt = ORM::get_db()->prepare("
        INSERT INTO tbl_data_usage_daily (usage_date, router_id, username, service, mac_address, bytes_in, bytes_out, total_bytes)
        VALUES (:date, :router_id, :username, :service, :mac, :bytes_in, :bytes_out, :total_bytes)
        ON DUPLICATE KEY UPDATE
            bytes_in = bytes_in + VALUES(bytes_in),
            bytes_out = bytes_out + VALUES(bytes_out),
            total_bytes = total_bytes + VALUES(total_bytes),
            mac_address = IF(VALUES(mac_address) != '', VALUES(mac_address), mac_address),
            updated_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        ':date'        => $date,
        ':router_id'   => (int)$routerId,
        ':username'    => $username,
        ':service'     => $service,
        ':mac'         => $mac,
        ':bytes_in'    => $deltaIn,
        ':bytes_out'   => $deltaOut,
        ':total_bytes' => $deltaTotal
    ]);
}

/**
 * Helper to convert RouterOS uptime string (e.g. "1d04:20:15", "04:20:15", "5m20s") into total seconds.
 */
function data_usage_uptime_to_seconds($uptime)
{
    $uptime = trim((string)$uptime);
    if (empty($uptime)) return 0;

    $totalSec = 0;
    if (preg_match('/^(?:(\d+)w)?(?:(\d+)d)?(?:(\d{1,2}):(\d{2}):(\d{2}))$/i', $uptime, $m)) {
        $weeks = !empty($m[1]) ? (int)$m[1] : 0;
        $days = !empty($m[2]) ? (int)$m[2] : 0;
        $hours = (int)$m[3];
        $mins = (int)$m[4];
        $secs = (int)$m[5];
        return ($weeks * 7 * 86400) + ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs;
    }
    
    if (preg_match_all('/(\d+)([wdhms])/i', $uptime, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $val = (int)$match[1];
            $unit = strtolower($match[2]);
            if ($unit === 'w') $totalSec += $val * 604800;
            elseif ($unit === 'd') $totalSec += $val * 86400;
            elseif ($unit === 'h') $totalSec += $val * 3600;
            elseif ($unit === 'm') $totalSec += $val * 60;
            elseif ($unit === 's') $totalSec += $val;
        }
        if ($totalSec > 0) return $totalSec;
    }

    if (is_numeric($uptime)) return (int)$uptime;
    return 0;
}

/**
 * Helper to parse RouterOS date/time strings (e.g. "aug/14/2026 12:30:15", "aug/14 12:30:15") into MySQL Y-m-d H:i:s.
 * Falls back to (now - uptime) if string is missing/empty.
 */
function data_usage_parse_routeros_datetime($str, $uptimeStr = '')
{
    $str = trim((string)$str);
    if (!empty($str) && $str !== 'none' && $str !== 'never') {
        if (preg_match('/^([a-z]{3})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}:\d{2}(?::\d{2})?)$/i', $str, $m)) {
            $normalized = $m[2] . '-' . $m[1] . '-' . $m[3] . ' ' . $m[4];
            $ts = strtotime($normalized);
            if ($ts && $ts > 0) return date('Y-m-d H:i:s', $ts);
        } elseif (preg_match('/^([a-z]{3})\/(\d{1,2})\s+(\d{1,2}:\d{2}(?::\d{2})?)$/i', $str, $m)) {
            $normalized = $m[2] . '-' . $m[1] . '-' . date('Y') . ' ' . $m[3];
            $ts = strtotime($normalized);
            if ($ts && $ts > 0) return date('Y-m-d H:i:s', $ts);
        }

        $ts = strtotime($str);
        if ($ts && $ts > 0) {
            return date('Y-m-d H:i:s', $ts);
        }
    }

    if (!empty($uptimeStr)) {
        $uptimeSec = data_usage_uptime_to_seconds($uptimeStr);
        if ($uptimeSec > 0) {
            return date('Y-m-d H:i:s', time() - $uptimeSec);
        }
    }

    return date('Y-m-d H:i:s');
}

/**
 * Helper to sanitize and normalize MAC address (e.g. "CC:2D:21:5D:F1:B0" -> "cc2d215df1b0").
 */
function data_usage_clean_mac($mac)
{
    $mac = preg_replace('/[^0-9A-Fa-f]/', '', (string)$mac);
    return strtolower($mac);
}

/**
 * Helper to generate a collision-free Hotspot session ID in clean hex format (e.g. "hs81a029bf").
 */
function data_usage_generate_hotspot_session_id($username, $mac, $startTimeEpoch = 0)
{
    $cleanMac = data_usage_clean_mac($mac);
    $ts = ($startTimeEpoch > 0) ? (int)$startTimeEpoch : time();
    $hash = sprintf('%08x', crc32($username . '|' . $cleanMac . '|' . $ts));
    return 'hs' . $hash;
}

/**
 * Helper to sanitize and normalize session_id consistently across ALL entry paths.
 * Normalizes decimal representations (e.g. 2164260866) into lowercase hex (81000002).
 */
function data_usage_clean_session_id($sessionId, $username = '', $mac = '')
{
    $sId = trim(str_replace(['<', '>', '"', "'", ' '], '', (string)$sessionId));
    if (stripos($sId, '0x') === 0) {
        $sId = substr($sId, 2);
    }
    // If pure decimal digits and > 2147483647 (0x80000000), convert decimal integer to hex string
    if (ctype_digit($sId) && (float)$sId > 2147483647) {
        $sId = dechex((float)$sId);
    }
    if (empty($sId)) {
        $sId = !empty($mac) ? trim($mac) : trim($username);
    }
    return strtolower($sId);
}

/**
 * =========================================================================
 * WEBHOOK RECEIVER ENGINE
 * Endpoints: session-start, session-end, batch-sync, single-sync
 * =========================================================================
 */
function data_usage_webhook()
{
    data_usage_init_db();
    header('Content-Type: application/json; charset=utf-8');

    // Read POST or GET data (support raw JSON payload as well)
    $input = file_get_contents('php://input');
    $json = json_decode($input, true) ?: [];

    $expectedToken = data_usage_get_webhook_token();
    if ($expectedToken !== '') {
        $providedToken = (string) ($_POST['token'] ?? $json['token'] ?? $_GET['token'] ?? $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '');
        if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized webhook token']);
            exit;
        }
    }

    $action          = $_POST['action'] ?? $json['action'] ?? $_GET['action'] ?? '';
    $routerId        = (int)($_POST['router'] ?? $_POST['router_id'] ?? $json['router'] ?? $json['router_id'] ?? $_GET['router'] ?? 0);
    $service         = $_POST['service'] ?? $json['service'] ?? $_GET['service'] ?? 'PPPoE';
    $username        = trim($_POST['username'] ?? $_POST['user'] ?? $json['username'] ?? $json['user'] ?? $_GET['username'] ?? '');
    $rawSid          = $_POST['session_id'] ?? $_POST['sid'] ?? $json['session_id'] ?? $json['sid'] ?? $_GET['session_id'] ?? '';
    $mac             = trim($_POST['mac'] ?? $_POST['mac_address'] ?? $json['mac'] ?? $json['mac_address'] ?? $_GET['mac'] ?? '');
    $ip              = trim($_POST['ip'] ?? $_POST['ip_address'] ?? $_POST['address'] ?? $json['ip'] ?? $json['ip_address'] ?? $json['address'] ?? $_GET['ip'] ?? '');
    $uptime          = trim($_POST['uptime'] ?? $json['uptime'] ?? $_GET['uptime'] ?? '');
    $startTimeParam  = trim($_POST['start_time'] ?? $json['start_time'] ?? $_GET['start_time'] ?? '');
    $closedTimeParam = trim($_POST['closed_time'] ?? $json['closed_time'] ?? $_GET['closed_time'] ?? '');
    $closeReasonParam= trim($_POST['close_reason'] ?? $_POST['cause'] ?? $json['close_reason'] ?? $json['cause'] ?? $_GET['close_reason'] ?? '');
    $uptimeSecsParam = (int)($_POST['uptime_secs'] ?? $json['uptime_secs'] ?? 0);
    
    // tx = Upload (bytes_in), rx = Download (bytes_out)
    $txBytes   = (float)($_POST['tx'] ?? $_POST['bytes_in'] ?? $json['tx'] ?? $json['bytes_in'] ?? 0);
    $rxBytes   = (float)($_POST['rx'] ?? $_POST['bytes_out'] ?? $json['rx'] ?? $json['bytes_out'] ?? 0);

    // Resolve Router Name
    $routerName = '';
    if ($routerId > 0) {
        $rObj = ORM::for_table('tbl_routers')->find_one($routerId);
        if ($rObj) $routerName = $rObj->name;
    }

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    // ─── 1. ACTION: SESSION-START (Instant Connect Trigger) ───
    if ($action === 'session-start' || $action === 'connect' || $action === 'login') {
        if (empty($username)) {
            echo json_encode(['status' => 'error', 'message' => 'username is required']);
            exit;
        }

        $realStartTime = data_usage_parse_routeros_datetime($startTimeParam, $uptime);
        $sessionId = '';

        if ($service === 'Hotspot') {
            // Hotspot: clean MAC address & timestamp
            $sessionId = !empty($rawSid) && (strpos($rawSid, 'hs') === 0 || strpos($rawSid, 'hs_') === 0)
                ? data_usage_clean_session_id($rawSid, $username, $mac) 
                : data_usage_generate_hotspot_session_id($username, $mac, strtotime($realStartTime));
        } else {
            // PPPoE: Real hex session-id from /ppp active
            $sessionId = data_usage_clean_session_id($rawSid, $username, $mac);
        }

        // Fast Zombie Check: Close ANY older active session for this specific client
        $zombieQuery = ORM::for_table('tbl_data_usage_sessions')
            ->where('router_id', $routerId)
            ->where('username', $username)
            ->where('service', $service)
            ->where('status', 'active')
            ->where_not_equal('session_id', $sessionId);

        if ($service === 'Hotspot' && !empty($mac)) {
            $zombieQuery->where('mac_address', $mac);
        }

        $oldSessions = $zombieQuery->find_many();
        foreach ($oldSessions as $old) {
            $old->status = 'closed';
            $old->closed_time = $realStartTime;
            $old->close_reason = 'collision_reconnect';
            $old->save();
        }

        // Upsert the current session as active
        $session = ORM::for_table('tbl_data_usage_sessions')
            ->where('router_id', $routerId)
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->find_one();

        if (!$session) {
            $session = ORM::for_table('tbl_data_usage_sessions')->create();
            $session->session_id = $sessionId;
            $session->router_id = $routerId;
            $session->router_name = $routerName;
            $session->username = $username;
            $session->service = $service;
            $session->start_time = $realStartTime;
            $session->bytes_in = 0;
            $session->bytes_out = 0;
            $session->total_bytes = 0;
            $session->last_bytes_in = 0;
            $session->last_bytes_out = 0;
        }

        // Update active network metadata only (never overwrite start_time if already set)
        $session->ip_address = $ip ?: $session->ip_address;
        $session->mac_address = $mac ?: $session->mac_address;
        $session->uptime = $uptime ?: $session->uptime;
        $session->status = 'active';
        $session->last_seen = $now;
        $session->closed_time = null;
        $session->close_reason = null;
        $session->save();

        data_usage_log(
            'session-start',
            "User connected via {$service} (IP: " . ($session->ip_address ?: '-') . ", MAC: " . ($session->mac_address ?: '-') . ", SessionID: {$sessionId})",
            'success',
            $routerId,
            $username,
            $routerName
        );

        echo json_encode([
            'status' => 'success',
            'action' => 'session-start',
            'session_id' => $sessionId,
            'user' => $username,
            'start_time' => $session->start_time
        ]);
        exit;
    }

    // ─── 2. ACTION: SESSION-END (Instant Disconnect Trigger) ───
    if ($action === 'session-end' || $action === 'disconnect' || $action === 'logout') {
        if (empty($username) && empty($rawSid)) {
            echo json_encode(['status' => 'error', 'message' => 'username or session_id is required']);
            exit;
        }

        $session = null;

        // Match currently active session
        if ($service === 'Hotspot' && !empty($username)) {
            $query = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $routerId)
                ->where('username', $username)
                ->where('service', 'Hotspot')
                ->where('status', 'active');
            if (!empty($mac)) {
                $query->where('mac_address', $mac);
            }
            $session = $query->order_by_desc('last_seen')->find_one();
        } elseif (!empty($username)) {
            // PPPoE Match by username + service
            $session = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $routerId)
                ->where('username', $username)
                ->where('service', $service)
                ->where('status', 'active')
                ->order_by_desc('last_seen')
                ->find_one();
        }

        // Fallback: match by session_id
        if (!$session && !empty($rawSid)) {
            $sId = data_usage_clean_session_id($rawSid, $username, $mac);
            $session = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $routerId)
                ->where('session_id', $sId)
                ->where('status', 'active')
                ->order_by_desc('last_seen')
                ->find_one();
        }

        if ($session) {
            $finalIn = ($txBytes > 0) ? $txBytes : $session->last_bytes_in;
            $finalOut = ($rxBytes > 0) ? $rxBytes : $session->last_bytes_out;

            $deltaIn = max(0, $finalIn - $session->last_bytes_in);
            $deltaOut = max(0, $finalOut - $session->last_bytes_out);

            if ($deltaIn > 0 || $deltaOut > 0) {
                data_usage_add_daily_delta($routerId, $session->username, $session->service, $session->mac_address, $deltaIn, $deltaOut);
            }

            $realClosedTime = data_usage_parse_routeros_datetime($closedTimeParam);
            $realCloseReason = !empty($closeReasonParam) ? $closeReasonParam : 'logout';

            if ($uptimeSecsParam > 0 && ($session->start_time === $session->closed_time || empty($session->start_time))) {
                $session->start_time = date('Y-m-d H:i:s', strtotime($realClosedTime) - $uptimeSecsParam);
            }

            $session->bytes_in = $finalIn;
            $session->bytes_out = $finalOut;
            $session->total_bytes = $finalIn + $finalOut;
            $session->last_bytes_in = $finalIn;
            $session->last_bytes_out = $finalOut;
            $session->status = 'closed';
            $session->closed_time = $realClosedTime;
            $session->close_reason = $realCloseReason;
            $session->save();

            data_usage_log(
                'session-end',
                "User disconnected ({$session->service}, Reason: {$realCloseReason}, Session: {$session->session_id}, Usage: " . data_usage_format_bytes($session->total_bytes) . ")",
                'info',
                $routerId,
                $session->username,
                $routerName
            );

            echo json_encode([
                'status' => 'success',
                'action' => 'session-end',
                'session_id' => $session->session_id,
                'user' => $session->username,
                'closed_time' => $session->closed_time,
                'close_reason' => $session->close_reason,
                'final_bytes' => $session->total_bytes
            ]);
            exit;
        }

        echo json_encode(['status' => 'not_found', 'message' => 'No active session found to close']);
        exit;
    }

    // ─── 3. ACTION: BATCH-SYNC (dataUsagePoller 2-Minute Script) ───
    $rawBatch = $json['batch'] ?? $_POST['batch'] ?? null;
    if ($action === 'batch-sync' || !empty($rawBatch)) {
        $batch = is_array($rawBatch) ? $rawBatch : (is_string($rawBatch) ? json_decode($rawBatch, true) : []);
        if (!is_array($batch)) {
            $batch = [];
        }

        if (empty($batch)) {
            data_usage_log('batch-sync', 'Batch sync received with empty active sessions list', 'info', $routerId, '', $routerName);
            echo json_encode([
                'status' => 'success',
                'action' => 'batch-sync',
                'processed' => 0,
                'message' => 'Batch list was empty'
            ]);
            exit;
        }

        $processedCount = 0;
        $batchDeltaIn = 0;
        $batchDeltaOut = 0;

        foreach ($batch as $item) {
            $iUser = trim($item['username'] ?? $item['user'] ?? '');
            if (empty($iUser)) continue;

            $iSvc   = trim($item['service'] ?? 'PPPoE');
            $iMac   = trim($item['mac'] ?? $item['mac_address'] ?? '');
            $iIp    = trim($item['ip'] ?? $item['ip_address'] ?? $item['address'] ?? '');
            $rawUptime = trim($item['uptime'] ?? '');
            $iUptime= !empty($rawUptime) ? data_usage_format_uptime(data_usage_uptime_to_seconds($rawUptime)) : '';
            $iTx    = (float)($item['tx'] ?? $item['bytes_in'] ?? 0);
            $iRx    = (float)($item['rx'] ?? $item['bytes_out'] ?? 0);
            $rawSid = trim($item['session_id'] ?? $item['sid'] ?? '');

            $sId = data_usage_clean_session_id($rawSid, $iUser, $iMac);

            $session = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $routerId)
                ->where('session_id', $sId)
                ->where('status', 'active')
                ->find_one();

            if (!$session) {
                $session = ORM::for_table('tbl_data_usage_sessions')
                    ->where('router_id', $routerId)
                    ->where('username', $iUser)
                    ->where('service', $iSvc)
                    ->where('status', 'active')
                    ->order_by_desc('last_seen')
                    ->find_one();
            }

            if ($session) {
                $deltaIn = ($iTx >= $session->last_bytes_in) ? ($iTx - $session->last_bytes_in) : $iTx;
                $deltaOut = ($iRx >= $session->last_bytes_out) ? ($iRx - $session->last_bytes_out) : $iRx;

                if ($deltaIn > 0 || $deltaOut > 0) {
                    data_usage_add_daily_delta($routerId, $iUser, $iSvc, $iMac ?: $session->mac_address, $deltaIn, $deltaOut, $today);
                    $batchDeltaIn += $deltaIn;
                    $batchDeltaOut += $deltaOut;
                }

                $session->bytes_in = $iTx;
                $session->bytes_out = $iRx;
                $session->total_bytes = $iTx + $iRx;
                $session->last_bytes_in = $iTx;
                $session->last_bytes_out = $iRx;
                $session->last_seen = $now;
                $session->uptime = $iUptime ?: $session->uptime;
                $session->ip_address = $iIp ?: $session->ip_address;
                $session->mac_address = $iMac ?: $session->mac_address;
                $session->save();
            } else {
                // Close older active collision sessions
                $zombies = ORM::for_table('tbl_data_usage_sessions')
                    ->where('router_id', $routerId)
                    ->where('username', $iUser)
                    ->where('status', 'active')
                    ->find_many();

                foreach ($zombies as $z) {
                    $z->status = 'closed';
                    $z->closed_time = $now;
                    $z->close_reason = 'collision_reconnect';
                    $z->save();
                }

                $calcStartTime = !empty($iUptime)
                    ? date('Y-m-d H:i:s', time() - data_usage_uptime_to_seconds($iUptime))
                    : $now;

                $session = ORM::for_table('tbl_data_usage_sessions')->create();
                $session->session_id = $sId;
                $session->router_id = $routerId;
                $session->router_name = $routerName;
                $session->username = $iUser;
                $session->service = $iSvc;
                $session->ip_address = $iIp;
                $session->mac_address = $iMac;
                $session->uptime = $iUptime;
                $session->status = 'active';
                $session->bytes_in = $iTx;
                $session->bytes_out = $iRx;
                $session->total_bytes = $iTx + $iRx;
                $session->last_bytes_in = $iTx;
                $session->last_bytes_out = $iRx;
                $session->start_time = $calcStartTime;
                $session->last_seen = $now;
                $session->closed_time = null;
                $session->close_reason = null;
                $session->save();

                if ($iTx > 0 || $iRx > 0) {
                    data_usage_add_daily_delta($routerId, $iUser, $iSvc, $iMac, $iTx, $iRx, $today);
                    $batchDeltaIn += $iTx;
                    $batchDeltaOut += $iRx;
                }
            }

            $processedCount++;
        }

        data_usage_set_config('data_usage_last_sync_' . $routerId, $now);
        data_usage_set_config('data_usage_last_cron_run', $now);

        data_usage_log(
            'batch-sync',
            "Synced {$processedCount} active session(s). Delta: " . data_usage_format_bytes($batchDeltaIn + $batchDeltaOut) . " (Up: " . data_usage_format_bytes($batchDeltaIn) . ", Down: " . data_usage_format_bytes($batchDeltaOut) . ")",
            'success',
            $routerId,
            '',
            $routerName
        );

        echo json_encode([
            'status' => 'success',
            'action' => 'batch-sync',
            'processed' => $processedCount,
            'total_items' => count($batch),
            'delta_in' => $batchDeltaIn,
            'delta_out' => $batchDeltaOut
        ]);
        exit;
    }

    // ─── 4. SINGLE-SYNC / LEGACY POLLED PUSH ───
    if (!empty($username)) {
        $sId = $sessionId ?: ($mac ?: $username);

        // Only match currently active session with this exact normalized session_id
        $session = ORM::for_table('tbl_data_usage_sessions')
            ->where('router_id', $routerId)
            ->where('session_id', $sId)
            ->where('status', 'active')
            ->find_one();

        if ($session) {
            $deltaIn = ($txBytes >= $session->last_bytes_in) ? ($txBytes - $session->last_bytes_in) : $txBytes;
            $deltaOut = ($rxBytes >= $session->last_bytes_out) ? ($rxBytes - $session->last_bytes_out) : $rxBytes;

            if ($deltaIn > 0 || $deltaOut > 0) {
                data_usage_add_daily_delta($routerId, $username, $service, $mac ?: $session->mac_address, $deltaIn, $deltaOut, $today);
            }

            $session->bytes_in = $txBytes;
            $session->bytes_out = $rxBytes;
            $session->total_bytes = $txBytes + $rxBytes;
            $session->last_bytes_in = $txBytes;
            $session->last_bytes_out = $rxBytes;
            $session->last_seen = $now;
            $session->ip_address = $ip ?: $session->ip_address;
            $session->mac_address = $mac ?: $session->mac_address;
            $session->uptime = $uptime ?: $session->uptime;
            $session->save();
        } else {
            // Close any older active session for this user
            $zombies = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $routerId)
                ->where('username', $username)
                ->where('status', 'active')
                ->find_many();

            foreach ($zombies as $z) {
                $z->status = 'closed';
                $z->closed_time = $now;
                $z->close_reason = 'collision_reconnect';
                $z->save();
            }

            $session = ORM::for_table('tbl_data_usage_sessions')->create();
            $session->session_id = $sId;
            $session->router_id = $routerId;
            $session->router_name = $routerName;
            $session->username = $username;
            $session->service = $service;
            $session->ip_address = $ip;
            $session->mac_address = $mac;
            $session->uptime = $uptime;
            $session->status = 'active';
            $session->bytes_in = $txBytes;
            $session->bytes_out = $rxBytes;
            $session->total_bytes = $txBytes + $rxBytes;
            $session->last_bytes_in = $txBytes;
            $session->last_bytes_out = $rxBytes;
            $session->start_time = $now;
            $session->last_seen = $now;
            $session->closed_time = null;
            $session->close_reason = null;
            $session->save();

            if ($txBytes > 0 || $rxBytes > 0) {
                data_usage_add_daily_delta($routerId, $username, $service, $mac, $txBytes, $rxBytes, $today);
            }
        }

        data_usage_log(
            'single-sync',
            "Push sync for {$username} ({$service}, Total: " . data_usage_format_bytes($txBytes + $rxBytes) . ")",
            'info',
            $routerId,
            $username,
            $routerName
        );

        echo json_encode(['status' => 'success', 'message' => 'Record updated', 'session_id' => $sId]);
        exit;
    }

    data_usage_log(
        'webhook-error',
        "Invalid webhook request received. Action: '" . ($action ?: 'none') . "', User: '" . ($username ?: 'none') . "'",
        'warning',
        $routerId,
        $username,
        $routerName
    );

    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Aliases for webhook routes
function data_usage_collector() { data_usage_webhook(); }
function data_usage_api() { data_usage_webhook(); }
function dataUsage_webhook() { data_usage_webhook(); }

/**
 * =========================================================================
 * ADMIN CONTROLLERS & VIEWS
 * =========================================================================
 */

/**
 * Helper: Connect to MikroTik Router with strict socket timeout (prevents website hanging on offline routers)
 */
function data_usage_get_mikrotik_client($router, $timeout = 6.0)
{
    global $_app_stage;
    if ($_app_stage == 'demo') {
        return null;
    }
    try {
        $ip = $router->ip_address;
        $iport = explode(":", $ip);
        $host = $iport[0];
        $port = !empty($iport[1]) ? (int)$iport[1] : null;

        return new RouterOS\Client($host, $router->username, $router->password, $port, false, (double)$timeout);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Helper: Query MikroTik Router Statistics (Online count, Offline count, Last Sync)
 */
function data_usage_get_router_monitor_data($router)
{
    $lastSync = data_usage_get_config('data_usage_last_sync_' . $router->id) ?: date('Y-m-d H:i:s');

    $info = [
        'id'            => $router->id,
        'name'          => $router->name,
        'ip_address'    => $router->ip_address,
        'online'        => false,
        'online_count'  => 0,
        'offline_count' => 0,
        'total_users'   => 0,
        'last_sync'     => $lastSync,
        'error'         => ''
    ];

    try {
        $client = data_usage_get_mikrotik_client($router, 6.0);
        if (!$client) {
            $info['error'] = 'Router unreachable';
            return $info;
        }

        $info['online'] = true;

        // Use the exact same live session query to get 100% consistent online count (PPPoE + Hotspot only)
        $liveSessions = data_usage_get_direct_live_sessions($router);
        $onlineCount = count($liveSessions);
        $info['live_sessions'] = $liveSessions;

        // Count configured PPPoE secrets (filter for pppoe or any) + Hotspot users
        $pppSecretCount = 0;
        try {
            $pppSecReq = new RouterOS\Request('/ppp/secret/print');
            $pppSecrets = $client->sendSync($pppSecReq);
            foreach ($pppSecrets as $sec) {
                $svc = strtolower($sec->getProperty('service') ?: 'any');
                if ($svc === 'pppoe' || $svc === 'any' || empty($svc)) {
                    $pppSecretCount++;
                }
            }
        } catch (Exception $ePppSec) {}

        $hsUserCount = 0;
        try {
            $hsUsers = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/print'));
            $hsUserCount = count($hsUsers);
        } catch (Exception $eHsUsr) {}

        $totalConfigured = $pppSecretCount + $hsUserCount;
        $offlineCount = max(0, $totalConfigured - $onlineCount);

        $info['online_count']  = $onlineCount;
        $info['offline_count'] = $offlineCount;
        $info['total_users']   = $totalConfigured;
    } catch (Exception $e) {
        $info['error'] = $e->getMessage();
    }

    return $info;
}

/**
 * Helper: Fetch real-time live sessions directly from RouterOS API
 */
function data_usage_get_direct_live_sessions($router)
{
    $sessions = [];
    $now = date('Y-m-d H:i:s');

    try {
        $client = data_usage_get_mikrotik_client($router, 6.0);
        if (!$client) {
            return [];
        }

        // 1. Fetch PPPoE active
        try {
            $pppActive = $client->sendSync(new RouterOS\Request('/ppp/active/print'));
            $pppMap = [];
            foreach ($pppActive as $p) {
                if ($p->getType() !== RouterOS\Response::TYPE_DATA) continue;
                $uName = $p->getProperty('name');
                if (!empty($uName)) {
                    $rawSid = $p->getProperty('session-id') ?: ($p->getProperty('.id') ?: $uName);
                    $callerId = $p->getProperty('caller-id') ?: '';
                    $pppMap[$uName] = [
                        'caller_id'  => $callerId,
                        'address'    => $p->getProperty('address') ?: '',
                        'uptime'     => $p->getProperty('uptime') ?: '',
                        'session_id' => data_usage_clean_session_id($rawSid, $uName, $callerId)
                    ];
                }
            }

            $interfaces = $client->sendSync(new RouterOS\Request('/interface/print'));
            foreach ($interfaces as $iface) {
                if ($iface->getType() !== RouterOS\Response::TYPE_DATA) continue;
                $ifType = $iface->getProperty('type');
                $ifName = $iface->getProperty('name');

                if ($ifType === 'pppoe-in' || strpos($ifName, '<pppoe-') === 0) {
                    $uName = $ifName;
                    if (strpos($ifName, '<pppoe-') === 0 && substr($ifName, -1) === '>') {
                        $uName = substr($ifName, 7, -1);
                    }

                    $rawTx  = (float)$iface->getProperty('tx-byte'); // Client Download
                    $rawRx  = (float)$iface->getProperty('rx-byte'); // Client Upload
                    $linkUp = $iface->getProperty('last-link-up-time') ?: '';

                    $callerId = $pppMap[$uName]['caller_id'] ?? '';
                    $addr     = $pppMap[$uName]['address'] ?? '';
                    $uptime   = $pppMap[$uName]['uptime'] ?? '';
                    $sId      = $pppMap[$uName]['session_id'] ?? data_usage_clean_session_id($callerId, $uName, $callerId);
                    $realStartTime = data_usage_parse_routeros_datetime($linkUp, $uptime);

                    $sessions[] = [
                        'username'        => $uName,
                        'fullname'        => '',
                        'session_id'      => $sId,
                        'service'         => 'PPPoE',
                        'router_id'       => $router->id,
                        'router_name'     => $router->name,
                        'ip_address'      => $addr,
                        'mac_address'     => $callerId,
                        'start_time'      => $realStartTime,
                        'uptime'          => $uptime,
                        'uptime_fmt'      => $uptime,
                        'uptime_seconds'  => data_usage_uptime_to_seconds($uptime),
                        'bytes_in'        => $rawRx,
                        'bytes_out'       => $rawTx,
                        'total_bytes'     => $rawRx + $rawTx,
                        'bytes_in_fmt'    => data_usage_format_bytes($rawRx),
                        'bytes_out_fmt'   => data_usage_format_bytes($rawTx),
                        'total_bytes_fmt' => data_usage_format_bytes($rawRx + $rawTx),
                        'last_seen'       => $now,
                        'status'          => 'Online'
                    ];
                }
            }
        } catch (Exception $ePpp) {}

        // 2. Fetch Hotspot active
        try {
            $hsActive = $client->sendSync(new RouterOS\Request('/ip/hotspot/active/print'));
            foreach ($hsActive as $hs) {
                if ($hs->getType() !== RouterOS\Response::TYPE_DATA) continue;
                $uName = $hs->getProperty('user');
                if (!empty($uName)) {
                    $mac    = $hs->getProperty('mac-address') ?: '';
                    $ip     = $hs->getProperty('address') ?: '';
                    $uptime = $hs->getProperty('uptime') ?: '';
                    $tx     = (float)$hs->getProperty('bytes-in');  // Upload (Client in)
                    $rx     = (float)$hs->getProperty('bytes-out'); // Download (Client out)
                    $rawSid = $hs->getProperty('session-id') ?: '';
                    
                    $calcStartTime = date('Y-m-d H:i:s', time() - data_usage_uptime_to_seconds($uptime));
                    $sId = !empty($rawSid) && (strpos($rawSid, 'hs') === 0 || strpos($rawSid, 'hs_') === 0)
                        ? $rawSid 
                        : data_usage_generate_hotspot_session_id($uName, $mac, strtotime($calcStartTime));

                    $sessions[] = [
                        'username'        => $uName,
                        'fullname'        => '',
                        'session_id'      => $sId,
                        'service'         => 'Hotspot',
                        'router_id'       => $router->id,
                        'router_name'     => $router->name,
                        'ip_address'      => $ip,
                        'mac_address'     => $mac,
                        'start_time'      => $calcStartTime,
                        'uptime'          => $uptime,
                        'uptime_fmt'      => $uptime,
                        'uptime_seconds'  => data_usage_uptime_to_seconds($uptime),
                        'bytes_in'        => $tx,
                        'bytes_out'       => $rx,
                        'total_bytes'     => $tx + $rx,
                        'bytes_in_fmt'    => data_usage_format_bytes($tx),
                        'bytes_out_fmt'   => data_usage_format_bytes($rx),
                        'total_bytes_fmt' => data_usage_format_bytes($tx + $rx),
                        'last_seen'       => $now,
                        'status'          => 'Online'
                    ];
                }
            }
        } catch (Exception $eHs) {}

        // Batch fetch customer fullnames in 1 single fast query for online users only
        $activeUsernames = array_unique(array_filter(array_column($sessions, 'username')));
        if (!empty($activeUsernames)) {
            try {
                $custRows = ORM::for_table('tbl_customers')
                    ->select_many('username', 'fullname')
                    ->where_in('username', array_values($activeUsernames))
                    ->find_array();
                $custMap = [];
                foreach ($custRows as $cr) {
                    $custMap[strtolower($cr['username'])] = $cr['fullname'];
                }
                foreach ($sessions as &$s) {
                    $uLower = strtolower($s['username']);
                    $s['fullname'] = $custMap[$uLower] ?? '';
                }
                unset($s);
            } catch (Exception $eCust) {}
        }
    } catch (Exception $e) {}

    return $sessions;
}


function data_usage_get_dev_script()
{
    $tgSvg = '<svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" style="vertical-align: -2px; margin-right: 4px;"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
    $jsonSvg = json_encode($tgSvg);
    $duVer = htmlspecialchars(DATA_USAGE_PLUGIN_VERSION, ENT_QUOTES, 'UTF-8');

    return '<script>' . "\n"
        . '(function() {' . "\n"
        . '    var tgSvg = ' . $jsonSvg . ';' . "\n"
        . '    function injectFooterBrand() {' . "\n"
        . '        var footer = document.querySelector(".main-footer, .theme-footer, footer");' . "\n"
        . '        if (footer && !document.getElementById("du_footer_brand")) {' . "\n"
        . '            var span = document.createElement("span");' . "\n"
        . '            span.id = "du_footer_brand";' . "\n"
        . '            span.innerHTML = \'<b>Data Usage</b> | <a href="https://t.me/mhmd_3010" target="_blank" rel="noopener noreferrer">Mahmud</a> | V ' . $duVer . '\';' . "\n"
        . '            if (footer.classList.contains("main-footer")) {' . "\n"
        . '                if (window.getComputedStyle(footer).position === "static") footer.style.position = "relative";' . "\n"
        . '                span.style.cssText = "position:absolute; right:20px; top:50%; transform:translateY(-50%); z-index:2;";' . "\n"
        . '                footer.appendChild(span);' . "\n"
        . '            } else {' . "\n"
        . '                span.style.cssText = "margin-left:auto;";' . "\n"
        . '                footer.appendChild(span);' . "\n"
        . '            }' . "\n"
        . '        }' . "\n"
        . '    }' . "\n"
        . '    function initDevPopups() {' . "\n"
        . '        if (typeof Swal === "undefined") return;' . "\n"
        . '        if (!sessionStorage.getItem("dataUsageDevModalShown")) {' . "\n"
        . '            sessionStorage.setItem("dataUsageDevModalShown", "true");' . "\n"
        . '            Swal.fire({' . "\n"
        . '                title: "Advance Data Usage",' . "\n"
        . '                html: \'<div style="text-align:left; font-size:13px; color:#475569; margin-bottom:10px; background:#f8fafc; padding:12px 14px; border-radius:8px; border:1px solid #e2e8f0;">\' +' . "\n"
        . '                      \'<p style="margin-bottom:6px; color:#0f172a; font-weight:700;"><i class="fa fa-check-circle text-success"></i> Key Features:</p>\' +' . "\n"
        . '                      \'<ul style="padding-left:18px; margin-bottom:12px; line-height:1.5;">\' +' . "\n"
        . '                      \'<li><b>Direct RouterOS API:</b> Real-time active user tracking without polling delay</li>\' +' . "\n"
        . '                      \'<li><b>Hardware Accounting:</b> Strict on-up/on-down sync with interface timestamps</li>\' +' . "\n"
        . '                      \'<li><b>Multi-Router Live Monitor:</b> Instant reachability & connected counts</li>\' +' . "\n"
        . '                      \'</ul>\' +' . "\n"
        . '                      \'<div style="text-align:center; padding-top:6px; border-top:1px dashed #cbd5e1;">\' +' . "\n"
        . '                      \'<p style="font-size:11px; color:#64748b; margin-bottom:8px;">Developed by <b>Mahmud</b></p>\' +' . "\n"
        . '                      \'<a href="https://t.me/mhmd_3010" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info" style="border-radius:20px; padding:6px 18px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:6px; color:#fff;">\' +' . "\n"
        . '                      tgSvg + \' Telegram\' +' . "\n"
        . '                      \'</a>\' +' . "\n"
        . '                      \'</div>\' +' . "\n"
        . '                      \'</div>\',' . "\n"
        . '                showConfirmButton: false,' . "\n"
        . '                timer: 5000,' . "\n"
        . '                timerProgressBar: true,' . "\n"
        . '                allowOutsideClick: true,' . "\n"
        . '                backdrop: "rgba(0,0,0,0.6)",' . "\n"
        . '                didOpen: function(popup) {' . "\n"
        . '                    popup.addEventListener("mouseenter", Swal.stopTimer);' . "\n"
        . '                    popup.addEventListener("mouseleave", Swal.resumeTimer);' . "\n"
        . '                }' . "\n"
        . '            });' . "\n"
        . '        }' . "\n"
        . '        var triggerEgg = function() {' . "\n"
        . '            Swal.fire({' . "\n"
        . '                title: "Advance Data Usage",' . "\n"
        . '                html: \'<div style="text-align:center; padding:10px;">\' +' . "\n"
        . '                      \'<p style="font-size:14px; margin-bottom:16px;">Developed by <b>Mahmud</b></p>\' +' . "\n"
        . '                      \'<a href="https://t.me/mhmd_3010" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-sm" style="border-radius:20px; padding:6px 18px; display:inline-flex; align-items:center; justify-content:center; gap:6px; color:#fff;">\' +' . "\n"
        . '                      tgSvg + \' Telegram\' +' . "\n"
        . '                      \'</a>\' +' . "\n"
        . '                      \'</div>\',' . "\n"
        . '                showConfirmButton: true,' . "\n"
        . '                confirmButtonText: "Close"' . "\n"
        . '            });' . "\n"
        . '        };' . "\n"
        . '        var titles = document.querySelectorAll(".data-usage-header-title, h3, .panel-heading span, .panel-heading strong");' . "\n"
        . '        titles.forEach(function(el) {' . "\n"
        . '            var txt = (el.innerText || "").toLowerCase();' . "\n"
        . '            if (txt.indexOf("dashboard") !== -1 || txt.indexOf("data usage") !== -1 || txt.indexOf("history") !== -1 || txt.indexOf("router setup") !== -1 || txt.indexOf("logs") !== -1 || txt.indexOf("user") !== -1) {' . "\n"
        . '                el.style.cursor = "pointer";' . "\n"
        . '                el.title = "Developer Info";' . "\n"
        . '                el.addEventListener("click", triggerEgg);' . "\n"
        . '            }' . "\n"
        . '        });' . "\n"
        . '    }' . "\n"
        . '    function fixDataTablesLoader() {' . "\n"
        . '        var clearLoader = function() {' . "\n"
        . '            setTimeout(function() {' . "\n"
        . '                if (typeof window.hideTableLoading === "function") window.hideTableLoading();' . "\n"
        . '            }, 50);' . "\n"
        . '        };' . "\n"
        . '        document.addEventListener("click", function(e) {' . "\n"
        . '            if (e.target && e.target.closest && e.target.closest(".dataTables_paginate a")) {' . "\n"
        . '                clearLoader();' . "\n"
        . '            }' . "\n"
        . '        }, true);' . "\n"
        . '        if (window.jQuery) {' . "\n"
        . '            jQuery(document).on("draw.dt", clearLoader);' . "\n"
        . '        }' . "\n"
        . '    }' . "\n"
        . '    if (document.readyState === "loading") {' . "\n"
        . '        document.addEventListener("DOMContentLoaded", function() {' . "\n"
        . '            injectFooterBrand();' . "\n"
        . '            initDevPopups();' . "\n"
        . '            fixDataTablesLoader();' . "\n"
        . '        });' . "\n"
        . '    } else {' . "\n"
        . '        injectFooterBrand();' . "\n"
        . '        initDevPopups();' . "\n"
        . '        fixDataTablesLoader();' . "\n"
        . '    }' . "\n"
        . '})();' . "\n"
        . '</script>' . "\n";
}

function data_usage_view($template)
{
    global $ui;
    $html = $ui->fetch($template);
    $devScript = data_usage_get_dev_script();
    if (stripos($html, '</body>') !== false) {
        $html = str_ireplace('</body>', $devScript . '</body>', $html);
    } else {
        $html .= $devScript;
    }
    echo $html;
}

/**
 * 1. Admin Dashboard Controller: Router Monitor Cards & Direct API Live Sessions
 */
function data_usage_admin()
{
    global $ui;
    data_usage_require_admin();
    $admin = Admin::_info();
    data_usage_init_db();

    $ui->assign('_system_menu', 'plugin/data_usage_admin');
    $ui->assign('_admin', $admin);
    $ui->assign('_title', 'Data Usage - Dashboard');

    $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap.min.css">');
    $ui->assign('xfooter', '<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap.min.js"></script>');

    $filterRouter = isset($_POST['router']) ? (int)$_POST['router'] : (isset($_GET['router']) ? (int)$_GET['router'] : 0);
    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();

    $routerCards = [];
    $currentRouter = null;
    $currentRouterInfo = null;
    $sessionsFormatted = [];

    if ($filterRouter > 0) {
        // Specific Router selected: Allow access even if offline
        $currentRouter = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($filterRouter);
        if ($currentRouter) {
            $currentRouterInfo = data_usage_get_router_monitor_data($currentRouter);
            $routerIsOnline = !empty($currentRouterInfo['online']);

            // If router is online and user registry table is empty for this router, sync users right now
            $userCountInDb = ORM::for_table('tbl_data_usage_router_users')
                ->where('router_id', $currentRouter->id)
                ->count();
            if ($routerIsOnline && $userCountInDb == 0) {
                data_usage_sync_router_users($currentRouter->id);
            }

            // 1. Fetch live sessions directly (already populated with fullnames in batch)
            $liveSessions = $currentRouterInfo['live_sessions'] ?? [];
            if ($routerIsOnline && empty($liveSessions)) {
                $liveSessions = data_usage_get_direct_live_sessions($currentRouter);
            }

            // 2. Counts only on initial load; DataTables loads rows via server-side AJAX (&ajax=1)
            $liveMap = [];
            $onlineKeys = [];
            foreach ($liveSessions as $ls) {
                $key = strtolower($ls['username']) . '_' . strtolower($ls['service']);
                $liveMap[$key] = $ls;
                $liveMap[strtolower($ls['username'])] = $ls;
                $onlineKeys[strtolower($ls['username'])] = true;
            }
            $onlineCount = count($liveSessions);
            $totalUsersCount = (int) ORM::for_table('tbl_data_usage_router_users')
                ->where('router_id', $currentRouter->id)
                ->where_null('missing_since')
                ->count();
            if ($totalUsersCount < $onlineCount) {
                $totalUsersCount = $onlineCount;
            }
            $offlineCount = max(0, $totalUsersCount - $onlineCount);

            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                $draw = (int) ($_GET['draw'] ?? 1);
                $start = max(0, (int) ($_GET['start'] ?? 0));
                $length = (int) ($_GET['length'] ?? 10);
                if ($length <= 0 || $length > 200) {
                    $length = 10;
                }
                $searchVal = trim((string) ($_GET['search']['value'] ?? ''));
                $svcFilter = trim((string) ($_GET['columns'][1]['search']['value'] ?? ''));
                $statusFilter = trim((string) ($_GET['columns'][3]['search']['value'] ?? ''));
                $orderCol = (int) ($_GET['order'][0]['column'] ?? 0);
                $orderDir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

                $formatRow = function (array $s) {
                    $uEsc = htmlspecialchars((string) $s['username'], ENT_QUOTES, 'UTF-8');
                    $uUrl = urlencode((string) $s['username']);
                    $rId = (int) $s['router_id'];
                    $fnHtml = !empty($s['fullname']) ? '<br><small class="text-muted">' . htmlspecialchars((string) $s['fullname'], ENT_QUOTES, 'UTF-8') . '</small>' : '';
                    $colUser = '<a href="' . getUrl('plugin/data_usage_user&username=' . $uUrl . '&router_id=' . $rId) . '" style="font-weight: 600;">' . $uEsc . '</a>' . $fnHtml;

                    $svc = (string) ($s['service'] ?: 'PPPoE');
                    $colSvc = $svc === 'PPPoE'
                        ? '<span class="label label-primary">PPPoE</span>'
                        : '<span class="label label-warning">' . htmlspecialchars($svc, ENT_QUOTES, 'UTF-8') . '</span>';

                    $ipEsc = htmlspecialchars((string) ($s['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8');
                    $macEsc = htmlspecialchars((string) ($s['mac_address'] ?? '-'), ENT_QUOTES, 'UTF-8');
                    if ($s['status'] === 'Online') {
                        $colIpMac = '<span style="font-family: monospace; font-size: 11px;">' . $ipEsc . '</span>';
                        if (!empty($s['mac_address']) && $s['mac_address'] !== '-') {
                            $colIpMac .= '<br><span class="text-muted" style="font-family: monospace; font-size: 11px;">' . $macEsc . '</span>';
                        }
                    } else {
                        $colIpMac = (!empty($s['mac_address']) && $s['mac_address'] !== '-')
                            ? '<span class="text-muted" style="font-family: monospace; font-size: 11px;">' . $macEsc . '</span>'
                            : '<span class="text-muted">-</span>';
                    }

                    $colStatus = $s['status'] === 'Online'
                        ? '<span class="label label-success">Online</span>'
                        : '<span class="label label-default">Offline</span>';

                    $colUptime = $s['status'] === 'Online'
                        ? '<span class="text-primary" style="font-weight: 600;">' . htmlspecialchars((string) $s['uptime_fmt'], ENT_QUOTES, 'UTF-8') . '</span>'
                        : '<span class="text-muted">-</span>';

                    $colLastSeen = $s['status'] === 'Online'
                        ? '<span class="label label-success">Now</span>'
                        : '<small class="text-muted">' . htmlspecialchars((string) ($s['last_seen'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</small>';

                    $colIn = '<span class="text-info">' . htmlspecialchars((string) $s['bytes_in_fmt'], ENT_QUOTES, 'UTF-8') . '</span>';
                    $colOut = '<span class="text-success">' . htmlspecialchars((string) $s['bytes_out_fmt'], ENT_QUOTES, 'UTF-8') . '</span>';
                    $colTotal = '<strong>' . htmlspecialchars((string) $s['total_bytes_fmt'], ENT_QUOTES, 'UTF-8') . '</strong>';

                    $uJs = htmlspecialchars(json_encode((string) $s['username']), ENT_QUOTES, 'UTF-8');
                    $svcJs = htmlspecialchars(json_encode($svc), ENT_QUOTES, 'UTF-8');
                    $sidJs = htmlspecialchars(json_encode((string) ($s['session_id'] ?? '-')), ENT_QUOTES, 'UTF-8');

                    $colAct = '<div style="text-align: center; white-space: nowrap;">'
                        . '<button type="button" class="btn btn-xs btn-primary" onclick="refreshRow(' . $uJs . ', ' . $rId . ', ' . $svcJs . ', this)">Refresh</button> ';
                    if ($s['status'] === 'Online') {
                        $colAct .= '<button type="button" class="btn btn-xs btn-danger" onclick="disconnectUser(' . $sidJs . ', ' . $rId . ', ' . $uJs . ')">Disconnect</button> ';
                    }
                    $colAct .= '<a href="' . getUrl('plugin/data_usage_user&username=' . $uUrl . '&router_id=' . $rId) . '" class="btn btn-xs btn-info">History</a></div>';

                    $rowId = 'session_row_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $s['username']);
                    return [
                        'DT_RowId' => $rowId,
                        $colUser, $colSvc, $colIpMac, $colStatus, $colUptime, $colLastSeen, $colIn, $colOut, $colTotal, $colAct,
                    ];
                };

                if (strcasecmp($statusFilter, 'Online') === 0) {
                    $filtered = [];
                    foreach ($liveSessions as $ls) {
                        if ($svcFilter !== '' && strcasecmp((string) $ls['service'], $svcFilter) !== 0) {
                            continue;
                        }
                        if ($searchVal !== '') {
                            $hay = strtolower(($ls['username'] ?? '') . ' ' . ($ls['fullname'] ?? '') . ' ' . ($ls['ip_address'] ?? '') . ' ' . ($ls['mac_address'] ?? ''));
                            if (strpos($hay, strtolower($searchVal)) === false) {
                                continue;
                            }
                        }
                        $filtered[] = [
                            'username'        => $ls['username'],
                            'fullname'        => $ls['fullname'] ?? '',
                            'session_id'      => $ls['session_id'] ?? '-',
                            'service'         => $ls['service'] ?: 'PPPoE',
                            'router_id'       => $currentRouter->id,
                            'ip_address'      => $ls['ip_address'] ?? '-',
                            'mac_address'     => $ls['mac_address'] ?? '-',
                            'last_seen'       => $ls['uptime_fmt'] ?? '-',
                            'uptime_fmt'      => $ls['uptime_fmt'] ?? '-',
                            'uptime_seconds'  => $ls['uptime_seconds'] ?? data_usage_uptime_to_seconds($ls['uptime_fmt'] ?? ''),
                            'bytes_in'        => (float) ($ls['bytes_in'] ?? 0),
                            'bytes_out'       => (float) ($ls['bytes_out'] ?? 0),
                            'total_bytes'     => (float) ($ls['total_bytes'] ?? 0),
                            'bytes_in_fmt'    => $ls['bytes_in_fmt'] ?? '0 B',
                            'bytes_out_fmt'   => $ls['bytes_out_fmt'] ?? '0 B',
                            'total_bytes_fmt' => $ls['total_bytes_fmt'] ?? '0 B',
                            'status'          => 'Online',
                        ];
                    }
                    usort($filtered, function ($a, $b) use ($orderCol, $orderDir) {
                        $cmp = 0;
                        switch ($orderCol) {
                            case 1: $cmp = strcasecmp($a['service'], $b['service']); break;
                            case 4: $cmp = $a['uptime_seconds'] <=> $b['uptime_seconds']; break;
                            case 6: $cmp = $a['bytes_in'] <=> $b['bytes_in']; break;
                            case 7: $cmp = $a['bytes_out'] <=> $b['bytes_out']; break;
                            case 8: $cmp = $a['total_bytes'] <=> $b['total_bytes']; break;
                            default: $cmp = strcasecmp($a['username'], $b['username']); break;
                        }
                        return $orderDir === 'desc' ? -$cmp : $cmp;
                    });
                    $recordsFiltered = count($filtered);
                    $pageRows = array_slice($filtered, $start, $length);
                    $custMap = data_usage_batch_customers(array_column($pageRows, 'username'));
                    $outData = [];
                    foreach ($pageRows as $pr) {
                        if (empty($pr['fullname'])) {
                            $pr['fullname'] = $custMap[strtolower($pr['username'])] ?? '';
                        }
                        $outData[] = $formatRow($pr);
                    }
                    echo json_encode([
                        'draw' => $draw,
                        'recordsTotal' => $totalUsersCount,
                        'recordsFiltered' => $recordsFiltered,
                        'data' => $outData,
                    ]);
                    exit;
                }

                // All or Offline: query tbl_data_usage_router_users with SQL LIMIT/OFFSET
                $where = ["router_id = ?", "missing_since IS NULL"];
                $params = [$currentRouter->id];
                if ($svcFilter !== '') {
                    $where[] = "service = ?";
                    $params[] = $svcFilter;
                }
                if ($searchVal !== '') {
                    $where[] = "(username LIKE ? OR mac_address LIKE ?)";
                    $params[] = '%' . $searchVal . '%';
                    $params[] = '%' . $searchVal . '%';
                }
                if (strcasecmp($statusFilter, 'Offline') === 0 && !empty($onlineKeys)) {
                    $onlineNames = array_keys($onlineKeys);
                    if (count($onlineNames) <= 2000) {
                        $placeholders = implode(',', array_fill(0, count($onlineNames), '?'));
                        $where[] = "LOWER(username) NOT IN ($placeholders)";
                        foreach ($onlineNames as $onName) {
                            $params[] = $onName;
                        }
                    }
                }
                $whereSql = implode(' AND ', $where);
                $db = ORM::get_db();
                $cntStmt = $db->prepare("SELECT COUNT(*) FROM tbl_data_usage_router_users WHERE $whereSql");
                $cntStmt->execute($params);
                $recordsFiltered = (int) $cntStmt->fetchColumn();

                $sortColSql = $orderCol === 1 ? 'service' : 'username';
                $sortDirSql = $orderDir === 'desc' ? 'DESC' : 'ASC';
                $dataStmt = $db->prepare("SELECT username, service, mac_address FROM tbl_data_usage_router_users WHERE $whereSql ORDER BY $sortColSql $sortDirSql LIMIT $length OFFSET $start");
                $dataStmt->execute($params);
                $ruRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                $pageUsernames = array_column($ruRows, 'username');
                $custMap = data_usage_batch_customers($pageUsernames);
                $lastSeenMap = [];
                if (!empty($pageUsernames)) {
                    $ph = implode(',', array_fill(0, count($pageUsernames), '?'));
                    $lsStmt = $db->prepare("SELECT username, MAX(COALESCE(closed_time, last_seen)) AS last_time FROM tbl_data_usage_sessions WHERE router_id = ? AND username IN ($ph) GROUP BY username");
                    $lsStmt->execute(array_merge([$currentRouter->id], $pageUsernames));
                    foreach ($lsStmt->fetchAll(PDO::FETCH_ASSOC) as $ss) {
                        $lastSeenMap[strtolower($ss['username'])] = $ss['last_time'];
                    }
                }

                $outData = [];
                foreach ($ruRows as $ru) {
                    $uName = $ru['username'];
                    $svc = $ru['service'] ?: 'PPPoE';
                    $key = strtolower($uName) . '_' . strtolower($svc);
                    $fullname = $custMap[strtolower($uName)] ?? '';
                    if (isset($liveMap[$key]) || isset($liveMap[strtolower($uName)])) {
                        $live = $liveMap[$key] ?? $liveMap[strtolower($uName)];
                        $row = [
                            'username'        => $uName,
                            'fullname'        => $fullname ?: ($live['fullname'] ?? ''),
                            'session_id'      => $live['session_id'],
                            'service'         => $live['service'] ?: $svc,
                            'router_id'       => $currentRouter->id,
                            'ip_address'      => $live['ip_address'],
                            'mac_address'     => $live['mac_address'] ?: ($ru['mac_address'] ?: '-'),
                            'last_seen'       => $live['uptime_fmt'],
                            'uptime_fmt'      => $live['uptime_fmt'],
                            'bytes_in_fmt'    => $live['bytes_in_fmt'],
                            'bytes_out_fmt'   => $live['bytes_out_fmt'],
                            'total_bytes_fmt' => $live['total_bytes_fmt'],
                            'status'          => 'Online',
                        ];
                    } else {
                        $row = [
                            'username'        => $uName,
                            'fullname'        => $fullname,
                            'session_id'      => '-',
                            'service'         => $svc,
                            'router_id'       => $currentRouter->id,
                            'ip_address'      => '-',
                            'mac_address'     => !empty($ru['mac_address']) ? $ru['mac_address'] : '-',
                            'last_seen'       => $lastSeenMap[strtolower($uName)] ?? '-',
                            'uptime_fmt'      => '-',
                            'bytes_in_fmt'    => '0 B',
                            'bytes_out_fmt'   => '0 B',
                            'total_bytes_fmt' => '0 B',
                            'status'          => 'Offline',
                        ];
                    }
                    $outData[] = $formatRow($row);
                }

                echo json_encode([
                    'draw' => $draw,
                    'recordsTotal' => $totalUsersCount,
                    'recordsFiltered' => $recordsFiltered,
                    'data' => $outData,
                ]);
                exit;
            }

            $sessionsFormatted = [];
            $ui->assign('routerIsOnline', $routerIsOnline);
            $ui->assign('onlineCount', $onlineCount);
            $ui->assign('offlineCount', $offlineCount);
            $ui->assign('totalUsersCount', $totalUsersCount);
        } else {
            r2(getUrl('plugin/data_usage_admin'), 'e', 'Router not found.');
        }
    } else {
        // All Routers Overview: Fast instant render from DB without blocking page load on RouterOS API
        foreach ($routers as $r) {
            $lastSync = data_usage_get_config('data_usage_last_sync_' . $r->id, '');
            $lastSyncFmt = !empty($lastSync) ? date('d M Y H:i', strtotime($lastSync)) : 'Never';

            $dbActiveCount = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $r->id)
                ->where('status', 'active')
                ->count();

            $routerCards[] = [
                'id'           => $r->id,
                'name'         => $r->name,
                'ip_address'   => $r->ip_address,
                'online'       => true,
                'online_count' => $dbActiveCount,
                'last_sync'    => $lastSyncFmt,
                'is_loading'   => true
            ];
        }
    }

    $ui->assign('routers', $routers);
    $ui->assign('filterRouter', $filterRouter);
    $ui->assign('routerCards', $routerCards);
    $ui->assign('currentRouter', $currentRouter);
    $ui->assign('currentRouterInfo', $currentRouterInfo);
    $ui->assign('sessions', $sessionsFormatted);

    $ui->assign('cronInfo', data_usage_get_cron_info());
    $ui->assign('cronCommand', '*/5 * * * * php ' . __DIR__ . '/cron_sync.php >/dev/null 2>&1');
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    data_usage_view('data_usage/dashboard.tpl');
}

/**
 * 2. Daily & Historical Usage Report
 */
function data_usage_history()
{
    global $ui;
    data_usage_require_admin();
    $admin = Admin::_info();
    data_usage_init_db();

    $ui->assign('_system_menu', 'plugin/data_usage_history');
    $ui->assign('_admin', $admin);
    $ui->assign('_title', 'Data Usage - Daily History');

    $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap.min.css">');
    $ui->assign('xfooter', '<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap.min.js"></script>');

    $startDate = isset($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET['start_date'])) ? trim($_GET['start_date']) : date('Y-m-01');
    $endDate = isset($_GET['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET['end_date'])) ? trim($_GET['end_date']) : date('Y-m-d');
    $filterRouter = isset($_POST['router']) ? (int)$_POST['router'] : (isset($_GET['router']) ? (int)$_GET['router'] : 0);
    $filterService = isset($_POST['service']) ? trim($_POST['service']) : (isset($_GET['service']) ? trim($_GET['service']) : '');
    $search = isset($_POST['search']) ? trim($_POST['search']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 30;

    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();

    // Query builder for aggregates
    $query = ORM::for_table('tbl_data_usage_daily')
        ->where_gte('usage_date', $startDate)
        ->where_lte('usage_date', $endDate);

    if ($filterRouter > 0) $query->where('router_id', $filterRouter);
    if (!empty($filterService)) $query->where('service', $filterService);
    if (!empty($search)) {
        $query->where_raw('(username LIKE ? OR mac_address LIKE ?)', ["%$search%", "%$search%"]);
    }

    $allowedSort = [
        'date'        => 'usage_date',
        'username'    => 'username',
        'service'     => 'service',
        'router'      => 'router_id',
        'upload'      => 'bytes_in',
        'download'    => 'bytes_out',
        'total'       => 'total_bytes'
    ];
    $rawOrder = $_POST['order'] ?? $_GET['order'] ?? 'date';
    $rawOrderBy = $_POST['orderby'] ?? $_GET['orderby'] ?? 'desc';
    $order = isset($allowedSort[$rawOrder]) ? $rawOrder : 'date';
    $orderby = in_array(strtolower($rawOrderBy), ['asc', 'desc']) ? strtolower($rawOrderBy) : 'desc';

    $dbSortCol = $allowedSort[$order];
    if ($orderby === 'asc') {
        $query->order_by_asc($dbSortCol);
    } else {
        $query->order_by_desc($dbSortCol);
    }

    $routerMap = [];
    foreach ($routers as $r) {
        $routerMap[$r->id] = $r->name;
    }

    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json; charset=utf-8');
        $draw = (int) ($_GET['draw'] ?? 1);
        $start = max(0, (int) ($_GET['start'] ?? 0));
        $length = (int) ($_GET['length'] ?? 10);
        if ($length <= 0 || $length > 200) {
            $length = 10;
        }
        $dtSearch = trim((string) ($_GET['search']['value'] ?? ''));
        $colIdx = (int) ($_GET['order'][0]['column'] ?? 0);
        $colDir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $colMap = [
            0 => 'usage_date',
            1 => 'username',
            2 => 'service',
            3 => 'router_id',
            4 => 'mac_address',
            5 => 'bytes_in',
            6 => 'bytes_out',
            7 => 'total_bytes',
        ];
        $sortSql = ($colMap[$colIdx] ?? 'usage_date') . ' ' . $colDir;

        $where = ["usage_date >= ?", "usage_date <= ?"];
        $params = [$startDate, $endDate];
        if ($filterRouter > 0) {
            $where[] = "router_id = ?";
            $params[] = $filterRouter;
        }
        if (!empty($filterService)) {
            $where[] = "service = ?";
            $params[] = $filterService;
        }
        $effectiveSearch = $dtSearch !== '' ? $dtSearch : $search;
        if ($effectiveSearch !== '') {
            $where[] = "(username LIKE ? OR mac_address LIKE ?)";
            $params[] = "%$effectiveSearch%";
            $params[] = "%$effectiveSearch%";
        }
        $whereSql = implode(' AND ', $where);
        $db = ORM::get_db();

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM tbl_data_usage_daily WHERE usage_date >= ? AND usage_date <= ?");
        $totalStmt->execute([$startDate, $endDate]);
        $recordsTotal = (int) $totalStmt->fetchColumn();

        $cntStmt = $db->prepare("SELECT COUNT(*) FROM tbl_data_usage_daily WHERE $whereSql");
        $cntStmt->execute($params);
        $recordsFiltered = (int) $cntStmt->fetchColumn();

        $dataStmt = $db->prepare("SELECT usage_date, router_id, username, service, mac_address, bytes_in, bytes_out, total_bytes FROM tbl_data_usage_daily WHERE $whereSql ORDER BY $sortSql LIMIT $length OFFSET $start");
        $dataStmt->execute($params);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $custMap = data_usage_batch_customers(array_column($rows, 'username'));
        $outData = [];
        foreach ($rows as $h) {
            $uEsc = htmlspecialchars((string) $h['username'], ENT_QUOTES, 'UTF-8');
            $uUrl = urlencode((string) $h['username']);
            $fullname = $custMap[strtolower($h['username'])] ?? '';
            $fnHtml = $fullname !== '' ? '<br><small class="text-muted">' . htmlspecialchars((string) $fullname, ENT_QUOTES, 'UTF-8') . '</small>' : '';
            $svc = (string) ($h['service'] ?: 'PPPoE');
            $svcHtml = $svc === 'PPPoE'
                ? '<span class="label label-primary">PPPoE</span>'
                : '<span class="label label-warning">' . htmlspecialchars($svc, ENT_QUOTES, 'UTF-8') . '</span>';
            $rName = htmlspecialchars((string) ($routerMap[$h['router_id']] ?? ('Router #' . $h['router_id'])), ENT_QUOTES, 'UTF-8');
            $macHtml = '<span style="font-family: monospace; font-size: 11px;">' . htmlspecialchars((string) $h['mac_address'], ENT_QUOTES, 'UTF-8') . '</span>';

            $outData[] = [
                htmlspecialchars((string) $h['usage_date'], ENT_QUOTES, 'UTF-8'),
                '<a href="' . getUrl('plugin/data_usage_user&username=' . $uUrl) . '" style="font-weight: 600;">' . $uEsc . '</a>' . $fnHtml,
                $svcHtml,
                $rName,
                $macHtml,
                '<span class="text-info">' . htmlspecialchars(data_usage_format_bytes($h['bytes_in']), ENT_QUOTES, 'UTF-8') . '</span>',
                '<span class="text-success">' . htmlspecialchars(data_usage_format_bytes($h['bytes_out']), ENT_QUOTES, 'UTF-8') . '</span>',
                '<strong>' . htmlspecialchars(data_usage_format_bytes($h['total_bytes']), ENT_QUOTES, 'UTF-8') . '</strong>',
                '<div class="text-center"><a href="' . getUrl('plugin/data_usage_user&username=' . $uUrl) . '" class="btn btn-xs btn-info">Details</a></div>',
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $outData,
        ]);
        exit;
    }

    // Totals in selected range (fast SQL SUM)
    $rangeSumSql = "SELECT SUM(bytes_in) as total_in, SUM(bytes_out) as total_out, SUM(total_bytes) as total_all 
                    FROM tbl_data_usage_daily WHERE usage_date >= :s AND usage_date <= :e";
    $rangeParams = ['s' => $startDate, 'e' => $endDate];
    if ($filterRouter > 0) {
        $rangeSumSql .= " AND router_id = :r";
        $rangeParams['r'] = $filterRouter;
    }
    if (!empty($filterService)) {
        $rangeSumSql .= " AND service = :svc";
        $rangeParams['svc'] = $filterService;
    }
    if (!empty($search)) {
        $rangeSumSql .= " AND (username LIKE :srch OR mac_address LIKE :srch)";
        $rangeParams['srch'] = "%$search%";
    }
    $rangeRow = ORM::for_table('tbl_data_usage_daily')->raw_query($rangeSumSql, $rangeParams)->find_one();
    $rangeTotal = $rangeRow ? (float)$rangeRow->total_all : 0;
    $rangeIn = $rangeRow ? (float)$rangeRow->total_in : 0;
    $rangeOut = $rangeRow ? (float)$rangeRow->total_out : 0;

    $historyFormatted = [];

    $ui->assign('routers', $routers);
    $ui->assign('startDate', $startDate);
    $ui->assign('endDate', $endDate);
    $ui->assign('filterRouter', $filterRouter);
    $ui->assign('filterService', $filterService);
    $ui->assign('search', $search);
    $ui->assign('order', $order);
    $ui->assign('orderby', $orderby);
    $ui->assign('page', $page);
    $ui->assign('totalPages', $totalPages);
    $ui->assign('totalRecords', $totalRecords);
    $ui->assign('history', $historyFormatted);
    $ui->assign('rangeTotalFmt', data_usage_format_bytes($rangeTotal));
    $ui->assign('rangeInFmt', data_usage_format_bytes($rangeIn));
    $ui->assign('rangeOutFmt', data_usage_format_bytes($rangeOut));
    $ui->assign('cronInfo', data_usage_get_cron_info());

    data_usage_view('data_usage/history.tpl');
}

/**
 * 3. Individual User Detailed Sessions Drilldown
 */
function data_usage_user()
{
    global $ui;
    data_usage_require_admin();
    $admin = Admin::_info();
    data_usage_init_db();

    $username = trim($_POST['username'] ?? $_GET['username'] ?? '');
    $routerId = (int)($_POST['router_id'] ?? $_GET['router_id'] ?? $_POST['router'] ?? $_GET['router'] ?? 0);

    if (empty($username)) {
        r2(getUrl('plugin/data_usage_admin'), 'e', 'Username is required');
    }

    $targetRouter = null;
    if ($routerId > 0) {
        $targetRouter = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
    }

    $pageTitle = 'Data Usage - User: ' . htmlspecialchars($username);
    if ($targetRouter) {
        $pageTitle .= ' (' . htmlspecialchars($targetRouter->name) . ')';
    }

    $ui->assign('_system_menu', 'plugin/data_usage_admin');
    $ui->assign('_admin', $admin);
    $ui->assign('_title', $pageTitle);

    $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap.min.css">');
    $ui->assign('xfooter', '<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap.min.js"></script>');

    $customer = ORM::for_table('tbl_customers')->where('username', $username)->find_one();

    $allowedSort = [
        'start_time'  => 'start_time',
        'closed_time' => 'closed_time',
        'upload'      => 'bytes_in',
        'download'    => 'bytes_out',
        'total'       => 'total_bytes',
        'service'     => 'service',
        'status'      => 'status'
    ];
    $rawOrder = $_POST['order'] ?? $_GET['order'] ?? 'start_time';
    $rawOrderBy = $_POST['orderby'] ?? $_GET['orderby'] ?? 'desc';
    $order = isset($allowedSort[$rawOrder]) ? $rawOrder : 'start_time';
    $orderby = in_array(strtolower($rawOrderBy), ['asc', 'desc']) ? strtolower($rawOrderBy) : 'desc';

    // Scoped session logs query
    $sessQuery = ORM::for_table('tbl_data_usage_sessions')->where('username', $username);
    if ($routerId > 0) {
        $sessQuery->where('router_id', $routerId);
    }
    $dbSortCol = $allowedSort[$order];
    if ($orderby === 'asc') {
        $sessQuery->order_by_asc($dbSortCol);
    } else {
        $sessQuery->order_by_desc($dbSortCol);
    }

    $sessions = $sessQuery->limit(100)->find_many();

    $sessionsFormatted = [];
    $allTimeBytes = 0;
    foreach ($sessions as $s) {
        $item = $s->as_array();
        $item['bytes_in_fmt'] = data_usage_format_bytes($s->bytes_in);
        $item['bytes_out_fmt'] = data_usage_format_bytes($s->bytes_out);
        $item['total_bytes_fmt'] = data_usage_format_bytes($s->total_bytes);

        $duration = !empty($s->uptime) ? data_usage_format_uptime(data_usage_uptime_to_seconds($s->uptime)) : data_usage_format_uptime(max(0, strtotime($s->closed_time ?: date('Y-m-d H:i:s')) - strtotime($s->start_time)));
        $item['duration_fmt'] = $duration;
        $allTimeBytes += (float)$s->total_bytes;

        $sessionsFormatted[] = $item;
    }

    // Daily summary for this user (last 30 days) - strictly scoped by router_id
    $dailyQuery = ORM::for_table('tbl_data_usage_daily')->where('username', $username);
    if ($routerId > 0) {
        $dailyQuery->where('router_id', $routerId);
    }
    $dailySummary = $dailyQuery->order_by_desc('usage_date')->limit(30)->find_many();

    $dailyFormatted = [];
    foreach ($dailySummary as $d) {
        $dItem = $d->as_array();
        $dItem['bytes_in_fmt'] = data_usage_format_bytes($d->bytes_in);
        $dItem['bytes_out_fmt'] = data_usage_format_bytes($d->bytes_out);
        $dItem['total_bytes_fmt'] = data_usage_format_bytes($d->total_bytes);
        $dailyFormatted[] = $dItem;
    }

    // Find default router and service for this user
    $latestQuery = ORM::for_table('tbl_data_usage_sessions')->where('username', $username);
    if ($routerId > 0) {
        $latestQuery->where('router_id', $routerId);
    }
    $latestSession = $latestQuery->order_by_desc('last_seen')->find_one();
    $userRouterId = $routerId > 0 ? $routerId : ($latestSession ? $latestSession->router_id : 0);
    $userService = $latestSession ? $latestSession->service : 'PPPoE';

    if ($routerId > 0) {
        $rUser = ORM::for_table('tbl_data_usage_router_users')
            ->where('router_id', $routerId)
            ->where('username', $username)
            ->find_one();
        if ($rUser && !empty($rUser->service)) {
            $userService = $rUser->service;
        }
    }

    // Find active session for this user strictly on this router
    $activeQuery = ORM::for_table('tbl_data_usage_sessions')
        ->where('username', $username)
        ->where('status', 'active');
    if ($routerId > 0) {
        $activeQuery->where('router_id', $routerId);
    }
    $activeSession = $activeQuery->order_by_desc('last_seen')->find_one();
    $activeSessionId = $activeSession ? $activeSession->session_id : '';
    $activeRouterId = $activeSession ? $activeSession->router_id : $userRouterId;
    $activeService = $activeSession ? $activeSession->service : $userService;
    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();

    // Monthly usage calculation (excluding today, as it is still running)
    $monthStart = date('Y-m-01');
    $today = date('Y-m-d');
    $monthlyQuery = ORM::for_table('tbl_data_usage_daily')
        ->where('username', $username)
        ->where_gte('usage_date', $monthStart)
        ->where_lt('usage_date', $today);
    if ($routerId > 0) {
        $monthlyQuery->where('router_id', $routerId);
    }
    $monthlySum = (float)($monthlyQuery->sum('total_bytes') ?: 0);
    $monthlyBytesFmt = data_usage_format_bytes($monthlySum);

    $ui->assign('username', $username);
    $ui->assign('routerId', $routerId);
    $ui->assign('targetRouter', $targetRouter);
    $ui->assign('customer', $customer);
    $ui->assign('order', $order);
    $ui->assign('orderby', $orderby);
    $ui->assign('sessions', $sessionsFormatted);
    $ui->assign('dailySummary', $dailyFormatted);
    $ui->assign('allTimeBytesFmt', data_usage_format_bytes($allTimeBytes));
    $ui->assign('monthlyBytesFmt', $monthlyBytesFmt);
    $ui->assign('currentMonthName', date('F'));
    $ui->assign('cronInfo', data_usage_get_cron_info());
    $ui->assign('routers', $routers);
    $ui->assign('userRouterId', $userRouterId);
    $ui->assign('userService', $userService);
    $ui->assign('activeSessionId', $activeSessionId);
    $ui->assign('activeRouterId', $activeRouterId);
    $ui->assign('activeService', $activeService);
    $ui->assign('trafficPollMs', 200);
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());

    data_usage_view('data_usage/user_details.tpl');
}

/**
 * 4. MikroTik Script Generator & Setup Guide
 */
function data_usage_scripts()
{
    global $ui;
    data_usage_require_admin();
    $admin = Admin::_info();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_traffic_settings'])) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('plugin/data_usage_scripts'), 'e', 'Security token expired. Please try again.');
        }
        if (isset($_POST['log_retention_days'])) {
            $retention = max(0, min(365, (int)$_POST['log_retention_days']));
            data_usage_set_config('data_usage_log_retention_days', (string)$retention);
        }
        r2(getUrl('plugin/data_usage_scripts'), 's', 'Settings updated successfully.');
    }

    $ui->assign('_system_menu', 'plugin/data_usage_admin');
    $ui->assign('_admin', $admin);
    $ui->assign('_title', 'Data Usage - Settings & MikroTik Setup');

    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    $selectedRouterId = isset($_GET['router_id']) ? (int)$_GET['router_id'] : ($routers[0]->id ?? 1);

    $appUrl = rtrim(APP_URL, '/');
    $webhookUrl = data_usage_webhook_url($appUrl);

    $targetRouter = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($selectedRouterId);
    $pppProfiles = [];
    $hsProfiles = [];
    $routerOnline = false;

    if ($targetRouter) {
        try {
            $client = data_usage_get_mikrotik_client($targetRouter, 6.0);
            if ($client) {
                $routerOnline = true;
                // 1. Fetch PPPoE profiles
                try {
                    $res = $client->sendSync(new RouterOS\Request('/ppp/profile/print'));
                    foreach ($res as $p) {
                        $pName = (string)$p->getProperty('name');
                        if (empty($pName)) continue;
                        $onUp = (string)$p->getProperty('on-up');
                        $onDown = (string)$p->getProperty('on-down');
                        $installed = (strpos($onUp, 'data_usage_webhook') !== false || strpos($onUp, 'dataUsage') !== false);
                        $pppProfiles[] = [
                            'name'      => $pName,
                            'installed' => $installed,
                            'on_up'     => $onUp,
                            'on_down'   => $onDown
                        ];
                    }
                } catch (Exception $eP) {}

                // 2. Fetch Hotspot user profiles
                try {
                    $res = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
                    foreach ($res as $p) {
                        $pName = (string)$p->getProperty('name');
                        if (empty($pName)) continue;
                        $onLogin = (string)$p->getProperty('on-login');
                        $onLogout = (string)$p->getProperty('on-logout');
                        $installed = (strpos($onLogin, 'data_usage_webhook') !== false || strpos($onLogin, 'dataUsage') !== false);
                        $hsProfiles[] = [
                            'name'      => $pName,
                            'installed' => $installed,
                            'on_login'  => $onLogin,
                            'on_logout' => $onLogout
                        ];
                    }
                } catch (Exception $eH) {}
            }
        } catch (Exception $e) {}
    }

    $ui->assign('routers', $routers);
    $ui->assign('selectedRouterId', $selectedRouterId);
    $ui->assign('targetRouter', $targetRouter);
    $ui->assign('routerOnline', $routerOnline);
    $ui->assign('pppProfiles', $pppProfiles);
    $ui->assign('hsProfiles', $hsProfiles);
    $ui->assign('webhookUrl', $webhookUrl);
    $ui->assign('appUrl', $appUrl);
    $ui->assign('logRetentionDays', (int)data_usage_get_config('data_usage_log_retention_days', '30'));
    $ui->assign('cronInfo', data_usage_get_cron_info());
    $ui->assign('cronCommand', '*/5 * * * * php ' . __DIR__ . '/cron_sync.php >/dev/null 2>&1');
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());

    data_usage_view('data_usage/scripts.tpl');
}

/**
 * 4.1 Manage Profile Scripts (Selective Apply / Remove on PPP & Hotspot Profiles)
 */
function data_usage_manage_profile_scripts()
{
    data_usage_require_admin();
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'Security token expired. Please try again.');
    }

    $routerId = (int)($_POST['router_id'] ?? 0);
    $action = $_POST['action'] ?? 'install'; // 'install' or 'remove'
    $selectedPpp = (array)($_POST['ppp_profiles'] ?? []);
    $selectedHs = (array)($_POST['hs_profiles'] ?? []);

    if ($routerId <= 0) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'Target router required.');
    }

    if (empty($selectedPpp) && empty($selectedHs)) {
        r2(getUrl('plugin/data_usage_scripts&router_id=' . $routerId), 'w', 'Please select at least one profile.');
    }

    $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
    if (!$router) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'Router not found.');
    }

    $client = data_usage_get_mikrotik_client($router, 7.0);
    if (!$client) {
        r2(getUrl('plugin/data_usage_scripts&router_id=' . $routerId), 'e', 'Cannot connect to router via API.');
    }

    $appUrl = rtrim(APP_URL, '/');
    $webhookUrl = data_usage_webhook_url($appUrl);

    // Script definitions
    $pppOnUp = ':delay 1s;' . "\n"
        . ':local routerId "' . $routerId . '";' . "\n"
        . ':local serverUrl "' . $webhookUrl . '";' . "\n"
        . ':local sid "";' . "\n"
        . ':foreach s in=[/ppp active find where name=$user] do={ :set sid [/ppp active get $s session-id]; }' . "\n"
        . ':if ($sid = "") do={ :set sid $user; };' . "\n"
        . ':local callerId $"caller-id";' . "\n"
        . ':local remoteIp $"remote-address";' . "\n"
        . ':log info ("dataUsage [PPPoE on-up]: User " . $user . " connected (IP: " . $remoteIp . ", MAC: " . $callerId . ", SessionID: " . $sid . ")");' . "\n"
        . '/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=PPPoE&username=$user&session_id=$sid&mac=$callerId&ip=$remoteIp" keep-result=no;';

    $pppOnDown = ':local routerId "' . $routerId . '";' . "\n"
        . ':local serverUrl "' . $webhookUrl . '";' . "\n"
        . ':local secret [/ppp secret find where name=$user];' . "\n"
        . ':local loggedOut [/ppp secret get $secret last-logged-out];' . "\n"
        . ':local discReason [/ppp secret get $secret last-disconnect-reason];' . "\n"
        . ':log info ("dataUsage [PPPoE on-down]: User " . $user . " disconnected (" . $discReason . ")");' . "\n"
        . '/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=PPPoE&username=$user&closed_time=$loggedOut&close_reason=$discReason" keep-result=no;';

    $hsOnLogin = ':local routerId "' . $routerId . '";' . "\n"
        . ':local serverUrl "' . $webhookUrl . '";' . "\n"
        . ':log info ("dataUsage [Hotspot on-login]: User " . $user . " logged in (IP: " . $address . ", MAC: " . $"mac-address" . ")");' . "\n"
        . '/tool fetch url=$serverUrl http-method=post http-data="action=session-start&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address" keep-result=no;';

    $hsOnLogout = ':local routerId "' . $routerId . '";' . "\n"
        . ':local serverUrl "' . $webhookUrl . '";' . "\n"
        . ':log info ("dataUsage [Hotspot on-logout]: User " . $user . " logged out (Cause: " . $cause . ")");' . "\n"
        . '/tool fetch url=$serverUrl http-method=post http-data="action=session-end&router=$routerId&service=Hotspot&username=$user&mac=$"mac-address"&ip=$address&uptime_secs=$"uptime-secs"&bytes_in=$"bytes-in"&bytes_out=$"bytes-out"&close_reason=$cause" keep-result=no;';

    $updatedCount = 0;

    // 1. Process PPPoE Profiles
    if (!empty($selectedPpp)) {
        try {
            $res = $client->sendSync(new RouterOS\Request('/ppp/profile/print'));
            foreach ($res as $p) {
                $pName = (string)$p->getProperty('name');
                if (in_array($pName, $selectedPpp)) {
                    $setReq = new RouterOS\Request('/ppp/profile/set');
                    $setReq->setArgument('numbers', $p->getProperty('.id'));
                    if ($action === 'install') {
                        $setReq->setArgument('on-up', $pppOnUp);
                        $setReq->setArgument('on-down', $pppOnDown);
                    } else {
                        $setReq->setArgument('on-up', '');
                        $setReq->setArgument('on-down', '');
                    }
                    $client->sendSync($setReq);
                    $updatedCount++;
                }
            }
        } catch (Exception $eP) {}
    }

    // 2. Process Hotspot User Profiles
    if (!empty($selectedHs)) {
        try {
            $res = $client->sendSync(new RouterOS\Request('/ip/hotspot/user/profile/print'));
            foreach ($res as $p) {
                $pName = (string)$p->getProperty('name');
                if (in_array($pName, $selectedHs)) {
                    $setReq = new RouterOS\Request('/ip/hotspot/user/profile/set');
                    $setReq->setArgument('numbers', $p->getProperty('.id'));
                    if ($action === 'install') {
                        $setReq->setArgument('on-login', $hsOnLogin);
                        $setReq->setArgument('on-logout', $hsOnLogout);
                    } else {
                        $setReq->setArgument('on-login', '');
                        $setReq->setArgument('on-logout', '');
                    }
                    $client->sendSync($setReq);
                    $updatedCount++;
                }
            }
        } catch (Exception $eH) {}
    }

    $msg = ($action === 'install') 
        ? 'Successfully installed scripts on ' . $updatedCount . ' profile(s).'
        : 'Successfully removed scripts from ' . $updatedCount . ' profile(s).';

    r2(getUrl('plugin/data_usage_scripts&router_id=' . $routerId), 's', $msg);
}

/**
 * 4.2 Event & Poller Logs Viewer
 */
function data_usage_logs()
{
    global $ui;
    data_usage_require_admin();
    $admin = Admin::_info();
    data_usage_init_db();

    $ui->assign('_system_menu', 'plugin/data_usage_admin');
    $ui->assign('_admin', $admin);
    $ui->assign('_title', 'Data Usage - Event Logs');

    $ui->assign('xheader', '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap.min.css">');
    $ui->assign('xfooter', '<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap.min.js"></script>');

    $filterRouter = isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0;
    $filterEvent  = isset($_GET['event_type']) ? trim($_GET['event_type']) : '';
    $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
    $search       = isset($_GET['search']) ? trim($_GET['search']) : '';

    $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();

    $query = ORM::for_table('tbl_data_usage_logs');
    if ($filterRouter > 0) {
        $query->where('router_id', $filterRouter);
    }
    if (!empty($filterEvent)) {
        $query->where('event_type', $filterEvent);
    }
    if (!empty($filterStatus)) {
        $query->where('status', $filterStatus);
    }
    if (!empty($search)) {
        $query->where_raw('(username LIKE ? OR details LIKE ? OR ip_address LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
    }

    $logs = $query->order_by_desc('id')->limit(1000)->find_many();

    $logsFormatted = [];
    foreach ($logs as $l) {
        $logsFormatted[] = $l->as_array();
    }

    $ui->assign('routers', $routers);
    $ui->assign('filterRouter', $filterRouter);
    $ui->assign('filterEvent', $filterEvent);
    $ui->assign('filterStatus', $filterStatus);
    $ui->assign('search', $search);
    $ui->assign('logs', $logsFormatted);
    $ui->assign('totalLogs', count($logsFormatted));
    $ui->assign('cronInfo', data_usage_get_cron_info());
    $ui->assign('cronCommand', '*/5 * * * * php ' . __DIR__ . '/cron_sync.php >/dev/null 2>&1');
    $ui->assign('csrf_token', Csrf::generateAndStoreToken());

    data_usage_view('data_usage/logs.tpl');
}

/**
 * 4.3 Clear / Prune Event Logs
 */
function data_usage_clear_logs()
{
    data_usage_require_admin();
    $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('plugin/data_usage_logs'), 'e', 'Security token expired. Please try again.');
    }

    $keepDays = isset($_POST['keep_days']) ? (int)$_POST['keep_days'] : (isset($_GET['keep_days']) ? (int)$_GET['keep_days'] : 0);

    if ($keepDays > 0) {
        ORM::raw_execute("DELETE FROM tbl_data_usage_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL " . (int)$keepDays . " DAY)");
        $stmt = ORM::get_last_statement();
        $deleted = $stmt ? $stmt->rowCount() : 0;
        data_usage_log('system', "Cleaned logs older than $keepDays days ($deleted removed) by " . Admin::_info()->username, 'info');
        r2(getUrl('plugin/data_usage_logs'), 's', "Logs older than $keepDays days have been deleted ($deleted removed).");
    } else {
        ORM::raw_execute("TRUNCATE TABLE tbl_data_usage_logs");
        data_usage_log('system', 'All logs cleared by administrator ' . Admin::_info()->username, 'info');
        r2(getUrl('plugin/data_usage_logs'), 's', 'All event logs have been cleared.');
    }
}

/**
 * 4.2 Live Status Check (On-Demand Single User Direct MikroTik Query)
 */
function data_usage_live_status()
{
    data_usage_require_admin();
    session_write_close();
    data_usage_init_db();
    header('Content-Type: application/json; charset=utf-8');

    $username = trim($_POST['username'] ?? $_GET['username'] ?? '');
    $routerId = (int)($_POST['router_id'] ?? $_GET['router_id'] ?? 0);
    $service  = trim($_POST['service'] ?? $_GET['service'] ?? 'PPPoE');

    if (empty($username) || $routerId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username and target Router required']);
        exit;
    }

    $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
    if (!$router) {
        echo json_encode(['status' => 'error', 'message' => 'Router not found or disabled']);
        exit;
    }

    $res = data_usage_query_live_user_status($router, $username, $service);
    echo json_encode($res);
    exit;
}

function data_usage_query_live_user_status($router, $username, $service = 'all')
{
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    try {
        $client = data_usage_get_mikrotik_client($router, 6.0);
        if (!$client) {
            return ['status' => 'error', 'message' => 'Failed to connect to MikroTik API (' . $router->name . ')'];
        }

        $checkPppoe = ($service === 'all' || strtolower($service) === 'pppoe');
        $checkHotspot = ($service === 'all' || strtolower($service) === 'hotspot');

        $pppoeResult = null;
        $hotspotResult = null;

        // 1. Audit PPPoE Independently
        if ($checkPppoe) {
            try {
                $req = new RouterOS\Request('/ppp/active/print');
                $req->setQuery(RouterOS\Query::where('name', $username));
                $pppRes = $client->sendSync($req);

                $activePpp = null;
                foreach ($pppRes as $p) {
                    if ($p->getType() === RouterOS\Response::TYPE_DATA && !empty($p->getProperty('name'))) {
                        $activePpp = $p;
                        break;
                    }
                }

                if ($activePpp) {
                    $rawSid   = $activePpp->getProperty('session-id') ?: ($activePpp->getProperty('.id') ?: $username);
                    $callerId = $activePpp->getProperty('caller-id') ?: '';
                    $ip       = $activePpp->getProperty('address') ?: '';
                    $rawUptime= $activePpp->getProperty('uptime') ?: '';
                    $uptime   = !empty($rawUptime) ? data_usage_format_uptime(data_usage_uptime_to_seconds($rawUptime)) : '';
                    $sId      = data_usage_clean_session_id($rawSid, $username, $callerId);

                    $rawTx = 0; $rawRx = 0; $linkUp = '';
                    try {
                        $ifReq = new RouterOS\Request('/interface/print');
                        $ifReq->setQuery(RouterOS\Query::where('name', '<pppoe-' . $username . '>'));
                        $ifRes = $client->sendSync($ifReq);
                        foreach ($ifRes as $iface) {
                            $rawTx  = (float)$iface->getProperty('tx-byte');
                            $rawRx  = (float)$iface->getProperty('rx-byte');
                            $linkUp = $iface->getProperty('last-link-up-time') ?: '';
                            break;
                        }
                    } catch (Exception $eIf) {}

                    $session = ORM::for_table('tbl_data_usage_sessions')
                        ->where('router_id', $router->id)
                        ->where('session_id', $sId)
                        ->where('status', 'active')
                        ->find_one();

                    if ($session) {
                        $deltaIn = max(0, $rawRx - $session->last_bytes_in);
                        $deltaOut = max(0, $rawTx - $session->last_bytes_out);
                        if ($deltaIn > 0 || $deltaOut > 0) {
                            data_usage_add_daily_delta($router->id, $username, 'PPPoE', $callerId ?: $session->mac_address, $deltaIn, $deltaOut, $today);
                        }

                        $session->bytes_in = $rawRx;
                        $session->bytes_out = $rawTx;
                        $session->total_bytes = $rawRx + $rawTx;
                        $session->last_bytes_in = $rawRx;
                        $session->last_bytes_out = $rawTx;
                        $session->last_seen = $now;
                        $session->uptime = $uptime ?: $session->uptime;
                        $session->ip_address = $ip ?: $session->ip_address;
                        $session->mac_address = $callerId ?: $session->mac_address;
                        $session->save();
                    } else {
                        $realStartTime = data_usage_parse_routeros_datetime($linkUp, $uptime);

                        // Close older active PPPoE sessions for this user on this router to avoid duplicates
                        $oldSessions = ORM::for_table('tbl_data_usage_sessions')
                            ->where('router_id', $router->id)
                            ->where('username', $username)
                            ->where('service', 'PPPoE')
                            ->where('status', 'active')
                            ->find_many();
                        foreach ($oldSessions as $old) {
                            $old->status = 'closed';
                            $old->closed_time = $realStartTime ?: $now;
                            $old->close_reason = 'new_session_detected';
                            $old->save();
                        }

                        $session = ORM::for_table('tbl_data_usage_sessions')->create();
                        $session->session_id = $sId;
                        $session->router_id = $router->id;
                        $session->router_name = $router->name;
                        $session->username = $username;
                        $session->service = 'PPPoE';
                        $session->ip_address = $ip;
                        $session->mac_address = $callerId;
                        $session->uptime = $uptime;
                        $session->status = 'active';
                        $session->bytes_in = $rawRx;
                        $session->bytes_out = $rawTx;
                        $session->total_bytes = $rawRx + $rawTx;
                        $session->last_bytes_in = $rawRx;
                        $session->last_bytes_out = $rawTx;
                        $session->start_time = $realStartTime;
                        $session->last_seen = $now;
                        $session->save();

                        if ($rawRx > 0 || $rawTx > 0) {
                            data_usage_add_daily_delta($router->id, $username, 'PPPoE', $callerId, $rawRx, $rawTx, $today);
                        }
                    }

                    $pppoeResult = [
                        'status'     => 'success',
                        'online'     => true,
                        'service'    => 'PPPoE',
                        'session_id' => $sId,
                        'ip'         => $ip,
                        'mac'        => $callerId,
                        'uptime'     => $uptime,
                        'upload'     => data_usage_format_bytes($rawRx),
                        'download'   => data_usage_format_bytes($rawTx),
                        'total'      => data_usage_format_bytes($rawRx + $rawTx),
                        'start_time' => $session->start_time
                    ];
                } else {
                    // PPPoE is offline: close ONLY PPPoE active DB sessions
                    $lastLoggedOut = '';
                    $lastDiscReason = '';
                    try {
                        $secReq = new RouterOS\Request('/ppp/secret/print');
                        $secReq->setQuery(RouterOS\Query::where('name', $username));
                        $secRes = $client->sendSync($secReq);
                        foreach ($secRes as $sec) {
                            $lastLoggedOut = $sec->getProperty('last-logged-out') ?: '';
                            $lastDiscReason = $sec->getProperty('last-disconnect-reason') ?: '';
                            break;
                        }
                    } catch (Exception $eSec) {}

                    $activePppDb = ORM::for_table('tbl_data_usage_sessions')
                        ->where('router_id', $router->id)
                        ->where('username', $username)
                        ->where('service', 'PPPoE')
                        ->where('status', 'active')
                        ->find_many();

                    foreach ($activePppDb as $act) {
                        $act->status = 'closed';
                        $act->closed_time = data_usage_parse_routeros_datetime($lastLoggedOut);
                        $act->close_reason = $lastDiscReason ?: 'live_check_offline';
                        $act->save();
                    }

                    $pppoeResult = [
                        'status'                 => 'success',
                        'online'                 => false,
                        'service'                => 'PPPoE',
                        'last_logged_out'        => $lastLoggedOut,
                        'last_disconnect_reason' => $lastDiscReason,
                        'message'                => 'PPPoE is offline'
                    ];
                }
            } catch (Exception $ePppCheck) {}
        }

        // 2. Audit Hotspot Independently
        if ($checkHotspot) {
            try {
                $req = new RouterOS\Request('/ip/hotspot/active/print');
                $req->setQuery(RouterOS\Query::where('user', $username));
                $hsRes = $client->sendSync($req);

                $activeHs = null;
                foreach ($hsRes as $hs) {
                    if ($hs->getType() === RouterOS\Response::TYPE_DATA && !empty($hs->getProperty('user'))) {
                        $activeHs = $hs;
                        break;
                    }
                }

                if ($activeHs) {
                    $mac    = $activeHs->getProperty('mac-address') ?: '';
                    $ip     = $activeHs->getProperty('address') ?: '';
                    $rawUptime= $activeHs->getProperty('uptime') ?: '';
                    $uptime   = !empty($rawUptime) ? data_usage_format_uptime(data_usage_uptime_to_seconds($rawUptime)) : '';
                    $rawRx    = (float)$activeHs->getProperty('bytes-in');
                    $rawTx  = (float)$activeHs->getProperty('bytes-out');

                    $sessionQuery = ORM::for_table('tbl_data_usage_sessions')
                        ->where('router_id', $router->id)
                        ->where('username', $username)
                        ->where('service', 'Hotspot')
                        ->where('status', 'active');
                    if (!empty($mac) && $mac !== '-') {
                        $sessionQuery->where('mac_address', $mac);
                    }
                    $session = $sessionQuery->find_one();

                    if ($session) {
                        $deltaIn = max(0, $rawRx - $session->last_bytes_in);
                        $deltaOut = max(0, $rawTx - $session->last_bytes_out);
                        if ($deltaIn > 0 || $deltaOut > 0) {
                            data_usage_add_daily_delta($router->id, $username, 'Hotspot', $mac ?: $session->mac_address, $deltaIn, $deltaOut, $today);
                        }

                        $session->bytes_in = $rawRx;
                        $session->bytes_out = $rawTx;
                        $session->total_bytes = $rawRx + $rawTx;
                        $session->last_bytes_in = $rawRx;
                        $session->last_bytes_out = $rawTx;
                        $session->last_seen = $now;
                        $session->uptime = $uptime ?: $session->uptime;
                        $session->ip_address = $ip ?: $session->ip_address;
                        $session->mac_address = $mac ?: $session->mac_address;
                        $session->save();
                    } else {
                        $calcStartTime = date('Y-m-d H:i:s', time() - data_usage_uptime_to_seconds($uptime));
                        $sessionId = data_usage_generate_hotspot_session_id($username, $mac, strtotime($calcStartTime));

                        // Close older active Hotspot sessions for this user on this router to avoid duplicates
                        $oldSessions = ORM::for_table('tbl_data_usage_sessions')
                            ->where('router_id', $router->id)
                            ->where('username', $username)
                            ->where('service', 'Hotspot')
                            ->where('status', 'active');
                        if (!empty($mac) && $mac !== '-') {
                            $oldSessions->where('mac_address', $mac);
                        }
                        $oldSessionsList = $oldSessions->find_many();
                        foreach ($oldSessionsList as $old) {
                            $old->status = 'closed';
                            $old->closed_time = $calcStartTime ?: $now;
                            $old->close_reason = 'new_session_detected';
                            $old->save();
                        }

                        $session = ORM::for_table('tbl_data_usage_sessions')->create();
                        $session->session_id = $sessionId;
                        $session->router_id = $router->id;
                        $session->router_name = $router->name;
                        $session->username = $username;
                        $session->service = 'Hotspot';
                        $session->ip_address = $ip;
                        $session->mac_address = $mac;
                        $session->uptime = $uptime;
                        $session->status = 'active';
                        $session->bytes_in = $rawRx;
                        $session->bytes_out = $rawTx;
                        $session->total_bytes = $rawRx + $rawTx;
                        $session->last_bytes_in = $rawRx;
                        $session->last_bytes_out = $rawTx;
                        $session->start_time = $calcStartTime;
                        $session->last_seen = $now;
                        $session->save();

                        if ($rawRx > 0 || $rawTx > 0) {
                            data_usage_add_daily_delta($router->id, $username, 'Hotspot', $mac, $rawRx, $rawTx, $today);
                        }
                    }

                    $hotspotResult = [
                        'status'     => 'success',
                        'online'     => true,
                        'service'    => 'Hotspot',
                        'session_id' => $session->session_id,
                        'ip'         => $ip,
                        'mac'        => $mac,
                        'uptime'     => $uptime,
                        'upload'     => data_usage_format_bytes($rawRx),
                        'download'   => data_usage_format_bytes($rawTx),
                        'total'      => data_usage_format_bytes($rawRx + $rawTx),
                        'start_time' => $session->start_time
                    ];
                } else {
                    // Hotspot is offline: close ONLY Hotspot active DB sessions
                    $activeHsDb = ORM::for_table('tbl_data_usage_sessions')
                        ->where('router_id', $router->id)
                        ->where('username', $username)
                        ->where('service', 'Hotspot')
                        ->where('status', 'active')
                        ->find_many();

                    foreach ($activeHsDb as $act) {
                        $act->status = 'closed';
                        $act->closed_time = $act->last_seen ?: $now;
                        $act->close_reason = 'live_check_offline';
                        $act->save();
                    }

                    $hotspotResult = [
                        'status'  => 'success',
                        'online'  => false,
                        'service' => 'Hotspot',
                        'message' => 'Hotspot is offline'
                    ];
                }
            } catch (Exception $eHsCheck) {}
        }

        // Return the active connection if available, or the offline response
        if ($service === 'Hotspot') {
            return $hotspotResult ?: ['status' => 'error', 'message' => 'Failed to check Hotspot status'];
        }
        if ($service === 'PPPoE') {
            return $pppoeResult ?: ['status' => 'error', 'message' => 'Failed to check PPPoE status'];
        }

        // $service === 'all': return whichever is online, or offline
        if ($hotspotResult && $hotspotResult['online']) {
            return $hotspotResult;
        }
        if ($pppoeResult && $pppoeResult['online']) {
            return $pppoeResult;
        }

        return $pppoeResult ?: ($hotspotResult ?: ['status' => 'success', 'online' => false, 'message' => 'User is offline']);
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * 4.1 Asynchronous Router Live Status & Online Count Endpoint
 */
function data_usage_async_routers_status()
{
    data_usage_require_admin();
    session_write_close();
    header('Content-Type: application/json; charset=utf-8');

    $routerId = isset($_REQUEST['router_id']) ? (int)$_REQUEST['router_id'] : 0;
    $routersQuery = ORM::for_table('tbl_routers')->where('enabled', '1');
    if ($routerId > 0) {
        $routersQuery->where('id', $routerId);
    }
    $routers = $routersQuery->find_many();

    $results = [];
    foreach ($routers as $r) {
        $status = data_usage_get_router_monitor_data($r);
        $results[$r->id] = $status;
    }

    echo json_encode([
        'status'  => 'success',
        'routers' => $results
    ]);
    exit;
}

/**
 * 4.2 Live User Traffic Monitor Endpoint (High-Frequency Polling)
 */
function data_usage_live_traffic()
{
    if (php_sapi_name() !== 'cli') {
        data_usage_require_admin();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Content-Type: application/json; charset=utf-8');

    $routerId = isset($_REQUEST['router_id']) ? (int)$_REQUEST['router_id'] : 0;
    $username = isset($_REQUEST['username']) ? trim((string)$_REQUEST['username']) : '';
    $service  = isset($_REQUEST['service']) ? trim((string)$_REQUEST['service']) : 'PPPoE';

    if ($routerId <= 0 || empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid router or username']);
        exit;
    }

    $router = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
    if (!$router) {
        echo json_encode(['status' => 'error', 'message' => 'Router not found or disabled']);
        exit;
    }

    try {
        $client = data_usage_get_mikrotik_client($router, 2.5);
        if (!$client) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to connect to MikroTik']);
            exit;
        }

        $uploadBps = 0;
        $downloadBps = 0;
        $isOnline = false;

        if (strtolower($service) === 'hotspot') {
            // 1. Hotspot: query queue/simple (<hotspot-username>)
            $foundQueue = false;
            try {
                $qReq = new RouterOS\Request('/queue/simple/print');
                $qReq->setQuery(RouterOS\Query::where('name', '<hotspot-' . $username . '>'));
                $qRes = $client->sendSync($qReq);
                foreach ($qRes as $q) {
                    if ($q->getType() === RouterOS\Response::TYPE_DATA) {
                        $rateStr = (string)$q->getProperty('rate');
                        if (!empty($rateStr)) {
                            $parts = explode('/', $rateStr);
                            if (count($parts) >= 2) {
                                $uploadBps = (float)$parts[0];   // Client upload (rx)
                                $downloadBps = (float)$parts[1]; // Client download (tx)
                                $foundQueue = true;
                                $isOnline = true;
                                break;
                            }
                        }
                    }
                }
            } catch (Exception $eQ) {}

            // Fallback: check queue where name is $username
            if (!$foundQueue) {
                try {
                    $qReq = new RouterOS\Request('/queue/simple/print');
                    $qReq->setQuery(RouterOS\Query::where('name', $username));
                    $qRes = $client->sendSync($qReq);
                    foreach ($qRes as $q) {
                        if ($q->getType() === RouterOS\Response::TYPE_DATA) {
                            $rateStr = (string)$q->getProperty('rate');
                            if (!empty($rateStr)) {
                                $parts = explode('/', $rateStr);
                                if (count($parts) >= 2) {
                                    $uploadBps = (float)$parts[0];
                                    $downloadBps = (float)$parts[1];
                                    $foundQueue = true;
                                    $isOnline = true;
                                    break;
                                }
                            }
                        }
                    }
                } catch (Exception $eQ2) {}
            }

            if (!$foundQueue) {
                // Check if user is in /ip/hotspot/active
                try {
                    $hsReq = new RouterOS\Request('/ip/hotspot/active/print');
                    $hsReq->setQuery(RouterOS\Query::where('user', $username));
                    $hsRes = $client->sendSync($hsReq);
                    foreach ($hsRes as $hs) {
                        if ($hs->getType() === RouterOS\Response::TYPE_DATA && !empty($hs->getProperty('user'))) {
                            $isOnline = true;
                            break;
                        }
                    }
                } catch (Exception $eHs) {}
            }
        } else {
            // 2. PPPoE: monitor dynamic interface <pppoe-username>
            try {
                $ifReq = new RouterOS\Request('/interface/monitor-traffic');
                $ifReq->setArgument('interface', '<pppoe-' . $username . '>');
                $ifReq->setArgument('once', '');
                $ifRes = $client->sendSync($ifReq);
                foreach ($ifRes as $item) {
                    if ($item->getType() === RouterOS\Response::TYPE_DATA) {
                        $uploadBps   = (float)$item->getProperty('rx-bits-per-second'); // Client upload
                        $downloadBps = (float)$item->getProperty('tx-bits-per-second'); // Client download
                        $isOnline    = true;
                        break;
                    }
                }
            } catch (Exception $eIf) {}
        }

        echo json_encode([
            'status'       => 'success',
            'online'       => $isOnline,
            'service'      => $service,
            'username'     => $username,
            'upload_bps'   => $uploadBps,
            'download_bps' => $downloadBps,
            'upload_fmt'   => data_usage_format_bps($uploadBps),
            'download_fmt' => data_usage_format_bps($downloadBps),
            'poll_ms'      => 200,
            'timestamp'    => microtime(true)
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}


/**
 * Synchronize full user list from RouterOS (/ppp/secret and /ip/hotspot/user)
 * Stores in separate tbl_data_usage_router_users table without touching session tracking.
 */
function data_usage_sync_router_users($routerId = 0)
{
    data_usage_init_db();
    $routersQuery = ORM::for_table('tbl_routers')->where('enabled', '1');
    if ($routerId > 0) {
        $routersQuery->where('id', $routerId);
    }
    $routers = $routersQuery->find_many();

    $now = date('Y-m-d H:i:s');
    $syncedCount = 0;

    foreach ($routers as $router) {
        try {
            $client = data_usage_get_mikrotik_client($router, 7.0);
            if (!$client) {
                continue;
            }

            // 1. Fetch PPPoE Users (/ppp/secret)
            try {
                $req = new RouterOS\Request('/ppp/secret/print');
                $secrets = $client->sendSync($req);
                foreach ($secrets as $s) {
                    if ($s->getType() !== RouterOS\Response::TYPE_DATA) continue;
                    $uName = trim((string)$s->getProperty('name'));
                    if (empty($uName)) continue;
                    $svc = (string)$s->getProperty('service');
                    $serviceType = (strtolower($svc) === 'hotspot') ? 'Hotspot' : 'PPPoE';
                    $mac = (string)$s->getProperty('caller-id') ?: (string)$s->getProperty('remote-mac-address');
                    $profile = (string)$s->getProperty('profile');
                    $comment = (string)$s->getProperty('comment');
                    $disabled = (string)$s->getProperty('disabled') === 'true' ? 1 : 0;

                    $ru = ORM::for_table('tbl_data_usage_router_users')
                        ->where('router_id', $router->id)
                        ->where('username', $uName)
                        ->where('service', $serviceType)
                        ->find_one();

                    if (!$ru) {
                        $ru = ORM::for_table('tbl_data_usage_router_users')->create();
                        $ru->router_id = $router->id;
                        $ru->username = $uName;
                        $ru->service = $serviceType;
                    }
                    $ru->mac_address = $mac ?: $ru->mac_address;
                    $ru->profile = $profile;
                    $ru->comment = $comment;
                    $ru->disabled = $disabled;
                    $ru->last_synced = $now;
                    $ru->save();
                    $syncedCount++;
                }
            } catch (Exception $ePpp) {}

            // 2. Fetch Hotspot Users (/ip/hotspot/user)
            try {
                $req = new RouterOS\Request('/ip/hotspot/user/print');
                $hsUsers = $client->sendSync($req);
                $currentHsUsersMap = [];

                foreach ($hsUsers as $u) {
                    if ($u->getType() !== RouterOS\Response::TYPE_DATA) continue;
                    $uName = trim((string)$u->getProperty('name'));
                    if (empty($uName) || $uName === 'default-trial') continue;
                    $mac = (string)$u->getProperty('mac-address');
                    $profile = (string)$u->getProperty('profile');
                    $comment = (string)$u->getProperty('comment');
                    $disabled = (string)$u->getProperty('disabled') === 'true' ? 1 : 0;

                    $currentHsUsersMap[$uName] = true;

                    $ru = ORM::for_table('tbl_data_usage_router_users')
                        ->where('router_id', $router->id)
                        ->where('username', $uName)
                        ->where('service', 'Hotspot')
                        ->find_one();

                    if (!$ru) {
                        $ru = ORM::for_table('tbl_data_usage_router_users')->create();
                        $ru->router_id = $router->id;
                        $ru->username = $uName;
                        $ru->service = 'Hotspot';
                    }
                    $ru->mac_address = $mac ?: $ru->mac_address;
                    $ru->profile = $profile;
                    $ru->comment = $comment;
                    $ru->disabled = $disabled;
                    $ru->missing_since = null; // Active on router: unmark missing
                    $ru->last_synced = $now;
                    $ru->save();
                    $syncedCount++;
                }

                // 3. Hotspot 7-Day Missing Grace Period Check (PPPoE untouched)
                $existingHsUsers = ORM::for_table('tbl_data_usage_router_users')
                    ->where('router_id', $router->id)
                    ->where('service', 'Hotspot')
                    ->find_many();

                foreach ($existingHsUsers as $exRu) {
                    if (!isset($currentHsUsersMap[$exRu->username])) {
                        // User is missing from /ip hotspot user
                        if (empty($exRu->missing_since)) {
                            // First noticed missing: mark timestamp, don't delete yet
                            $exRu->missing_since = $now;
                            $exRu->save();
                        } else {
                            // Already marked missing: check if 7 days (7 * 86400s) have passed
                            $missingDuration = time() - strtotime($exRu->missing_since);
                            if ($missingDuration >= (7 * 86400)) {
                                // Grace period expired: delete session history, daily history, and registry entry
                                ORM::for_table('tbl_data_usage_sessions')
                                    ->where('router_id', $router->id)
                                    ->where('username', $exRu->username)
                                    ->where('service', 'Hotspot')
                                    ->delete_many();

                                ORM::for_table('tbl_data_usage_daily')
                                    ->where('router_id', $router->id)
                                    ->where('username', $exRu->username)
                                    ->where('service', 'Hotspot')
                                    ->delete_many();

                                $exRu->delete();
                            }
                        }
                    }
                }
            } catch (Exception $eHs) {}

        } catch (Exception $e) {}
    }

    return $syncedCount;
}

/**
 * Auto-prune event logs older than configured retention days
 */
function data_usage_auto_prune_logs()
{
    $retention = (int)data_usage_get_config('data_usage_log_retention_days', '30');
    if ($retention > 0) {
        ORM::raw_execute("DELETE FROM tbl_data_usage_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL " . (int)$retention . " DAY)");
    }
}

/**
 * 5. On-Demand / Cron Failsafe RouterOS API Sync
 */
function data_usage_sync()
{
    if (php_sapi_name() !== 'cli') {
        data_usage_require_admin();
    }
    data_usage_init_db();

    // Auto-prune event logs according to retention days
    data_usage_auto_prune_logs();

    $routerId = isset($_POST['router_id']) ? (int)$_POST['router_id'] : (isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0);

    // Sync full user registry to separate table
    data_usage_sync_router_users($routerId);

    $routersToSync = [];
    if ($routerId > 0) {
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
        if ($r) $routersToSync[] = $r;
    } else {
        $routersToSync = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    }

    if (empty($routersToSync)) {
        if (php_sapi_name() === 'cli') {
            echo "No active router found to sync.\n";
            return;
        }
        r2(getUrl('plugin/data_usage_admin'), 'e', 'No active router found to sync');
    }

    $totalSynced = 0;
    $totalReconciled = 0;
    $errors = [];
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    foreach ($routersToSync as $router) {
        try {
            $client = data_usage_get_mikrotik_client($router, 7.0);
            if (!$client) {
                $errors[] = $router->name . ': Connection failed';
                continue;
            }

            $activePppSids = [];
            $activeHsKeys = [];
            $activeHsUsernames = [];

            // 1. Process PPPoE active sessions
            try {
                $pppActive = $client->sendSync(new RouterOS\Request('/ppp/active/print'));
                $pppMap = [];
                foreach ($pppActive as $p) {
                    if ($p->getType() !== RouterOS\Response::TYPE_DATA) continue;
                    $uName = $p->getProperty('name');
                    if (!empty($uName)) {
                        $rawSid = $p->getProperty('session-id') ?: ($p->getProperty('.id') ?: $uName);
                        $callerId = $p->getProperty('caller-id') ?: '';
                        $cleanSid = data_usage_clean_session_id($rawSid, $uName, $callerId);
                        $pppMap[$uName] = [
                            'caller_id'  => $callerId,
                            'address'    => $p->getProperty('address') ?: '',
                            'uptime'     => $p->getProperty('uptime') ?: '',
                            'session_id' => $cleanSid
                        ];
                    }
                }

                $interfaces = $client->sendSync(new RouterOS\Request('/interface/print'));
                foreach ($interfaces as $iface) {
                    if ($iface->getType() !== RouterOS\Response::TYPE_DATA) continue;
                    $ifType = $iface->getProperty('type');
                    $ifName = $iface->getProperty('name');

                    if ($ifType === 'pppoe-in' || strpos($ifName, '<pppoe-') === 0) {
                        $uName = $ifName;
                        if (strpos($ifName, '<pppoe-') === 0 && substr($ifName, -1) === '>') {
                            $uName = substr($ifName, 7, -1);
                        }

                        $rawTx = (float)$iface->getProperty('tx-byte'); // Client Download
                        $rawRx = (float)$iface->getProperty('rx-byte'); // Client Upload
                        $linkUp = $iface->getProperty('last-link-up-time') ?: '';

                        $callerId = $pppMap[$uName]['caller_id'] ?? '';
                        $addr     = $pppMap[$uName]['address'] ?? '';
                        $uptime   = $pppMap[$uName]['uptime'] ?? '';
                        $sId      = $pppMap[$uName]['session_id'] ?? data_usage_clean_session_id($callerId, $uName, $callerId);

                        $activePppSids[] = $sId;

                        $session = ORM::for_table('tbl_data_usage_sessions')
                            ->where('router_id', $router->id)
                            ->where('session_id', $sId)
                            ->where('status', 'active')
                            ->find_one();

                        if ($session) {
                            $deltaIn = max(0, $rawRx - $session->last_bytes_in);
                            $deltaOut = max(0, $rawTx - $session->last_bytes_out);
                            if ($deltaIn > 0 || $deltaOut > 0) {
                                data_usage_add_daily_delta($router->id, $uName, 'PPPoE', $callerId ?: $session->mac_address, $deltaIn, $deltaOut, $today);
                            }

                            $session->bytes_in = $rawRx;
                            $session->bytes_out = $rawTx;
                            $session->total_bytes = $rawRx + $rawTx;
                            $session->last_bytes_in = $rawRx;
                            $session->last_bytes_out = $rawTx;
                            $session->last_seen = $now;
                            $session->uptime = $uptime ?: $session->uptime;
                            $session->ip_address = $addr ?: $session->ip_address;
                            $session->mac_address = $callerId ?: $session->mac_address;
                            $session->save();
                        } else {
                            $realStartTime = data_usage_parse_routeros_datetime($linkUp, $uptime);

                            $session = ORM::for_table('tbl_data_usage_sessions')->create();
                            $session->session_id = $sId;
                            $session->router_id = $router->id;
                            $session->router_name = $router->name;
                            $session->username = $uName;
                            $session->service = 'PPPoE';
                            $session->ip_address = $addr;
                            $session->mac_address = $callerId;
                            $session->uptime = $uptime;
                            $session->status = 'active';
                            $session->bytes_in = $rawRx;
                            $session->bytes_out = $rawTx;
                            $session->total_bytes = $rawRx + $rawTx;
                            $session->last_bytes_in = $rawRx;
                            $session->last_bytes_out = $rawTx;
                            $session->start_time = $realStartTime;
                            $session->last_seen = $now;
                            $session->save();

                            if ($rawRx > 0 || $rawTx > 0) {
                                data_usage_add_daily_delta($router->id, $uName, 'PPPoE', $callerId, $rawRx, $rawTx, $today);
                            }
                        }
                        $totalSynced++;
                    }
                }
            } catch (Exception $ePpp) {}

            // 2. Process Hotspot active users
            try {
                $hsActive = $client->sendSync(new RouterOS\Request('/ip/hotspot/active/print'));
                foreach ($hsActive as $hs) {
                    if ($hs->getType() !== RouterOS\Response::TYPE_DATA) continue;
                    $uName  = $hs->getProperty('user');
                    if (!empty($uName)) {
                        $mac    = $hs->getProperty('mac-address') ?: '';
                        $ip     = $hs->getProperty('address') ?: '';
                        $uptime = $hs->getProperty('uptime') ?: '';
                        $tx     = (float)$hs->getProperty('bytes-in');
                        $rx     = (float)$hs->getProperty('bytes-out');

                        $activeHsUsernames[] = $uName;
                        if (!empty($mac) && $mac !== '-') {
                            $activeHsKeys[] = $uName . '_' . $mac;
                        }

                        $sessionQuery = ORM::for_table('tbl_data_usage_sessions')
                            ->where('router_id', $router->id)
                            ->where('username', $uName)
                            ->where('service', 'Hotspot')
                            ->where('status', 'active');
                        if (!empty($mac) && $mac !== '-') {
                            $sessionQuery->where('mac_address', $mac);
                        }
                        $session = $sessionQuery->find_one();

                        if ($session) {
                            $deltaIn = max(0, $tx - $session->last_bytes_in);
                            $deltaOut = max(0, $rx - $session->last_bytes_out);
                            if ($deltaIn > 0 || $deltaOut > 0) {
                                data_usage_add_daily_delta($router->id, $uName, 'Hotspot', $mac ?: $session->mac_address, $deltaIn, $deltaOut, $today);
                            }

                            $session->bytes_in = $tx;
                            $session->bytes_out = $rx;
                            $session->total_bytes = $tx + $rx;
                            $session->last_bytes_in = $tx;
                            $session->last_bytes_out = $rx;
                            $session->last_seen = $now;
                            $session->uptime = $uptime ?: $session->uptime;
                            $session->ip_address = $ip ?: $session->ip_address;
                            $session->mac_address = $mac ?: $session->mac_address;
                            $session->save();
                        } else {
                            $calcStartTime = date('Y-m-d H:i:s', time() - data_usage_uptime_to_seconds($uptime));
                            $newSid = data_usage_generate_hotspot_session_id($uName, $mac, strtotime($calcStartTime));

                            $session = ORM::for_table('tbl_data_usage_sessions')->create();
                            $session->session_id = $newSid;
                            $session->router_id = $router->id;
                            $session->router_name = $router->name;
                            $session->username = $uName;
                            $session->service = 'Hotspot';
                            $session->ip_address = $ip;
                            $session->mac_address = $mac;
                            $session->uptime = $uptime;
                            $session->status = 'active';
                            $session->bytes_in = $tx;
                            $session->bytes_out = $rx;
                            $session->total_bytes = $tx + $rx;
                            $session->last_bytes_in = $tx;
                            $session->last_bytes_out = $rx;
                            $session->start_time = $calcStartTime;
                            $session->last_seen = $now;
                            $session->save();

                            if ($tx > 0 || $rx > 0) {
                                data_usage_add_daily_delta($router->id, $uName, 'Hotspot', $mac, $tx, $rx, $today);
                            }
                        }
                        $totalSynced++;
                    }
                }
            } catch (Exception $eHs) {}

            // 3. Failsafe Reconciler: Close missing active sessions in DB
            $dbActiveSessions = ORM::for_table('tbl_data_usage_sessions')
                ->where('router_id', $router->id)
                ->where('status', 'active')
                ->find_many();

            // Cache /ppp/secret lookup table for fast reconciliation
            $secretMap = [];
            try {
                $secRes = $client->sendSync(new RouterOS\Request('/ppp/secret/print'));
                foreach ($secRes as $sec) {
                    $sName = $sec->getProperty('name');
                    if (!empty($sName)) {
                        $secretMap[$sName] = [
                            'last_logged_out'        => $sec->getProperty('last-logged-out') ?: '',
                            'last_disconnect_reason' => $sec->getProperty('last-disconnect-reason') ?: ''
                        ];
                    }
                }
            } catch (Exception $eSecMap) {}

            foreach ($dbActiveSessions as $act) {
                if ($act->service === 'PPPoE') {
                    if (!in_array($act->session_id, $activePppSids)) {
                        $secInfo = $secretMap[$act->username] ?? [];
                        $loggedOutStr = $secInfo['last_logged_out'] ?? '';
                        $discReason = $secInfo['last_disconnect_reason'] ?? 'cron_reconciled';

                        $act->status = 'closed';
                        $act->closed_time = data_usage_parse_routeros_datetime($loggedOutStr, $act->uptime);
                        $act->close_reason = $discReason ?: 'cron_reconciled';
                        $act->save();
                        $totalReconciled++;
                    }
                } elseif ($act->service === 'Hotspot') {
                    $isLive = false;
                    if (!empty($act->mac_address) && $act->mac_address !== '-') {
                        $key = $act->username . '_' . $act->mac_address;
                        if (in_array($key, $activeHsKeys)) {
                            $isLive = true;
                        }
                    } else {
                        // Fallback for missing/blank/"-" MAC address
                        if (in_array($act->username, $activeHsUsernames)) {
                            $isLive = true;
                        }
                    }

                    if (!$isLive) {
                        $act->status = 'closed';
                        $act->closed_time = $act->last_seen ?: $now;
                        $act->close_reason = 'session_timeout';
                        $act->save();
                        $totalReconciled++;
                    }
                }
            }

            data_usage_set_config('data_usage_last_sync_' . $router->id, date('Y-m-d H:i:s'));
        } catch (Exception $e) {
            $errors[] = $router->name . ': ' . $e->getMessage();
        }
    }

    $msg = "Synced {$totalSynced} active sessions and reconciled {$totalReconciled} closed sessions.";
    if (!empty($errors)) {
        $msg .= " (Errors: " . implode('; ', $errors) . ")";
    }

    data_usage_log('cron-sync', $msg, !empty($errors) ? 'warning' : 'success', $routerId);

    if (php_sapi_name() === 'cli') {
        data_usage_set_config('data_usage_last_cron_run', date('Y-m-d H:i:s'));
        echo $msg . PHP_EOL;
        return;
    }

    r2(getUrl('plugin/data_usage_admin'), !empty($errors) ? 'w' : 's', $msg);
}

/**
 * 6. Export Daily History to CSV
 */
function data_usage_export()
{
    data_usage_require_admin();
    data_usage_init_db();

    $startDate = isset($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET['start_date'])) ? trim($_GET['start_date']) : date('Y-m-01');
    $endDate = isset($_GET['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_GET['end_date'])) ? trim($_GET['end_date']) : date('Y-m-d');
    $filterRouter = isset($_GET['router']) ? (int)$_GET['router'] : 0;

    $sql = "SELECT usage_date, router_id, username, service, mac_address, bytes_in, bytes_out, total_bytes
            FROM tbl_data_usage_daily
            WHERE usage_date >= ? AND usage_date <= ?";
    $params = [$startDate, $endDate];
    if ($filterRouter > 0) {
        $sql .= " AND router_id = ?";
        $params[] = $filterRouter;
    }
    $sql .= " ORDER BY usage_date DESC";

    $stmt = ORM::get_db()->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_usage_' . $startDate . '_to_' . $endDate . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Router ID', 'Username', 'Service', 'MAC Address', 'Upload (Bytes)', 'Download (Bytes)', 'Total (Bytes)', 'Upload (Formatted)', 'Download (Formatted)', 'Total (Formatted)']);

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['usage_date'],
            $r['router_id'],
            $r['username'],
            $r['service'],
            $r['mac_address'],
            $r['bytes_in'],
            $r['bytes_out'],
            $r['total_bytes'],
            data_usage_format_bytes($r['bytes_in']),
            data_usage_format_bytes($r['bytes_out']),
            data_usage_format_bytes($r['total_bytes'])
        ]);
    }
    fclose($out);
    exit;
}

/**
 * 7. Admin Disconnect User from RouterOS
 */
function data_usage_disconnect()
{
    data_usage_require_admin();
    $sessionId = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';
    $routerId = isset($_POST['router_id']) ? (int)$_POST['router_id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $redirectTo = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '';

    if ($redirectTo === 'user' && !empty($username)) {
        $redirectUrl = getUrl('plugin/data_usage_user&username=' . urlencode($username));
    } elseif ($routerId > 0) {
        $redirectUrl = getUrl('plugin/data_usage_admin&router=' . $routerId);
    } else {
        $redirectUrl = getUrl('plugin/data_usage_admin');
    }

    if (!Csrf::check($_POST['csrf_token'] ?? '')) {
        r2($redirectUrl, 'e', 'Security token expired. Please try again.');
    }

    if (empty($sessionId) && empty($username)) {
        r2($redirectUrl, 'e', 'Invalid session parameters');
    }

    $disconnected = false;

    // 1. Send Disconnect signal to RouterOS directly
    if ($routerId > 0) {
        $router = ORM::for_table('tbl_routers')->find_one($routerId);
        if ($router) {
            try {
                $client = data_usage_get_mikrotik_client($router, 6.0);
                if ($client) {
                    if (empty($username) && !empty($sessionId)) {
                        $sRow = ORM::for_table('tbl_data_usage_sessions')
                            ->where('router_id', $routerId)
                            ->where('session_id', $sessionId)
                            ->find_one();
                        if ($sRow) $username = $sRow->username;
                    }

                    if (!empty($username)) {
                        // PPPoE disconnect
                        try {
                            $req = new RouterOS\Request('/ppp/active/print');
                            $req->setQuery(RouterOS\Query::where('name', $username));
                            $actives = $client->sendSync($req);
                            foreach ($actives as $act) {
                                $del = new RouterOS\Request('/ppp/active/remove');
                                $del->setArgument('numbers', $act->getProperty('.id'));
                                $client->sendSync($del);
                                $disconnected = true;
                            }
                        } catch (Exception $ePpp) {}

                        // Hotspot disconnect
                        try {
                            $req = new RouterOS\Request('/ip/hotspot/active/print');
                            $req->setQuery(RouterOS\Query::where('user', $username));
                            $actives = $client->sendSync($req);
                            foreach ($actives as $act) {
                                $del = new RouterOS\Request('/ip/hotspot/active/remove');
                                $del->setArgument('numbers', $act->getProperty('.id'));
                                $client->sendSync($del);
                                $disconnected = true;
                            }
                        } catch (Exception $eHs) {}
                    }
                }
            } catch (Exception $e) {}
        }
    }

    // 2. Mark session as closed in DB
    $sessQuery = ORM::for_table('tbl_data_usage_sessions')->where('status', 'active');
    if ($routerId > 0) {
        $sessQuery->where('router_id', $routerId);
    }
    if (!empty($sessionId)) {
        $sessQuery->where('session_id', $sessionId);
    } elseif (!empty($username)) {
        $sessQuery->where('username', $username);
    }

    $session = $sessQuery->find_one();
    if ($session) {
        $session->status = 'closed';
        $session->closed_time = date('Y-m-d H:i:s');
        $session->close_reason = 'admin_forced';
        $session->save();
        $disconnected = true;
    }

    if ($disconnected) {
        data_usage_log('disconnect', "Admin disconnected user {$username} (SessionID: " . ($sessionId ?: '-') . ")", 'warning', $routerId, $username);
        r2($redirectUrl, 's', 'User session disconnected successfully');
    }

    r2($redirectUrl, 's', 'Disconnect signal processed');
}

/**
 * 8. Automatic MikroTik Poller Script Generation & Installation
 */
function data_usage_generate_poller_source($routerId)
{
    $appUrl = rtrim(APP_URL, '/');
    $webhookUrl = data_usage_webhook_url($appUrl);

    return ':local routerId "' . (int)$routerId . '";' . "\n"
        . ':local serverUrl "' . $webhookUrl . '";' . "\n"
        . ':local jsonPayload "[";' . "\n"
        . ':local isFirst true;' . "\n"
        . ':local sessionCount 0;' . "\n\n"
        . '# Process PPPoE Active' . "\n"
        . ':foreach i in=[/interface find where type="pppoe-in"] do={' . "\n"
        . '    :local ifName [/interface get $i name];' . "\n"
        . '    :local uName $ifName;' . "\n"
        . '    :if ([:pick $ifName 0 7] = "<pppoe-" && [:pick $ifName ([:len $ifName] - 1) [:len $ifName]] = ">") do={' . "\n"
        . '        :set uName [:pick $ifName 7 ([:len $ifName] - 1)];' . "\n"
        . '    }' . "\n"
        . '    :local rawTx [/interface get $i tx-byte];' . "\n"
        . '    :local rawRx [/interface get $i rx-byte];' . "\n"
        . '    :local mac "";' . "\n"
        . '    :local addr "";' . "\n"
        . '    :local sessId "";' . "\n"
        . '    :local uptime "";' . "\n"
        . '    :foreach j in=[/ppp active find where name="$uName"] do={' . "\n"
        . '        :set mac [/ppp active get $j caller-id];' . "\n"
        . '        :set addr [/ppp active get $j address];' . "\n"
        . '        :set sessId [/ppp active get $j session-id];' . "\n"
        . '        :set uptime [/ppp active get $j uptime];' . "\n"
        . '    }' . "\n"
        . '    :if ($sessId = "") do={ :set sessId $uName; }' . "\n"
        . '    :if ($isFirst = true) do={ :set isFirst false; } else={ :set jsonPayload ($jsonPayload . ","); }' . "\n"
        . '    :set jsonPayload ($jsonPayload . "{\"session_id\":\"" . $sessId . "\",\"username\":\"" . $uName . "\",\"service\":\"PPPoE\",\"mac\":\"" . $mac . "\",\"ip\":\"" . $addr . "\",\"uptime\":\"" . $uptime . "\",\"tx\":" . $rawRx . ",\"rx\":" . $rawTx . "}");' . "\n"
        . '    :set sessionCount ($sessionCount + 1);' . "\n"
        . '}' . "\n\n"
        . '# Process Hotspot Active' . "\n"
        . ':foreach i in=[/ip hotspot active find] do={' . "\n"
        . '    :local uName [/ip hotspot active get $i user];' . "\n"
        . '    :local mac [/ip hotspot active get $i mac-address];' . "\n"
        . '    :local ip [/ip hotspot active get $i address];' . "\n"
        . '    :local uptime [/ip hotspot active get $i uptime];' . "\n"
        . '    :local sessId $mac;' . "\n"
        . '    :if ($sessId = "") do={ :set sessId $uName; }' . "\n"
        . '    :local rawRx [/ip hotspot active get $i bytes-in];' . "\n"
        . '    :local rawTx [/ip hotspot active get $i bytes-out];' . "\n"
        . '    :if ($isFirst = true) do={ :set isFirst false; } else={ :set jsonPayload ($jsonPayload . ","); }' . "\n"
        . '    :set jsonPayload ($jsonPayload . "{\"session_id\":\"" . $sessId . "\",\"username\":\"" . $uName . "\",\"service\":\"Hotspot\",\"mac\":\"" . $mac . "\",\"ip\":\"" . $ip . "\",\"uptime\":\"" . $uptime . "\",\"tx\":" . $rawRx . ",\"rx\":" . $rawTx . "}");' . "\n"
        . '    :set sessionCount ($sessionCount + 1);' . "\n"
        . '}' . "\n\n"
        . ':set jsonPayload ($jsonPayload . "]");' . "\n\n"
        . ':if ($jsonPayload != "[]") do={' . "\n"
        . '    :log info ("dataUsage [Poller]: Syncing " . $sessionCount . " active sessions to server...");' . "\n"
        . '    /tool fetch url=$serverUrl http-method=post http-header-field="Content-Type: application/json" http-data=("{\"action\":\"batch-sync\",\"router\":\"" . $routerId . "\",\"batch\":" . $jsonPayload . "}") keep-result=no;' . "\n"
        . '}' . "\n";
}

function data_usage_install_poller_to_router($router)
{
    try {
        $client = Mikrotik::getClient($router->ip_address, $router->username, $router->password);
        if (!$client) {
            return [false, 'Failed to connect to router API (' . $router->name . ')'];
        }

        $source = data_usage_generate_poller_source($router->id);

        // 1. Script Management (Remove old script if exists, then add fresh)
        try {
            $scriptReq = new RouterOS\Request('/system/script/print');
            $scriptReq->setQuery(RouterOS\Query::where('name', 'dataUsagePoller'));
            $existingScripts = $client->sendSync($scriptReq);

            foreach ($existingScripts as $es) {
                $delReq = new RouterOS\Request('/system/script/remove');
                $delReq->setArgument('numbers', $es->getProperty('.id'));
                $client->sendSync($delReq);
            }
        } catch (Exception $eCleanScript) {}

        $addReq = new RouterOS\Request('/system/script/add');
        $addReq->setArgument('name', 'dataUsagePoller');
        $addReq->setArgument('source', $source);
        $addReq->setArgument('policy', 'ftp,reboot,read,write,policy,test,password,sniff,sensitive');
        $client->sendSync($addReq);

        // 2. Scheduler Management (Remove old schedule if exists, then add fresh)
        try {
            $schedReq = new RouterOS\Request('/system/scheduler/print');
            $schedReq->setQuery(RouterOS\Query::where('name', 'dataUsagePollerSchedule'));
            $existingSched = $client->sendSync($schedReq);

            foreach ($existingSched as $esh) {
                $delSched = new RouterOS\Request('/system/scheduler/remove');
                $delSched->setArgument('numbers', $esh->getProperty('.id'));
                $client->sendSync($delSched);
            }
        } catch (Exception $eCleanSched) {}

        $addSched = new RouterOS\Request('/system/scheduler/add');
        $addSched->setArgument('name', 'dataUsagePollerSchedule');
        $addSched->setArgument('on-event', 'dataUsagePoller');
        $addSched->setArgument('interval', '2m');
        $addSched->setArgument('start-time', 'startup');
        $addSched->setArgument('policy', 'ftp,reboot,read,write,policy,test,password,sniff,sensitive');
        $client->sendSync($addSched);

        // 3. Trigger immediate run
        try {
            $runReq = new RouterOS\Request('/system/script/run');
            $runReq->setArgument('number', 'dataUsagePoller');
            $client->sendSync($runReq);
        } catch (Exception $eRun) {}

        return [true, 'Installed & activated successfully on ' . $router->name];
    } catch (Exception $e) {
        return [false, $router->name . ': ' . $e->getMessage()];
    }
}

function data_usage_install_poller()
{
    data_usage_require_admin();
    $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'Security token expired. Please try again.');
    }

    $routerId = isset($_POST['router_id']) ? (int)$_POST['router_id'] : (isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0);

    $routers = [];
    if ($routerId > 0) {
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
        if ($r) $routers[] = $r;
    } else {
        $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    }

    if (empty($routers)) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'No enabled routers found');
    }

    $success = [];
    $errors = [];
    foreach ($routers as $router) {
        list($ok, $msg) = data_usage_install_poller_to_router($router);
        if ($ok) {
            $success[] = $msg;
        } else {
            $errors[] = $msg;
        }
    }

    $redirectUrl = getUrl('plugin/data_usage_scripts') . ($routerId > 0 ? '&router_id=' . $routerId : '');

    if (!empty($success)) {
        $msg = implode('; ', $success);
        if (!empty($errors)) $msg .= ' (Errors: ' . implode('; ', $errors) . ')';
        r2($redirectUrl, !empty($errors) ? 'w' : 's', $msg);
    } else {
        r2($redirectUrl, 'e', implode('; ', $errors));
    }
}

function data_usage_remove_poller_from_router($router)
{
    try {
        $client = data_usage_get_mikrotik_client($router, 7.0);
        if (!$client) {
            $client = Mikrotik::getClient($router->ip_address, $router->username, $router->password);
        }
        if (!$client) {
            return [false, 'Failed to connect to router API (' . $router->name . ')'];
        }

        // 1. Scheduler Management (Remove schedule if exists)
        try {
            $schedReq = new RouterOS\Request('/system/scheduler/print');
            $schedReq->setQuery(RouterOS\Query::where('name', 'dataUsagePollerSchedule'));
            $existingSched = $client->sendSync($schedReq);

            foreach ($existingSched as $esh) {
                $delSched = new RouterOS\Request('/system/scheduler/remove');
                $delSched->setArgument('numbers', $esh->getProperty('.id'));
                $client->sendSync($delSched);
            }
        } catch (Exception $eCleanSched) {}

        // 2. Script Management (Remove script if exists)
        try {
            $scriptReq = new RouterOS\Request('/system/script/print');
            $scriptReq->setQuery(RouterOS\Query::where('name', 'dataUsagePoller'));
            $existingScripts = $client->sendSync($scriptReq);

            foreach ($existingScripts as $es) {
                $delReq = new RouterOS\Request('/system/script/remove');
                $delReq->setArgument('numbers', $es->getProperty('.id'));
                $client->sendSync($delReq);
            }
        } catch (Exception $eCleanScript) {}

        data_usage_log($router->name, 'Live poller script and scheduler removed from router', 'info');
        return [true, 'Removed successfully from ' . $router->name];
    } catch (Exception $e) {
        return [false, $router->name . ': ' . $e->getMessage()];
    }
}

function data_usage_remove_poller()
{
    data_usage_require_admin();
    $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!Csrf::check($csrf_token)) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'Security token expired. Please try again.');
    }

    $routerId = isset($_POST['router_id']) ? (int)$_POST['router_id'] : (isset($_GET['router_id']) ? (int)$_GET['router_id'] : 0);

    $routers = [];
    if ($routerId > 0) {
        $r = ORM::for_table('tbl_routers')->where('enabled', '1')->find_one($routerId);
        if ($r) $routers[] = $r;
    } else {
        $routers = ORM::for_table('tbl_routers')->where('enabled', '1')->find_many();
    }

    if (empty($routers)) {
        r2(getUrl('plugin/data_usage_scripts'), 'e', 'No enabled routers found');
    }

    $success = [];
    $errors = [];
    foreach ($routers as $router) {
        list($ok, $msg) = data_usage_remove_poller_from_router($router);
        if ($ok) {
            $success[] = $msg;
        } else {
            $errors[] = $msg;
        }
    }

    $redirectUrl = getUrl('plugin/data_usage_scripts') . ($routerId > 0 ? '&router_id=' . $routerId : '');

    if (!empty($success)) {
        $msg = implode('; ', $success);
        if (!empty($errors)) $msg .= ' (Errors: ' . implode('; ', $errors) . ')';
        r2($redirectUrl, !empty($errors) ? 'w' : 's', $msg);
    } else {
        r2($redirectUrl, 'e', implode('; ', $errors));
    }
}

