<?php
session_start();
include("database/connection.php");

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required data is provided
if (!isset($_POST['id']) || !isset($_POST['pack_collected'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$id = intval($_POST['id']);
$pack_collected = intval($_POST['pack_collected']);

// Validate pack_collected value (should be 0 or 1)
if ($pack_collected !== 0 && $pack_collected !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid pack_collected value']);
    exit();
}

try {
    // Update the pack_collected status in the database
    $sql = "UPDATE induction_students SET pack_collected = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $pack_collected, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Pack collected status updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No changes made or student not found'
            ]);
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>