<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $edit_id = $_POST['edit_id'] ?? null;
    $programme_id = isset($_POST['programme_id']) ? (int)$_POST['programme_id'] : null;  // FIX: cast to int
    $batch_id = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : null;              // FIX: cast to int
    $course_fee_lkr = $_POST['course_fee_lkr'] ?? null;
    $uni_fee_gbp = $_POST['uni_fee_gbp'] ?? null;
    $uni_fee_usd = $_POST['uni_fee_usd'] ?? null;
    $uni_fee_euro = $_POST['uni_fee_euro'] ?? null;
    $register_date = $_POST['register_date'] ?? null;
    $installment_no = isset($_POST['installment_no']) ? (int)$_POST['installment_no'] : null; // FIX: cast to int
    $registration_fee = $_POST['registration_fee'] ?? null;
    $only_course_fee = $_POST['only_course_fee'] ?? null;
    $installment_interval = $_POST['installment_interval'] ?? null; // keep as float/decimal (1, 1.5, 2, 2.5, 3)

    // Get installment data (using new names to avoid conflict)
    $inst_ids = $_POST['final_installment_id'] ?? [];
    $inst_nos = $_POST['final_installment_no'] ?? [];
    $inst_dates = $_POST['final_installment_date'] ?? [];
    $inst_amounts = $_POST['final_installment_amount'] ?? [];

    if (!is_numeric($course_fee_lkr) || !is_numeric($uni_fee_gbp) || !is_numeric($uni_fee_usd) || !is_numeric($uni_fee_euro) || !is_numeric($registration_fee) || !is_numeric($only_course_fee)) {
        echo "<script>alert('Please enter valid numeric values.');</script>";
        echo '<script>window.location.href = "program_payment_allocation.php";</script>'; // FIX: no HTTP_REFERER
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get program name, batch name, and category
        $program_name = '';
        $batch_name = '';
        $category = '';

        // FIX: bind_param type changed from "s" to "i" — programme_id is an integer
        $program_stmt = $conn->prepare("SELECT program_name, cetegory FROM program_table WHERE program_code = ?");
        $program_stmt->bind_param("i", $programme_id);
        $program_stmt->execute();
        $program_result = $program_stmt->get_result();
        if ($program_result->num_rows > 0) {
            $program_row = $program_result->fetch_assoc();
            $program_name = $program_row['program_name'];
            $category = $program_row['cetegory'];
        }
        $program_stmt->close();

        $batch_stmt = $conn->prepare("SELECT batch_name FROM batch_table WHERE id = ?");
        $batch_stmt->bind_param("i", $batch_id);
        $batch_stmt->execute();
        $batch_result = $batch_stmt->get_result();
        if ($batch_result->num_rows > 0) {
            $batch_row = $batch_result->fetch_assoc();
            $batch_name = $batch_row['batch_name'];
        }
        $batch_stmt->close();

        if ($edit_id) {
            // FIX: installment_interval is decimal (1, 1.5, 2, 2.5, 3) so keep as "d"
            // bind_param string: i=programme_id, i=batch_id, d=course_fee_lkr, d=uni_fee_gbp,
            //                    d=uni_fee_usd, d=uni_fee_euro, s=register_date, i=installment_no,
            //                    d=registration_fee, d=only_course_fee, d=installment_interval, i=edit_id
            $stmt = $conn->prepare("UPDATE payment_batch_allocation 
                SET programme_id = ?, batch_id = ?, course_fee_lkr = ?, uni_fee_gbp = ?, uni_fee_usd = ?, uni_fee_euro = ?, register_date = ?, installment_no = ?, registration_fee = ?, only_course_fee = ?, installment_interval = ?
                WHERE id = ?");
            $stmt->bind_param("iiddddsidddi", $programme_id, $batch_id, $course_fee_lkr, $uni_fee_gbp, $uni_fee_usd, $uni_fee_euro, $register_date, $installment_no, $registration_fee, $only_course_fee, $installment_interval, $edit_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // FIX: both programme_id and batch_id are integers — was "si", now "ii"
            $checkStmt = $conn->prepare("SELECT id FROM payment_batch_allocation WHERE programme_id = ? AND batch_id = ?");
            $checkStmt->bind_param("ii", $programme_id, $batch_id);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                echo "<script>alert('Record with this Programme and Batch already exists. You can update it.');</script>";
                echo '<script>window.location.href = "program_payment_allocation.php";</script>'; // FIX: no HTTP_REFERER
                $checkStmt->close();
                $conn->rollback();
                exit;
            }
            $checkStmt->close();

            // FIX: bind_param string corrected — "iiddddsiddd"
            // i=programme_id, i=batch_id, d=course_fee_lkr, d=uni_fee_gbp, d=uni_fee_usd,
            // d=uni_fee_euro, s=register_date, i=installment_no, d=registration_fee,
            // d=only_course_fee, d=installment_interval
            $stmt = $conn->prepare("INSERT INTO payment_batch_allocation 
                (programme_id, batch_id, course_fee_lkr, uni_fee_gbp, uni_fee_usd, uni_fee_euro, register_date, installment_no, registration_fee, only_course_fee, installment_interval)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddddsiddd", $programme_id, $batch_id, $course_fee_lkr, $uni_fee_gbp, $uni_fee_usd, $uni_fee_euro, $register_date, $installment_no, $registration_fee, $only_course_fee, $installment_interval);
            $stmt->execute();
            $stmt->close();
        }

        // Process installments for final year programs
        if ($category === 'final_year' && !empty($inst_nos)) {
            // First, collect all existing installment IDs
            $existing_ids = [];
            if ($edit_id) {
                $get_ids_stmt = $conn->prepare("SELECT id FROM final_yeat_instalment_data WHERE program_id = ? AND batch_id = ?");
                $get_ids_stmt->bind_param("ii", $programme_id, $batch_id);
                $get_ids_stmt->execute();
                $ids_result = $get_ids_stmt->get_result();
                while ($row = $ids_result->fetch_assoc()) {
                    $existing_ids[] = $row['id'];
                }
                $get_ids_stmt->close();
            }

            // Prepare update and insert statements
            $update_stmt = $conn->prepare("UPDATE final_yeat_instalment_data SET installment_no = ?, instalment_date = ?, instalment_amount = ? WHERE id = ?");
            $insert_stmt = $conn->prepare("INSERT INTO final_yeat_instalment_data (program_id, batch_id, program, batch, installment_no, instalment_date, instalment_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $submitted_ids = [];

            foreach ($inst_nos as $index => $inst_no) {
                $inst_id = $inst_ids[$index] ?? null;
                $inst_date = $inst_dates[$index] ?? '';
                $inst_amount = $inst_amounts[$index] ?? 0;

                if ($inst_id && in_array($inst_id, $existing_ids)) {
                    // Update existing installment
                    $update_stmt->bind_param("isdi", $inst_no, $inst_date, $inst_amount, $inst_id);
                    $update_stmt->execute();
                    $submitted_ids[] = $inst_id;
                } else {
                    // Insert new installment
                    $insert_stmt->bind_param("iissisd", $programme_id, $batch_id, $program_name, $batch_name, $inst_no, $inst_date, $inst_amount);
                    $insert_stmt->execute();
                    $new_id = $conn->insert_id;
                    $submitted_ids[] = $new_id;
                }
            }

            $update_stmt->close();
            $insert_stmt->close();

            // Delete any installments that weren't submitted (in case user reduced number of installments)
            if ($edit_id && !empty($existing_ids)) {
                $ids_to_delete = array_diff($existing_ids, $submitted_ids);
                if (!empty($ids_to_delete)) {
                    $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
                    $types = str_repeat('i', count($ids_to_delete));
                    $delete_stmt = $conn->prepare("DELETE FROM final_yeat_instalment_data WHERE id IN ($placeholders)");
                    $delete_stmt->bind_param($types, ...$ids_to_delete);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
            }
        } elseif ($edit_id) {
            // If the program is no longer a final year program, delete all existing installments
            $delete_all_stmt = $conn->prepare("DELETE FROM final_yeat_instalment_data WHERE program_id = ? AND batch_id = ?");
            $delete_all_stmt->bind_param("ii", $programme_id, $batch_id);
            $delete_all_stmt->execute();
            $delete_all_stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = $edit_id ? "Record updated successfully!" : "Record added successfully!";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        echo '<script>window.location.href = "program_payment_allocation.php";</script>'; // FIX: no HTTP_REFERER
        $conn->close();
        exit;
    }

    $conn->close();
    echo '<script>window.location.href = "program_payment_allocation.php";</script>'; // FIX: no HTTP_REFERER
    exit;
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
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Batch Payment Allocation</h4>
                </div>

                 <div class="row mb-5" id="paymentFormCard">
                    <div class="card">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-plus-circle"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0 me-2">Add Payment for particular Program</h6>
                        </div>

                        <form method="POST" class="mb-3" id="paymentForm">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="card mt-3">
                                        <div class="card-body shadow">
                                            <!-- Programme -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="programme">Programme</label></div>
                                                    <div class="col-md-9">
                                                        <select name="programme_id" id="programme" class="form-control select2" required>
                                                            <option value="">Select Programme</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Batch -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="batch">Batch</label></div>
                                                    <div class="col-md-9">
                                                        <select name="batch_id" id="batch" class="form-control select2" required>
                                                            <option value="">Select Batch</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Register Date -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="register_date">Register Date</label></div>
                                                    <div class="col-md-9">
                                                        <input type="date" class="form-control" name="register_date" id="register_date" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Installment No -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="installment_no">Installment No</label></div>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" name="installment_no" id="installment_no" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Registration Fee -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="registration_fee">Registration Fee</label></div>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" name="registration_fee" id="registration_fee" required min="0" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Installment Interval -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label for="installment_interval">Installment Interval</label></div>
                                                    <div class="col-md-9">
                                                        <select name="installment_interval" id="installment_interval" class="form-control" required>
                                                            <option value="1">1 Month</option>
                                                            <option value="1.5">1 and Half Month</option>
                                                            <option value="2">2 Months</option>
                                                            <option value="2.5">2 and Half Months</option>
                                                            <option value="3">3 Months</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Final Course Fee -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"><label>Final Course Fee</label></div>
                                                    <div class="col-md-9">
                                                        <p class="form-control-plaintext" id="final_course_fee">N/A</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="card mt-3 shadow-sm rounded-3 border-0">
                                        <div class="card-header bg-primary text-white font-weight-bold">
                                            Payment Details
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <strong>Course Fee (LKR):</strong> <span id="course_fee_lkr">N/A</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <strong>University Fee (GBP):</strong> <span id="course_fee_gbp">N/A</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <strong>University Fee (USD):</strong> <span id="course_fee_usd">N/A</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <strong>University Fee (Euro):</strong> <span id="course_fee_euro">N/A</span>
                                                </li>
                                            </ul>

                                            <!-- Hidden Inputs -->
                                            <input type="hidden" name="course_fee_lkr" id="hidden_course_fee_lkr">
                                            <input type="hidden" name="uni_fee_gbp" id="hidden_course_fee_gbp">
                                            <input type="hidden" name="uni_fee_usd" id="hidden_course_fee_usd">
                                            <input type="hidden" name="uni_fee_euro" id="hidden_course_fee_euro">
                                            <input type="hidden" name="only_course_fee" id="only_course_fee">
                                            <input type="hidden" name="edit_id" id="edit_id">
                                            <input type="hidden" id="program_category" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Final Year Installment Details Section -->
                            <div class="row mb-5" id="finalYearSection" style="display: none;">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Installment Details (Final Year)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="installmentTable">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th style="width: 20%;">Installment No</th>
                                                        <th style="width: 40%;">Installment Date</th>
                                                        <th style="width: 40%;">Installment Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="installmentTableBody">
                                                    <!-- Installment rows will be generated here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                                <button type="button" class="btn btn-secondary" id="cancelEdit" style="display:none;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Payment Batch Allocation Data</h6>
                        </div>
                        <div class="card-body">
                            <table id="paymentTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Programme Name</th>
                                        <th>Batch Name</th>
                                        <th>Course Fee (LKR)</th>
                                        <th>University Fee (GBP)</th>
                                        <th>University Fee (USD)</th>
                                        <th>University Fee (Euro)</th>
                                        <th>Register Date</th>
                                        <th>Installment No</th>
                                        <th>Registration Fee</th>
                                        <th>Installment Interval</th>
                                        <th>Only Course Fee</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated here by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    $('.select2').select2();
                    var isEditMode = false; // Flag to track edit mode

                    // Initialize DataTable
                    var table = $('#paymentTable').DataTable({
                        "pageLength": 100,
                        ajax: {
                            url: 'fetch_payment_batch_allocation.php',
                            dataSrc: ''
                        },
                        columns: [{
                                data: 'program_name'
                            },
                            {
                                data: 'batch_name'
                            },
                            {
                                data: 'course_fee_lkr'
                            },
                            {
                                data: 'uni_fee_gbp'
                            },
                            {
                                data: 'uni_fee_usd'
                            },
                            {
                                data: 'uni_fee_euro'
                            },
                            {
                                data: 'register_date'
                            },
                            {
                                data: 'installment_no'
                            },
                            {
                                data: 'registration_fee'
                            },
                            {
                                data: 'installment_interval'
                            },
                            {
                                data: 'only_course_fee'
                            },
                            {
                                data: null,
                                render: function(data, type, row) {
                                    return `<button class="btn btn-warning btn-sm edit-btn" data-id="${row.id}">Edit</button>`;
                                }
                            }
                        ]
                    });

                    // Load programmes
                    $.ajax({
                        url: "transection_exams/fetch_programmes.php",
                        method: "GET",
                        dataType: "json",
                        success: function(data) {
                            let programme = $('#programme');
                            programme.html('<option value="">Select Programme</option>');
                            data.forEach(function(item) {
                                programme.append(`<option value="${item.program_code}">${item.program_name}</option>`);
                            });
                        }
                    });

                    // Handle edit button click
                    $('#paymentTable tbody').on('click', '.edit-btn', function() {
                        var data = table.row($(this).parents('tr')).data();
                        isEditMode = true; // Set edit mode flag
                        
                        
                        // Scroll to and highlight the form card
                        $('html, body').animate({
                            scrollTop: $('#paymentFormCard').offset().top - 20
                        }, 500);

                        // Add temporary highlight effect
                        $('#paymentFormCard').css('background-color', '#fff3cd');
                        setTimeout(function() {
                            $('#paymentFormCard').css('background-color', '');
                        }, 1000);
                        

                        // Populate the form with the row data
                        $('#edit_id').val(data.id);
                        $('#programme').val(data.programme_id).trigger('change');
                        $('#hidden_course_fee_lkr').val(data.course_fee_lkr);
                        $('#hidden_course_fee_gbp').val(data.uni_fee_gbp);
                        $('#hidden_course_fee_usd').val(data.uni_fee_usd);
                        $('#hidden_course_fee_euro').val(data.uni_fee_euro);
                        $('#registration_fee').val(data.registration_fee);
                        $('#installment_no').val(data.installment_no);
                        $('#installment_interval').val(data.installment_interval);
                        $('#register_date').val(data.register_date);
                        $('#only_course_fee').val(data.only_course_fee);

                        // Update the displayed values
                        $('#course_fee_lkr').text(data.course_fee_lkr || 'N/A');
                        $('#course_fee_gbp').text(data.uni_fee_gbp || 'N/A');
                        $('#course_fee_usd').text(data.uni_fee_usd || 'N/A');
                        $('#course_fee_euro').text(data.uni_fee_euro || 'N/A');
                        $('#final_course_fee').text(data.only_course_fee || '0.00');

                        // Change the submit button text and show cancel button
                        $('#submitBtn').text('Update Record');
                        $('#cancelEdit').show();

                        // Load batches for the selected programme
                        loadBatches(data.programme_id, data.batch_id);

                        // After loading batches, check if it's final year and fetch installments
                        setTimeout(function() {
                            if ($('#program_category').val() === 'final_year') {
                                // Fetch existing installments using program_id and batch_id (more reliable)
                                $.ajax({
                                    url: 'fetch_final_year_installments.php',
                                    method: 'POST',
                                    data: {
                                        program_id: data.programme_id,
                                        batch_id: data.batch_id
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.installments && response.installments.length > 0) {
                                            // Populate the installment table
                                            const tbody = $('#installmentTableBody');
                                            tbody.empty();
                                            response.installments.forEach(function(inst) {
                                                const row = $('<tr></tr>');
                                                row.append(`
                                                    <td>
                                                        <input type="hidden" name="final_installment_id[]" value="${inst.id}">
                                                        <input type="text" class="form-control" name="final_installment_no[]" value="${inst.installment_no}" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="date" class="form-control installment-date" name="final_installment_date[]" value="${inst.instalment_date}">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control installment-amount" name="final_installment_amount[]" value="${inst.instalment_amount}" step="0.01" min="0">
                                                    </td>
                                                `);
                                                tbody.append(row);
                                            });
                                        } else {
                                            // Generate default installments
                                            generateInstallmentTable();
                                        }
                                    }
                                });
                            }
                        }, 500); // Small delay to ensure batches are loaded

                        // Clear edit mode flag after setup so user can manually change programme if needed
                        setTimeout(function() {
                            isEditMode = false;
                        }, 600);
                    });

                    // Cancel edit mode
                    $('#cancelEdit').click(function() {
                        resetForm();
                    });

                    // Function to load batches based on selected programme
                    function loadBatches(programmeId, selectedBatchId) {
                        $.ajax({
                            url: "transection_exams/fetch_batches.php",
                            method: "POST",
                            data: {
                                programme_id: programmeId
                            },
                            dataType: "json",
                            success: function(data) {
                                let batch = $('#batch');
                                batch.html('<option value="">Select Batch</option>');
                                data.forEach(function(item) {
                                    batch.append(`<option value="${item.id}" ${item.id == selectedBatchId ? 'selected' : ''}>${item.batch_name}</option>`);
                                });
                                batch.trigger('change');
                            }
                        });
                    }

                    // Reset form to add new record
                    function resetForm() {
                        isEditMode = false; // Clear edit mode flag
                        $('#paymentForm')[0].reset();
                        $('#edit_id').val('');
                        $('#final_course_fee').text('N/A');
                        $('#course_fee_lkr').text('N/A');
                        $('#course_fee_gbp').text('N/A');
                        $('#course_fee_usd').text('N/A');
                        $('#course_fee_euro').text('N/A');
                        $('#installment_interval').val('1'); // Reset to default value
                        $('#submitBtn').text('Submit');
                        $('#cancelEdit').hide();
                        $('#programme').val('').trigger('change');
                    }

                    // Programme change handler
                    $('#programme').change(function() {
                        let programmeId = $(this).val();
                        $('#batch').html('<option value="">Select Batch</option>');

                        if (programmeId) {
                            // Load batches
                            $.ajax({
                                url: "transection_exams/fetch_batches.php",
                                method: "POST",
                                data: {
                                    programme_id: programmeId
                                },
                                dataType: "json",
                                success: function(data) {
                                    let batch = $('#batch');
                                    batch.html('<option value="">Select Batch</option>');
                                    data.forEach(function(item) {
                                        batch.append(`<option value="${item.id}">${item.batch_name}</option>`);
                                    });
                                }
                            });

                            // Load fees (only if not in edit mode)
                            if (!isEditMode) {
                                $.ajax({
                                    url: "transection_exams/fetch_course_fees.php",
                                    method: "POST",
                                    data: {
                                        programme_id: programmeId
                                    },
                                    dataType: "json",
                                    success: function(fees) {
                                        $('#course_fee_lkr').text(fees.course_fee_lkr || 'N/A');
                                        $('#course_fee_gbp').text(fees.course_fee_gbp || 'N/A');
                                        $('#course_fee_usd').text(fees.course_fee_usd || 'N/A');
                                        $('#course_fee_euro').text(fees.course_fee_euro || 'N/A');
                                        $('#program_category').val(fees.cetegory || '');

                                        $('#hidden_course_fee_lkr').val(fees.course_fee_lkr || '');
                                        $('#hidden_course_fee_gbp').val(fees.course_fee_gbp || '');
                                        $('#hidden_course_fee_usd').val(fees.course_fee_usd || '');
                                        $('#hidden_course_fee_euro').val(fees.course_fee_euro || '');
                                        calculateFinalFee();

                                        // Check if final year
                                        if (fees.cetegory === 'final_year') {
                                            $('#finalYearSection').show();
                                        } else {
                                            $('#finalYearSection').hide();
                                        }
                                    }
                                });
                            } else {
                                // In edit mode, we still need to get the program category to show/hide the final year section
                                $.ajax({
                                    url: "transection_exams/fetch_course_fees.php",
                                    method: "POST",
                                    data: {
                                        programme_id: programmeId
                                    },
                                    dataType: "json",
                                    success: function(fees) {
                                        $('#program_category').val(fees.cetegory || '');

                                        // Check if final year
                                        if (fees.cetegory === 'final_year') {
                                            $('#finalYearSection').show();
                                        } else {
                                            $('#finalYearSection').hide();
                                        }
                                    }
                                });
                            }
                        }
                    });

                    // Calculate final fee when registration fee changes
                    $('#registration_fee').on('input', function() {
                        calculateFinalFee();
                    });

                    // Re-generate installments when any of these fields change
                    $('#installment_no, #register_date, #installment_interval, #registration_fee').on('input change', function() {
                        if ($('#program_category').val() === 'final_year') {
                            generateInstallmentTable();
                        }
                    });

                    function calculateFinalFee() {
                        const courseFee = parseFloat($('#hidden_course_fee_lkr').val()) || 0;
                        const regFee = parseFloat($('#registration_fee').val()) || 0;
                        const finalFee = courseFee - regFee;

                        if (regFee > courseFee) {
                            alert("Registration Fee cannot be more than the Course Fee (" + courseFee.toFixed(2) + ")");
                            $('#registration_fee').val('');
                            $('#final_course_fee').text('0.00');
                            $('#only_course_fee').val('0.00');
                        } else {
                            $('#final_course_fee').text(finalFee > 0 ? finalFee.toFixed(2) : '0.00');
                            $('#only_course_fee').val(finalFee > 0 ? finalFee.toFixed(2) : '0.00');
                        }

                        // If final year, regenerate installments
                        if ($('#program_category').val() === 'final_year') {
                            generateInstallmentTable();
                        }
                    }

                    // Function to generate the installment table
                    function generateInstallmentTable() {
                        const installmentNo = parseInt($('#installment_no').val()) || 0;
                        const finalCourseFee = parseFloat($('#only_course_fee').val()) || 0;
                        const installmentInterval = parseFloat($('#installment_interval').val()) || 1;
                        const registerDate = $('#register_date').val();
                        const tbody = $('#installmentTableBody');

                        tbody.empty();

                        if (installmentNo <= 0 || finalCourseFee <= 0 || !registerDate) {
                            return;
                        }

                        const installmentAmount = finalCourseFee / installmentNo;
                        const baseDate = new Date(registerDate);

                        for (let i = 1; i <= installmentNo; i++) {
                            const dueDateObj = new Date(baseDate);

                            if (installmentInterval === 1) {
                                // For 1-month interval, add 1 month for each installment (including first)
                                dueDateObj.setMonth(dueDateObj.getMonth() + i);
                            } else if (i > 1) {
                                // For other intervals, first is base date, then add interval and set to 15th
                                const monthsToAdd = (i - 1) * installmentInterval;
                                const roundedMonths = Math.round(monthsToAdd);
                                dueDateObj.setMonth(dueDateObj.getMonth() + roundedMonths);
                                dueDateObj.setDate(15);
                            }

                            const dueDate = dueDateObj.toISOString().split('T')[0];

                            const row = $('<tr></tr>');
                            row.append(`
                                <td>
                                    <input type="hidden" name="final_installment_id[]" value="">
                                    <input type="text" class="form-control" name="final_installment_no[]" value="${i}" readonly>
                                </td>
                                <td>
                                    <input type="date" class="form-control installment-date" name="final_installment_date[]" data-index="${i}" value="${dueDate}">
                                </td>
                                <td>
                                    <input type="number" class="form-control installment-amount" name="final_installment_amount[]" data-index="${i}" value="${installmentAmount.toFixed(2)}" step="0.01" min="0">
                                </td>
                            `);
                            tbody.append(row);
                        }
                    }

                    // Form submission validation
                    $('#paymentForm').on('submit', function(e) {
                        const regFee = parseFloat($('#registration_fee').val()) || 0;
                        const courseFee = parseFloat($('#hidden_course_fee_lkr').val()) || 0;

                        if (regFee > courseFee) {
                            alert("Registration Fee cannot be more than the Course Fee.");
                            e.preventDefault();
                            return false;
                        }
                    });
                });
            </script>

            <!-- Include necessary JS files -->
            <script src="vendor/datatables/jquery.dataTables.min.js"></script>
            <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
            <link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
            <script src="js/demo/datatables-demo.js"></script>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        </div>
    </div>
</div>
</body>

</html>