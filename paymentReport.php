<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 



// Fetch active students for the dropdown
$students = [];
$sql = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id 
        FROM allocate_programme ap
        JOIN students s ON ap.student_code = s.student_code
        WHERE ap.status = 'active'";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

// Fetch programmes for the dropdown
$programmes = [];
$sql2 = "SELECT program_code, program_name FROM program_table";
$result2 = $conn->query($sql2);
while ($row2 = $result2->fetch_assoc()) {
    $programmes[] = $row2;
}

// Set default date to today
$today = date('Y-m-d');
$asAtDate = isset($_POST['asAtDate']) ? $_POST['asAtDate'] : $today;
$betweenStart = isset($_POST['betweenStart']) ? $_POST['betweenStart'] : $today;
$betweenEnd = isset($_POST['betweenEnd']) ? $_POST['betweenEnd'] : $today;

// Process form submission
$selectedStudent = null;
$selectedProgramme = null;
$selectedBatch = null;
$studentDetails = null;
$programmeDetails = null;
$batchDetails = null;
$programmeStudents = [];
$universityFees = [];
$installmentPayments = [];
$installmentDetails = [];
$paymentInfo = [];
$allPayments = [];
$programBatchPayments = [];
$totalPaid = 0;
$totalUniFees = 0;
$totalCourseFee = 0;
$totalOutstanding = 0;
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : 'asAt';

// Fetch all batches for dropdown population
$batches = [];
$batchSql = "SELECT b.id, b.batch_name, p.program_code, p.program_name 
             FROM batch_table b
             JOIN program_table p ON b.programme = p.program_code
             ORDER BY p.program_name, b.batch_name";
$batchResult = $conn->query($batchSql);
while ($batchRow = $batchResult->fetch_assoc()) {
    if (!isset($batches[$batchRow['program_code']])) {
        $batches[$batchRow['program_code']] = [];
    }
    $batches[$batchRow['program_code']][] = [
        'id' => $batchRow['id'],
        'batch_name' => $batchRow['batch_name']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($reportType === 'student' && isset($_POST['studentSelect']) && !empty($_POST['studentSelect'])) {
        $selectedStudent = $_POST['studentSelect'];

        // Get student details
        $studentSql = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, ap.programme_code, b.batch_name, p.program_name
                      FROM students s
                      JOIN allocate_programme ap ON s.student_code = ap.student_code
                      JOIN batch_table b ON ap.batch_id = b.id
                      JOIN program_table p ON ap.programme_code = p.program_code
                      WHERE s.student_code = ?";
        $stmt = $conn->prepare($studentSql);
        $stmt->bind_param("i", $selectedStudent);
        $stmt->execute();
        $studentDetails = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($studentDetails) {
            $programBatch = $studentDetails['program_name'] . ' - ' . $studentDetails['batch_name'];

            // Get university fees
            $uniFeesSql = "SELECT * FROM payment_uni_fee WHERE student_id = ?";
            $stmt = $conn->prepare($uniFeesSql);
            $stmt->bind_param("i", $selectedStudent);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $universityFees[] = $row;
                $totalUniFees += $row['LKR_money'];
            }
            $stmt->close();

            // Get installment payment plan
            $installmentSql = "SELECT * FROM installment_payment_table WHERE student_id = ?";
            $stmt = $conn->prepare($installmentSql);
            $stmt->bind_param("i", $selectedStudent);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $installmentPayments[] = $row;
                $totalCourseFee = $row['coursefee_total'];
            }
            $stmt->close();

            // Get installment details
            $installmentDetailsSql = "SELECT * FROM installment_details_table WHERE student_id = ?";
            $stmt = $conn->prepare($installmentDetailsSql);
            $stmt->bind_param("i", $selectedStudent);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $installmentDetails[] = $row;
            }
            $stmt->close();

            // Get payment info
            $paymentInfoSql = "SELECT * FROM payment_wise_info WHERE student_id = ?";
            $stmt = $conn->prepare($paymentInfoSql);
            $stmt->bind_param("i", $selectedStudent);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $paymentInfo[] = $row;
                $totalPaid += $row['paymentAmount'];
            }
            $stmt->close();

            // Calculate outstanding
            $totalOutstanding = $totalCourseFee + $totalUniFees - $totalPaid;
        }
    } elseif ($reportType === 'programme' && isset($_POST['programmeSelect']) && !empty($_POST['programmeSelect'])) {
        $selectedProgramme = $_POST['programmeSelect'];
        $selectedBatch = isset($_POST['batchSelect']) && !empty($_POST['batchSelect']) ? $_POST['batchSelect'] : null;

        // Get programme details
        $programmeSql = "SELECT program_code, program_name FROM program_table WHERE program_code = ?";
        $stmt = $conn->prepare($programmeSql);
        $stmt->bind_param("i", $selectedProgramme);
        $stmt->execute();
        $programmeDetails = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Get batch details if selected
        if ($selectedBatch) {
            $batchSql = "SELECT id, batch_name FROM batch_table WHERE id = ?";
            $stmt = $conn->prepare($batchSql);
            $stmt->bind_param("i", $selectedBatch);
            $stmt->execute();
            $batchDetails = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if ($programmeDetails) {
            // Get all students in this programme and batch (if selected)
            $programmeStudentsSql = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, b.batch_name
                                    FROM allocate_programme ap
                                    JOIN students s ON ap.student_code = s.student_code
                                    JOIN batch_table b ON ap.batch_id = b.id
                                    WHERE ap.programme_code = ? ";

            if ($selectedBatch) {
                $programmeStudentsSql .= "AND ap.batch_id = ? ";
            }

            $programmeStudentsSql .= "AND ap.status = 'active' ORDER BY b.batch_name, s.last_name, s.first_name";

            $stmt = $conn->prepare($programmeStudentsSql);

            if ($selectedBatch) {
                $stmt->bind_param("ii", $selectedProgramme, $selectedBatch);
            } else {
                $stmt->bind_param("i", $selectedProgramme);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($student = $result->fetch_assoc()) {
                $studentId = $student['student_code'];
                $student['payments'] = [];
                $student['universityFees'] = [];
                $student['totalPaid'] = 0;
                $student['totalUniFees'] = 0;
                $student['totalCourseFee'] = 0;

                // Get university fees for this student
                $uniFeesSql = "SELECT * FROM payment_uni_fee WHERE student_id = ?";
                $stmtUni = $conn->prepare($uniFeesSql);
                $stmtUni->bind_param("i", $studentId);
                $stmtUni->execute();
                $uniResult = $stmtUni->get_result();
                while ($row = $uniResult->fetch_assoc()) {
                    $student['universityFees'][] = $row;
                    $student['totalUniFees'] += $row['LKR_money'];
                }
                $stmtUni->close();

                // Get course fee for this student
                $installmentSql = "SELECT coursefee_total FROM installment_payment_table WHERE student_id = ?";
                $stmtInstall = $conn->prepare($installmentSql);
                $stmtInstall->bind_param("i", $studentId);
                $stmtInstall->execute();
                $installResult = $stmtInstall->get_result();
                if ($row = $installResult->fetch_assoc()) {
                    $student['totalCourseFee'] = $row['coursefee_total'];
                }
                $stmtInstall->close();

                // Get payment info for this student
                $paymentInfoSql = "SELECT * FROM payment_wise_info WHERE student_id = ?";
                $stmtPayment = $conn->prepare($paymentInfoSql);
                $stmtPayment->bind_param("i", $studentId);
                $stmtPayment->execute();
                $paymentResult = $stmtPayment->get_result();
                while ($row = $paymentResult->fetch_assoc()) {
                    $student['payments'][] = $row;
                    $student['totalPaid'] += $row['paymentAmount'];
                }
                $stmtPayment->close();

                $programmeStudents[] = $student;
            }
            $stmt->close();

            // Group students by batch
            $batchGroups = [];
            foreach ($programmeStudents as $student) {
                $batchName = $student['batch_name'];
                if (!isset($batchGroups[$batchName])) {
                    $batchGroups[$batchName] = [];
                }
                $batchGroups[$batchName][] = $student;
            }
            $programmeStudents = $batchGroups;
        }
    } elseif ($reportType === 'asAt') {
        // All AS At report type
        $asAtDate = $_POST['asAtDate'];

        // Get all payments up to the specified date
        $allPaymentsSql = "
            SELECT 
                p.id as payment_id,
                p.student_id,
                s.first_name,
                s.last_name,
                ap.student_registration_id,
                pt.program_name,
                bt.batch_name,
                p.installmentNumber as payment_for,
                p.paymentAmount as amount,
                p.paid_date,
                p.payment_type,
                p.rcpt_number,
                p.status,
                p.cancellation_reason,
                'Course Fee' as payment_category
            FROM 
                payment_wise_info p
                JOIN students s ON p.student_id = s.student_code
                JOIN allocate_programme ap ON s.student_code = ap.student_code
                JOIN program_table pt ON ap.programme_code = pt.program_code
                JOIN batch_table bt ON ap.batch_id = bt.id
            WHERE 
                p.paid_date <= ?
            
            UNION ALL
            
            SELECT 
                u.id as payment_id,
                u.student_id,
                s.first_name,
                s.last_name,
                ap.student_registration_id,
                pt.program_name,
                bt.batch_name,
                CONCAT('University Fee - ', u.currency_type) as payment_for,
                u.LKR_money as amount,
                u.paid_date,
                u.payment_type,
                u.rcpt_number,
                 NULL as status, 
                  NULL as cancellation_reason,   
                'University Fee' as payment_category
            FROM 
                payment_uni_fee u
                JOIN students s ON u.student_id = s.student_code
                JOIN allocate_programme ap ON s.student_code = ap.student_code
                JOIN program_table pt ON ap.programme_code = pt.program_code
                JOIN batch_table bt ON ap.batch_id = bt.id
            WHERE 
                u.paid_date <= ?
            
            ORDER BY 
                program_name, batch_name, last_name, first_name, paid_date DESC
        ";

        $stmt = $conn->prepare($allPaymentsSql);
        $stmt->bind_param("ss", $asAtDate, $asAtDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $allPayments[] = $row;
            $totalPaid += $row['amount'];
        }
        $stmt->close();

        // Group payments by program and batch
        $programBatchPayments = [];
        foreach ($allPayments as $payment) {
            $programName = $payment['program_name'];
            $batchName = $payment['batch_name'];

            if (!isset($programBatchPayments[$programName])) {
                $programBatchPayments[$programName] = [];
            }

            if (!isset($programBatchPayments[$programName][$batchName])) {
                $programBatchPayments[$programName][$batchName] = [
                    'students' => [],
                    'totalPaid' => 0
                ];
            }

            $studentId = $payment['student_id'];
            $studentName = $payment['first_name'] . ' ' . $payment['last_name'];
            $studentRegId = $payment['student_registration_id'];

            if (!isset($programBatchPayments[$programName][$batchName]['students'][$studentId])) {
                $programBatchPayments[$programName][$batchName]['students'][$studentId] = [
                    'id' => $studentId,
                    'name' => $studentName,
                    'registration_id' => $studentRegId,
                    'payments' => [],
                    'totalPaid' => 0
                ];
            }

            $programBatchPayments[$programName][$batchName]['students'][$studentId]['payments'][] = $payment;
            $programBatchPayments[$programName][$batchName]['students'][$studentId]['totalPaid'] += $payment['amount'];
            $programBatchPayments[$programName][$batchName]['totalPaid'] += $payment['amount'];
        }
    } elseif ($reportType === 'allBetween') {
        // All Between report type
        $betweenStart = $_POST['betweenStart'];
        $betweenEnd = $_POST['betweenEnd'];

        // Get all payments between the specified dates
        $allPaymentsSql = "
            SELECT 
                p.id as payment_id,
                p.student_id,
                s.first_name,
                s.last_name,
                ap.student_registration_id,
                pt.program_name,
                bt.batch_name,
                p.installmentNumber as payment_for,
                p.paymentAmount as amount,
                p.paid_date,
                p.payment_type,
                p.rcpt_number,
                p.status, 
                p.cancellation_reason, 
                'Course Fee' as payment_category
            FROM 
                payment_wise_info p
                JOIN students s ON p.student_id = s.student_code
                JOIN allocate_programme ap ON s.student_code = ap.student_code
                JOIN program_table pt ON ap.programme_code = pt.program_code
                JOIN batch_table bt ON ap.batch_id = bt.id
            WHERE 
                p.paid_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                u.id as payment_id,
                u.student_id,
                s.first_name,
                s.last_name,
                ap.student_registration_id,
                pt.program_name,
                bt.batch_name,
                CONCAT('University Fee - ', u.currency_type) as payment_for,
                u.LKR_money as amount,
                u.paid_date,
                u.payment_type,
                u.rcpt_number,
                NULL as status,  
                NULL as cancellation_reason,  
                'University Fee' as payment_category
            FROM 
                payment_uni_fee u
                JOIN students s ON u.student_id = s.student_code
                JOIN allocate_programme ap ON s.student_code = ap.student_code
                JOIN program_table pt ON ap.programme_code = pt.program_code
                JOIN batch_table bt ON ap.batch_id = bt.id
            WHERE 
                u.paid_date BETWEEN ? AND ?
            
            ORDER BY 
                program_name, batch_name, last_name, first_name, paid_date DESC
        ";

        $stmt = $conn->prepare($allPaymentsSql);
        $stmt->bind_param("ssss", $betweenStart, $betweenEnd, $betweenStart, $betweenEnd);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $allPayments[] = $row;
            $totalPaid += $row['amount'];
        }
        $stmt->close();

        // Group payments by program and batch
        $programBatchPayments = [];
        foreach ($allPayments as $payment) {
            $programName = $payment['program_name'];
            $batchName = $payment['batch_name'];

            if (!isset($programBatchPayments[$programName])) {
                $programBatchPayments[$programName] = [];
            }

            if (!isset($programBatchPayments[$programName][$batchName])) {
                $programBatchPayments[$programName][$batchName] = [
                    'students' => [],
                    'totalPaid' => 0
                ];
            }

            $studentId = $payment['student_id'];
            $studentName = $payment['first_name'] . ' ' . $payment['last_name'];
            $studentRegId = $payment['student_registration_id'];

            if (!isset($programBatchPayments[$programName][$batchName]['students'][$studentId])) {
                $programBatchPayments[$programName][$batchName]['students'][$studentId] = [
                    'id' => $studentId,
                    'name' => $studentName,
                    'registration_id' => $studentRegId,
                    'payments' => [],
                    'totalPaid' => 0
                ];
            }

            $programBatchPayments[$programName][$batchName]['students'][$studentId]['payments'][] = $payment;
            $programBatchPayments[$programName][$batchName]['students'][$studentId]['totalPaid'] += $payment['amount'];
            $programBatchPayments[$programName][$batchName]['totalPaid'] += $payment['amount'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payment Report</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- SheetJS (xlsx) for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: "Open Sans", sans-serif;
            line-height: 20px;
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .summary-card {
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .badge-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .badge-secondary {
            background-color: #e2e3e5;
            color: #41464b;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            background-color: transparent;
        }

        .table th {
            font-weight: 600;
            color: #495057;
        }

        .payment-status {
            font-weight: 600;
        }

        .btn-icon {
            padding: 0.25rem 0.5rem;
        }

        .student-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .student-badge {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }

        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .report-filter h2 {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .filter-row {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .filter-label {
            width: 120px;
            display: flex;
            align-items: center;
        }

        .filter-input {
            flex: 1;
        }

        .submit-btn {
            margin-top: 1rem;
            margin-left: 120px;
        }

        /* Select2 custom styling */
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #212529;
            line-height: 1.5;
            padding-left: 0;
            padding-right: 20px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 5px;
        }

        .payment-history-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .payment-history-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #495057;
        }

        .uni-payment-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .currency-badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .gbp-badge {
            background-color: #dcfce7;
            color: #166534;
        }

        .usd-badge {
            background-color: #f0fdf4;
            color: #15803d;
        }

        .lkr-badge {
            background-color: #ecfdf5;
            color: #047857;
        }

        .student-accordion .accordion-button:not(.collapsed) {
            background-color: #e7f5ff;
            color: #0d6efd;
        }

        .student-accordion .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(0, 0, 0, .125);
        }

        .student-accordion .accordion-item {
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
        }

        .batch-header {
            background-color: #f0f7ff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 5px solid #0d6efd;
        }

        .batch-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 0;
        }

        .batch-count {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .program-header {
            background-color: #e9ecef;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 5px solid #495057;
        }

        .program-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0;
        }

        .program-count {
            font-size: 1rem;
            color: #6c757d;
        }

        .date-range-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .btn-email-print {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }

            .accordion-button::after {
                display: none;
            }

            .accordion-collapse {
                display: block !important;
            }
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
                <div class="container-fluid py-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h2 class="text-secondary">Student Payment Report</h2>
                        </div>
                        <div class="col-md-6 text-end no-print">
                            <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-1"></i> Export
                            </button>
                        </div>
                    </div>

                    <!-- Report Filter Form -->
                    <div class="card shadow-sm mb-4 no-print">
                        <div class="card-body bg-light">
                            <form id="reportForm" method="POST" class="p-4 bg-white rounded shadow-sm">
                                <h5 class="mb-4 text-primary fw-semibold">
                                    <i class="fas fa-filter me-2"></i>Filter Report
                                </h5>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="reportType" id="asAt" value="asAt" <?= ($reportType === 'asAt') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="asAt">All AS At</label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="date" class="form-control" id="asAtDate" name="asAtDate" value="<?= $asAtDate ?>" <?= ($reportType !== 'asAt') ? 'disabled' : '' ?>>
                                    </div>
                                </div>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="reportType" id="allBetween" value="allBetween" <?= ($reportType === 'allBetween') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="allBetween">All Between</label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="betweenStart" name="betweenStart" value="<?= $betweenStart ?>" <?= ($reportType !== 'allBetween') ? 'disabled' : '' ?>>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="betweenEnd" name="betweenEnd" value="<?= $betweenEnd ?>" <?= ($reportType !== 'allBetween') ? 'disabled' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="reportType" id="student" value="student" <?= ($reportType === 'student') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="student">Student</label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <select class="form-select select2" id="studentSelect" name="studentSelect" <?= ($reportType !== 'student') ? 'disabled' : '' ?>>
                                            <option value="">Select Student</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?= htmlspecialchars($student['student_code']) ?>" <?= ($selectedStudent == $student['student_code']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_registration_id'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 align-items-center mb-4">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="reportType" id="programme" value="programme" <?= ($reportType === 'programme') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="programme">Programme</label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <select class="form-select select2" id="programmeSelect" name="programmeSelect" <?= ($reportType !== 'programme') ? 'disabled' : '' ?>>
                                                    <option value="">Select Programme</option>
                                                    <?php foreach ($programmes as $prog): ?>
                                                        <option value="<?= htmlspecialchars($prog['program_code']) ?>" <?= ($selectedProgramme == $prog['program_code']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($prog['program_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <select class="form-select select2" id="batchSelect" name="batchSelect" <?= ($reportType !== 'programme' || !$selectedProgramme) ? 'disabled' : '' ?>>
                                                    <option value="">All Batches</option>
                                                    <?php if ($selectedProgramme && isset($batches[$selectedProgramme])): ?>
                                                        <?php foreach ($batches[$selectedProgramme] as $batch): ?>
                                                            <option value="<?= htmlspecialchars($batch['id']) ?>" <?= ($selectedBatch == $batch['id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($batch['batch_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4 py-2">
                                        <i class="fas fa-search me-2"></i>Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($selectedStudent && $studentDetails): ?>
                        <!-- Individual Student Report -->
                        <!-- Student Info Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <div class="student-info">
                                    <h5 class="mb-0 text-primary">
                                        Payment Details for <?= htmlspecialchars($studentDetails['first_name'] . ' ' . $studentDetails['last_name']) ?>
                                    </h5>
                                    <span class="badge bg-light text-dark student-badge">
                                        <?= htmlspecialchars($studentDetails['student_registration_id']) ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-0 mt-2">
                                    Program: <?= htmlspecialchars($programBatch) ?>
                                    <?php if (!empty($installmentPayments)): ?>
                                        | Payment Type: <?= ucfirst($installmentPayments[0]['fee_type'] ?? 'N/A') ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- University Fee Section -->
                        <?php if (!empty($universityFees)): ?>
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0 text-primary">
                                            <i class="fas fa-university me-2"></i> University Fee Payments
                                        </h5>
                                        <span class="badge uni-payment-badge">
                                            Total: Rs. <?= number_format($totalUniFees, 2) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="universityFeesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Receipt #</th>
                                                    <th>Currency</th>
                                                    <th>Amount</th>
                                                    <th>Exchange Rate</th>
                                                    <th>LKR Equivalent</th>
                                                    <th>Payment Date</th>
                                                    <th>Payment Type</th>
                                                    <th class="no-print">Actions</th>
                                                    <th>System Date</th>
                                                    <th>By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($universityFees as $fee): ?>
                                                    <tr>
                                                        <td>
                                                            <?= htmlspecialchars($fee['rcpt_number']) ?>
                                                        </td>
                                                        <td>
                                                            <span class="currency-badge 
                                                    <?= strtolower($fee['currency_type']) === 'gbp' ? 'gbp-badge' : (strtolower($fee['currency_type']) === 'usd' ? 'usd-badge' : 'lkr-badge') ?>">
                                                                <?= htmlspecialchars($fee['currency_type']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= number_format($fee['paid_amount'], 2) ?></td>
                                                        <td><?= number_format($fee['exchange_rate'], 2) ?></td>
                                                        <td class="fw-bold text-success">Rs. <?= number_format($fee['LKR_money'], 2) ?></td>

                                                        <td>
                                                            <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                            <?= htmlspecialchars($fee['paid_date']) ?>
                                                        </td>
                                                        <td class="text-capitalize">
                                                            <span class="badge bg-light text-dark">
                                                                <?= htmlspecialchars($fee['payment_type']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="no-print">
                                                            <div class="d-flex gap-2">
                                                                <a href="payment-details.php?type=uni&id=<?= $fee['id'] ?>&student=<?= $selectedStudent ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                    <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                        <td> <?= htmlspecialchars($fee['entered_date']) ?></td>
                                                        <td> <?= htmlspecialchars($fee['entered_by']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Payment History Section -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-history me-2"></i> Payment History
                                    </h5>
                                    <span class="badge bg-success">
                                        Total Paid: Rs. <?= number_format($totalPaid, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($paymentInfo)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="paymentHistoryTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Receipt #</th>
                                                    <th>Payment For</th>
                                                    <th>Amount</th>
                                                    <th>Payment Date</th>
                                                    <th>Payment Type</th>
                                                    <th>Payment status</th>
                                                    <th>Reason</th>
                                                    <th class="no-print">Actions</th>
                                                    <th>System date</th>
                                                    <th>By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paymentInfo as $payment): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($payment['rcpt_number'] ?? '') ?></td>
                                                        <td class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($payment['installmentNumber'] ?? '')) ?></td>
                                                        <td class="fw-bold">Rs. <?= number_format($payment['paymentAmount'] ?? 0, 2) ?></td>
                                                        <td>
                                                            <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                            <?= htmlspecialchars($payment['paid_date'] ?? '') ?>
                                                        </td>
                                                        <td class="text-capitalize">
                                                            <span class="badge bg-light text-dark">
                                                                <?= htmlspecialchars($payment['payment_type'] ?? '') ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-capitalize">
                                                            <span class="badge <?= $payment['status'] == 'cancelled' ? 'bg-danger' : 'bg-success' ?> text-dark">
                                                                <?= htmlspecialchars($payment['status'] ?? '') ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-capitalize">
                                                            <?= htmlspecialchars($payment['cancellation_reason'] ?? '') ?>
                                                        </td>
                                                        <td class="no-print">
                                                            <div class="d-flex gap-2">
                                                                <a href="payment-details.php?type=course&id=<?= $payment['id'] ?? '' ?>&student=<?= $selectedStudent ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                    <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                        <td> <?= htmlspecialchars($payment['entered_date'] ?? '') ?></td>
                                                        <td> <?= htmlspecialchars($payment['entered_by'] ?? '') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No payment records found for this student.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($selectedProgramme && $programmeDetails): ?>
                        <!-- Programme Report -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <?= htmlspecialchars($programmeDetails['program_name']) ?>
                                        <?php if ($batchDetails): ?>
                                            <span class="badge bg-info ms-2">
                                                <?= htmlspecialchars($batchDetails['batch_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <span class="badge bg-primary">
                                        <?= count($programmeStudents, COUNT_RECURSIVE) - count($programmeStudents) ?> Students
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Students grouped by batch -->
                                <?php if (!empty($programmeStudents)): ?>
                                    <?php foreach ($programmeStudents as $batchName => $batchStudents): ?>
                                        <div class="batch-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="batch-title">
                                                    <?= htmlspecialchars($programmeDetails['program_name']) ?> - <?= htmlspecialchars($batchName) ?>
                                                </h6>
                                                <span class="batch-count">
                                                    <?= count($batchStudents) ?> Students
                                                </span>
                                            </div>
                                        </div>

                                        <div class="accordion student-accordion mb-4" id="accordion<?= str_replace(' ', '', $batchName) ?>">
                                            <?php foreach ($batchStudents as $index => $student): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading<?= $index . str_replace(' ', '', $batchName) ?>">
                                                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index . str_replace(' ', '', $batchName) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index . str_replace(' ', '', $batchName) ?>">
                                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                                <div>
                                                                    <span class="fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span>
                                                                    <span class="ms-2 text-muted">(<?= htmlspecialchars($student['student_registration_id']) ?>)</span>
                                                                </div>
                                                                <div>
                                                                    <span class="badge bg-success me-2">Paid: Rs. <?= number_format($student['totalPaid'], 2) ?></span>
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </h2>
                                                    <div id="collapse<?= $index . str_replace(' ', '', $batchName) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index . str_replace(' ', '', $batchName) ?>" data-bs-parent="#accordion<?= str_replace(' ', '', $batchName) ?>">
                                                        <div class="accordion-body">
                                                            <!-- University Fee Section -->
                                                            <?php if (!empty($student['universityFees'])): ?>
                                                                <div class="card mb-3">
                                                                    <div class="card-header bg-light">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-0">
                                                                                <i class="fas fa-university me-2"></i> University Fee Payments
                                                                            </h6>
                                                                            <span class="badge uni-payment-badge">
                                                                                Total: Rs. <?= number_format($student['totalUniFees'], 2) ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-sm table-hover programme-uni-fees-table">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>Recipt #</th>
                                                                                        <th>Currency</th>
                                                                                        <th>Amount</th>
                                                                                        <th>Exchange Rate</th>
                                                                                        <th>LKR Equivalent</th>
                                                                                        <th>Payment Date</th>
                                                                                        <th>Payment Type</th>
                                                                                        <th class="no-print">Actions</th>
                                                                                        <th>System date</th>
                                                                                        <th>By</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($student['universityFees'] as $fee): ?>
                                                                                        <tr>
                                                                                            <td> <?= htmlspecialchars($fee['rcpt_number']) ?></td>
                                                                                            <td>
                                                                                                <span class="currency-badge 
                                                                                    <?= strtolower($fee['currency_type']) === 'gbp' ? 'gbp-badge' : (strtolower($fee['currency_type']) === 'usd' ? 'usd-badge' : 'lkr-badge') ?>">
                                                                                                    <?= htmlspecialchars($fee['currency_type']) ?>
                                                                                                </span>
                                                                                            </td>
                                                                                            <td><?= number_format($fee['paid_amount'], 2) ?></td>
                                                                                            <td><?= number_format($fee['exchange_rate'], 2) ?></td>
                                                                                            <td class="fw-bold text-success">Rs. <?= number_format($fee['LKR_money'], 2) ?></td>
                                                                                            <td>
                                                                                                <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                                                                <?= htmlspecialchars($fee['paid_date']) ?>
                                                                                            </td>
                                                                                            <td class="text-capitalize">
                                                                                                <span class="badge bg-light text-dark">
                                                                                                    <?= htmlspecialchars($fee['payment_type']) ?>
                                                                                                </span>
                                                                                            </td>
                                                                                            <td class="no-print">
                                                                                                <div class="d-flex gap-2">
                                                                                                    <a href="payment-details.php?type=uni&id=<?= $fee['id'] ?? '' ?>&student=<?= $student['student_code'] ?? '' ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                                                        <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                                                    </a>
                                                                                                </div>
                                                                                            </td>
                                                                                            <td> <?= htmlspecialchars($fee['entered_date']) ?></td>
                                                                                            <td> <?= htmlspecialchars($fee['entered_by']) ?></td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Payment History Section -->
                                                            <div class="card">
                                                                <div class="card-header bg-light">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <h6 class="mb-0">
                                                                            <i class="fas fa-history me-2"></i> Payment History
                                                                        </h6>
                                                                        <span class="badge bg-success">
                                                                            Total Paid: Rs. <?= number_format($student['totalPaid'], 2) ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <?php if (!empty($student['payments'])): ?>
                                                                        <div class="table-responsive">
                                                                            <table class="table table-sm table-hover programme-payments-table">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>Receipt #</th>
                                                                                        <th>Payment For</th>
                                                                                        <th>Amount</th>
                                                                                        <th>Payment Date</th>
                                                                                        <th>Payment Type</th>
                                                                                        <th class="no-print">Actions</th>
                                                                                        <th>Payment status </th>
                                                                                        <th>Reason</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($student['payments'] as $payment): ?>
                                                                                        <tr>
                                                                                            <td><?= htmlspecialchars($payment['rcpt_number'] ?? '') ?></td>
                                                                                            <td class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($payment['installmentNumber'] ?? '')) ?></td>
                                                                                            <td class="fw-bold">Rs. <?= number_format($payment['paymentAmount'] ?? 0, 2) ?></td>
                                                                                            <td>
                                                                                                <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                                                                <?= htmlspecialchars($payment['paid_date'] ?? '') ?>
                                                                                            </td>
                                                                                            <td class="text-capitalize">
                                                                                                <span class="badge bg-light text-dark">
                                                                                                    <?= htmlspecialchars($payment['payment_type'] ?? '') ?>
                                                                                                </span>
                                                                                            </td>
                                                                                            <td class="no-print">
                                                                                                <div class="d-flex gap-2">
                                                                                                    <a href="payment-details.php?type=course&id=<?= $payment['id'] ?? '' ?>&student=<?= $student['student_code'] ?? '' ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                                                        <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                                                    </a>
                                                                                                </div>
                                                                                            </td>
                                                                                            <td class="text-capitalize">
                                                                                                <span class="badge <?= $payment['status'] == 'cancelled' ? 'bg-danger' : 'bg-success' ?> text-dark">
                                                                                                    <?= htmlspecialchars($payment['status'] ?? '') ?>
                                                                                                </span>
                                                                                            </td>



                                                                                            <td> <?= htmlspecialchars($payment['cancellation_reason'] ?? '') ?> </td>

                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="alert alert-info">
                                                                            <i class="fas fa-info-circle me-2"></i> No payment records found for this student.
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No students found for this programme<?= $batchDetails ? ' and batch' : '' ?>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($reportType === 'asAt'): ?>
                        <!-- All AS At Report -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-calendar-check me-2"></i> All Payments As At
                                        <span class="date-range-badge">
                                            <?= date('d M Y', strtotime($asAtDate)) ?>
                                        </span>
                                    </h5>
                                    <!-- <span class="badge bg-success">
                                        Total Paid: Rs. <?= number_format($totalPaid, 2) ?>
                                    </span> -->
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($programBatchPayments)): ?>
                                    <?php foreach ($programBatchPayments as $programName => $batches): ?>
                                        <div class="program-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="program-title">
                                                    <?= htmlspecialchars($programName) ?>
                                                </h6>
                                                <span class="program-count">
                                                    <?= count($batches) ?> Batches
                                                </span>
                                            </div>
                                        </div>

                                        <?php foreach ($batches as $batchName => $batchData): ?>
                                            <div class="batch-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="batch-title">
                                                        <?= htmlspecialchars($programName) ?> - <?= htmlspecialchars($batchName) ?>
                                                    </h6>
                                                    <div>
                                                        <span class="batch-count me-3">
                                                            <?= count($batchData['students']) ?> Students
                                                        </span>
                                                        <span class="badge bg-success">
                                                            Total: Rs. <?= number_format($batchData['totalPaid'], 2) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion student-accordion mb-4" id="accordionAsAt<?= str_replace(' ', '', $batchName) ?>">
                                                <?php $index = 0; ?>
                                                <?php foreach ($batchData['students'] as $student): ?>
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingAsAt<?= $index . str_replace(' ', '', $batchName) ?>">
                                                            <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAsAt<?= $index . str_replace(' ', '', $batchName) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapseAsAt<?= $index . str_replace(' ', '', $batchName) ?>">
                                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                                    <div>
                                                                        <span class="fw-bold"><?= htmlspecialchars($student['name']) ?></span>
                                                                        <span class="ms-2 text-muted">(<?= htmlspecialchars($student['registration_id']) ?>)</span>
                                                                    </div>
                                                                    <div>
                                                                        <span class="badge bg-success me-2">Paid: Rs. <?= number_format($student['totalPaid'], 2) ?></span>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseAsAt<?= $index . str_replace(' ', '', $batchName) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="headingAsAt<?= $index . str_replace(' ', '', $batchName) ?>" data-bs-parent="#accordionAsAt<?= str_replace(' ', '', $batchName) ?>">
                                                            <div class="accordion-body">
                                                                <!-- Payment History Section -->
                                                                <div class="card">
                                                                    <div class="card-header bg-light">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-0">
                                                                                <i class="fas fa-history me-2"></i> Payment History
                                                                            </h6>
                                                                            <span class="badge bg-success">
                                                                                Total Paid: Rs. <?= number_format($student['totalPaid'], 2) ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <?php if (!empty($student['payments'])): ?>
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm table-hover asat-payments-table">
                                                                                    <thead class="table-light">
                                                                                        <tr>
                                                                                            <th>Receipt #</th>
                                                                                            <th>Payment For</th>
                                                                                            <th>Amount</th>
                                                                                            <th>Payment Date</th>
                                                                                            <th>Payment Type</th>
                                                                                            <th>Category</th>
                                                                                            <th>payment status</th>
                                                                                            <th>Reason</th>
                                                                                            <th class="no-print">Actions</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($student['payments'] as $payment): ?>
                                                                                            <tr>
                                                                                                <td><?= htmlspecialchars($payment['rcpt_number'] ?? '') ?></td>
                                                                                                <td class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($payment['payment_for'] ?? '')) ?></td>
                                                                                                <td class="fw-bold">Rs. <?= number_format($payment['amount'] ?? 0, 2) ?></td>
                                                                                                <td>
                                                                                                    <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                                                                    <?= htmlspecialchars($payment['paid_date'] ?? '') ?>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                    <span class="badge bg-light text-dark">
                                                                                                        <?= htmlspecialchars($payment['payment_type'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <span class="badge <?= ($payment['payment_category'] ?? '') === 'University Fee' ? 'uni-payment-badge' : 'bg-info text-white' ?>">
                                                                                                        <?= htmlspecialchars($payment['payment_category'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                    <span class="badge <?= $payment['status'] == 'cancelled' ? 'bg-danger' : 'bg-success' ?>">
                                                                                                        <?= htmlspecialchars($payment['status'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                        <?= htmlspecialchars($payment['cancellation_reason'] ?? '') ?>
                                                                                                </td>
                                                                                                <td class="no-print">
                                                                                                    <div class="d-flex gap-2">
                                                                                                        <a href="payment-details.php?type=<?= ($payment['payment_category'] ?? '') === 'University Fee' ? 'uni' : 'course' ?>&id=<?= $payment['payment_id'] ?? '' ?>&student=<?= $student['id'] ?? '' ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                                                            <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                                                        </a>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="alert alert-info">
                                                                                <i class="fas fa-info-circle me-2"></i> No payment records found for this student.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php $index++; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No payments found up to <?= date('d M Y', strtotime($asAtDate)) ?>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($reportType === 'allBetween'): ?>
                        <!-- All Between Report -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-calendar-alt me-2"></i> All Payments Between
                                        <span class="date-range-badge">
                                            <?= date('d M Y', strtotime($betweenStart)) ?> - <?= date('d M Y', strtotime($betweenEnd)) ?>
                                        </span>
                                    </h5>
                                    <span class="badge bg-success">
                                        Total Paid: Rs. <?= number_format($totalPaid, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($programBatchPayments)): ?>
                                    <?php foreach ($programBatchPayments as $programName => $batches): ?>
                                        <div class="program-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="program-title">
                                                    <?= htmlspecialchars($programName) ?>
                                                </h6>
                                                <span class="program-count">
                                                    <?= count($batches) ?> Batches
                                                </span>
                                            </div>
                                        </div>

                                        <?php foreach ($batches as $batchName => $batchData): ?>
                                            <div class="batch-header">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="batch-title">
                                                        <?= htmlspecialchars($programName) ?> - <?= htmlspecialchars($batchName) ?>
                                                    </h6>
                                                    <div>
                                                        <span class="batch-count me-3">
                                                            <?= count($batchData['students']) ?> Students
                                                        </span>
                                                        <span class="badge bg-success">
                                                            Total: Rs. <?= number_format($batchData['totalPaid'], 2) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="accordion student-accordion mb-4" id="accordionBetween<?= str_replace(' ', '', $batchName) ?>">
                                                <?php $index = 0; ?>
                                                <?php foreach ($batchData['students'] as $student): ?>
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingBetween<?= $index . str_replace(' ', '', $batchName) ?>">
                                                            <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBetween<?= $index . str_replace(' ', '', $batchName) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapseBetween<?= $index . str_replace(' ', '', $batchName) ?>">
                                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                                    <div>
                                                                        <span class="fw-bold"><?= htmlspecialchars($student['name']) ?></span>
                                                                        <span class="ms-2 text-muted">(<?= htmlspecialchars($student['registration_id']) ?>)</span>
                                                                    </div>
                                                                    <div>
                                                                        <span class="badge bg-success me-2">Paid: Rs. <?= number_format($student['totalPaid'], 2) ?></span>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseBetween<?= $index . str_replace(' ', '', $batchName) ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="headingBetween<?= $index . str_replace(' ', '', $batchName) ?>" data-bs-parent="#accordionBetween<?= str_replace(' ', '', $batchName) ?>">
                                                            <div class="accordion-body">
                                                                <!-- Payment History Section -->
                                                                <div class="card">
                                                                    <div class="card-header bg-light">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <h6 class="mb-0">
                                                                                <i class="fas fa-history me-2"></i> Payment History sss
                                                                            </h6>
                                                                            <span class="badge bg-success">
                                                                                Total Paid: Rs. <?= number_format($student['totalPaid'], 2) ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <?php if (!empty($student['payments'])): ?>
                                                                            <div class="table-responsive">
                                                                                <table class="table table-sm table-hover between-payments-table">
                                                                                    <thead class="table-light">
                                                                                        <tr>
                                                                                            <th>Receipts #</th>
                                                                                            <th>Payment For</th>
                                                                                            <th>Amount</th>
                                                                                            <th>Payment Date</th>
                                                                                            <th>Payment Type</th>
                                                                                            <th>Category</th>
                                                                                            <th>Payment Status</th>
                                                                                            <th>Reason</th>
                                                                                            <th class="no-print">Actions</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($student['payments'] as $payment): ?>
                                                                                            <tr>
                                                                                                <td><?= htmlspecialchars($payment['rcpt_number'] ?? '') ?></td>
                                                                                                <td class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($payment['payment_for'] ?? '')) ?></td>
                                                                                                <td class="fw-bold">Rs. <?= number_format($payment['amount'] ?? 0, 2) ?></td>
                                                                                                <td>
                                                                                                    <i class="far fa-calendar-alt me-1 text-muted"></i>
                                                                                                    <?= htmlspecialchars($payment['paid_date'] ?? '') ?>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                    <span class="badge bg-light text-dark">
                                                                                                        <?= htmlspecialchars($payment['payment_type'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <span class="badge <?= ($payment['payment_category'] ?? '') === 'University Fee' ? 'uni-payment-badge' : 'bg-info text-white' ?>">
                                                                                                        <?= htmlspecialchars($payment['payment_category'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                    <span class="badge <?= $payment['status'] == 'cancelled' ? 'bg-danger' : 'bg-success' ?>">
                                                                                                        <?= htmlspecialchars($payment['status'] ?? '') ?>
                                                                                                    </span>
                                                                                                </td>
                                                                                                <td class="text-capitalize">
                                                                                                    <?= htmlspecialchars($payment['cancellation_reason'] ?? '') ?>
                                                                                                </td>
                                                                                                <td class="no-print">
                                                                                                    <div class="d-flex gap-2">
                                                                                                        <a href="payment-details.php?type=<?= ($payment['payment_category'] ?? '') === 'University Fee' ? 'uni' : 'course' ?>&id=<?= $payment['payment_id'] ?? '' ?>&student=<?= $student['id'] ?? '' ?>" target="_blank" class="btn btn-sm btn-primary btn-email-print">
                                                                                                            <i class="fas fa-envelope"></i>/<i class="fas fa-print"></i>
                                                                                                        </a>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="alert alert-info">
                                                                                <i class="fas fa-info-circle me-2"></i> No payment records found for this student.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php $index++; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No payments found between <?= date('d M Y', strtotime($betweenStart)) ?> and <?= date('d M Y', strtotime($betweenEnd)) ?>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                width: '100%',
                allowClear: true,
                minimumResultsForSearch: 10
            });

            // Function to update form fields based on selection
            function updateFields() {
                // Disable all fields first
                $('#asAtDate, #betweenStart, #betweenEnd, #studentSelect, #programmeSelect, #batchSelect').prop('disabled', true);

                // Enable relevant fields based on selection
                if ($('#asAt').is(':checked')) {
                    $('#asAtDate').prop('disabled', false);
                } else if ($('#allBetween').is(':checked')) {
                    $('#betweenStart, #betweenEnd').prop('disabled', false);
                } else if ($('#student').is(':checked')) {
                    $('#studentSelect').prop('disabled', false);
                } else if ($('#programme').is(':checked')) {
                    $('#programmeSelect').prop('disabled', false);
                    if ($('#programmeSelect').val()) {
                        $('#batchSelect').prop('disabled', false);
                    }
                }

                // Refresh Select2 to update disabled state appearance
                $('.select2').select2({
                    width: '100%',
                    allowClear: true,
                    minimumResultsForSearch: 10
                });
            }

            // Handle radio button changes
            $('input[name="reportType"]').change(updateFields);

            // Handle programme select change to load batches
            $('#programmeSelect').change(function() {
                const programCode = $(this).val();

                // Clear and disable batch select first
                $('#batchSelect').empty().append('<option value="">All Batches</option>').prop('disabled', true);

                if (programCode && $('#programme').is(':checked')) {
                    // Enable batch select
                    $('#batchSelect').prop('disabled', false);

                    // Load batches via AJAX
                    $.ajax({
                        url: 'get-batches-for-payment.php',
                        type: 'POST',
                        data: {
                            program_code: programCode
                        },
                        dataType: 'json',
                        success: function(batches) {
                            if (batches.length > 0) {
                                batches.forEach(function(batch) {
                                    $('#batchSelect').append(
                                        $('<option></option>').val(batch.id).text(batch.batch_name)
                                    );
                                });
                            }
                            // Refresh Select2
                            $('#batchSelect').trigger('change');
                        },
                        error: function() {
                            console.error('Failed to load batches');
                        }
                    });
                }

                // Refresh Select2
                $('.select2').select2({
                    width: '100%',
                    allowClear: true,
                    minimumResultsForSearch: 10
                });
            });

            // Initial call to set up form
            updateFields();

            // Additional fix to ensure scrollbar is hidden after dropdown is opened
            $(document).on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-results__options').css({
                        'scrollbar-width': 'none',
                        '-ms-overflow-style': 'none',
                        'overflow-y': 'none'
                    });
                    $('.select2-results__options::-webkit-scrollbar').css({
                        'width': '0',
                        'display': 'none'
                    });
                }, 0);
            });
        });



        function exportToExcel() {
            // Determine which report type is active
            const reportType = $('input[name="reportType"]:checked').val();
            let fileName = 'Payment_Report';
            let workbook = XLSX.utils.book_new();

            // Prepare data based on report type
            if (reportType === 'student') {
                const selectedText = $('#studentSelect option:selected').text();
                const studentName = selectedText.substring(0, selectedText.lastIndexOf('(')).trim();
                const studentId = selectedText.substring(selectedText.lastIndexOf('(') + 1, selectedText.lastIndexOf(')')).trim();
                fileName = `Student_Payment_Report_${studentName}`;

                // Create a single worksheet for all student data
                const allPaymentData = [];

                // Add student information header
                allPaymentData.push({
                    'Student Name': studentName,
                    'Student ID': studentId,
                    'Program': $('.student-info').next('p').text().replace('Program:', '').split('|')[0].trim()
                });

                // Add a blank row for separation
                allPaymentData.push({});

                // Add university fees data
                if ($('#universityFeesTable').length) {
                    // Add a section header
                    allPaymentData.push({
                        'Student Name': 'UNIVERSITY FEE PAYMENTS',
                        'Student ID': ''
                    });

                    // Add a blank row
                    allPaymentData.push({});

                    $('#universityFeesTable tbody tr').each(function() {
                        const cells = $(this).find('td');
                        allPaymentData.push({
                            'Student Name': studentName,
                            'Student ID': studentId,
                            'Payment Type': 'University Fee',
                            'Currency': $(cells[0]).text().trim(),
                            'Amount': $(cells[1]).text().trim(),
                            'Exchange Rate': $(cells[2]).text().trim(),
                            'LKR Equivalent': $(cells[3]).text().trim().replace('Rs. ', ''),
                            'Payment Date': $(cells[4]).text().trim(),
                            'Method': $(cells[5]).text().trim()
                        });
                    });

                    // Add a blank row after university fees
                    allPaymentData.push({});
                }

                // Add payment history data
                if ($('#paymentHistoryTable').length) {
                    // Add a section header
                    allPaymentData.push({
                        'Student Name': 'COURSE FEE PAYMENTS',
                        'Student ID': ''
                    });

                    // Add a blank row
                    allPaymentData.push({});

                    $('#paymentHistoryTable tbody tr').each(function() {
                        const cells = $(this).find('td');
                        allPaymentData.push({
                            'Student Name': studentName,
                            'Student ID': studentId,
                            'Payment Type': 'Course Fee',
                            'Receipt #': $(cells[0]).text().trim(),
                            'Payment For': $(cells[1]).text().trim(),
                            'Amount': $(cells[2]).text().trim().replace('Rs. ', ''),
                            'Payment Date': $(cells[3]).text().trim(),
                            'Method': $(cells[4]).text().trim()
                        });
                    });
                }

                // Create a single worksheet with all data
                if (allPaymentData.length > 0) {
                    const allPaymentsWs = XLSX.utils.json_to_sheet(allPaymentData);
                    XLSX.utils.book_append_sheet(workbook, allPaymentsWs, 'Student Payments');
                }

            } else if (reportType === 'programme') {
                const programmeName = $('#programmeSelect option:selected').text().trim();
                const batchName = $('#batchSelect option:selected').text().trim();
                fileName = `Programme_Payment_Report_${programmeName}${batchName !== 'All Batches' ? '_' + batchName : ''}`;

                // Create summary worksheet with program details
                const summaryData = [{
                    'Programme': programmeName,
                    'Batch': batchName !== 'All Batches' ? batchName : 'All Batches',
                    'Report Date': new Date().toLocaleDateString()
                }];

                const summaryWs = XLSX.utils.json_to_sheet(summaryData);
                XLSX.utils.book_append_sheet(workbook, summaryWs, 'Summary');

                // Create a worksheet for each batch
                $('.batch-header').each(function(batchIndex) {
                    const currentBatchName = $(this).find('.batch-title').text().trim();

                    // Student data for this batch
                    const studentData = [];

                    // Add a blank row for better readability
                    studentData.push({
                        'Student Name': '',
                        'Student ID': ''
                    });

                    // Process each student in this batch
                    $(this).next('.student-accordion').find('.accordion-item').each(function(studentIndex) {
                        const studentName = $(this).find('.accordion-button .fw-bold').text().trim();
                        const studentId = $(this).find('.accordion-button .text-muted').text().replace(/[()]/g, '').trim();

                        // Add student header
                        studentData.push({
                            'Student Name': studentName,
                            'Student ID': studentId,
                            'Registration ID': studentId
                        });

                        // Add university fees if available
                        const uniFees = [];
                        $(this).find('.programme-uni-fees-table tbody tr').each(function() {
                            const cells = $(this).find('td');
                            uniFees.push({
                                'Type': 'University Fee',
                                'Currency': $(cells[0]).text().trim(),
                                'Amount': $(cells[1]).text().trim(),
                                'Exchange Rate': $(cells[2]).text().trim(),
                                'LKR Equivalent': $(cells[3]).text().trim().replace('Rs. ', ''),
                                'Payment Date': $(cells[4]).text().trim(),
                                'Payment Type': $(cells[5]).text().trim()
                            });
                        });

                        // Add course payments
                        const payments = [];
                        $(this).find('.programme-payments-table tbody tr').each(function() {
                            const cells = $(this).find('td');
                            payments.push({
                                'Type': 'Course Fee',
                                'Receipt #': $(cells[0]).text().trim(),
                                'Payment For': $(cells[1]).text().trim(),
                                'Amount': $(cells[2]).text().trim().replace('Rs. ', ''),
                                'Payment Date': $(cells[3]).text().trim(),
                                'Payment Type': $(cells[4]).text().trim()
                            });
                        });

                        // Combine all payments for this student
                        const allPayments = [...uniFees, ...payments];

                        // Add all payments to student data with student info
                        allPayments.forEach(payment => {
                            studentData.push({
                                'Student Name': studentName,
                                'Student ID': studentId,
                                'Payment Type': payment.Type,
                                'Receipt #': payment['Receipt #'] || 'N/A',
                                'Payment For': payment['Payment For'] || payment.Currency || 'N/A',
                                'Amount': payment['Amount'] || payment['LKR Equivalent'] || 'N/A',
                                'Payment Date': payment['Payment Date'],
                                'Method': payment['Payment Type']
                            });
                        });

                        // Add a blank row after each student for better readability
                        studentData.push({
                            'Student Name': '',
                            'Student ID': '',
                            'Payment Type': '',
                            'Receipt #': '',
                            'Payment For': '',
                            'Amount': '',
                            'Payment Date': '',
                            'Method': ''
                        });
                    });

                    // Create worksheet for this batch
                    if (studentData.length > 0) {
                        const batchWs = XLSX.utils.json_to_sheet(studentData);
                        const sheetName = `Batch_${batchIndex + 1}_${currentBatchName.split('-').pop().trim()}`.substring(0, 31);
                        XLSX.utils.book_append_sheet(workbook, batchWs, sheetName);
                    }
                });

            } else if (reportType === 'asAt' || reportType === 'allBetween') {
                // Set filename based on report type
                if (reportType === 'asAt') {
                    const asAtDate = $('#asAtDate').val();
                    fileName = `Payment_Report_AsAt_${asAtDate}`;
                } else {
                    const startDate = $('#betweenStart').val();
                    const endDate = $('#betweenEnd').val();
                    fileName = `Payment_Report_Between_${startDate}_and_${endDate}`;
                }

                // Create summary worksheet
                const reportTitle = reportType === 'asAt' ?
                    `All Payments As At ${$('.date-range-badge').text().trim()}` :
                    `All Payments Between ${$('.date-range-badge').text().trim()}`;

                const summaryData = [{
                    'Report Type': reportTitle,
                    'Report Generated': new Date().toLocaleDateString()
                }];

                const summaryWs = XLSX.utils.json_to_sheet(summaryData);
                XLSX.utils.book_append_sheet(workbook, summaryWs, 'Summary');

                // Process each program
                $('.program-header').each(function(programIndex) {
                    const programName = $(this).find('.program-title').text().trim();

                    // Create a worksheet for each program
                    const programData = [];

                    // Add program summary
                    programData.push({
                        'Program': programName,
                        'Report Type': reportTitle
                    });

                    // Add a blank row
                    programData.push({
                        'Program': '',
                        'Report Type': ''
                    });

                    // Process each batch in this program
                    $(this).nextUntil('.program-header', '.batch-header').each(function(batchIndex) {
                        const batchName = $(this).find('.batch-title').text().trim().split('-').pop().trim();

                        // Add batch summary
                        programData.push({
                            'Batch': batchName
                        });

                        // Process each student in this batch
                        const accordionId = $(this).next('.student-accordion').attr('id');
                        $(`#${accordionId} .accordion-item`).each(function(studentIndex) {
                            const studentName = $(this).find('.accordion-button .fw-bold').text().trim();
                            const studentId = $(this).find('.accordion-button .text-muted').text().replace(/[()]/g, '').trim();

                            // Add student header
                            programData.push({
                                'Student': studentName,
                                'ID': studentId
                            });

                            // Get the table selector based on report type
                            const tableSelector = reportType === 'asAt' ? '.asat-payments-table' : '.between-payments-table';

                            // Add all payments for this student
                            $(this).find(tableSelector + ' tbody tr').each(function() {
                                const cells = $(this).find('td');
                                programData.push({
                                    'Student': studentName,
                                    'ID': studentId,
                                    'Receipt #': $(cells[0]).text().trim(),
                                    'Payment For': $(cells[1]).text().trim(),
                                    'Amount': $(cells[2]).text().trim().replace('Rs. ', ''),
                                    'Payment Date': $(cells[3]).text().trim(),
                                    'Payment Type': $(cells[4]).text().trim(),
                                    'Category': $(cells[5]).text().trim()
                                });
                            });

                            // Add a blank row after each student
                            programData.push({
                                'Student': '',
                                'ID': '',
                                'Receipt #': '',
                                'Payment For': '',
                                'Amount': '',
                                'Payment Date': '',
                                'Payment Type': '',
                                'Category': ''
                            });
                        });

                        // Add a separator between batches
                        programData.push({
                            'Student': '------------------------',
                            'ID': '------------------------',
                            'Receipt #': '------------------------',
                            'Payment For': '------------------------',
                            'Amount': '------------------------',
                            'Payment Date': '------------------------',
                            'Payment Type': '------------------------',
                            'Category': '------------------------'
                        });
                    });

                    // Create worksheet for this program
                    if (programData.length > 0) {
                        const programWs = XLSX.utils.json_to_sheet(programData);
                        const sheetName = `Program_${programName}`.substring(0, 31).replace(/[*?:/\\[\]]/g, '_');
                        XLSX.utils.book_append_sheet(workbook, programWs, sheetName);
                    }
                });
            }

            // If no worksheets added, show alert
            if (workbook.SheetNames.length === 0) {
                alert('No data available to export.');
                return;
            }

            // Apply styling to all worksheets
            workbook.SheetNames.forEach(function(sheetName) {
                const worksheet = workbook.Sheets[sheetName];

                // Set column widths
                const cols = [];
                const range = XLSX.utils.decode_range(worksheet['!ref']);

                // Get max width for each column
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    let maxWidth = 10; // Default width

                    for (let R = range.s.r; R <= range.e.r; ++R) {
                        const cellAddress = XLSX.utils.encode_cell({
                            r: R,
                            c: C
                        });
                        const cell = worksheet[cellAddress];

                        if (cell && cell.v) {
                            const cellText = String(cell.v);
                            const width = cellText.length * 1.2; // Approximate width
                            maxWidth = Math.max(maxWidth, width);
                        }
                    }

                    cols.push({
                        width: Math.min(maxWidth, 50)
                    }); // Cap width at 50
                }

                worksheet['!cols'] = cols;
            });

            // Generate Excel file and trigger download
            XLSX.writeFile(workbook, `${fileName}.xlsx`);

            // Show success message
            alert('Export completed successfully!');
        }
    </script>

</body>

</html>