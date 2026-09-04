<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

// Check if export flag is set
if (!isset($_POST['export']) || $_POST['export'] != 'excel') {
    echo "Invalid request";
    exit;
}

// Get parameters from POST
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : 'all';
$selectedStudent = isset($_POST['student']) ? $_POST['student'] : '';
$selectedProgram = isset($_POST['program']) ? $_POST['program'] : '';
$searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';
$paymentDetails = [];

// Process data for export
// Get all payment plans for students
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
        appt.registration_fee_USD
    FROM allocate_programme ap
    JOIN students s ON ap.student_code = s.student_code
    JOIN program_table p ON ap.programme_code = p.program_code
    JOIN batch_table b ON ap.batch_id = b.id
    LEFT JOIN add_payment_plan_table appt ON s.student_code = appt.student_id 
        AND CONCAT(p.program_name, ' - ', b.batch_name) = appt.programme_batch
    WHERE ap.status = 'active'
";

// Add filters based on report type
if ($reportType == 'student' && !empty($selectedStudent)) {
    $paymentPlansQuery .= " AND s.student_code = $selectedStudent";
} else if ($reportType == 'programme' && !empty($selectedProgram)) {
    $paymentPlansQuery .= " AND p.program_code = $selectedProgram";
}

// If search term is provided, filter students by name or registration ID for any report type
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

        // 1. Check University Fee Payments
        // Get university fee amounts from payment plan
        $uniFeeLKR = !empty($plan['university_fee_LKR']) ? $plan['university_fee_LKR'] : 0;
        $uniFeeGBP = !empty($plan['university_fee_GBP']) ? $plan['university_fee_GBP'] : 0;
        $uniFeeUSD = !empty($plan['university_fee_USD']) ? $plan['university_fee_USD'] : 0;

        // Calculate total university fee in LKR
        $totalUniFee = $uniFeeLKR;

        // Get university fee payments from payment_uni_fee table
        $uniPaymentsQuery = "
            SELECT 
                SUM(LKR_money) as total_paid_lkr,
                currency_type,
                paid_amount,
                exchange_rate
            FROM payment_uni_fee
            WHERE student_id = $studentId AND program_batch = '$programmeBatch'
            GROUP BY currency_type
        ";

        $uniPaymentsResult = $conn->query($uniPaymentsQuery);
        $uniPaid = 0;
        $uniPaymentDetails = [];

        if ($uniPaymentsResult && $uniPaymentsResult->num_rows > 0) {
            while ($payment = $uniPaymentsResult->fetch_assoc()) {
                $uniPaid += $payment['total_paid_lkr'];
                $uniPaymentDetails[] = [
                    'currency_type' => $payment['currency_type'],
                    'paid_amount' => $payment['paid_amount'],
                    'exchange_rate' => $payment['exchange_rate'],
                    'lkr_amount' => $payment['total_paid_lkr']
                ];
            }
        }

        // Check if there are any GBP or USD fees
        if ($uniFeeGBP > 0) {
            // Add GBP fee to the payment details
            $outstandingGBP = $uniFeeGBP;
            $paidGBP = 0;

            // Check if there are any GBP payments
            foreach ($uniPaymentDetails as $payment) {
                if ($payment['currency_type'] == 'GBP') {
                    $paidGBP += $payment['paid_amount'];
                }
            }

            $outstandingGBP = $uniFeeGBP - $paidGBP;

            // Always add GBP fee to payment details if it exists
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

        if ($uniFeeUSD > 0) {
            // Add USD fee to the payment details
            $outstandingUSD = $uniFeeUSD;
            $paidUSD = 0;

            // Check if there are any USD payments
            foreach ($uniPaymentDetails as $payment) {
                if ($payment['currency_type'] == 'USD') {
                    $paidUSD += $payment['paid_amount'];
                }
            }

            $outstandingUSD = $uniFeeUSD - $paidUSD;

            // Always add USD fee to payment details if it exists
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

        if ($uniFeeLKR > 0) {
            // Add LKR fee to the payment details
            $outstandingLKR = $uniFeeLKR;
            $paidLKR = 0;

            // Check if there are any LKR payments
            foreach ($uniPaymentDetails as $payment) {
                if ($payment['currency_type'] == 'LKR') {
                    $paidLKR += $payment['paid_amount'];
                }
            }

            $outstandingLKR = $uniFeeLKR - $paidLKR;

            // Always add LKR fee to payment details if it exists
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

        // 2. Check Registration Fee Payments
        $regFeeLKR = !empty($plan['registration_fee_LKR']) ? $plan['registration_fee_LKR'] : 0;
        $regFeeGBP = !empty($plan['registration_fee_GBP']) ? $plan['registration_fee_GBP'] : 0;
        $regFeeUSD = !empty($plan['registration_fee_USD']) ? $plan['registration_fee_USD'] : 0;

        // Get registration fee payments
        $regPaymentsQuery = "
            SELECT 
                SUM(paymentAmount) as total_paid
            FROM payment_wise_info
            WHERE student_id = $studentId 
            AND program_batch = '$programmeBatch'
            AND installmentNumber = 'Initial Payment'
        ";
        $regPaymentsResult = $conn->query($regPaymentsQuery);
        $regPaid = 0;

        if ($regPaymentsResult && $regPaymentsResult->num_rows > 0) {
            $regPayment = $regPaymentsResult->fetch_assoc();
            $regPaid = !empty($regPayment['total_paid']) ? $regPayment['total_paid'] : 0;
        }

        // Calculate outstanding registration fee
        $totalRegFee = $regFeeLKR + $regFeeGBP + $regFeeUSD;
        $outstandingRegFee = $totalRegFee - $regPaid;

        // Add registration fee to payment details regardless of outstanding amount
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
                'due_date' => date('Y-m-d'), // Current date as placeholder
                'total_paid_amount' => $regPaid,
                'outstanding_amount' => $outstandingRegFee,
                'currency' => 'LKR'
            ];
        }

        // 3. Check Installment Payments
        $installmentsQuery = "
            SELECT 
                idt.id,
                idt.installment_numbers,
                idt.installment_amount,
                idt.devided_values,
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
                $dueDate = $installment['due_date'];

                // Get installment payments from payment_wise_info
                $installmentPaymentsQuery = "
                    SELECT 
                        SUM(paymentAmount) as total_paid
                    FROM payment_wise_info
                    WHERE student_id = $studentId 
                    AND program_batch = '$programmeBatch'
                    AND installmentNumber = '$installmentNumber'
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
                    'due_date' => $dueDate,
                    'outstanding_amount' => $outstandingInstallment,
                    'currency' => 'LKR'
                ];
            }
        }
    }
}

// First, identify students with outstanding payments
$studentsWithOutstanding = [];
foreach ($paymentDetails as $detail) {
    if ($detail['outstanding_amount'] > 0) {
        $studentsWithOutstanding[$detail['student_code']] = true;
    }
}

// Group payments by student for better organization in Excel
$studentPayments = [];
foreach ($paymentDetails as $detail) {
    $studentId = $detail['student_code'];
    
    // Only process if student has any outstanding payments
    if (isset($studentsWithOutstanding[$studentId])) {
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
}

// Create Excel file
// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Outstanding_Payments_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Create Excel content
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Outstanding Payments</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<style>
    table, td, th {
        border: 1px solid #000000;
        border-collapse: collapse;
    }
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    .student-header {
        background-color: #e9ecef;
        font-weight: bold;
        text-align: left;
        padding: 5px;
    }
    .student-details {
        background-color: #f8f9fa;
        padding: 5px;
    }
    .total-row {
        background-color: #f0f0f0;
        font-weight: bold;
    }
    .uni-fee-row {
        background-color: #e6f7ff;
        font-weight: bold;
    }
    .no-outstanding {
        background-color: #d4edda;
        color: #155724;
        padding: 5px;
        text-align: center;
        font-weight: bold;
    }
</style>';
echo '</head>';
echo '<body>';

// Title
echo '<h1>Student Outstanding Payment Report</h1>';
echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';

// Report parameters
echo '<table width="100%">';
echo '<tr><th colspan="2">Report Parameters</th></tr>';
echo '<tr><td>Report Type</td><td>' . ucfirst($reportType) . '</td></tr>';
if ($reportType == 'student' && !empty($selectedStudent)) {
    // Get student name
    $studentQuery = "SELECT CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE student_code = $selectedStudent";
    $studentResult = $conn->query($studentQuery);
    $studentName = ($studentResult && $studentResult->num_rows > 0) ? $studentResult->fetch_assoc()['student_name'] : 'Unknown';
    echo '<tr><td>Student</td><td>' . $studentName . '</td></tr>';
} else if ($reportType == 'programme' && !empty($selectedProgram)) {
    // Get program name
    $programQuery = "SELECT program_name FROM program_table WHERE program_code = $selectedProgram";
    $programResult = $conn->query($programQuery);
    $programName = ($programResult && $programResult->num_rows > 0) ? $programResult->fetch_assoc()['program_name'] : 'Unknown';
    echo '<tr><td>Programme</td><td>' . $programName . '</td></tr>';
}
if (!empty($searchTerm)) {
    echo '<tr><td>Search Term</td><td>' . $searchTerm . '</td></tr>';
}
echo '</table>';
echo '<br>';

// Check if we have data
if (empty($studentPayments)) {
    echo '<p>No outstanding payments found.</p>';
} else {
    // Loop through each student
    foreach ($studentPayments as $studentId => $studentData) {
        $studentName = $studentData['student_name'];
        $firstName = $studentData['first_name'];
        $lastName = $studentData['last_name'];
        $email = $studentData['email'] ?? 'N/A';
        $contactNo = $studentData['contact_no'] ?? 'N/A';
        $studentRegId = $studentData['student_registration_id'];
        $studentPaymentDetails = $studentData['payments'];
        $totalOutstanding = $studentData['total_outstanding'];

        // Student header
        echo '<div class="student-header">Student: ' . $studentName . ' (' . $studentRegId . ')</div>';

        // Student details
        echo '<table class="student-details" width="100%">';
        echo '<tr><td width="120"><b>Name:</b></td><td>' . $firstName . ' ' . $lastName . '</td></tr>';
        echo '<tr><td><b>ID:</b></td><td>' . $studentRegId . '</td></tr>';
        if (!empty($email) && $email != 'N/A') {
            echo '<tr><td><b>Email:</b></td><td>' . $email . '</td></tr>';
        }
        if (!empty($contactNo) && $contactNo != 'N/A') {
            echo '<tr><td><b>Contact:</b></td><td>' . $contactNo . '</td></tr>';
        }
        echo '</table>';
        echo '<br>';

        if ($totalOutstanding <= 0) {
            echo '<div class="no-outstanding">No outstanding payments for this student</div>';
        } else {
            // Payment details table
            echo '<table width="100%" border="1">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Programme</th>';
            echo '<th>Payment Type</th>';
            echo '<th>Installment</th>';
            echo '<th>Due Date</th>';
            echo '<th>Original Amount</th>';
            echo '<th>Paid Amount</th>';
            echo '<th>Remaining Balance</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $totalOriginalAmount = 0;
            $totalPaidAmount = 0;
            $totalOutstandingAmount = 0;

            $uniTotalInstallmentAmount = 0;
            $uniTotalPaidAmount = 0;
            $uniTotalOutstandingAmount = 0;

            foreach ($studentPaymentDetails as $detail) {
                // Show all payments for students with outstanding amounts
                $isUniFee = strpos($detail['payment_type'], 'University Fee') !== false;

                // For installments, use original_amount and installment_amount
                $originalAmount = isset($detail['original_amount']) ? $detail['original_amount'] : $detail['installment_amount'];
                $paidAmount = isset($detail['original_amount']) ? $detail['installment_amount'] : $detail['total_paid_amount'];

                if ($isUniFee) {
                    // Add to University Fee totals
                    $uniTotalInstallmentAmount += $originalAmount;
                    $uniTotalPaidAmount += $paidAmount;
                    $uniTotalOutstandingAmount += $detail['outstanding_amount'];
                } else {
                    // Add to regular totals (Registration Fee and Installment)
                    $totalOriginalAmount += $originalAmount;
                    $totalPaidAmount += $paidAmount;
                    $totalOutstandingAmount += $detail['outstanding_amount'];
                }

                // Determine if payment is past due
                $isPastDue = false;
                if (!empty($detail['due_date'])) {
                    $dueDate = new DateTime($detail['due_date']);
                    $today = new DateTime();
                    $isPastDue = $dueDate < $today;
                }

                // Format due date
                $formattedDueDate = 'N/A';
                if (!empty($detail['due_date']) && $detail['due_date'] != '0000-00-00') {
                    $formattedDueDate = date('Y-m-d', strtotime($detail['due_date']));
                    if ($isPastDue) {
                        $formattedDueDate .= ' (Past Due)';
                    }
                }

                // Row style
                $rowStyle = '';
                if ($detail['outstanding_amount'] <= 0) {
                    $rowStyle = 'background-color: #f8f9fa;'; // Light gray for paid items
                } else if ($isPastDue) {
                    $rowStyle = 'color: #cc0000;'; // Red for past due
                }

                echo '<tr style="' . $rowStyle . '">';
                echo '<td>' . $detail['programme_batch'] . '</td>';
                echo '<td>' . $detail['payment_type'] . '</td>';
                echo '<td>' . $detail['installment_numbers'] . '</td>';
                echo '<td>' . $formattedDueDate . '</td>';
                echo '<td>' . number_format($originalAmount, 2) . ' ' . $detail['currency'] . '</td>';
                echo '<td>' . number_format($paidAmount, 2) . ' ' . $detail['currency'] . '</td>';
                echo '<td>' . number_format($detail['outstanding_amount'], 2) . ' ' . $detail['currency'] . 
                     ($detail['outstanding_amount'] <= 0 ? ' (Paid)' : '') . '</td>';
                echo '</tr>';
            }

            // Total rows
            echo '<tr class="total-row">';
            echo '<td colspan="4" style="text-align: right;">Total (Registration Fee + Installment):</td>';
            echo '<td>' . number_format($totalOriginalAmount, 2) . '</td>';
            echo '<td>' . number_format($totalPaidAmount, 2) . '</td>';
            echo '<td>' . number_format($totalOutstandingAmount, 2) . '</td>';
            echo '</tr>';

            echo '<tr class="uni-fee-row">';
            echo '<td colspan="4" style="text-align: right;">University Fee Outstanding:</td>';
            echo '<td>' . number_format($uniTotalInstallmentAmount, 2) . '</td>';
            echo '<td>' . number_format($uniTotalPaidAmount, 2) . '</td>';
            echo '<td>' . number_format($uniTotalOutstandingAmount, 2) . '</td>';
            echo '</tr>';

            echo '</tbody>';
            echo '</table>';
        }

        echo '<br><br>'; // Add space between students
    }
}

echo '</body>';
echo '</html>';

exit;
?>