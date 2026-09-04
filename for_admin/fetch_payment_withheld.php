<?php
include("../database/connection.php");
header('Content-Type: application/json');

if (!isset($_GET['student_code']) || empty($_GET['student_code'])) {
    echo json_encode(['status' => false, 'message' => 'No student code provided']);
    exit;
}

$student_code = $_GET['student_code'];

// Fetch withheld payments including program and batch info
$sql = "
    SELECT 
        pwt.*,
        pt.program_name,
        bt.batch_name
    FROM payment_withheld_table pwt
    LEFT JOIN program_table pt ON pwt.program_id = pt.program_code
    LEFT JOIN batch_table bt ON pwt.batch_id = bt.id
    WHERE pwt.student_code = ?
    ORDER BY pwt.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code); // student_code is varchar
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
