<?php
session_start();
require_once("../database/connection.php");

$tutor_id = $_SESSION['user_id'] ?? 0;

$date = $_POST['date'] ?? '';
$session_id = $_POST['session_id'] ?? 0;

if (!$tutor_id || !$date || !$session_id) {
    echo json_encode([]);
    exit;
}

// Get already booked slots (not hidden)
$stmt = $conn->prepare("
    SELECT start_time, end_time
    FROM tutor_time_slots
    WHERE tutor_id = ?
    AND slot_date = ?
    AND is_hidden = 0
");

$stmt->bind_param("is", $tutor_id, $date);
$stmt->execute();

$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

echo json_encode($slots);