<?php
include("../database/connection.php");

if (isset($_POST['program_id']) && isset($_POST['batch_id'])) {
    $program_id = $_POST['program_id'];
    $batch_id = $_POST['batch_id'];

    $query = "
        SELECT allocate_programme.*, 
               program_table.*, 
               batch_table.*, 
               students.* 
        FROM allocate_programme
        JOIN program_table ON allocate_programme.programme_code = program_table.program_code 
        JOIN batch_table ON allocate_programme.batch_id = batch_table.id 
        JOIN students ON allocate_programme.student_code = students.student_code 
        WHERE allocate_programme.programme_code = '$program_id' 
          AND allocate_programme.batch_id = '$batch_id'
    ";

    $result = mysqli_query($conn, $query);

    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }

    echo json_encode($students);
}
