<?php
session_start();
include("../database/connection.php");

$programme_id = $_POST['programme_id'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';
$module_id = $_POST['module_id'] ?? '';

if (empty($programme_id) || empty($batch_id) || empty($module_id)) {
    echo json_encode([]);
    exit();
}

// Fetch lecturers who have feedback for this exact Programme/Batch/Module combo
$query = "SELECT DISTINCT l.id, l.lecturer_name as username, a.full_name 
          FROM lecturer_table l
          INNER JOIN feedback f ON l.id = f.lecturer_id
          LEFT JOIN admin a ON l.lecturer_name = a.username
          WHERE f.program_id = ? AND f.batch_id = ? AND f.module_id = ?
          ORDER BY l.lecturer_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $programme_id, $batch_id, $module_id);
$stmt->execute();
$result = $stmt->get_result();

$lecturers = [];
while ($row = $result->fetch_assoc()) {
    $display_name = $row['username'];
    if (!empty($row['full_name'])) {
        $display_name .= ' (' . $row['full_name'] . ')';
    }
    $lecturers[] = [
        'id' => $row['id'],
        'name' => $display_name
    ];
}

header('Content-Type: application/json');
echo json_encode($lecturers);
