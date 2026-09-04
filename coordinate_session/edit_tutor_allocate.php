<?php
include("../database/connection.php");
$current_user_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$allocate_id = intval($_POST['allocate_id'] ?? 0);
$tutor_id    = $_POST['tutor_id'] ?? '';
$session_id  = $_POST['session_id'] ?? '';

if (!$allocate_id) {
    echo json_encode(['status'=>'error', 'msg'=>'Invalid allocation ID']);
    exit;
}

// If both tutor and session are empty → unallocate
if ($tutor_id === '' && $session_id === '') {
    $stmt = $conn->prepare("DELETE FROM tutor_allocate WHERE id=?");
    $stmt->bind_param("i", $allocate_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status'=>'success', 'msg'=>'Unallocated successfully']);
    exit;
}

// Otherwise, update allocation
$stmt = $conn->prepare("UPDATE tutor_allocate SET tutor_id=?, session_id=?, created_by=? WHERE id=?");
$stmt->bind_param("iiii", $tutor_id, $session_id, $current_user_id, $allocate_id);
$stmt->execute();
$stmt->close();

echo json_encode(['status'=>'success', 'msg'=>'Allocation updated successfully']);
