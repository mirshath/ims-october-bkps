<?php
session_start();
include(__DIR__ . '/../database/connection.php'); // adjust path to match your project structure

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$program_id = isset($_POST['program_id']) ? trim($_POST['program_id']) : '';
$batch_id   = isset($_POST['batch_id']) ? trim($_POST['batch_id']) : '';

if ($program_id === '' || $batch_id === '') {
    echo json_encode(['success' => false, 'message' => 'Missing program_id or batch_id']);
    exit;
}

// NOTE: Adjust $conn / mysqli usage below if your connection.php exposes a
// different variable (e.g. $pdo) or uses PDO instead of mysqli.
try {
    $stmt = $conn->prepare(
        "SELECT id FROM induction_email_body_db_table WHERE program_id = ? AND batch_id = ? LIMIT 1"
    );
    $stmt->bind_param("si", $program_id, $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        'success' => true,
        'exists'  => $result->num_rows > 0
    ]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}