<?php
include("../database/connection.php");

$university_id = $_POST['university_id'];

$query = "SELECT program_code, program_name FROM program_table 
          WHERE university_id = ? ORDER BY program_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$result = $stmt->get_result();

$output = '<option value="">-- Select Programme --</option>';
while($row = $result->fetch_assoc()) {
    $output .= "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
}

echo $output;
