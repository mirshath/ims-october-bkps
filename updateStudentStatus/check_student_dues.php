<?php

include("../database/connection.php");

$student_code = $_POST['student_code'] ?? '';
$program_code = $_POST['program_code'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';

$response = ['has_dues' => false];

if ($student_code && $program_code && $batch_id) {
    $stmt = $conn->prepare(
        "SELECT due_count_bms_fees, due_count_uni_fees
         FROM payment_withheld_table
         WHERE student_code = ? AND program_id = ? AND batch_id = ?"
    );
    $stmt->bind_param("sii", $student_code, $program_code, $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['due_count_bms_fees'] > 0 || $row['due_count_uni_fees'] > 0) {
            $response['has_dues'] = true;
        }
    }

    $stmt->close();
} else {
    $response['error'] = true;
    $response['message'] = 'Student code, program code, and batch id are required!';
}

echo json_encode($response);
