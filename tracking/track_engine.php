<?php
/**
 * Enterprise Telemetry Passive Collection Engine
 * ZERO HTML injection - 100% safe for print windows, PDFs, and AJAX APIs.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Process background asynchronous LAN IP synchronization requests instantly
if (isset($_GET['action']) && $_GET['action'] === 'sync_lan') {
    if (!empty($_POST['local_ip'])) {
        $_SESSION['local_ip'] = filter_var(trim($_POST['local_ip']), FILTER_DEFAULT);
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'synchronized']);
    exit;
}

// 2. Capture baseline execution window metrics
$engine_start_time = microtime(true);

// 3. Evaluate upstream network request payload metrics
$payload_body_bytes = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
$payload_header_bytes = 0;
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $header_key => $header_value) {
        $payload_header_bytes += strlen($header_key) + strlen($header_value) + 4;
    }
}
$engine_traffic_up_kb = round(($payload_header_bytes + $payload_body_bytes) / 1024, 2);
$initial_memory_footprint = memory_get_usage();

// 4. Register structural shutdown capture hook to commit metrics down to database layers
register_shutdown_function(function() use ($engine_start_time, $engine_traffic_up_kb, $initial_memory_footprint) {
    
    // Calculate total internal execution timeline speed
    $engine_end_time = microtime(true);
    $load_time_ms = round(($engine_end_time - $engine_start_time) * 1000, 2);

    // Track output memory allocation profile to safely estimate down-traffic weights
    $final_memory_footprint = memory_get_peak_usage();
    $raw_down_bytes = ($final_memory_footprint - $initial_memory_footprint);
    if ($raw_down_bytes < 1024) {
        $raw_down_bytes = function_exists('ob_get_length') ? ob_get_length() : 4096;
    }
    $traffic_down_kb = round(max($raw_down_bytes, 4096) / 1024, 2);
    $total_data_kb = $engine_traffic_up_kb + $traffic_down_kb;

    // Collate session metadata signatures
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
    $local_ip = isset($_SESSION['local_ip']) ? $_SESSION['local_ip'] : 'Resolving...';
    $session_id = session_id() ? session_id() : 'None';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $response_code = http_response_code() ? http_response_code() : 200;
    
    $visit_date = date('Y-m-d');
    $visit_hour = (int)date('H');
    $full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    // Multi-tier structural network parsing for public endpoint identification
    $public_ip = 'Unknown';
    $ip_priorities = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ip_priorities as $header_key) {
        if (!empty($_SERVER[$header_key])) {
            $extracted_vector = trim(explode(',', $_SERVER[$header_key])[0]);
            if (filter_var($extracted_vector, FILTER_VALIDATE_IP)) {
                $public_ip = $extracted_vector;
                break;
            }
        }
    }

    // Isolate client environment metrics
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $browser = 'Unknown Browser'; $os = 'Unknown OS'; $device = 'Desktop';

    if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) { $browser = 'Internet Explorer'; }
    elseif (preg_match('/Firefox/i', $user_agent)) { $browser = 'Firefox'; }
    elseif (preg_match('/Chrome/i', $user_agent)) { $browser = 'Chrome'; }
    elseif (preg_match('/Safari/i', $user_agent)) { $browser = 'Safari'; }
    elseif (preg_match('/Opera/i', $user_agent)) { $browser = 'Opera'; }
    elseif (preg_match('/Edge/i', $user_agent)) { $browser = 'Edge'; }

    if (preg_match('/windows|win32/i', $user_agent)) { $os = 'Windows'; }
    elseif (preg_match('/macintosh|mac os x/i', $user_agent)) { $os = 'macOS'; }
    elseif (preg_match('/linux/i', $user_agent)) { $os = 'Linux'; }
    elseif (preg_match('/android/i', $user_agent)) { $os = 'Android'; }
    elseif (preg_match('/iphone|ipad/i', $user_agent)) { $os = 'iOS'; }

    if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $user_agent)) { $device = 'Mobile'; }
    elseif (preg_match('/ipad|tablet|playbook|silk/i', $user_agent)) { $device = 'Tablet'; }

    // Mount centralized database handler dependency mapping
    $db_path = dirname(__DIR__) . "/database/connection.php";
    if (file_exists($db_path)) { include($db_path); } else { @include("database/connection.php"); }

    $db = null; $type = '';
    foreach (['conn', 'con', 'db', 'link', 'pdo'] as $wrapper) {
        if (isset($$wrapper)) {
            if ($$wrapper instanceof PDO) { $db = $$wrapper; $type = 'pdo'; break; }
            elseif ($$wrapper instanceof mysqli) { $db = $$wrapper; $type = 'mysqli'; break; }
        }
    }
    if (!$db) return;

    // Secure database transactional persistence sequence
    if ($type === 'pdo') {
        try {
            $stmt = $db->prepare("INSERT INTO page_usage (visit_date, visit_hour, session_id, username, local_ip, public_ip, full_url, referer, request_method, response_code, browser, operating_system, device_type, traffic_up_kb, traffic_down_kb, total_data_kb, load_time_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$visit_date, $visit_hour, $session_id, $username, $local_ip, $public_ip, $full_url, $referer, $request_method, $response_code, $browser, $os, $device, $engine_traffic_up_kb, $traffic_down_kb, $total_data_kb, $load_time_ms]);
        } catch (Exception $e) {}
    } elseif ($type === 'mysqli') {
        $stmt = $db->prepare("INSERT INTO page_usage (visit_date, visit_hour, session_id, username, local_ip, public_ip, full_url, referer, request_method, response_code, browser, operating_system, device_type, traffic_up_kb, traffic_down_kb, total_data_kb, load_time_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sisssssssisssdddd", $visit_date, $visit_hour, $session_id, $username, $local_ip, $public_ip, $full_url, $referer, $request_method, $response_code, $browser, $os, $device, $engine_traffic_up_kb, $traffic_down_kb, $total_data_kb, $load_time_ms);
            $stmt->execute(); $stmt->close();
        }
    }
});
?>