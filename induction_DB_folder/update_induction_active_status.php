<?php
session_start();
include '../database/connection.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $programId = isset($_POST['program_id']) ? trim($_POST['program_id']) : '';
    $batchId = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    if (!in_array($status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    if ($id > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE induction_active_table SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else if ($programId && $batchId) {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO induction_active_table (program_id, batch_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)");
        $stmt->bind_param("sis", $programId, $batchId, $status);
        
        if ($stmt->execute()) {
            $newId = $stmt->insert_id > 0 ? $stmt->insert_id : null;
            echo json_encode(['success' => true, 'message' => 'Status saved successfully', 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving status: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
