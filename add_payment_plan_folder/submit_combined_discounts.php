<?php
session_start();
include '../database/connection.php';

$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload || !is_array($payload)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$studentId = $payload['student_id'] ?? '';
$programmeBatch = $payload['programme_batch'] ?? '';
$programId = $payload['program_id'] ?? '';
$batchId = $payload['batch_id'] ?? '';
$registration = $payload['registration'] ?? [];
$installments = $payload['installments'] ?? [];
$programDiscountPercentage = isset($payload['program_discount_percentage']) && is_numeric($payload['program_discount_percentage']) ? floatval($payload['program_discount_percentage']) : 0.0;

if (empty($studentId) || empty($programmeBatch) || empty($programId) || empty($batchId)) {
    echo json_encode(['success' => false, 'message' => 'Missing required identifiers']);
    exit;
}

$conn->begin_transaction();

try {
    $totalDiscountValue = 0.0;

    // Process each installment
    foreach ($installments as $row) {
        $id = intval($row['id'] ?? 0);
        if ($id <= 0) continue;

        // Get existing updated_by
        $stmt = $conn->prepare("SELECT updated_by FROM installment_details_table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $existingUpdatedBy = $res && ($tmp = $res->fetch_assoc()) ? ($tmp['updated_by'] ?? '') : '';
        $stmt->close();

        $newUpdatedBy = empty($existingUpdatedBy) ? $username : $existingUpdatedBy . ',' . $username;

        $discountType = $row['discount_type'] ?? 'N/A';
        $rawDiscountValue = $row['discount_value'] ?? null;
        $discountValue = ($discountType === 'Value' && is_numeric($rawDiscountValue)) ? floatval($rawDiscountValue) : null;

        if ($discountValue > 0) $totalDiscountValue += $discountValue;

        $installmentAmount = isset($row['installment_amount']) ? floatval($row['installment_amount']) : 0.0;
        $dueDate = $row['due_date'] ?? null;
        $remark = $row['remark'] ?? '';

        // Update installment details
        $stmt = $conn->prepare("UPDATE installment_details_table 
            SET installment_amount=?, discount_type=?, discount_value=?, due_date=?, remark=?, updated_by=?
            WHERE id=?");
        $stmt->bind_param("dsssssi", $installmentAmount, $discountType, $discountValue, $dueDate, $remark, $newUpdatedBy, $id);
        $stmt->execute();
        $stmt->close();

        // Insert into payment_plan_history
        if (!empty($discountValue) && $discountValue > 0) {
            $stmt = $conn->prepare("INSERT INTO payment_plan_history 
                (student_id, program_id, batch_id, discount_type, discount_value, created_at, updated_by, re_marks)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("iiisdss", $studentId, $programId, $batchId, $discountType, $discountValue, $username, $remark);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Handle course fee updates
    if ($totalDiscountValue > 0 || $programDiscountPercentage > 0) {
        $stmt = $conn->prepare("SELECT coursefee FROM installment_payment_table WHERE student_id=? AND programme_batch=?");
        $stmt->bind_param("ss", $studentId, $programmeBatch);
        $stmt->execute();
        $res = $stmt->get_result();
        $currentCourseFee = ($res && ($tmp = $res->fetch_assoc())) ? floatval($tmp['coursefee']) : 0;
        $stmt->close();

        $percentageDiscountAmount = 0.0;
        if ($programDiscountPercentage > 0) {
            $stmt = $conn->prepare("SELECT courseFeeLKR_total FROM add_payment_plan_table WHERE student_id=? AND programme_batch=?");
            $stmt->bind_param("ss", $studentId, $programmeBatch);
            $stmt->execute();
            $res = $stmt->get_result();
            $courseFeeLKRTotal = ($res && ($tmp = $res->fetch_assoc())) ? floatval($tmp['courseFeeLKR_total']) : 0;
            $stmt->close();

            $percentageDiscountAmount = ($courseFeeLKRTotal * $programDiscountPercentage)/100.0;

            $percentageRemark = "Program discount: {$programDiscountPercentage}% applied to course fee";
            $stmt = $conn->prepare("INSERT INTO payment_plan_history 
                (student_id, program_id, batch_id, discount_type, discount_value, created_at, updated_by, re_marks)
                VALUES (?, ?, ?, 'Percentage', ?, NOW(), ?, ?)");
            $stmt->bind_param("iiidss", $studentId, $programId, $batchId, $percentageDiscountAmount, $username, $percentageRemark);
            $stmt->execute();
            $stmt->close();
        }

        $newCourseFee = max(0, $currentCourseFee - $totalDiscountValue - $percentageDiscountAmount);

        if ($programDiscountPercentage > 0) {
            $stmt = $conn->prepare("UPDATE installment_payment_table 
                SET discounted_percentage=?, dis_yes_no=1, coursefee=? 
                WHERE student_id=? AND programme_batch=?");
            $stmt->bind_param("ddss", $programDiscountPercentage, $newCourseFee, $studentId, $programmeBatch);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE installment_payment_table SET coursefee=? WHERE student_id=? AND programme_batch=?");
            $stmt->bind_param("dss", $newCourseFee, $studentId, $programmeBatch);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Handle registration fee discount
    $regFeeDiscount = isset($registration['discount_value']) ? floatval($registration['discount_value']) : 0.0;
    $regFeeRemark = $registration['remark'] ?? '';
    $dtype = $registration['dtype'] ?? 'regfeeDiscount';

    if ($regFeeDiscount > 0) {
        $stmt = $conn->prepare("INSERT INTO payment_plan_regfee_discount 
            (student_id, program_id, batch_id, d_type, discount_value, remarks, created_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("iiisdss", $studentId, $programId, $batchId, $dtype, $regFeeDiscount, $regFeeRemark, $username);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE installment_payment_table SET registrationfee=GREATEST(registrationfee-?,0) WHERE student_id=? AND programme_batch=?");
        $stmt->bind_param("dss", $regFeeDiscount, $studentId, $programmeBatch);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    // ----------------------------------
    // Trigger discount-aware payment due update
    // Use output buffering to prevent included script from breaking JSON
    $_POST['student_id'] = $studentId;
    $_POST['programme_batch'] = $programmeBatch;
    $_POST['program_Code'] = $programId;

    ob_start();
    include "../payment_folder/update_payment_due_tables_with_discount.php";
    ob_end_clean(); // discard any output from included script
    // ----------------------------------

    echo json_encode(['success' => true, 'message' => 'All discounts applied successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
