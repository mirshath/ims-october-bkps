<?php
header('Content-Type: application/json');
include 'database/connection.php';  // your DB connection file

if (!isset($_POST['nic'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$nic = $_POST['nic'];

// sanitize input for safety
$nic = trim($nic);
$nic = mysqli_real_escape_string($conn, $nic);

$query = "SELECT 1 FROM students WHERE nic = '$nic' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    // DB error
    echo json_encode(['exists' => false]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
