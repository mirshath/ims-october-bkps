<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID required']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM lib_item WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'item' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
$stmt->close();
?>