<?php
include("../database/connection.php");

if (!isset($_GET['student_id'])) {
    echo json_encode(["status" => false, "message" => "No student ID provided"]);
    exit();
}

$student_id = $_GET['student_id'];

// Fetch installment payment details
$sql = "
    SELECT ipt.*,
           a.programme_batch,
           s.first_name,
           s.last_name
    FROM installment_payment_table ipt
    LEFT JOIN add_payment_plan_table a 
        ON ipt.id = a.id
    LEFT JOIN students s 
        ON ipt.student_id = s.student_code
    WHERE ipt.student_id = ?
    ORDER BY ipt.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Optionally format human-readable info
        $row['student_name'] = trim($row['first_name'] . " " . $row['last_name']);
        $rows[] = $row;
    }
    echo json_encode($rows);
} else {
    echo json_encode(["status" => false, "message" => "No installment payment found"]);
}
