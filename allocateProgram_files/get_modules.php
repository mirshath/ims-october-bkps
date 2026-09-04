<?php
include("../database/connection.php");

$programme_code = $_GET['programme_code'];

if (!$programme_code) {
    echo json_encode(['error' => 'No programme code provided']);
    exit();
}

// Prepare the SQL query to fetch modules based on the selected programme
$sql = "SELECT * FROM modules WHERE programme_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit();
}

$stmt->bind_param("i", $programme_code);
$stmt->execute();
$result = $stmt->get_result();

$modules = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
} else {
    echo json_encode(['error' => 'No modules found']);
}

echo json_encode($modules);

$stmt->close();
$conn->close();
?>
