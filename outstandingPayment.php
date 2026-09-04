<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

// Initialize variables
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : 'all';
$selectedStudent = isset($_POST['student']) ? $_POST['student'] : '';
$selectedProgram = isset($_POST['program']) ? $_POST['program'] : '';
$selectedBatch = isset($_POST['batch']) ? $_POST['batch'] : '';
$searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';
$paymentDetails = [];

// Fetch programs for dropdown
$programsQuery = "SELECT program_code, program_name FROM program_table";
$programsResult = $conn->query($programsQuery);
$programs = [];
if ($programsResult->num_rows > 0) {
    while ($row = $programsResult->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Fetch batches for dropdown (optional)
$batchesQuery = "SELECT id, batch_name FROM batch_table";
$batchesResult = $conn->query($batchesQuery);
$batches = [];
if ($batchesResult->num_rows > 0) {
    while ($row = $batchesResult->fetch_assoc()) {
        $batches[] = $row;
    }
}

// Fetch students
$studentsQuery = "
    SELECT s.student_code, s.first_name, s.last_name, ap.programme_code, ap.batch_id, ap.student_registration_id
    FROM allocate_programme ap
    JOIN students s ON ap.student_code = s.student_code
    WHERE ap.status = 'active'
";

// Filter by program
if ($reportType == 'programme' && !empty($selectedProgram)) {
    $studentsQuery .= " AND ap.programme_code = $selectedProgram";
}

// Filter by batch
if ($reportType == 'programme' && !empty($selectedBatch)) {
    $studentsQuery .= " AND ap.batch_id = $selectedBatch";
}

// Filter by search term
if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $studentsQuery .= " AND (s.first_name LIKE '%$searchTerm%' OR s.last_name LIKE '%$searchTerm%' OR ap.student_registration_id LIKE '%$searchTerm%')";
}

$studentsQuery .= " ORDER BY s.first_name, s.last_name";
$studentsResult = $conn->query($studentsQuery);
$students = [];
if ($studentsResult->num_rows > 0) {
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paymentPlansQuery = "
        SELECT 
            s.student_code,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            s.first_name,
            s.last_name,
            s.personal_email as email,
            s.mobile as contact_no,
            ap.student_registration_id,
            p.program_code,
            p.program_name,
            b.batch_name,
            CONCAT(p.program_name, ' - ', b.batch_name) AS programme_batch,
            appt.university_fee_LKR,
            appt.university_fee_GBP,
            appt.university_fee_USD,
            appt.registration_fee_LKR,
            appt.registration_fee_GBP,
            appt.registration_fee_USD,
            appt.lkr_reg_due_date
        FROM allocate_programme ap
        JOIN students s ON ap.student_code = s.student_code
        JOIN program_table p ON ap.programme_code = p.program_code
        JOIN batch_table b ON ap.batch_id = b.id
        LEFT JOIN add_payment_plan_table appt ON s.student_code = appt.student_id 
            AND CONCAT(p.program_name, ' - ', b.batch_name) = appt.programme_batch
        WHERE ap.status = 'active'
    ";

    // Filters
    if ($reportType == 'student' && !empty($selectedStudent)) {
        $paymentPlansQuery .= " AND s.student_code = $selectedStudent";
    } elseif ($reportType == 'programme' && !empty($selectedProgram)) {
        $paymentPlansQuery .= " AND p.program_code = $selectedProgram";
        if (!empty($selectedBatch)) {
            $paymentPlansQuery .= " AND b.id = $selectedBatch";
        }
    }

    if (!empty($searchTerm)) {
        $searchTerm = $conn->real_escape_string($searchTerm);
        $paymentPlansQuery .= " AND (s.first_name LIKE '%$searchTerm%' OR s.last_name LIKE '%$searchTerm%' OR ap.student_registration_id LIKE '%$searchTerm%')";
    }

    // Order by student name
    $paymentPlansQuery .= " ORDER BY s.first_name, s.last_name";

    $paymentPlansResult = $conn->query($paymentPlansQuery);

    if ($paymentPlansResult && $paymentPlansResult->num_rows > 0) {
        while ($plan = $paymentPlansResult->fetch_assoc()) {
            $studentId = $plan['student_code'];
            $programmeBatch = $plan['programme_batch'];

            // 1. Check University Fee Payments - FIXED VERSION
            // Get university fee amounts from payment plan
            $uniFeeLKR = !empty($plan['university_fee_LKR']) ? $plan['university_fee_LKR'] : 0;
            $uniFeeGBP = !empty($plan['university_fee_GBP']) ? $plan['university_fee_GBP'] : 0;
            $uniFeeUSD = !empty($plan['university_fee_USD']) ? $plan['university_fee_USD'] : 0;

            // FIXED: Calculate university fee payments separately for each currency

            // LKR University Fee
            if ($uniFeeLKR > 0) {
                $uniPaymentsLKRQuery = "
                    SELECT 
                        COALESCE(SUM(LKR_money), 0) as total_paid_lkr
                    FROM payment_uni_fee
                    WHERE student_id = $studentId 
                    AND program_batch = '$programmeBatch'
                    AND (currency_type = 'LKR' OR currency_type IS NULL)
                ";

                $uniPaymentsLKRResult = $conn->query($uniPaymentsLKRQuery);
                $paidLKR = 0;

                if ($uniPaymentsLKRResult && $uniPaymentsLKRResult->num_rows > 0) {
                    $payment = $uniPaymentsLKRResult->fetch_assoc();
                    $paidLKR = $payment['total_paid_lkr'];
                }

                $outstandingLKR = $uniFeeLKR - $paidLKR;

                // Add LKR fee to payment details
                $paymentDetails[] = [
                    'student_code' => $studentId,
                    'student_name' => $plan['student_name'],
                    'first_name' => $plan['first_name'],
                    'last_name' => $plan['last_name'],
                    'email' => $plan['email'],
                    'contact_no' => $plan['contact_no'],
                    'student_registration_id' => $plan['student_registration_id'],
                    'programme_batch' => $programmeBatch,
                    'payment_type' => 'University Fee (LKR)',
                    'installment_numbers' => 'N/A',
                    'installment_amount' => $uniFeeLKR,
                    'due_date' => date('Y-m-d'), // Current date as placeholder
                    'total_paid_amount' => $paidLKR,
                    'outstanding_amount' => $outstandingLKR,
                    'currency' => 'LKR'
                ];
            }

            // GBP University Fee
            if ($uniFeeGBP > 0) {
                $uniPaymentsGBPQuery = "
                    SELECT 
                        COALESCE(SUM(paid_amount), 0) as total_paid_gbp
                    FROM payment_uni_fee
                    WHERE student_id = $studentId 
                    AND program_batch = '$programmeBatch'
                    AND currency_type = 'GBP'
                ";

                $uniPaymentsGBPResult = $conn->query($uniPaymentsGBPQuery);
                $paidGBP = 0;

                if ($uniPaymentsGBPResult && $uniPaymentsGBPResult->num_rows > 0) {
                    $payment = $uniPaymentsGBPResult->fetch_assoc();
                    $paidGBP = $payment['total_paid_gbp'];
                }

                $outstandingGBP = $uniFeeGBP - $paidGBP;

                // Add GBP fee to payment details
                $paymentDetails[] = [
                    'student_code' => $studentId,
                    'student_name' => $plan['student_name'],
                    'first_name' => $plan['first_name'],
                    'last_name' => $plan['last_name'],
                    'email' => $plan['email'],
                    'contact_no' => $plan['contact_no'],
                    'student_registration_id' => $plan['student_registration_id'],
                    'programme_batch' => $programmeBatch,
                    'payment_type' => 'University Fee (GBP)',
                    'installment_numbers' => 'N/A',
                    'installment_amount' => $uniFeeGBP,
                    'due_date' => date('Y-m-d'), // Current date as placeholder
                    'total_paid_amount' => $paidGBP,
                    'outstanding_amount' => $outstandingGBP,
                    'currency' => 'GBP'
                ];
            }

            // USD University Fee
            if ($uniFeeUSD > 0) {
                $uniPaymentsUSDQuery = "
                    SELECT 
                        COALESCE(SUM(paid_amount), 0) as total_paid_usd
                    FROM payment_uni_fee
                    WHERE student_id = $studentId 
                    AND program_batch = '$programmeBatch'
                    AND currency_type = 'USD'
                ";

                $uniPaymentsUSDResult = $conn->query($uniPaymentsUSDQuery);
                $paidUSD = 0;

                if ($uniPaymentsUSDResult && $uniPaymentsUSDResult->num_rows > 0) {
                    $payment = $uniPaymentsUSDResult->fetch_assoc();
                    $paidUSD = $payment['total_paid_usd'];
                }

                $outstandingUSD = $uniFeeUSD - $paidUSD;

                // Add USD fee to payment details
                $paymentDetails[] = [
                    'student_code' => $studentId,
                    'student_name' => $plan['student_name'],
                    'first_name' => $plan['first_name'],
                    'last_name' => $plan['last_name'],
                    'email' => $plan['email'],
                    'contact_no' => $plan['contact_no'],
                    'student_registration_id' => $plan['student_registration_id'],
                    'programme_batch' => $programmeBatch,
                    'payment_type' => 'University Fee (USD)',
                    'installment_numbers' => 'N/A',
                    'installment_amount' => $uniFeeUSD,
                    'due_date' => date('Y-m-d'), // Current date as placeholder
                    'total_paid_amount' => $paidUSD,
                    'outstanding_amount' => $outstandingUSD,
                    'currency' => 'USD'
                ];
            }

            // 2. Check Registration Fee Payments
            $regFeeLKR = !empty($plan['registration_fee_LKR']) ? $plan['registration_fee_LKR'] : 0;
            $regFeeGBP = !empty($plan['registration_fee_GBP']) ? $plan['registration_fee_GBP'] : 0;
            $regFeeUSD = !empty($plan['registration_fee_USD']) ? $plan['registration_fee_USD'] : 0;

            // Get registration fee payments
            $regPaymentsQuery = "
                SELECT 
                    COALESCE(SUM(paymentAmount), 0) as total_paid
                FROM payment_wise_info
                WHERE student_id = $studentId
                AND status ='paid'
                AND program_batch = '$programmeBatch'
                AND (installmentNumber = 'Initial Payment' 
                     OR installmentNumber LIKE '%initial%' 
                     OR installmentNumber LIKE '%registration%'
                     OR installmentNumber LIKE '%Registration%')
            ";
            $regPaymentsResult = $conn->query($regPaymentsQuery);
            $regPaid = 0;

            if ($regPaymentsResult && $regPaymentsResult->num_rows > 0) {
                $regPayment = $regPaymentsResult->fetch_assoc();
                $regPaid = !empty($regPayment['total_paid']) ? $regPayment['total_paid'] : 0;
                $dueDate = !empty($plan['lkr_reg_due_date']) ? $plan['lkr_reg_due_date'] : 'N/A';
            }

            // Calculate outstanding registration fee
            $totalRegFee = $regFeeLKR + $regFeeGBP + $regFeeUSD;
            $outstandingRegFee = $totalRegFee - $regPaid;

            // Add registration fee to payment details
            if ($totalRegFee > 0) {
                $paymentDetails[] = [
                    'student_code' => $studentId,
                    'student_name' => $plan['student_name'],
                    'first_name' => $plan['first_name'],
                    'last_name' => $plan['last_name'],
                    'email' => $plan['email'],
                    'contact_no' => $plan['contact_no'],
                    'student_registration_id' => $plan['student_registration_id'],
                    'programme_batch' => $programmeBatch,
                    'payment_type' => 'Registration Fee',
                    'installment_numbers' => 'Initial Payment',
                    'installment_amount' => $totalRegFee,
                    'due_date' => $dueDate,
                    'total_paid_amount' => $regPaid,
                    'outstanding_amount' => $outstandingRegFee,
                    'currency' => 'LKR'
                ];
            }

            // 3. Check Installment Payments
            $installmentsQuery = "
                SELECT 
                    idt.*,
                    idt.due_date
                FROM installment_payment_table ipt
                JOIN installment_details_table idt ON ipt.id = idt.installment_payment_table_id
                JOIN add_payment_plan_table appt ON ipt.payment_plans_tb_id = appt.id
                WHERE appt.student_id = $studentId 
                AND appt.programme_batch = '$programmeBatch'
                ORDER BY idt.installment_numbers
            ";

            $installmentsResult = $conn->query($installmentsQuery);

            if ($installmentsResult && $installmentsResult->num_rows > 0) {
                while ($installment = $installmentsResult->fetch_assoc()) {
                    $installmentNumber = $installment['installment_numbers'];
                    $originalAmount = !empty($installment['devided_values']) ? $installment['devided_values'] : 0;

                    // Apply discount if exists
                    if (!empty($installment['discount_type'])) {
                        if ($installment['discount_type'] == 'Value') {
                            $originalAmount -= $installment['discount_value'];
                        } elseif ($installment['discount_type'] == 'Percentage') {
                            $originalAmount -= ($originalAmount * $installment['discount_value']) / 100;
                        }
                    }

                    // Get installment payments from payment_wise_info
                    $installmentPaymentsQuery = "
                        SELECT 
                            COALESCE(SUM(paymentAmount), 0) as total_paid
                        FROM payment_wise_info
                        WHERE student_id = $studentId 
                        AND status ='paid'
                        AND program_batch = '$programmeBatch'
                        AND installmentNumber = '$installmentNumber'
                        AND installmentNumber != 'Initial Payment'
                        AND installmentNumber NOT LIKE '%initial%'
                        AND installmentNumber NOT LIKE '%registration%'
                        AND installmentNumber NOT LIKE '%Registration%'
                    ";

                    $installmentPaymentsResult = $conn->query($installmentPaymentsQuery);
                    $installmentPaid = 0;

                    if ($installmentPaymentsResult && $installmentPaymentsResult->num_rows > 0) {
                        $installmentPayment = $installmentPaymentsResult->fetch_assoc();
                        $installmentPaid = !empty($installmentPayment['total_paid']) ? $installmentPayment['total_paid'] : 0;
                    }

                    // Calculate outstanding installment amount
                    $outstandingInstallment = $originalAmount - $installmentPaid;

                    // Add to payment details
                    $paymentDetails[] = [
                        'student_code' => $studentId,
                        'student_name' => $plan['student_name'],
                        'first_name' => $plan['first_name'],
                        'last_name' => $plan['last_name'],
                        'email' => $plan['email'],
                        'contact_no' => $plan['contact_no'],
                        'student_registration_id' => $plan['student_registration_id'],
                        'programme_batch' => $programmeBatch,
                        'payment_type' => 'Installment',
                        'installment_numbers' => $installmentNumber,
                        'original_amount' => $originalAmount,
                        'installment_amount' => $installmentPaid,
                        'due_date' => $installment['due_date'],
                        'total_paid_amount' => $installmentPaid,
                        'outstanding_amount' => $outstandingInstallment,
                        'currency' => 'LKR'
                    ];
                }
            }
        }
    }
}

// UPDATED: Function to calculate past due and paid totals for a student with separate past due calculations
function calculateStudentTotals($studentPaymentDetails)
{
    $totals = [
        'total_original' => 0,
        'total_paid' => 0,
        'total_outstanding' => 0,
        'past_due_total' => 0,
        'past_due_count' => 0,
        // Separate University Fee totals
        'uni_fee_original' => 0,
        'uni_fee_paid' => 0,
        'uni_fee_outstanding' => 0,
        'uni_fee_past_due_total' => 0,
        'uni_fee_past_due_count' => 0,
        // Registration Fee + Installment totals (combined)
        'reg_installment_original' => 0,
        'reg_installment_paid' => 0,
        'reg_installment_outstanding' => 0,
        'reg_installment_past_due_total' => 0,
        'reg_installment_past_due_count' => 0
    ];

    $currentDate = new DateTime();

    foreach ($studentPaymentDetails as $detail) {
        // Check if this is University Fee (any currency)
        $isUniFee = strpos($detail['payment_type'], 'University Fee') !== false;

        // For installments, use original_amount if available, otherwise use installment_amount
        $originalAmount = isset($detail['original_amount']) ? $detail['original_amount'] : $detail['installment_amount'];
        $paidAmount = $detail['total_paid_amount'];
        $outstandingAmount = $detail['outstanding_amount'];

        // Check if payment is past due
        $isPastDue = false;
        if (!empty($detail['due_date']) && $outstandingAmount > 0) {
            $dueDate = new DateTime($detail['due_date']);
            $isPastDue = $dueDate < $currentDate;
        }

        // Separate University Fee from Registration Fee + Installments
        if ($isUniFee) {
            // University Fee totals (separate)
            $totals['uni_fee_original'] += $originalAmount;
            $totals['uni_fee_paid'] += $paidAmount;
            $totals['uni_fee_outstanding'] += $outstandingAmount;

            // University Fee past due
            if ($isPastDue) {
                $totals['uni_fee_past_due_total'] += $outstandingAmount;
                $totals['uni_fee_past_due_count']++;
            }
        } else {
            // Registration Fee + Installment totals (combined)
            $totals['reg_installment_original'] += $originalAmount;
            $totals['reg_installment_paid'] += $paidAmount;
            $totals['reg_installment_outstanding'] += $outstandingAmount;

            // Registration Fee + Installment past due
            if ($isPastDue) {
                $totals['reg_installment_past_due_total'] += $outstandingAmount;
                $totals['reg_installment_past_due_count']++;
            }
        }

        // Add to overall totals
        $totals['total_original'] += $originalAmount;
        $totals['total_paid'] += $paidAmount;
        $totals['total_outstanding'] += $outstandingAmount;

        // Add to overall past due
        if ($isPastDue) {
            $totals['past_due_total'] += $outstandingAmount;
            $totals['past_due_count']++;
        }
    }

    return $totals;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Student Outstanding Payment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f5f5f5;
        }

        .header {
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
        }

        .form-section {
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
        }

        .report-section {
            padding: 20px 0;
            min-height: 200px;
        }

        .footer {
            padding: 15px 0;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
        }

        .action-btn {
            background-color: #4aabc5;
            border: none;
        }

        .submit-btn {
            background-color: #5cb85c;
            border: none;
        }

        .label-text {
            display: inline-block;
            width: 150px;
            text-align: right;
            margin-right: 10px;
        }

        .payment-breakdown {
            font-size: 0.85rem;
            color: #666;
        }

        .payment-type {
            display: inline-block;
            margin-right: 10px;
        }

        /* Updated styling for different total rows */
        .reg-installment-total-row {
            font-weight: bold;
            background-color: #f0f8ff;
            border-left: 4px solid #4169e1;
        }

        .uni-fee-row {
            font-weight: bold;
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
        }

        .past-due-reg-installment-row {
            font-weight: bold;
            background-color: #ffe6e6;
            border-left: 4px solid #ff4d4f;
            color: #cc0000;
        }

        .past-due-uni-fee-row {
            font-weight: bold;
            background-color: #fff0f0;
            border-left: 4px solid #ff6b6b;
            color: #cc0000;
        }

        .payment-type-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .university-fee {
            background-color: #e6f7ff;
            color: #0066cc;
        }

        .registration-fee {
            background-color: #fff2e6;
            color: #ff8c1a;
        }

        .installment {
            background-color: #e6ffe6;
            color: #009900;
        }

        .past-due {
            color: #cc0000;
        }

        .currency-badge {
            font-size: 0.7rem;
            padding: 2px 5px;
            border-radius: 10px;
            background-color: #f0f0f0;
            color: #666;
            margin-left: 5px;
        }

        .paid-amount {
            color: #009900;
        }

        .remaining-amount {
            color: #cc0000;
            font-weight: bold;
        }

        .fully-paid {
            background-color: #f8f9fa;
        }

        .fully-paid .remaining-amount {
            color: #28a745;
        }

        .student-header {
            background-color: #e9ecef;
            font-weight: bold;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
        }

        .student-section {
            margin-bottom: 30px;
            border-bottom: 2px dashed #ccc;
            padding-bottom: 20px;
        }

        .no-outstanding {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        .student-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #4aabc5;
        }

        .student-details-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .student-detail-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }

        .student-detail-value {
            flex: 1;
        }

        .search-box {
            position: relative;
            margin-bottom: 15px;
        }

        .search-box .form-control {
            padding-right: 40px;
        }

        .search-box .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        .sortable {
            cursor: pointer;
            position: relative;
        }

        .sortable:after {
            content: "↕";
            position: absolute;
            right: 8px;
            color: #999;
            font-size: 12px;
        }

        .sortable.asc:after {
            content: "↑";
            color: #333;
        }

        .sortable.desc:after {
            content: "↓";
            color: #333;
        }

        .table th {
            position: relative;
            padding-right: 20px;
        }

        .select2-container--bootstrap-5 .select2-selection {
            height: 38px;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("nav.php"); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("includes/topnav.php"); ?>

                <!-- Begin Page Content -->
                <div class="p-3" style="font-size: 12px;">
                    <!-- Displaying sub_list_value -->
                    <div class="">
                        <div class="container">
                            <!-- Header -->
                            <div class="row header">
                                <div class="col-md-6">
                                    <h2 class="text-secondary">Enhanced Student Outstanding Payment</h2>
                                </div>
                                <div class="col-md-6 text-end no-print">
                                    <button class="btn btn-primary action-btn me-2" onclick="exportToExcel()">Export</button>
                                    <button class="btn btn-primary action-btn" onclick="window.print()">Print</button>
                                </div>
                            </div>


                            <!-- Form Section -->
                            <form method="post" class="no-print">
                                <div class="row form-section">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <span class="label-text">Report Type</span>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="reportType" id="all" value="all" <?php echo $reportType == 'all' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="all">All</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="reportType" id="student" value="student" <?php echo $reportType == 'student' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="student">Student</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="reportType" id="programme" value="programme" <?php echo $reportType == 'programme' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="programme">Programme</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6 mb-3">
                                        <div class="mb-3">
                                            <label for="studentSelect" class="form-label">Select Student:</label>
                                            <select class="form-select select2" name="student" id="studentSelect" <?php echo $reportType != 'student' ? 'disabled' : ''; ?>>
                                                <option value="">Select a student</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?php echo $student['student_code']; ?>" <?php echo $selectedStudent == $student['student_code'] ? 'selected' : ''; ?>>
                                                        <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_registration_id'] . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="mb-3">
                                            <label for="programSelect" class="form-label">Select Programme:</label>
                                            <select class="form-select select2" name="program" id="programSelect" <?php echo $reportType != 'programme' ? 'disabled' : ''; ?>>
                                                <option value="">Select a programme</option>
                                                <?php foreach ($programs as $program): ?>
                                                    <option value="<?php echo $program['program_code']; ?>" <?php echo $selectedProgram == $program['program_code'] ? 'selected' : ''; ?>>
                                                        <?php echo $program['program_name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>




                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="batchSelect" class="form-label">Select Batch:</label>
                                        <select class="form-select select2" name="batch" id="batchSelect" disabled>
                                            <option value="">Select a batch</option>
                                            <!-- Options will be loaded dynamically via AJAX -->
                                        </select>
                                    </div>


                                    <script>
                                        $(document).ready(function() {
                                            $('#programSelect').change(function() {
                                                var programId = $(this).val();
                                                if (programId != '') {
                                                    $.ajax({
                                                        url: 'get_batches_for_out.php', // we'll create this
                                                        type: 'POST',
                                                        data: {
                                                            program_id: programId
                                                        },
                                                        success: function(response) {
                                                            $('#batchSelect').html(response);
                                                            $('#batchSelect').prop('disabled', false);
                                                            $('#batchSelect').select2(); // if using select2
                                                        }
                                                    });
                                                } else {
                                                    $('#batchSelect').html('<option value="">Select a batch</option>');
                                                    $('#batchSelect').prop('disabled', true);
                                                }
                                            });
                                        });
                                    </script>

                                    <div id="studentSearchSection" class="col-md-6 mb-3" style="<?php echo ($reportType == 'programme' && !empty($selectedProgram)) || $reportType == 'all' ? '' : 'display: none;'; ?>">
                                        <div class="mb-3">
                                            <label for="searchTerm" class="form-label">Search Student:</label>
                                            <div class="search-box">
                                                <input type="text" class="form-control" id="searchTerm" name="searchTerm" placeholder="Search by name or ID" value="<?php echo $searchTerm; ?>">
                                                <i class="bi bi-search search-icon"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 text-end mb-3">
                                        <button type="submit" class="btn btn-success submit-btn">Submit</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Report Section -->
                            <div class="row report-section">
                                <div class="col-12">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-grid me-2"></i>
                                        <span class="fw-bold">Enhanced Outstanding Payment Report</span>
                                    </div>

                                    <?php if (!empty($paymentDetails)): ?>
                                        <?php if ($reportType == 'all' || $reportType == 'programme'): ?>
                                            <?php
                                            // Group payments by student
                                            $studentPayments = [];
                                            foreach ($paymentDetails as $detail) {
                                                $studentId = $detail['student_code'];
                                                if (!isset($studentPayments[$studentId])) {
                                                    $studentPayments[$studentId] = [
                                                        'student_name' => $detail['student_name'],
                                                        'first_name' => $detail['first_name'],
                                                        'last_name' => $detail['last_name'],
                                                        'email' => $detail['email'],
                                                        'contact_no' => $detail['contact_no'],
                                                        'student_registration_id' => $detail['student_registration_id'],
                                                        'payments' => [],
                                                        'total_outstanding' => 0
                                                    ];
                                                }
                                                $studentPayments[$studentId]['payments'][] = $detail;
                                                $studentPayments[$studentId]['total_outstanding'] += $detail['outstanding_amount'];
                                            }

                                            // Filter out students with no outstanding payments
                                            $studentPayments = array_filter($studentPayments, function ($studentData) {
                                                return $studentData['total_outstanding'] > 0;
                                            });

                                            // Display payments grouped by student
                                            foreach ($studentPayments as $studentId => $studentData):
                                                $studentName = $studentData['student_name'];
                                                $firstName = $studentData['first_name'];
                                                $lastName = $studentData['last_name'];
                                                $email = $studentData['email'] ?? 'N/A';
                                                $contactNo = $studentData['contact_no'] ?? 'N/A';
                                                $studentRegId = $studentData['student_registration_id'];
                                                $studentPaymentDetails = $studentData['payments'];
                                                $totalOutstanding = $studentData['total_outstanding'];

                                                // Calculate enhanced totals for this student
                                                $studentTotals = calculateStudentTotals($studentPaymentDetails);
                                            ?>
                                                <div class="student-section">
                                                    <div class="student-header">
                                                        <i class="bi bi-person-circle me-2"></i>
                                                        Student: <?php echo $studentName; ?> (<?php echo $studentRegId; ?>)
                                                    </div>

                                                    <!-- Student Details Card -->
                                                    <div class="student-details">
                                                        <div class="student-details-row">
                                                            <div class="student-detail-label">Name:</div>
                                                            <div class="student-detail-value"><?php echo $firstName . ' ' . $lastName; ?></div>
                                                        </div>
                                                        <div class="student-details-row">
                                                            <div class="student-detail-label">ID:</div>
                                                            <div class="student-detail-value"><?php echo $studentRegId; ?></div>
                                                        </div>
                                                        <?php if (!empty($email) && $email != 'N/A'): ?>
                                                            <div class="student-details-row">
                                                                <div class="student-detail-label">Email:</div>
                                                                <div class="student-detail-value"><?php echo $email; ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($contactNo) && $contactNo != 'N/A'): ?>
                                                            <div class="student-details-row">
                                                                <div class="student-detail-label">Contact:</div>
                                                                <div class="student-detail-value"><?php echo $contactNo; ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($totalOutstanding <= 0): ?>
                                                        <div class="no-outstanding mt-3">
                                                            <i class="bi bi-check-circle-fill me-2"></i>
                                                            No outstanding payments for this student
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="table-responsive mt-3">
                                                            <table class="table table-striped table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="sortable" data-sort="programme">Programme</th>
                                                                        <th class="sortable" data-sort="payment-type">Payment Type</th>
                                                                        <th class="sortable" data-sort="installment">Installment</th>
                                                                        <th class="sortable" data-sort="due-date">Due Date</th>
                                                                        <th class="sortable" data-sort="original-amount">Original Amount</th>
                                                                        <th class="sortable" data-sort="paid-amount">Paid Amount</th>
                                                                        <th class="sortable" data-sort="remaining-balance">Remaining Balance</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    foreach ($studentPaymentDetails as $detail):
                                                                        // Check if this is a University Fee payment
                                                                        $isUniFee = strpos($detail['payment_type'], 'University Fee') !== false;

                                                                        // For installments, use original_amount and installment_amount
                                                                        $originalAmount = isset($detail['original_amount']) ? $detail['original_amount'] : $detail['installment_amount'];
                                                                        $paidAmount = $detail['total_paid_amount'];

                                                                        // Check if payment is fully paid
                                                                        $isFullyPaid = $detail['outstanding_amount'] <= 0;

                                                                        // Determine if payment is past due
                                                                        $isPastDue = false;
                                                                        if (!empty($detail['due_date']) && !$isFullyPaid) {
                                                                            $dueDate = new DateTime($detail['due_date']);
                                                                            $today = new DateTime();
                                                                            $isPastDue = $dueDate < $today;
                                                                        }

                                                                        // Set payment type badge class
                                                                        $paymentTypeBadgeClass = '';
                                                                        if ($isUniFee) {
                                                                            $paymentTypeBadgeClass = 'university-fee';
                                                                        } else if ($detail['payment_type'] == 'Registration Fee') {
                                                                            $paymentTypeBadgeClass = 'registration-fee';
                                                                        } else if ($detail['payment_type'] == 'Installment') {
                                                                            $paymentTypeBadgeClass = 'installment';
                                                                        }

                                                                        // Set row class based on payment status
                                                                        $rowClass = '';
                                                                        if ($isFullyPaid) {
                                                                            $rowClass = 'fully-paid';
                                                                        } else if ($isPastDue) {
                                                                            $rowClass = 'past-due';
                                                                        }
                                                                    ?>
                                                                        <tr class="<?php echo $rowClass; ?>">
                                                                            <td><?php echo $detail['programme_batch']; ?></td>
                                                                            <td>
                                                                                <span class="payment-type-badge <?php echo $paymentTypeBadgeClass; ?>">
                                                                                    <?php echo $detail['payment_type']; ?>
                                                                                </span>
                                                                            </td>
                                                                            <td><?php echo str_replace('_', ' ', $detail['installment_numbers']); ?></td>
                                                                            <td>
                                                                                <?php
                                                                                if (!empty($detail['due_date']) && $detail['due_date'] != '0000-00-00') {
                                                                                    echo date('Y-m-d', strtotime($detail['due_date']));
                                                                                    if ($isPastDue) {
                                                                                        echo ' <span class="badge bg-danger">Past Due</span>';
                                                                                    } else if ($isFullyPaid) {
                                                                                        echo ' <span class="badge bg-success">Paid</span>';
                                                                                    }
                                                                                } else {
                                                                                    echo 'N/A';
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo number_format($originalAmount, 2); ?>
                                                                                <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                                    <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="paid-amount">
                                                                                <?php if ($paidAmount > 0): ?>
                                                                                    <?php echo number_format($paidAmount, 2); ?>
                                                                                <?php else: ?>
                                                                                    0.00
                                                                                <?php endif; ?>
                                                                                <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                                    <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="remaining-amount">
                                                                                <?php echo number_format($detail['outstanding_amount'], 2); ?>
                                                                                <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                                    <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                                <?php endif; ?>
                                                                                <?php if ($isFullyPaid): ?>
                                                                                    <span class="badge bg-success ms-1">Paid</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>

                                                                    <!-- Enhanced Total Rows with separate past due calculations -->
                                                                    <tr class="reg-installment-total-row">
                                                                        <td colspan="4" class="text-end">Total (Registration Fee + Installment):</td>
                                                                        <td><?php echo number_format($studentTotals['reg_installment_original'], 2); ?></td>
                                                                        <td class="paid-amount"><?php echo number_format($studentTotals['reg_installment_paid'], 2); ?></td>
                                                                        <td class="remaining-amount"><?php echo number_format($studentTotals['reg_installment_outstanding'], 2); ?></td>
                                                                    </tr>
                                                                    <tr class="uni-fee-row">
                                                                        <td colspan="4" class="text-end">University Fee Outstanding:</td>
                                                                        <td><?php echo number_format($studentTotals['uni_fee_original'], 2); ?></td>
                                                                        <td class="paid-amount"><?php echo number_format($studentTotals['uni_fee_paid'], 2); ?></td>
                                                                        <td class="remaining-amount"><?php echo number_format($studentTotals['uni_fee_outstanding'], 2); ?></td>
                                                                    </tr>
                                                                    <tr class="past-due-reg-installment-row">
                                                                        <td colspan="4" class="text-end">Past Due Total - Registration Fee + Installment (<?php echo $studentTotals['reg_installment_past_due_count']; ?> payments):</td>
                                                                        <td>-</td>
                                                                        <td>-</td>
                                                                        <td class="remaining-amount"><?php echo number_format($studentTotals['reg_installment_past_due_total'], 2); ?></td>
                                                                    </tr>
                                                                    <tr class="past-due-uni-fee-row">
                                                                        <td colspan="4" class="text-end">Past Due Total - University Fee (<?php echo $studentTotals['uni_fee_past_due_count']; ?> payments):</td>
                                                                        <td>-</td>
                                                                        <td>-</td>
                                                                        <td class="remaining-amount"><?php echo number_format($studentTotals['uni_fee_past_due_total'], 2); ?></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <?php
                                            // Calculate total outstanding amount for student
                                            $totalStudentOutstanding = 0;
                                            $studentInfo = null;

                                            foreach ($paymentDetails as $detail) {
                                                $totalStudentOutstanding += $detail['outstanding_amount'];
                                                if ($studentInfo === null) {
                                                    $studentInfo = [
                                                        'name' => $detail['student_name'],
                                                        'first_name' => $detail['first_name'],
                                                        'last_name' => $detail['last_name'],
                                                        'email' => $detail['email'] ?? 'N/A',
                                                        'contact_no' => $detail['contact_no'] ?? 'N/A',
                                                        'student_registration_id' => $detail['student_registration_id']
                                                    ];
                                                }
                                            }

                                            // Calculate enhanced totals for single student
                                            $studentTotals = calculateStudentTotals($paymentDetails);

                                            if ($totalStudentOutstanding <= 0): ?>
                                                <div class="alert alert-info">No outstanding payments found for this student.</div>
                                            <?php else: ?>
                                                <!-- Student Details Card -->
                                                <div class="student-details">
                                                    <div class="student-details-row">
                                                        <div class="student-detail-label">Name:</div>
                                                        <div class="student-detail-value"><?php echo $studentInfo['first_name'] . ' ' . $studentInfo['last_name']; ?></div>
                                                    </div>
                                                    <div class="student-details-row">
                                                        <div class="student-detail-label">ID:</div>
                                                        <div class="student-detail-value"><?php echo $studentInfo['student_registration_id']; ?></div>
                                                    </div>
                                                    <?php if (!empty($studentInfo['email']) && $studentInfo['email'] != 'N/A'): ?>
                                                        <div class="student-details-row">
                                                            <div class="student-detail-label">Email:</div>
                                                            <div class="student-detail-value"><?php echo $studentInfo['email']; ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($studentInfo['contact_no']) && $studentInfo['contact_no'] != 'N/A'): ?>
                                                        <div class="student-details-row">
                                                            <div class="student-detail-label">Contact:</div>
                                                            <div class="student-detail-value"><?php echo $studentInfo['contact_no']; ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th class="sortable" data-sort="programme">Programme</th>
                                                                <th class="sortable" data-sort="payment-type">Payment Type</th>
                                                                <th class="sortable" data-sort="installment">Installment</th>
                                                                <th class="sortable" data-sort="due-date">Due Date</th>
                                                                <th class="sortable" data-sort="original-amount">Original Amount</th>
                                                                <th class="sortable" data-sort="paid-amount">Paid Amount</th>
                                                                <th class="sortable" data-sort="remaining-balance">Remaining Balance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            foreach ($paymentDetails as $detail):
                                                                // Check if this is a University Fee payment
                                                                $isUniFee = strpos($detail['payment_type'], 'University Fee') !== false;

                                                                // For installments, use original_amount and installment_amount
                                                                $originalAmount = isset($detail['original_amount']) ? $detail['original_amount'] : $detail['installment_amount'];
                                                                $paidAmount = $detail['total_paid_amount'];

                                                                // Check if payment is fully paid
                                                                $isFullyPaid = $detail['outstanding_amount'] <= 0;

                                                                // Determine if payment is past due
                                                                $isPastDue = false;
                                                                if (!empty($detail['due_date']) && !$isFullyPaid) {
                                                                    $dueDate = new DateTime($detail['due_date']);
                                                                    $today = new DateTime();
                                                                    $isPastDue = $dueDate < $today;
                                                                }

                                                                // Set payment type badge class
                                                                $paymentTypeBadgeClass = '';
                                                                if ($isUniFee) {
                                                                    $paymentTypeBadgeClass = 'university-fee';
                                                                } else if ($detail['payment_type'] == 'Registration Fee') {
                                                                    $paymentTypeBadgeClass = 'registration-fee';
                                                                } else if ($detail['payment_type'] == 'Installment') {
                                                                    $paymentTypeBadgeClass = 'installment';
                                                                }

                                                                // Set row class based on payment status
                                                                $rowClass = '';
                                                                if ($isFullyPaid) {
                                                                    $rowClass = 'fully-paid';
                                                                } else if ($isPastDue) {
                                                                    $rowClass = 'past-due';
                                                                }
                                                            ?>
                                                                <tr class="<?php echo $rowClass; ?>">
                                                                    <td><?php echo $detail['programme_batch']; ?></td>
                                                                    <td>
                                                                        <span class="payment-type-badge <?php echo $paymentTypeBadgeClass; ?>">
                                                                            <?php echo $detail['payment_type']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo str_replace('_', ' ', $detail['installment_numbers']); ?></td>
                                                                    <td>
                                                                        <?php
                                                                        if (!empty($detail['due_date']) && $detail['due_date'] != '0000-00-00') {
                                                                            echo date('Y-m-d', strtotime($detail['due_date']));
                                                                            if ($isPastDue) {
                                                                                echo ' <span class="badge bg-danger">Past Due</span>';
                                                                            } else if ($isFullyPaid) {
                                                                                echo ' <span class="badge bg-success">Paid</span>';
                                                                            }
                                                                        } else {
                                                                            echo 'N/A';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php echo number_format($originalAmount, 2); ?>
                                                                        <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                            <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="paid-amount">
                                                                        <?php if ($paidAmount > 0): ?>
                                                                            <?php echo number_format($paidAmount, 2); ?>
                                                                        <?php else: ?>
                                                                            0.00
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                            <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="remaining-amount">
                                                                        <?php echo number_format($detail['outstanding_amount'], 2); ?>
                                                                        <?php if (!empty($detail['currency']) && $detail['currency'] != 'LKR'): ?>
                                                                            <span class="currency-badge"><?php echo $detail['currency']; ?></span>
                                                                        <?php endif; ?>
                                                                        <?php if ($isFullyPaid): ?>
                                                                            <span class="badge bg-success ms-1">Paid</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>

                                                            <!-- Enhanced Total Rows with separate past due calculations -->
                                                            <tr class="reg-installment-total-row">
                                                                <td colspan="4" class="text-end">Total (Registration Fee + Installment):</td>
                                                                <td><?php echo number_format($studentTotals['reg_installment_original'], 2); ?></td>
                                                                <td class="paid-amount"><?php echo number_format($studentTotals['reg_installment_paid'], 2); ?></td>
                                                                <td class="remaining-amount"><?php echo number_format($studentTotals['reg_installment_outstanding'], 2); ?></td>
                                                            </tr>
                                                            <tr class="uni-fee-row">
                                                                <td colspan="4" class="text-end">University Fee Outstanding:</td>
                                                                <td><?php echo number_format($studentTotals['uni_fee_original'], 2); ?></td>
                                                                <td class="paid-amount"><?php echo number_format($studentTotals['uni_fee_paid'], 2); ?></td>
                                                                <td class="remaining-amount"><?php echo number_format($studentTotals['uni_fee_outstanding'], 2); ?></td>
                                                            </tr>
                                                            <tr class="past-due-reg-installment-row">
                                                                <td colspan="4" class="text-end">Past Due Total - Registration Fee + Installment (<?php echo $studentTotals['reg_installment_past_due_count']; ?> payments):</td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td class="remaining-amount"><?php echo number_format($studentTotals['reg_installment_past_due_total'], 2); ?></td>
                                                            </tr>
                                                            <tr class="past-due-uni-fee-row">
                                                                <td colspan="4" class="text-end">Past Due Total - University Fee (<?php echo $studentTotals['uni_fee_past_due_count']; ?> payments):</td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td class="remaining-amount"><?php echo number_format($studentTotals['uni_fee_past_due_total'], 2); ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
                                            <div class="alert alert-info">No outstanding payments found.</div>
                                        <?php else: ?>
                                            <div class="text-center mt-4">
                                                <h5>Select options and submit to view outstanding payments</h5>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="row footer">
                                <div class="col-12">
                                    <p>2025 © BMS | Business Management School</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Page Content -->
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
        });

        // Enable/disable dropdowns based on report type selection
        document.addEventListener('DOMContentLoaded', function() {
            const allRadio = document.getElementById('all');
            const studentRadio = document.getElementById('student');
            const programmeRadio = document.getElementById('programme');
            const studentSelect = document.getElementById('studentSelect');
            const programSelect = document.getElementById('programSelect');
            const studentSearchSection = document.getElementById('studentSearchSection');

            function handleRadioChange() {
                studentSelect.disabled = !studentRadio.checked;
                programSelect.disabled = !programmeRadio.checked;

                // Show/hide student search section for both All and Programme report types
                if (allRadio.checked) {
                    studentSearchSection.style.display = 'block';
                } else if (programmeRadio.checked && programSelect.value) {
                    studentSearchSection.style.display = 'block';
                } else {
                    studentSearchSection.style.display = 'none';
                }

                // Reset values when changing report type
                if (allRadio.checked) {
                    studentSelect.value = '';
                    programSelect.value = '';

                    // Reset Select2
                    $('.select2').val(null).trigger('change');
                }

                // Reinitialize Select2 after changing disabled state
                $('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select an option',
                    disabled: function() {
                        return $(this).is(':disabled');
                    }
                });
            }

            allRadio.addEventListener('change', handleRadioChange);
            studentRadio.addEventListener('change', handleRadioChange);
            programmeRadio.addEventListener('change', handleRadioChange);

            // Add event listener for program selection to update student search section
            programSelect.addEventListener('change', function() {
                if (programmeRadio.checked) {
                    studentSearchSection.style.display = this.value ? 'block' : 'none';
                }
            });

            // Initialize the form state on page load
            handleRadioChange();
        });

        // Export to Excel function
        function exportToExcel() {
            // Create a form to submit for Excel export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_outstanding_payment.php';

            // Add current filters as hidden inputs
            const reportTypeInput = document.createElement('input');
            reportTypeInput.type = 'hidden';
            reportTypeInput.name = 'reportType';
            reportTypeInput.value = document.querySelector('input[name="reportType"]:checked').value;
            form.appendChild(reportTypeInput);

            if (document.getElementById('student').checked) {
                const studentInput = document.createElement('input');
                studentInput.type = 'hidden';
                studentInput.name = 'student';
                studentInput.value = document.getElementById('studentSelect').value;
                form.appendChild(studentInput);
            }

            if (document.getElementById('programme').checked) {
                const programInput = document.createElement('input');
                programInput.type = 'hidden';
                programInput.name = 'program';
                programInput.value = document.getElementById('programSelect').value;
                form.appendChild(programInput);
            }

            // Include search term if available
            const searchTerm = document.getElementById('searchTerm').value;
            if (searchTerm) {
                const searchInput = document.createElement('input');
                searchInput.type = 'hidden';
                searchInput.name = 'searchTerm';
                searchInput.value = searchTerm;
                form.appendChild(searchInput);
            }

            // Add export flag
            const exportInput = document.createElement('input');
            exportInput.type = 'hidden';
            exportInput.name = 'export';
            exportInput.value = 'excel';
            form.appendChild(exportInput);

            // Submit the form
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all sortable headers
            const sortableHeaders = document.querySelectorAll('th.sortable');

            // Add click event to each sortable header
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const table = this.closest('table');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr:not(.reg-installment-total-row):not(.uni-fee-row):not(.past-due-reg-installment-row):not(.past-due-uni-fee-row)'));
                    const sortBy = this.dataset.sort;
                    const index = Array.from(this.parentNode.children).indexOf(this);

                    // Toggle sort direction
                    const isAsc = this.classList.contains('asc');

                    // Remove sort classes from all headers in this table
                    table.querySelectorAll('th.sortable').forEach(th => {
                        th.classList.remove('asc', 'desc');
                    });

                    // Add appropriate sort class to clicked header
                    this.classList.add(isAsc ? 'desc' : 'asc');

                    // Sort the rows
                    rows.sort((a, b) => {
                        let aValue = a.children[index].innerText.trim();
                        let bValue = b.children[index].innerText.trim();

                        // Handle numeric values (amounts)
                        if (sortBy === 'original-amount' || sortBy === 'paid-amount' || sortBy === 'remaining-balance') {
                            // Extract numeric value, removing currency formatting
                            aValue = parseFloat(aValue.replace(/[^0-9.-]+/g, '')) || 0;
                            bValue = parseFloat(bValue.replace(/[^0-9.-]+/g, '')) || 0;

                            return isAsc ? bValue - aValue : aValue - bValue;
                        }

                        // Handle date values
                        if (sortBy === 'due-date') {
                            // Extract date part only (ignore Past Due or Paid badges)
                            const aDate = aValue.split(' ')[0];
                            const bDate = bValue.split(' ')[0];

                            if (aDate === 'N/A') return isAsc ? 1 : -1;
                            if (bDate === 'N/A') return isAsc ? -1 : 1;

                            const aTime = new Date(aDate).getTime();
                            const bTime = new Date(bDate).getTime();

                            return isAsc ? bTime - aTime : aTime - bTime;
                        }

                        // Default string comparison
                        return isAsc ?
                            bValue.localeCompare(aValue) :
                            aValue.localeCompare(bValue);
                    });

                    // Remove all existing rows
                    rows.forEach(row => row.remove());

                    // Get total and summary rows
                    const regInstallmentTotalRow = tbody.querySelector('.reg-installment-total-row');
                    const uniFeeRow = tbody.querySelector('.uni-fee-row');
                    const pastDueRegInstallmentRow = tbody.querySelector('.past-due-reg-installment-row');
                    const pastDueUniFeeRow = tbody.querySelector('.past-due-uni-fee-row');

                    // Remove summary rows if they exist
                    if (regInstallmentTotalRow) regInstallmentTotalRow.remove();
                    if (uniFeeRow) uniFeeRow.remove();
                    if (pastDueRegInstallmentRow) pastDueRegInstallmentRow.remove();
                    if (pastDueUniFeeRow) pastDueUniFeeRow.remove();

                    // Append sorted rows
                    rows.forEach(row => tbody.appendChild(row));

                    // Re-append summary rows at the end if they exist
                    if (regInstallmentTotalRow) tbody.appendChild(regInstallmentTotalRow);
                    if (uniFeeRow) tbody.appendChild(uniFeeRow);
                    if (pastDueRegInstallmentRow) tbody.appendChild(pastDueRegInstallmentRow);
                    if (pastDueUniFeeRow) tbody.appendChild(pastDueUniFeeRow);
                });
            });
        });
    </script>
</body>

</html>