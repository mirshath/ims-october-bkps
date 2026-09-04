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
        currency_type,
        paid_amount,
        exchange_rate,
        LKR_money,
        rcpt_number,
        paid_date,
        payment_type,
        bank_name,
        card_bank_deposit_dt,
        entered_by,
        entered_date
    FROM payment_uni_fee
    WHERE student_id = ?
    ORDER BY id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {

    // Remove NULL, empty string and zero values
    $clean = [];
    foreach ($row as $key => $value) {
        if ($value !== null && $value !== "" && $value != 0) {
            $clean[$key] = $value;
        }
    }

    $data[] = $clean;
}

if (empty($data)) {
    echo json_encode(["status" => false, "message" => "No university fee payments found"]);
} else {
    echo json_encode($data);
}
