<?php
/**
 * ajax/_bootstrap.php
 *
 * Included by every ajax/*.php endpoint in this feature.
 * Purpose: guarantee the response is ALWAYS valid JSON, even if something
 * fatals below (wrong path, undefined $conn, SQL error, etc). This is what
 * turns a silent "request error" in the browser into a readable message.
 */

session_start();

// Never let raw PHP notices/warnings/fatals print into the response body -
// that's what breaks JSON parsing client-side.
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');

// Buffer output so we can discard any stray whitespace/warnings that leak
// out before we get a chance to send clean JSON.
ob_start();

function ajax_fail($message, $extra = []) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message
    ], $extra));
    exit();
}

// Catch fatal errors (e.g. include failed, undefined function/class) that
// would otherwise print an HTML error page instead of JSON.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal PHP error: ' . $error['message'],
            'file'    => $error['file'],
            'line'    => $error['line']
        ]);
    }
});

set_exception_handler(function ($e) {
    ajax_fail('Unhandled exception: ' . $e->getMessage());
});

if (!isset($_SESSION['username'])) {
    ajax_fail('Not authenticated (session missing/expired).');
}

/*
 * ---- Locate and include database/connection.php ----
 * Adjust CONNECTION_PATH below if your project's connection file lives
 * somewhere else relative to this ajax/ folder.
 */
$connectionCandidates = [
    __DIR__ . '/../database/connection.php',
    __DIR__ . '/../../database/connection.php',
];

$connectionFile = null;
foreach ($connectionCandidates as $candidate) {
    if (file_exists($candidate)) {
        $connectionFile = $candidate;
        break;
    }
}

if ($connectionFile === null) {
    ajax_fail(
        'Could not find database/connection.php. Checked: ' . implode(', ', $connectionCandidates) .
        '. Edit CONNECTION_PATH candidates in ajax/_bootstrap.php to match your project structure.'
    );
}

require $connectionFile;

/*
 * ---- Verify we actually got a usable mysqli connection in $conn ----
 * If your connection.php uses a different variable name or PDO, this is
 * where it will tell you clearly instead of failing silently later.
 */
if (!isset($conn)) {
    ajax_fail('database/connection.php did not define a $conn variable. Check the variable name it actually uses (e.g. $mysqli, $db, $pdo) and update these ajax files to match.');
}

if (!($conn instanceof mysqli)) {
    ajax_fail('$conn is not a mysqli instance (got ' . gettype($conn) . '). These ajax files use mysqli syntax ($conn->prepare, $conn->query) — if your project uses PDO, the queries need to be rewritten for PDO.');
}

if ($conn->connect_error) {
    ajax_fail('Database connection failed: ' . $conn->connect_error);
}
