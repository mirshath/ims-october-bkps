<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------

// Fetch active students with their details
$query = "SELECT ap.student_registration_id, s.student_code, s.first_name, s.last_name, 
          p.program_name, b.batch_name
          FROM allocate_programme ap
          JOIN students s ON ap.student_code = s.student_code
          JOIN program_table p ON ap.programme_code = p.program_code
          JOIN batch_table b ON ap.batch_id = b.id
          WHERE ap.status = 'active'
          ORDER BY s.first_name, s.last_name";
$result = mysqli_query($conn, $query);

// Check for query execution error
if (!$result) {
    echo "Error: " . mysqli_error($conn);
}
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
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Penalty Payment Form</h4>
                </div>

                <!-- Payment Form -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0">Penalty Payment Form</h6>
                            </div>
                            <div class="card-body">
                                <form action="process_penalty_payment.php" method="POST" id="penaltyForm">
                                    <!-- Select Student -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="studentDropdown" class="form-label">Select Student:</label>
                                        </div>
                                        <div class="col-md-8">
                                            <select id="studentDropdown" name="student_id" class="form-select select2-dropdown" required>
                                                <option value="">Select Student</option>
                                                <?php
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $studentId = $row['student_code'];
                                                        $studentName = $row['first_name'] . ' ' . $row['last_name'];
                                                        $regId = $row['student_registration_id'];
                                                        $program = $row['program_name'];
                                                        $batch = $row['batch_name'];
                                                        
                                                        echo "<option value=\"$studentId\" data-program=\"$program\" data-batch=\"$batch\">
                                                            $regId - $studentName ($program, $batch)
                                                        </option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <div id="studentDetails" class="mt-2 small text-muted d-none">
                                                <div><strong>Program:</strong> <span id="programInfo"></span></div>
                                                <div><strong>Batch:</strong> <span id="batchInfo"></span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Select Penalty -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="penaltyDropdown" class="form-label">Select Penalty:</label>
                                        </div>
                                        <div class="col-md-8">
                                            <select id="penaltyDropdown" name="penalty_type" class="form-select" required>
                                                <option value="">Select Penalty</option>
                                                <option value="Late Fee">Late Fee</option>
                                                <option value="Missing Documents">Missing Documents</option>
                                                <option value="Late Assignment">Late Assignment</option>
                                                <option value="Exam Retake">Exam Retake</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Penalty -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="penaltyInput" class="form-label">Penalty Amount:</label>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="number" id="penaltyInput" name="penalty_amount" class="form-control" placeholder="Enter Penalty Amount" required>
                                        </div>
                                    </div>

                                    <!-- Discount Type & Discount -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="discountType" class="form-label">Discount Type:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <select id="discountType" name="discount_type" class="form-select">
                                                <option value="N/A" selected>N/A</option>
                                                <option value="Scholarship">Scholarship</option>
                                                <option value="Special Case">Special Case</option>
                                                <option value="Financial Hardship">Financial Hardship</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" id="discountAmount" name="discount_amount" class="form-control" placeholder="0" value="0">
                                        </div>
                                    </div>

                                    <!-- Amount -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="amountInput" class="form-label">Final Amount:</label>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="number" id="amountInput" name="final_amount" class="form-control" placeholder="Final Payment Amount" readonly>
                                        </div>
                                    </div>

                                    <!-- Paid Date -->
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-4 text-end">
                                            <label for="paidDate" class="form-label">Penalty/Other Paid Date:</label>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="date" id="paidDate" name="paid_date" class="form-control" required>
                                        </div>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-success w-25">Save</button>
                                        </div>
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

<!-- JS and CSS includes -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for student dropdown
    $('.select2-dropdown').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Select a student",
        allowClear: true
    });

    // Show student details when a student is selected
    $('#studentDropdown').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const program = selectedOption.data('program');
        const batch = selectedOption.data('batch');
        
        if (program && batch) {
            $('#programInfo').text(program);
            $('#batchInfo').text(batch);
            $('#studentDetails').removeClass('d-none');
        } else {
            $('#studentDetails').addClass('d-none');
        }
    });

    // Calculate final amount when penalty or discount changes
    $('#penaltyInput, #discountAmount').on('input', function() {
        calculateFinalAmount();
    });

    // Set today's date as default for paid date
    const today = new Date().toISOString().split('T')[0];
    $('#paidDate').val(today);

    // Function to calculate final amount
    function calculateFinalAmount() {
        const penaltyAmount = parseFloat($('#penaltyInput').val()) || 0;
        const discountAmount = parseFloat($('#discountAmount').val()) || 0;
        const finalAmount = Math.max(0, penaltyAmount - discountAmount);
        $('#amountInput').val(finalAmount.toFixed(2));
    }

    // Form validation
    $('#penaltyForm').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Validate penalty amount
        const penaltyAmount = parseFloat($('#penaltyInput').val());
        if (isNaN(penaltyAmount) || penaltyAmount <= 0) {
            isValid = false;
            $('#penaltyInput').addClass('is-invalid');
        }
        
        // Validate discount amount
        const discountAmount = parseFloat($('#discountAmount').val());
        if (isNaN(discountAmount) || discountAmount < 0) {
            isValid = false;
            $('#discountAmount').addClass('is-invalid');
        }
        
        // Check if discount is greater than penalty
        if (discountAmount > penaltyAmount) {
            isValid = false;
            $('#discountAmount').addClass('is-invalid');
            alert('Discount amount cannot be greater than penalty amount');
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
