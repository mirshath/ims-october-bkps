<?php
// Start output buffering immediately to catch any stray warnings or notices
ob_start();

session_start();

// Disable system error displays to prevent notices from corrupting the JSON stream
error_reporting(0);
ini_set('display_errors', 0);

// Explicitly define return data format
header('Content-Type: application/json; charset=utf-8');

// Find and include database connection regardless of directory nesting level
$paths = [
    __DIR__ . '/../database/connection.php',
    __DIR__ . '/database/connection.php',
    '../database/connection.php',
    'database/connection.php'
];

$conn = null;
foreach ($paths as $path) {
    if (file_exists($path)) {
        include($path);
        if (isset($conn) && $conn) {
            break;
        }
    }
}

// Check database layer status
if (!$conn || $conn->connect_error) {
    ob_clean();
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Validate inbound key identifier
if (!isset($_POST['id']) || trim($_POST['id']) === '') {
    ob_clean();
    echo json_encode(['error' => 'No category ID provided']);
    exit;
}

$id = (int)$_POST['id'];

// Bind parameters using prepared queries to secure data access
$stmt = $conn->prepare("SELECT * FROM category WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        ob_clean();
        echo json_encode(['error' => 'Category record not found']);
    } else {
        $row = $result->fetch_assoc();
        
        // Wipe away session warnings before returning pure JSON output
        ob_clean();
        echo json_encode($row);
    }
    $stmt->close();
} else {
    ob_clean();
    echo json_encode(['error' => 'Failed to build structural statement query']);
}

$conn->close();