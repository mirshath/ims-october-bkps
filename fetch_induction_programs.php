<?php
session_start();
include 'database/connection.php';

// Get distinct programmes from induction_students table
$result = $conn->query("SELECT DISTINCT programme FROM induction_students WHERE programme IS NOT NULL AND programme != '' ORDER BY programme");

$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = $row['programme'];
}

echo json_encode($programs);
?>
