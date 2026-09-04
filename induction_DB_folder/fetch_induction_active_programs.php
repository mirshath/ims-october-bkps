<?php
session_start();
include '../database/connection.php';

try {
    // Get all unique program_id and batch_id from induction_emails_sent
    $sql = "SELECT DISTINCT 
                ies.program_id, 
                ies.batch_id
            FROM induction_emails_sent ies
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id";
    
    $result = $conn->query($sql);
    
    $programBatches = [];
    while ($row = $result->fetch_assoc()) {
        $programId = $row['program_id'];
        $batchId = $row['batch_id'];
        
        // Check if this combination exists in induction_active_table
        $checkSql = "SELECT id, status, created_at FROM induction_active_table WHERE program_id = ? AND batch_id = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("si", $programId, $batchId);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        
        $id = null;
        $status = 'active'; // default status
        $createdAt = null;
        
        if ($checkResult->num_rows > 0) {
            $existingRow = $checkResult->fetch_assoc();
            $id = $existingRow['id'];
            $status = $existingRow['status'];
            $createdAt = $existingRow['created_at'];
        }
        
        // Get program and batch names
        $detailsSql = "SELECT program_name FROM program_table WHERE program_code = ?";
        $stmtDetails = $conn->prepare($detailsSql);
        $stmtDetails->bind_param("s", $programId);
        $stmtDetails->execute();
        $programResult = $stmtDetails->get_result();
        $programName = $programResult->fetch_assoc()['program_name'];
        
        $batchSql = "SELECT batch_name FROM batch_table WHERE id = ?";
        $stmtBatch = $conn->prepare($batchSql);
        $stmtBatch->bind_param("i", $batchId);
        $stmtBatch->execute();
        $batchResult = $stmtBatch->get_result();
        $batchName = $batchResult->fetch_assoc()['batch_name'];
        
        $programBatches[] = [
            'id' => $id,
            'program_id' => $programId,
            'batch_id' => $batchId,
            'status' => $status,
            'program_name' => $programName,
            'batch_name' => $batchName,
            'created_at' => $createdAt
        ];
        
        $stmt->close();
        $stmtDetails->close();
        $stmtBatch->close();
    }
    
    // Sort by created_at DESC (nulls last)
    usort($programBatches, function($a, $b) {
        if ($a['created_at'] === null && $b['created_at'] === null) return 0;
        if ($a['created_at'] === null) return 1;
        if ($b['created_at'] === null) return -1;
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode(['success' => true, 'data' => $programBatches]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
