<?php
session_start();
include("../database/connection.php");

header('Content-Type: application/json');

$programme_code = $_GET['programme_code'] ?? '';
$role = $_SESSION['role'] ?? '';

if ($programme_code === '') {
    echo json_encode([]);
    exit;
}

if ($role === 'super_admin') {
    $sql = "SELECT id, batch_name, batch_hide_active
            FROM batch_table
            WHERE programme = ?
            ORDER BY batch_name ASC";
} else {
    $sql = "SELECT id, batch_name, batch_hide_active
            FROM batch_table
            WHERE programme = ? AND batch_hide_active = 'active'
            ORDER BY batch_name ASC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $programme_code);
$stmt->execute();
$result = $stmt->get_result();

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

echo json_encode($batches);

$stmt->close();
$conn->close();
