<?php
// include("../../database/connection.php");

// if(isset($_POST['university_id'])) {
//     $university_id = mysqli_real_escape_string($conn, $_POST['university_id']);

//     $query = "SELECT program_code, program_name FROM program_table WHERE university_id = ?";
//     $stmt = mysqli_prepare($conn, $query);
//     mysqli_stmt_bind_param($stmt, "i", $university_id);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);

//     echo "<option value=''>Select Program</option>";
//     while($row = mysqli_fetch_assoc($result)) {
//         echo "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
//     }

//     mysqli_stmt_close($stmt);
// }
// mysqli_close($conn);

// ------------------------------------------ 22.09.2025 ------------------ 

session_start();
include("../../database/connection.php");

if (isset($_POST['university_id'])) {
    $university_id = mysqli_real_escape_string($conn, $_POST['university_id']);
    $user_id = $_SESSION['user_id'] ?? 0;
    $role    = $_SESSION['role'] ?? '';

    if ($role === 'super_admin') {
        // Super admin: all programs from selected university
        $query = "
            SELECT program_code, program_name 
            FROM program_table 
            WHERE university_id = ? 
            ORDER BY program_name
        ";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $university_id);
    } else {
        // Other users: only allocated programs within selected university
        $query = "
            SELECT pt.program_code, pt.program_name
            FROM program_allocation_user pau
            INNER JOIN program_table pt ON pau.program_code = pt.program_code
            WHERE pt.university_id = ? AND pau.user_id = ?
            ORDER BY pt.program_name
        ";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $university_id, $user_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    echo "<option value=''>Select Program</option>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
    }

    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
// ------------------------------------------------- 
