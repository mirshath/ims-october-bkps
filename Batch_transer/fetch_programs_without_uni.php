<?php

session_start();
include("../database/connection.php");

// Get session data only (not university)
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

$output = '<option value="">-- Select Programme --</option>';

if ($role === 'super_admin') {
    // Super admin: get all programs (no university WHERE)
    $query = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
    $stmt = $conn->prepare($query);
    // no bind_param needed
} else {
    // Other users: only programs allocated to the user, no university WHERE
    $query = "
        SELECT pt.program_code, pt.program_name
        FROM program_allocation_user AS pau
        INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
        WHERE pau.user_id = ?
        ORDER BY pt.program_name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}

// Execute and fetch
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $output .= "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
}

echo $output;
