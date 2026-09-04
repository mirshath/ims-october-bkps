<?php
// Start output buffering to capture any accidental warnings/notices
ob_start();

session_start();

// Suppress error displays completely
error_reporting(0);
ini_set('display_errors', 0);

// Set strict JSON header
header('Content-Type: application/json; charset=utf-8');

// Database connection with path array
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
        break;
    }
}

// Double check database variable instance compatibility
if (!isset($conn) || !$conn) {
    ob_clean(); // Wipe out any printed warnings
    echo json_encode(['error' => 'Database connection variable not found']);
    exit;
}

if ($conn->connect_error) {
    ob_clean();
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || trim($_POST['id']) === '') {
    ob_clean();
    echo json_encode(['error' => 'No book ID provided']);
    exit;
}

$id = (int)$_POST['id'];

// Secure statement preparation
$stmt = $conn->prepare("
    SELECT books.*, category.name AS category_name, books.id AS bookid 
    FROM books 
    LEFT JOIN category ON category.id = books.category_id 
    WHERE books.id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Clear any notice warnings written to the buffer before printing clean JSON
        ob_clean();
        echo json_encode(['error' => 'Book not found']);
    } else {
        $row = $result->fetch_assoc();
        
        // CRITICAL STEP: Erase everything else out of the buffer memory 
        // (including that session notice), leaving it completely pristine.
        ob_clean();
        echo json_encode($row);
    }
    $stmt->close();
} else {
    ob_clean();
    echo json_encode(['error' => 'Failed to prepare database statement']);
}

$conn->close();