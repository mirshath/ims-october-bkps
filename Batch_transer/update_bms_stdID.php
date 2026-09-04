<?php

include("../database/connection.php");

if (!empty($_POST['student_registration_id'])) {
    foreach ($_POST['student_registration_id'] as $allocate_id => $student_registration_id) {
        $student_registration_id = mysqli_real_escape_string($conn, $student_registration_id);

        $sql = "
            UPDATE allocate_programme a
            JOIN students s ON s.student_code = a.student_code
            SET a.student_registration_id = '$student_registration_id'
            WHERE a.id = '$allocate_id' AND a.status = 'active'
        ";

        if (!mysqli_query($conn, $sql)) {
            echo "Error: " . mysqli_error($conn);
            exit;
        }
    }
    echo "Student Registration IDs updated successfully.";
} else {
    echo "No data received.";
}

// ---------------- 
// ---------------- 
