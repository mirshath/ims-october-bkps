<?php
session_start();
include '../database/connection.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

try {
    if ($role === 'super_admin') {
        $query = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
        $stmt = $conn->prepare($query);
    } else {
        $query = "
            SELECT pt.program_code, pt.program_name 
            FROM program_allocation_user pau
            INNER JOIN program_table pt ON pau.program_code = pt.program_code
            WHERE pau.user_id = ?
            ORDER BY pt.program_name
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    
    echo json_encode($programs);
    
} catch (Exception $e) {
    echo json_encode([]);
}
$conn->close();
?>
