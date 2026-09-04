<?php
session_start();
$updated_by = $_SESSION['username'];
include '../database/connection.php'; // Ensure you include your DB connection

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data)) {
    $conn->begin_transaction(); // Start transaction

    try {
        // Variables to track student_id, programme_batch and total discount
        $studentId = null;
        $programmeBatch = null;
        $totalDiscountValue = 0;

        // ----------------------------- Update installment_details_table
        foreach ($data as $row) {
            // Capture student_id and programme_batch from first row
            if ($studentId === null) {
                // Get student_id and programme_batch from installment_details_table
                $stmt = $conn->prepare("SELECT student_id, programme_batch FROM installment_details_table WHERE id = ?");
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($rowData = $result->fetch_assoc()) {
                    $studentId = $rowData['student_id'];
                    $programmeBatch = $rowData['programme_batch'];
                }
                $stmt->close();
            }

            // Get the existing updated_by value
            $stmt = $conn->prepare("SELECT updated_by FROM installment_details_table WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingUpdatedBy = $result->fetch_assoc()['updated_by'];

            // Append new updater's name
            $newUpdatedBy = empty($existingUpdatedBy) ? $updated_by : $existingUpdatedBy . ',' . $updated_by;

            // Handle discount_value: if Percentage type, set to NULL
            $discountType = $row['discount_type'];
            $discountValue = $discountType === "Percentage" ? null : $row['discount_value'];

            // Accumulate discount values (only for Value type, not Percentage)
            if ($discountType === "Value" && !empty($discountValue)) {
                $totalDiscountValue += floatval($discountValue);
            }

            // Update the installment_details_table with the data
            $stmt = $conn->prepare("UPDATE installment_details_table 
                SET installment_amount = ?, discount_type = ?, discount_value = ?, due_date = ?, remark = ?, updated_by = ?
                WHERE id = ?");
            $stmt->bind_param(
                "dsssssi",
                $row['installment_amount'],
                $discountType,
                $discountValue,
                $row['due_date'],
                $row['remark'],
                $newUpdatedBy,
                $row['id']
            );
            $stmt->execute();
            $stmt->close();
        }

        // ----------------------------- Update installment_payment_table
        if ($studentId !== null && $programmeBatch !== null && $totalDiscountValue > 0) {
            // Get current coursefee from installment_payment_table
            $stmt = $conn->prepare("SELECT coursefee FROM installment_payment_table 
                                   WHERE student_id = ? AND programme_batch = ?");
            $stmt->bind_param("ss", $studentId, $programmeBatch);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentCourseFee = 0;
            if ($rowData = $result->fetch_assoc()) {
                $currentCourseFee = $rowData['coursefee'] ?? 0;
            }
            $stmt->close();

            // Calculate new coursefee (current - discount)
            $newCourseFee = $currentCourseFee - $totalDiscountValue;

            // Ensure coursefee doesn't go negative
            if ($newCourseFee < 0) {
                $newCourseFee = 0;
            }

            // Update the coursefee in installment_payment_table
            $stmt = $conn->prepare("UPDATE installment_payment_table 
                                   SET coursefee = ? 
                                   WHERE student_id = ? AND programme_batch = ?");
            $stmt->bind_param("dss", $newCourseFee, $studentId, $programmeBatch);
            $stmt->execute();
            $stmt->close();
        }

        // ----------------------------- 

        $conn->commit(); // Commit changes

        // Trigger due table recalculation server-side to reflect discounted installment amounts
        try {
            // Attempt to resolve program_code from programme_batch (format: "Program Name - Batch Name")
            $programCode = '';
            if (!empty($programmeBatch)) {
                $parts = explode(' - ', $programmeBatch);
                $programName = trim($parts[0] ?? '');
                if ($programName !== '') {
                    $stmt = $conn->prepare("SELECT program_code FROM program_table WHERE program_name = ?");
                    $stmt->bind_param("s", $programName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res && isset($res['program_code'])) {
                        $programCode = $res['program_code'];
                    }
                    $stmt->close();
                }
            }

            // Post to update_payment_due_tables
            $postData = http_build_query([
                'student_id' => $studentId,
                'programme_batch' => $programmeBatch,
                'program_Code' => $programCode
            ]);
            $ch = curl_init('http://localhost/ims/payment_folder/update_payment_due_tables.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            // Swallow errors; front-end also triggers a refresh
        }

        echo json_encode([
            "success" => true,
            "message" => "Installment details and course fee updated successfully",
            "total_discount_applied" => $totalDiscountValue,
            "new_coursefee" => $newCourseFee ?? $currentCourseFee ?? 0
        ]);
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "No data received."]);
}
