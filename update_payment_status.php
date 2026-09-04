<?php
session_start();
include("database/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $programId = $_POST['program_id'];
    $paymentStatus = $_POST['payment_status'];

    // Prepare the SQL statement to update the payment status
    $stmt = $conn->prepare("UPDATE payment_withheld_table SET payment_status = ? WHERE student_code = ? AND program_id = ?");
    $stmt->bind_param("ssi", $paymentStatus, $studentId, $programId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}
