<?php
session_start();
// get_student.php
include("database/connection.php");

// Retrieve the student code from the AJAX request and sanitize it
$student_code = mysqli_real_escape_string($conn, $_POST['id']);

$sql = "SELECT * FROM students WHERE student_code = '$student_code'";
$result = mysqli_query($conn, $sql);

$student = array();

if (mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
}

echo json_encode($student);

mysqli_close($conn);
