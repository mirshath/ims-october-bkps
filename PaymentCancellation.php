<?php
session_start();
include("database/connection.php");

if (
    isset($_GET['action']) && $_GET['action'] == 'getReceipts'
    && isset($_GET['studentId'])
    && isset($_GET['programme'])
    && isset($_GET['batch'])
) {

    $studentId = $_GET['studentId'];
    $programme = $_GET['programme'];
    $batch     = $_GET['batch'];

    // Combine program + batch if your DB stores them together
    $program_batch_value = $programme . ' - ' . $batch;

    // Check if status column exists
    $checkStatusColumn = "SHOW COLUMNS FROM payment_wise_info LIKE 'status'";
    $statusColumnExists = mysqli_query($conn, $checkStatusColumn);
    $statusExists = (mysqli_num_rows($statusColumnExists) > 0);

    // Column name in your table
    $programBatchColumn = "program_batch"; // <--- make sure this exists in your DB

    if ($statusExists) {
        $query = "SELECT id, installmentNumber, paymentAmount, paid_date, rcpt_number 
                  FROM payment_wise_info 
                  WHERE student_id = ? 
                    AND $programBatchColumn = ?
                    AND (status = 'paid' OR status IS NULL)
                  ORDER BY paid_date DESC";
    } else {
        $query = "SELECT id, installmentNumber, paymentAmount, paid_date, rcpt_number 
                  FROM payment_wise_info 
                  WHERE student_id = ? 
                    AND $programBatchColumn = ?
                  ORDER BY paid_date DESC";
    }

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $studentId, $program_batch_value);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $receipts = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $receipts[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($receipts);
    exit;
}



// Now include the header file after handling AJAX requests
include("includes/header.php");

// Add Select2 CSS and JS
echo '
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Custom styles for Select2 with Bootstrap */
    .select2-container--classic .select2-selection--single {
        height: 38px;
        border-radius: 0.375rem 0 0 0.375rem;
        border: 1px solid #ced4da;
    }
    .select2-container--classic .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
    .select2-container--classic .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-dropdown {
        border: 1px solid #ced4da;
    }
    .select2-results__option {
        padding: 6px 12px;
    }
    .select2-container--classic .select2-results__option--highlighted[aria-selected] {
        background-color: #0d6efd;
    }
    .select2-bootstrap-container .select2-container {
        flex: 1 1 auto;
    }
    .select2-bootstrap-container .btn {
        border-radius: 0 0.375rem 0.375rem 0;
    }
</style>
';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Modified function to get students with active status and detailed information
function getStudents($conn)
{
    $query = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, 
              p.program_name, b.batch_name 
              FROM students s
              JOIN allocate_programme ap ON s.student_code = ap.student_code
              JOIN program_table p ON ap.programme_code = p.program_code
              JOIN batch_table b ON ap.batch_id = b.id
            
              ORDER BY s.first_name, s.last_name";

    $result = mysqli_query($conn, $query);
    $students = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
    }

    return $students;
}

// Check and add required columns to payment_wise_info table
$requiredColumns = [
    'status' => "ALTER TABLE payment_wise_info ADD COLUMN status ENUM('paid', 'pending', 'cancelled') DEFAULT 'paid'",
    'cancellation_reason' => "ALTER TABLE payment_wise_info ADD COLUMN cancellation_reason TEXT",
    'cancelled_by' => "ALTER TABLE payment_wise_info ADD COLUMN cancelled_by VARCHAR(50)",
    'cancelled_at' => "ALTER TABLE payment_wise_info ADD COLUMN cancelled_at DATETIME"
];

foreach ($requiredColumns as $column => $alterQuery) {
    $checkColumn = "SHOW COLUMNS FROM payment_wise_info LIKE '$column'";
    $columnExists = mysqli_query($conn, $checkColumn);

    if (!$columnExists || mysqli_num_rows($columnExists) == 0) {
        mysqli_query($conn, $alterQuery);
    }
}

// Check if payment_cancellation_log table exists
$checkLogTable = "SHOW TABLES LIKE 'payment_cancellation_log'";
$logTableExists = mysqli_query($conn, $checkLogTable);
$tableExists = (mysqli_num_rows($logTableExists) > 0);

// Create payment_cancellation_log table if it doesn't exist
if (!$tableExists) {
    $createLogTable = "CREATE TABLE payment_cancellation_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id INT NOT NULL,
        student_id INT NOT NULL,
        reason TEXT NOT NULL,
        cancelled_by VARCHAR(50) NOT NULL,
        cancelled_at DATETIME NOT NULL
    )";
    mysqli_query($conn, $createLogTable);
}

// Get all students
$students = getStudents($conn);



// ------------------- 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['studentId'];
    $receiptId = $_POST['receiptId'];
    $reason = $_POST['reason'];

    // Cancel payment
    $updateQuery = "UPDATE payment_wise_info SET status = 'cancelled', cancellation_reason = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "si", $reason, $receiptId);
    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        // 1. Get payment details from payment_wise_info
        $getPaymentQuery = "SELECT student_id, installmentNumber, paymentAmount 
    FROM payment_wise_info 
    WHERE id = ?";
        $getStmt = mysqli_prepare($conn, $getPaymentQuery);
        mysqli_stmt_bind_param($getStmt, "i", $receiptId);
        mysqli_stmt_execute($getStmt);
        $paymentResult = mysqli_stmt_get_result($getStmt);
        $paymentData = mysqli_fetch_assoc($paymentResult);

        if ($paymentData) {
            $studentId = $paymentData['student_id'];
            $installmentNumber = $paymentData['installmentNumber'];
            $cancelledAmount = $paymentData['paymentAmount'];

            if ($installmentNumber === 'Initial Payment') {
                // Fetch programme_batch from installment_payment_table
                $getBatchQuery = "SELECT programme_batch FROM installment_payment_table 
          WHERE student_id = ? LIMIT 1";
                $batchStmt = mysqli_prepare($conn, $getBatchQuery);
                mysqli_stmt_bind_param($batchStmt, "i", $studentId);
                mysqli_stmt_execute($batchStmt);
                $batchResult = mysqli_stmt_get_result($batchStmt);
                $batchData = mysqli_fetch_assoc($batchResult);
                $programmeBatch = $batchData['programme_batch'] ?? null;

                if ($programmeBatch) {
                    // Update registrationfee
                    $updateRegFee = "UPDATE installment_payment_table 
             SET registrationfee = registrationfee + ? 
             WHERE student_id = ? AND programme_batch = ?";
                    $regStmt = mysqli_prepare($conn, $updateRegFee);
                    mysqli_stmt_bind_param($regStmt, "dis", $cancelledAmount, $studentId, $programmeBatch);
                    mysqli_stmt_execute($regStmt);
                }
            } else {
                // For other installments: update installment_details_table
                $getBatchQuery = "SELECT programme_batch 
          FROM installment_details_table 
          WHERE student_id = ? AND installment_numbers = ? 
          LIMIT 1";
                $batchStmt = mysqli_prepare($conn, $getBatchQuery);
                mysqli_stmt_bind_param($batchStmt, "ss", $studentId, $installmentNumber);
                mysqli_stmt_execute($batchStmt);
                $batchResult = mysqli_stmt_get_result($batchStmt);
                $batchData = mysqli_fetch_assoc($batchResult);

                if ($batchData) {
                    $programmeBatch = $batchData['programme_batch'];
                    $updateInstallment = "UPDATE installment_details_table 
                  SET installment_amount = installment_amount + ? 
                  WHERE student_id = ? AND programme_batch = ? AND installment_numbers = ?";
                    $installStmt = mysqli_prepare($conn, $updateInstallment);
                    mysqli_stmt_bind_param($installStmt, "dsss", $cancelledAmount, $studentId, $programmeBatch, $installmentNumber);
                    mysqli_stmt_execute($installStmt);
                }
            }
        }

        // Log the cancellation
        $logQuery = "INSERT INTO payment_cancellation_log (payment_id, student_id, reason, cancelled_by, cancelled_at) 
VALUES (?, ?, ?, ?, NOW())";
        $logStmt = mysqli_prepare($conn, $logQuery);
        mysqli_stmt_bind_param($logStmt, "iiss", $receiptId, $studentId, $reason, $_SESSION['username']);
        mysqli_stmt_execute($logStmt);

        echo '<script>
            alert("Payment cancelled successfully!");
            window.location.href = "PaymentCancellation";
        </script>';
    } else {
        echo '<script>
            alert("Error cancelling payment. Please try again.");
        </script>';
    }
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// include 'PermissionChecking.php';
// --------------------------------------------------------------------- 

?>

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
            <div class="container-fluid p-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Cancel Payment</h4>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light d-flex align-items-center">
                                <i class="bi bi-x-circle text-danger me-2 fs-5"></i>
                                <h5 class="card-title mb-0">Payment Cancellation Form</h5>
                            </div>
                            <div class="card-body">
                                <form id="paymentCancellationForm" method="post">
                                    <div class="mb-3">
                                        <label for="studentSelect" class="form-label">Select Student:</label>
                                        <select class="form-select" id="studentSelect" name="studentId" required>
                                            <option value="" selected disabled>Choose a student...</option>
                                            <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['student_code']; ?>"
                                                    data-reg-id="<?php echo $student['student_registration_id']; ?>"
                                                    data-program="<?php echo $student['program_name']; ?>"
                                                    data-batch="<?php echo $student['batch_name']; ?>">
                                                    <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                                    [<?php echo $student['student_registration_id']; ?>] -
                                                    <?php echo $student['program_name']; ?> -
                                                    <?php echo $student['batch_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div id="studentDetailsCard" class="card mb-3 d-none">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Student Details</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Registration ID:</strong> <span id="studentRegId"></span></p>
                                                    <p class="mb-1"><strong>Name:</strong> <span id="studentName"></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Program:</strong> <span id="studentProgram"></span></p>
                                                    <p class="mb-1"><strong>Batch:</strong> <span id="studentBatch"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="paymentReceiptSelect" class="form-label">Select Payment Receipt:</label>
                                        <select class="form-select" id="paymentReceiptSelect" name="receiptId" required disabled>
                                            <option value="" selected disabled>Choose a payment receipt...</option>
                                        </select>
                                        <div class="form-text text-muted">Select the payment receipt you want to cancel</div>
                                    </div>

                                    <div id="receiptDetailsCard" class="card mb-3 d-none">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Receipt Details</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Receipt #:</strong> <span id="receiptNumber"></span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Amount:</strong> <span id="receiptAmount"></span></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Paid Date:</strong> <span id="receiptDate"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="reasonTextarea" class="form-label">Reason for Cancellation:</label>
                                        <textarea class="form-control" id="reasonTextarea" name="reason" rows="3" placeholder="Please provide a detailed reason for cancellation" required></textarea>
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>Warning:</strong> This action cannot be undone. Cancelling a payment will reverse the transaction and may affect student records.
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <a href="payments.php" class="btn btn-secondary me-md-2">
                                            <i class="bi bi-arrow-left"></i> Back
                                        </a>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-x-circle"></i> Cancel Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for student dropdown
        $('#studentSelect').select2({
            placeholder: "Choose a student...",
            width: '100%',
            theme: "classic",
            allowClear: true
        });

        // Initialize Select2 for payment receipt dropdown (will be enabled when a student is selected)
        $('#paymentReceiptSelect').select2({
            placeholder: "Choose a payment receipt...",
            width: '100%',
            theme: "classic",
            allowClear: true,
            disabled: true
        });

        // When student is selected
        // $('#studentSelect').on('select2:select', function() {
        //     const studentId = $(this).val();
        //     const studentName = $(this).find('option:selected').text().split('[')[0].trim();
        //     const regId = $(this).find('option:selected').data('reg-id');
        //     const program = $(this).find('option:selected').data('program');
        //     const batch = $(this).find('option:selected').data('batch');

        //     if (studentId) {
        //         // Display student details
        //         $('#studentName').text(studentName);
        //         $('#studentRegId').text(regId);
        //         $('#studentProgram').text(program);
        //         $('#studentBatch').text(batch);
        //         $('#studentDetailsCard').removeClass('d-none');

        //         // Enable payment receipt dropdown
        //         $('#paymentReceiptSelect').prop('disabled', false).select2('enable');

        //         // Fetch payment receipts for this student
        //         $.ajax({
        //             // url: 'PaymentCancellation.php?action=getReceipts&studentId=' + studentId,
        //             url: 'PaymentCancellation.php',
        //             type: 'GET',
        //             dataType: 'json',
        //             success: function(data) {
        //                 // Clear and reset the payment receipt dropdown
        //                 $('#paymentReceiptSelect').empty();
        //                 $('#paymentReceiptSelect').append('<option value="" selected disabled>Choose a payment receipt...</option>');

        //                 if (data && data.length > 0) {
        //                     $.each(data, function(index, receipt) {
        //                         const formattedDate = receipt.paid_date ? new Date(receipt.paid_date).toLocaleDateString() : 'N/A';
        //                         const receiptText = `Receipt #${receipt.rcpt_number || 'N/A'} - ${receipt.installmentNumber || 'N/A'} - Rs.${receipt.paymentAmount || '0'} - ${formattedDate}`;

        //                         $('#paymentReceiptSelect').append(
        //                             $('<option></option>')
        //                             .val(receipt.id)
        //                             .text(receiptText)
        //                             .data('receipt-number', receipt.rcpt_number)
        //                             .data('amount', receipt.paymentAmount)
        //                             .data('date', formattedDate)
        //                         );
        //                     });

        //                     // Refresh Select2 after adding options
        //                     $('#paymentReceiptSelect').trigger('change');
        //                 } else {
        //                     $('#paymentReceiptSelect').append('<option value="" disabled>No payment receipts found</option>');
        //                 }

        //                 // Refresh Select2
        //                 $('#paymentReceiptSelect').select2('destroy').select2({
        //                     placeholder: "Choose a payment receipt...",
        //                     width: '100%',
        //                     theme: "classic",
        //                     allowClear: true
        //                 });
        //             },
        //             error: function(xhr, status, error) {
        //                 console.error("AJAX Error:", status, error);
        //                 console.error("Response:", xhr.responseText);
        //                 alert('Error fetching payment receipts. Please check the console for details.');
        //             }
        //         });
        //     } else {
        //         $('#studentDetailsCard').addClass('d-none');
        //         $('#paymentReceiptSelect').prop('disabled', true).select2('disable');
        //         $('#receiptDetailsCard').addClass('d-none');
        //     }
        // });


        $('#studentSelect').on('select2:select', function() {
            const studentId = $(this).val();
            const studentName = $(this).find('option:selected').text().split('[')[0].trim();
            const regId = $(this).find('option:selected').data('reg-id');
            const program = $(this).find('option:selected').data('program');
            const batch = $(this).find('option:selected').data('batch');

            if (studentId) {
                // Display student details
                $('#studentName').text(studentName);
                $('#studentRegId').text(regId);
                $('#studentProgram').text(program);
                $('#studentBatch').text(batch);
                $('#studentDetailsCard').removeClass('d-none');

                // Enable payment receipt dropdown
                $('#paymentReceiptSelect').prop('disabled', false);

                // Fetch payment receipts for this student + program + batch
                $.ajax({
                    url: 'PaymentCancellation.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'getReceipts',
                        studentId: studentId,
                        programme: program,
                        batch: batch
                    },
                    success: function(data) {
                        $('#paymentReceiptSelect').empty();
                        $('#paymentReceiptSelect').append('<option value="" selected disabled>Choose a payment receipt...</option>');

                        if (data && data.length > 0) {
                            $.each(data, function(index, receipt) {
                                const formattedDate = receipt.paid_date ? new Date(receipt.paid_date).toLocaleDateString() : 'N/A';
                                const receiptText = `Receipt #${receipt.rcpt_number || 'N/A'} - ${receipt.installmentNumber || 'N/A'} - Rs.${receipt.paymentAmount || '0'} - ${formattedDate}`;

                                $('#paymentReceiptSelect').append(
                                    $('<option></option>')
                                    .val(receipt.id)
                                    .text(receiptText)
                                    .data('receipt-number', receipt.rcpt_number)
                                    .data('amount', receipt.paymentAmount)
                                    .data('date', formattedDate)
                                );
                            });
                        } else {
                            $('#paymentReceiptSelect').append('<option value="" disabled>No payment receipts found</option>');
                        }

                        // Refresh Select2
                        $('#paymentReceiptSelect').select2({
                            placeholder: "Choose a payment receipt...",
                            width: '100%',
                            theme: "classic",
                            allowClear: true
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response:", xhr.responseText);
                        alert('Error fetching payment receipts. Please check the console for details.');
                    }
                });
            } else {
                $('#studentDetailsCard').addClass('d-none');
                $('#paymentReceiptSelect').prop('disabled', true);
                $('#receiptDetailsCard').addClass('d-none');
            }
        });


        // When payment receipt is selected
        $('#paymentReceiptSelect').on('select2:select', function() {
            const selectedOption = $(this).find('option:selected');

            if (selectedOption.val()) {
                // Display receipt details
                $('#receiptNumber').text(selectedOption.data('receipt-number') || 'N/A');
                $('#receiptAmount').text('Rs.' + selectedOption.data('amount'));
                $('#receiptDate').text(selectedOption.data('date'));
                $('#receiptDetailsCard').removeClass('d-none');
            } else {
                $('#receiptDetailsCard').addClass('d-none');
            }
        });

        // Form submission confirmation
        $('#paymentCancellationForm').submit(function(e) {
            if (!confirm('Are you sure you want to cancel this payment? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>