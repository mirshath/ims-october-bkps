<?php
session_start();
include("../database/connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Check if programme_id is provided
if (!isset($_POST['programme_id']) || empty($_POST['programme_id'])) {
    echo json_encode(['error' => 'Programme ID is required']);
    exit;
}

$programmeId = mysqli_real_escape_string($conn, $_POST['programme_id']);

// Query to get batches for the selected programme
$query = "SELECT id, batch_name FROM batch_table WHERE programme = ? ORDER BY batch_name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $programmeId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$batches = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $batches[] = $row;
    }
}

// Return batches as JSON
echo json_encode($batches);
exit;
