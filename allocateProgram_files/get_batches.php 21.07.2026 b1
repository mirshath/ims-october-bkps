<?php
include("../database/connection.php");

$programme_code = $_GET['programme_code'];

$sql = "SELECT id, batch_name FROM batch_table WHERE programme = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $programme_code);
$stmt->execute();
$result = $stmt->get_result();

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

echo json_encode($batches);

$stmt->close();
$conn->close();
?>
