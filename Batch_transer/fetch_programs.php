<?php
// include("../database/connection.php");

// $university_id = $_POST['university_id'];

// $query = "SELECT program_code, program_name FROM program_table 
//           WHERE university_id = ? ORDER BY program_name";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("i", $university_id);
// $stmt->execute();
// $result = $stmt->get_result();

// $output = '<option value="">-- Select Programme --</option>';
// while($row = $result->fetch_assoc()) {
//     $output .= "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
// }

// echo $output;

// ----------- 2025.10.16 updated new changed ----- 



session_start();
include("../database/connection.php");

// Get POST and session data
$university_id = $_POST['university_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

$output = '<option value="">-- Select Programme --</option>';

if ($role === 'super_admin') {
    // Super admin: get all programs for the university
    $query = "SELECT program_code, program_name FROM program_table 
              WHERE university_id = ? ORDER BY program_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $university_id);
} else {
    // Other users: only programs allocated to the user
    $query = "
        SELECT pt.program_code, pt.program_name
        FROM program_allocation_user AS pau
        INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
        WHERE pau.user_id = ? AND pt.university_id = ?
        ORDER BY pt.program_name
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $university_id);
}

// Execute and fetch
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $output .= "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
}

echo $output;



?>