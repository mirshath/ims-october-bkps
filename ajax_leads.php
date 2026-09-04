<?php
session_start();
include("database/connection.php");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_status') {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE leads SET status=? WHERE id=?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => true]);
        } elseif ($action === 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM leads WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => true]);
        }
    }
}
?>
