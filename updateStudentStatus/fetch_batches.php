<?php
session_start();
include '../database/connection.php'; // Include your database connection file

if (isset($_POST['program_id'])) {
    $programId = $_POST['program_id'];
    $role = $_SESSION['role'] ?? '';

    // Fetch batches based on the selected program
    if ($role === 'super_admin') {
        $query = "SELECT * FROM batch_table WHERE programme  = '$programId'";
    } else {
        $query = "SELECT * FROM batch_table WHERE programme  = '$programId' AND batch_hide_active = 'active'";
    }
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='{$row['id']}'>{$row['batch_name']}</option>";
        }
    } else {
        echo "<option value=''>No batches available</option>";
    }
}
?>
