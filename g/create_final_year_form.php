<?php
session_start();

// Don't let stray PHP warnings/notices leak into the JSON output.
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

include(__DIR__ . "/../database/connection.php");

header('Content-Type: application/json');

// Make mysqli throw exceptions instead of silently returning false on
// prepare()/execute() errors (e.g. missing table) - so our catch block
// below can report the *real* reason instead of a generic failure.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['username'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit();
}

$created_by   = $_SESSION['username'];
$programme_id = $_POST['programme_id'] ?? '';
$batch_id     = $_POST['batch_id'] ?? '';
$module_id    = $_POST['module_id'] ?? '';
$lecturer_id  = $_POST['lecturer_id'] ?? '';
$criteria     = $_POST['criteria'] ?? [];        // array of strings, e.g. ["Presentation","Preparation",...]
$commentFields = $_POST['comment_fields'] ?? [];  // array of strings, e.g. ["Additional Comments"]

// ---------------------------------------------------------------
// Basic validation
// ---------------------------------------------------------------
if ($programme_id === '' || $batch_id === '' || $module_id === '' || $lecturer_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'Programme, batch, module and lecturer are all required.']);
    exit();
}
if (!is_array($criteria) || count($criteria) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'At least one criteria field is required.']);
    exit();
}
if (!is_array($commentFields) || count($commentFields) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'At least one comments field is required.']);
    exit();
}

// ---------------------------------------------------------------
// Duplicate check: same Programme + Batch + Module + Lecturer
// ---------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT id, link_id FROM feedback_links
     WHERE programme_id = ? AND batch_id = ? AND module_id = ? AND lecturer_id = ?
     LIMIT 1"
);
$stmt->bind_param("ssss", $programme_id, $batch_id, $module_id, $lecturer_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $existing_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/g/feedback.php?id=' . $existing['link_id'];

    ob_end_clean();
    echo json_encode([
        'status'         => 'duplicate',
        'message'        => 'This feedback link already exists!',
        'link'           => $existing_link,
        'programme_name' => fetchName($conn, 'program_table', 'program_name', 'program_code', $programme_id),
        'batch_name'     => fetchName($conn, 'batch_table', 'batch_name', 'id', $batch_id),
        'module_name'    => fetchName($conn, 'modules', 'module_name', 'id', $module_id),
        'lecturer_name'  => fetchLecturerDisplayName($conn, $lecturer_id),
    ]);
    exit();
}

$conn->begin_transaction();

try {
    // -------------------------------------------------------------
    // 1) Create a new feedback_links row for this combination.
    // -------------------------------------------------------------
    $prog_name = fetchName($conn, "program_table", "program_name", "program_code", $programme_id);
    $batch_name = fetchName($conn, "batch_table", "batch_name", "id", $batch_id);
    $module_name = fetchName($conn, "modules", "module_name", "id", $module_id);
    $lecturer_name = fetchName($conn, "lecturer_table", "lecturer_name", "id", $lecturer_id);

    $base_slug = strtolower(trim(preg_replace(
        '/[^a-z0-9]+/i',
        '-',
        "$prog_name $batch_name $module_name $lecturer_name"
    ), '-'));
    $slug = $base_slug . '-' . substr(bin2hex(random_bytes(3)), 0, 5);

    $stmt = $conn->prepare(
        "INSERT INTO feedback_links (link_id, programme_id, batch_id, module_id, lecturer_id, active, created_by)
         VALUES (?, ?, ?, ?, ?, 1, ?)"
    );
    $stmt->bind_param("ssssss", $slug, $programme_id, $batch_id, $module_id, $lecturer_id, $created_by);
    $stmt->execute();
    $link_row_id = $stmt->insert_id;
    $stmt->close();

    // -------------------------------------------------------------
    // 2) Insert the custom form fields for this new link.
    // -------------------------------------------------------------
    $stmt = $conn->prepare(
        "INSERT INTO feedback_form_fields (link_id, field_type, field_label, field_order) VALUES (?, ?, ?, ?)"
    );

    $order = 0;
    foreach ($criteria as $label) {
        $label = trim($label);
        if ($label === '') continue;
        $type = 'rating';
        $stmt->bind_param("issi", $link_row_id, $type, $label, $order);
        $stmt->execute();
        $order++;
    }
    foreach ($commentFields as $label) {
        $label = trim($label);
        if ($label === '') continue;
        $type = 'comment';
        $stmt->bind_param("issi", $link_row_id, $type, $label, $order);
        $stmt->execute();
        $order++;
    }
    $stmt->close();

    $conn->commit();

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $link_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/g/feedback.php?id=" . $slug;

    ob_end_clean();
    echo json_encode([
        'status'   => 'success',
        'message'  => 'Form saved successfully.',
        'link_id'  => $link_row_id,
        'slug'     => $slug,
        'link'     => $link_url,
        'field_count' => $order
    ]);
} catch (\Throwable $e) {
    // Catches mysqli exceptions AND fatal errors (e.g. missing table,
    // bad column) so the browser always gets valid JSON back.
    if ($conn->in_transaction ?? true) {
        @$conn->rollback();
    }
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Failed to save form: ' . $e->getMessage()]);
}

/**
 * Small helper to look up a display name by id/code.
 */
function fetchName($conn, $table, $nameCol, $keyCol, $keyVal)
{
    $stmt = $conn->prepare("SELECT `$nameCol` AS name FROM `$table` WHERE `$keyCol` = ? LIMIT 1");
    $stmt->bind_param("s", $keyVal);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['name'] ?? 'unknown';
}

function fetchLecturerDisplayName($conn, $lecturer_id)
{
    $stmt = $conn->prepare(
        "SELECT l.lecturer_name AS username, a.full_name
         FROM lecturer_table l
         LEFT JOIN admin a ON l.lecturer_name = a.username
         WHERE l.id = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $lecturer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $display_name = $row['username'] ?? 'N/A';
    if (!empty($row['full_name'])) {
        $display_name .= ' (' . $row['full_name'] . ')';
    }
    return $display_name;
}
