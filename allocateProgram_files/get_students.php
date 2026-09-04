<?php

include("../database/connection.php");

$sql = "SELECT student_code, first_name, last_name,nic FROM students";
$result = $conn->query($sql);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);

$conn->close();
?>
