<?php
session_start();
include 'database/connection.php';

try {
    // Get all unique program_id and batch_id from induction_emails_sent
    $sql = "SELECT DISTINCT program_id, batch_id FROM induction_emails_sent";
    $result = $conn->query($sql);
    
    $insertCount = 0;
    $updateCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $programId = $row['program_id'];
        $batchId = $row['batch_id'];
        
        // Insert or update
        $stmt = $conn->prepare("INSERT INTO induction_active_table (program_id, batch_id, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("si", $programId, $batchId);
        
        if ($stmt->execute()) {
            if ($stmt->insert_id > 0) {
                $insertCount++;
            } else {
                $updateCount++;
            }
        }
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Initialized successfully! Inserted: $insertCount, Updated: $updateCount"
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
