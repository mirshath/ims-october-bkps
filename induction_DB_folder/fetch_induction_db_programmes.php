<?php
session_start();
include '../database/connection.php';

try {
    $programmeBatches = [];
    $sql = "SELECT DISTINCT 
                CONCAT(pt.program_name, ' - ', bt.batch_name) AS programme_batch
            FROM induction_emails_sent ies
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id
            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
            WHERE (iat.status = 'active' OR iat.status IS NULL)
            ORDER BY pt.program_name, bt.batch_name";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $programmeBatches[] = $row['programme_batch'];
    }
    
    echo json_encode($programmeBatches);
} catch (Exception $e) {
    echo json_encode([]);
}
