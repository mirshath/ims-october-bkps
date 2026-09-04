<?php
session_start();
include("../database/connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentId = $_POST['student_id'] ?? '';
    $programmeBatch = $_POST['programme_batch'] ?? '';
    global $programme_code;
    $programme_code = $_POST['program_Code'] ?? '';
    $totalPaymentMade = floatval($_POST['totalPayment'] ?? 0); // ✅ NEW: Get actual payment amount

    if (empty($studentId) || empty($programmeBatch)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }

    $result = updatePaymentDueTablesOnSuccess($studentId, $programmeBatch, $programme_code, $totalPaymentMade, $conn);
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

function updatePaymentDueTablesOnSuccess($studentId, $programmeBatch, $programme_code, $totalPaymentMade, $conn)
{
    try {
        // Start transaction for data consistency
        $conn->begin_transaction();

        // Get student information
        $studentQuery = "
            SELECT 
                s.student_code,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                ap.student_registration_id,
                ap.programme_code,
                ap.batch_id
            FROM students s
            JOIN allocate_programme ap ON s.student_code = ap.student_code
            WHERE s.student_code = ? AND ap.status = 'active'";

        // Add programme_code filter if provided
        if (!empty($programme_code)) {
            $studentQuery .= " AND ap.programme_code = ?";
            $stmt = $conn->prepare($studentQuery);
            $stmt->bind_param("is", $studentId, $programme_code);
        } else {
            $stmt = $conn->prepare($studentQuery);
            $stmt->bind_param("i", $studentId);
        }

        $stmt->execute();
        $studentInfo = $stmt->get_result()->fetch_assoc();

        if (!$studentInfo) {
            throw new Exception("Student not found");
        }

        // Fetch program details from program_table
        $programQuery = "
            SELECT program_name, payment_method FROM program_table 
            WHERE program_code = ?";
        $stmt = $conn->prepare($programQuery);
        $stmt->bind_param("s", $programme_code);
        $stmt->execute();
        $programDetails = $stmt->get_result()->fetch_assoc();

        if ($programDetails) {
            $programName = $programDetails['program_name'];
            $paymentMethod = $programDetails['payment_method'];
            error_log("Program Name: " . $programName);
            error_log("Payment Method: " . $paymentMethod);
        } else {
            error_log("No program found for code: " . $programme_code);
            $paymentMethod = 1; // Default payment method
        }

        // Get payment plan details
        $paymentPlanQuery = "
            SELECT 
                appt.university_fee_LKR,
                appt.university_fee_GBP,
                appt.university_fee_USD,
                appt.registration_fee_LKR,
                appt.registration_fee_GBP,
                appt.registration_fee_USD,
                appt.lkr_reg_date,
                appt.lkr_reg_due_date
            FROM add_payment_plan_table appt
            WHERE appt.student_id = ? AND appt.programme_batch = ?
        ";

        $stmt = $conn->prepare($paymentPlanQuery);
        $stmt->bind_param("is", $studentId, $programmeBatch);
        $stmt->execute();
        $paymentPlan = $stmt->get_result()->fetch_assoc();

        if (!$paymentPlan) {
            throw new Exception("Payment plan not found for student");
        }

        $paymentDetails = [];

        // Calculate University Fee Outstanding (LKR) - EXCLUDE for payment method 3
        if ($paymentMethod != '3' && !empty($paymentPlan['university_fee_LKR']) && $paymentPlan['university_fee_LKR'] > 0) {
            $uniFeeLKR = floatval($paymentPlan['university_fee_LKR']);

            $uniPaymentsLKRQuery = "
                SELECT COALESCE(SUM(LKR_money), 0) as total_paid_lkr
                FROM payment_uni_fee
                WHERE student_id = ? AND program_batch = ? 
                AND (currency_type = 'LKR' OR currency_type IS NULL)
            ";

            $stmt = $conn->prepare($uniPaymentsLKRQuery);
            $stmt->bind_param("is", $studentId, $programmeBatch);
            $stmt->execute();
            $paidLKR = floatval($stmt->get_result()->fetch_assoc()['total_paid_lkr']);

            $outstandingLKR = $uniFeeLKR - $paidLKR;

            if ($uniFeeLKR > 0) {
                $paymentDetails[] = [
                    'payment_type' => 'University Fee (LKR)',
                    'installment_amount' => $uniFeeLKR,
                    'total_paid_amount' => $paidLKR,
                    'outstanding_amount' => max(0, $outstandingLKR),
                    'due_date' => date('Y-m-d'),
                    'currency' => 'LKR'
                ];
            }
        }

        // Calculate University Fee Outstanding (GBP) - EXCLUDE for payment method 3
        if ($paymentMethod != '3' && !empty($paymentPlan['university_fee_GBP']) && $paymentPlan['university_fee_GBP'] > 0) {
            $uniFeeGBP = floatval($paymentPlan['university_fee_GBP']);

            $uniPaymentsGBPQuery = "
                SELECT COALESCE(SUM(LKR_money), 0) as total_paid_gbp
                FROM payment_uni_fee
                WHERE student_id = ? AND program_batch = ? AND currency_type = 'GBP'
            ";

            $stmt = $conn->prepare($uniPaymentsGBPQuery);
            $stmt->bind_param("is", $studentId, $programmeBatch);
            $stmt->execute();
            $paidGBP = floatval($stmt->get_result()->fetch_assoc()['total_paid_gbp']);

            $outstandingGBP = $uniFeeGBP - $paidGBP;

            if ($uniFeeGBP > 0) {
                $paymentDetails[] = [
                    'payment_type' => 'University Fee (GBP)',
                    'installment_amount' => $uniFeeGBP,
                    'total_paid_amount' => $paidGBP,
                    'outstanding_amount' => max(0, $outstandingGBP),
                    'due_date' => date('Y-m-d'),
                    'currency' => 'GBP'
                ];
            }
        }

        // Calculate University Fee Outstanding (USD) - EXCLUDE for payment method 3
        if ($paymentMethod != '3' && !empty($paymentPlan['university_fee_USD']) && $paymentPlan['university_fee_USD'] > 0) {
            $uniFeeUSD = floatval($paymentPlan['university_fee_USD']);

            $uniPaymentsUSDQuery = "
                SELECT COALESCE(SUM(LKR_money), 0) as total_paid_usd
                FROM payment_uni_fee
                WHERE student_id = ? AND program_batch = ? AND currency_type = 'USD'
            ";

            $stmt = $conn->prepare($uniPaymentsUSDQuery);
            $stmt->bind_param("is", $studentId, $programmeBatch);
            $stmt->execute();
            $paidUSD = floatval($stmt->get_result()->fetch_assoc()['total_paid_usd']);

            $outstandingUSD = $uniFeeUSD - $paidUSD;

            if ($uniFeeUSD > 0) {
                $paymentDetails[] = [
                    'payment_type' => 'University Fee (USD)',
                    'installment_amount' => $uniFeeUSD,
                    'total_paid_amount' => $paidUSD,
                    'outstanding_amount' => max(0, $outstandingUSD),
                    'due_date' => date('Y-m-d'),
                    'currency' => 'USD'
                ];
            }
        }

        // Calculate Registration Fee Outstanding - ALWAYS INCLUDED
        $currentRegFee = 0;
        $regFeeStmt = $conn->prepare("SELECT registrationfee FROM installment_payment_table WHERE student_id = ? AND programme_batch = ?");
        $regFeeStmt->bind_param("is", $studentId, $programmeBatch);
        $regFeeStmt->execute();
        $regRow = $regFeeStmt->get_result()->fetch_assoc();
        if ($regRow && isset($regRow['registrationfee'])) {
            $currentRegFee = floatval($regRow['registrationfee']);
        }
        $regFeeStmt->close();

        $regPaymentsQuery = "
            SELECT COALESCE(SUM(paymentAmount), 0) as total_paid
            FROM payment_wise_info
            WHERE student_id = ? AND program_batch = ?
            AND (installmentNumber = 'Initial Payment' 
                 OR installmentNumber LIKE '%initial%' 
                 OR installmentNumber LIKE '%registration%'
                 OR installmentNumber LIKE '%Registration%')
            AND status = 'paid'
        ";

        $stmt = $conn->prepare($regPaymentsQuery);
        $stmt->bind_param("is", $studentId, $programmeBatch);
        $stmt->execute();
        $regPaid = floatval($stmt->get_result()->fetch_assoc()['total_paid']);

        $outstandingRegFee = $currentRegFee - $regPaid;

        if ($currentRegFee > 0) {
            $paymentDetails[] = [
                'payment_type' => 'Registration Fee',
                'installment_amount' => $currentRegFee,
                'total_paid_amount' => $regPaid,
                'outstanding_amount' => max(0, $outstandingRegFee),
                'due_date' => !empty($paymentPlan['lkr_reg_due_date']) ? $paymentPlan['lkr_reg_due_date'] : date('Y-m-d'),
                'currency' => 'LKR'
            ];
        }

        // Calculate Installment Outstanding - ALWAYS INCLUDED
        $installmentsQuery = "
            SELECT 
                idt.id,
                idt.installment_numbers,
                idt.installment_amount,
                idt.devided_values,
                idt.due_date,
                idt.discount_type,
                idt.discount_value,
                ipt.discounted_percentage,
                ipt.dis_yes_no
            FROM installment_payment_table ipt
            JOIN installment_details_table idt ON ipt.id = idt.installment_payment_table_id
            JOIN add_payment_plan_table appt ON ipt.payment_plans_tb_id = appt.id
            WHERE appt.student_id = ? AND appt.programme_batch = ?
            ORDER BY idt.installment_numbers
        ";

        $stmt = $conn->prepare($installmentsQuery);
        $stmt->bind_param("is", $studentId, $programmeBatch);
        $stmt->execute();
        $installmentsResult = $stmt->get_result();

        while ($installment = $installmentsResult->fetch_assoc()) {
            $installmentNumber = $installment['installment_numbers'];

            // Choose base amount
            $devided = !empty($installment['devided_values']) ? floatval($installment['devided_values']) : 0;
            $savedInstallmentAmount = isset($installment['installment_amount']) && $installment['installment_amount'] !== null
                ? floatval($installment['installment_amount']) : null;

            $useSavedAmount = $savedInstallmentAmount !== null;
            $originalAmount = $useSavedAmount ? $savedInstallmentAmount : $devided;

            // Only apply discounts when using base devided values
            if (!$useSavedAmount) {
                $planDiscountPct = isset($installment['discounted_percentage']) ? floatval($installment['discounted_percentage']) : 0;
                $planDiscountFlag = isset($installment['dis_yes_no']) ? intval($installment['dis_yes_no']) : 0;
                if ($planDiscountFlag === 1 && $planDiscountPct > 0) {
                    $originalAmount -= ($originalAmount * $planDiscountPct) / 100;
                }

                if (!empty($installment['discount_type']) && !empty($installment['discount_value'])) {
                    $discountValue = floatval($installment['discount_value']);
                    if ($installment['discount_type'] == 'Value') {
                        $originalAmount -= $discountValue;
                    } elseif ($installment['discount_type'] == 'Percentage') {
                        $originalAmount -= ($originalAmount * $discountValue) / 100;
                    }
                }

                if ($originalAmount < 0) {
                    $originalAmount = 0;
                }
            }

            $dueDate = !empty($installment['due_date']) ? $installment['due_date'] : date('Y-m-d');

            // Get installment payments
            $installmentPaymentsQuery = "
                SELECT COALESCE(SUM(paymentAmount), 0) as total_paid
                FROM payment_wise_info
                WHERE student_id = ? AND program_batch = ? AND installmentNumber = ?
                AND installmentNumber != 'Initial Payment'
                AND installmentNumber NOT LIKE '%initial%'
                AND installmentNumber NOT LIKE '%registration%'
                AND installmentNumber NOT LIKE '%Registration%'
                AND status = 'paid'
            ";

            $stmt = $conn->prepare($installmentPaymentsQuery);
            $stmt->bind_param("iss", $studentId, $programmeBatch, $installmentNumber);
            $stmt->execute();
            $installmentPaid = floatval($stmt->get_result()->fetch_assoc()['total_paid']);

            $outstandingInstallment = $originalAmount - $installmentPaid;

            if ($originalAmount > 0) {
                $paymentDetails[] = [
                    'payment_type' => 'Installment',
                    'installment_numbers' => $installmentNumber,
                    'installment_amount' => $originalAmount,
                    'total_paid_amount' => $installmentPaid,
                    'outstanding_amount' => max(0, $outstandingInstallment),
                    'due_date' => $dueDate,
                    'currency' => 'LKR'
                ];
            }
        }

        // Calculate totals
        $studentTotals = calculateStudentTotals($paymentDetails);

        // --- BEGIN UPDATED BLOCK: Discounted balance calculation for updates ---
        // If record already exists, use fresh discounted outstanding (before) minus payment made (instead of only reducing)
        $getExistingDueRowQuery = "
            SELECT id, remaining_full_amount 
            FROM payment_due_tables 
            WHERE student_code = ? AND programme_batch = ?
        ";
        $stmt = $conn->prepare($getExistingDueRowQuery);
        $stmt->bind_param("ss", $studentInfo['student_code'], $programmeBatch);
        $stmt->execute();
        $existingRow = $stmt->get_result()->fetch_assoc();

        if ($existingRow) {
            // Always update using the true current recalculated discounted outstanding!
            $previousRemaining = floatval($existingRow['remaining_full_amount']);

            // Use newly calculated discounted studentTotals['total_outstanding'] and just subtract the submitted payment
            $currentDiscountedOutstanding = floatval($studentTotals['total_outstanding']);

            // Prevent reducing below zero
            $newRemainingBalance = max(0, $currentDiscountedOutstanding - $totalPaymentMade);

            error_log("Existing Due Row - Prev: $previousRemaining | New Discounted Outstanding: $currentDiscountedOutstanding | Payment: $totalPaymentMade | Set Remaining: $newRemainingBalance");

            // ✅ Update with current discounted outstanding minus payment
            $updateDueQuery = "
                UPDATE payment_due_tables
                SET student_name = ?,
                    student_registration_id = ?,
                    due_count_bms_fees = ?,
                    due_count_uni_fees = ?,
                    remaining_full_amount = ?,
                    updated_at = NOW(),
                    payment_method = ?
                WHERE student_code = ? AND programme_batch = ?
            ";

            $stmt = $conn->prepare($updateDueQuery);
            $stmt->bind_param(
                "ssiidsss",
                $studentInfo['student_name'],
                $studentInfo['student_registration_id'],
                $studentTotals['reg_installment_past_due_count'],
                $studentTotals['uni_fee_past_due_count'],
                $newRemainingBalance,
                $paymentMethod,
                $studentInfo['student_code'],
                $programmeBatch
            );
            $stmt->execute();

            $currentBalance = $currentDiscountedOutstanding;  // for response
        } else {
            // First insert, just use calculated discounted outstanding as original, minus nothing (payment is accounted in paid val)
            $insertDueQuery = "
                INSERT INTO payment_due_tables (
                    student_code,
                    student_name,
                    student_registration_id,
                    programme_batch,
                    due_count_bms_fees,
                    due_count_uni_fees,
                    remaining_full_amount,
                    payment_method,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";

            $stmt = $conn->prepare($insertDueQuery);
            $stmt->bind_param(
                "ssssiids",
                $studentInfo['student_code'],
                $studentInfo['student_name'],
                $studentInfo['student_registration_id'],
                $programmeBatch,
                $studentTotals['reg_installment_past_due_count'],
                $studentTotals['uni_fee_past_due_count'],
                $studentTotals['total_outstanding'],
                $paymentMethod
            );
            $stmt->execute();

            // On creation, new balance is just outstanding (payment is already considered in totals)
            $currentBalance = $studentTotals['total_outstanding'];
            $newRemainingBalance = $studentTotals['total_outstanding'];
        }

        // --- END UPDATED BLOCK ---

        // Update payment_withheld_table
        updatePaymentWithheldTable(
            $conn,
            $studentInfo['student_code'],
            $studentInfo['student_registration_id'],
            $studentInfo['programme_code'],
            $studentInfo['batch_id'],
            $studentTotals,
            $paymentMethod
        );

        // Commit transaction
        $conn->commit();

        return [
            'success' => true,
            'message' => 'Payment due tables updated successfully',
            'totals' => $studentTotals,
            'previous_balance' => $currentBalance,
            'payment_made' => $totalPaymentMade,
            'new_balance' => $newRemainingBalance,
            'past_due_total' => $studentTotals['past_due_total'],
            'past_due_count' => $studentTotals['past_due_count'],
            'has_past_due' => ($studentTotals['past_due_total'] > 0 || $studentTotals['past_due_count'] > 0)
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Error updating payment tables: ' . $e->getMessage()
        ];
    }
}


function updatePaymentWithheldTable($conn, $studentCode, $studentRegistrationId, $programId, $batchId, $studentTotals, $paymentMethod)
{
    try {
        $totalPastDue = $studentTotals['past_due_total'];
        $dueBMSCount = $studentTotals['reg_installment_past_due_count'];
        $dueUniCount = $studentTotals['uni_fee_past_due_count'];

        $paymentStatus = 'active';

        if ($paymentMethod == '3') {
            $registrationOutstanding = isset($studentTotals['registration_outstanding']) ?
                $studentTotals['registration_outstanding'] : 0;
            $installmentOutstanding = $studentTotals['reg_installment_outstanding'];
            $regInstallmentTotal = $registrationOutstanding + $installmentOutstanding;

            if ($regInstallmentTotal >= 50000) {
                $paymentStatus = 'withheld';
            }
        } else {
            if ($dueBMSCount >= intval($paymentMethod) && $totalPastDue > 0) {
                $paymentStatus = 'withheld';
            }
        }

        $checkQuery = "
            SELECT id FROM payment_withheld_table 
            WHERE student_code = ? AND program_id = ? AND batch_id = ?
        ";

        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("sii", $studentCode, $programId, $batchId);
        $stmt->execute();
        $existingRecord = $stmt->get_result()->fetch_assoc();

        if ($existingRecord) {
            $updateQuery = "
                UPDATE payment_withheld_table 
                SET payment_status = ?, 
                    student_registration_id = ?,
                    due_count_bms_fees = ?, 
                    due_count_uni_fees = ?, 
                    last_payment_date = NOW(),
                    updated_at = NOW()
                WHERE student_code = ? AND program_id = ? AND batch_id = ?
            ";

            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param(
                "ssiisii",
                $paymentStatus,
                $studentRegistrationId,
                $dueBMSCount,
                $dueUniCount,
                $studentCode,
                $programId,
                $batchId
            );
            $stmt->execute();
        } else {
            if ($studentTotals['total_outstanding'] > 0 || $totalPastDue > 0) {
                $insertQuery = "
                    INSERT INTO payment_withheld_table 
                    (student_code, student_registration_id, program_id, batch_id, 
                     payment_status, due_count_bms_fees, due_count_uni_fees, 
                     last_payment_date, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
                ";

                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param(
                    "ssiisii",
                    $studentCode,
                    $studentRegistrationId,
                    $programId,
                    $batchId,
                    $paymentStatus,
                    $dueBMSCount,
                    $dueUniCount
                );
                $stmt->execute();
            }
        }

        return true;
    } catch (Exception $e) {
        throw new Exception("Error updating payment withheld table: " . $e->getMessage());
    }
}

function calculateStudentTotals($studentPaymentDetails)
{
    $totals = [
        'total_original' => 0,
        'total_paid' => 0,
        'total_outstanding' => 0,
        'past_due_total' => 0,
        'past_due_count' => 0,
        'uni_fee_original' => 0,
        'uni_fee_paid' => 0,
        'uni_fee_outstanding' => 0,
        'uni_fee_past_due_total' => 0,
        'uni_fee_past_due_count' => 0,
        'reg_installment_original' => 0,
        'reg_installment_paid' => 0,
        'reg_installment_outstanding' => 0,
        'reg_installment_past_due_total' => 0,
        'reg_installment_past_due_count' => 0,
        'registration_outstanding' => 0
    ];

    $currentDate = new DateTime();

    foreach ($studentPaymentDetails as $detail) {
        $isUniFee = strpos($detail['payment_type'], 'University Fee') !== false;
        $isRegistrationFee = strpos($detail['payment_type'], 'Registration Fee') !== false;
        $originalAmount = floatval($detail['installment_amount']);
        $paidAmount = floatval($detail['total_paid_amount']);
        $outstandingAmount = floatval($detail['outstanding_amount']);

        $isPastDue = false;
        if (!empty($detail['due_date']) && $outstandingAmount > 0) {
            try {
                $dueDate = new DateTime($detail['due_date']);
                $isPastDue = $dueDate < $currentDate;
            } catch (Exception $e) {
                $isPastDue = false;
            }
        }

        if ($isUniFee) {
            $totals['uni_fee_original'] += $originalAmount;
            $totals['uni_fee_paid'] += $paidAmount;

            if ($outstandingAmount > 0) {
                $totals['uni_fee_outstanding'] += $outstandingAmount;
            }

            if ($isPastDue && $outstandingAmount > 0) {
                $totals['uni_fee_past_due_total'] += $outstandingAmount;
                $totals['uni_fee_past_due_count']++;
            }
        } else {
            $totals['reg_installment_original'] += $originalAmount;
            $totals['reg_installment_paid'] += $paidAmount;

            if ($outstandingAmount > 0) {
                $totals['reg_installment_outstanding'] += $outstandingAmount;

                if ($isRegistrationFee) {
                    $totals['registration_outstanding'] += $outstandingAmount;
                }
            }

            if ($isPastDue && $outstandingAmount > 0) {
                $totals['reg_installment_past_due_total'] += $outstandingAmount;
                $totals['reg_installment_past_due_count']++;
            }
        }

        $totals['total_original'] += $originalAmount;
        $totals['total_paid'] += $paidAmount;

        if ($outstandingAmount > 0) {
            $totals['total_outstanding'] += $outstandingAmount;
        }

        if ($isPastDue && $outstandingAmount > 0) {
            $totals['past_due_total'] += $outstandingAmount;
            $totals['past_due_count']++;
        }
    }

    foreach ($totals as $key => $value) {
        if (strpos($key, 'count') === false) {
            $totals[$key] = round($value, 2);
        }
    }

    return $totals;
}
