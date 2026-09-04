<?php
include("../database/connection.php");

$query = "SELECT id, semester_name as name FROM semester_table ORDER BY semester_name";
$result = mysqli_query($conn, $query);

$semesters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $semesters[] = $row;
}

echo json_encode($semesters);
?>
