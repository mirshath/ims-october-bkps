<?php
include 'database/connection.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

header('Content-Type: application/json');

$query = "SELECT DISTINCT programme FROM induction_students WHERE programme IS NOT NULL AND programme != '' ORDER BY programme ASC";
$result = $conn->query($query);

$programmes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $programmes[] = $row['programme'];
    }
}

echo json_encode($programmes, JSON_UNESCAPED_UNICODE);
$conn->close();
?>