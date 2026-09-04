<?php
include("../database/connection.php");

foreach ($_POST['bms_email'] as $allocate_id => $bms_email) {
    $bms_email = mysqli_real_escape_string($conn, $bms_email);
    mysqli_query($conn, "
        UPDATE allocate_programme a
        JOIN students s ON s.student_code = a.student_code
        SET s.bms_email = '$bms_email'
        WHERE a.id = '$allocate_id' AND a.status = 'active'
    ");
}

echo "BMS Emails updated successfully.";
?>
