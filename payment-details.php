<?php
ini_set('memory_limit', '256M');
set_time_limit(300); // 5 minutes

session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

// Check if required parameters are provided
if (!isset($_GET['type']) || !isset($_GET['id']) || !isset($_GET['student'])) {
    echo '<script>alert("Missing required parameters"); window.history.back();</script>';
    exit;
}

$paymentId = $_GET['id'];
$studentId = $_GET['student'];
$paymentType = $_GET['type']; // 'uni' for university fee, 'course' for course fee

// Initialize variables
$paymentDetails = null;
$studentDetails = null;
$programDetails = null;
$outstandingDetails = [];

// Get student details
$studentSql = "SELECT s.student_code, s.first_name, s.last_name, s.mobile, s.personal_email, s.bms_email,
            ap.student_registration_id, ap.programme_code, b.batch_name, p.program_name,
            CONCAT(p.program_name, ' - ', b.batch_name) AS program_batch
          FROM students s
          JOIN allocate_programme ap ON s.student_code = ap.student_code
          JOIN batch_table b ON ap.batch_id = b.id
          JOIN program_table p ON ap.programme_code = p.program_code
          WHERE s.student_code = ?";
$stmt = $conn->prepare($studentSql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$studentDetails = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$studentDetails) {
    echo '<script>alert("Student not found"); window.history.back();</script>';
    exit;
}

// Get program_batch for filtering
$programBatch = $studentDetails['program_batch'];

// Get payment details based on payment type
if ($paymentType === 'uni') {
    // University fee payment
    $paymentSql = "SELECT u.*, 
                    DATE_FORMAT(u.paid_date, '%d %M %Y') as formatted_date
                  FROM payment_uni_fee u
                  WHERE u.id = ? AND u.student_id = ?";
    $stmt = $conn->prepare($paymentSql);
    $stmt->bind_param("ii", $paymentId, $studentId);
    $stmt->execute();
    $paymentDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paymentDetails) {
        $paymentDetails['payment_category'] = 'University Fee';
        $paymentDetails['payment_for'] = 'University Fee - ' . $paymentDetails['currency_type'];
        $paymentDetails['amount'] = $paymentDetails['LKR_money'];
    }
} else {
    // Course fee payment
    $paymentSql = "SELECT p.*, 
                    DATE_FORMAT(p.paid_date, '%d %M %Y') as formatted_date
                  FROM payment_wise_info p
                  WHERE p.id = ? AND p.student_id = ?";
    $stmt = $conn->prepare($paymentSql);
    $stmt->bind_param("ii", $paymentId, $studentId);
    $stmt->execute();
    $paymentDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paymentDetails) {
        $paymentDetails['payment_category'] = 'Course Fee';
        $paymentDetails['payment_for'] = str_replace('_', ' ', $paymentDetails['installmentNumber']);
        $paymentDetails['amount'] = $paymentDetails['paymentAmount'];
    }
}

if (!$paymentDetails) {
    echo '<script>alert("Payment details not found"); window.history.back();</script>';
    exit;
}

// ========================================
// COMPREHENSIVE COURSE FEE CALCULATION
// ========================================

$courseFeeDetails = [];
$discountDetails = [];

// 1. Get Total Course Fee Amount from installment_payment_table
$totalCourseFee = 0;
$totalRegistrationFee = 0;
$totalDiscount = 0;
$discountType = '';

$installmentPaymentSql = "SELECT * FROM installment_payment_table WHERE student_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($installmentPaymentSql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$installmentPayment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($installmentPayment) {
    // Check available columns and use them
    $totalCourseFee = $installmentPayment['coursefee_total'] ?? $installmentPayment['coursefee'] ?? 0;
    $totalRegistrationFee = $installmentPayment['registrationfee'] ?? 0;

    // Check for discount information
    if (isset($installmentPayment['discount_type']) && !empty($installmentPayment['discount_type'])) {
        $discountType = $installmentPayment['discount_type'];
        $discountValue = $installmentPayment['discount_value'] ?? 0;

        if ($discountType == 'Value') {
            $totalDiscount = $discountValue;
        } elseif ($discountType == 'Percentage' && $totalCourseFee > 0) {
            $totalDiscount = ($totalCourseFee * $discountValue) / 100;
        }

        $discountDetails = [
            'type' => $discountType,
            'value' => $discountValue,
            'amount' => $totalDiscount
        ];
    }

    // If coursefee_total doesn't exist, try to calculate from coursefee + paid amounts
    if (!isset($installmentPayment['coursefee_total']) && isset($installmentPayment['coursefee'])) {
        // Get total paid course fees to calculate original total
        $paidCourseFeeSql = "SELECT COALESCE(SUM(paymentAmount), 0) as total_paid 
                            FROM payment_wise_info 
                            WHERE student_id = ? 
                            AND program_batch = ?
                            AND installmentNumber != 'Initial Payment' 
                            AND installmentNumber NOT LIKE '%initial%' 
                            AND installmentNumber NOT LIKE '%registration%'
                            AND installmentNumber NOT LIKE '%Registration%'";
        $stmt = $conn->prepare($paidCourseFeeSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalPaidCourseFee = $paidResult['total_paid'] ?? 0;
        $currentOutstanding = $installmentPayment['coursefee'] ?? 0;
        $totalCourseFee = $currentOutstanding + $totalPaidCourseFee;
    }
}

// 2. If no data in installment_payment_table, try add_payment_plan_table
if ($totalCourseFee == 0) {
    $paymentPlanSql = "SELECT * FROM add_payment_plan_table WHERE student_id = ? AND programme_batch = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($paymentPlanSql);
    $stmt->bind_param("is", $studentId, $programBatch);
    $stmt->execute();
    $paymentPlan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paymentPlan) {
        $totalCourseFee = $paymentPlan['course_fee'] ?? 0;
        $totalRegistrationFee = $paymentPlan['registration_fee'] ?? 0;

        // Check for discount information
        if (isset($paymentPlan['discount_type']) && !empty($paymentPlan['discount_type'])) {
            $discountType = $paymentPlan['discount_type'];
            $discountValue = $paymentPlan['discount_value'] ?? 0;

            if ($discountType == 'Value') {
                $totalDiscount = $discountValue;
            } elseif ($discountType == 'Percentage' && $totalCourseFee > 0) {
                $totalDiscount = ($totalCourseFee * $discountValue) / 100;
            }

            $discountDetails = [
                'type' => $discountType,
                'value' => $discountValue,
                'amount' => $totalDiscount
            ];
        }
    }
}

// 3. Check for discount in installment_details_table
if (empty($discountDetails)) {
    $discountSql = "SELECT 
                        idt.discount_type, 
                        idt.discount_value,
                        SUM(CASE 
                            WHEN idt.discount_type = 'Value' THEN idt.discount_value 
                            WHEN idt.discount_type = 'Percentage' THEN (idt.devided_values * idt.discount_value / 100)
                            ELSE 0 
                        END) as total_discount
                    FROM installment_details_table idt
                    JOIN installment_payment_table ipt ON idt.installment_payment_table_id = ipt.id
                    WHERE ipt.student_id = ?
                    AND idt.discount_type IS NOT NULL
                    AND idt.discount_value > 0
                    GROUP BY idt.discount_type";
    $stmt = $conn->prepare($discountSql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $discountResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($discountResult) {
        $discountType = $discountResult['discount_type'];
        $discountValue = $discountResult['discount_value'];
        $totalDiscount = $discountResult['total_discount'];

        $discountDetails = [
            'type' => $discountType,
            'value' => $discountValue,
            'amount' => $totalDiscount
        ];
    }
}

// 3. Calculate Total Paid Course Fee and Registration Fee
$paidCourseFeeSql = "SELECT 
                        COALESCE(SUM(CASE 
                            WHEN installmentNumber != 'Initial Payment' 
                            AND installmentNumber NOT LIKE '%initial%' 
                            AND installmentNumber NOT LIKE '%registration%'
                            AND installmentNumber NOT LIKE '%Registration%'
                            THEN paymentAmount 
                            ELSE 0 
                        END), 0) as course_fee_paid,
                        COALESCE(SUM(CASE 
                            WHEN installmentNumber = 'Initial Payment' 
                            OR installmentNumber LIKE '%initial%' 
                            OR installmentNumber LIKE '%registration%'
                            OR installmentNumber LIKE '%Registration%'
                            THEN paymentAmount 
                            ELSE 0 
                        END), 0) as registration_fee_paid
                    FROM payment_wise_info 
                    WHERE student_id = ? AND program_batch = ?";
$stmt = $conn->prepare($paidCourseFeeSql);
$stmt->bind_param("is", $studentId, $programBatch);
$stmt->execute();
$paidResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalPaidCourseFee = $paidResult['course_fee_paid'] ?? 0;
$totalPaidRegistrationFee = $paidResult['registration_fee_paid'] ?? 0;

// 4. Calculate Outstanding Amounts
// Apply discount to total course fee if exists
$discountedCourseFee = $totalCourseFee - $totalDiscount;
$outstandingCourseFee = max(0, $discountedCourseFee - $totalPaidCourseFee);
$outstandingRegistrationFee = max(0, $totalRegistrationFee - $totalPaidRegistrationFee);

$courseFeeDetails = [
    'total_course' => $totalCourseFee,
    'discounted_course' => $discountedCourseFee,
    'discount' => $totalDiscount,
    'discount_type' => $discountType,
    'discount_value' => $discountDetails['value'] ?? 0,
    'paid_course' => $totalPaidCourseFee,
    'outstanding_course' => $outstandingCourseFee,
    'total_registration' => $totalRegistrationFee,
    'paid_registration' => $totalPaidRegistrationFee,
    'outstanding_registration' => $outstandingRegistrationFee
];

// ========================================
// COMPREHENSIVE UNIVERSITY FEE CALCULATION
// ========================================

$uniFeeDetails = [];

// Get university fee details from installment_payment_table
if ($installmentPayment) {
    // Check for LKR University Fee
    $lkrTotal = 0;
    if (isset($installmentPayment['unifee_lkr_total'])) {
        $lkrTotal = $installmentPayment['unifee_lkr_total'];
    } elseif (isset($installmentPayment['unifee_lkr'])) {
        // Calculate total from current outstanding + paid
        $paidLKRSql = "SELECT COALESCE(SUM(LKR_money), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND (currency_type = 'LKR' OR currency_type IS NULL)";
        $stmt = $conn->prepare($paidLKRSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidLKR = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $lkrTotal = $installmentPayment['unifee_lkr'] + $paidLKR;
    }

    if ($lkrTotal > 0) {
        $paidLKRSql = "SELECT COALESCE(SUM(LKR_money), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND (currency_type = 'LKR' OR currency_type IS NULL)";
        $stmt = $conn->prepare($paidLKRSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidLKR = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingLKR = max(0, $lkrTotal - $paidLKR);

        if ($outstandingLKR > 0 || $paidLKR > 0) {
            $uniFeeDetails['LKR'] = [
                'total' => $lkrTotal,
                'paid' => $paidLKR,
                'outstanding' => $outstandingLKR
            ];
        }
    }

    // Check for GBP University Fee
    $gbpTotal = 0;
    if (isset($installmentPayment['unifee_gbp_total'])) {
        $gbpTotal = $installmentPayment['unifee_gbp_total'];
    } elseif (isset($installmentPayment['unifee_gbp'])) {
        $paidGBPSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'GBP'";
        $stmt = $conn->prepare($paidGBPSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidGBP = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $gbpTotal = $installmentPayment['unifee_gbp'] + $paidGBP;
    }

    if ($gbpTotal > 0) {
        $paidGBPSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'GBP'";
        $stmt = $conn->prepare($paidGBPSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidGBP = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingGBP = max(0, $gbpTotal - $paidGBP);

        if ($outstandingGBP > 0 || $paidGBP > 0) {
            $uniFeeDetails['GBP'] = [
                'total' => $gbpTotal,
                'paid' => $paidGBP,
                'outstanding' => $outstandingGBP
            ];
        }
    }

    // Check for USD University Fee
    $usdTotal = 0;
    if (isset($installmentPayment['unifee_usd_total'])) {
        $usdTotal = $installmentPayment['unifee_usd_total'];
    } elseif (isset($installmentPayment['unifee_usd'])) {
        $paidUSDSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'USD'";
        $stmt = $conn->prepare($paidUSDSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidUSD = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $usdTotal = $installmentPayment['unifee_usd'] + $paidUSD;
    }

    if ($usdTotal > 0) {
        $paidUSDSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'USD'";
        $stmt = $conn->prepare($paidUSDSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidUSD = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingUSD = max(0, $usdTotal - $paidUSD);

        if ($outstandingUSD > 0 || $paidUSD > 0) {
            $uniFeeDetails['USD'] = [
                'total' => $usdTotal,
                'paid' => $paidUSD,
                'outstanding' => $outstandingUSD
            ];
        }
    }
}

// If no university fee details found in installment_payment_table, check add_payment_plan_table
if (empty($uniFeeDetails) && isset($paymentPlan)) {
    // LKR University Fee
    if (isset($paymentPlan['university_fee_LKR']) && $paymentPlan['university_fee_LKR'] > 0) {
        $totalLKR = $paymentPlan['university_fee_LKR'];

        $paidLKRSql = "SELECT COALESCE(SUM(LKR_money), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND (currency_type = 'LKR' OR currency_type IS NULL)";
        $stmt = $conn->prepare($paidLKRSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidLKR = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingLKR = max(0, $totalLKR - $paidLKR);

        $uniFeeDetails['LKR'] = [
            'total' => $totalLKR,
            'paid' => $paidLKR,
            'outstanding' => $outstandingLKR
        ];
    }

    // GBP University Fee
    if (isset($paymentPlan['university_fee_GBP']) && $paymentPlan['university_fee_GBP'] > 0) {
        $totalGBP = $paymentPlan['university_fee_GBP'];

        $paidGBPSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'GBP'";
        $stmt = $conn->prepare($paidGBPSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidGBP = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingGBP = max(0, $totalGBP - $paidGBP);

        $uniFeeDetails['GBP'] = [
            'total' => $totalGBP,
            'paid' => $paidGBP,
            'outstanding' => $outstandingGBP
        ];
    }

    // USD University Fee
    if (isset($paymentPlan['university_fee_USD']) && $paymentPlan['university_fee_USD'] > 0) {
        $totalUSD = $paymentPlan['university_fee_USD'];

        $paidUSDSql = "SELECT COALESCE(SUM(paid_amount), 0) as total_paid 
                      FROM payment_uni_fee 
                      WHERE student_id = ? 
                      AND program_batch = ?
                      AND currency_type = 'USD'";
        $stmt = $conn->prepare($paidUSDSql);
        $stmt->bind_param("is", $studentId, $programBatch);
        $stmt->execute();
        $paidUSD = $stmt->get_result()->fetch_assoc()['total_paid'];
        $stmt->close();

        $outstandingUSD = max(0, $totalUSD - $paidUSD);

        $uniFeeDetails['USD'] = [
            'total' => $totalUSD,
            'paid' => $paidUSD,
            'outstanding' => $outstandingUSD
        ];
    }
}

// Format the receipt number - Use actual rcpt_number from database
$receiptNumber = $paymentType === 'uni' ? ($paymentDetails['rcpt_number'] ?? 'UNI-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT)) : $paymentDetails['rcpt_number'];

// Get current date for the receipt
$currentDate = date('d M Y');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= $receiptNumber ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Open Sans", sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
        }

        .payment-receipt {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .receipt-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .receipt-logo {
            max-height: 80px;
        }

        .receipt-title {
            font-size: 24px;
            font-weight: 700;
            color: #0d6efd;
            margin-bottom: 5px;
        }

        .receipt-subtitle {
            font-size: 16px;
            color: #6c757d;
        }

        .receipt-number {
            font-size: 18px;
            font-weight: 600;
            color: #198754;
            margin-bottom: 5px;
        }

        .receipt-date {
            font-size: 14px;
            color: #6c757d;
        }

        .student-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .student-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .student-id {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .payment-details {
            margin-bottom: 20px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-label {
            font-weight: 600;
            color: #495057;
        }

        .payment-value {
            text-align: right;
        }

        .payment-amount {
            font-size: 24px;
            font-weight: 700;
            color: #198754;
            text-align: right;
            margin-top: 10px;
        }

        .receipt-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 10px;
            text-align: center;
        }

        .badge-category {
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .badge-uni {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .badge-course {
            background-color: #dcfce7;
            color: #166534;
        }

        .outstanding-section {
            background-color: #fff8f8;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #f5e6e6;
        }

        .outstanding-title {
            font-size: 18px;
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .outstanding-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e9ecef;
        }

        .outstanding-total {
            font-size: 18px;
            font-weight: 700;
            color: #dc3545;
            text-align: right;
            margin-top: 10px;
        }

        .fee-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }

        .fee-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        @media print {
            body {
                background-color: #fff;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                height: 100%;
            }

            .no-print {
                display: none !important;
            }

            #wrapper,
            #content-wrapper,
            #content,
            .container-fluid {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .payment-receipt {
                box-shadow: none;
                margin: 0 auto;
                padding: 15px;
                max-width: 100%;
                width: 650px;
            }

            .receipt-header,
            .student-info,
            .payment-details,
            .outstanding-section,
            .signature-section,
            .receipt-footer {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .text-md-end {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            .payment-receipt {
                page-break-inside: avoid;
            }
        }

        .fee-breakdown-section {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #0d6efd;
        }

        .fee-breakdown-section .fee-title {
            font-size: 16px;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .fee-breakdown-section .outstanding-row {
            padding: 8px 0;
            border-bottom: 1px dashed #e9ecef;
        }

        .fee-breakdown-section .payment-label {
            font-weight: 600;
            color: #495057;
        }

        .fee-breakdown-section .payment-value {
            text-align: right;
        }

        .fee-breakdown-section .paid-amount {
            color: #198754;
        }

        .fee-breakdown-section .remaining-amount {
            color: #dc3545;
        }

        .summary-section {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }

        .summary-section .total-outstanding {
            font-size: 18px;
            font-weight: 700;
            color: #dc3545;
        }

        .discount-badge {
            background-color: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 5px;
        }

        .discount-row {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
        }

        @media print {

            /* A4 dimensions in pixels at 96 DPI */
            .payment-receipt {
                width: 210mm;
                /* A4 width */
                min-height: auto;
                /* Remove fixed height */
                padding: 10mm;
                /* Reduce padding */
                margin: 0 auto;
                box-sizing: border-box;
                page-break-after: avoid;
                /* Prevent page break */
            }

            /* Optimize spacing for single page */
            .receipt-header {
                margin-bottom: 10px;
                padding-bottom: 5px;
            }

            .receipt-logo {
                max-height: 50px;
                /* Reduce logo size further */
            }

            .receipt-title {
                font-size: 18px;
                margin-bottom: 3px;
            }

            .receipt-subtitle {
                font-size: 11px;
                margin: 3px 0;
            }

            .student-info {
                padding: 8px;
                margin-bottom: 10px;
            }

            .student-name {
                font-size: 14px;
                margin-bottom: 2px;
            }

            .student-id,
            .mb-0,
            p {
                font-size: 11px;
                margin-bottom: 1px;
            }

            .payment-details {
                margin-bottom: 10px;
            }

            .payment-row {
                padding: 3px 0;
            }

            .payment-label,
            .payment-value {
                font-size: 11px;
            }

            .payment-amount {
                font-size: 16px;
                margin-top: 3px;
            }

            .fee-breakdown-section {
                padding: 8px;
                margin-top: 8px;
            }

            .fee-title {
                font-size: 12px;
                margin-bottom: 3px;
            }

            .outstanding-section {
                padding: 8px;
                margin-top: 8px;
            }

            .outstanding-row {
                padding: 2px 0;
            }

            .signature-section {
                margin-top: 15px;
            }

            .signature-line {
                width: 120px;
            }

            .receipt-footer {
                margin-top: 10px;
                font-size: 10px;
            }

            /* Ensure proper spacing between sections */
            .fee-section,
            .summary-section {
                margin-top: 5px;
            }

            /* Optimize badge sizes */
            .badge {
                font-size: 10px;
                padding: 2px 4px;
            }

            /* Reduce gaps between elements */
            .payment-receipt {
                gap: 5px;
            }

            /* Optimize row heights */
            .outstanding-row,
            .payment-row {
                min-height: 18px;
            }
        }

        /* Add these styles for better content organization */
        .payment-receipt {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Ensure content doesn't overflow */
        * {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Optimize spacing for better fit */
        .outstanding-row,
        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 24px;
        }
    </style>
</head>

<body>
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="no-print">
            <?php include("nav.php"); ?>
        </div>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <div class="no-print">
                    <?php include("includes/topnav.php"); ?>
                </div>

                <!-- Begin Page Content -->
                <div class="container-fluid py-4">
                    <div class="row mb-4 no-print">
                        <div class="col-md-6">
                            <h2 class="text-secondary">Payment Receipt</h2>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-secondary me-2" onclick="window.history.back()">
                                <i class="fas fa-arrow-left me-1"></i> Back
                            </button>
                            <button class="btn btn-outline-primary me-2" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button class="btn btn-outline-success" id="emailButton" onclick="sendEmail()">
                                <i class="fas fa-envelope me-1"></i> Email
                            </button>
                        </div>
                    </div>

                    <!-- Payment Receipt -->
                    <div class="payment-receipt">
                        <div class="receipt-header d-flex justify-content-between align-items-center">
                            <div>
                                <img src="https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png" alt="Receipt Logo" style="width: 150px;" class="receipt-logo">
                                <p class="receipt-subtitle"> <br>Business Management School <br>591, Galle Road, Colombo 06</p>

                            </div>
                            <div class="text-end">
                                <h1 class="receipt-title">PAYMENT RECEIPT</h1>
                                <p class="receipt-number">Receipt #: <?= htmlspecialchars($receiptNumber) ?></p>
                                <p class="receipt-date">Date: <?= $currentDate ?></p>
                            </div>
                        </div>

                        <div class="student-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="student-name"><?= htmlspecialchars($studentDetails['first_name'] . ' ' . $studentDetails['last_name']) ?></h3>
                                    <p class="student-id">Student ID: <?= htmlspecialchars($studentDetails['student_registration_id']) ?></p>
                                    <p class="mb-0"><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($studentDetails['bms_email'] ?? 'N/A') ?></p>
                                    <p><i class="fas fa-phone me-2"></i> <?= htmlspecialchars($studentDetails['mobile'] ?? 'N/A') ?></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <p><strong>Program:</strong> <?= htmlspecialchars($studentDetails['program_name']) ?></p>
                                    <p><strong>Batch:</strong> <?= htmlspecialchars($studentDetails['batch_name']) ?></p>
                                    <p>
                                        <span class="badge badge-category <?= $paymentType === 'uni' ? 'badge-uni' : 'badge-course' ?>">
                                            <?= htmlspecialchars($paymentDetails['payment_category']) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="payment-details">
                            <h4 class="mb-3">Payment Information</h4>

                            <div class="payment-row">
                                <div class="payment-label">Payment For</div>
                                <div class="payment-value"><?= htmlspecialchars($paymentDetails['payment_for']) ?></div>
                            </div>

                            <div class="payment-row">
                                <div class="payment-label">Payment Date</div>
                                <div class="payment-value"><?= htmlspecialchars($paymentDetails['formatted_date']) ?></div>
                            </div>

                            <div class="payment-row">
                                <div class="payment-label">Payment Method</div>
                                <div class="payment-value text-capitalize"><?= htmlspecialchars($paymentDetails['payment_type']) ?></div>
                            </div>

                            <?php if ($paymentType === 'uni'): ?>
                                <div class="payment-row">
                                    <div class="payment-label">Original Amount</div>
                                    <div class="payment-value"><?= htmlspecialchars($paymentDetails['currency_type']) ?> <?= number_format($paymentDetails['paid_amount'], 2) ?></div>
                                </div>

                                <div class="payment-row">
                                    <div class="payment-label">Exchange Rate</div>
                                    <div class="payment-value"><?= number_format($paymentDetails['exchange_rate'], 2) ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="payment-amount">
                                Rs. <?= number_format($paymentDetails['amount'], 2) ?>
                            </div>
                        </div>

                        <!-- Course Fee Breakdown Section -->
                        <?php if ($courseFeeDetails['total_course'] > 0): ?>
                            <div class="fee-breakdown-section">
                                <h5 class="fee-title">Course Fee Breakdown</h5>
                                <div class="outstanding-row">
                                    <div class="payment-label">Total Course Fee</div>
                                    <div class="payment-value">Rs. <?= number_format($courseFeeDetails['total_course'], 2) ?></div>
                                </div>

                                <?php if ($totalDiscount > 0): ?>
                                    <!-- <div class="outstanding-row discount-row">
                                        <div class="payment-label">
                                            Discount
                                            <?php if ($discountType == 'Percentage'): ?>
                                                <span class="discount-badge"><?= number_format($courseFeeDetails['discount_value'], 2) ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="payment-value paid-amount">- Rs. <?= number_format($totalDiscount, 2) ?></div>
                                    </div> -->
                                    <div class="outstanding-row">
                                        <div class="payment-label"><strong>Discounted Course Fee</strong></div>
                                        <div class="payment-value"><strong>Rs. <?= number_format($courseFeeDetails['discounted_course'], 2) ?></strong></div>
                                    </div>
                                <?php endif; ?>

                                <!-- <div class="outstanding-row">
                                    <div class="payment-label">Course Fee Payments</div>
                                    <div class="payment-value paid-amount">Rs. <?= number_format($courseFeeDetails['paid_course'], 2) ?></div>
                                </div>
                                <div class="outstanding-row">
                                    <div class="payment-label">Registration/Initial Payments</div>
                                    <div class="payment-value paid-amount">Rs. <?= number_format($courseFeeDetails['paid_registration'], 2) ?></div>
                                </div> -->
                                <?php
                                // Calculate outstanding after deducting both course payments and registration payments
                                $totalPaidForCourse = $courseFeeDetails['paid_course'] + $courseFeeDetails['paid_registration'];
                                $actualOutstandingCourse = max(0, $courseFeeDetails['discounted_course'] - $totalPaidForCourse);
                                ?>
                                <div class="outstanding-row">
                                    <div class="payment-label">Total Paid (Course + Registration)</div>
                                    <div class="payment-value paid-amount">Rs. <?= number_format($totalPaidForCourse, 2) ?></div>
                                </div>
                                <!-- <div class="outstanding-row">
                                    <div class="payment-label"><strong>Outstanding Course Fee</strong></div>
                                    <div class="payment-value remaining-amount"><strong>Rs. <?= number_format($actualOutstandingCourse, 2) ?></strong></div>
                                </div> -->
                                <?php if ($actualOutstandingCourse <= 0): ?>
                                    <div class="outstanding-row">
                                        <div class="payment-label"></div>
                                        <div class="payment-value"><span class="badge bg-success">Fully Paid</span></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Outstanding Amount Section - CORRECTED CALCULATION -->
                        <?php
                        $hasOutstandingCourseFee = $actualOutstandingCourse > 0;
                        $hasOutstandingUniFee = false;
                        foreach ($uniFeeDetails as $currency => $details) {
                            if ($details['outstanding'] > 0) {
                                $hasOutstandingUniFee = true;
                                break;
                            }
                        }

                        if ($hasOutstandingCourseFee || $hasOutstandingUniFee):
                        ?>
                            <div class="outstanding-section">
                                <!-- <h4 class="outstanding-title">Outstanding Balance</h4> -->

                                <?php if ($hasOutstandingCourseFee): ?>
                                    <div class="outstanding-row">
                                        <div class="payment-label" style="font-weight: bolder; color: red;">Outstanding Course Fee</div>
                                        <div class="payment-value" style="font-weight: bolder; color: red;">Rs. <?= number_format($actualOutstandingCourse, 2) ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasOutstandingUniFee): ?>
                                    <div class="fee-section">
                                        <?php foreach ($uniFeeDetails as $currency => $details): ?>
                                            <?php if ($details['outstanding'] > 0): ?>
                                                <div class="outstanding-row">
                                                    <div class="payment-label" style="font-weight: bolder; color: red;">Outstanding University Fee (<?= $currency ?>)</div>
                                                    <div class="payment-value" style="font-weight: bolder; color: red;"><?= $currency ?> <?= number_format($details['outstanding'], 2) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Summary Section -->
                        <?php
                        // Use the corrected outstanding course fee calculation
                        $totalOutstanding = $actualOutstandingCourse; // Only course fee outstanding since registration is included above

                        if ($totalOutstanding > 0):
                        ?>
                            <!-- <div class="summary-section">
                                <div class="outstanding-row total-outstanding">
                                    <div class="payment-label"><strong>Total Outstanding (LKR)</strong></div>
                                    <div class="payment-value"><strong>Rs. <?= number_format($totalOutstanding, 2) ?></strong></div>
                                </div>
                            </div> -->
                        <?php else: ?>
                            <div class="summary-section">
                                <div class="outstanding-row">
                                    <div class="payment-label"></div>
                                    <div class="payment-value"><span class="badge bg-success fs-6">All LKR Fees Paid</span></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="signature-section">
                            <div>
                                <div class="signature-line">
                                    Student Signature
                                </div>
                            </div>
                            <div>
                                <div class="signature-line">
                                    Authorized Signature
                                </div>
                            </div>
                        </div>

                        <div class="receipt-footer">
                            <p>Thank you for your payment. This receipt is computer generated and does not require a physical signature.</p>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add html2pdf.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // Function to send email
        async function sendEmail() {
            // Get the button element
            const emailButton = document.getElementById('emailButton');

            // Get student BMS email
            const studentEmail = "<?= htmlspecialchars($studentDetails['bms_email'] ?? '') ?>";

            if (!studentEmail) {
                alert("Student BMS email address is not available.");
                return;
            }

            try {
                // Disable the button and show loading state
                emailButton.disabled = true;
                emailButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating PDF...';

                // Generate PDF
                const element = document.querySelector('.payment-receipt');
                const opt = {
                    margin: [0.3, 0.3, 0.3, 0.3], // [top, left, bottom, right] margins in inches
                    filename: 'payment-receipt.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        letterRendering: true
                    },
                    jsPDF: {
                        unit: 'in',
                        format: 'a4',
                        orientation: 'portrait',
                        pagesplit: false // Ensure all content fits on one page
                    },
                    pagebreak: {
                        mode: 'avoid-all'
                    }
                };

                // Generate PDF and get the blob
                const pdfBlob = await html2pdf().set(opt).from(element).output('blob');

                // Create FormData object
                const formData = new FormData();
                formData.append('payment_id', <?= $paymentId ?>);
                formData.append('student_id', <?= $studentId ?>);
                formData.append('payment_type', '<?= $paymentType ?>');
                formData.append('email', studentEmail);
                formData.append('pdf', pdfBlob, 'payment-receipt.pdf');

                // Update button text
                emailButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

                // AJAX call to send email
                $.ajax({
                    url: 'send-receipt-email.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Show success message
                        alert('Receipt has been sent to ' + studentEmail);

                        // Reset button state
                        emailButton.disabled = false;
                        emailButton.innerHTML = '<i class="fas fa-envelope me-1"></i> Email';
                    },
                    error: function() {
                        // Show error message
                        alert('Failed to send email. Please try again.');

                        // Reset button state
                        emailButton.disabled = false;
                        emailButton.innerHTML = '<i class="fas fa-envelope me-1"></i> Email';
                    }
                });
            } catch (error) {
                alert('Failed to generate PDF. Please try again.');
                emailButton.disabled = false;
                emailButton.innerHTML = '<i class="fas fa-envelope me-1"></i> Email';
            }
        }
    </script>
</body>

</html>