<?php
session_start();
include '../database/connection.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

try {
    if ($role === 'super_admin') {
        $query = "
            SELECT 
                t.*, 
                pt.program_name, 
                bt.batch_name 
            FROM induction_email_body_db_table t
            INNER JOIN program_table pt ON t.program_id = pt.program_code
            INNER JOIN batch_table bt ON t.batch_id = bt.id
            ORDER BY t.created_at DESC
        ";
        $stmt = $conn->prepare($query);
    } else {
        $query = "
            SELECT 
                t.*, 
                pt.program_name, 
                bt.batch_name 
            FROM induction_email_body_db_table t
            INNER JOIN program_table pt ON t.program_id = pt.program_code
            INNER JOIN batch_table bt ON t.batch_id = bt.id
            INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
            WHERE pau.user_id = ?
            ORDER BY t.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'templates' => [],
        'message' => $e->getMessage()
    ]);
}
$conn->close();
?>
