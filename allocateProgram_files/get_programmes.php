<?php
include("../database/connection.php");

$university_id = $_GET['university_id'];

$sql = "SELECT program_code, program_name FROM program_table WHERE university_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $university_id);
$stmt->execute();
$result = $stmt->get_result();

$programmes = [];
while ($row = $result->fetch_assoc()) {
    $programmes[] = $row;
}

echo json_encode($programmes);

$stmt->close();
$conn->close();
?>
