<?php
session_start();
include '../database/connection.php';

try {
    $programmeBatch = isset($_GET['programme']) ? trim($_GET['programme']) : '';
    
    // Base query with status from induction_active_table
    $baseSql = "SELECT 
                ies.id,
                ies.allocate_programme_id,
                s.student_code,
                CONCAT(s.title, ' ', s.first_name, ' ', s.last_name) AS full_name,
                s.nic,
                pt.program_name AS programme,
                bt.batch_name AS batch,
                s.mobile AS contact_no,
                ies.email,
                ies.fees_paid,
                ies.attended,
                ies.pack_collected,
                iat.status AS allocation_status
            FROM induction_emails_sent ies
            INNER JOIN allocate_programme ap ON ies.allocate_programme_id = ap.id
            INNER JOIN students s ON ap.student_code = s.student_code
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id
            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
            WHERE (iat.status = 'active' OR iat.status IS NULL)";
    
    $students = [];
    
    // Get all students first
    $sql = $baseSql;
    $params = [];
    $types = "";
    
    if ($programmeBatch) {
        $parts = explode(' - ', $programmeBatch, 2);
        if (count($parts) === 2) {
            $programmeName = $parts[0];
            $batchName = $parts[1];
            $sql .= " AND pt.program_name = ? AND bt.batch_name = ?";
            $params[] = $programmeName;
            $params[] = $batchName;
            $types = "ss";
        } else {
            $sql .= " AND pt.program_name = ?";
            $params[] = $programmeBatch;
            $types = "s";
        }
    }
    
    $sql .= " ORDER BY s.last_name, s.first_name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // If no status in iat, default to active
        $status = $row['allocation_status'] ?? 'active';
        unset($row['allocation_status']);
        
        $students[] = $row;
    }
    
    echo json_encode(['data' => $students]);
} catch (Exception $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
