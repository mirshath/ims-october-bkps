<?php
require_once __DIR__ . '/db.php';

/**
 * Get the base path of the application dynamically
 * This works regardless of where the app is hosted (e.g., /hosted_bms_pos/, /stall/, /, etc.)
 */
function get_base_path()
{
    static $base_path = null;
    if ($base_path !== null) {
        return $base_path;
    }
    
    // // Get the script name (e.g., /stall/admin/detailed-report.php or /stall/login.php)
    // $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    
    // // Remove the filename to get the directory
    // $dir = dirname($script_name);
    
    // // If we're in a subdirectory (like admin/), go up one level to get the base
    // // Otherwise, use the directory as-is (it's already the base)
    // if (basename($dir) === 'admin') {
    //     $dir = dirname($dir);
    // }
    
    // Normalize: ensure it starts with / and ends with /
    // $base_path = '/' . trim($dir, '/\\');
    // if ($base_path !== '/') {
    //     $base_path .= '/';
    // }
    
    // return $base_path;
    
    
    
    
    
    
    try {
        // Get the script name (e.g., /stall/admin/detailed-report.php or /stall/login.php)
        $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        
        // Fallback if script name is empty or invalid
        if (empty($script_name) || $script_name === '.' || $script_name === '/') {
            $base_path = '/';
            return $base_path;
        }
        
        // Remove the filename to get the directory
        $dir = dirname($script_name);
        
        // Handle edge cases - dirname can return '.' or '\' on Windows
        if ($dir === '.' || $dir === '\\' || $dir === '/' || empty($dir)) {
            $base_path = '/';
            return $base_path;
        }
        
        // If we're in a subdirectory (like admin/), go up one level to get the base
        // Otherwise, use the directory as-is (it's already the base)
        $dir_basename = basename($dir);
        if ($dir_basename === 'admin') {
            $dir = dirname($dir);
            // Handle case where dirname returns '.' or '\' after going up
            if ($dir === '.' || $dir === '\\' || $dir === '/' || empty($dir)) {
                $base_path = '/';
                return $base_path;
            }
        }
        
        // Normalize: ensure it starts with / and ends with /
        // Remove any backslashes (Windows) and normalize forward slashes
        $dir = str_replace('\\', '/', $dir);
        $base_path = '/' . trim($dir, '/');
        if ($base_path !== '/') {
            $base_path .= '/';
        }
        
        return $base_path;
    } catch (Exception $e) {
        // If anything goes wrong, default to root
        error_log("get_base_path() error: " . $e->getMessage());
        $base_path = '/';
        return $base_path;
    }
}

function start_secure_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (!isset($_SESSION['__ip'])) {
        $_SESSION['__ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['__ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    } else {
        if (($_SESSION['__ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '') ||
            ($_SESSION['__ua'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')
        ) {
            session_regenerate_id(true);
            $_SESSION = [];
        }
    }
}

function csrf_token()
{
    start_secure_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify($token)
{
    start_secure_session();
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// function auth_login($email, $password)
// {
//     $conn = get_db();
//     if (!$conn) return false;
//     $st = mysqli_prepare($conn, "SELECT id, email, password_hash, name, role FROM users WHERE email=?");
//     mysqli_stmt_bind_param($st, 's', $email);
//     mysqli_stmt_execute($st);
//     $res = mysqli_stmt_get_result($st);
//     $u = $res ? mysqli_fetch_assoc($res) : null;
//     mysqli_stmt_close($st);
//     if (!$u) return false;
//     if (!password_verify($password, $u['password_hash'])) return false;
//     start_secure_session();
//     session_regenerate_id(true);
//     $_SESSION['user'] = ['id' => (int)$u['id'], 'email' => $u['email'], 'name' => $u['name'], 'role' => $u['role']];
//     return true;
// }

function auth_login($email, $password)
{
    $conn = get_db();
    if (!$conn) return false;

    $st = mysqli_prepare($conn, "SELECT id, email, password_hash, name, role FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $email);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $u = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($st);

    if (!$u) return false; // Email not found
    if (!password_verify($password, $u['password_hash'])) return false; // Invalid password

    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'email' => $u['email'],
        'name' => $u['name'],
        'role' => $u['role']
    ];
    return true;
}


function auth_current_user()
{
    start_secure_session();
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function auth_is_admin()
{
    $u = auth_current_user();
    return $u && isset($u['role']) && $u['role'] === 'admin';
}

function auth_logout()
{
    start_secure_session();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

function require_login()
{
    if (!auth_current_user()) {
        $base = get_base_path();
        header('Location: ' . $base . 'login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

function require_user()
{
    $u = auth_current_user();
    $base = get_base_path();
    if (!$u) {
        header('Location: ' . $base . 'login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? $base . 'index.php'));
        exit;
    }
    if (($u['role'] ?? '') !== 'user') {
        if (($u['role'] ?? '') === 'admin') {
            header('Location: ' . $base . 'admin/index.php');
        } else {
            auth_logout();
            header('Location: ' . $base . 'login.php');
        }
        exit;
    }
    return $u;
}

function require_admin()
{
    if (!auth_is_admin()) {
        $base = get_base_path();
        header('Location: ' . $base . 'admin/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}
