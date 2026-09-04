<?php
session_start();
include 'database/connection.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $currentPack = isset($_POST['pack']) ? intval($_POST['pack']) : 0;
    $newPack = $currentPack == 1 ? 0 : 1;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }

    $sql = "UPDATE induction_emails_sent SET pack_collected = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $newPack, $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
