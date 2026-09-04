<?php
session_start();
include '../database/connection.php';

header('Content-Type: application/json');

$programmeFilter = isset($_GET['programme']) ? trim($_GET['programme']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '';
$dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : '';

$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';

mysqli_set_charset($conn, "utf8mb4");

$whereConditions = [];

if ($role !== 'super_admin') {
    $whereConditions[] = "pau.user_id = " . intval($user_id);
}

if ($programmeFilter !== '') {
    $parts = explode(' - ', $programmeFilter, 2);
    if (count($parts) === 2) {
        $programmeName = $parts[0];
        $batchName = $parts[1];
        $whereConditions[] = "pt.program_name = '" . mysqli_real_escape_string($conn, $programmeName) . "' AND bt.batch_name = '" . mysqli_real_escape_string($conn, $batchName) . "'";
    } else {
        $whereConditions[] = "pt.program_name = '" . mysqli_real_escape_string($conn, $programmeFilter) . "'";
    }
}

if ($statusFilter !== '') {
    $whereConditions[] = "log.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

if ($dateFrom !== '') {
    $whereConditions[] = "DATE(log.sent_at) >= '" . mysqli_real_escape_string($conn, $dateFrom) . "'";
}

if ($dateTo !== '') {
    $whereConditions[] = "DATE(log.sent_at) <= '" . mysqli_real_escape_string($conn, $dateTo) . "'";
}

if ($role === 'super_admin') {
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    $sql = "SELECT 
                log.id,
                log.student_code,
                log.student_name,
                log.email_address,
                pt.program_name,
                bt.batch_name,
                log.status,
                log.error_message,
                log.sent_by,
                log.sent_at
            FROM induction_db_email_send_log_table log
            LEFT JOIN program_table pt ON log.program_id = pt.program_code
            LEFT JOIN batch_table bt ON log.batch_id = bt.id
            LEFT JOIN induction_active_table iat ON log.program_id = iat.program_id AND log.batch_id = iat.batch_id
            " . (!empty($whereClause) ? $whereClause . " AND (iat.status = 'active' OR iat.status IS NULL)" : "WHERE (iat.status = 'active' OR iat.status IS NULL)") . "
            ORDER BY log.sent_at DESC";
} else {
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    $sql = "SELECT 
                log.id,
                log.student_code,
                log.student_name,
                log.email_address,
                pt.program_name,
                bt.batch_name,
                log.status,
                log.error_message,
                log.sent_by,
                log.sent_at
            FROM induction_db_email_send_log_table log
            LEFT JOIN program_table pt ON log.program_id = pt.program_code
            LEFT JOIN batch_table bt ON log.batch_id = bt.id
            INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
            LEFT JOIN induction_active_table iat ON log.program_id = iat.program_id AND log.batch_id = iat.batch_id
            " . (!empty($whereClause) ? $whereClause . " AND (iat.status = 'active' OR iat.status IS NULL)" : "WHERE pau.user_id = " . intval($user_id) . " AND (iat.status = 'active' OR iat.status IS NULL)") . "
            ORDER BY log.sent_at DESC";
}

$result = $conn->query($sql);

$data = [];
$index = 1;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            $index,
            $row['student_code'],
            $row['student_name'],
            $row['email_address'],
            $row['program_name'],
            $row['batch_name'],
            $row['status'],
            $row['error_message'],
            $row['sent_by'],
            $row['sent_at']
        ];
        $index++;
    }
}

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
