<?php
session_start();
include("database/connection.php");

// Check if the request is an AJAX POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lead_id = $_POST['lead_id'];
    $status = $_POST['status'];

    // Update the lead status
    $query = "UPDATE leads SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $lead_id);
    
    if ($stmt->execute()) {
        // Respond with a success message
        echo json_encode(['status' => 'success']);
    } else {
        // Respond with an error message
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>
