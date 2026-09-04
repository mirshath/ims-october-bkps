<?php
session_start();
include("../../database/connection.php");

if(isset($_POST['program_id'])) {
    $program_id = mysqli_real_escape_string($conn, $_POST['program_id']);
    $role = $_SESSION['role'] ?? '';
    
    if ($role === 'super_admin') {
        $query = "SELECT id, batch_name FROM batch_table WHERE programme = ?";
    } else {
        $query = "SELECT id, batch_name FROM batch_table WHERE programme = ? AND batch_hide_active = 'active'";
    }
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $program_id); // Changed from "i" to "s" because program_code is VARCHAR
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    echo "<option value=''>Select Batch</option>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['id']}'>{$row['batch_name']}</option>";
    }
    
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
?>