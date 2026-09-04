<?php
session_start();
include("../database/connection.php");
/** @var mysqli $conn */

$programme_id = $_POST['programme_id'] ?? '';

if (empty($programme_id)) {
    echo json_encode([]);
    exit();
}

$query = "SELECT DISTINCT bt.* 
          FROM batch_table bt
          INNER JOIN feedback_links fl ON bt.id = CAST(fl.batch_id AS UNSIGNED)
          INNER JOIN feedback_submissions fs ON CAST(fl.id AS UNSIGNED) = fs.link_id
          WHERE fl.programme_id = ? AND fl.active = 1
          ORDER BY bt.id";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $programme_id);
$stmt->execute();
$result = $stmt->get_result();

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

header('Content-Type: application/json');
echo json_encode($batches);
?>
