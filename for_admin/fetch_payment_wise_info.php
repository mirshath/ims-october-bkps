<?php
include("../database/connection.php");

if (!isset($_GET['student_code'])) {
    echo json_encode(["status" => false, "message" => "student_code missing"]);
    exit();
}

$student_code = $_GET['student_code'];

$sql = "
    SELECT
        id,
        student_id,
        program_batch,
        installmentNumber,
        paymentAmount,
        paid_date,
        payment_type,
        bank_name,
        card_bank_deposit_dt,
        entered_date,
        entered_by,
        rcpt_number,
        status,
        cancellation_reason,
        cancelled_by,
        cancelled_at
    FROM payment_wise_info
    WHERE student_id = ?
    ORDER BY id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {

    // Remove NULL or empty values:
    $clean = [];
    foreach ($row as $key => $value) {
        if ($value !== null && $value !== "" && $value != 0) {
            $clean[$key] = $value;
        }
    }

    $data[] = $clean;
}

if (empty($data)) {
    echo json_encode(["status" => false, "message" => "No payment-wise info found"]);
} else {
    echo json_encode($data);
}
