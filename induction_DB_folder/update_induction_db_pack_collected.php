<?php
session_start();
header('Content-Type: application/json');
include '../database/connection.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $packCollected = isset($_POST['pack_collected']) ? intval($_POST['pack_collected']) : 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE induction_emails_sent SET pack_collected = ? WHERE id = ?");
    $stmt->bind_param("ii", $packCollected, $id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Pack collected status updated!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
