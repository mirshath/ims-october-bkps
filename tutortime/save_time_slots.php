<?php
session_start();
require_once("../database/connection.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Who is the person currently logged in and clicking the button?
$current_logged_user = $_SESSION['user_id'] ?? 0;

// 2. Which tutor are we saving slots FOR? 
// (If Admin selected a tutor, use that. Otherwise, use the logged-in user).
$tutor_id = $_POST['tutor_id'] ?? $current_logged_user;

if (!$current_logged_user) {
    echo "Auth error: No session found.";
    exit;
}

// POST data
$date = $_POST['slot_date'] ?? '';
$slots = json_decode($_POST['slots'] ?? '[]', true);
$alloc_id = $_POST['session_id'] ?? 0;
$program_code = $_POST['program_code'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';

if (!$date || !$alloc_id || empty($slots)) {
    echo "Missing data";
    exit;
}

/* ================= INSERT PREPARE ================= */
// Added 'created_by' to the columns list and a '?' to values
$stmt = $conn->prepare("
    INSERT INTO tutor_time_slots 
    (tutor_id, student_id, slot_date, start_time, end_time, is_hidden, status, created_at, program_code, batch_id, allocation_id, created_by) 
    VALUES (?, NULL, ?, ?, ?, 0, 'Pending', NOW(), ?, ?, ?, ?)
");

if (!$stmt) {
    echo "Prepare failed: " . $conn->error;
    exit;
}

/* ================= LOOP SLOTS ================= */
foreach ($slots as $s) {
    $start = $s['start'] ?? '';
    $end   = $s['end'] ?? '';

    if (!$start || !$end) continue;

    /* ================= OVERLAP CHECK ================= */
    $check = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM tutor_time_slots 
        WHERE tutor_id = ? 
        AND slot_date = ? 
        AND is_hidden = 0 
        AND (
            (start_time < ? AND end_time > ?)
        )
    ");

    $check->bind_param("isss", $tutor_id, $date, $end, $start);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if ($row['cnt'] > 0) continue; 

    /* ================= INSERT ================= */
    // Note: the last 'i' in the string represents created_by
    $stmt->bind_param(
        "issssiii", 
        $tutor_id, 
        $date, 
        $start, 
        $end, 
        $program_code, 
        $batch_id, 
        $alloc_id,
        $current_logged_user // This is the ID of the person who performed the action
    );

    $stmt->execute();
}

$stmt->close();
echo "success";
?>