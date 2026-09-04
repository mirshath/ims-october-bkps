<?php
ob_start();
error_reporting(0); // Disable error reporting to prevent breaking JSON output
session_start();
include('../database/connection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in again.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $link_id = $_POST['link_id'] ?? '';
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

    if (empty($link_id)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid link ID.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE feedback_links SET active = ? WHERE link_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("is", $active, $link_id);

        if ($stmt->execute()) {
        $status_text = ($active == 1) ? 'Active' : 'Inactive';
        ob_clean(); // Clear buffer before sending JSON
        echo json_encode(['status' => 'success', 'message' => "Link status updated to $status_text successfully."]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    ob_clean(); // Clear buffer before sending JSON
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
    
    $conn->close();
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>