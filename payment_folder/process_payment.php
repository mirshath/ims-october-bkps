<?php
session_start();
$Session_username = $_SESSION['username'] ?? '';
include '../database/connection.php';

// Initialize POST variables
$studentId = $_POST['studentId'] ?? null;
$studentName = $_POST['studentName'] ?? '';
$programmeBatch = $_POST['programmeBatch'] ?? '';
$totalPayment = $_POST['totalPayment'] ?? '';
$initialPayment = $_POST['initialPayment'] ?? null;
$installments = $_POST['installments'] ?? null;
$USDfeeAmount = $_POST['USDfeeAmount'] ?? null;
$USDexchangeRate = $_POST['USDexchangeRate'] ?? null;
$paymentLKRValue = $_POST['paymentLKRValue'] ?? null;
$CRC = $_POST['CRC'] ?? null;
$paidDate = $_POST['paidDate'] ?? null;
$paymentType = $_POST['paymentType'] ?? '';
$bankName = $_POST['bankName'] ?? null;
$paymentDate = $_POST['paymentDate'] ?? null;
$receiptNumber = $_POST['receiptNumber'] ?? null;
$programCode = $_POST['programCode'] ?? null;

// ✅ NEW: basic server-side sanity checks on amounts.
// If someone tampers with the form (or a bug sends a negative number),
// this stops it before it ever reaches the database.
function reject_if_negative($value, $label)
{
    if ($value !== null && $value !== '' && floatval($value) < 0) {
        echo json_encode(['success' => false, 'message' => "Invalid $label: cannot be negative."]);
        exit;
    }
}
reject_if_negative($initialPayment, 'initial payment amount');
reject_if_negative($USDfeeAmount, 'university fee amount');
if ($installments) {
    foreach ($installments as $inst) {
        reject_if_negative($inst['paymentAmount'] ?? 0, 'installment payment amount');
    }
}

// Only process if some required fields are provided
if ($studentId || $studentName || $programmeBatch || $totalPayment) {

    // ✅ NEW: Require a receipt number and use it as an idempotency key.
    // If this exact receipt number was already fully processed, we bail out
    // immediately instead of re-inserting the same payment a second time.
    // (Requires: ALTER TABLE payment_wise_info ADD UNIQUE KEY uniq_receipt (rcpt_number, installmentNumber);
    //  and similarly consider a unique key on payment_uni_fee.rcpt_number if that table can also duplicate.)
    if (empty($receiptNumber)) {
        echo json_encode(['success' => false, 'message' => 'Missing receipt number.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // ✅ NEW: Early duplicate check inside the transaction.
        // This is a belt-and-suspenders check in addition to the DB unique keys below -
        // it catches the duplicate before doing any work, not just at insert time.
        // 🔧 FIXED: originally only checked payment_wise_info - now also checks
        // payment_uni_fee, since a uni-fee-only payment never touches payment_wise_info
        // and would have sailed through the old check undetected.
        $dupCheckSql1 = "SELECT id FROM payment_wise_info WHERE rcpt_number = ? LIMIT 1";
        $dupStmt1 = $conn->prepare($dupCheckSql1);
        $dupStmt1->bind_param("s", $receiptNumber);
        $dupStmt1->execute();
        $dupFound = $dupStmt1->get_result()->fetch_assoc();
        $dupStmt1->close();

        $dupCheckSql2 = "SELECT id FROM payment_uni_fee WHERE rcpt_number = ? LIMIT 1";
        $dupStmt2 = $conn->prepare($dupCheckSql2);
        $dupStmt2->bind_param("s", $receiptNumber);
        $dupStmt2->execute();
        $dupFound = $dupFound ?: $dupStmt2->get_result()->fetch_assoc();
        $dupStmt2->close();

        if ($dupFound) {
            throw new Exception("Duplicate submission detected - this payment (receipt $receiptNumber) was already recorded.");
        }

        // Determine the correct date columns based on payment type
        $paid_date_value = null;
        $card_deposit_value = null;
        if ($paymentType === 'cash' || $paymentType === 'card') {
            $paid_date_value = $paidDate ?: null;
        } elseif ($paymentType === 'bank_deposit') {
            $card_deposit_value = $paymentDate ?: null;
        }

        // ------------------- Insert University Fee Payment -------------------
        if (!empty($USDfeeAmount)) {
            $currencyType = isset($CRC) ? array_keys($CRC)[0] : 'LKR';
            $paidAmount = $USDfeeAmount;
            $exchangeRate = $USDexchangeRate ?: 1.0;
            $LKRmoney = $paymentLKRValue ?: $USDfeeAmount;

            $sql = "INSERT INTO payment_uni_fee 
                    (student_id, program_batch, currency_type, paid_amount, exchange_rate, LKR_money, rcpt_number, paid_date, payment_type, bank_name, card_bank_deposit_dt, entered_by, entered_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issdisssssss",
                $studentId,
                $programmeBatch,
                $currencyType,
                $paidAmount,
                $exchangeRate,
                $LKRmoney,
                $receiptNumber,
                $paid_date_value,
                $paymentType,
                $bankName,
                $card_deposit_value,
                $Session_username
            );

            // 🔧 FIXED: this insert had no duplicate-key handling at all - added to
            // match the same pattern used for payment_wise_info below. Requires:
            // ALTER TABLE payment_uni_fee ADD UNIQUE KEY uniq_receipt (rcpt_number);
            if (!$stmt->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Duplicate payment blocked: this university fee payment (receipt $receiptNumber) was already submitted.");
                }
                throw new Exception("Error inserting into payment_uni_fee: " . $stmt->error);
            }

            // ✅ CHANGED: lock the row we're about to decrement (FOR UPDATE) so a
            // second concurrent request has to wait until this transaction commits
            // before it can read/write the same row. Without this, two requests can
            // both read the same starting balance and both subtract from it.
            if ($currencyType === 'USD') {
                $lockSql = "SELECT unifee_usd FROM installment_payment_table WHERE student_id = ? AND programme_batch = ? FOR UPDATE";
            } elseif ($currencyType === 'GBP') {
                $lockSql = "SELECT unifee_gbp FROM installment_payment_table WHERE student_id = ? AND programme_batch = ? FOR UPDATE";
            } else {
                $lockSql = "SELECT unifee_lkr FROM installment_payment_table WHERE student_id = ? AND programme_batch = ? FOR UPDATE";
            }
            $lockStmt = $conn->prepare($lockSql);
            $lockStmt->bind_param("is", $studentId, $programmeBatch);
            $lockStmt->execute();
            $lockStmt->close(); // we just needed the row lock; value itself isn't used here

            // Update installment table (GREATEST(0, ...) was already here - kept as-is, it's correct)
            if ($currencyType === 'USD') {
                $updateQuery = "UPDATE installment_payment_table SET unifee_usd = GREATEST(0, unifee_usd - ?) WHERE student_id = ? AND programme_batch = ?";
            } elseif ($currencyType === 'GBP') {
                $updateQuery = "UPDATE installment_payment_table SET unifee_gbp = GREATEST(0, unifee_gbp - ?) WHERE student_id = ? AND programme_batch = ?";
            } else {
                $updateQuery = "UPDATE installment_payment_table SET unifee_lkr = GREATEST(0, unifee_lkr - ?) WHERE student_id = ? AND programme_batch = ?";
            }
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("dis", $paidAmount, $studentId, $programmeBatch);
            if (!$updateStmt->execute()) {
                throw new Exception("Error updating installment_payment_table: " . $updateStmt->error);
            }
        }

        // ------------------- Process Installments -------------------
        $totalInstallments = 0;
        if ($installments) {
            foreach ($installments as $installment) {
                $installmentNumber = $installment['installmentNumber'] ?? '';
                $paymentAmount = $installment['paymentAmount'] ?? 0;
                $totalInstallments += $paymentAmount;

                // ✅ CHANGED: added "FOR UPDATE" to lock this specific installment row
                // until the transaction commits/rolls back. This is what actually
                // prevents the race condition - a second request for the same
                // installment now has to wait in line instead of reading stale data.
                $query = "SELECT installment_amount FROM installment_details_table 
                          WHERE student_id = ? AND programme_batch = ? AND installment_numbers = ?
                          FOR UPDATE";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $studentId, $programmeBatch, $installmentNumber);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    // ✅ CHANGED: floor at 0 in PHP as a second safety net
                    // (the SQL-side GREATEST(0,...) below is the primary guard).
                    $newAmount = max(0, $row['installment_amount'] - $paymentAmount);

                    // ✅ CHANGED: GREATEST(0, ...) added directly in the UPDATE so the
                    // column itself can never go negative even under a race, matching
                    // the pattern already used for unifee_usd/gbp/lkr above.
                    $updateQuery = "UPDATE installment_details_table 
                                    SET installment_amount = GREATEST(0, ?) 
                                    WHERE student_id = ? AND programme_batch = ? AND installment_numbers = ?";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("dsss", $newAmount, $studentId, $programmeBatch, $installmentNumber);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Error updating installment: " . $updateStmt->error);
                    }
                }

                // Insert into payment_wise_info
                $insertQuery = "INSERT INTO payment_wise_info 
                                (student_id, program_batch, installmentNumber, paymentAmount, paid_date, payment_type, bank_name, card_bank_deposit_dt, entered_by, rcpt_number) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param(
                    "issdssssss",
                    $studentId,
                    $programmeBatch,
                    $installmentNumber,
                    $paymentAmount,
                    $paid_date_value,
                    $paymentType,
                    $bankName,
                    $card_deposit_value,
                    $Session_username,
                    $receiptNumber
                );

                // ✅ NEW: if a UNIQUE KEY on (rcpt_number, installmentNumber) exists on
                // payment_wise_info, a genuine duplicate submission fails here with
                // MySQL error 1062. We turn that into a clean, friendly message
                // instead of a generic SQL error.
                if (!$insertStmt->execute()) {
                    if ($conn->errno == 1062) {
                        throw new Exception("Duplicate payment blocked: this installment payment (receipt $receiptNumber) was already submitted.");
                    }
                    throw new Exception("Error inserting into payment_wise_info: " . $insertStmt->error);
                }
            }
        }

        // ------------------- Update Total Course/Registration Fee -------------------
        // ✅ CHANGED: added "FOR UPDATE" to lock this row too, for the same reason as above.
        $idQuery = "SELECT installment_payment_table_id FROM installment_details_table WHERE student_id = ? AND programme_batch = ? FOR UPDATE";
        $idStmt = $conn->prepare($idQuery);
        $idStmt->bind_param("ss", $studentId, $programmeBatch);
        $idStmt->execute();
        $idResult = $idStmt->get_result();

        if ($idRow = $idResult->fetch_assoc()) {
            $installmentPaymentTableId = $idRow['installment_payment_table_id'];
            $updateQuery = "UPDATE installment_payment_table SET ";
            $params = [];
            $types = "";

            // ✅ CHANGED: wrapped both decrements in GREATEST(0, ...) so coursefee
            // and registrationfee can never be driven negative by a duplicate request.
            if (!empty($totalInstallments)) {
                $updateQuery .= "coursefee = GREATEST(0, coursefee - ?), ";
                $params[] = $totalInstallments;
                $types .= "d";
            }
            if (!empty($initialPayment)) {
                $updateQuery .= "registrationfee = GREATEST(0, registrationfee - ?), ";
                $params[] = $initialPayment;
                $types .= "d";
            }

            if (count($params) > 0) {
                $updateQuery = rtrim($updateQuery, ', ') . " WHERE id = ?";
                $params[] = $installmentPaymentTableId;
                $types .= "i";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param($types, ...$params);
                if (!$updateStmt->execute()) {
                    throw new Exception("Error updating installment_payment_table: " . $updateStmt->error);
                }
            }
        }

        // ------------------- Insert Initial Payment -------------------
        if (!empty($initialPayment)) {
            $insertInitialQuery = "INSERT INTO payment_wise_info 
                                   (student_id, program_batch, installmentNumber, paymentAmount, paid_date, payment_type, bank_name, card_bank_deposit_dt, entered_by, rcpt_number) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertInitialStmt = $conn->prepare($insertInitialQuery);
            $installmentNumber = 'Initial Payment';
            $insertInitialStmt->bind_param(
                "issdssssss",
                $studentId,
                $programmeBatch,
                $installmentNumber,
                $initialPayment,
                $paid_date_value,
                $paymentType,
                $bankName,
                $card_deposit_value,
                $Session_username,
                $receiptNumber
            );

            // ✅ NEW: same duplicate-key handling as above.
            if (!$insertInitialStmt->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("Duplicate payment blocked: this initial payment (receipt $receiptNumber) was already submitted.");
                }
                throw new Exception("Error inserting initial payment: " . $insertInitialStmt->error);
            }
        }

        $conn->commit();

        // ✅ Return success with total payment for the update script
        echo json_encode([
            'success' => true,
            'message' => 'All payment details processed successfully!',
            'receipt_number' => $receiptNumber,
            'total_payment' => floatval($totalPayment),  // ✅ Include total payment
            'student_id' => $studentId,
            'programme_batch' => $programmeBatch,
            'program_code' => $programCode
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
}