<?php
include("../database/connection.php");

$query = "SELECT id, university_name FROM universities ORDER BY university_name";
$result = $conn->query($query);

$output = '<option value="">-- Select University --</option>';
while($row = $result->fetch_assoc()) {
    $output .= "<option value='{$row['id']}'>{$row['university_name']}</option>";
}

echo $output;
?>