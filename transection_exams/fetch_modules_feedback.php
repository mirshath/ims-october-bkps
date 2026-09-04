<?php
session_start();
include("../database/connection.php");

$batch_id = $_POST['batch_id'] ?? '';

if (empty($batch_id)) {
    echo json_encode([]);
    exit();
}

// Fetch modules that have feedback for this specific batch
$query = "SELECT DISTINCT m.id, m.module_name as name 
          FROM modules m
          INNER JOIN feedback f ON m.id = f.module_id
          WHERE f.batch_id = ?
          ORDER BY m.module_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

$modules = [];
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}

header('Content-Type: application/json');
echo json_encode($modules);
