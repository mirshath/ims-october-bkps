<?php
session_start();
include("../database/connection.php");

$programme_id = $_POST['programme_id'] ?? '';

if (empty($programme_id)) {
    echo json_encode([]);
    exit();
}

// Fetch batches for this programme that have feedback
$query = "SELECT DISTINCT bt.* 
          FROM batch_table bt
          INNER JOIN feedback f ON bt.id = f.batch_id
          WHERE bt.programme = ?
          ORDER BY bt.id";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $programme_id);
$stmt->execute();
$result = $stmt->get_result();

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

header('Content-Type: application/json');
echo json_encode($batches);
