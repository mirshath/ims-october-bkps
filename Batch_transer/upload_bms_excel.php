<?php
session_start();
include("../database/connection.php");

if(isset($_POST['excelData'])) {
    $rows = json_decode($_POST['excelData'], true);
    $report = [];

    foreach($rows as $key => $row) {
        if($key == 0) continue; // skip header row

        $student_code = trim($row[1]); // Column B: Student Code
        $bms_email = trim($row[4]);    // Column E: BMS Email

        if(empty($student_code)) {
            $report[] = "Row ".($key+1)." skipped: Student Code empty.";
            continue;
        }

        $check = mysqli_query($conn, "SELECT bms_email FROM students WHERE student_code = '$student_code'");
        if(mysqli_num_rows($check) > 0) {
            $update = mysqli_query($conn, "UPDATE students SET bms_email = '$bms_email' WHERE student_code = '$student_code'");
            if($update) {
                $report[] = "Row ".($key+1)." updated successfully.";
            } else {
                $report[] = "Row ".($key+1)." failed to update.";
            }
        } else {
            $report[] = "Row ".($key+1)." skipped: Student Code not found.";
        }
    }

    echo implode("<br>", $report);
} else {
    echo "No Excel data received.";
}
?>
