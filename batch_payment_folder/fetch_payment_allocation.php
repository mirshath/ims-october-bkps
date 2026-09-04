<?php
include("../database/connection.php");

// Get parameters from POST request
$programme_id = $_POST['programme_id'] ?? null;
$batch_id = $_POST['batch_id'] ?? null;

if (empty($programme_id) || empty($batch_id)) {
    echo json_encode(['success' => false, 'message' => 'Programme ID and Batch ID are required']);
    exit;
}

// Prepare query to fetch from payment_batch_allocation
$query = "SELECT * FROM payment_batch_allocation WHERE programme_id = ? AND batch_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $programme_id, $batch_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'No payment plan found for this programme and batch']);
}

$stmt->close();
$conn->close();
?>