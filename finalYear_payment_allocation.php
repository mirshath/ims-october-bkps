<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
// require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 




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
                    <h4 class="h4 mb-0 text-gray-800">Final Year Payment Allocation</h4>
                </div>

                <div class="row mb-5">
                    <div class="card">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-plus-circle"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0 me-2">Add Payment for particular Final Year Program</h6>
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
                                            <!-- Submit -->
                                            <div class="form-group text-right">
                                                <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                                                <button type="button" class="btn btn-secondary" id="cancelEdit" style="display:none;">Cancel</button>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            
            </div>

            <script>
                $(document).ready(function() {
                    $('.select2').select2();

                    // Initialize DataTable
                    var table = $('#paymentTable').DataTable({
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

                            // Load fees
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

                                    $('#hidden_course_fee_lkr').val(fees.course_fee_lkr || '');
                                    $('#hidden_course_fee_gbp').val(fees.course_fee_gbp || '');
                                    $('#hidden_course_fee_usd').val(fees.course_fee_usd || '');
                                    $('#hidden_course_fee_euro').val(fees.course_fee_euro || '');
                                    calculateFinalFee();
                                }
                            });
                        }
                    });

                    // Calculate final fee when registration fee changes
                    $('#registration_fee').on('input', function() {
                        calculateFinalFee();
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