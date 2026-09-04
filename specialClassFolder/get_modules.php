<?php
session_start();
include("../database/connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['programme_id']) || empty($_POST['programme_id']) || 
    !isset($_POST['batch_id']) || empty($_POST['batch_id'])) {
    echo json_encode(['error' => 'Programme ID and Batch ID are required']);
    exit;
}

$programmeId = mysqli_real_escape_string($conn, $_POST['programme_id']);
$batchId = mysqli_real_escape_string($conn, $_POST['batch_id']);

// Query to get modules for the selected programme and batch
$query = "SELECT id, module_name FROM modules WHERE programme_id = ? ORDER BY module_name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $programmeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$modules = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
}

// Return modules as JSON
echo json_encode($modules);
exit;
