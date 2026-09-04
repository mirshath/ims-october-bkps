<?php
//session_start();
include("../../database/connection.php");

// Default university (since your create_session page doesn't send it)
$university_id = 1;

$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';

if ($role === 'super_admin') {
    // ✅ Super Admin → see all programs
    $query = "
        SELECT program_code, program_name 
        FROM program_table 
        WHERE university_id = ? 
        ORDER BY program_name
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $university_id);

} else {
    // ✅ Normal Users → only assigned programs
    $query = "
        SELECT pt.program_code, pt.program_name
        FROM program_allocation_user pau
        INNER JOIN program_table pt 
            ON pau.program_code = pt.program_code
        WHERE pt.university_id = ? 
        AND pau.user_id = ?
        ORDER BY pt.program_name
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $university_id, $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Dropdown options
echo "<option value=''>-- Select Program --</option>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<option value='" . $row['program_code'] . "'>" 
        . htmlspecialchars($row['program_name']) . 
        "</option>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>