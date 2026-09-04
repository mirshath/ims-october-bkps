<?php
session_start();
include '../database/connection.php';

try {
    $user_id = $_SESSION['user_id'] ?? 0;
    $role    = $_SESSION['role'] ?? '';
    $programBatches = [];

    if ($role === 'super_admin') {
        $sql = "SELECT DISTINCT 
                    CONCAT(pt.program_name, ' - ', bt.batch_name) AS programme_batch
                FROM induction_emails_sent ies
                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                INNER JOIN batch_table bt ON ies.batch_id = bt.id
                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                WHERE (iat.status = 'active' OR iat.status IS NULL)
                ORDER BY pt.program_name, bt.batch_name";
        $result = $conn->query($sql);
    } else {
        $sql = "SELECT DISTINCT 
                    CONCAT(pt.program_name, ' - ', bt.batch_name) AS programme_batch
                FROM induction_emails_sent ies
                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                INNER JOIN batch_table bt ON ies.batch_id = bt.id
                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                WHERE (iat.status = 'active' OR iat.status IS NULL) AND pau.user_id = ?
                ORDER BY pt.program_name, bt.batch_name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
    
    while ($row = $result->fetch_assoc()) {
        $programBatches[] = $row['programme_batch'];
    }
    
    echo json_encode($programBatches);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
