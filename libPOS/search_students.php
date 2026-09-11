<?php
session_start();
// libPOS/search_students.php
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
$students = [];

try {
    if ($query === '') {
        $sql = "
            SELECT
                ap.student_code,
                ap.student_registration_id,
                ap.programme_code,
                ap.batch_id,
                s.first_name,
                s.last_name,
                s.nic
            FROM allocate_programme ap
            INNER JOIN students s ON s.student_code = ap.student_code
            WHERE ap.status = 'active'
            ORDER BY s.first_name
            LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
    } else {
        $search = "%{$query}%";
        $sql = "
            SELECT
                ap.student_code,
                ap.student_registration_id,
                ap.programme_code,
                ap.batch_id,
                s.first_name,
                s.last_name,
                s.nic
            FROM allocate_programme ap
            INNER JOIN students s ON s.student_code = ap.student_code
            WHERE ap.status = 'active'
            AND (
                s.first_name LIKE ?
                OR s.last_name LIKE ?
                OR ap.student_code LIKE ?
                OR ap.student_registration_id LIKE ?
                OR s.nic LIKE ?
            )
            ORDER BY s.first_name
            LIMIT 50
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $search, $search, $search, $search, $search);
    }

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $students[] = [
            'student_code' => $row['student_code'] ?? '',
            'student_registration_id' => $row['student_registration_id'] ?? $row['student_code'] ?? '',
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'nic' => $row['nic'] ?? '',
            'programme_code' => $row['programme_code'] ?? '',
            'batch_id' => $row['batch_id'] ?? ''
        ];
    }

    $stmt->close();
    jsonResponse($students);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()]);
}
