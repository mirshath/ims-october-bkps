<?php
session_start();
include("../database/connection.php");
/** @var mysqli $conn */

$batch_id = $_POST['batch_id'] ?? '';

if (empty($batch_id)) {
    echo json_encode([]);
    exit();
}

$query = "SELECT DISTINCT m.id, m.module_name as name 
          FROM modules m
          INNER JOIN feedback_links fl ON m.id = CAST(fl.module_id AS UNSIGNED)
          INNER JOIN feedback_submissions fs ON CAST(fl.id AS UNSIGNED) = fs.link_id
          WHERE fl.batch_id = ? AND fl.active = 1
          ORDER BY m.module_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $batch_id);
$stmt->execute();
$result = $stmt->get_result();

$modules = [];
while ($row = $result->fetch_assoc()) {
    $modules[] = $row;
}

header('Content-Type: application/json');
echo json_encode($modules);
?>
