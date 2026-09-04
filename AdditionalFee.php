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
                    <h4 class="h4 mb-0 text-gray-800">Additional Fee Payment</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light d-flex align-items-center">
                                <i class="fas fa-money-bill-wave me-2"></i> Additional Fee Payment
                            </div>
                            <div class="card-body">
                                <form id="additionalFeeForm" action="process_additional_fee.php" method="POST">
                                    <div class="row mb-3">
                                        <label for="studentSelect" class="col-sm-3 col-form-label text-md-end">Select Student:</label>
                                        <div class="col-sm-9">
                                            <select class="form-select select2-dropdown" id="studentSelect" name="student_id" required>
                                                <option value="">Choose a student...</option>
                                                <?php
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $studentId = $row['student_code'];
                                                        $studentName = $row['first_name'] . ' ' . $row['last_name'];
                                                        $regId = $row['student_registration_id'];
                                                        $program = $row['program_name'];
                                                        $batch = $row['batch_name'];
                                                        
                                                        echo "<option value=\"$studentId\" data-program=\"$program\" data-batch=\"$batch\" data-regid=\"$regId\">
                                                            $regId - $studentName ($program, $batch)
                                                        </option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <div id="studentDetails" class="mt-2 small text-muted d-none">
                                                <div><strong>Registration ID:</strong> <span id="regIdInfo"></span></div>
                                                <div><strong>Program:</strong> <span id="programInfo"></span></div>
                                                <div><strong>Batch:</strong> <span id="batchInfo"></span></div>
                                            </div>
                                            <input type="hidden" id="selectedRegId" name="student_registration_id">
                                            <input type="hidden" id="selectedProgram" name="program_name">
                                            <input type="hidden" id="selectedBatch" name="batch_name">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="description" class="col-sm-3 col-form-label text-md-end">Description:</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="description" name="description">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="amount" class="col-sm-3 col-form-label text-md-end">Amount:</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0">
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-sm-9 offset-sm-3">
                                            <button type="button" id="addButton" class="btn btn-primary">
                                                <i class="fas fa-plus-circle"></i> Add Fee Item
                                            </button>
                                        </div>
                                    </div>

                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            Fee Details
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="text-center" style="width: 10%">Seq</th>
                                                            <th style="width: 55%">Description</th>
                                                            <th class="text-end" style="width: 25%">Amount</th>
                                                            <th class="text-center" style="width: 10%">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="feeTableBody">
                                                        <!-- Fee items will be added here dynamically -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="paidDate" class="col-sm-3 col-form-label text-md-end">Paid Date:</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="paidDate" name="paid_date" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="netTotal" class="col-sm-3 col-form-label text-md-end">Net Total:</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control" id="netTotal" name="net_total" value="0" readonly>
                                        </div>
                                    </div>

                                    <!-- Hidden input to store fee items as JSON -->
                                    <input type="hidden" id="feeItems" name="fee_items" value="[]">

                                    <div class="row">
                                        <div class="col-sm-9 offset-sm-3">
                                            <button type="submit" class="btn btn-success me-2">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button type="button" class="btn btn-danger" id="cancelButton">
                                                <i class="fas fa-times-circle"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div> <!-- card-body -->
                        </div> <!-- card -->
                    </div> <!-- col -->
                </div> <!-- row -->
            </div> <!-- p-3 -->
        </div> <!-- #content -->
    </div> <!-- #content-wrapper -->
</div> <!-- #wrapper -->

<!-- Scripts and Styles -->
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

        // Set today's date as default for paid date
        const today = new Date().toISOString().split('T')[0];
        $('#paidDate').val(today);

        // Show student details when a student is selected
        $('#studentSelect').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const program = selectedOption.data('program');
            const batch = selectedOption.data('batch');
            const regId = selectedOption.data('regid');
            
            if (program && batch) {
                $('#regIdInfo').text(regId);
                $('#programInfo').text(program);
                $('#batchInfo').text(batch);
                $('#studentDetails').removeClass('d-none');
                
                // Store values in hidden fields
                $('#selectedRegId').val(regId);
                $('#selectedProgram').val(program);
                $('#selectedBatch').val(batch);
            } else {
                $('#studentDetails').addClass('d-none');
                $('#selectedRegId').val('');
                $('#selectedProgram').val('');
                $('#selectedBatch').val('');
            }
        });

        // Add fee item to table
        let sequence = 1;
        let feeItems = [];
        
        $('#addButton').click(function() {
            const description = $('#description').val();
            const amount = parseFloat($('#amount').val());
            
            if (description && !isNaN(amount) && amount > 0) {
                // Add to table
                const rowId = 'fee-item-' + sequence;
                $('#feeTableBody').append(`
                    <tr id="${rowId}">
                        <td class="text-center">${sequence}</td>
                        <td>${description}</td>
                        <td class="text-end">${amount.toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger delete-fee" data-row="${rowId}" data-amount="${amount}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
                
                // Add to fee items array
                feeItems.push({
                    id: rowId,
                    sequence: sequence,
                    description: description,
                    amount: amount
                });
                
                // Update hidden input with JSON data
                $('#feeItems').val(JSON.stringify(feeItems));
                
                // Update total
                updateNetTotal();
                
                // Clear inputs
                $('#description').val('');
                $('#amount').val('');
                sequence++;
                
                // Focus back on description field
                $('#description').focus();
            } else {
                alert('Please enter a valid description and amount greater than zero');
            }
        });
        
        // Delete fee item
        $(document).on('click', '.delete-fee', function() {
            const rowId = $(this).data('row');
            
            // Remove from DOM
            $('#' + rowId).remove();
            
            // Remove from fee items array
            feeItems = feeItems.filter(item => item.id !== rowId);
            
            // Update hidden input with JSON data
            $('#feeItems').val(JSON.stringify(feeItems));
            
            // Update total
            updateNetTotal();
            
            // Resequence visible rows
            $('#feeTableBody tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        });
        
        // Function to update net total
        function updateNetTotal() {
            let total = 0;
            feeItems.forEach(item => {
                total += item.amount;
            });
            $('#netTotal').val(total.toFixed(2));
        }
        
        // Cancel button
        $('#cancelButton').click(function() {
            if (confirm('Are you sure you want to cancel? All entered data will be lost.')) {
                window.location.href = 'index.php'; // Redirect to dashboard or appropriate page
            }
        });
        
        // Form validation
        $('#additionalFeeForm').on('submit', function(e) {
            if (feeItems.length === 0) {
                e.preventDefault();
                alert('Please add at least one fee item before saving');
                return false;
            }
            
            if (!$('#studentSelect').val()) {
                e.preventDefault();
                alert('Please select a student');
                return false;
            }
            
            if (!$('#paidDate').val()) {
                e.preventDefault();
                alert('Please select a paid date');
                return false;
            }
            
            return true;
        });
    });
</script>
</body>
</html>
