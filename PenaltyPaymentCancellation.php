<?php
session_start();
include("database/connection.php");


if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}



// Handle AJAX request for payment receipts BEFORE including header.php
// This prevents the "headers already sent" error
if (isset($_GET['action']) && $_GET['action'] == 'getReceipts' && isset($_GET['studentId'])) {
    $studentId = $_GET['studentId'];
    
    // Fetch penalty payments for this student
    $query = "SELECT id, penalty_type, penalty_amount, discount_amount, final_amount, paid_date 
              FROM penalty_payments 
              WHERE student_id = ? AND status = 'paid'
              ORDER BY paid_date DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $receipts = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $receipts[] = $row;
        }
    }
    
    // Set headers and output JSON
    header('Content-Type: application/json');
    echo json_encode($receipts);
    exit;
}

// Now include the header file after handling AJAX requests
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Function to get students with active status
function getStudents($conn) {
    $query = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, 
              p.program_name, b.batch_name 
              FROM students s
              JOIN allocate_programme ap ON s.student_code = ap.student_code
              JOIN program_table p ON ap.programme_code = p.program_code
              JOIN batch_table b ON ap.batch_id = b.id
              WHERE ap.status = 'active'
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
        cancelled_at DATETIME NOT NULL,
        payment_type VARCHAR(20) DEFAULT 'regular'
    )";
    mysqli_query($conn, $createLogTable);
}

// Check if payment_type column exists in payment_cancellation_log table
$checkPaymentTypeColumn = "SHOW COLUMNS FROM payment_cancellation_log LIKE 'payment_type'";
$paymentTypeColumnExists = mysqli_query($conn, $checkPaymentTypeColumn);
$hasPaymentTypeColumn = (mysqli_num_rows($paymentTypeColumnExists) > 0);

// Add payment_type column if it doesn't exist
if (!$hasPaymentTypeColumn) {
    $addPaymentTypeColumn = "ALTER TABLE payment_cancellation_log ADD COLUMN payment_type VARCHAR(20) DEFAULT 'regular'";
    mysqli_query($conn, $addPaymentTypeColumn);
    // Check if column was added successfully
    $paymentTypeColumnExists = mysqli_query($conn, $checkPaymentTypeColumn);
    $hasPaymentTypeColumn = (mysqli_num_rows($paymentTypeColumnExists) > 0);
}

// Get all students
$students = getStudents($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['studentId'];
    $receiptId = $_POST['receiptId'];
    $reason = $_POST['reason'];
    
    // Update penalty payment status to cancelled
    $updateQuery = "UPDATE penalty_payments SET status = 'cancelled' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $receiptId);
    $success = mysqli_stmt_execute($stmt);
    
    if ($success) {
        // Log the cancellation - use different queries based on whether payment_type column exists
        if ($hasPaymentTypeColumn) {
            $logQuery = "INSERT INTO payment_cancellation_log (payment_id, student_id, reason, cancelled_by, cancelled_at, payment_type) VALUES (?, ?, ?, ?, NOW(), 'penalty')";
            $logStmt = mysqli_prepare($conn, $logQuery);
            mysqli_stmt_bind_param($logStmt, "iiss", $receiptId, $studentId, $reason, $_SESSION['username']);
        } else {
            $logQuery = "INSERT INTO payment_cancellation_log (payment_id, student_id, reason, cancelled_by, cancelled_at) VALUES (?, ?, ?, ?, NOW())";
            $logStmt = mysqli_prepare($conn, $logQuery);
            mysqli_stmt_bind_param($logStmt, "iiss", $receiptId, $studentId, $reason, $_SESSION['username']);
        }
        
        mysqli_stmt_execute($logStmt);
        
        echo '<script>
            alert("Penalty payment cancelled successfully!");
            window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";
        </script>';
    } else {
        echo '<script>
            alert("Error cancelling payment. Please try again.");
        </script>';
    }
}




// ---------------------------- allowed Redirections -------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------



?>

<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Custom styles for Select2 with Bootstrap */
    .select2-container--classic .select2-selection--single {
        height: 38px;
        border-radius: 0.375rem;
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
</style>

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
                <!-- Breadcrumb & Title -->
                <div class="row mb-4">
                    <div class="col-12">
                       
                        <h4 class="border-bottom pb-2 mb-4">Cancel Penalty Payment</h4>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="row">
                    <div class="col-md-8 col-lg-6 mx-auto">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-x-circle text-danger me-2"></i>Penalty Payment Cancellation Form
                                </h5>
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
                                        <label for="paymentReceiptSelect" class="form-label">Select Penalty Payment:</label>
                                        <select class="form-select" id="paymentReceiptSelect" name="receiptId" required disabled>
                                            <option value="" selected disabled>Choose a penalty payment...</option>
                                        </select>
                                        <div class="form-text text-muted">Select the penalty payment you want to cancel</div>
                                    </div>

                                    <div id="receiptDetailsCard" class="card mb-3 d-none">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Payment Details</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Penalty Type:</strong> <span id="penaltyType"></span></p>
                                                    <p class="mb-1"><strong>Original Amount:</strong> <span id="originalAmount"></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Final Amount:</strong> <span id="finalAmount"></span></p>
                                                    <p class="mb-1"><strong>Paid Date:</strong> <span id="paidDate"></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="reasonTextarea" class="form-label">Reason for Cancellation:</label>
                                        <textarea class="form-control" id="reasonTextarea" name="reason" rows="3" placeholder="Please provide a detailed reason for cancellation" required></textarea>
                                    </div>

                                    <div class="alert alert-warning">
                                        <div class="d-flex">
                                            <div class="me-2">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </div>
                                            <div>
                                                <strong>Warning:</strong> This action cannot be undone. Cancelling a payment will reverse the transaction and may affect student records.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <a href="#" class="btn btn-secondary me-md-2">
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
            </div> <!-- /.container-fluid -->
        </div> <!-- /.content -->
    </div> <!-- /.content-wrapper -->
</div> <!-- /#wrapper -->

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
            placeholder: "Choose a penalty payment...",
            width: '100%',
            theme: "classic",
            allowClear: true,
            disabled: true
        });
        
        // When student is selected
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
                $('#paymentReceiptSelect').prop('disabled', false).select2('enable');
                
                // Fetch penalty payments for this student
                $.ajax({
                    url: 'PenaltyPaymentCancellation.php?action=getReceipts&studentId=' + studentId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        // Clear and reset the payment receipt dropdown
                        $('#paymentReceiptSelect').empty();
                        $('#paymentReceiptSelect').append('<option value="" selected disabled>Choose a penalty payment...</option>');
                        
                        if (data && data.length > 0) {
                            $.each(data, function(index, receipt) {
                                const formattedDate = receipt.paid_date ? new Date(receipt.paid_date).toLocaleDateString() : 'N/A';
                                const receiptText = `${receipt.penalty_type} - Rs.${receipt.final_amount || '0'} - ${formattedDate}`;
                                
                                $('#paymentReceiptSelect').append(
                                    $('<option></option>')
                                        .val(receipt.id)
                                        .text(receiptText)
                                        .data('penalty-type', receipt.penalty_type)
                                        .data('original-amount', receipt.penalty_amount)
                                        .data('final-amount', receipt.final_amount)
                                        .data('date', formattedDate)
                                );
                            });
                            
                            // Refresh Select2 after adding options
                            $('#paymentReceiptSelect').trigger('change');
                        } else {
                            $('#paymentReceiptSelect').append('<option value="" disabled>No penalty payments found</option>');
                        }
                        
                        // Refresh Select2
                        $('#paymentReceiptSelect').select2('destroy').select2({
                            placeholder: "Choose a penalty payment...",
                            width: '100%',
                            theme: "classic",
                            allowClear: true
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response:", xhr.responseText);
                        alert('Error fetching penalty payments. Please check the console for details.');
                    }
                });
            } else {
                $('#studentDetailsCard').addClass('d-none');
                $('#paymentReceiptSelect').prop('disabled', true).select2('disable');
                $('#receiptDetailsCard').addClass('d-none');
            }
        });
        
        // When payment receipt is selected
        $('#paymentReceiptSelect').on('select2:select', function() {
            const selectedOption = $(this).find('option:selected');
            
            if (selectedOption.val()) {
                // Display receipt details
                $('#penaltyType').text(selectedOption.data('penalty-type'));
                $('#originalAmount').text('Rs.' + selectedOption.data('original-amount'));
                $('#finalAmount').text('Rs.' + selectedOption.data('final-amount'));
                $('#paidDate').text(selectedOption.data('date'));
                $('#receiptDetailsCard').removeClass('d-none');
            } else {
                $('#receiptDetailsCard').addClass('d-none');
            }
        });

        // Form submission confirmation
        $('#paymentCancellationForm').submit(function(e) {
            if (!confirm('Are you sure you want to cancel this penalty payment? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>
