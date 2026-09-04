<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

include("database/connection.php");

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
$types = '';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where = "WHERE (
        s.first_name LIKE ?
        OR s.last_name LIKE ?
        OR s.nic LIKE ?
        OR s.student_code LIKE ?
        OR EXISTS (
            SELECT 1 FROM allocate_programme ap2
            WHERE ap2.student_code = s.student_code
            AND ap2.student_registration_id LIKE ?
        )
    )";
    $params = [$like, $like, $like, $like, $like];
    $types = 'sssss';
}

$sql = "SELECT s.student_code, s.first_name, s.last_name, s.nic,
        IFNULL(
            (SELECT MIN(ap.student_registration_id)
             FROM allocate_programme ap
             WHERE ap.student_code = s.student_code),
            'N/A'
        ) AS student_registration_id
        FROM students s
        $where
        ORDER BY s.first_name ASC, s.last_name ASC
        LIMIT ? OFFSET ?";

$params[] = $limit + 1;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$hasMore = count($rows) > $limit;
if ($hasMore) {
    array_pop($rows);
}

$results = [];
foreach ($rows as $row) {
    $results[] = [
        'id' => $row['student_code'],
        'text' => '[' . $row['student_registration_id'] . '] ' . $row['nic'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'],
    ];
}

echo json_encode([
    'results' => $results,
    'pagination' => ['more' => $hasMore],
]);

$stmt->close();
$conn->close();
