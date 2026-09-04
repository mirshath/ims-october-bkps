<?php
session_start();
include '../database/connection.php'; // Include DB connection

$data = json_decode(file_get_contents("php://input"), true);

$studentId = $data['student_id'];
$programId = $data['program_id'];
$batchId = $data['batch_id'];
$discountType = $data['discount_type'];
$discountValue = $data['discount_value'];
$updatedBy = $data['session_username']; // Get the session username
$remark = $data['remark']; // Get the remark

if ($discountType == 'Value') { // Check if discount type is 'Value'
    $query = "INSERT INTO payment_plan_history (student_id, program_id, batch_id, discount_type, discount_value, updated_by, re_marks) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siissss", $studentId, $programId, $batchId, $discountType, $discountValue, $updatedBy, $remark); // Bind the new parameters

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else { // If discount type is not 'Value', do nothing
    echo json_encode(["success" => true, "message" => "Discount type is not 'Value', no need to insert."]);
}
