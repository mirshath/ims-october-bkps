<?php
session_start();
include("../database/connection.php");

if (isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];
    $role = $_SESSION['role'] ?? '';
    
    if ($role === 'super_admin') {
        $batches_query = "SELECT * FROM batch_table WHERE programme  = '$program_id'"; // Adjust the query according to your table structure
    } else {
        $batches_query = "SELECT * FROM batch_table WHERE programme  = '$program_id' AND batch_hide_active = 'active'"; // Adjust the query according to your table structure
    }
    $batches_result = mysqli_query($conn, $batches_query);
    $batches = mysqli_fetch_all($batches_result, MYSQLI_ASSOC);
    
    echo json_encode($batches);
}
?>
