<?php
include("../database/connection.php");

$query = "SELECT * FROM semester_table";
$result = mysqli_query($conn, $query);

$semesters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $semesters[] = $row;
}

echo json_encode($semesters);
?>