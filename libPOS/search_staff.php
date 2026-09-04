<?php
// libPOS/search_staff.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Colombo');

// Database connection
$basePath = dirname(__DIR__);
require_once $basePath . '/database/connection.php';

function jsonResponse($data)
{

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    jsonResponse(['error' => 'Database connection failed']);
}

if ($conn->connect_error) {
    jsonResponse(['error' => $conn->connect_error]);
}

if (!isset($_SESSION['username'])) {
    jsonResponse([]);
}

$query = trim($_GET['q'] ?? '');

try {
    if ($query === '') {
        $sql = "
            SELECT
                id,
                title,
                full_name,
                nic
            FROM admin
            ORDER BY full_name
            LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
    } else {
        $search = "%{$query}%";
        $sql = "
            SELECT
                id,
                title,
                full_name,
                nic
            FROM admin
            WHERE full_name LIKE ?
            OR nic LIKE ?
            ORDER BY full_name
            LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $search, $search);
    }

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $fullName = trim(($row['title'] ?? '') . ' ' . ($row['full_name'] ?? ''));
        $data[] = [
            'id' => $row['id'] ?? '',
            'full_name' => $fullName ?: ($row['full_name'] ?? ''),
            'nic' => $row['nic'] ?? ''
        ];
    }

    $stmt->close();
    jsonResponse($data);

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()]);
}
?>