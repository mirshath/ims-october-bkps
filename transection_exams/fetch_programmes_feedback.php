<?php
session_start();
include("../database/connection.php");

// Get current user info from session
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

// Initialize query
if ($role === 'super_admin') {
    // Super admin gets all programs that have feedback
    $query = "SELECT DISTINCT pt.* 
              FROM program_table pt
              INNER JOIN feedback f ON pt.program_code = f.program_id
              ORDER BY pt.program_name";
} else {
    // Other users get only allocated programs that have feedback
    $query = "
        SELECT DISTINCT pt.*
        FROM program_allocation_user AS pau
        INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
        INNER JOIN feedback f ON pt.program_code = f.program_id
        WHERE pau.user_id = ?
        ORDER BY pt.program_name
    ";
}

// Prepare and execute query securely
if ($role === 'super_admin') {
    $result = mysqli_query($conn, $query);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Fetch results
$programmes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $programmes[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($programmes);
