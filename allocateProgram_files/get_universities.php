<?php
include("../database/connection.php");


$sql = "SELECT id, university_name FROM universities";
$result = $conn->query($sql);

$universities = [];
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}

echo json_encode($universities);

$conn->close();
?>
