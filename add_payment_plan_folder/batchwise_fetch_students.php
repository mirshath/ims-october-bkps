<?php
session_start();
require_once __DIR__ . '/../database/connection.php';

header('Content-Type: application/json');

ob_start();

$response = [];

try {
    if (!isset($_POST['programme_id']) || !isset($_POST['batch_id'])) {
        throw new Exception('Missing parameters: programme_id or batch_id');
    }

    $programme_id = $_POST['programme_id'];
    $batch_id     = $_POST['batch_id'];
    $status       = isset($_POST['student_status']) && $_POST['student_status'] !== ''
        ? $_POST['student_status']
        : '';

    $query = "
        SELECT 
            students.student_code, 
            students.first_name, 
            students.last_name, 
            allocate_programme.student_registration_id,
            allocate_programme.status AS alloc_status
        FROM allocate_programme
        INNER JOIN students ON allocate_programme.student_code = students.student_code
        WHERE allocate_programme.programme_code = ? 
          AND allocate_programme.batch_id = ? 
    ";

    $bindTypes = 'ii';
    $bindParams = [$programme_id, $batch_id];

    if ($status !== '') {
        $query .= " AND allocate_programme.status = ? ";
        $bindTypes .= 's';
        $bindParams[] = $status;
    }

    $query .= " ORDER BY students.first_name, students.last_name ";

    /** @var mysqli $conn */
    global $conn;

    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'unknown'));
    }

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('SQL Prepare Failed: ' . $conn->error);
    }

    if (!empty($bindParams)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
    }

    $ok = $stmt->execute();
    if (!$ok) {
        throw new Exception('SQL Execute Failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $response = $students;

} catch (Throwable $e) {
    $response = [
        'error' => $e->getMessage(),
        'trace' => $e->__toString()
    ];
    http_response_code(500);
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
