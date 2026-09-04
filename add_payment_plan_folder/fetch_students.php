<?php
// Database connection
include '../database/connection.php';  // Ensure to include your DB connection file

// Fetch students with active status
$sql = "SELECT s.*, ap.*
        FROM students s
        INNER JOIN allocate_programme ap ON s.student_code = ap.student_code
        WHERE ap.status = 'active'";
$result = mysqli_query($conn, $sql);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

echo json_encode($students);
