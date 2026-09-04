<?php
session_start();
include("../database/connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Check if program_id is provided
if (!isset($_POST['program_id']) || empty($_POST['program_id'])) {
    echo json_encode(['compulsory' => [], 'elective' => []]);
    exit;
}

$program_id = mysqli_real_escape_string($conn, $_POST['program_id']);

// Query to get modules for the selected program, separated by type
$query = "SELECT id, module_name, type FROM modules WHERE programme_id = ? ORDER BY module_name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $program_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$compulsoryModules = [];
$electiveModules = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['type'] === 'Compulsory') {
        $compulsoryModules[] = [
            'id' => $row['id'],
            'module_name' => $row['module_name']
        ];
    } else {
        $electiveModules[] = [
            'id' => $row['id'],
            'module_name' => $row['module_name']
        ];
    }
}

// Return modules as JSON, separated by type
echo json_encode([
    'compulsory' => $compulsoryModules,
    'elective' => $electiveModules
]);
?>
