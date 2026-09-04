<?php
/**
 * pos-includes/bootstrap.php
 * ---------------------------------------------------------------------
 * Replaces the stall app's own includes/auth.php + includes/db.php.
 * The POS module no longer has its own login system - it reuses the
 * IMS session (login.php / $_SESSION['username']) and the IMS mysqli
 * connection (database/connection.php).
 *
 * Include this file AFTER session_start() and AFTER database/connection.php
 * have already run (struc.php / index.php style), e.g.:
 *
 *   session_start();
 *   include("database/connection.php");   // creates $conn
 *   include("pos-includes/bootstrap.php");
 *   include("includes/header.php");
 * ---------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $conn should already exist from database/connection.php. Fall back to
// including it directly in case a page forgets to do so.
if (!isset($conn)) {
    require_once __DIR__ . '/../database/connection.php';
}

/**
 * Backward-compat shim: the original POS code (checkout.php, receipt.php,
 * pos-includes/products.php, admin pages, etc.) calls get_db() everywhere.
 * Keep that working by just handing back the IMS connection.
 */
function get_db()
{
    global $conn;
    return $conn;
}

/* ---------------------------------------------------------------------
 * CSRF helpers (kept from the original POS module, namespaced so they
 * never collide with anything IMS might add later).
 * ------------------------------------------------------------------- */
function csrf_token()
{
    if (empty($_SESSION['pos_csrf'])) {
        $_SESSION['pos_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['pos_csrf'];
}

function csrf_verify($token)
{
    return is_string($token) && isset($_SESSION['pos_csrf']) && hash_equals($_SESSION['pos_csrf'], $token);
}

/* ---------------------------------------------------------------------
 * Auth helpers wired to the IMS login (login.php sets these on success:
 * $_SESSION['user_id'], $_SESSION['username'], $_SESSION['admin_email'],
 * $_SESSION['role']).
 * ------------------------------------------------------------------- */

// TODO: confirm these match the exact values stored in the `admin.role`
// column of your IMS database, then adjust if needed.
if (!defined('POS_ADMIN_ROLES')) {
    define('POS_ADMIN_ROLES', ['super_admin', 'admin', 'manager', 'data_enter', 'lecture']);
}

function pos_current_user()
{
    if (!isset($_SESSION['username'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['username'],
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

function pos_is_admin()
{
    $u = pos_current_user();
    return $u && in_array($u['role'], POS_ADMIN_ROLES, true);
}

/** Use on normal (HTML) pages - matches the redirect style used in struc.php */
function pos_require_login()
{
    if (!isset($_SESSION['username'])) {
        echo '<script>window.location.href = "login";</script>';
        exit;
    }
}

/** Use on normal (HTML) admin-only pages */
function pos_require_admin()
{
    pos_require_login();
    if (!pos_is_admin()) {
        $_SESSION['message'] = "You don't have permission to access the POS admin area.";
        echo '<script>window.location.href = "pos_store.php";</script>';
        exit;
    }
}

/** Use on JSON/AJAX endpoints (e.g. pos_checkout.php) - no redirect script */
function pos_require_login_json()
{
    if (!isset($_SESSION['username'])) {
        if (ob_get_length() !== false) {
            ob_clean();
        }
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'not_authenticated']);
        exit;
    }
}
