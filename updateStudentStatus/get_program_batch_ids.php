<?php
include('../database/connection.php');

header('Content-Type: application/json');

$program_name = $_POST['program_name'] ?? '';
$batch_name = $_POST['batch_name'] ?? '';

if (!$program_name || !$batch_name) {
    echo json_encode(['error' => true, 'message' => 'Program name or batch name missing']);
    exit;
}

// Fetch program_code
$progSql = "SELECT program_code FROM program_table WHERE program_name = ?";
$stmt = $conn->prepare($progSql);
$stmt->bind_param("s", $program_name);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['error' => true, 'message' => 'Program not found']);
    exit;
}
$program_code = $result->fetch_assoc()['program_code'];

// Fetch batch_id
$batchSql = "SELECT id FROM batch_table WHERE batch_name = ?";
$stmt2 = $conn->prepare($batchSql);
$stmt2->bind_param("s", $batch_name);
$stmt2->execute();
$result2 = $stmt2->get_result();
if ($result2->num_rows == 0) {
    echo json_encode(['error' => true, 'message' => 'Batch not found']);
    exit;
}
$batch_id = $result2->fetch_assoc()['id'];

echo json_encode(['error' => false, 'program_code' => $program_code, 'batch_id' => $batch_id]);
