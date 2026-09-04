<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

if (!isset($_GET['catagory_id'])) {
    echo json_encode(['success' => false, 'sub_categories' => []]);
    exit;
}

$catagory_id = (int)$_GET['catagory_id'];
$stmt = $conn->prepare("SELECT id, sub_catagory_name FROM lib_sub_category WHERE catagory_id = ? AND is_active = 1 ORDER BY sub_catagory_name");
$stmt->bind_param("i", $catagory_id);
$stmt->execute();
$result = $stmt->get_result();

$sub_categories = [];
while ($row = $result->fetch_assoc()) {
    $sub_categories[] = $row;
}

echo json_encode(['success' => true, 'sub_categories' => $sub_categories]);
$stmt->close();
?>