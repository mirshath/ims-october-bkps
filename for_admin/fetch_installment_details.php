<?php
include("../database/connection.php");
header('Content-Type: application/json');

if (!isset($_GET['student_code']) || empty($_GET['student_code'])) {
    echo json_encode(['status' => false, 'message' => 'No student code provided']);
    exit;
}

$student_code = $_GET['student_code'];

// Fetch all installment details for this student
$sql = "
    SELECT 
        idt.*,
        ipt.coursefee AS payment_plan_course_fee,
        ipt.fee_type AS payment_plan_fee_type,
        ipt.registrationfee AS payment_plan_registration_fee
    FROM installment_details_table idt
    LEFT JOIN installment_payment_table ipt ON idt.installment_payment_table_id = ipt.id
    WHERE idt.student_id = ?
    ORDER BY idt.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code); // student_id is varchar in installment_details_table
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
