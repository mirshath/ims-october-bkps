<?php
session_start();
include '../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'] ?? '';
    $programmeBatch = $_POST['programme_batch'] ?? '';
    $percentage = $_POST['percentage'] ?? '';

    if (!empty($studentId) && !empty($programmeBatch) && is_numeric($percentage)) {

        $conn->begin_transaction(); // Start transaction

        try {
            // Step 1: Get current coursefee from installment_payment_table
            $stmt = $conn->prepare("SELECT coursefee FROM installment_payment_table 
                                   WHERE student_id = ? AND programme_batch = ?");
            $stmt->bind_param("ss", $studentId, $programmeBatch);
            $stmt->execute();
            $result = $stmt->get_result();

            $currentCourseFee = 0;

            if ($rowData = $result->fetch_assoc()) {
                $currentCourseFee = floatval($rowData['coursefee'] ?? 0);
            }
            $stmt->close();

            // Step 2: Get courseFeeLKR_total from add_payment_plan_table
            $stmt = $conn->prepare("SELECT courseFeeLKR_total FROM add_payment_plan_table 
                                   WHERE student_id = ? AND programme_batch = ?");
            $stmt->bind_param("ss", $studentId, $programmeBatch);
            $stmt->execute();
            $result = $stmt->get_result();

            $courseFeeLKRTotal = 0;

            if ($rowData = $result->fetch_assoc()) {
                $courseFeeLKRTotal = floatval($rowData['courseFeeLKR_total'] ?? 0);
            }
            $stmt->close();

            // Step 3: Calculate discount amount based on percentage from courseFeeLKR_total
            $discountAmount = ($courseFeeLKRTotal * $percentage) / 100;

            // Step 4: Calculate new coursefee (current coursefee - discount)
            $newCourseFee = $currentCourseFee - $discountAmount;

            // Ensure coursefee doesn't go negative
            if ($newCourseFee < 0) {
                $newCourseFee = 0;
            }

            // Step 5: Update installment_payment_table with percentage, dis_yes_no flag, and new coursefee
            $stmt = $conn->prepare("UPDATE installment_payment_table 
                                   SET discounted_percentage = ?, dis_yes_no = 1, coursefee = ? 
                                   WHERE student_id = ? AND programme_batch = ?");
            $stmt->bind_param("ddss", $percentage, $newCourseFee, $studentId, $programmeBatch);

            if ($stmt->execute()) {
                $conn->commit(); // Commit transaction
                echo json_encode([
                    'success' => true,
                    'message' => 'Percentage discount and coursefee updated successfully',
                    'percentage_applied' => $percentage,
                    'discount_amount' => $discountAmount,
                    'courseFeeLKR_total' => $courseFeeLKRTotal,
                    'current_coursefee_before' => $currentCourseFee,
                    'new_coursefee' => $newCourseFee
                ]);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Database update failed']);
            }

            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback(); // Rollback on error
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
